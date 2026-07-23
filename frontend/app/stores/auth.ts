import { defineStore } from "pinia";

export type UserRole = "owner" | "tenant" | "admin";

export interface AuthUser {
  id: string;
  name: string;
  email: string;
  phone: string | null;
  role: UserRole;
}

interface AuthState {
  user: AuthUser | null;
  loading: boolean;
  /** False until the boot `fetchMe()` has settled — the route guard waits on this. */
  authReady: boolean;
}

interface RegisterPayload {
  name: string;
  email: string;
  phone: string;
  password: string;
}

/**
 * Real Sanctum SPA cookie auth. The httpOnly session cookie is the single
 * source of truth — no localStorage. `login`/`register` prime the CSRF
 * cookie then POST; `fetchMe` hydrates on boot; the route guard waits on
 * `authReady`.
 */
export const useAuthStore = defineStore("auth", {
  state: (): AuthState => ({
    user: null,
    loading: false,
    authReady: false,
  }),

  getters: {
    isAuthenticated: (s) => s.user !== null,
    isOwner: (s) => s.user?.role === "owner",
    isTenant: (s) => s.user?.role === "tenant",
    isAdmin: (s) => s.user?.role === "admin",
  },

  actions: {
    async login(email: string, password: string) {
      this.loading = true;
      try {
        const { request } = useApi();
        // Prime the CSRF cookie before the stateful POST.
        await request("/../sanctum/csrf-cookie");
        const res = await request<{ user: AuthUser }>("/auth/login", {
          method: "POST",
          body: { email, password },
        });
        this.user = res.user;
      } finally {
        this.loading = false;
      }
    },

    async register(payload: RegisterPayload) {
      this.loading = true;
      try {
        const { request } = useApi();
        await request("/../sanctum/csrf-cookie");
        const res = await request<{ user: AuthUser }>("/auth/register", {
          method: "POST",
          body: {
            ...payload,
            password_confirmation: payload.password,
          },
        });
        this.user = res.user;
      } finally {
        this.loading = false;
      }
    },

    async logout() {
      try {
        const { request } = useApi();
        await request("/auth/logout", { method: "POST" });
      } catch {
        // Even if the server call fails, drop local state.
      }
      this.user = null;
    },

    /**
     * Boot hydration: ask the server who we are. A 401 is the expected
     * "not logged in" case, not an error to surface. Always marks the
     * session ready so the route guard can proceed.
     */
    async fetchMe() {
      try {
        const { request } = useApi();
        this.user = await request<AuthUser>("/auth/me");
      } catch {
        this.user = null;
      } finally {
        this.authReady = true;
      }
    },
  },
});
