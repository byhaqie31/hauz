import type { Invoice } from "~/types/invoice";
import type { Payment } from "~/types/payment";
import type {
  InvoicesService,
  InvoiceWithRefs,
} from "~/services/contracts/invoices";
import { invoicesMock, paymentsMock } from "~/demo/data/invoices";
import { agreementsMock } from "~/demo/data/agreements";
import { unitsMock } from "~/demo/data/units";
import { propertiesMock } from "~/demo/data/properties";
import { tenantsMock } from "~/demo/data/tenants";

const hydrate = (inv: Invoice): InvoiceWithRefs => {
  const agreement =
    agreementsMock.find((a) => a.id === inv.agreementId) ?? null;
  const unit = agreement
    ? (unitsMock.find((u) => u.id === agreement.unitId) ?? null)
    : null;
  const property = unit
    ? (propertiesMock.find((p) => p.id === unit.propertyId) ?? null)
    : null;
  const tenant = agreement
    ? (tenantsMock.find((t) => t.id === agreement.tenantId) ?? null)
    : null;
  const payments = paymentsMock.filter((p) => p.invoiceId === inv.id);
  return {
    invoice: structuredClone(inv),
    agreement: agreement ? structuredClone(agreement) : null,
    unit: unit ? structuredClone(unit) : null,
    property: property ? structuredClone(property) : null,
    tenant: tenant ? structuredClone(tenant) : null,
    payments: structuredClone(payments),
  };
};

export const demoInvoices: InvoicesService = {
  async getInvoices() {
    return structuredClone(invoicesMock);
  },

  async getInvoicesWithRefs() {
    return invoicesMock.map(hydrate);
  },

  async getInvoice(id) {
    const found = invoicesMock.find((i) => i.id === id);
    return found ? structuredClone(found) : null;
  },

  async getInvoicesForTenant(tenantId) {
    const agreementIds = new Set(
      agreementsMock.filter((a) => a.tenantId === tenantId).map((a) => a.id),
    );
    return invoicesMock
      .filter((i) => agreementIds.has(i.agreementId))
      .map(hydrate);
  },

  async updateStatus(id, status) {
    const idx = invoicesMock.findIndex((i) => i.id === id);
    if (idx === -1) throw new Error(`Invoice ${id} not found`);
    invoicesMock[idx] = { ...invoicesMock[idx]!, status };
    return structuredClone(invoicesMock[idx]!);
  },

  async recordPayment(input) {
    const now = new Date().toISOString();
    const payment: Payment = {
      id: crypto.randomUUID(),
      ...input,
      status: "successful",
      createdAt: now,
    };
    paymentsMock.push(payment);
    const idx = invoicesMock.findIndex((i) => i.id === input.invoiceId);
    if (idx === -1) throw new Error(`Invoice ${input.invoiceId} not found`);
    invoicesMock[idx] = { ...invoicesMock[idx]!, status: "paid" };
    return {
      payment: structuredClone(payment),
      invoice: structuredClone(invoicesMock[idx]!),
    };
  },

  async sendInvoice() {
    // No persistent state for "lastSentAt" in demo — the backend owns that.
    return { sentAt: new Date().toISOString() };
  },

  async payForTenant(invoiceId, method) {
    // Stand in for the FPX redirect round-trip, then mirror what the API
    // does: amount = amount + lateFee, paidAt = now, invoice → paid.
    await new Promise((r) => setTimeout(r, 900));
    const idx = invoicesMock.findIndex((i) => i.id === invoiceId);
    if (idx === -1) throw new Error(`Invoice ${invoiceId} not found`);
    const inv = invoicesMock[idx]!;
    const now = new Date().toISOString();
    const payment: Payment = {
      id: crypto.randomUUID(),
      invoiceId,
      amount: inv.amount + inv.lateFee,
      method,
      status: "successful",
      paidAt: now,
      reference: `${method.toUpperCase()}-${Date.now().toString().slice(-8)}`,
      createdAt: now,
    };
    paymentsMock.push(payment);
    invoicesMock[idx] = { ...inv, status: "paid" };
    return {
      payment: structuredClone(payment),
      invoice: structuredClone(invoicesMock[idx]!),
    };
  },
};
