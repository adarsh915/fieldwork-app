import { MaterialCommunityIcons, MaterialIcons } from "@expo/vector-icons";
import { useNavigation, useFocusEffect } from "@react-navigation/native";
import type { NativeStackNavigationProp } from "@react-navigation/native-stack";
import { useCallback, useEffect, useRef, useState } from "react";
import {
  ActivityIndicator,
  FlatList,
  Image,
  Modal,
  Platform,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from "react-native";
import { SafeAreaView, useSafeAreaInsets } from "react-native-safe-area-context";

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
  const insets = useSafeAreaInsets();

  const [selectedExpense, setSelectedExpense] = useState<ExpenseBill | null>(null);
  const [photoViewerVisible, setPhotoViewerVisible] = useState(false);
  const [selectedPhotoUrl, setSelectedPhotoUrl] = useState<string | null>(null);
  const [selectedPhotoTitle, setSelectedPhotoTitle] = useState("");

  const openPhotoViewer = (url: string | null | undefined, title: string) => {
    if (!url) return;
    setSelectedPhotoUrl(url);
    setSelectedPhotoTitle(title);
    setPhotoViewerVisible(true);
  };

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
      <TouchableOpacity
        style={styles.card}
        activeOpacity={0.88}
        onPress={() => setSelectedExpense(item)}
      >
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
          <View style={{ flex: 1, justifyContent: "center" }}>
            <Text style={styles.amountText}>
              ₹{Number(item.amount).toFixed(2)}
            </Text>
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

          {/* Receipt / Meter Photo Thumbnail on Card */}
          {item.bill_photo_url ? (
            <TouchableOpacity
              style={styles.cardThumbWrap}
              activeOpacity={0.8}
              onPress={() =>
                openPhotoViewer(
                  item.bill_photo_url,
                  `${item.category?.name || (item.is_system_generated ? "Travel" : "Expense")} Receipt`
                )
              }
            >
              <Image source={{ uri: item.bill_photo_url }} style={styles.cardThumbImg} />
              <View style={styles.thumbZoomBadge}>
                <MaterialIcons name="zoom-in" size={12} color="#fff" />
              </View>
            </TouchableOpacity>
          ) : (
            <View style={styles.noPhotoMiniBox}>
              <MaterialIcons name="image-not-supported" size={16} color="#CBD5E1" />
              <Text style={styles.noPhotoMiniText}>No Photo</Text>
            </View>
          )}
        </View>

        {/* Quick Itemized Preview for Stationery / Multi-item Expenses */}
        {item.items && item.items.length > 0 && (
          <View style={styles.itemsQuickPreviewWrap}>
            <Text style={styles.itemsQuickPreviewText} numberOfLines={1}>
              📋 {item.items.map((it) => `${it.item_name} (x${it.quantity})`).join(", ")}
            </Text>
          </View>
        )}

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

        {/* Action Buttons: View Details (Always) & Edit Claim (Pending/Rejected) */}
        <View style={styles.cardFooter}>
          <TouchableOpacity
            style={styles.viewDetailsBtn}
            activeOpacity={0.8}
            onPress={() => setSelectedExpense(item)}
          >
            <MaterialIcons name="visibility" size={14} color="#7367F0" />
            <Text style={styles.viewDetailsBtnText}>View Details</Text>
          </TouchableOpacity>

          {!item.is_system_generated && (isRejected || isPending) && (
            <TouchableOpacity
              style={[
                styles.editActionBtn,
                isRejected && styles.editActionBtnRejected,
              ]}
              activeOpacity={0.8}
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
                {!item.bill_photo_url ? "Add Photo / Edit" : (isRejected ? "Edit & Resubmit" : "Edit Claim")}
              </Text>
            </TouchableOpacity>
          )}
        </View>
      </TouchableOpacity>
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

      {/* ── EXPENSE DETAILS MODAL (FOR ALL STATUSES INCLUDING APPROVED) ── */}
      <Modal
        visible={!!selectedExpense}
        transparent
        animationType="slide"
        onRequestClose={() => setSelectedExpense(null)}
      >
        <View style={styles.modalBackdrop}>
          <View style={styles.modalCard}>
            <View style={styles.modalHeader}>
              <View style={{ flexDirection: "row", alignItems: "center", flex: 1, marginRight: 8 }}>
                <MaterialCommunityIcons
                  name={selectedExpense?.is_system_generated ? "car-connected" : "receipt"}
                  size={24}
                  color={selectedExpense?.is_system_generated ? "#7367F0" : "#F59E0B"}
                />
                <Text style={styles.modalHeaderTitle} numberOfLines={1}>
                  {selectedExpense?.category?.name ||
                    (selectedExpense?.is_system_generated ? "Auto Travel Allowance" : "Expense Details")}
                </Text>
              </View>
              <TouchableOpacity
                style={styles.modalCloseIconBtn}
                onPress={() => setSelectedExpense(null)}
              >
                <MaterialIcons name="close" size={20} color="#64748B" />
              </TouchableOpacity>
            </View>

            <ScrollView
              style={styles.modalScroll}
              contentContainerStyle={{ padding: 16, paddingBottom: 24 }}
              showsVerticalScrollIndicator={false}
            >
              {selectedExpense && (
                <>
                  {/* Amount & Status Top Banner */}
                  <View style={styles.modalTopBanner}>
                    <View>
                      <Text style={styles.modalBannerAmountLabel}>Claim Amount</Text>
                      <Text style={styles.modalBannerAmountVal}>
                        ₹{Number(selectedExpense.amount).toFixed(2)}
                      </Text>
                    </View>
                    <View style={{ alignItems: "flex-end" }}>
                      <Text
                        style={[
                          styles.statusBadge,
                          selectedExpense.status === "approved"
                            ? styles.statusApproved
                            : selectedExpense.status === "temp_approved"
                            ? styles.statusVerified
                            : selectedExpense.status === "rejected" ||
                              selectedExpense.status === "manager_rejected"
                            ? styles.statusRejected
                            : styles.statusPending,
                        ]}
                      >
                        {selectedExpense.status === "pending"
                          ? "PENDING (L1)"
                          : selectedExpense.status === "temp_approved"
                          ? "VERIFIED (L1)"
                          : selectedExpense.status.toUpperCase()}
                      </Text>
                      <Text style={styles.modalBannerDate}>
                        Date:{" "}
                        {selectedExpense.date
                          ? new Date(selectedExpense.date).toLocaleDateString(undefined, {
                              day: "numeric",
                              month: "short",
                              year: "numeric",
                            })
                          : new Date(selectedExpense.created_at).toLocaleDateString()}
                      </Text>
                    </View>
                  </View>

                  {/* Shift Timing Tag */}
                  {selectedExpense.is_after_day_end && (
                    <View style={styles.afterDayEndBanner}>
                      <MaterialIcons name="warning" size={16} color="#D97706" />
                      <Text style={styles.afterDayEndText}>
                        Submitted after day end or backdated for approval.
                      </Text>
                    </View>
                  )}

                  {/* Bill / Receipt Photo Proof */}
                  <Text style={styles.modalSectionHeading}>🧾 Receipt Voucher / Photo Proof</Text>
                  {selectedExpense.bill_photo_url ? (
                    <TouchableOpacity
                      style={styles.modalPhotoViewerBtn}
                      activeOpacity={0.9}
                      onPress={() =>
                        openPhotoViewer(
                          selectedExpense.bill_photo_url,
                          `${selectedExpense.category?.name || "Expense"} Receipt`
                        )
                      }
                    >
                      <Image
                        source={{ uri: selectedExpense.bill_photo_url }}
                        style={styles.modalPhotoLarge}
                        resizeMode="cover"
                      />
                      <View style={styles.modalPhotoZoomBar}>
                        <MaterialIcons name="zoom-in" size={16} color="#FFFFFF" />
                        <Text style={styles.modalPhotoZoomBarText}>Tap to Zoom Full Screen Photo</Text>
                      </View>
                    </TouchableOpacity>
                  ) : (
                    <View style={styles.noPhotoCard}>
                      <MaterialCommunityIcons name="image-off-outline" size={32} color="#94A3B8" />
                      <Text style={styles.noPhotoCardTitle}>No Receipt Photo Attached</Text>
                      <Text style={styles.noPhotoCardSubtitle}>
                        This expense claim was recorded without a photo proof.
                      </Text>
                      {!selectedExpense.is_system_generated &&
                        (selectedExpense.status === "pending" ||
                          selectedExpense.status === "rejected" ||
                          selectedExpense.status === "manager_rejected") && (
                          <TouchableOpacity
                            style={styles.addPhotoNowBtn}
                            onPress={() => {
                              const exp = selectedExpense;
                              setSelectedExpense(null);
                              navigation.navigate("AddExpense", { expense: exp });
                            }}
                          >
                            <MaterialIcons name="add-a-photo" size={16} color="#FFFFFF" />
                            <Text style={styles.addPhotoNowBtnText}>Upload Receipt Photo</Text>
                          </TouchableOpacity>
                        )}
                    </View>
                  )}

                  {/* Stationery / Itemized Breakdown */}
                  {selectedExpense.items && selectedExpense.items.length > 0 && (
                    <View style={styles.modalItemsSection}>
                      <Text style={styles.modalSectionHeading}>
                        📝 Itemized Breakdown ({selectedExpense.items.length} items)
                      </Text>
                      <View style={styles.itemsTable}>
                        {selectedExpense.items.map((it, idx) => (
                          <View key={idx} style={styles.itemsTableRow}>
                            <View style={{ flex: 1 }}>
                              <Text style={styles.itemsTableRowName}>{it.item_name}</Text>
                              <Text style={styles.itemsTableRowSub}>
                                {it.quantity} × ₹{Number(it.unit_price).toFixed(2)}
                              </Text>
                            </View>
                            <Text style={styles.itemsTableRowTotal}>
                              ₹{Number(it.total_price).toFixed(2)}
                            </Text>
                          </View>
                        ))}
                      </View>
                    </View>
                  )}

                  {/* System Travel Details (if auto-travel claim) */}
                  {selectedExpense.is_system_generated && selectedExpense.day_start && (
                    <View style={styles.systemTravelDetailCard}>
                      <Text style={styles.modalSectionHeading}>🚗 Travel & Route Verification</Text>
                      <View style={styles.systemTravelGrid}>
                        <View style={styles.systemTravelCol}>
                          <Text style={styles.systemTravelLabel}>Vehicle</Text>
                          <Text style={styles.systemTravelVal}>
                            {selectedExpense.day_start.vehicle_type?.name || "Vehicle"}
                            {selectedExpense.day_start.vehicle_no ? ` (${selectedExpense.day_start.vehicle_no})` : ""}
                          </Text>
                        </View>
                        <View style={styles.systemTravelCol}>
                          <Text style={styles.systemTravelLabel}>Distance</Text>
                          <Text style={styles.systemTravelVal}>
                            {selectedExpense.day_start.closing_km !== null && selectedExpense.day_start.opening_km !== null
                              ? `${Math.max(0, Math.round(selectedExpense.day_start.closing_km - selectedExpense.day_start.opening_km))} KM`
                              : "—"}
                          </Text>
                        </View>
                      </View>

                      {/* Meter Photos */}
                      <View style={styles.meterPhotosRow}>
                        {selectedExpense.day_start.opening_km_photo_url && (
                          <TouchableOpacity
                            style={styles.meterThumbBtn}
                            onPress={() =>
                              openPhotoViewer(
                                selectedExpense.day_start?.opening_km_photo_url,
                                "Opening Meter Reading"
                              )
                            }
                          >
                            <Image
                              source={{ uri: selectedExpense.day_start.opening_km_photo_url }}
                              style={styles.meterThumbImg}
                            />
                            <Text style={styles.meterThumbLabel}>
                              Start: {selectedExpense.day_start.opening_km ?? 0} KM
                            </Text>
                          </TouchableOpacity>
                        )}
                        {selectedExpense.day_start.closing_km_photo_url && (
                          <TouchableOpacity
                            style={styles.meterThumbBtn}
                            onPress={() =>
                              openPhotoViewer(
                                selectedExpense.day_start?.closing_km_photo_url,
                                "Closing Meter Reading"
                              )
                            }
                          >
                            <Image
                              source={{ uri: selectedExpense.day_start.closing_km_photo_url }}
                              style={styles.meterThumbImg}
                            />
                            <Text style={styles.meterThumbLabel}>
                              End: {selectedExpense.day_start.closing_km ?? 0} KM
                            </Text>
                          </TouchableOpacity>
                        )}
                      </View>
                    </View>
                  )}

                  {/* Verification & Approval History */}
                  <View style={styles.auditSection}>
                    <Text style={styles.modalSectionHeading}>🛡️ Approval Status & Audit Trail</Text>
                    {selectedExpense.temp_approved_by ? (
                      <View style={styles.auditCard}>
                        <MaterialIcons name="verified" size={16} color="#2563EB" />
                        <View style={{ flex: 1, marginLeft: 8 }}>
                          <Text style={styles.auditCardTitle}>
                            Verified (Level 1) by {selectedExpense.temp_approved_by.name}
                          </Text>
                          {selectedExpense.temp_approved_at && (
                            <Text style={styles.auditCardDate}>
                              {new Date(selectedExpense.temp_approved_at).toLocaleString()}
                            </Text>
                          )}
                          {selectedExpense.temp_approved_note ? (
                            <Text style={styles.auditCardNote}>
                              “{selectedExpense.temp_approved_note}”
                            </Text>
                          ) : null}
                        </View>
                      </View>
                    ) : null}

                    {selectedExpense.approved_by ? (
                      <View style={[styles.auditCard, { backgroundColor: "#F0FDF4", borderColor: "#BBF7D0" }]}>
                        <MaterialIcons name="check-circle" size={16} color="#16A34A" />
                        <View style={{ flex: 1, marginLeft: 8 }}>
                          <Text style={[styles.auditCardTitle, { color: "#166534" }]}>
                            Final Approved (Level 2) by {selectedExpense.approved_by.name}
                          </Text>
                          {selectedExpense.approved_at && (
                            <Text style={styles.auditCardDate}>
                              {new Date(selectedExpense.approved_at).toLocaleString()}
                            </Text>
                          )}
                          {selectedExpense.final_note ? (
                            <Text style={[styles.auditCardNote, { color: "#14532D" }]}>
                              “{selectedExpense.final_note}”
                            </Text>
                          ) : null}
                        </View>
                      </View>
                    ) : null}

                    {selectedExpense.rejection_reason ? (
                      <View style={[styles.auditCard, { backgroundColor: "#FEF2F2", borderColor: "#FECACA" }]}>
                        <MaterialIcons name="error" size={16} color="#DC2626" />
                        <View style={{ flex: 1, marginLeft: 8 }}>
                          <Text style={[styles.auditCardTitle, { color: "#991B1B" }]}>
                            Rejected
                            {selectedExpense.rejected_by?.name ? ` by ${selectedExpense.rejected_by.name}` : ""}
                          </Text>
                          {selectedExpense.rejected_at && (
                            <Text style={styles.auditCardDate}>
                              {new Date(selectedExpense.rejected_at).toLocaleString()}
                            </Text>
                          )}
                          <Text style={[styles.auditCardNote, { color: "#7F1D1D" }]}>
                            Reason: {selectedExpense.rejection_reason}
                          </Text>
                        </View>
                      </View>
                    ) : null}

                    {!selectedExpense.temp_approved_by &&
                      !selectedExpense.approved_by &&
                      !selectedExpense.rejection_reason && (
                        <View style={styles.auditPendingBox}>
                          <MaterialIcons name="hourglass-empty" size={16} color="#D97706" />
                          <Text style={styles.auditPendingText}>
                            Claim submitted and currently awaiting manager verification.
                          </Text>
                        </View>
                      )}
                  </View>
                </>
              )}
            </ScrollView>

            {/* Modal Bottom Actions */}
            <View style={[styles.modalBottomActions, { paddingBottom: Math.max(insets.bottom, 12) + 8 }]}>
              {selectedExpense &&
                !selectedExpense.is_system_generated &&
                (selectedExpense.status === "pending" ||
                  selectedExpense.status === "rejected" ||
                  selectedExpense.status === "manager_rejected") && (
                  <TouchableOpacity
                    style={styles.modalEditBtn}
                    onPress={() => {
                      const exp = selectedExpense;
                      setSelectedExpense(null);
                      navigation.navigate("AddExpense", { expense: exp });
                    }}
                  >
                    <MaterialIcons name="edit" size={16} color="#FFFFFF" />
                    <Text style={styles.modalEditBtnText}>Edit Claim Details</Text>
                  </TouchableOpacity>
                )}
              <TouchableOpacity
                style={styles.modalCloseBtn}
                onPress={() => setSelectedExpense(null)}
              >
                <Text style={styles.modalCloseBtnText}>Close</Text>
              </TouchableOpacity>
            </View>
          </View>
        </View>
      </Modal>

      {/* ── HIGH-RES PHOTO LIGHTBOX MODAL ── */}
      <Modal
        visible={photoViewerVisible}
        transparent
        animationType="fade"
        onRequestClose={() => setPhotoViewerVisible(false)}
      >
        <View style={styles.photoViewerBackdrop}>
          <SafeAreaView style={styles.photoViewerSafe}>
            <View style={styles.photoViewerHeader}>
              <Text style={styles.photoViewerTitle} numberOfLines={1}>
                {selectedPhotoTitle || "Receipt Photo"}
              </Text>
              <TouchableOpacity
                style={styles.photoCloseBtn}
                onPress={() => setPhotoViewerVisible(false)}
              >
                <MaterialIcons name="close" size={24} color="#FFFFFF" />
              </TouchableOpacity>
            </View>
            <View style={styles.photoViewerBody}>
              {selectedPhotoUrl ? (
                <Image
                  source={{ uri: selectedPhotoUrl }}
                  style={styles.photoViewerImage}
                  resizeMode="contain"
                />
              ) : null}
            </View>
          </SafeAreaView>
        </View>
      </Modal>
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

  // ── Card Thumbnails & Quick Preview ──
  cardThumbWrap: {
    width: 54,
    height: 54,
    borderRadius: 8,
    overflow: "hidden",
    position: "relative",
    backgroundColor: "#E2E8F0",
    borderWidth: 1,
    borderColor: "#CBD5E1",
  },
  cardThumbImg: {
    width: "100%",
    height: "100%",
    resizeMode: "cover",
  },
  thumbZoomBadge: {
    position: "absolute",
    bottom: 2,
    right: 2,
    backgroundColor: "rgba(0,0,0,0.65)",
    borderRadius: 10,
    width: 18,
    height: 18,
    alignItems: "center",
    justifyContent: "center",
  },
  noPhotoMiniBox: {
    width: 54,
    height: 54,
    borderRadius: 8,
    backgroundColor: "#F1F5F9",
    borderWidth: 1,
    borderColor: "#E2E8F0",
    alignItems: "center",
    justifyContent: "center",
  },
  noPhotoMiniText: {
    fontSize: 9,
    color: "#94A3B8",
    fontWeight: "600",
    marginTop: 2,
  },
  itemsQuickPreviewWrap: {
    marginTop: 8,
    paddingHorizontal: 10,
    paddingVertical: 6,
    backgroundColor: "#F8FAFC",
    borderRadius: 6,
    borderWidth: 1,
    borderColor: "#E2E8F0",
  },
  itemsQuickPreviewText: {
    fontSize: 11,
    color: "#475569",
    fontWeight: "500",
  },
  viewDetailsBtn: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#F5F3FF",
    paddingHorizontal: 12,
    paddingVertical: 7,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: "#DDD6FE",
    marginRight: 8,
  },
  viewDetailsBtnText: {
    fontSize: 12,
    fontWeight: "700",
    color: "#7367F0",
    marginLeft: 4,
  },

  // ── Details Modal ──
  modalBackdrop: {
    flex: 1,
    backgroundColor: "rgba(15, 23, 42, 0.7)",
    justifyContent: "flex-end",
  },
  modalCard: {
    backgroundColor: "#FFFFFF",
    borderTopLeftRadius: 24,
    borderTopRightRadius: 24,
    maxHeight: "88%",
    shadowColor: "#000",
    shadowOffset: { width: 0, height: -4 },
    shadowOpacity: 0.15,
    shadowRadius: 12,
    elevation: 20,
  },
  modalHeader: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    paddingHorizontal: 18,
    paddingVertical: 16,
    borderBottomWidth: 1,
    borderBottomColor: "#F1F5F9",
  },
  modalHeaderTitle: {
    fontSize: 17,
    fontWeight: "800",
    color: "#0F172A",
    marginLeft: 8,
    flex: 1,
  },
  modalCloseIconBtn: {
    padding: 6,
    borderRadius: 16,
    backgroundColor: "#F1F5F9",
  },
  modalScroll: {
    flexGrow: 0,
  },
  modalTopBanner: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    backgroundColor: "#F8FAFC",
    padding: 14,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: "#E2E8F0",
    marginBottom: 12,
  },
  modalBannerAmountLabel: {
    fontSize: 11,
    fontWeight: "700",
    color: "#64748B",
    textTransform: "uppercase",
    letterSpacing: 0.5,
  },
  modalBannerAmountVal: {
    fontSize: 22,
    fontWeight: "800",
    color: "#0F172A",
    marginTop: 2,
  },
  modalBannerDate: {
    fontSize: 11,
    color: "#64748B",
    marginTop: 4,
    fontWeight: "500",
  },
  afterDayEndBanner: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#FFFBEB",
    borderWidth: 1,
    borderColor: "#FDE68A",
    borderRadius: 8,
    padding: 10,
    marginBottom: 14,
    gap: 6,
  },
  afterDayEndText: {
    fontSize: 12,
    color: "#92400E",
    fontWeight: "600",
    flex: 1,
  },
  modalSectionHeading: {
    fontSize: 13,
    fontWeight: "800",
    color: "#334155",
    textTransform: "uppercase",
    letterSpacing: 0.5,
    marginTop: 10,
    marginBottom: 8,
  },

  // ── Receipt Photo ──
  modalPhotoViewerBtn: {
    borderRadius: 12,
    overflow: "hidden",
    borderWidth: 1,
    borderColor: "#CBD5E1",
    position: "relative",
    marginBottom: 14,
    backgroundColor: "#0F172A",
  },
  modalPhotoLarge: {
    width: "100%",
    height: 200,
  },
  modalPhotoZoomBar: {
    position: "absolute",
    bottom: 0,
    left: 0,
    right: 0,
    backgroundColor: "rgba(15, 23, 42, 0.75)",
    paddingVertical: 6,
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    gap: 6,
  },
  modalPhotoZoomBarText: {
    color: "#FFFFFF",
    fontSize: 12,
    fontWeight: "700",
  },
  noPhotoCard: {
    padding: 20,
    backgroundColor: "#F8FAFC",
    borderRadius: 12,
    borderWidth: 1,
    borderColor: "#E2E8F0",
    alignItems: "center",
    justifyContent: "center",
    marginBottom: 14,
  },
  noPhotoCardTitle: {
    fontSize: 14,
    fontWeight: "700",
    color: "#475569",
    marginTop: 8,
  },
  noPhotoCardSubtitle: {
    fontSize: 12,
    color: "#94A3B8",
    textAlign: "center",
    marginTop: 4,
  },
  addPhotoNowBtn: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#7367F0",
    paddingHorizontal: 14,
    paddingVertical: 8,
    borderRadius: 8,
    marginTop: 12,
    gap: 6,
  },
  addPhotoNowBtnText: {
    color: "#FFFFFF",
    fontSize: 12,
    fontWeight: "700",
  },

  // ── Items Table ──
  modalItemsSection: {
    marginBottom: 14,
  },
  itemsTable: {
    backgroundColor: "#F8FAFC",
    borderRadius: 10,
    borderWidth: 1,
    borderColor: "#E2E8F0",
    overflow: "hidden",
  },
  itemsTableRow: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    paddingHorizontal: 12,
    paddingVertical: 9,
    borderBottomWidth: 1,
    borderBottomColor: "#EDF2F7",
  },
  itemsTableRowName: {
    fontSize: 13,
    fontWeight: "700",
    color: "#1E293B",
  },
  itemsTableRowSub: {
    fontSize: 11,
    color: "#64748B",
    marginTop: 1,
  },
  itemsTableRowTotal: {
    fontSize: 13,
    fontWeight: "800",
    color: "#0F172A",
  },

  // ── System Travel ──
  systemTravelDetailCard: {
    marginBottom: 14,
  },
  systemTravelGrid: {
    flexDirection: "row",
    backgroundColor: "#F8FAFC",
    borderRadius: 10,
    borderWidth: 1,
    borderColor: "#E2E8F0",
    padding: 12,
    marginBottom: 8,
    justifyContent: "space-between",
  },
  systemTravelCol: {
    flex: 1,
  },
  systemTravelLabel: {
    fontSize: 11,
    fontWeight: "700",
    color: "#64748B",
    textTransform: "uppercase",
  },
  systemTravelVal: {
    fontSize: 14,
    fontWeight: "800",
    color: "#0F172A",
    marginTop: 2,
  },
  meterPhotosRow: {
    flexDirection: "row",
    gap: 10,
  },
  meterThumbBtn: {
    flex: 1,
    borderRadius: 8,
    overflow: "hidden",
    borderWidth: 1,
    borderColor: "#CBD5E1",
    backgroundColor: "#F1F5F9",
  },
  meterThumbImg: {
    width: "100%",
    height: 90,
    resizeMode: "cover",
  },
  meterThumbLabel: {
    fontSize: 11,
    fontWeight: "700",
    color: "#334155",
    paddingVertical: 4,
    textAlign: "center",
    backgroundColor: "#F8FAFC",
  },

  // ── Audit Section ──
  auditSection: {
    marginBottom: 10,
  },
  auditCard: {
    flexDirection: "row",
    alignItems: "flex-start",
    backgroundColor: "#EFF6FF",
    borderWidth: 1,
    borderColor: "#BFDBFE",
    borderRadius: 10,
    padding: 10,
    marginBottom: 8,
  },
  auditCardTitle: {
    fontSize: 12,
    fontWeight: "700",
    color: "#1E40AF",
  },
  auditCardDate: {
    fontSize: 11,
    color: "#64748B",
    marginTop: 1,
  },
  auditCardNote: {
    fontSize: 11,
    color: "#1E3A8A",
    fontStyle: "italic",
    marginTop: 3,
  },
  auditPendingBox: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#FFFBEB",
    borderWidth: 1,
    borderColor: "#FDE68A",
    borderRadius: 10,
    padding: 10,
    gap: 8,
  },
  auditPendingText: {
    fontSize: 12,
    color: "#92400E",
    fontWeight: "500",
    flex: 1,
  },

  // ── Modal Actions ──
  modalBottomActions: {
    flexDirection: "row",
    paddingHorizontal: 16,
    paddingTop: 12,
    borderTopWidth: 1,
    borderTopColor: "#F1F5F9",
    gap: 10,
  },
  modalEditBtn: {
    flex: 1,
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    backgroundColor: "#7367F0",
    borderRadius: 10,
    paddingVertical: 12,
    gap: 6,
  },
  modalEditBtnText: {
    color: "#FFFFFF",
    fontSize: 14,
    fontWeight: "700",
  },
  modalCloseBtn: {
    flex: 1,
    alignItems: "center",
    justifyContent: "center",
    backgroundColor: "#F1F5F9",
    borderRadius: 10,
    paddingVertical: 12,
  },
  modalCloseBtnText: {
    color: "#475569",
    fontSize: 14,
    fontWeight: "700",
  },

  // ── High-Res Photo Viewer Lightbox ──
  photoViewerBackdrop: {
    flex: 1,
    backgroundColor: "rgba(0,0,0,0.95)",
  },
  photoViewerSafe: {
    flex: 1,
  },
  photoViewerHeader: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    paddingHorizontal: 16,
    paddingVertical: 12,
  },
  photoViewerTitle: {
    color: "#FFFFFF",
    fontSize: 16,
    fontWeight: "700",
    flex: 1,
    marginRight: 10,
  },
  photoCloseBtn: {
    padding: 6,
    borderRadius: 20,
    backgroundColor: "rgba(255,255,255,0.2)",
  },
  photoViewerBody: {
    flex: 1,
    alignItems: "center",
    justifyContent: "center",
    padding: 8,
  },
  photoViewerImage: {
    width: "100%",
    height: "100%",
  },
});
