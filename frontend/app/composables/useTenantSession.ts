import { computed } from "vue";

/**
 * Resolves the *current* tenant's record id — the single binding point
 * between the signed-in tenant user and the tenant-scoped data layer.
 *
 * Mock/demo: the signed-in tenant is bound to the richest seeded tenant
 * (Aminah — active agreement at Suria KLCC, paid + outstanding invoices,
 * open + resolved issues) so every tenant surface has realistic data to
 * show. Backend swap: a real tenant's auth-user id *is* their tenant id,
 * so this collapses to `auth.user.id` and the mock branch falls away.
 */
const DEMO_TENANT_ID = "t-aminah";

export const useTenantSession = () => {
  const { useMock } = useEnv();
  const auth = useAuthStore();

  const tenantId = computed<string | null>(() =>
    useMock ? DEMO_TENANT_ID : (auth.user?.id ?? null),
  );

  return { tenantId };
};
