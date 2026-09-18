import React, { useCallback, useEffect, useState } from "react";
import {
  ActivityIndicator,
  Alert,
  FlatList,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from "react-native";
import { MaterialCommunityIcons } from "@expo/vector-icons";
import ScreenHeader from "../components/ScreenHeader";
import StatusBadge from "../components/StatusBadge";
import {
  cancelLeave,
  getLeaveBalance,
  getMyLeaves,
  Leave,
  LeaveBalance,
  requestLeaveCancellation,
} from "../api/leave";
import { HttpError } from "../api/http";
import { type NativeStackScreenProps } from "@react-navigation/native-stack";
import { type AppStackParamList } from "../navigation/RootNavigator";

type Props = NativeStackScreenProps<AppStackParamList, "MyLeaves">;
type Tab = "applications" | "balance";

const STATUS_COLOR: Record<string, string> = {
  pending: "#f59e0b",
  approved: "#22c55e",
  rejected: "#f87171",
  cancellation_requested: "#f59e0b",
  cancelled: "#64748b",
};

const STATUS_DISPLAY: Record<string, string> = {
  pending: "PENDING",
  approved: "APPROVED",
  rejected: "REJECTED",
  cancellation_requested: "CANCEL REQUESTED",
  cancelled: "CANCELLED",
};

// ─── Styles ───────────────────────────────────────────────────────────────────

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#0f172a" },
  applyBtn: { padding: 4 },
  tabBar: { flexDirection: "row", backgroundColor: "#1e293b", borderBottomWidth: 1, borderColor: "#334155" },
  tab: { flex: 1, paddingVertical: 13, alignItems: "center" },
  tabActive: { borderBottomWidth: 2, borderColor: "#7367F0" },
  tabText: { color: "#64748b", fontWeight: "600", fontSize: 14 },
  tabTextActive: { color: "#7367F0" },
  list: { padding: 16, paddingBottom: 40 },
  // Leave card
  card: { backgroundColor: "#1e293b", borderRadius: 14, padding: 16, marginBottom: 12 },
  cardHeader: { flexDirection: "row", alignItems: "center", marginBottom: 10 },
  leaveTypeChip: { width: 44, height: 44, borderRadius: 22, backgroundColor: "#7367F020", alignItems: "center", justifyContent: "center", marginRight: 12 },
  leaveTypeCode: { color: "#7367F0", fontWeight: "800", fontSize: 15 },
  cardHeaderRight: { flex: 1, flexDirection: "row", justifyContent: "space-between", alignItems: "center" },
  leaveTypeName: { color: "#f1f5f9", fontWeight: "600", fontSize: 15 },
  statusBadge: { borderRadius: 6, paddingHorizontal: 8, paddingVertical: 3 },
  statusText: { fontSize: 11, fontWeight: "700" },
  dateRow: { flexDirection: "row", alignItems: "center", marginBottom: 6, gap: 4 },
  dateText: { color: "#94a3b8", fontSize: 13 },
  metaRow: { flexDirection: "row", alignItems: "center", gap: 6, marginBottom: 6 },
  metaText: { color: "#64748b", fontSize: 12 },
  metaDot: { color: "#334155", fontSize: 16 },
  reason: { color: "#64748b", fontSize: 13, marginTop: 4, fontStyle: "italic" },
  rejectionBox: { flexDirection: "row", backgroundColor: "#f8717120", borderRadius: 6, padding: 8, marginTop: 8 },
  rejectionLabel: { color: "#f87171", fontSize: 12, fontWeight: "700" },
  rejectionText: { color: "#fca5a5", fontSize: 12, flex: 1 },
  cancelBtn: { flexDirection: "row", alignItems: "center", justifyContent: "center", gap: 6, marginTop: 12, backgroundColor: "#f8717115", borderRadius: 8, paddingVertical: 10 },
  cancelText: { color: "#f87171", fontWeight: "600" },
  // Balance card
  balanceCard: { backgroundColor: "#1e293b", borderRadius: 14, padding: 16, marginBottom: 12 },
  balanceHeader: { flexDirection: "row", justifyContent: "space-between", alignItems: "center", marginBottom: 12 },
  balanceName: { color: "#f1f5f9", fontWeight: "700", fontSize: 15 },
  balanceCode: { color: "#7367F0", fontWeight: "600", backgroundColor: "#7367F020", paddingHorizontal: 8, paddingVertical: 3, borderRadius: 6 },
  availableBanner: { flexDirection: "row", alignItems: "center", justifyContent: "space-between", backgroundColor: "#22c55e15", borderColor: "#22c55e40", borderWidth: 1, borderRadius: 10, padding: 12, marginBottom: 14 },
  bannerSubtitle: { color: "#22c55e", fontSize: 12, fontWeight: "700" },
  bannerFormula: { color: "#94a3b8", fontSize: 10, marginTop: 2, fontStyle: "italic" },
  bannerAvailableVal: { color: "#22c55e", fontSize: 22, fontWeight: "800" },
  balanceStats: { flexDirection: "row", justifyContent: "space-around", marginBottom: 14 },
  balanceStat: { alignItems: "center" },
  balanceVal: { color: "#f1f5f9", fontSize: 20, fontWeight: "800" },
  balanceLbl: { color: "#64748b", fontSize: 11, marginTop: 2 },
  progressTrack: { height: 4, backgroundColor: "#334155", borderRadius: 2, overflow: "hidden" },
  progressFill: { height: 4, backgroundColor: "#f87171", borderRadius: 2 },
  // States
  empty: { alignItems: "center", paddingTop: 60 },
  emptyText: { color: "#475569", marginTop: 12, fontSize: 15 },
  applyEmptyBtn: { marginTop: 20, backgroundColor: "#7367F0", paddingHorizontal: 24, paddingVertical: 12, borderRadius: 10 },
  applyEmptyText: { color: "#fff", fontWeight: "700" },
  errorBox: { flex: 1, alignItems: "center", justifyContent: "center", padding: 24 },
  errorText: { color: "#f87171", textAlign: "center", marginBottom: 12 },
  retryBtn: { backgroundColor: "#7367F0", paddingHorizontal: 20, paddingVertical: 10, borderRadius: 8 },
  retryText: { color: "#fff", fontWeight: "700" },
});

function LeaveCard({
  item,
  onCancel,
  onRequestCancellation,
}: {
  item: Leave;
  onCancel: (id: number) => void;
  onRequestCancellation: (id: number) => void;
}) {
  const durationLabel =
    item.duration === "full"
      ? "Full Day"
      : item.duration === "half_am"
        ? "Half Day (AM)"
        : "Half Day (PM)";

  const fromLabel = new Date(item.from_date).toLocaleDateString("en-IN", {
    day: "2-digit", month: "short", year: "numeric",
  });
  const toLabel = new Date(item.to_date).toLocaleDateString("en-IN", {
    day: "2-digit", month: "short", year: "numeric",
  });

  return (
    <View style={styles.card}>
      {/* Header row */}
      <View style={styles.cardHeader}>
        <View style={styles.leaveTypeChip}>
          <Text style={styles.leaveTypeCode}>
            {item.leave_type?.code ?? "L"}
          </Text>
        </View>
        <View style={styles.cardHeaderRight}>
          <Text style={styles.leaveTypeName}>{item.leave_type?.name ?? "Leave"}</Text>
          <View style={[styles.statusBadge, { backgroundColor: (STATUS_COLOR[item.status] || "#64748b") + "22" }]}>
            <Text style={[styles.statusText, { color: STATUS_COLOR[item.status] || "#64748b" }]}>
              {STATUS_DISPLAY[item.status] || item.status.toUpperCase()}
            </Text>
          </View>
        </View>
      </View>

      {/* Date range */}
      <View style={styles.dateRow}>
        <MaterialCommunityIcons name="calendar-start" size={14} color="#64748b" />
        <Text style={styles.dateText}>{fromLabel}</Text>
        <MaterialCommunityIcons name="arrow-right" size={14} color="#475569" style={{ marginHorizontal: 4 }} />
        <MaterialCommunityIcons name="calendar-end" size={14} color="#64748b" />
        <Text style={styles.dateText}>{toLabel}</Text>
      </View>

      {/* Meta */}
      <View style={styles.metaRow}>
        <Text style={styles.metaText}>{durationLabel}</Text>
        <Text style={styles.metaDot}>·</Text>
        <Text style={styles.metaText}>
          {item.days_count} {item.days_count === 1 ? "day" : "days"}
        </Text>
      </View>

      {/* Reason */}
      {item.reason ? (
        <Text style={styles.reason} numberOfLines={2}>{item.reason}</Text>
      ) : null}

      {/* Rejection reason */}
      {item.status === "rejected" && item.rejected_reason ? (
        <View style={styles.rejectionBox}>
          <Text style={styles.rejectionLabel}>Reason: </Text>
          <Text style={styles.rejectionText}>{item.rejected_reason}</Text>
        </View>
      ) : null}

      {/* Action buttons */}
      {item.status === "pending" && (
        <TouchableOpacity
          style={styles.cancelBtn}
          onPress={() => onCancel(item.id)}
          accessibilityLabel="Cancel leave application"
        >
          <MaterialCommunityIcons name="close-circle-outline" size={16} color="#f87171" />
          <Text style={styles.cancelText}>Cancel Application</Text>
        </TouchableOpacity>
      )}

      {item.status === "approved" && (
        <TouchableOpacity
          style={[styles.cancelBtn, { backgroundColor: "#f59e0b15" }]}
          onPress={() => onRequestCancellation(item.id)}
          accessibilityLabel="Request cancellation for approved leave"
        >
          <MaterialCommunityIcons name="undo-variant" size={16} color="#f59e0b" />
          <Text style={[styles.cancelText, { color: "#f59e0b" }]}>Request Cancellation</Text>
        </TouchableOpacity>
      )}
    </View>
  );
}

function BalanceCard({ item }: { item: LeaveBalance }) {
  const totalPool = (item.allocated || 0) + (item.carried_forward || 0);
  const pct = totalPool > 0 ? Math.min(((item.used + (item.pending_days || 0)) / totalPool) * 100, 100) : 0;
  const isMonthly = item.leave_type?.accrual_frequency === "monthly";
  const pendingDays = item.pending_days || 0;
  const netAvailable = item.effective_available ?? (item.available - pendingDays);

  return (
    <View style={styles.balanceCard}>
      <View style={styles.balanceHeader}>
        <View style={{ flex: 1 }}>
          <Text style={styles.balanceName}>{item.leave_type.name}</Text>
          <Text style={{ fontSize: 11, color: isMonthly ? "#f59e0b" : "#64748b", fontWeight: "600", marginTop: 2 }}>
            {isMonthly ? `📅 Monthly Accrual (+${item.leave_type.days_per_month ?? 0}/mo)` : "🗓️ Annual Upfront Policy"}
          </Text>
        </View>
        <Text style={styles.balanceCode}>{item.leave_type.code}</Text>
      </View>

      {/* Available Hero Banner */}
      <View style={styles.availableBanner}>
        <View style={{ flex: 1 }}>
          <Text style={styles.bannerSubtitle}>Net Usable Available Balance</Text>
          <Text style={styles.bannerFormula}>
            ({item.allocated} {isMonthly ? "Earned" : "Alloc"}{item.carried_forward > 0 ? ` + ${item.carried_forward} Carry` : ""}) - {item.used} Used{pendingDays > 0 ? ` - ${pendingDays} Pending` : ""}
          </Text>
        </View>
        <Text style={styles.bannerAvailableVal}>{netAvailable} <Text style={{ fontSize: 13, fontWeight: "600" }}>Days</Text></Text>
      </View>

      <View style={styles.balanceStats}>
        <View style={styles.balanceStat}>
          <Text style={styles.balanceVal}>{item.allocated}</Text>
          <Text style={styles.balanceLbl}>{isMonthly ? "Earned" : "Allocated"}</Text>
        </View>

        {item.carried_forward > 0 && (
          <View style={styles.balanceStat}>
            <Text style={[styles.balanceVal, { color: "#60a5fa" }]}>+{item.carried_forward}</Text>
            <Text style={styles.balanceLbl}>Carry Fwd</Text>
          </View>
        )}

        <View style={styles.balanceStat}>
          <Text style={[styles.balanceVal, { color: "#f87171" }]}>{item.used}</Text>
          <Text style={styles.balanceLbl}>Used</Text>
        </View>

        {pendingDays > 0 && (
          <View style={styles.balanceStat}>
            <Text style={[styles.balanceVal, { color: "#f59e0b" }]}>{pendingDays}</Text>
            <Text style={styles.balanceLbl}>Pending</Text>
          </View>
        )}

        <View style={styles.balanceStat}>
          <Text style={[styles.balanceVal, { color: "#22c55e" }]}>{netAvailable}</Text>
          <Text style={styles.balanceLbl}>Usable</Text>
        </View>
      </View>

      {/* Progress bar */}
      <View style={styles.progressTrack}>
        <View style={[styles.progressFill, { width: `${pct}%` as any }]} />
      </View>
    </View>
  );
}

export default function MyLeavesScreen({ navigation }: Props) {
  const [tab, setTab] = useState<Tab>("applications");
  const [leaves, setLeaves] = useState<Leave[]>([]);
  const [balances, setBalances] = useState<LeaveBalance[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const [lRes, bRes] = await Promise.all([getMyLeaves(), getLeaveBalance()]);
      setLeaves(lRes.data);
      setBalances(bRes.data);
    } catch (e) {
      const err = e as HttpError;
      setError(err.message ?? "Failed to load leave data.");
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { load(); }, [load]);

  const handleCancel = (id: number) => {
    Alert.alert(
      "Cancel Leave",
      "Are you sure you want to cancel this leave application?",
      [
        { text: "No", style: "cancel" },
        {
          text: "Yes, Cancel",
          style: "destructive",
          onPress: async () => {
            try {
              await cancelLeave(id);
              load(); // refresh
            } catch (e) {
              const err = e as HttpError;
              Alert.alert("Error", err.message ?? "Failed to cancel leave.");
            }
          },
        },
      ]
    );
  };

  const handleRequestCancellation = (id: number) => {
    Alert.alert(
      "Request Cancellation",
      "Are you sure you want to request cancellation for this approved leave?",
      [
        { text: "No", style: "cancel" },
        {
          text: "Yes, Request Cancellation",
          style: "destructive",
          onPress: async () => {
            try {
              await requestLeaveCancellation(id);
              Alert.alert("Success", "Cancellation request submitted. Awaiting admin approval.");
              load();
            } catch (e) {
              const err = e as HttpError;
              Alert.alert("Error", err.message ?? "Failed to request cancellation.");
            }
          },
        },
      ]
    );
  };

  const tabs: Array<{ key: Tab; label: string }> = [
    { key: "applications", label: "Applications" },
    { key: "balance", label: "Balance" },
  ];

  return (
    <View style={styles.container}>
      <ScreenHeader
        title="My Leaves"
        onBack={() => navigation.goBack()}
        bgColor="#7367F0"
        rightElement={
          <TouchableOpacity
            onPress={() => navigation.navigate("LeaveApplication")}
            accessibilityLabel="Apply for leave"
            style={styles.applyBtn}
          >
            <MaterialCommunityIcons name="plus" size={22} color="#fff" />
          </TouchableOpacity>
        }
      />

      {/* Tabs */}
      <View style={styles.tabBar}>
        {tabs.map((t) => (
          <TouchableOpacity
            key={t.key}
            style={[styles.tab, tab === t.key && styles.tabActive]}
            onPress={() => setTab(t.key)}
          >
            <Text style={[styles.tabText, tab === t.key && styles.tabTextActive]}>
              {t.label}
            </Text>
          </TouchableOpacity>
        ))}
      </View>

      {loading ? (
        <ActivityIndicator color="#7367F0" size="large" style={{ marginTop: 40 }} />
      ) : error ? (
        <View style={styles.errorBox}>
          <Text style={styles.errorText}>{error}</Text>
          <TouchableOpacity onPress={load} style={styles.retryBtn}>
            <Text style={styles.retryText}>Retry</Text>
          </TouchableOpacity>
        </View>
      ) : tab === "applications" ? (
        <FlatList
          data={leaves}
          keyExtractor={(i) => String(i.id)}
          contentContainerStyle={styles.list}
          ListEmptyComponent={
            <View style={styles.empty}>
              <MaterialCommunityIcons name="calendar-blank" size={48} color="#334155" />
              <Text style={styles.emptyText}>No leave applications yet.</Text>
              <TouchableOpacity
                style={styles.applyEmptyBtn}
                onPress={() => navigation.navigate("LeaveApplication")}
              >
                <Text style={styles.applyEmptyText}>Apply Now</Text>
              </TouchableOpacity>
            </View>
          }
          renderItem={({ item }) => (
            <LeaveCard item={item} onCancel={handleCancel} onRequestCancellation={handleRequestCancellation} />
          )}
        />
      ) : (
        <FlatList
          data={balances}
          keyExtractor={(i) => String(i.leave_type.id)}
          contentContainerStyle={styles.list}
          renderItem={({ item }) => <BalanceCard item={item} />}
        />
      )}
    </View>
  );
}
