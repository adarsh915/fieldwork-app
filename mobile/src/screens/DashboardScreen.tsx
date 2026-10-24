import { MaterialCommunityIcons, MaterialIcons } from "@expo/vector-icons";
import { useNavigation, useFocusEffect } from "@react-navigation/native";
import type { NativeStackNavigationProp } from "@react-navigation/native-stack";
import { useEffect, useState, useCallback } from "react";
import {
  ActivityIndicator,
  Alert,
  Image,
  ScrollView,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from "react-native";
import { SafeAreaView, useSafeAreaInsets } from "react-native-safe-area-context";

import { getDayStatus } from "../api/day";
import { fetchUnreadCount, subscribeUnreadCount } from "../api/notifications";
import { useAuth } from "../auth/AuthContext";
import type { RootStackParamList } from "../navigation/RootNavigator";

type Nav = NativeStackNavigationProp<RootStackParamList, "Dashboard">;

// ─── Menu item type ────────────────────────────────────────────────────────────
type MenuItem = {
  label: string;
  icon: keyof typeof MaterialCommunityIcons.glyphMap;
  onPress?: () => void;
};

export default function DashboardScreen() {
  const navigation = useNavigation<Nav>();
  const insets = useSafeAreaInsets();

  const { user, logout } = useAuth();
  const [loggingOut, setLoggingOut] = useState(false);

  const [dayActive, setDayActive] = useState(false);
  const [dayCompleted, setDayCompleted] = useState(false);
  const [unreadNotifCount, setUnreadNotifCount] = useState(0);

  // Sync real-time count updates
  useEffect(() => {
    const unsubscribe = subscribeUnreadCount((count) => {
      setUnreadNotifCount(count);
    });
    return unsubscribe;
  }, []);

  // Fetch day status & unread notification count when screen comes into focus
  useFocusEffect(
    useCallback(() => {
      let isActive = true;
      
      // Fetch unread notifications
      fetchUnreadCount()
        .then((count) => {
          if (isActive) setUnreadNotifCount(count);
        })
        .catch(() => {});

      getDayStatus()
        .then((res) => {
          if (isActive && res.success) {
            if (res.day_started && res.data) {
              // If end_time is set, the day is completed
              if (res.data.end_time) {
                setDayCompleted(true);
                setDayActive(false);
              } else {
                setDayActive(true);
                setDayCompleted(false);
              }
            } else {
              setDayActive(false);
              setDayCompleted(false);
            }
          }
        })
        .catch(console.error);
      return () => {
        isActive = false;
      };
    }, []),
  );

  // ── Logout handler ──────────────────────────────────────────────────────────
  function handleLogout() {
    Alert.alert("Logout", "Are you sure you want to logout?", [
      { text: "Cancel", style: "cancel" },
      {
        text: "Logout",
        style: "destructive",
        onPress: async () => {
          setLoggingOut(true);
          try {
            await logout();
          } finally {
            setLoggingOut(false);
          }
        },
      },
    ]);
  }

  // ── Parties (Customers) grid ────────────────────────────────────────────────
  const partiesMenu: MenuItem[] = [
    {
      label: "Customer\n(Party)",
      icon: "account-group-outline",
      onPress: () => navigation.navigate("DealerList"),
    },
    {
      label: "Products",
      icon: "package-variant",
      onPress: () => navigation.navigate("ProductList"),
    },
    {
      label: "Order\nReg.",
      icon: "clipboard-list-outline",
      onPress: () => navigation.navigate("OrderList"),
    },
    {
      label: "Payment\nReg.",
      icon: "cash-multiple",
      onPress: () => navigation.navigate("PaymentRegister"),
    },
    {
      label: "Visit",
      icon: "map-marker-outline",
      onPress: () => navigation.navigate("Report"),
    },
    { label: "Product\nDemo", icon: "package-variant-closed" },
    { label: "My\nMeeting", icon: "calendar-account-outline" },
    { label: "Customers", icon: "account-tie-outline" },
    { label: "New Deal", icon: "handshake-outline" },
  ];

  // ── Employee (Hr/Reporting) grid ────────────────────────────────────────────
  const employeeMenu: MenuItem[] = [
    {
      label: "Activities",
      icon: "run-fast",
      onPress: () => navigation.navigate("Report"),
    },
    { label: "My Expense\nBill", icon: "receipt", onPress: () => navigation.navigate("ExpenseList") },
    { label: "Expense\nSummary", icon: "chart-bar-stacked", onPress: () => navigation.navigate("ExpenseMonthlySummary") },
    { label: "Attendance", icon: "calendar-check-outline", onPress: () => navigation.navigate("AttendanceCalendar") },
    { label: "My Leaves", icon: "calendar-remove-outline", onPress: () => navigation.navigate("MyLeaves") },
    { label: "Product\nPlaning", icon: "chart-bar" },
    { label: "Coll.\nPlaning", icon: "briefcase-outline" },
    { label: "My Report", icon: "file-chart-outline", onPress: () => navigation.navigate("Report") },
  ];

  return (
    <View style={styles.safe}>
      {/* ── Header with Seamless Purple Top Bar ── */}
      <SafeAreaView edges={["top", "left", "right"]} style={{ backgroundColor: PURPLE }}>
        <View style={styles.header}>
          <Text style={styles.headerTitle}>AMPL SOLUTION</Text>

          <View style={styles.headerActions}>
            {/* Notification Bell */}
            <TouchableOpacity
              style={styles.headerIcon}
              activeOpacity={0.8}
              onPress={() => navigation.navigate("Notifications")}
            >
              <MaterialIcons name="notifications-none" size={24} color="#fff" />
              {unreadNotifCount > 0 && (
                <View style={styles.notifBadge}>
                  <Text style={styles.notifBadgeText}>
                    {unreadNotifCount > 9 ? "9+" : unreadNotifCount}
                  </Text>
                </View>
              )}
            </TouchableOpacity>

            {/* Profile Rounded Avatar (Opens User Profile) */}
            <TouchableOpacity
              style={styles.profileAvatarBtn}
              activeOpacity={0.85}
              onPress={() => navigation.navigate("UserProfile")}
              accessibilityLabel="View user profile"
            >
              {user?.profile_image_url ? (
                <Image
                  source={{ uri: user.profile_image_url }}
                  style={styles.profileAvatarImg}
                />
              ) : (
                <View style={styles.profileAvatarFallback}>
                  <Text style={styles.profileAvatarInitial}>
                    {user?.name
                      ? user.name
                          .split(" ")
                          .map((n) => n[0])
                          .slice(0, 2)
                          .join("")
                          .toUpperCase()
                      : "U"}
                  </Text>
                </View>
              )}
            </TouchableOpacity>
          </View>
        </View>
      </SafeAreaView>

      <ScrollView
        style={styles.scroll}
        contentContainerStyle={[
          styles.scrollContent,
          { paddingBottom: Math.max(insets.bottom, 10) + 75 },
        ]}
        showsVerticalScrollIndicator={false}
      >
        {/* ── User Info Card ──────────────────────────────────────────────── */}
        <View style={styles.userCard}>
          <View style={styles.userInfo}>
            <View style={styles.userRow}>
              <Text style={styles.userLabel}>Code : </Text>
              <Text style={styles.userValue}>{user?.employee_id ?? "—"}</Text>
            </View>
            <View style={styles.userRow}>
              <Text style={styles.userLabel}>Name : </Text>
              <Text style={styles.userValue} numberOfLines={1}>
                {user?.name ?? "—"}
              </Text>
            </View>
            <View style={styles.userRow}>
              <Text style={styles.userLabel}>Mobile: </Text>
              <Text style={styles.userValue}>{user?.phone ?? "—"}</Text>
            </View>
            <View style={styles.userRow}>
              <Text style={styles.userLabel}>H.Q. : </Text>
              <Text style={styles.userValue}>{user?.department ?? "—"}</Text>
            </View>
          </View>

          {/* Day Start Button */}
          <TouchableOpacity
            style={[
              styles.dayStartBtn,
              dayActive && {
                backgroundColor: "#F59E0B",
                shadowColor: "#F59E0B",
              }, // Orange for Day End
              dayCompleted && {
                backgroundColor: "#10B981",
                shadowColor: "#10B981",
              }, // Green for completed
            ]}
            activeOpacity={0.85}
            disabled={dayCompleted}
            onPress={() => {
              if (dayActive) {
                navigation.navigate("DayEnd");
              } else {
                navigation.navigate("DayStart");
              }
            }}
          >
            <MaterialCommunityIcons
              name={
                dayCompleted
                  ? "check-circle-outline"
                  : dayActive
                    ? "weather-night"
                    : "weather-sunny"
              }
              size={22}
              color="#fff"
            />
            <Text style={styles.dayStartText}>
              {dayCompleted
                ? `DAY\nDONE`
                : dayActive
                  ? `DAY\nEND`
                  : `DAY\nSTART`}
            </Text>
          </TouchableOpacity>
        </View>

        {/* ── Section: Parties (Customers) ────────────────────────────────── */}
        <View style={styles.sectionHeader}>
          <Text style={styles.sectionTitle}>Parties (Customers)</Text>
          <Text style={styles.sectionSub}>
            Customer/Parties, Demo and New Lead Options
          </Text>
        </View>

        <View style={styles.menuGrid}>
          {partiesMenu.map((item, idx) => (
            <TouchableOpacity
              key={idx}
              style={styles.menuItem}
              activeOpacity={0.8}
              onPress={item.onPress}
            >
              <View style={styles.menuIconWrap}>
                <MaterialCommunityIcons
                  name={item.icon}
                  size={34}
                  color="#7367F0"
                />
              </View>
              <Text style={styles.menuLabel}>{item.label}</Text>
            </TouchableOpacity>
          ))}
        </View>

        {/* ── Section: Employee (Hr/Reporting) ────────────────────────────── */}
        <View style={styles.sectionHeader}>
          <Text style={styles.sectionTitle}>Employee (Hr/Reporting)</Text>
          <Text style={styles.sectionSub}>
            Employee Hiring, Planning and Reporting Options
          </Text>
        </View>

        <View style={styles.menuGrid}>
          {employeeMenu.map((item, idx) => (
            <TouchableOpacity
              key={idx}
              style={styles.menuItem}
              activeOpacity={0.8}
              onPress={item.onPress}
            >
              <View style={styles.menuIconWrap}>
                <MaterialCommunityIcons
                  name={item.icon}
                  size={34}
                  color="#7367F0"
                />
              </View>
              <Text style={styles.menuLabel}>{item.label}</Text>
            </TouchableOpacity>
          ))}
        </View>

        <View style={{ height: 20 }} />
      </ScrollView>
    </View>
  );
}

// ─── Styles ────────────────────────────────────────────────────────────────────
const PURPLE = "#7367F0";

const styles = StyleSheet.create({
  safe: {
    flex: 1,
    backgroundColor: "#F4F4F8",
  },

  // Header
  header: {
    backgroundColor: PURPLE,
    flexDirection: "row",
    alignItems: "center",
    paddingHorizontal: 16,
    paddingVertical: 12,
  },
  headerTitle: {
    flex: 1,
    color: "#fff",
    fontSize: 18,
    fontWeight: "800",
    letterSpacing: 0.5,
  },
  headerActions: {
    flexDirection: "row",
    alignItems: "center",
  },
  headerIcon: {
    width: 36,
    height: 36,
    alignItems: "center",
    justifyContent: "center",
    borderRadius: 10,
    position: "relative",
  },
  notifBadge: {
    position: "absolute",
    top: 2,
    right: 2,
    backgroundColor: "#EF4444",
    minWidth: 16,
    height: 16,
    borderRadius: 8,
    alignItems: "center",
    justifyContent: "center",
    paddingHorizontal: 3,
    borderWidth: 1.5,
    borderColor: PURPLE,
  },
  notifBadgeText: {
    color: "#FFFFFF",
    fontSize: 9,
    fontWeight: "800",
    textAlign: "center",
  },
  profileAvatarBtn: {
    width: 36,
    height: 36,
    borderRadius: 18,
    overflow: "hidden",
    borderWidth: 2,
    borderColor: "rgba(255,255,255,0.85)",
    backgroundColor: "rgba(255,255,255,0.25)",
    alignItems: "center",
    justifyContent: "center",
    marginLeft: 6,
  },
  profileAvatarImg: {
    width: 36,
    height: 36,
    borderRadius: 18,
  },
  profileAvatarFallback: {
    width: "100%",
    height: "100%",
    alignItems: "center",
    justifyContent: "center",
  },
  profileAvatarInitial: {
    color: "#FFFFFF",
    fontSize: 13,
    fontWeight: "900",
  },

  // Scroll
  scroll: { flex: 1 },
  scrollContent: { paddingHorizontal: 10, paddingTop: 10 },

  // User Card
  userCard: {
    backgroundColor: "#fff",
    borderRadius: 12,
    padding: 14,
    flexDirection: "row",
    alignItems: "center",
    marginBottom: 10,
    shadowColor: "#7367F0",
    shadowOpacity: 0.08,
    shadowRadius: 8,
    elevation: 4,
    borderLeftWidth: 4,
    borderLeftColor: PURPLE,
  },
  userInfo: { flex: 1 },
  userRow: {
    flexDirection: "row",
    marginBottom: 3,
  },
  userLabel: {
    fontSize: 12,
    color: "#6b7280",
    fontWeight: "600",
    fontFamily: "monospace",
  },
  userValue: {
    fontSize: 12,
    color: "#111827",
    fontWeight: "700",
    flex: 1,
  },
  dayStartBtn: {
    width: 72,
    height: 72,
    borderRadius: 36,
    backgroundColor: PURPLE,
    alignItems: "center",
    justifyContent: "center",
    shadowColor: PURPLE,
    shadowOpacity: 0.35,
    shadowRadius: 10,
    elevation: 8,
    marginLeft: 12,
  },
  dayStartText: {
    color: "#fff",
    fontSize: 10,
    fontWeight: "900",
    textAlign: "center",
    marginTop: 2,
    lineHeight: 13,
  },

  // Section header
  sectionHeader: {
    marginBottom: 8,
    marginTop: 4,
  },
  sectionTitle: {
    fontSize: 14,
    fontWeight: "800",
    color: "#111827",
  },
  sectionSub: {
    fontSize: 11,
    color: "#9ca3af",
    marginTop: 1,
  },

  // Menu Grid
  menuGrid: {
    flexDirection: "row",
    flexWrap: "wrap",
    marginBottom: 12,
  },
  menuItem: {
    width: "25%",
    alignItems: "center",
    paddingVertical: 10,
  },
  menuIconWrap: {
    width: 62,
    height: 62,
    borderRadius: 14,
    backgroundColor: "#fff",
    alignItems: "center",
    justifyContent: "center",
    marginBottom: 6,
    shadowColor: "#7367F0",
    shadowOpacity: 0.1,
    shadowRadius: 6,
    elevation: 3,
    borderWidth: 1,
    borderColor: "#EDE9FE",
  },
  menuLabel: {
    fontSize: 10,
    fontWeight: "700",
    color: "#374151",
    textAlign: "center",
    lineHeight: 13,
  },

  // Bottom bar
  bottomBar: {
    flexDirection: "row",
    backgroundColor: "#fff",
    borderTopWidth: 1,
    borderTopColor: "#e5e7eb",
    paddingVertical: 8,
    paddingBottom: 10,
  },
  bottomTab: {
    flex: 1,
    alignItems: "center",
    justifyContent: "center",
  },
  bottomLabel: {
    fontSize: 10,
    fontWeight: "600",
    color: "#9ca3af",
    marginTop: 2,
  },
  bottomLabelActive: {
    color: PURPLE,
    fontWeight: "800",
  },
  comingSoonToast: {
    position: "absolute",
    bottom: "100%",
    alignSelf: "center",
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: PURPLE,
    paddingHorizontal: 16,
    paddingVertical: 10,
    borderRadius: 24,
    marginBottom: 8,
    shadowColor: PURPLE,
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.35,
    shadowRadius: 8,
    elevation: 8,
    zIndex: 99,
  },
  comingSoonText: {
    color: "#fff",
    fontSize: 12,
    fontWeight: "600",
  },
});
