export type UserRole = "owner" | "tenant" | "admin";

export interface AuthUser {
  id: string;
  name: string;
  email: string;
  phone: string | null;
  role: UserRole;
}
