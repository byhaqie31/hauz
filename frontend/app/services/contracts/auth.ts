import type { AuthUser } from "~/types/auth";

export interface RegisterPayload {
  name: string;
  email: string;
  phone: string;
  password: string;
}

export interface AuthAdapter {
  login(email: string, password: string): Promise<AuthUser>;
  register(payload: RegisterPayload): Promise<AuthUser>;
  logout(): Promise<void>;
  /** Boot hydration. Resolves `null` when not signed in — that case never throws. */
  fetchMe(): Promise<AuthUser | null>;
}
