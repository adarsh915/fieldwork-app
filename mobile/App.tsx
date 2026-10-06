import React, { useEffect } from "react";
import { NavigationContainer } from "@react-navigation/native";
import { StatusBar } from "expo-status-bar";
import { SafeAreaProvider } from "react-native-safe-area-context";
import Constants, { AppOwnership } from "expo-constants";

import { AuthProvider } from "./src/auth/AuthContext";
import RootNavigator from "./src/navigation/RootNavigator";

/**
 * App Entry Point
 *
 * Wraps the entire app with:
 *   <AuthProvider>        – provides global auth state (user, login, logout)
 *   <NavigationContainer> – required by React Navigation
 */
export default function App() {
  useEffect(() => {
    // Skip remote notification listeners inside Expo Go to avoid SDK 53+ restriction
    if (Constants.appOwnership === AppOwnership.Expo) {
      return;
    }

    try {
      const Notifications = require("expo-notifications");
      // Listener for notifications received while app is in foreground
      const notificationSubscription = Notifications.addNotificationReceivedListener(
        (notification: any) => {
          console.log("[Notification Received]:", notification?.request?.content?.title);
        }
      );

      // Listener for when user taps on a notification
      const responseSubscription = Notifications.addNotificationResponseReceivedListener(
        (response: any) => {
          console.log(
            "[Notification Clicked]:",
            response?.notification?.request?.content?.data
          );
        }
      );

      return () => {
        notificationSubscription?.remove?.();
        responseSubscription?.remove?.();
      };
    } catch (e) {
      console.log("[Notification Listeners Error]:", e);
    }
  }, []);

  return (
    <SafeAreaProvider>
      <AuthProvider>
        <NavigationContainer>
          <RootNavigator />
          <StatusBar style="auto" />
        </NavigationContainer>
      </AuthProvider>
    </SafeAreaProvider>
  );
}



