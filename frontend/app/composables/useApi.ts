/**
 * Sanctum-aware $fetch wrapper.
 *
 * - Sends the session cookie on every call (`credentials: "include"`).
 * - Attaches the `X-XSRF-TOKEN` header from the `XSRF-TOKEN` cookie Sanctum
 *   sets after `GET /sanctum/csrf-cookie`, so state-changing requests pass
 *   CSRF verification.
 * - On a 401 (other than the auth-probe endpoints, which legitimately 401
 *   when logged out or on a bad password), clears auth state and bounces to
 *   the login page.
 * - Leaves 422s to throw as ofetch FetchErrors with `.data = { message, errors }`
 *   intact, so forms can map them via `useApiError().toFieldErrors`.
 */
const readXsrfToken = (): string | null => {
  if (!import.meta.client) return null;
  const match = document.cookie
    .split("; ")
    .find((row) => row.startsWith("XSRF-TOKEN="));
  return match ? decodeURIComponent(match.split("=")[1] ?? "") : null;
};

export const useApi = () => {
  const config = useRuntimeConfig();
  const baseURL = config.public.apiBase;

  const request = $fetch.create({
    baseURL,
    credentials: "include",
    headers: { Accept: "application/json" },
    onRequest({ options }) {
      const token = readXsrfToken();
      if (token) {
        options.headers = new Headers(options.headers);
        options.headers.set("X-XSRF-TOKEN", token);
      }
    },
    onResponseError({ request: req, response }) {
      if (response.status !== 401) return;
      const url = typeof req === "string" ? req : req.url;
      // Auth probes are allowed to 401 without a redirect: /auth/me is the
      // boot "am I logged in?" check, /auth/login is a failed sign-in.
      if (url.includes("/auth/me") || url.includes("/auth/login")) return;

      const auth = useAuthStore();
      auth.$patch({ user: null });
      navigateTo("/auth/login");
    },
  });

  return { request, baseURL };
};
