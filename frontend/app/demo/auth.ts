import type { AuthUser, UserRole } from "~/types/auth";
import type { AuthAdapter } from "~/services/contracts/auth";

/**
 * Demo auth. No backend: the email prefix decides the role, and the user is
 * persisted to localStorage so a refresh keeps the demo session (there is no
 * session cookie in demo). Never touches the network.
 *
 * A demo tenant signs in as the richest seeded tenant (Aminah — active
 * agreement at Suria KLCC, paid + outstanding invoices, open + resolved
 * issues) so every tenant surface has data. `useTenantSession` binds to the
 * same id, so the two cannot drift.
 */
export const DEMO_TENANT_ID = "t-aminah";
/** Matches `demo/data/owner.ts` profile.id so settings hydrates for the demo owner. */
const DEMO_OWNER_ID = "stub-owner";

const STORAGE_KEY = "roofly_auth";

const persist = (user: AuthUser | null) => {
  if (!import.meta.client) return;
  try {
    if (user) localStorage.setItem(STORAGE_KEY, JSON.stringify(user));
    else localStorage.removeItem(STORAGE_KEY);
  } catch {
    // Quota / private mode — non-fatal in demo.
  }
};

const restore = (): AuthUser | null => {
  if (!import.meta.client) return null;
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    return raw ? (JSON.parse(raw) as AuthUser) : null;
  } catch {
    localStorage.removeItem(STORAGE_KEY);
    return null;
  }
};

const roleFor = (email: string): UserRole =>
  email.startsWith("tenant")
    ? "tenant"
    : email.startsWith("admin")
      ? "admin"
      : "owner";

const userFor = (email: string, role: UserRole): AuthUser =>
  role === "tenant"
    ? {
        id: DEMO_TENANT_ID,
        name: "Aminah Binti Yusof",
        email,
        phone: "+60 12-345 6789",
        role,
      }
    : {
        id: role === "admin" ? "stub-admin" : DEMO_OWNER_ID,
        name: role === "admin" ? "Admin" : "Cik Aminah",
        email,
        phone: null,
        role,
      };

const delay = () => new Promise((r) => setTimeout(r, 300));

export const demoAuth: AuthAdapter = {
  async login(email) {
    await delay();
    const user = userFor(email, roleFor(email));
    persist(user);
    return user;
  },

  async register(payload) {
    await delay();
    const user: AuthUser = {
      id: DEMO_OWNER_ID,
      name: payload.name,
      email: payload.email,
      phone: payload.phone,
      role: "owner",
    };
    persist(user);
    return user;
  },

  async logout() {
    persist(null);
  },

  async fetchMe() {
    return restore();
  },
};
