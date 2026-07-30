import { MaterialCommunityIcons, MaterialIcons } from "@expo/vector-icons";
import { useNavigation, useFocusEffect } from "@react-navigation/native";
import type { NativeStackNavigationProp } from "@react-navigation/native-stack";
import { useState, useCallback } from "react";
import {
  ActivityIndicator,
  Alert,
  ScrollView,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";

import { getDayStatus } from "../api/day";
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
  const { user, logout } = useAuth();
  const [loggingOut, setLoggingOut] = useState(false);

  const [dayActive, setDayActive] = useState(false);
  const [dayCompleted, setDayCompleted] = useState(false);

  // Fetch day status when screen comes into focus
  useFocusEffect(
    useCallback(() => {
      let isActive = true;
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
    { label: "Order\nReg.", icon: "clipboard-list-outline" },
    { label: "Payment\nReg.", icon: "cash-multiple" },
    { label: "Visit", icon: "map-marker-outline" },
    { label: "Product\nDemo", icon: "package-variant-closed" },
    { label: "My\nMeeting", icon: "calendar-account-outline" },
    { label: "Customers", icon: "account-tie-outline" },
    { label: "New Deal", icon: "handshake-outline" },
  ];

  // ── Employee (Hr/Reporting) grid ────────────────────────────────────────────
  const employeeMenu: MenuItem[] = [
    { label: "Activities", icon: "run-fast" },
    { label: "My Expense\nBill", icon: "receipt", onPress: () => navigation.navigate("ExpenseList") },
    { label: "Leave", icon: "calendar-remove-outline" },
    { label: "Product\nPlaning", icon: "chart-bar" },
    { label: "Coll.\nPlaning", icon: "briefcase-outline" },
    { label: "My Report", icon: "file-chart-outline" },
  ];

  // ── Bottom tabs ─────────────────────────────────────────────────────────────
  const bottomTabs = [
    { key: "Home", label: "Home", icon: "home" as const, active: true },
    {
      key: "Track",
      label: "Track",
      icon: "crosshairs-gps" as const,
      active: false,
      onPress: () => navigation.navigate("Track"),
    },
    { key: "MIS", label: "MIS", icon: "chart-pie" as const, active: false },
    {
      key: "Task",
      label: "Task",
      icon: "checkbox-marked-outline" as const,
      active: false,
    },
    {
      key: "Calendar",
      label: "Calendar",
      icon: "calendar-month-outline" as const,
      active: false,
      onPress: () => navigation.navigate("Calendar"),
    },
  ];

  return (
    <SafeAreaView style={styles.safe} edges={["top"]}>
      {/* ── Header ─────────────────────────────────────────────────────────── */}
      <View style={styles.header}>
        <TouchableOpacity style={styles.headerIcon}>
          <MaterialCommunityIcons name="menu" size={26} color="#fff" />
        </TouchableOpacity>

        <Text style={styles.headerTitle}>AMPL SOLUTION</Text>

        <View style={styles.headerActions}>
          <TouchableOpacity style={styles.headerIcon}>
            <MaterialCommunityIcons
              name="barcode-scan"
              size={22}
              color="#fff"
            />
          </TouchableOpacity>
          <TouchableOpacity style={styles.headerIcon}>
            <MaterialIcons name="notifications-none" size={24} color="#fff" />
          </TouchableOpacity>
          <TouchableOpacity style={styles.headerIcon}>
            <MaterialIcons name="access-time" size={22} color="#fff" />
          </TouchableOpacity>
          {/* Logout */}
          <TouchableOpacity
            style={[styles.headerIcon, styles.logoutIcon]}
            onPress={handleLogout}
            disabled={loggingOut}
          >
            {loggingOut ? (
              <ActivityIndicator size="small" color="#fff" />
            ) : (
              <MaterialIcons name="logout" size={22} color="#fff" />
            )}
          </TouchableOpacity>
        </View>
      </View>

      <ScrollView
        style={styles.scroll}
        contentContainerStyle={styles.scrollContent}
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

        <View style={{ height: 80 }} />
      </ScrollView>

      {/* ── Bottom Navigation ───────────────────────────────────────────────── */}
      <View style={styles.bottomBar}>
        {bottomTabs.map((tab) => (
          <TouchableOpacity
            key={tab.key}
            style={styles.bottomTab}
            activeOpacity={0.8}
            onPress={tab.onPress}
          >
            <MaterialCommunityIcons
              name={tab.icon}
              size={24}
              color={tab.active ? "#7367F0" : "#9ca3af"}
            />
            <Text
              style={[
                styles.bottomLabel,
                tab.active && styles.bottomLabelActive,
              ]}
            >
              {tab.label}
            </Text>
          </TouchableOpacity>
        ))}
      </View>
    </SafeAreaView>
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
    paddingHorizontal: 10,
    paddingVertical: 10,
  },
  headerTitle: {
    flex: 1,
    color: "#fff",
    fontSize: 16,
    fontWeight: "800",
    letterSpacing: 0.5,
    marginLeft: 6,
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
  },
  logoutIcon: {
    backgroundColor: "rgba(255,255,255,0.18)",
    marginLeft: 2,
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
});
