import * as Notifications from "expo-notifications";
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

export type NotificationsListResponse = {
  success: boolean;
  unread_count: number;
  data: InAppNotification[];
};

export type UnreadCountResponse = {
  success: boolean;
  count: number;
};

type CountListener = (count: number) => void;
const countListeners = new Set<CountListener>();

export function subscribeUnreadCount(listener: CountListener): () => void {
  countListeners.add(listener);
  return () => {
    countListeners.delete(listener);
  };
}

export function broadcastUnreadCount(count: number): void {
  countListeners.forEach((fn) => {
    try {
      fn(count);
    } catch {}
  });
}

/**
 * Fetch all notifications with unread count and sync app launcher badge
 */
export async function fetchNotifications(): Promise<{
  notifications: InAppNotification[];
  unreadCount: number;
}> {
  try {
    const res = await requestJson<NotificationsListResponse>("/notifications");
    const count = res?.unread_count ?? 0;
    
    broadcastUnreadCount(count);

    // Update phone launcher badge count
    try {
      await Notifications.setBadgeCountAsync(count);
    } catch {}

    return {
      notifications: res?.data ?? [],
      unreadCount: count,
    };
  } catch {
    return { notifications: [], unreadCount: 0 };
  }
}

/**
 * Fetch unread notification count
 */
export async function fetchUnreadCount(): Promise<number> {
  try {
    const res = await requestJson<UnreadCountResponse>("/notifications/unread-count");
    const count = res?.count ?? 0;

    broadcastUnreadCount(count);

    try {
      await Notifications.setBadgeCountAsync(count);
    } catch {}

    return count;
  } catch {
    return 0;
  }
}

/**
 * Fetch latest unread popup notifications
 */
export async function fetchLatestNotifications(): Promise<InAppNotification[]> {
  try {
    const res = await requestJson<NotificationsListResponse>("/notifications/latest");
    return res?.data ?? [];
  } catch {
    return [];
  }
}

/**
 * Mark a single notification as read
 */
export async function markNotificationRead(id: number): Promise<number> {
  try {
    const res = await requestJson<{ success: boolean; unread_count: number }>(
      `/notifications/${id}/read`,
      { method: "POST" }
    );
    const count = res?.unread_count ?? 0;

    broadcastUnreadCount(count);

    try {
      await Notifications.setBadgeCountAsync(count);
    } catch {}

    return count;
  } catch {
    return 0;
  }
}

/**
 * Mark all notifications as read
 */
export async function markAllNotificationsRead(): Promise<void> {
  try {
    await requestJson("/notifications/read-all", { method: "POST" });
    broadcastUnreadCount(0);
    try {
      await Notifications.setBadgeCountAsync(0);
    } catch {}
  } catch {}
}
