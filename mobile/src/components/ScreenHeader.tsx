import { MaterialCommunityIcons, MaterialIcons } from "@expo/vector-icons";
import { useNavigation, useRoute, useFocusEffect } from "@react-navigation/native";
import React, { useCallback, useEffect, useState } from "react";
import { StyleSheet, Text, TouchableOpacity, View } from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";

import { fetchUnreadCount, subscribeUnreadCount } from "../api/notifications";

type ScreenHeaderProps = {
  title: string;
  onBack?: () => void;
  rightElement?: React.ReactNode;
  bgColor?: string;
  showNotification?: boolean;
  edges?: ("top" | "left" | "right" | "bottom")[];
};

export default function ScreenHeader({
  title,
  onBack,
  rightElement,
  bgColor = "#7367F0",
  showNotification = true,
  edges = ["top", "left", "right"],
}: ScreenHeaderProps) {
  const navigation = useNavigation<any>();
  const [unreadCount, setUnreadCount] = useState<number>(0);

  // Auto-detect if currently on Notifications screen
  let isNotificationsScreen = false;
  try {
    const route = useRoute();
    isNotificationsScreen = route?.name === "Notifications";
  } catch {
    // Outside navigation context fallback
  }

  const shouldShowBell = showNotification && !isNotificationsScreen;

  // Real-time unread count updates via broadcast listener
  useEffect(() => {
    if (!shouldShowBell) return;
    const unsubscribe = subscribeUnreadCount((count) => {
      setUnreadCount(count);
    });
    return unsubscribe;
  }, [shouldShowBell]);

  // Refresh unread count whenever the screen gains focus
  useFocusEffect(
    useCallback(() => {
      let isActive = true;
      if (shouldShowBell) {
        fetchUnreadCount()
          .then((count) => {
            if (isActive) setUnreadCount(count);
          })
          .catch(() => {});
      }
      return () => {
        isActive = false;
      };
    }, [shouldShowBell])
  );

  return (
    <SafeAreaView style={{ backgroundColor: bgColor }} edges={edges}>
      <View style={styles.header}>
        {onBack ? (
          <TouchableOpacity
            style={styles.backBtn}
            onPress={onBack}
            activeOpacity={0.7}
            accessibilityLabel="Back"
          >
            <MaterialCommunityIcons name="arrow-left" size={24} color="#FFFFFF" />
          </TouchableOpacity>
        ) : (
          <View style={styles.spacer} />
        )}

        <Text style={styles.headerTitle} numberOfLines={1}>
          {title}
        </Text>

        <View style={styles.rightSlot}>
          {rightElement}

          {shouldShowBell && (
            <TouchableOpacity
              style={styles.notifBtn}
              activeOpacity={0.75}
              onPress={() => navigation.navigate("Notifications")}
              accessibilityLabel="Notifications"
            >
              <MaterialIcons name="notifications-none" size={24} color="#FFFFFF" />
              {unreadCount > 0 && (
                <View style={[styles.badge, { borderColor: bgColor }]}>
                  <Text style={styles.badgeText}>
                    {unreadCount > 9 ? "9+" : unreadCount}
                  </Text>
                </View>
              )}
            </TouchableOpacity>
          )}

          {!rightElement && !shouldShowBell && <View style={styles.spacer} />}
        </View>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  header: {
    height: 56,
    paddingHorizontal: 10,
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
  },
  backBtn: {
    width: 40,
    height: 40,
    alignItems: "center",
    justifyContent: "center",
  },
  spacer: {
    width: 40,
  },
  headerTitle: {
    flex: 1,
    textAlign: "center",
    color: "#FFFFFF",
    fontSize: 17,
    fontWeight: "800",
    marginHorizontal: 4,
  },
  rightSlot: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "flex-end",
    minWidth: 40,
    gap: 4,
  },
  notifBtn: {
    width: 40,
    height: 40,
    alignItems: "center",
    justifyContent: "center",
    position: "relative",
  },
  badge: {
    position: "absolute",
    top: 3,
    right: 3,
    backgroundColor: "#EF4444",
    borderRadius: 9,
    minWidth: 17,
    height: 17,
    paddingHorizontal: 3,
    alignItems: "center",
    justifyContent: "center",
    borderWidth: 1.5,
  },
  badgeText: {
    color: "#FFFFFF",
    fontSize: 9,
    fontWeight: "800",
    textAlign: "center",
  },
});

