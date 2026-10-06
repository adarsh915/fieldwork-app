import { Platform } from "react-native";
import * as Device from "expo-device";
import * as Notifications from "expo-notifications";
import Constants from "expo-constants";
import { updateFcmToken } from "../api/auth";

// Configure how notifications appear when app is open (foreground)
Notifications.setNotificationHandler({
  handleNotification: async () => ({
    shouldShowAlert: true,
    shouldShowBanner: true,
    shouldShowList: true,
    shouldPlaySound: true,
    shouldSetBadge: true,
  }),
});

/**
 * Register device for push notifications and sync real token to backend.
 */
export async function registerForPushNotificationsAsync(): Promise<string | null> {
  let token: string | null = null;

  // Setup Android notification channel
  if (Platform.OS === "android") {
    try {
      await Notifications.setNotificationChannelAsync("default", {
        name: "Default",
        importance: Notifications.AndroidImportance.MAX,
        vibrationPattern: [0, 250, 250, 250],
        lightColor: "#2563EB",
        sound: "default",
      });
    } catch (e) {
      console.log("[PushNotifications] Channel setup error:", e);
    }
  }

  // Push notifications only work on physical devices
  if (!Device.isDevice) {
    console.log("[PushNotifications] Push notifications require a physical device.");
    return null;
  }

  try {
    // Request permissions
    const permissions: any = await Notifications.getPermissionsAsync();
    let isGranted = Boolean(permissions?.granted || permissions?.status === "granted");

    if (!isGranted) {
      const requestResult: any = await Notifications.requestPermissionsAsync();
      isGranted = Boolean(requestResult?.granted || requestResult?.status === "granted");
    }

    if (!isGranted) {
      console.log("[PushNotifications] Permission not granted by user.");
      return null;
    }

    // On Android, get native device FCM token directly so backend pushes via Firebase HTTP v1
    if (Platform.OS === "android") {
      try {
        const deviceTokenObj = await Notifications.getDevicePushTokenAsync();
        token = typeof deviceTokenObj === "object" ? (deviceTokenObj as any)?.data ?? (deviceTokenObj as any)?.token : deviceTokenObj;
        console.log("[PushNotifications] Native Android FCM Token:", token);
      } catch (nativeErr) {
        console.log("[PushNotifications] Native FCM token error, falling back to Expo token:", nativeErr);
      }
    }

    // Fallback to Expo Push Token if native device token is not available
    if (!token) {
      try {
        const projectId =
          Constants?.expoConfig?.extra?.eas?.projectId ??
          Constants?.easConfig?.projectId ??
          "3e4f7a64-cc21-45fc-a0c5-a9075728c836";

        const expoTokenObj = await Notifications.getExpoPushTokenAsync({ projectId });
        token = expoTokenObj.data;
        console.log("[PushNotifications] Expo Push Token:", token);
      } catch (expoErr) {
        console.log("[PushNotifications] Expo Push Token error:", expoErr);
      }
    }

    console.log("[PushNotifications] Registered Real Token:", token);

    // Sync token to Laravel backend
    if (token) {
      await updateFcmToken(token);
      console.log("[PushNotifications] Token successfully saved to backend.");
    }

    return token;
  } catch (error) {
    console.log("[PushNotifications] Error during registration:", error);
    return null;
  }
}
