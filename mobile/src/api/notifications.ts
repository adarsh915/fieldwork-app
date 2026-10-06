import { requestJson } from "./http";

export type InAppNotification = {
  id: number;
  user_id: number;
  title: string;
  body: string;
  screen?: string;
  is_read: boolean;
  created_at: string;
};

export type NotificationsResponse = {
  success: boolean;
  data: InAppNotification[];
};

export async function fetchLatestNotifications(): Promise<InAppNotification[]> {
  try {
    const res = await requestJson<NotificationsResponse>("/notifications/latest");
    return res?.data ?? [];
  } catch {
    return [];
  }
}

export async function markNotificationRead(id: number): Promise<void> {
  try {
    await requestJson(`/notifications/${id}/read`, { method: "POST" });
  } catch {}
}
