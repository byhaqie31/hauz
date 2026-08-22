import type { Invoice } from "~/types/invoice";
import type {
  InvoicesService,
  InvoiceWithRefs,
} from "~/services/contracts/invoices";

const EXPAND = "expand=agreement,unit,property,tenant,payments";

export const apiInvoices: InvoicesService = {
  getInvoices: () => useApi().request<Invoice[]>("/invoices"),

  getInvoicesWithRefs: () =>
    useApi().request<InvoiceWithRefs[]>(`/invoices?${EXPAND}`),

  getInvoice: (id) => useApi().request<Invoice>(`/invoices/${id}`),

  getInvoicesForTenant: () =>
    useApi().request<InvoiceWithRefs[]>(`/me/invoices?${EXPAND}`),

  updateStatus: (id, status) =>
    useApi().request<Invoice>(`/invoices/${id}`, {
      method: "PATCH",
      body: { status },
    }),

  recordPayment: (input) =>
    useApi().request(`/invoices/${input.invoiceId}/payments`, {
      method: "POST",
      body: input,
    }),

  sendInvoice: (id) =>
    useApi().request(`/invoices/${id}/send`, { method: "POST" }),
};
