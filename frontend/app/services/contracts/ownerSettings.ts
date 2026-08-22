import type {
  NotificationPreferencesUpdate,
  OwnerAccount,
  OwnerPreferencesUpdate,
  OwnerProfileUpdate,
  Plan,
} from "~/types/owner";

export interface OwnerSettingsService {
  getAccount(): Promise<OwnerAccount>;
  updateProfile(patch: OwnerProfileUpdate): Promise<OwnerAccount>;
  updatePreferences(patch: OwnerPreferencesUpdate): Promise<OwnerAccount>;
  updateNotifications(
    patch: NotificationPreferencesUpdate,
  ): Promise<OwnerAccount>;
  getPlans(): Promise<Plan[]>;
}
