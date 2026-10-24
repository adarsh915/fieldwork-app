import React, { useEffect } from "react";
import { NavigationContainer } from "@react-navigation/native";
import { StatusBar } from "expo-status-bar";
import { SafeAreaProvider } from "react-native-safe-area-context";
import * as Notifications from "expo-notifications";

import { AuthProvider } from "./src/auth/AuthContext";
import { AlertModalProvider } from "./src/context/AlertModalContext";
import RootNavigator from "./src/navigation/RootNavigator";
import { navigationRef, navigateToNotificationTarget } from "./src/navigation/navigationRef";
import { fetchUnreadCount } from "./src/api/notifications";

/**
 * App Entry Point
 *
 * Wraps the entire app with:
 *   <AuthProvider>        – provides global auth state (user, login, logout)
 *   <NavigationContainer> – with global navigationRef for deep-linking
 */
export default function App() {
  useEffect(() => {
    // 1. Initial badge sync
    fetchUnreadCount().catch(() => {});

    // 2. Foreground notification listener
    const notificationSubscription = Notifications.addNotificationReceivedListener(
      (notification) => {
        console.log("[Notification Received]:", notification?.request?.content?.title);
        // Sync unread badge count
        fetchUnreadCount().catch(() => {});
      }
    );

    // 3. User tapped on a notification in system tray (App in background or foreground)
    const responseSubscription = Notifications.addNotificationResponseReceivedListener(
      (response) => {
        const data = response?.notification?.request?.content?.data;
        console.log("[Notification Tapped]:", data);
        const screen = data?.screen;
        if (screen && typeof screen === "string") {
          // Slight delay to ensure navigation container is ready
          setTimeout(() => {
            navigateToNotificationTarget(screen);
          }, 300);
        }
      }
    );

    // 4. Check if app was opened directly from a notification (Cold start)
    Notifications.getLastNotificationResponseAsync().then((response) => {
      if (response) {
        const screen = response.notification?.request?.content?.data?.screen;
        if (screen && typeof screen === "string") {
          setTimeout(() => {
            navigateToNotificationTarget(screen);
          }, 600);
        }
      }
    }).catch(() => {});

    return () => {
      notificationSubscription.remove();
      responseSubscription.remove();
    };
  }, []);

  return (
    <SafeAreaProvider>
      <AuthProvider>
        <AlertModalProvider>
          <NavigationContainer ref={navigationRef}>
            <RootNavigator />
            <StatusBar style="auto" />
          </NavigationContainer>
        </AlertModalProvider>
      </AuthProvider>
    </SafeAreaProvider>
  );
}
