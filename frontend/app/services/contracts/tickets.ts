import type {
  Ticket,
  TicketComment,
  TicketCommentInput,
  TicketInput,
  TicketStatus,
} from "~/types/ticket";
import type { Property } from "~/types/property";
import type { Unit } from "~/types/unit";
import type { Tenant } from "~/types/tenant";

export interface TicketWithRefs {
  ticket: Ticket;
  unit: Unit | null;
  property: Property | null;
  reporter: Tenant | null; // null when reporterRole === "owner"
  comments: TicketComment[];
}

export interface TicketsService {
  getTickets(): Promise<Ticket[]>;
  getTicketsWithRefs(): Promise<TicketWithRefs[]>;
  getTicket(id: string): Promise<Ticket | null>;
  getTicketWithRefs(id: string): Promise<TicketWithRefs | null>;
  /** Tenant-shell scope: issues the tenant reported themselves. API: `/me/tickets`. */
  getTicketsForTenant(tenantId: string): Promise<TicketWithRefs[]>;
  create(input: TicketInput): Promise<Ticket>;
  transitionStatus(id: string, next: TicketStatus): Promise<Ticket>;
  addComment(input: TicketCommentInput): Promise<TicketComment>;
}
