import { MaterialCommunityIcons, MaterialIcons } from "@expo/vector-icons";
import { useNavigation, useFocusEffect } from "@react-navigation/native";
import type { NativeStackNavigationProp } from "@react-navigation/native-stack";
import { useCallback, useEffect, useRef, useState } from "react";
import {
  ActivityIndicator,
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

import ScreenHeader from "../components/ScreenHeader";
import { getExpenses, type ExpenseBill } from "../api/expense";
import type { AppStackParamList } from "../navigation/RootNavigator";

type Nav = NativeStackNavigationProp<AppStackParamList, "ExpenseList">;

const PURPLE = "#7367F0";

const FILTER_OPTIONS: {
  key: "all" | "pending" | "approved" | "rejected";
  label: string;
  icon: keyof typeof MaterialIcons.glyphMap;
}[] = [
  { key: "all", label: "All", icon: "receipt-long" },
  { key: "pending", label: "Pending", icon: "hourglass-empty" },
  { key: "approved", label: "Approved", icon: "check-circle" },
  { key: "rejected", label: "Rejected", icon: "cancel" },
];

export default function ExpenseListScreen() {
  const navigation = useNavigation<Nav>();

  const [expenses, setExpenses] = useState<ExpenseBill[]>([]);
  const [page, setPage] = useState(1);
  const [hasMore, setHasMore] = useState(true);
  const [total, setTotal] = useState(0);

  const [loading, setLoading] = useState(true);
  const [loadingMore, setLoadingMore] = useState(false);
  const [refreshing, setRefreshing] = useState(false);

  const [filterStatus, setFilterStatus] = useState<
    "all" | "pending" | "approved" | "rejected"
  >("all");

  const [counts, setCounts] = useState({
    all: 0,
    pending: 0,
    approved: 0,
    rejected: 0,
  });

  // Search state (instant text input + debounced fetch)
  const [query, setQuery] = useState("");
  const queryRef = useRef("");
  queryRef.current = query;

  const filterStatusRef = useRef(filterStatus);
  filterStatusRef.current = filterStatus;

  const requestIdRef = useRef(0);
  const isInitialMount = useRef(true);

  // Fetch paginated expenses from backend
  const loadExpenses = useCallback(
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

      try {
        const res = await getExpenses({
          page: pageToLoad,
          per_page: 15,
          status: statusToFilter,
          search: searchQuery.trim(),
        });

        if (currentRequestId !== requestIdRef.current) return;

        if (res.success) {
          if (pageToLoad === 1) {
            setExpenses(res.data);
          } else {
            setExpenses((prev) => {
              const existing = new Set(prev.map((e) => e.id));
              const fresh = res.data.filter((e) => !existing.has(e.id));
              return [...prev, ...fresh];
            });
          }

          setPage(res.current_page ?? pageToLoad);
          setHasMore(res.has_more ?? false);
          setTotal(res.total ?? res.data.length);

          if (res.counts) {
            setCounts(res.counts);
          }
        }
      } catch (err) {
        console.warn("Failed to fetch expenses:", err);
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

  // Initial load
  useEffect(() => {
    loadExpenses(1, filterStatusRef.current, queryRef.current);
    isInitialMount.current = false;
  }, [loadExpenses]);

  // Debounced search when user types in search box
  useEffect(() => {
    if (isInitialMount.current) return;

    const timer = setTimeout(() => {
      setPage(1);
      loadExpenses(1, filterStatusRef.current, query);
    }, 350);

    return () => clearTimeout(timer);
  }, [query, loadExpenses]);

  // Handle filter tab click
  const handleFilterChange = (
    newStatus: "all" | "pending" | "approved" | "rejected"
  ) => {
    if (newStatus === filterStatus) return;
    setFilterStatus(newStatus);
    setPage(1);
    loadExpenses(1, newStatus, queryRef.current);
  };

  // Pull-to-refresh
  const handleRefresh = () => {
    loadExpenses(1, filterStatusRef.current, queryRef.current, true);
  };

  // On-scroll infinite chunk loading
  const handleEndReached = () => {
    if (!loading && !loadingMore && hasMore) {
      loadExpenses(page + 1, filterStatusRef.current, queryRef.current);
    }
  };

  // Refetch when returning to screen
  useFocusEffect(
    useCallback(() => {
      if (isInitialMount.current) return;
      loadExpenses(1, filterStatusRef.current, queryRef.current, true);
    }, [loadExpenses])
  );

  const renderExpenseItem = ({ item }: { item: ExpenseBill }) => {
    const isPending = item.status === "pending";
    const isVerified = item.status === "temp_approved";
    const isApproved = item.status === "approved";
    const isRejected =
      item.status === "rejected" || item.status === "manager_rejected";

    let badgeText = item.status ? item.status.toUpperCase() : "PENDING";
    let badgeStyle = styles.statusPending;

    if (item.status === "pending") {
      badgeText = "PENDING (L1)";
      badgeStyle = styles.statusPending;
    } else if (item.status === "temp_approved") {
      badgeText = "VERIFIED (L1)";
      badgeStyle = styles.statusVerified;
    } else if (item.status === "approved") {
      badgeText = "APPROVED";
      badgeStyle = styles.statusApproved;
    } else if (item.status === "manager_rejected") {
      badgeText = "MGR REJECTED";
      badgeStyle = styles.statusRejected;
    } else if (item.status === "rejected") {
      badgeText = "REJECTED";
      badgeStyle = styles.statusRejected;
    }

    return (
      <View style={styles.card}>
        <View style={styles.cardHeader}>
          <View
            style={{
              flexDirection: "row",
              alignItems: "center",
              flex: 1,
              marginRight: 8,
            }}
          >
            <MaterialCommunityIcons
              name={item.is_system_generated ? "car-connected" : "receipt"}
              size={20}
              color={item.is_system_generated ? "#7367F0" : "#F59E0B"}
            />
            <Text style={styles.categoryName} numberOfLines={1}>
              {item.category?.name ||
                (item.is_system_generated ? "Auto Travel" : "Unknown")}
            </Text>
            {item.items && item.items.length > 0 && (
              <View style={styles.itemsBadge}>
                <Text style={styles.itemsBadgeText}>
                  {item.items.length} items
                </Text>
              </View>
            )}
          </View>
          <Text style={[styles.statusBadge, badgeStyle]}>{badgeText}</Text>
        </View>

        <View style={styles.cardBody}>
          <Text style={styles.amountText}>
            ₹{Number(item.amount).toFixed(2)}
          </Text>
          <View style={{ alignItems: "flex-end" }}>
            <Text style={styles.dateText}>
              {item.date
                ? new Date(item.date).toLocaleDateString()
                : new Date(item.created_at).toLocaleDateString()}
            </Text>
            {item.is_after_day_end && (
              <Text
                style={{
                  fontSize: 10,
                  color: "#D97706",
                  fontWeight: "700",
                  marginTop: 2,
                }}
              >
                ⚠️ Post Day-End
              </Text>
            )}
          </View>
        </View>

        {/* Rejection Box if rejected */}
        {isRejected && (
          <View style={styles.rejectionBox}>
            <View style={{ flexDirection: "row", alignItems: "flex-start" }}>
              <MaterialIcons
                name="error-outline"
                size={16}
                color="#DC2626"
                style={{ marginTop: 1 }}
              />
              <View style={{ flex: 1, marginLeft: 6 }}>
                <Text style={styles.rejectionTitle}>
                  {item.status === "manager_rejected"
                    ? "Manager Rejection Reason:"
                    : "Rejection Reason:"}
                </Text>
                <Text style={styles.rejectionReasonText}>
                  {item.rejection_reason || "No detailed reason provided."}
                </Text>
                {item.rejected_by?.name && (
                  <Text style={styles.rejectedByText}>
                    By: {item.rejected_by.name}
                  </Text>
                )}
              </View>
            </View>
          </View>
        )}

        {/* Verification note if verified */}
        {isVerified && item.temp_approved_note && (
          <View style={styles.infoNoteBox}>
            <Text style={styles.infoNoteText}>
              Note: “{item.temp_approved_note}”
            </Text>
          </View>
        )}

        {/* Final approval note if present */}
        {isApproved && item.final_note && (
          <View style={styles.approvedNoteBox}>
            <Text style={styles.approvedNoteText}>
              Note: “{item.final_note}”
            </Text>
          </View>
        )}

        {/* Edit / Resubmit Action Button */}
        {!item.is_system_generated && (isRejected || isPending) && (
          <View style={styles.cardFooter}>
            <TouchableOpacity
              style={[
                styles.editActionBtn,
                isRejected && styles.editActionBtnRejected,
              ]}
              onPress={() =>
                navigation.navigate("AddExpense", { expense: item })
              }
            >
              <MaterialIcons
                name="edit"
                size={14}
                color={isRejected ? "#fff" : "#7367F0"}
              />
              <Text
                style={[
                  styles.editActionBtnText,
                  isRejected && styles.editActionBtnTextRejected,
                ]}
              >
                {isRejected ? "Edit & Resubmit Claim" : "Edit Claim"}
              </Text>
            </TouchableOpacity>
          </View>
        )}
      </View>
    );
  };

  const renderFooter = () => {
    if (!loadingMore) return null;
    return (
      <View style={styles.footerLoader}>
        <ActivityIndicator size="small" color={PURPLE} />
        <Text style={styles.footerLoaderText}>Loading more expenses...</Text>
      </View>
    );
  };

  return (
    <View style={{ flex: 1, backgroundColor: "#F4F4F8" }}>
      <ScreenHeader
        title="My Expenses"
        onBack={() => navigation.goBack()}
        rightElement={
          <TouchableOpacity
            style={{
              width: 36,
              height: 36,
              alignItems: "center",
              justifyContent: "center",
            }}
            onPress={() => navigation.navigate("AddExpense")}
          >
            <MaterialIcons name="add" size={24} color="#fff" />
          </TouchableOpacity>
        }
      />

      {/* ── Search Bar (Fast & Responsive) ── */}
      <View style={styles.searchSection}>
        <View style={styles.searchBox}>
          <MaterialIcons name="search" size={20} color="#94A3B8" />
          <TextInput
            style={styles.searchInput}
            placeholder="Search category, amount, notes, reasons..."
            placeholderTextColor="#94A3B8"
            value={query}
            onChangeText={setQuery}
            autoCorrect={false}
            clearButtonMode="while-editing"
          />
          {query.length > 0 && (
            <TouchableOpacity onPress={() => setQuery("")} hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }}>
              <MaterialIcons name="close" size={18} color="#94A3B8" />
            </TouchableOpacity>
          )}
        </View>
      </View>

      {/* ── Filter Capsule Slider (matching ReportScreen design) ── */}
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
                style={[styles.filterChip, active && styles.filterChipActive]}
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
        <View style={{ flex: 1, justifyContent: "center", alignItems: "center" }}>
          <ActivityIndicator size="large" color="#7367F0" />
          <Text style={{ marginTop: 12, color: "#64748B", fontSize: 13, fontWeight: "600" }}>
            Loading expenses...
          </Text>
        </View>
      ) : (
        <FlatList
          data={expenses}
          keyExtractor={(item) => item.id.toString()}
          contentContainerStyle={styles.listContent}
          renderItem={renderExpenseItem}
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
            <View style={styles.emptyContainer}>
              <MaterialCommunityIcons
                name="receipt-text-outline"
                size={56}
                color="#CBD5E1"
              />
              <Text style={styles.emptyTitle}>No Expenses Found</Text>
              <Text style={styles.emptySubtitle}>
                {query.trim().length > 0
                  ? `No results matching "${query}".`
                  : filterStatus !== "all"
                  ? `No ${filterStatus} expenses found.`
                  : "Tap + at the top right to record a new expense claim."}
              </Text>
            </View>
          }
        />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: "#F3F4F6" },
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
  listContent: {
    padding: 16,
    paddingBottom: 40,
  },
  card: {
    backgroundColor: "#fff",
    borderRadius: 16,
    padding: 16,
    marginBottom: 12,
    elevation: 3,
    shadowColor: "#000",
    shadowOpacity: 0.05,
    shadowRadius: 5,
  },
  cardHeader: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 12,
  },
  categoryName: {
    fontSize: 14,
    fontWeight: "700",
    color: "#374151",
    marginLeft: 8,
  },
  statusBadge: {
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 8,
    fontSize: 10,
    fontWeight: "800",
    color: "#fff",
    overflow: "hidden",
  },
  statusPending: { backgroundColor: "#F59E0B" },
  statusVerified: { backgroundColor: "#2563EB" },
  statusApproved: { backgroundColor: "#10B981" },
  statusRejected: { backgroundColor: "#DC2626" },
  itemsBadge: {
    backgroundColor: "#EEF2FF",
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 6,
    marginLeft: 6,
  },
  itemsBadgeText: {
    fontSize: 10,
    fontWeight: "700",
    color: "#4F46E5",
  },
  cardBody: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "flex-end",
  },
  amountText: {
    fontSize: 20,
    fontWeight: "900",
    color: "#111827",
  },
  dateText: {
    fontSize: 12,
    fontWeight: "600",
    color: "#9CA3AF",
  },
  rejectionBox: {
    marginTop: 12,
    padding: 10,
    backgroundColor: "#FEF2F2",
    borderRadius: 10,
    borderWidth: 1,
    borderColor: "#FECACA",
  },
  rejectionTitle: {
    fontSize: 12,
    fontWeight: "800",
    color: "#B91C1C",
  },
  rejectionReasonText: {
    fontSize: 12,
    color: "#991B1B",
    marginTop: 2,
    fontWeight: "500",
  },
  rejectedByText: {
    fontSize: 11,
    color: "#DC2626",
    marginTop: 3,
    fontWeight: "600",
  },
  infoNoteBox: {
    marginTop: 8,
    padding: 8,
    backgroundColor: "#EFF6FF",
    borderRadius: 8,
    borderWidth: 1,
    borderColor: "#BFDBFE",
  },
  infoNoteText: {
    fontSize: 11,
    color: "#1E40AF",
    fontStyle: "italic",
  },
  approvedNoteBox: {
    marginTop: 8,
    padding: 8,
    backgroundColor: "#F0FDF4",
    borderRadius: 8,
    borderWidth: 1,
    borderColor: "#BBF7D0",
  },
  approvedNoteText: {
    fontSize: 11,
    color: "#166534",
    fontStyle: "italic",
  },
  cardFooter: {
    marginTop: 12,
    paddingTop: 10,
    borderTopWidth: 1,
    borderTopColor: "#F3F4F6",
    flexDirection: "row",
    justifyContent: "flex-end",
  },
  editActionBtn: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#EEF2FF",
    paddingHorizontal: 12,
    paddingVertical: 7,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: "#C7D2FE",
  },
  editActionBtnRejected: {
    backgroundColor: "#DC2626",
    borderColor: "#B91C1C",
  },
  editActionBtnText: {
    fontSize: 12,
    fontWeight: "700",
    color: "#4F46E5",
    marginLeft: 4,
  },
  editActionBtnTextRejected: {
    color: "#FFFFFF",
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
  emptyContainer: {
    alignItems: "center",
    justifyContent: "center",
    paddingVertical: 60,
    paddingHorizontal: 20,
  },
  emptyTitle: {
    fontSize: 16,
    fontWeight: "800",
    color: "#334155",
    marginTop: 12,
  },
  emptySubtitle: {
    fontSize: 13,
    color: "#64748B",
    textAlign: "center",
    marginTop: 6,
    lineHeight: 18,
  },
});
