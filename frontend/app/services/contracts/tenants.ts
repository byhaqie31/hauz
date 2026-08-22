import type { Tenant, TenantInput, TenantUpdate } from "~/types/tenant";

export interface TenantsService {
  getTenants(): Promise<Tenant[]>;
  getTenant(id: string): Promise<Tenant | null>;
  invite(input: TenantInput): Promise<Tenant>;
  update(id: string, patch: TenantUpdate): Promise<Tenant>;
  remove(id: string): Promise<void>;
}
