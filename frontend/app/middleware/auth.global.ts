/**
 * Auth + role gate for the app shells.
 *
 * Only `/owner/*` and `/tenant/*` are protected — marketing, `/auth/*`,
 * `/demo/*`, and `/coming-soon` keep their own routing (see demo-only.global.ts;
 * its path set is disjoint from this one, so ordering doesn't matter).
 *
 * On a hard refresh of a protected page the boot plugin's `fetchMe()` may
 * still be in flight; we await `authReady` so we never bounce a logged-in
 * user to /login mid-hydration. On the server pass (SSR) `authReady` is
 * false and there's no session cookie access, so we skip — the client
 * plugin + this same guard re-run on hydration.
 */
export default defineNuxtRouteMiddleware(async (to) => {
  const isOwnerArea = to.path === "/owner" || to.path.startsWith("/owner/");
  const isTenantArea = to.path === "/tenant" || to.path.startsWith("/tenant/");
  if (!isOwnerArea && !isTenantArea) return;

  // SSR has no session cookie here; let the client guard decide post-hydration.
  if (import.meta.server) return;

  const auth = useAuthStore();
  // Wait out the boot hydration if it hasn't settled yet.
  if (!auth.authReady) {
    await auth.fetchMe();
  }

  if (!auth.isAuthenticated) {
    return navigateTo("/auth/login");
  }

  const inWrongShell =
    (isOwnerArea && !auth.isOwner) || (isTenantArea && !auth.isTenant);
  if (inWrongShell) {
    return navigateTo(auth.isTenant ? "/tenant" : "/owner");
  }
});
