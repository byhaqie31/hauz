import type { OwnerSettingsService } from "~/services/contracts/ownerSettings";
import { ownerAccountMock, plansMock } from "~/demo/data/owner";

export const demoOwnerSettings: OwnerSettingsService = {
  async getAccount() {
    return structuredClone(ownerAccountMock);
  },

  async updateProfile(patch) {
    ownerAccountMock.profile = { ...ownerAccountMock.profile, ...patch };
    return structuredClone(ownerAccountMock);
  },

  async updatePreferences(patch) {
    ownerAccountMock.preferences = { ...ownerAccountMock.preferences, ...patch };
    return structuredClone(ownerAccountMock);
  },

  async updateNotifications(patch) {
    ownerAccountMock.notifications = {
      events: {
        ...ownerAccountMock.notifications.events,
        ...(patch.events ?? {}),
      },
      channels: {
        ...ownerAccountMock.notifications.channels,
        ...(patch.channels ?? {}),
      },
    };
    return structuredClone(ownerAccountMock);
  },

  async getPlans() {
    return structuredClone(plansMock);
  },
};
