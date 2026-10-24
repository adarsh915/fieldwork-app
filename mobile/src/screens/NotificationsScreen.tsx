import React, { useState, useCallback } from "react";
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  TouchableOpacity,
  RefreshControl,
  ActivityIndicator,
} from "react-native";
import { MaterialCommunityIcons, MaterialIcons } from "@expo/vector-icons";
import { useNavigation, useFocusEffect } from "@react-navigation/native";
import type { NativeStackNavigationProp } from "@react-navigation/native-stack";
import { SafeAreaView, useSafeAreaInsets } from "react-native-safe-area-context";

import ScreenHeader from "../components/ScreenHeader";
import {
  fetchNotifications,
  markNotificationRead,
  markAllNotificationsRead,
  type InAppNotification,
} from "../api/notifications";
import { navigateToNotificationTarget } from "../navigation/navigationRef";
import type { RootStackParamList } from "../navigation/RootNavigator";

type Nav = NativeStackNavigationProp<RootStackParamList, "Notifications">;

const PURPLE = "#7367F0";

export default function NotificationsScreen() {
  const navigation = useNavigation<Nav>();
  const insets = useSafeAreaInsets();

  const [notifications, setNotifications] = useState<InAppNotification[]>([]);
  const [unreadCount, setUnreadCount] = useState<number>(0);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const loadData = useCallback(async () => {
    try {
      const res = await fetchNotifications();
      setNotifications(res.notifications);
      setUnreadCount(res.unreadCount);
    } catch (e) {
      console.log("[NotificationsScreen] Load error:", e);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, []);

  useFocusEffect(
    useCallback(() => {
      loadData();
    }, [loadData])
  );

  async function handleNotificationPress(item: InAppNotification) {
    // Optimistically mark as read
    if (!item.is_read) {
      setNotifications((prev) =>
        prev.map((n) => (n.id === item.id ? { ...n, is_read: true } : n))
      );
      setUnreadCount((c) => Math.max(0, c - 1));
      markNotificationRead(item.id);
    }

    // Navigate to deep-linked screen if provided
    if (item.screen && item.screen !== "Home" && item.screen !== "Notifications") {
      navigateToNotificationTarget(item.screen);
    }
  }

  async function handleMarkAllRead() {
    setNotifications((prev) => prev.map((n) => ({ ...n, is_read: true })));
    setUnreadCount(0);
    await markAllNotificationsRead();
  }

  function getScreenMeta(screen?: string) {
    switch (screen) {
      case "DayStart":
      case "DayEnd":
        return {
          icon: "clock-outline" as const,
          color: "#F59E0B",
          bg: "#FEF3C7",
          label: "Day Management",
        };
      case "Visits":
      case "DealerList":
        return {
          icon: "map-marker-radius-outline" as const,
          color: "#3B82F6",
          bg: "#DBEAFE",
          label: "Customer Visits",
        };
      case "Orders":
      case "OrderList":
        return {
          icon: "cart-outline" as const,
          color: "#10B981",
          bg: "#D1FAE5",
          label: "Orders",
        };
      case "Expenses":
      case "ExpenseList":
        return {
          icon: "cash-multiple" as const,
          color: "#8B5CF6",
          bg: "#EDE9FE",
          label: "Expenses",
        };
      case "Leaves":
      case "MyLeaves":
        return {
          icon: "calendar-account-outline" as const,
          color: "#EC4899",
          bg: "#FCE7F3",
          label: "Leaves",
        };
      default:
        return {
          icon: "bell-outline" as const,
          color: PURPLE,
          bg: "#EEF2FF",
          label: "Notice",
        };
    }
  }

  function formatTimestamp(isoStr: string) {
    try {
      const d = new Date(isoStr);
      const now = new Date();
      const diffMs = now.getTime() - d.getTime();
      const diffMins = Math.floor(diffMs / 60000);
      const diffHours = Math.floor(diffMins / 60);

      if (diffMins < 1) return "Just now";
      if (diffMins < 60) return `${diffMins}m ago`;
      if (diffHours < 24) return `${diffHours}h ago`;
      return d.toLocaleDateString(undefined, {
        day: "numeric",
        month: "short",
        hour: "2-digit",
        minute: "2-digit",
      });
    } catch {
      return "";
    }
  }

  const rightHeaderSlot = unreadCount > 0 ? (
    <TouchableOpacity
      style={styles.markAllHeaderBtn}
      onPress={handleMarkAllRead}
      activeOpacity={0.8}
    >
      <Text style={styles.markAllHeaderText}>Mark all read</Text>
    </TouchableOpacity>
  ) : (
    <View style={styles.headerPillDone}>
      <Text style={styles.headerPillDoneText}>All Read</Text>
    </View>
  );

  return (
    <View style={styles.container}>
      {/* ── Standard Purple Header matching all screens ── */}
      <ScreenHeader
        title={unreadCount > 0 ? `Notifications (${unreadCount})` : "Notifications"}
        onBack={() => navigation.goBack()}
        bgColor={PURPLE}
        rightElement={rightHeaderSlot}
        showNotification={false}
      />

      {/* ── Content ── */}
      {loading ? (
        <View style={styles.centerWrap}>
          <ActivityIndicator size="large" color={PURPLE} />
          <Text style={styles.loadingText}>Loading notifications...</Text>
        </View>
      ) : notifications.length === 0 ? (
        <View style={styles.centerWrap}>
          <View style={styles.emptyIconCircle}>
            <MaterialCommunityIcons
              name="bell-check-outline"
              size={56}
              color="#9CA3AF"
            />
          </View>
          <Text style={styles.emptyTitle}>No Notifications</Text>
          <Text style={styles.emptySub}>
            You are all caught up! New alerts and updates from admin will appear here.
          </Text>
        </View>
      ) : (
        <FlatList
          data={notifications}
          keyExtractor={(item) => String(item.id)}
          contentContainerStyle={[
            styles.listContent,
            { paddingBottom: Math.max(insets.bottom, 20) + 20 },
          ]}
          showsVerticalScrollIndicator={false}
          refreshControl={
            <RefreshControl
              refreshing={refreshing}
              onRefresh={() => {
                setRefreshing(true);
                loadData();
              }}
              colors={[PURPLE]}
            />
          }
          renderItem={({ item }) => {
            const meta = getScreenMeta(item.screen);
            return (
              <TouchableOpacity
                style={[
                  styles.card,
                  !item.is_read ? styles.cardUnread : styles.cardRead,
                ]}
                activeOpacity={0.8}
                onPress={() => handleNotificationPress(item)}
              >
                {/* Left category icon with clean unread indicator badge */}
                <View style={styles.iconContainer}>
                  <View style={[styles.iconWrap, { backgroundColor: meta.bg }]}>
                    <MaterialCommunityIcons
                      name={meta.icon}
                      size={22}
                      color={meta.color}
                    />
                  </View>
                  {!item.is_read && <View style={styles.unreadIndicatorDot} />}
                </View>

                {/* Body Content */}
                <View style={styles.cardBody}>
                  {/* Title & Timestamp row */}
                  <View style={styles.cardHeaderRow}>
                    <View style={styles.titleWithBadge}>
                      <Text
                        style={[
                          styles.cardTitle,
                          !item.is_read && styles.cardTitleUnread,
                        ]}
                        numberOfLines={1}
                      >
                        {item.title}
                      </Text>
                    </View>

                    <Text style={styles.timeText}>
                      {formatTimestamp(item.created_at)}
                    </Text>
                  </View>

                  {/* Message body */}
                  <Text style={styles.cardMessage}>{item.body}</Text>

                  {/* Footer tags and deep link prompt */}
                  <View style={styles.cardFooter}>
                    <View style={[styles.categoryPill, { backgroundColor: meta.bg }]}>
                      <Text style={[styles.categoryText, { color: meta.color }]}>
                        {meta.label}
                      </Text>
                    </View>

                    {item.screen && item.screen !== "Home" && (
                      <View style={styles.actionPrompt}>
                        <Text style={styles.actionPromptText}>Open Screen</Text>
                        <MaterialIcons
                          name="arrow-forward-ios"
                          size={10}
                          color={PURPLE}
                        />
                      </View>
                    )}
                  </View>
                </View>
              </TouchableOpacity>
            );
          }}
        />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: "#F4F4F8",
  },
  markAllHeaderBtn: {
    backgroundColor: "rgba(255, 255, 255, 0.2)",
    paddingVertical: 4,
    paddingHorizontal: 10,
    borderRadius: 8,
  },
  markAllHeaderText: {
    color: "#FFFFFF",
    fontSize: 11,
    fontWeight: "700",
  },
  headerPillDone: {
    backgroundColor: "rgba(255, 255, 255, 0.12)",
    paddingVertical: 3,
    paddingHorizontal: 8,
    borderRadius: 6,
  },
  headerPillDoneText: {
    color: "rgba(255, 255, 255, 0.75)",
    fontSize: 11,
    fontWeight: "600",
  },
  centerWrap: {
    flex: 1,
    alignItems: "center",
    justifyContent: "center",
    paddingHorizontal: 32,
  },
  loadingText: {
    marginTop: 12,
    fontSize: 14,
    color: "#64748B",
    fontWeight: "500",
  },
  emptyIconCircle: {
    width: 96,
    height: 96,
    borderRadius: 48,
    backgroundColor: "#E2E8F0",
    alignItems: "center",
    justifyContent: "center",
    marginBottom: 16,
  },
  emptyTitle: {
    fontSize: 18,
    fontWeight: "700",
    color: "#1E293B",
    marginBottom: 6,
  },
  emptySub: {
    fontSize: 13,
    color: "#64748B",
    textAlign: "center",
    lineHeight: 19,
  },
  listContent: {
    padding: 12,
    paddingTop: 12,
  },
  card: {
    flexDirection: "row",
    backgroundColor: "#FFFFFF",
    borderRadius: 12,
    padding: 12,
    marginBottom: 10,
    borderWidth: 1,
    borderColor: "#E2E8F0",
    shadowColor: PURPLE,
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.05,
    shadowRadius: 5,
    elevation: 2,
    alignItems: "flex-start",
  },
  cardUnread: {
    backgroundColor: "#FFFFFF",
    borderColor: "#C7D2FE",
    borderLeftWidth: 4,
    borderLeftColor: PURPLE,
    shadowOpacity: 0.1,
    elevation: 3,
  },
  cardRead: {
    backgroundColor: "#FAFAFD",
    borderLeftWidth: 4,
    borderLeftColor: "#E2E8F0",
  },
  iconContainer: {
    position: "relative",
    marginRight: 12,
  },
  iconWrap: {
    width: 42,
    height: 42,
    borderRadius: 10,
    alignItems: "center",
    justifyContent: "center",
  },
  unreadIndicatorDot: {
    position: "absolute",
    top: -2,
    right: -2,
    width: 10,
    height: 10,
    borderRadius: 5,
    backgroundColor: PURPLE,
    borderWidth: 2,
    borderColor: "#FFFFFF",
  },
  cardBody: {
    flex: 1,
  },
  cardHeaderRow: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    marginBottom: 4,
  },
  titleWithBadge: {
    flex: 1,
    marginRight: 8,
  },
  cardTitle: {
    fontSize: 14,
    fontWeight: "600",
    color: "#475569",
  },
  cardTitleUnread: {
    fontWeight: "800",
    color: "#1E1B4B",
    fontSize: 14,
  },
  timeText: {
    fontSize: 11,
    color: "#94A3B8",
    fontWeight: "600",
  },
  cardMessage: {
    fontSize: 13,
    color: "#334155",
    lineHeight: 18,
    marginBottom: 8,
  },
  cardFooter: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
  },
  categoryPill: {
    paddingHorizontal: 8,
    paddingVertical: 2.5,
    borderRadius: 6,
  },
  categoryText: {
    fontSize: 11,
    fontWeight: "700",
  },
  actionPrompt: {
    flexDirection: "row",
    alignItems: "center",
    gap: 3,
    backgroundColor: "#F3F4F6",
    paddingHorizontal: 8,
    paddingVertical: 2.5,
    borderRadius: 6,
  },
  actionPromptText: {
    fontSize: 11,
    fontWeight: "700",
    color: PURPLE,
  },
});
