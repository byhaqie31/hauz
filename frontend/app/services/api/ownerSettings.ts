import type { OwnerAccount, Plan } from "~/types/owner";
import type { OwnerSettingsService } from "~/services/contracts/ownerSettings";

export const apiOwnerSettings: OwnerSettingsService = {
  getAccount: () => useApi().request<OwnerAccount>("/account"),

  updateProfile: (patch) =>
    useApi().request<OwnerAccount>("/account/profile", {
      method: "PATCH",
      body: patch,
    }),

  updatePreferences: (patch) =>
    useApi().request<OwnerAccount>("/account/preferences", {
      method: "PATCH",
      body: patch,
    }),

  updateNotifications: (patch) =>
    useApi().request<OwnerAccount>("/account/notifications", {
      method: "PATCH",
      body: patch,
    }),

  getPlans: () => useApi().request<Plan[]>("/plans"),
};
