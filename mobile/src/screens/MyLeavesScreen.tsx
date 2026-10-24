import React, { useCallback, useEffect, useRef, useState } from "react";
import {
  ActivityIndicator,
  Alert,
  FlatList,
  Platform,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from "react-native";
import { MaterialCommunityIcons, MaterialIcons } from "@expo/vector-icons";
import ScreenHeader from "../components/ScreenHeader";
import {
  cancelLeave,
  getLeaveBalance,
  getMyLeaves,
  Leave,
  LeaveBalance,
  requestLeaveCancellation,
} from "../api/leave";
import { HttpError } from "../api/http";
import { useAlert } from "../context/AlertModalContext";
import { type NativeStackScreenProps } from "@react-navigation/native-stack";
import { type AppStackParamList } from "../navigation/RootNavigator";

type Props = NativeStackScreenProps<AppStackParamList, "MyLeaves">;
type Tab = "applications" | "balance";

const PURPLE = "#7367F0";

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

const FILTER_OPTIONS: {
  key: "all" | "pending" | "approved" | "rejected" | "cancelled";
  label: string;
  icon: keyof typeof MaterialIcons.glyphMap;
}[] = [
  { key: "all", label: "All", icon: "event-note" },
  { key: "pending", label: "Pending", icon: "hourglass-empty" },
  { key: "approved", label: "Approved", icon: "check-circle" },
  { key: "rejected", label: "Rejected", icon: "cancel" },
  { key: "cancelled", label: "Cancelled", icon: "block" },
];

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
    day: "2-digit",
    month: "short",
    year: "numeric",
  });
  const toLabel = new Date(item.to_date).toLocaleDateString("en-IN", {
    day: "2-digit",
    month: "short",
    year: "numeric",
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
          <Text style={styles.leaveTypeName}>
            {item.leave_type?.name ?? "Leave"}
          </Text>
          <View
            style={[
              styles.statusBadge,
              {
                backgroundColor:
                  (STATUS_COLOR[item.status] || "#64748b") + "22",
              },
            ]}
          >
            <Text
              style={[
                styles.statusText,
                { color: STATUS_COLOR[item.status] || "#64748b" },
              ]}
            >
              {STATUS_DISPLAY[item.status] || item.status.toUpperCase()}
            </Text>
          </View>
        </View>
      </View>

      {/* Date range */}
      <View style={styles.dateRow}>
        <MaterialCommunityIcons
          name="calendar-start"
          size={14}
          color="#64748b"
        />
        <Text style={styles.dateText}>{fromLabel}</Text>
        <MaterialCommunityIcons
          name="arrow-right"
          size={14}
          color="#475569"
          style={{ marginHorizontal: 4 }}
        />
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
        <Text style={styles.reason} numberOfLines={2}>
          {item.reason}
        </Text>
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
          <MaterialCommunityIcons
            name="close-circle-outline"
            size={16}
            color="#f87171"
          />
          <Text style={styles.cancelText}>Cancel Application</Text>
        </TouchableOpacity>
      )}

      {item.status === "approved" && (
        <TouchableOpacity
          style={[styles.cancelBtn, { backgroundColor: "#f59e0b15" }]}
          onPress={() => onRequestCancellation(item.id)}
          accessibilityLabel="Request cancellation for approved leave"
        >
          <MaterialCommunityIcons
            name="undo-variant"
            size={16}
            color="#f59e0b"
          />
          <Text style={[styles.cancelText, { color: "#f59e0b" }]}>
            Request Cancellation
          </Text>
        </TouchableOpacity>
      )}
    </View>
  );
}

function BalanceCard({ item }: { item: LeaveBalance }) {
  const totalPool = (item.allocated || 0) + (item.carried_forward || 0);
  const pct =
    totalPool > 0
      ? Math.min(
          ((item.used + (item.pending_days || 0)) / totalPool) * 100,
          100
        )
      : 0;
  const isMonthly = item.leave_type?.accrual_frequency === "monthly";
  const pendingDays = item.pending_days || 0;
  const netAvailable =
    item.effective_available ?? item.available - pendingDays;

  return (
    <View style={styles.balanceCard}>
      <View style={styles.balanceHeader}>
        <View style={{ flex: 1 }}>
          <Text style={styles.balanceName}>{item.leave_type.name}</Text>
          <Text
            style={{
              fontSize: 11,
              color: isMonthly ? "#f59e0b" : "#64748b",
              fontWeight: "600",
              marginTop: 2,
            }}
          >
            {isMonthly
              ? `📅 Monthly Accrual (+${item.leave_type.days_per_month ?? 0}/mo)`
              : "🗓️ Annual Upfront Policy"}
          </Text>
        </View>
        <Text style={styles.balanceCode}>{item.leave_type.code}</Text>
      </View>

      {/* Available Hero Banner */}
      <View style={styles.availableBanner}>
        <View style={{ flex: 1 }}>
          <Text style={styles.bannerSubtitle}>Net Usable Available Balance</Text>
          <Text style={styles.bannerFormula}>
            ({item.allocated} {isMonthly ? "Earned" : "Alloc"}
            {item.carried_forward > 0 ? ` + ${item.carried_forward} Carry` : ""}
            ) - {item.used} Used
            {pendingDays > 0 ? ` - ${pendingDays} Pending` : ""}
          </Text>
        </View>
        <Text style={styles.bannerAvailableVal}>
          {netAvailable}{" "}
          <Text style={{ fontSize: 13, fontWeight: "600" }}>Days</Text>
        </Text>
      </View>

      <View style={styles.balanceStats}>
        <View style={styles.balanceStat}>
          <Text style={styles.balanceVal}>{item.allocated}</Text>
          <Text style={styles.balanceLbl}>
            {isMonthly ? "Earned" : "Allocated"}
          </Text>
        </View>

        {item.carried_forward > 0 && (
          <View style={styles.balanceStat}>
            <Text style={[styles.balanceVal, { color: "#60a5fa" }]}>
              +{item.carried_forward}
            </Text>
            <Text style={styles.balanceLbl}>Carry Fwd</Text>
          </View>
        )}

        <View style={styles.balanceStat}>
          <Text style={[styles.balanceVal, { color: "#f87171" }]}>
            {item.used}
          </Text>
          <Text style={styles.balanceLbl}>Used</Text>
        </View>

        {pendingDays > 0 && (
          <View style={styles.balanceStat}>
            <Text style={[styles.balanceVal, { color: "#f59e0b" }]}>
              {pendingDays}
            </Text>
            <Text style={styles.balanceLbl}>Pending</Text>
          </View>
        )}

        <View style={styles.balanceStat}>
          <Text style={[styles.balanceVal, { color: "#22c55e" }]}>
            {netAvailable}
          </Text>
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
  const { showSuccess, showError, showConfirm } = useAlert();
  const [tab, setTab] = useState<Tab>("applications");
  const [leaves, setLeaves] = useState<Leave[]>([]);
  const [balances, setBalances] = useState<LeaveBalance[]>([]);

  // Applications pagination state
  const [page, setPage] = useState(1);
  const [hasMore, setHasMore] = useState(true);
  const [loading, setLoading] = useState(false);
  const [loadingMore, setLoadingMore] = useState(false);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);

  // Status Filter Capsule state
  const [filterStatus, setFilterStatus] = useState<
    "all" | "pending" | "approved" | "rejected" | "cancelled"
  >("all");
  const [counts, setCounts] = useState({
    all: 0,
    pending: 0,
    approved: 0,
    rejected: 0,
    cancelled: 0,
  });

  // Search state (instant text input + debounced fetch)
  const [query, setQuery] = useState("");
  const queryRef = useRef("");
  queryRef.current = query;

  const filterStatusRef = useRef(filterStatus);
  filterStatusRef.current = filterStatus;

  const requestIdRef = useRef(0);
  const isInitialMount = useRef(true);

  // Fetch paginated leaves
  const loadLeaves = useCallback(
    async (
      pageToLoad: number,
      statusToFilter: string,
      searchQuery: string,
      isRefresh = false
    ) => {
      const currentRequestId = ++requestIdRef.current;

      if (pageToLoad === 1) {
        if (isRefresh) {
          setRefreshing(true);
        } else {
          setLoading(true);
        }
      } else {
        setLoadingMore(true);
      }
      setError(null);

      try {
        const res = await getMyLeaves({
          page: pageToLoad,
          per_page: 15,
          status: statusToFilter,
          search: searchQuery.trim(),
        });

        if (currentRequestId !== requestIdRef.current) return;

        if (res.success) {
          if (pageToLoad === 1) {
            setLeaves(res.data);
          } else {
            setLeaves((prev) => {
              const existing = new Set(prev.map((l) => l.id));
              const fresh = res.data.filter((l) => !existing.has(l.id));
              return [...prev, ...fresh];
            });
          }

          setPage(res.current_page ?? pageToLoad);
          setHasMore(res.has_more ?? false);

          if (res.counts) {
            setCounts(res.counts);
          }
        }
      } catch (e: any) {
        if (currentRequestId === requestIdRef.current) {
          const err = e as HttpError;
          setError(err.message ?? "Failed to load leave applications.");
        }
      } finally {
        if (currentRequestId === requestIdRef.current) {
          setLoading(false);
          setLoadingMore(false);
          setRefreshing(false);
        }
      }
    },
    []
  );

  // Fetch balance data
  const loadBalances = useCallback(async () => {
    try {
      const bRes = await getLeaveBalance();
      if (bRes.success) {
        setBalances(bRes.data);
      }
    } catch (e) {
      console.warn("Failed to load balances:", e);
    }
  }, []);

  // Initial load
  useEffect(() => {
    loadLeaves(1, filterStatusRef.current, queryRef.current);
    loadBalances();
    isInitialMount.current = false;
  }, [loadLeaves, loadBalances]);

  // Debounced search when user types in search box
  useEffect(() => {
    if (isInitialMount.current) return;

    const timer = setTimeout(() => {
      setPage(1);
      loadLeaves(1, filterStatusRef.current, query);
    }, 350);

    return () => clearTimeout(timer);
  }, [query, loadLeaves]);

  // Handle status filter change
  const handleFilterChange = (
    newStatus: "all" | "pending" | "approved" | "rejected" | "cancelled"
  ) => {
    if (newStatus === filterStatus) return;
    setFilterStatus(newStatus);
    setPage(1);
    loadLeaves(1, newStatus, queryRef.current);
  };

  // Pull-to-refresh
  const handleRefresh = () => {
    if (tab === "applications") {
      loadLeaves(1, filterStatusRef.current, queryRef.current, true);
    } else {
      setRefreshing(true);
      loadBalances().finally(() => setRefreshing(false));
    }
  };

  // On-scroll infinite loading
  const handleEndReached = () => {
    if (!loading && !loadingMore && hasMore) {
      loadLeaves(page + 1, filterStatusRef.current, queryRef.current);
    }
  };

  const handleCancel = (id: number) => {
    showConfirm({
      title: "Cancel Leave Application",
      message: "Are you sure you want to cancel this pending leave application?",
      confirmText: "Yes, Cancel",
      cancelText: "Keep Application",
      isDestructive: true,
      onConfirm: async () => {
        try {
          await cancelLeave(id);
          showSuccess("Application Cancelled", "Your leave application has been cancelled.");
          loadLeaves(1, filterStatusRef.current, queryRef.current, true);
          loadBalances();
        } catch (e) {
          const err = e as HttpError;
          showError("Cancellation Failed", err.message ?? "Failed to cancel leave.");
        }
      },
    });
  };

  const handleRequestCancellation = (id: number) => {
    showConfirm({
      title: "Request Cancellation",
      message: "Are you sure you want to request cancellation for this approved leave?",
      confirmText: "Request Cancellation",
      cancelText: "No, Keep Leave",
      onConfirm: async () => {
        try {
          await requestLeaveCancellation(id);
          showSuccess(
            "Request Submitted",
            "Cancellation request submitted. Awaiting manager approval."
          );
          loadLeaves(1, filterStatusRef.current, queryRef.current, true);
          loadBalances();
        } catch (e) {
          const err = e as HttpError;
          showError("Request Failed", err.message ?? "Failed to request cancellation.");
        }
      },
    });
  };

  const tabs: Array<{ key: Tab; label: string }> = [
    { key: "applications", label: "Applications" },
    { key: "balance", label: "Balance" },
  ];

  const renderFooter = () => {
    if (!loadingMore) return null;
    return (
      <View style={styles.footerLoader}>
        <ActivityIndicator size="small" color={PURPLE} />
        <Text style={styles.footerLoaderText}>
          Loading more applications...
        </Text>
      </View>
    );
  };

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
            <Text
              style={[styles.tabText, tab === t.key && styles.tabTextActive]}
            >
              {t.label}
            </Text>
          </TouchableOpacity>
        ))}
      </View>

      {tab === "applications" ? (
        <>
          {/* Search bar */}
          <View style={styles.searchSection}>
            <View style={styles.searchBox}>
              <MaterialIcons name="search" size={20} color="#94A3B8" />
              <TextInput
                style={styles.searchInput}
                placeholder="Search reason, leave type, rejection note..."
                placeholderTextColor="#94A3B8"
                value={query}
                onChangeText={setQuery}
                autoCorrect={false}
                clearButtonMode="while-editing"
              />
              {query.length > 0 && (
                <TouchableOpacity
                  onPress={() => setQuery("")}
                  hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }}
                >
                  <MaterialIcons name="close" size={18} color="#94A3B8" />
                </TouchableOpacity>
              )}
            </View>
          </View>

          {/* Filter Capsule Slider */}
          <View style={styles.filterSliderContainer}>
            <ScrollView
              horizontal
              showsHorizontalScrollIndicator={false}
              contentContainerStyle={styles.filterPillsWrap}
            >
              {FILTER_OPTIONS.map((item) => {
                const active = filterStatus === item.key;
                const count = counts[item.key] ?? 0;
                return (
                  <TouchableOpacity
                    key={item.key}
                    style={[
                      styles.filterChip,
                      active && styles.filterChipActive,
                    ]}
                    onPress={() => handleFilterChange(item.key)}
                    activeOpacity={0.8}
                  >
                    <MaterialIcons
                      name={item.icon}
                      size={16}
                      color={active ? "#FFFFFF" : "#64748B"}
                    />
                    <Text
                      style={[
                        styles.filterChipText,
                        active && styles.filterChipTextActive,
                      ]}
                    >
                      {item.label}
                    </Text>
                    <View
                      style={[
                        styles.chipCountBadge,
                        active && styles.chipCountBadgeActive,
                      ]}
                    >
                      <Text
                        style={[
                          styles.chipCountText,
                          active && styles.chipCountTextActive,
                        ]}
                      >
                        {count}
                      </Text>
                    </View>
                  </TouchableOpacity>
                );
              })}
            </ScrollView>
          </View>

          {loading ? (
            <View
              style={{
                flex: 1,
                justifyContent: "center",
                alignItems: "center",
              }}
            >
              <ActivityIndicator color={PURPLE} size="large" />
              <Text
                style={{
                  marginTop: 12,
                  color: "#64748B",
                  fontSize: 13,
                  fontWeight: "600",
                }}
              >
                Loading leave applications...
              </Text>
            </View>
          ) : error ? (
            <View style={styles.errorBox}>
              <Text style={styles.errorText}>{error}</Text>
              <TouchableOpacity
                onPress={() =>
                  loadLeaves(1, filterStatusRef.current, queryRef.current)
                }
                style={styles.retryBtn}
              >
                <Text style={styles.retryText}>Retry</Text>
              </TouchableOpacity>
            </View>
          ) : (
            <FlatList
              data={leaves}
              keyExtractor={(i) => String(i.id)}
              contentContainerStyle={styles.list}
              initialNumToRender={10}
              maxToRenderPerBatch={10}
              windowSize={5}
              removeClippedSubviews={Platform.OS === "android"}
              onEndReachedThreshold={0.4}
              onEndReached={handleEndReached}
              ListFooterComponent={renderFooter}
              refreshControl={
                <RefreshControl
                  refreshing={refreshing}
                  onRefresh={handleRefresh}
                  colors={[PURPLE]}
                  tintColor={PURPLE}
                />
              }
              ListEmptyComponent={
                <View style={styles.empty}>
                  <MaterialCommunityIcons
                    name="calendar-blank"
                    size={48}
                    color="#94A3B8"
                  />
                  <Text style={styles.emptyText}>
                    {query.trim().length > 0
                      ? `No leaves matching "${query}".`
                      : filterStatus !== "all"
                      ? `No ${filterStatus} leave applications found.`
                      : "No leave applications yet."}
                  </Text>
                  <TouchableOpacity
                    style={styles.applyEmptyBtn}
                    onPress={() => navigation.navigate("LeaveApplication")}
                  >
                    <Text style={styles.applyEmptyText}>Apply Now</Text>
                  </TouchableOpacity>
                </View>
              }
              renderItem={({ item }) => (
                <LeaveCard
                  item={item}
                  onCancel={handleCancel}
                  onRequestCancellation={handleRequestCancellation}
                />
              )}
            />
          )}
        </>
      ) : (
        <FlatList
          data={balances}
          keyExtractor={(i) => String(i.leave_type.id)}
          contentContainerStyle={styles.list}
          refreshControl={
            <RefreshControl
              refreshing={refreshing}
              onRefresh={handleRefresh}
              colors={[PURPLE]}
              tintColor={PURPLE}
            />
          }
          renderItem={({ item }) => <BalanceCard item={item} />}
        />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#F4F4F8" },
  applyBtn: { padding: 4 },
  tabBar: {
    flexDirection: "row",
    backgroundColor: "#FFFFFF",
    borderBottomWidth: 1,
    borderColor: "#E5E7EB",
  },
  tab: { flex: 1, paddingVertical: 13, alignItems: "center" },
  tabActive: { borderBottomWidth: 2.5, borderColor: PURPLE },
  tabText: { color: "#6B7280", fontWeight: "700", fontSize: 14 },
  tabTextActive: { color: PURPLE, fontWeight: "800" },
  searchSection: {
    backgroundColor: "#FFFFFF",
    paddingHorizontal: 14,
    paddingTop: 10,
    paddingBottom: 6,
  },
  searchBox: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#F8FAFC",
    borderWidth: 1,
    borderColor: "#E2E8F0",
    borderRadius: 12,
    paddingHorizontal: 12,
    height: 42,
  },
  searchInput: {
    flex: 1,
    fontSize: 13,
    color: "#1E293B",
    marginLeft: 8,
    paddingVertical: 0,
  },
  filterSliderContainer: {
    backgroundColor: "#FFFFFF",
    borderBottomWidth: 1,
    borderBottomColor: "#E2E8F0",
    paddingVertical: 10,
    shadowColor: "#000",
    shadowOpacity: 0.03,
    shadowRadius: 3,
    elevation: 2,
  },
  filterPillsWrap: {
    paddingHorizontal: 14,
    gap: 8,
    flexDirection: "row",
    alignItems: "center",
  },
  filterChip: {
    flexDirection: "row",
    alignItems: "center",
    gap: 6,
    backgroundColor: "#FFFFFF",
    paddingHorizontal: 13,
    paddingVertical: 8,
    borderRadius: 20,
    borderWidth: 1,
    borderColor: "#E2E8F0",
    shadowColor: "#000",
    shadowOpacity: 0.03,
    shadowRadius: 4,
    elevation: 1,
  },
  filterChipActive: {
    backgroundColor: PURPLE,
    borderColor: PURPLE,
    shadowColor: PURPLE,
    shadowOpacity: 0.25,
    shadowRadius: 6,
    elevation: 3,
  },
  filterChipText: {
    fontSize: 12,
    fontWeight: "700",
    color: "#475569",
  },
  filterChipTextActive: {
    color: "#FFFFFF",
    fontWeight: "800",
  },
  chipCountBadge: {
    backgroundColor: "#F1F5F9",
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 10,
    minWidth: 18,
    alignItems: "center",
    justifyContent: "center",
  },
  chipCountBadgeActive: {
    backgroundColor: "rgba(255, 255, 255, 0.25)",
  },
  chipCountText: {
    fontSize: 10,
    fontWeight: "800",
    color: "#64748B",
  },
  chipCountTextActive: {
    color: "#FFFFFF",
  },
  list: { padding: 14, paddingBottom: 40 },
  card: {
    backgroundColor: "#FFFFFF",
    borderRadius: 14,
    padding: 16,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: "#E5E7EB",
    shadowColor: "#000",
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.05,
    shadowRadius: 3,
    elevation: 2,
  },
  cardHeader: {
    flexDirection: "row",
    alignItems: "center",
    marginBottom: 10,
  },
  leaveTypeChip: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: "#EDE9FE",
    alignItems: "center",
    justifyContent: "center",
    marginRight: 12,
  },
  leaveTypeCode: { color: PURPLE, fontWeight: "800", fontSize: 15 },
  cardHeaderRight: {
    flex: 1,
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
  },
  leaveTypeName: { color: "#111827", fontWeight: "700", fontSize: 15 },
  statusBadge: { borderRadius: 6, paddingHorizontal: 8, paddingVertical: 3 },
  statusText: { fontSize: 11, fontWeight: "700" },
  dateRow: {
    flexDirection: "row",
    alignItems: "center",
    marginBottom: 6,
    gap: 4,
  },
  dateText: { color: "#1F2937", fontSize: 13, fontWeight: "600" },
  metaRow: {
    flexDirection: "row",
    alignItems: "center",
    gap: 6,
    marginBottom: 6,
  },
  metaText: { color: "#6B7280", fontSize: 12, fontWeight: "600" },
  metaDot: { color: "#CBD5E1", fontSize: 16 },
  reason: {
    color: "#4B5563",
    fontSize: 13,
    marginTop: 4,
    fontStyle: "italic",
  },
  rejectionBox: {
    flexDirection: "row",
    backgroundColor: "#FEF2F2",
    borderRadius: 8,
    padding: 10,
    marginTop: 8,
    borderWidth: 1,
    borderColor: "#FECACA",
  },
  rejectionLabel: { color: "#DC2626", fontSize: 12, fontWeight: "700" },
  rejectionText: { color: "#B91C1C", fontSize: 12, flex: 1 },
  cancelBtn: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    gap: 6,
    marginTop: 12,
    backgroundColor: "#FEF2F2",
    borderRadius: 8,
    paddingVertical: 10,
    borderWidth: 1,
    borderColor: "#FECACA",
  },
  cancelText: { color: "#DC2626", fontWeight: "700", fontSize: 13 },
  balanceCard: {
    backgroundColor: "#FFFFFF",
    borderRadius: 14,
    padding: 16,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: "#E5E7EB",
    shadowColor: "#000",
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.05,
    shadowRadius: 3,
    elevation: 2,
  },
  balanceHeader: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 12,
  },
  balanceName: { color: "#111827", fontWeight: "800", fontSize: 16 },
  balanceCode: {
    color: PURPLE,
    fontWeight: "800",
    backgroundColor: "#EDE9FE",
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 8,
  },
  availableBanner: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    backgroundColor: "#F0FDF4",
    borderColor: "#BBF7D0",
    borderWidth: 1,
    borderRadius: 10,
    padding: 12,
    marginBottom: 14,
  },
  bannerSubtitle: { color: "#166534", fontSize: 12, fontWeight: "800" },
  bannerFormula: { color: "#4B5563", fontSize: 10, marginTop: 2 },
  bannerAvailableVal: {
    color: "#15803D",
    fontSize: 24,
    fontWeight: "800",
  },
  balanceStats: {
    flexDirection: "row",
    justifyContent: "space-around",
    marginBottom: 14,
    backgroundColor: "#F8FAFC",
    borderRadius: 10,
    paddingVertical: 10,
    borderWidth: 1,
    borderColor: "#E2E8F0",
  },
  balanceStat: { alignItems: "center" },
  balanceVal: { color: "#111827", fontSize: 18, fontWeight: "800" },
  balanceLbl: {
    color: "#64748B",
    fontSize: 11,
    fontWeight: "600",
    marginTop: 2,
  },
  progressTrack: {
    height: 6,
    backgroundColor: "#E2E8F0",
    borderRadius: 3,
    overflow: "hidden",
  },
  progressFill: {
    height: 6,
    backgroundColor: "#EF4444",
    borderRadius: 3,
  },
  footerLoader: {
    paddingVertical: 18,
    flexDirection: "row",
    justifyContent: "center",
    alignItems: "center",
    gap: 8,
  },
  footerLoaderText: {
    fontSize: 12,
    color: "#64748B",
    fontWeight: "600",
  },
  empty: { alignItems: "center", paddingTop: 60 },
  emptyText: {
    color: "#64748B",
    marginTop: 12,
    fontSize: 15,
    fontWeight: "600",
    textAlign: "center",
    paddingHorizontal: 20,
  },
  applyEmptyBtn: {
    marginTop: 20,
    backgroundColor: PURPLE,
    paddingHorizontal: 24,
    paddingVertical: 12,
    borderRadius: 10,
  },
  applyEmptyText: { color: "#fff", fontWeight: "700" },
  errorBox: {
    flex: 1,
    alignItems: "center",
    justifyContent: "center",
    padding: 24,
  },
  errorText: {
    color: "#EF4444",
    textAlign: "center",
    marginBottom: 12,
    fontWeight: "600",
  },
  retryBtn: {
    backgroundColor: PURPLE,
    paddingHorizontal: 20,
    paddingVertical: 10,
    borderRadius: 8,
  },
  retryText: { color: "#fff", fontWeight: "700" },
});
