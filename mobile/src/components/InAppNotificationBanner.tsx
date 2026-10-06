import React, { useEffect, useState, useRef } from "react";
import {
  View,
  Text,
  StyleSheet,
  Animated,
  TouchableOpacity,
  Platform,
  Vibration,
} from "react-native";
import { Ionicons } from "@expo/vector-icons";
import { useAuth } from "../auth/AuthContext";
import {
  fetchLatestNotifications,
  markNotificationRead,
  InAppNotification,
} from "../api/notifications";

export default function InAppNotificationBanner() {
  const { user } = useAuth();
  const [currentNotification, setCurrentNotification] = useState<InAppNotification | null>(null);
  const slideAnim = useRef(new Animated.Value(-120)).current;
  const lastSeenIdRef = useRef<number>(0);

  useEffect(() => {
    if (!user) return;

    // Check for unread notifications every 3.5 seconds
    const interval = setInterval(async () => {
      try {
        const notifications = await fetchLatestNotifications();
        if (notifications && notifications.length > 0) {
          const newest = notifications[0];
          if (newest.id !== lastSeenIdRef.current) {
            lastSeenIdRef.current = newest.id;
            triggerBanner(newest);
          }
        }
      } catch {}
    }, 3500);

    return () => clearInterval(interval);
  }, [user]);

  function triggerBanner(notification: InAppNotification) {
    setCurrentNotification(notification);

    // Haptic vibration feedback
    if (Platform.OS === "android" || Platform.OS === "ios") {
      Vibration.vibrate(300);
    }

    // Slide down animation
    Animated.spring(slideAnim, {
      toValue: Platform.OS === "ios" ? 50 : 25,
      useNativeDriver: true,
      friction: 7,
      tension: 40,
    }).start();

    // Auto-dismiss after 6 seconds
    setTimeout(() => {
      dismissBanner(notification.id);
    }, 6000);
  }

  function dismissBanner(id?: number) {
    Animated.timing(slideAnim, {
      toValue: -140,
      duration: 300,
      useNativeDriver: true,
    }).start(() => {
      setCurrentNotification(null);
      if (id) {
        markNotificationRead(id);
      }
    });
  }

  if (!currentNotification) return null;

  return (
    <Animated.View
      style={[
        styles.container,
        {
          transform: [{ translateY: slideAnim }],
        },
      ]}
    >
      <TouchableOpacity
        activeOpacity={0.9}
        style={styles.card}
        onPress={() => dismissBanner(currentNotification.id)}
      >
        <View style={styles.iconCircle}>
          <Ionicons name="notifications" size={20} color="#FFFFFF" />
        </View>

        <View style={styles.textContainer}>
          <Text style={styles.title} numberOfLines={1}>
            {currentNotification.title}
          </Text>
          <Text style={styles.body} numberOfLines={2}>
            {currentNotification.body}
          </Text>
        </View>

        <TouchableOpacity
          onPress={() => dismissBanner(currentNotification.id)}
          style={styles.closeBtn}
          hitSlop={{ top: 10, bottom: 10, left: 10, right: 10 }}
        >
          <Ionicons name="close" size={18} color="#64748B" />
        </TouchableOpacity>
      </TouchableOpacity>
    </Animated.View>
  );
}

const styles = StyleSheet.create({
  container: {
    position: "absolute",
    top: 0,
    left: 16,
    right: 16,
    zIndex: 99999,
    elevation: 10,
    shadowColor: "#000",
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.15,
    shadowRadius: 10,
  },
  card: {
    backgroundColor: "#1E293B",
    borderRadius: 14,
    paddingVertical: 12,
    paddingHorizontal: 14,
    flexDirection: "row",
    alignItems: "center",
    borderWidth: 1,
    borderColor: "#334155",
  },
  iconCircle: {
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: "#2563EB",
    justifyContent: "center",
    alignItems: "center",
    marginRight: 12,
  },
  textContainer: {
    flex: 1,
    paddingRight: 8,
  },
  title: {
    fontSize: 14,
    fontWeight: "700",
    color: "#FFFFFF",
    marginBottom: 2,
  },
  body: {
    fontSize: 12,
    color: "#94A3B8",
    lineHeight: 16,
  },
  closeBtn: {
    padding: 4,
    borderRadius: 12,
    backgroundColor: "#334155",
  },
});
