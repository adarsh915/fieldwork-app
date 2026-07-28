import { MaterialCommunityIcons, MaterialIcons } from "@expo/vector-icons";
import { useNavigation } from "@react-navigation/native";
import type { NativeStackNavigationProp } from "@react-navigation/native-stack";
import { useEffect, useState } from "react";
import {
  ScrollView,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";

import type { RootStackParamList } from "../../navigation/RootNavigator";

type Nav = NativeStackNavigationProp<RootStackParamList, "AdminDashboard">;

interface DashboardStats {
  totalEmployees: number;
  activeEmployees: number;
  inactiveEmployees: number;
  totalRoles: number;
}

export default function AdminDashboardScreen() {
  const navigation = useNavigation<Nav>();
  const [stats, setStats] = useState<DashboardStats>({
    totalEmployees: 45,
    activeEmployees: 42,
    inactiveEmployees: 3,
    totalRoles: 4,
  });

  useEffect(() => {
    // TODO: Fetch dashboard stats from API
    // fetchDashboardStats();
  }, []);

  const menuItems = [
    {
      title: "Employee Management",
      icon: "account-group" as const,
      color: "#7367F0",
      onPress: () => navigation.navigate("EmployeeList"),
    },
    {
      title: "Create Employee",
      icon: "account-plus" as const,
      color: "#16a34a",
      onPress: () => navigation.navigate("CreateEmployee"),
    },
    {
      title: "Role Management",
      icon: "shield-account" as const,
      color: "#f59e0b",
      onPress: () => {
        // Navigate to role management
      },
    },
    {
      title: "Reports",
      icon: "chart-box" as const,
      color: "#ef4444",
      onPress: () => {
        // Navigate to reports
      },
    },
  ];

  return (
    <SafeAreaView style={styles.safe}>
      {/* Header */}
      <View style={styles.header}>
        <TouchableOpacity style={styles.headerIconBtn} activeOpacity={0.8}>
          <MaterialIcons name="menu" size={26} color="#FFFFFF" />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Admin Panel</Text>
        <TouchableOpacity style={styles.headerIconBtn} activeOpacity={0.8}>
          <MaterialIcons name="account-circle" size={26} color="#FFFFFF" />
        </TouchableOpacity>
      </View>

      <ScrollView contentContainerStyle={styles.scrollContent}>
        {/* Stats Cards */}
        <View style={styles.statsGrid}>
          <View style={[styles.statCard, { backgroundColor: "#7367F0" }]}>
            <MaterialCommunityIcons
              name="account-group"
              size={40}
              color="#FFFFFF"
            />
            <Text style={styles.statValue}>{stats.totalEmployees}</Text>
            <Text style={styles.statLabel}>Total Employees</Text>
          </View>

          <View style={[styles.statCard, { backgroundColor: "#16a34a" }]}>
            <MaterialCommunityIcons
              name="account-check"
              size={40}
              color="#FFFFFF"
            />
            <Text style={styles.statValue}>{stats.activeEmployees}</Text>
            <Text style={styles.statLabel}>Active</Text>
          </View>

          <View style={[styles.statCard, { backgroundColor: "#ef4444" }]}>
            <MaterialCommunityIcons
              name="account-off"
              size={40}
              color="#FFFFFF"
            />
            <Text style={styles.statValue}>{stats.inactiveEmployees}</Text>
            <Text style={styles.statLabel}>Inactive</Text>
          </View>

          <View style={[styles.statCard, { backgroundColor: "#f59e0b" }]}>
            <MaterialCommunityIcons
              name="shield-account"
              size={40}
              color="#FFFFFF"
            />
            <Text style={styles.statValue}>{stats.totalRoles}</Text>
            <Text style={styles.statLabel}>Roles</Text>
          </View>
        </View>

        {/* Quick Actions */}
        <Text style={styles.sectionTitle}>Quick Actions</Text>
        <View style={styles.menuGrid}>
          {menuItems.map((item, index) => (
            <TouchableOpacity
              key={index}
              style={styles.menuItem}
              activeOpacity={0.8}
              onPress={item.onPress}
            >
              <View style={[styles.menuIcon, { backgroundColor: item.color }]}>
                <MaterialCommunityIcons
                  name={item.icon}
                  size={32}
                  color="#FFFFFF"
                />
              </View>
              <Text style={styles.menuTitle}>{item.title}</Text>
            </TouchableOpacity>
          ))}
        </View>

        {/* Recent Activity */}
        <Text style={styles.sectionTitle}>Recent Activity</Text>
        <View style={styles.activityCard}>
          <View style={styles.activityItem}>
            <MaterialCommunityIcons
              name="account-plus"
              size={24}
              color="#16a34a"
            />
            <View style={styles.activityText}>
              <Text style={styles.activityTitle}>New employee added</Text>
              <Text style={styles.activityTime}>2 hours ago</Text>
            </View>
          </View>

          <View style={styles.activityDivider} />

          <View style={styles.activityItem}>
            <MaterialCommunityIcons
              name="account-edit"
              size={24}
              color="#7367F0"
            />
            <View style={styles.activityText}>
              <Text style={styles.activityTitle}>Employee role updated</Text>
              <Text style={styles.activityTime}>5 hours ago</Text>
            </View>
          </View>

          <View style={styles.activityDivider} />

          <View style={styles.activityItem}>
            <MaterialCommunityIcons
              name="account-remove"
              size={24}
              color="#ef4444"
            />
            <View style={styles.activityText}>
              <Text style={styles.activityTitle}>Employee deactivated</Text>
              <Text style={styles.activityTime}>1 day ago</Text>
            </View>
          </View>
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: {
    flex: 1,
    backgroundColor: "#F5F5F5",
  },
  header: {
    backgroundColor: "#7367F0",
    paddingHorizontal: 14,
    paddingVertical: 16,
    flexDirection: "row",
    alignItems: "center",
  },
  headerIconBtn: {
    width: 40,
    height: 40,
    borderRadius: 14,
    alignItems: "center",
    justifyContent: "center",
  },
  headerTitle: {
    flex: 1,
    textAlign: "center",
    color: "#FFFFFF",
    fontSize: 18,
    fontWeight: "800",
    letterSpacing: 0.6,
  },
  scrollContent: {
    padding: 16,
    paddingBottom: 40,
  },
  statsGrid: {
    flexDirection: "row",
    flexWrap: "wrap",
    gap: 12,
    marginBottom: 24,
  },
  statCard: {
    flex: 1,
    minWidth: "47%",
    borderRadius: 16,
    padding: 20,
    alignItems: "center",
    shadowColor: "#000",
    shadowOpacity: 0.1,
    shadowRadius: 8,
    elevation: 4,
  },
  statValue: {
    fontSize: 32,
    fontWeight: "900",
    color: "#FFFFFF",
    marginTop: 8,
  },
  statLabel: {
    fontSize: 12,
    fontWeight: "600",
    color: "#FFFFFF",
    marginTop: 4,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: "800",
    color: "#111827",
    marginBottom: 12,
  },
  menuGrid: {
    flexDirection: "row",
    flexWrap: "wrap",
    gap: 12,
    marginBottom: 24,
  },
  menuItem: {
    flex: 1,
    minWidth: "47%",
    backgroundColor: "#FFFFFF",
    borderRadius: 16,
    padding: 20,
    alignItems: "center",
    shadowColor: "#000",
    shadowOpacity: 0.08,
    shadowRadius: 8,
    elevation: 3,
  },
  menuIcon: {
    width: 64,
    height: 64,
    borderRadius: 32,
    alignItems: "center",
    justifyContent: "center",
    marginBottom: 12,
  },
  menuTitle: {
    fontSize: 13,
    fontWeight: "700",
    color: "#111827",
    textAlign: "center",
  },
  activityCard: {
    backgroundColor: "#FFFFFF",
    borderRadius: 16,
    padding: 16,
    shadowColor: "#000",
    shadowOpacity: 0.08,
    shadowRadius: 8,
    elevation: 3,
  },
  activityItem: {
    flexDirection: "row",
    alignItems: "center",
    gap: 12,
  },
  activityText: {
    flex: 1,
  },
  activityTitle: {
    fontSize: 14,
    fontWeight: "700",
    color: "#111827",
  },
  activityTime: {
    fontSize: 12,
    fontWeight: "500",
    color: "#6B7280",
    marginTop: 2,
  },
  activityDivider: {
    height: 1,
    backgroundColor: "#E5E7EB",
    marginVertical: 12,
  },
});
