import type {
  Tenant,
  TenantEmergencyContact,
  TenantInput,
  TenantPersonal,
  TenantUpdate,
} from "~/types/tenant";

/** What a tenant sees of themselves — no owner-side fields (status, invitedAt). */
export type TenantProfile = Pick<
  Tenant,
  "id" | "name" | "email" | "phone" | "personal" | "emergencyContact"
>;

/** Email is the login identity and is not editable from the profile. */
export interface TenantProfileUpdate {
  name?: string;
  phone?: string;
  personal?: TenantPersonal;
  emergencyContact?: TenantEmergencyContact;
}

export interface TenantsService {
  getTenants(): Promise<Tenant[]>;
  getTenant(id: string): Promise<Tenant | null>;
  invite(input: TenantInput): Promise<Tenant>;
  update(id: string, patch: TenantUpdate): Promise<Tenant>;
  remove(id: string): Promise<void>;

  // ── Tenant-shell scope (`/me/profile`) ────────────────────────────────
  /** The signed-in tenant's own profile. `tenantId` is only used by demo. */
  getProfile(tenantId: string): Promise<TenantProfile | null>;
  updateProfile(
    tenantId: string,
    patch: TenantProfileUpdate,
  ): Promise<TenantProfile>;
}
