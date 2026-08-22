import type { Tenant } from "~/types/tenant";
import type { TenantsService } from "~/services/contracts/tenants";
import { tenantsMock } from "~/demo/data/tenants";

export const demoTenants: TenantsService = {
  async getTenants() {
    return structuredClone(tenantsMock);
  },

  async getTenant(id) {
    const found = tenantsMock.find((t) => t.id === id);
    return found ? structuredClone(found) : null;
  },

  async invite(input) {
    const now = new Date().toISOString();
    const created: Tenant = {
      id: crypto.randomUUID(),
      ...input,
      status: "invited",
      invitedAt: now,
      createdAt: now,
    };
    tenantsMock.push(created);
    return structuredClone(created);
  },

  async update(id, patch) {
    const idx = tenantsMock.findIndex((t) => t.id === id);
    if (idx === -1) throw new Error(`Tenant ${id} not found`);
    const existing = tenantsMock[idx]!;
    const merged: Tenant = {
      ...existing,
      ...patch,
      personal: patch.personal
        ? { ...(existing.personal ?? {}), ...patch.personal }
        : existing.personal,
      emergencyContact: patch.emergencyContact
        ? { ...(existing.emergencyContact ?? {}), ...patch.emergencyContact }
        : existing.emergencyContact,
    };
    tenantsMock[idx] = merged;
    return structuredClone(merged);
  },

  async remove(id) {
    const idx = tenantsMock.findIndex((t) => t.id === id);
    if (idx !== -1) tenantsMock.splice(idx, 1);
  },
};
