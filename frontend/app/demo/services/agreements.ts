import type { Agreement } from "~/types/agreement";
import type {
  AgreementsService,
  AgreementWithRefs,
} from "~/services/contracts/agreements";
import { agreementsMock } from "~/demo/data/agreements";
import { propertiesMock } from "~/demo/data/properties";
import { unitsMock } from "~/demo/data/units";
import { tenantsMock } from "~/demo/data/tenants";

const hydrate = (a: Agreement): AgreementWithRefs => {
  const unit = unitsMock.find((u) => u.id === a.unitId) ?? null;
  const property = unit
    ? (propertiesMock.find((p) => p.id === unit.propertyId) ?? null)
    : null;
  const tenant = tenantsMock.find((t) => t.id === a.tenantId) ?? null;
  return {
    agreement: structuredClone(a),
    unit: unit ? structuredClone(unit) : null,
    property: property ? structuredClone(property) : null,
    tenant: tenant ? structuredClone(tenant) : null,
  };
};

export const demoAgreements: AgreementsService = {
  async getAgreements() {
    return structuredClone(agreementsMock);
  },

  async getAgreementsWithRefs() {
    return agreementsMock.map(hydrate);
  },

  async getAgreement(id) {
    const found = agreementsMock.find((a) => a.id === id);
    return found ? structuredClone(found) : null;
  },

  async getActiveAgreementForTenant(tenantId) {
    const mine = agreementsMock.filter((a) => a.tenantId === tenantId);
    const current =
      mine.find((a) => a.status === "active") ??
      mine
        .filter((a) => a.status !== "draft")
        .sort((a, b) => b.startDate.localeCompare(a.startDate))[0] ??
      null;
    return current ? hydrate(current) : null;
  },

  async create(input) {
    const created: Agreement = {
      id: crypto.randomUUID(),
      ...input,
      createdAt: new Date().toISOString(),
    };
    agreementsMock.push(created);
    return structuredClone(created);
  },

  async update(id, patch) {
    const idx = agreementsMock.findIndex((a) => a.id === id);
    if (idx === -1) throw new Error(`Agreement ${id} not found`);
    const merged: Agreement = { ...agreementsMock[idx]!, ...patch };
    agreementsMock[idx] = merged;
    return structuredClone(merged);
  },

  async remove(id) {
    const idx = agreementsMock.findIndex((a) => a.id === id);
    if (idx !== -1) agreementsMock.splice(idx, 1);
  },
};
