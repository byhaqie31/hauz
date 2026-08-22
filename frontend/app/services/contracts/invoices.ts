import type { Invoice, InvoiceStatus } from "~/types/invoice";
import type { Payment, PaymentInput, PaymentMethod } from "~/types/payment";
import type { Agreement } from "~/types/agreement";
import type { Property } from "~/types/property";
import type { Unit } from "~/types/unit";
import type { Tenant } from "~/types/tenant";

export interface InvoiceWithRefs {
  invoice: Invoice;
  agreement: Agreement | null;
  unit: Unit | null;
  property: Property | null;
  tenant: Tenant | null;
  payments: Payment[];
}

export interface InvoicesService {
  getInvoices(): Promise<Invoice[]>;
  getInvoicesWithRefs(): Promise<InvoiceWithRefs[]>;
  getInvoice(id: string): Promise<Invoice | null>;
  /** Tenant-shell scope: invoices across all of the tenant's agreements. API: `/me/invoices`. */
  getInvoicesForTenant(tenantId: string): Promise<InvoiceWithRefs[]>;
  updateStatus(id: string, status: InvoiceStatus): Promise<Invoice>;
  recordPayment(
    input: PaymentInput,
  ): Promise<{ payment: Payment; invoice: Invoice }>;
  sendInvoice(id: string): Promise<{ sentAt: string }>;
  /**
   * Tenant-shell scope: the tenant pays one of their own invoices. The server
   * computes amount / paidAt / status; the client only picks the method.
   * API: `POST /me/invoices/{id}/pay`.
   */
  payForTenant(
    invoiceId: string,
    method: PaymentMethod,
  ): Promise<{ payment: Payment; invoice: Invoice }>;
}
