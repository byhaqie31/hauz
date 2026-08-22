import type { Tenant } from "~/types/tenant";
import type { TenantsService } from "~/services/contracts/tenants";

export const apiTenants: TenantsService = {
  getTenants: () => useApi().request<Tenant[]>("/tenants"),

  getTenant: (id) => useApi().request<Tenant>(`/tenants/${id}`),

  invite: (input) =>
    useApi().request<Tenant>("/tenants/invite", { method: "POST", body: input }),

  update: (id, patch) =>
    useApi().request<Tenant>(`/tenants/${id}`, { method: "PATCH", body: patch }),

  remove: async (id) => {
    await useApi().request(`/tenants/${id}`, { method: "DELETE" });
  },
};
