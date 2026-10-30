import { MaterialCommunityIcons, MaterialIcons } from "@expo/vector-icons";
import { useNavigation } from "@react-navigation/native";
import type { NativeStackNavigationProp } from "@react-navigation/native-stack";
import React, { useState } from "react";
import {
  ActivityIndicator,
  Alert,
  ScrollView,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from "react-native";
import { SafeAreaView, useSafeAreaInsets } from "react-native-safe-area-context";

import ScreenHeader from "../components/ScreenHeader";
import CachedImage from "../components/CachedImage";
import { useAuth } from "../auth/AuthContext";
import { useAlert } from "../context/AlertModalContext";
import type { RootStackParamList } from "../navigation/RootNavigator";

type Nav = NativeStackNavigationProp<RootStackParamList, "UserProfile">;

const PURPLE = "#7367F0";

export default function UserProfileScreen() {
  const navigation = useNavigation<Nav>();
  const insets = useSafeAreaInsets();
  const { user, logout } = useAuth();
  const { showConfirm, showError } = useAlert();
  const [loggingOut, setLoggingOut] = useState(false);

  const handleLogout = () => {
    showConfirm({
      title: "Log Out Account",
      message: "Are you sure you want to log out of your field account?",
      confirmText: "Log Out",
      cancelText: "Stay Logged In",
      isDestructive: true,
      onConfirm: async () => {
        setLoggingOut(true);
        try {
          await logout();
        } catch (err: any) {
          showError("Logout Error", err?.message || "Failed to log out.");
          setLoggingOut(false);
        }
      },
    });
  };

  const initials = user?.name
    ? user.name
        .split(" ")
        .map((n) => n[0])
        .slice(0, 2)
        .join("")
        .toUpperCase()
    : "U";

  const displayRole =
    user?.role_name ||
    (user?.roles && user.roles.length > 0 ? user.roles[0] : "Field Worker");

  return (
    <View style={styles.container}>
      <ScreenHeader
        title="My Profile"
        onBack={() => navigation.goBack()}
        bgColor={PURPLE}
      />

      <ScrollView
        style={styles.scroll}
        contentContainerStyle={[
          styles.scrollContent,
          { paddingBottom: Math.max(insets.bottom, 20) + 20 },
        ]}
        showsVerticalScrollIndicator={false}
      >
        {/* ── Profile Hero Card ── */}
        <View style={styles.heroCard}>
          <View style={styles.avatarContainer}>
            {user?.profile_image_url ? (
              <CachedImage
                source={{ uri: user.profile_image_url }}
                style={styles.avatarImg}
              />
            ) : (
              <View style={styles.avatarFallback}>
                <Text style={styles.avatarInitials}>{initials}</Text>
              </View>
            )}
            <View style={styles.statusDot} />
          </View>

          <Text style={styles.userName}>{user?.name ?? "User Profile"}</Text>
          
          <View style={styles.roleBadge}>
            <MaterialCommunityIcons name="shield-check" size={14} color="#7367F0" />
            <Text style={styles.roleBadgeText}>{displayRole}</Text>
          </View>

          {user?.employee_id && (
            <Text style={styles.empIdText}>Employee Code: {user.employee_id}</Text>
          )}


        </View>

        {/* ── Employee Details Card ── */}
        <View style={styles.sectionCard}>
          <Text style={styles.sectionHeaderTitle}>Account & Work Details</Text>

          <View style={styles.infoRow}>
            <View style={styles.infoIconWrap}>
              <MaterialCommunityIcons name="badge-account-horizontal" size={20} color="#7367F0" />
            </View>
            <View style={styles.infoContent}>
              <Text style={styles.infoLabel}>Employee Code</Text>
              <Text style={styles.infoValue}>{user?.employee_id || "Not assigned"}</Text>
            </View>
          </View>

          <View style={styles.divider} />

          <View style={styles.infoRow}>
            <View style={styles.infoIconWrap}>
              <MaterialCommunityIcons name="email-outline" size={20} color="#7367F0" />
            </View>
            <View style={styles.infoContent}>
              <Text style={styles.infoLabel}>Email Address</Text>
              <Text style={styles.infoValue}>{user?.email || "—"}</Text>
            </View>
          </View>

          <View style={styles.divider} />

          <View style={styles.infoRow}>
            <View style={styles.infoIconWrap}>
              <MaterialCommunityIcons name="phone-outline" size={20} color="#7367F0" />
            </View>
            <View style={styles.infoContent}>
              <Text style={styles.infoLabel}>Mobile Number</Text>
              <Text style={styles.infoValue}>{user?.phone || "Not provided"}</Text>
            </View>
          </View>

          <View style={styles.divider} />

          <View style={styles.infoRow}>
            <View style={styles.infoIconWrap}>
              <MaterialCommunityIcons name="map-marker-radius-outline" size={20} color="#7367F0" />
            </View>
            <View style={styles.infoContent}>
              <Text style={styles.infoLabel}>Headquarters / Territory</Text>
              <Text style={styles.infoValue}>{user?.department || "General Field Territory"}</Text>
            </View>
          </View>

          {user?.address && (
            <>
              <View style={styles.divider} />
              <View style={styles.infoRow}>
                <View style={styles.infoIconWrap}>
                  <MaterialCommunityIcons name="home-outline" size={20} color="#7367F0" />
                </View>
                <View style={styles.infoContent}>
                  <Text style={styles.infoLabel}>Base Address</Text>
                  <Text style={styles.infoValue}>{user.address}</Text>
                </View>
              </View>
            </>
          )}

          {user?.hire_date && (
            <>
              <View style={styles.divider} />
              <View style={styles.infoRow}>
                <View style={styles.infoIconWrap}>
                  <MaterialCommunityIcons name="calendar-check" size={20} color="#7367F0" />
                </View>
                <View style={styles.infoContent}>
                  <Text style={styles.infoLabel}>Joining Date</Text>
                  <Text style={styles.infoValue}>
                    {new Date(user.hire_date).toLocaleDateString("en-IN", {
                      day: "2-digit",
                      month: "short",
                      year: "numeric",
                    })}
                  </Text>
                </View>
              </View>
            </>
          )}
        </View>


        {/* ── Logout Button in Bottom ── */}
        <View style={styles.logoutSection}>
          <TouchableOpacity
            style={styles.logoutBtn}
            activeOpacity={0.85}
            onPress={handleLogout}
            disabled={loggingOut}
          >
            {loggingOut ? (
              <ActivityIndicator color="#DC2626" size="small" />
            ) : (
              <>
                <MaterialIcons name="logout" size={20} color="#DC2626" />
                <Text style={styles.logoutBtnText}>Log Out from Device</Text>
              </>
            )}
          </TouchableOpacity>
          <Text style={styles.logoutSubtext}>
            Logging out clears your active session and offline auth tokens.
          </Text>
        </View>
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: "#F4F4F8",
  },
  scroll: {
    flex: 1,
  },
  scrollContent: {
    padding: 16,
    gap: 14,
  },
  heroCard: {
    backgroundColor: "#FFFFFF",
    borderRadius: 20,
    padding: 22,
    alignItems: "center",
    shadowColor: "#000",
    shadowOpacity: 0.05,
    shadowRadius: 10,
    elevation: 3,
    borderWidth: 1,
    borderColor: "#E2E8F0",
  },
  avatarContainer: {
    position: "relative",
    marginBottom: 12,
  },
  avatarImg: {
    width: 88,
    height: 88,
    borderRadius: 44,
    borderWidth: 3,
    borderColor: "#EDE9FE",
  },
  avatarFallback: {
    width: 88,
    height: 88,
    borderRadius: 44,
    backgroundColor: "#7367F0",
    alignItems: "center",
    justifyContent: "center",
    borderWidth: 3,
    borderColor: "#EDE9FE",
    shadowColor: "#7367F0",
    shadowOpacity: 0.3,
    shadowRadius: 6,
    elevation: 4,
  },
  avatarInitials: {
    fontSize: 32,
    fontWeight: "900",
    color: "#FFFFFF",
    letterSpacing: 1,
  },
  statusDot: {
    position: "absolute",
    bottom: 2,
    right: 2,
    width: 18,
    height: 18,
    borderRadius: 9,
    backgroundColor: "#10B981",
    borderWidth: 3,
    borderColor: "#FFFFFF",
  },
  userName: {
    fontSize: 20,
    fontWeight: "800",
    color: "#1E293B",
    textAlign: "center",
    marginBottom: 6,
  },
  roleBadge: {
    flexDirection: "row",
    alignItems: "center",
    gap: 5,
    backgroundColor: "#EDE9FE",
    paddingHorizontal: 12,
    paddingVertical: 5,
    borderRadius: 20,
    marginBottom: 6,
  },
  roleBadgeText: {
    fontSize: 12,
    fontWeight: "800",
    color: "#7367F0",
    textTransform: "uppercase",
  },
  empIdText: {
    fontSize: 12,
    fontWeight: "600",
    color: "#64748B",
  },
  sectionCard: {
    backgroundColor: "#FFFFFF",
    borderRadius: 18,
    padding: 18,
    shadowColor: "#000",
    shadowOpacity: 0.04,
    shadowRadius: 8,
    elevation: 2,
    borderWidth: 1,
    borderColor: "#E2E8F0",
  },
  sectionHeaderTitle: {
    fontSize: 14,
    fontWeight: "800",
    color: "#334155",
    marginBottom: 14,
    textTransform: "uppercase",
    letterSpacing: 0.5,
  },
  infoRow: {
    flexDirection: "row",
    alignItems: "center",
    paddingVertical: 6,
  },
  infoIconWrap: {
    width: 40,
    height: 40,
    borderRadius: 12,
    backgroundColor: "#EDE9FE",
    alignItems: "center",
    justifyContent: "center",
    marginRight: 14,
  },
  infoContent: {
    flex: 1,
  },
  infoLabel: {
    fontSize: 11,
    fontWeight: "600",
    color: "#64748B",
    marginBottom: 2,
  },
  infoValue: {
    fontSize: 14,
    fontWeight: "700",
    color: "#1E293B",
  },
  divider: {
    height: 1,
    backgroundColor: "#F1F5F9",
    marginVertical: 6,
  },
  logoutSection: {
    marginTop: 10,
    alignItems: "center",
  },
  logoutBtn: {
    width: "100%",
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    gap: 8,
    backgroundColor: "#FEF2F2",
    borderWidth: 1.5,
    borderColor: "#FECACA",
    borderRadius: 16,
    paddingVertical: 14,
    shadowColor: "#DC2626",
    shadowOpacity: 0.06,
    shadowRadius: 6,
    elevation: 2,
  },
  logoutBtnText: {
    fontSize: 15,
    fontWeight: "800",
    color: "#DC2626",
  },
  logoutSubtext: {
    fontSize: 11,
    color: "#94A3B8",
    textAlign: "center",
    marginTop: 8,
    paddingHorizontal: 16,
  },
});
