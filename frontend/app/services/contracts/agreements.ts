import type {
  Agreement,
  AgreementInput,
  AgreementUpdate,
} from "~/types/agreement";
import type { Property } from "~/types/property";
import type { Unit } from "~/types/unit";
import type { Tenant } from "~/types/tenant";

export interface AgreementWithRefs {
  agreement: Agreement;
  unit: Unit | null;
  property: Property | null;
  tenant: Tenant | null;
}

export interface AgreementsService {
  getAgreements(): Promise<Agreement[]>;
  getAgreementsWithRefs(): Promise<AgreementWithRefs[]>;
  getAgreement(id: string): Promise<Agreement | null>;
  /**
   * Tenant-shell scope: the tenant's *current* agreement — active, else the
   * most recent non-draft. API: `/me/agreement` (server knows who's asking).
   */
  getActiveAgreementForTenant(
    tenantId: string,
  ): Promise<AgreementWithRefs | null>;
  create(input: AgreementInput): Promise<Agreement>;
  update(id: string, patch: AgreementUpdate): Promise<Agreement>;
  remove(id: string): Promise<void>;
}
