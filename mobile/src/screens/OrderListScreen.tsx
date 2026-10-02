import { MaterialCommunityIcons, MaterialIcons } from "@expo/vector-icons";
import { useNavigation, useRoute, useFocusEffect } from "@react-navigation/native";
import type { RouteProp } from "@react-navigation/native";
import type { NativeStackNavigationProp } from "@react-navigation/native-stack";
import { useCallback, useMemo, useState } from "react";
import {
  ActivityIndicator,
  FlatList,
  RefreshControl,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from "react-native";
import ScreenHeader from "../components/ScreenHeader";
import { getOrders } from "../api/order";
import type { AppStackParamList } from "../navigation/RootNavigator";

type Nav = NativeStackNavigationProp<AppStackParamList, "OrderList">;
type RouteType = RouteProp<AppStackParamList, "OrderList">;

export default function OrderListScreen() {
  const navigation = useNavigation<Nav>();
  const route = useRoute<RouteType>();
  const partyId = route.params?.partyId;
  const partyName = route.params?.partyName;

  const [orders, setOrders] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [searchQuery, setSearchQuery] = useState("");
  const [statusFilter, setStatusFilter] = useState<string>("all");

  const fetchOrders = useCallback(async (isRefresh = false) => {
    if (!isRefresh) setLoading(true);
    try {
      const res = await getOrders(partyId);
      if (res && res.data) {
        setOrders(res.data);
      }
    } catch (err) {
      console.warn("Failed to fetch orders", err);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [partyId]);

  useFocusEffect(
    useCallback(() => {
      fetchOrders();
    }, [fetchOrders])
  );

  const onRefresh = () => {
    setRefreshing(true);
    fetchOrders(true);
  };

  const filteredOrders = useMemo(() => {
    return orders.filter((order) => {
      const matchesSearch =
        !searchQuery.trim() ||
        (order.order_no && order.order_no.toLowerCase().includes(searchQuery.toLowerCase())) ||
        (order.party?.name && order.party.name.toLowerCase().includes(searchQuery.toLowerCase())) ||
        (partyName && partyName.toLowerCase().includes(searchQuery.toLowerCase()));

      const matchesStatus =
        statusFilter === "all" ||
        (order.status || "pending").toLowerCase() === statusFilter.toLowerCase();

      return matchesSearch && matchesStatus;
    });
  }, [orders, searchQuery, statusFilter, partyName]);

  const totalAmount = useMemo(() => {
    return filteredOrders.reduce((sum, order) => sum + (Number(order.total_amount) || 0), 0);
  }, [filteredOrders]);

  const renderOrderItem = ({ item }: { item: any }) => {
    const status = (item.status || "pending").toLowerCase();
    const isCompleted = status === "completed" || status === "approved";
    const isCancelled = status === "cancelled" || status === "rejected";

    return (
      <TouchableOpacity
        style={styles.orderCard}
        activeOpacity={0.8}
        onPress={() => navigation.navigate("OrderDetails", { order: item })}
      >
        <View style={styles.cardAccent} />
        <View style={styles.cardContent}>
          <View style={styles.cardHeader}>
            <View style={{ flex: 1, marginRight: 8 }}>
              <Text style={styles.orderNo} numberOfLines={1}>
                {item.order_no}
              </Text>
              <View style={{ flexDirection: "row", alignItems: "center", gap: 6, marginTop: 3 }}>
                {item.order_source === "phone" ? (
                  <View style={styles.phoneBadge}>
                    <MaterialCommunityIcons name="phone" size={10} color="#2563EB" />
                    <Text style={styles.phoneBadgeText}>
                      {item.is_after_day_end ? "Phone (Off-Shift)" : "Phone Order"}
                    </Text>
                  </View>
                ) : (
                  <View style={styles.visitBadge}>
                    <MaterialCommunityIcons name="map-marker" size={10} color="#059669" />
                    <Text style={styles.visitBadgeText}>Visit Order</Text>
                  </View>
                )}
              </View>
              {(item.party?.name || partyName) && (
                <View style={styles.partyRow}>
                  <MaterialCommunityIcons name="store" size={14} color="#6B7280" />
                  <Text style={styles.partyNameText} numberOfLines={1}>
                    {item.party?.name || partyName}
                  </Text>
                </View>
              )}
            </View>

            <View
              style={[
                styles.statusBadge,
                isCompleted && styles.statusCompleted,
                isCancelled && styles.statusCancelled,
              ]}
            >
              <Text
                style={[
                  styles.statusText,
                  isCompleted && styles.statusCompletedText,
                  isCancelled && styles.statusCancelledText,
                ]}
              >
                {(item.status || "pending").toUpperCase()}
              </Text>
            </View>
          </View>

          <View style={styles.divider} />

          <View style={styles.cardFooter}>
            <View>
              <Text style={styles.metaLabel}>Date</Text>
              <Text style={styles.metaValue}>
                {new Date(item.created_at || item.order_date).toLocaleDateString()}
              </Text>
            </View>

            <View>
              <Text style={styles.metaLabel}>Items</Text>
              <Text style={styles.metaValue}>
                {item.items?.length || 0} items
              </Text>
            </View>

            <View style={{ alignItems: "flex-end" }}>
              <Text style={styles.metaLabel}>Total Amount</Text>
              <Text style={styles.totalAmount}>
                ₹ {Number(item.total_amount).toFixed(2)}
              </Text>
            </View>

            <MaterialIcons name="chevron-right" size={20} color="#9CA3AF" />
          </View>

          {(item.order_type || item.payment_mode) && (
            <View style={styles.tagRow}>
              {item.order_type ? (
                <View style={styles.tag}>
                  <Text style={styles.tagText}>{item.order_type}</Text>
                </View>
              ) : null}
              {item.payment_mode ? (
                <View style={[styles.tag, styles.paymentTag]}>
                  <Text style={[styles.tagText, styles.paymentTagText]}>
                    {item.payment_mode}
                  </Text>
                </View>
              ) : null}
            </View>
          )}
        </View>
      </TouchableOpacity>
    );
  };

  return (
    <View style={styles.container}>
      <ScreenHeader
        title={partyName ? `Orders - ${partyName}` : "Order Register"}
        onBack={() => navigation.goBack()}
      />

      <View style={styles.topSection}>
        {/* Search */}
        <View style={styles.searchWrap}>
          <MaterialIcons name="search" size={20} color="#7367F0" />
          <TextInput
            style={styles.searchInput}
            placeholder="Search by order no or party..."
            placeholderTextColor="#9CA3AF"
            value={searchQuery}
            onChangeText={setSearchQuery}
          />
          {searchQuery.length > 0 && (
            <TouchableOpacity onPress={() => setSearchQuery("")}>
              <MaterialIcons name="close" size={18} color="#9CA3AF" />
            </TouchableOpacity>
          )}
        </View>

        {/* Status Filters */}
        <View style={styles.filterRow}>
          {["all", "pending", "completed", "cancelled"].map((st) => (
            <TouchableOpacity
              key={st}
              style={[
                styles.filterChip,
                statusFilter === st && styles.filterChipActive,
              ]}
              onPress={() => setStatusFilter(st)}
            >
              <Text
                style={[
                  styles.filterChipText,
                  statusFilter === st && styles.filterChipTextActive,
                ]}
              >
                {st.charAt(0).toUpperCase() + st.slice(1)}
              </Text>
            </TouchableOpacity>
          ))}
        </View>

        {/* Stats banner */}
        <View style={styles.statsCard}>
          <View style={styles.statsCol}>
            <Text style={styles.statsNumber}>{filteredOrders.length}</Text>
            <Text style={styles.statsLabel}>Total Orders</Text>
          </View>
          <View style={styles.statsDivider} />
          <View style={styles.statsCol}>
            <Text style={[styles.statsNumber, { color: "#10B981" }]}>
              ₹ {totalAmount.toFixed(2)}
            </Text>
            <Text style={styles.statsLabel}>Total Value</Text>
          </View>
        </View>
      </View>

      {loading ? (
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color="#7367F0" />
          <Text style={styles.loadingText}>Loading orders...</Text>
        </View>
      ) : (
        <FlatList
          data={filteredOrders}
          keyExtractor={(item, index) => item.id?.toString() || index.toString()}
          renderItem={renderOrderItem}
          contentContainerStyle={styles.listContent}
          refreshControl={
            <RefreshControl
              refreshing={refreshing}
              onRefresh={onRefresh}
              colors={["#7367F0"]}
            />
          }
          ListEmptyComponent={
            <View style={styles.emptyContainer}>
              <MaterialCommunityIcons
                name="clipboard-text-outline"
                size={56}
                color="#D1D5DB"
              />
              <Text style={styles.emptyTitle}>No Orders Found</Text>
              <Text style={styles.emptySubtitle}>
                {partyName
                  ? `No past orders recorded for ${partyName}.`
                  : "No past orders available."}
              </Text>
            </View>
          }
        />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: "#F3F4F6",
  },
  topSection: {
    paddingHorizontal: 14,
    paddingTop: 12,
  },
  searchWrap: {
    backgroundColor: "#FFFFFF",
    borderRadius: 12,
    borderWidth: 1,
    borderColor: "#E5E7EB",
    paddingHorizontal: 12,
    height: 46,
    flexDirection: "row",
    alignItems: "center",
    marginBottom: 10,
    gap: 8,
  },
  searchInput: {
    flex: 1,
    fontSize: 14,
    color: "#1F2937",
    fontWeight: "600",
  },
  filterRow: {
    flexDirection: "row",
    gap: 8,
    marginBottom: 10,
  },
  filterChip: {
    paddingHorizontal: 14,
    paddingVertical: 6,
    borderRadius: 20,
    backgroundColor: "#FFFFFF",
    borderWidth: 1,
    borderColor: "#E5E7EB",
  },
  filterChipActive: {
    backgroundColor: "#7367F0",
    borderColor: "#7367F0",
  },
  filterChipText: {
    fontSize: 12,
    fontWeight: "700",
    color: "#6B7280",
  },
  filterChipTextActive: {
    color: "#FFFFFF",
  },
  statsCard: {
    backgroundColor: "#FFFFFF",
    borderRadius: 12,
    paddingVertical: 10,
    paddingHorizontal: 16,
    flexDirection: "row",
    alignItems: "center",
    marginBottom: 10,
    shadowColor: "#000",
    shadowOpacity: 0.04,
    shadowRadius: 6,
    elevation: 2,
  },
  statsCol: {
    flex: 1,
    alignItems: "center",
  },
  statsNumber: {
    fontSize: 16,
    fontWeight: "800",
    color: "#7367F0",
  },
  statsLabel: {
    fontSize: 11,
    fontWeight: "600",
    color: "#6B7280",
    marginTop: 2,
  },
  statsDivider: {
    width: 1,
    height: 30,
    backgroundColor: "#E5E7EB",
  },
  listContent: {
    paddingHorizontal: 14,
    paddingBottom: 30,
  },
  orderCard: {
    backgroundColor: "#FFFFFF",
    borderRadius: 14,
    marginBottom: 12,
    overflow: "hidden",
    flexDirection: "row",
    shadowColor: "#000",
    shadowOpacity: 0.05,
    shadowRadius: 8,
    elevation: 3,
  },
  cardAccent: {
    width: 5,
    backgroundColor: "#7367F0",
  },
  cardContent: {
    flex: 1,
    padding: 14,
  },
  cardHeader: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "flex-start",
  },
  orderNo: {
    fontSize: 15,
    fontWeight: "800",
    color: "#1F2937",
  },
  partyRow: {
    flexDirection: "row",
    alignItems: "center",
    gap: 4,
    marginTop: 3,
  },
  partyNameText: {
    fontSize: 12,
    fontWeight: "600",
    color: "#4B5563",
  },
  statusBadge: {
    backgroundColor: "#FEF3C7",
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 6,
  },
  statusCompleted: {
    backgroundColor: "#D1FAE5",
  },
  statusCancelled: {
    backgroundColor: "#FEE2E2",
  },
  statusText: {
    fontSize: 10,
    fontWeight: "800",
    color: "#D97706",
  },
  statusCompletedText: {
    color: "#059669",
  },
  statusCancelledText: {
    color: "#DC2626",
  },
  phoneBadge: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#EFF6FF",
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 4,
    borderWidth: 1,
    borderColor: "#BFDBFE",
    gap: 3,
  },
  phoneBadgeText: {
    fontSize: 9,
    fontWeight: "700",
    color: "#1E40AF",
  },
  visitBadge: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#ECFDF5",
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 4,
    borderWidth: 1,
    borderColor: "#A7F3D0",
    gap: 3,
  },
  visitBadgeText: {
    fontSize: 9,
    fontWeight: "700",
    color: "#065F46",
  },
  divider: {
    height: 1,
    backgroundColor: "#F3F4F6",
    marginVertical: 10,
  },
  cardFooter: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
  },
  metaLabel: {
    fontSize: 10,
    fontWeight: "600",
    color: "#9CA3AF",
    marginBottom: 2,
  },
  metaValue: {
    fontSize: 12,
    fontWeight: "700",
    color: "#374151",
  },
  totalAmount: {
    fontSize: 14,
    fontWeight: "800",
    color: "#10B981",
  },
  tagRow: {
    flexDirection: "row",
    gap: 6,
    marginTop: 8,
  },
  tag: {
    backgroundColor: "#EEF2FF",
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 4,
  },
  tagText: {
    fontSize: 10,
    fontWeight: "700",
    color: "#4F46E5",
  },
  paymentTag: {
    backgroundColor: "#ECFDF5",
  },
  paymentTagText: {
    color: "#059669",
  },
  loadingContainer: {
    flex: 1,
    alignItems: "center",
    justifyContent: "center",
    paddingVertical: 40,
  },
  loadingText: {
    marginTop: 10,
    fontSize: 13,
    fontWeight: "600",
    color: "#6B7280",
  },
  emptyContainer: {
    alignItems: "center",
    justifyContent: "center",
    paddingVertical: 60,
  },
  emptyTitle: {
    fontSize: 16,
    fontWeight: "800",
    color: "#374151",
    marginTop: 12,
  },
  emptySubtitle: {
    fontSize: 12,
    color: "#9CA3AF",
    marginTop: 4,
    textAlign: "center",
    paddingHorizontal: 20,
  },
});
