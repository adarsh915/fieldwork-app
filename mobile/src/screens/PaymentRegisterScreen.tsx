import { MaterialCommunityIcons, MaterialIcons } from "@expo/vector-icons";
import { useNavigation, useFocusEffect } from "@react-navigation/native";
import type { NativeStackNavigationProp } from "@react-navigation/native-stack";
import { useCallback, useMemo, useState } from "react";
import {
  ActivityIndicator,
  FlatList,
  Platform,
  RefreshControl,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from "react-native";
import ScreenHeader from "../components/ScreenHeader";
import { getMyCollections, type PaymentRecord } from "../api/payment";
import type { AppStackParamList } from "../navigation/RootNavigator";

type Nav = NativeStackNavigationProp<AppStackParamList, "PaymentRegister">;

export default function PaymentRegisterScreen() {
  const navigation = useNavigation<Nav>();

  const [payments, setPayments] = useState<PaymentRecord[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [searchQuery, setSearchQuery] = useState("");
  const [statusFilter, setStatusFilter] = useState<string>("all");

  const fetchPayments = useCallback(async (isRefresh = false) => {
    if (!isRefresh) setLoading(true);
    try {
      const res = await getMyCollections(statusFilter !== "all" ? statusFilter : undefined);
      if (res && res.data) {
        setPayments(res.data);
      }
    } catch (err) {
      console.warn("Failed to fetch payments", err);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [statusFilter]);

  useFocusEffect(
    useCallback(() => {
      fetchPayments();
    }, [fetchPayments])
  );

  const onRefresh = () => {
    setRefreshing(true);
    fetchPayments(true);
  };

  const filteredPayments = useMemo(() => {
    return payments.filter((item) => {
      const q = searchQuery.trim().toLowerCase();
      const matchesSearch =
        !q ||
        item.receipt_no.toLowerCase().includes(q) ||
        (item.reference_number && item.reference_number.toLowerCase().includes(q)) ||
        (item.party?.name && item.party.name.toLowerCase().includes(q));

      const matchesStatus =
        statusFilter === "all" || item.status.toLowerCase() === statusFilter.toLowerCase();

      return matchesSearch && matchesStatus;
    });
  }, [payments, searchQuery, statusFilter]);

  const totalAmount = useMemo(() => {
    return filteredPayments.reduce((sum, item) => sum + (Number(item.amount) || 0), 0);
  }, [filteredPayments]);

  const confirmedAmount = useMemo(() => {
    return filteredPayments
      .filter((p) => p.status === "confirmed")
      .reduce((sum, item) => sum + (Number(item.amount) || 0), 0);
  }, [filteredPayments]);

  const renderPaymentItem = ({ item }: { item: PaymentRecord }) => {
    const isConfirmed = item.status === "confirmed";
    const isRejected = item.status === "rejected";

    return (
      <View style={styles.card}>
        <View style={[styles.cardAccent, isConfirmed && { backgroundColor: "#10B981" }, isRejected && { backgroundColor: "#EF4444" }]} />
        <View style={styles.cardContent}>
          <View style={styles.cardHeader}>
            <View style={{ flex: 1, marginRight: 8 }}>
              <Text style={styles.receiptNo}>{item.receipt_no}</Text>
              {item.party?.name && (
                <View style={styles.partyRow}>
                  <MaterialCommunityIcons name="store" size={14} color="#6B7280" />
                  <Text style={styles.partyNameText} numberOfLines={1}>
                    {item.party.name}
                  </Text>
                </View>
              )}
            </View>

            <View
              style={[
                styles.statusBadge,
                isConfirmed && styles.statusConfirmed,
                isRejected && styles.statusRejected,
              ]}
            >
              <Text
                style={[
                  styles.statusBadgeText,
                  isConfirmed && styles.statusConfirmedText,
                  isRejected && styles.statusRejectedText,
                ]}
              >
                {isConfirmed ? "CONFIRMED" : isRejected ? "REJECTED" : "PENDING"}
              </Text>
            </View>
          </View>

          <View style={styles.divider} />

          <View style={styles.cardFooter}>
            <View>
              <Text style={styles.metaLabel}>Date</Text>
              <Text style={styles.metaValue}>
                {new Date(item.collection_datetime || item.created_at).toLocaleDateString()}
              </Text>
            </View>

            <View>
              <Text style={styles.metaLabel}>Mode</Text>
              <Text style={[styles.metaValue, { textTransform: "uppercase" }]}>
                {item.payment_mode}
              </Text>
            </View>

            <View style={{ alignItems: "flex-end" }}>
              <Text style={styles.metaLabel}>Amount</Text>
              <Text style={styles.totalAmount}>
                ₹ {Number(item.amount).toFixed(2)}
              </Text>
            </View>
          </View>

          {item.reference_number ? (
            <View style={styles.refRow}>
              <Text style={styles.refLabel}>Ref / UTR: </Text>
              <Text style={styles.refValue}>{item.reference_number}</Text>
            </View>
          ) : null}

          {isRejected && item.rejection_reason && (
            <View style={styles.rejectionBox}>
              <Text style={styles.rejectionLabel}>Rejection Reason:</Text>
              <Text style={styles.rejectionText}>{item.rejection_reason}</Text>
            </View>
          )}
        </View>
      </View>
    );
  };

  return (
    <View style={styles.container}>
      <ScreenHeader title="Payment Register" onBack={() => navigation.goBack()} />

      <View style={styles.topSection}>
        {/* Search */}
        <View style={styles.searchWrap}>
          <MaterialIcons name="search" size={20} color="#7367F0" />
          <TextInput
            style={styles.searchInput}
            placeholder="Search by receipt no, dealer, UTR..."
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
          {["all", "pending", "confirmed", "rejected"].map((st) => (
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
                {st === "all" ? "All" : st.charAt(0).toUpperCase() + st.slice(1)}
              </Text>
            </TouchableOpacity>
          ))}
        </View>

        {/* Stats banner */}
        <View style={styles.statsCard}>
          <View style={styles.statsCol}>
            <Text style={styles.statsNumber}>{filteredPayments.length}</Text>
            <Text style={styles.statsLabel}>Total Entries</Text>
          </View>
          <View style={styles.statsDivider} />
          <View style={styles.statsCol}>
            <Text style={[styles.statsNumber, { color: "#F59E0B" }]}>
              ₹ {totalAmount.toFixed(2)}
            </Text>
            <Text style={styles.statsLabel}>Total Collected</Text>
          </View>
          <View style={styles.statsDivider} />
          <View style={styles.statsCol}>
            <Text style={[styles.statsNumber, { color: "#10B981" }]}>
              ₹ {confirmedAmount.toFixed(2)}
            </Text>
            <Text style={styles.statsLabel}>Confirmed</Text>
          </View>
        </View>
      </View>

      {loading ? (
        <View style={styles.loadingContainer}>
          <ActivityIndicator size="large" color="#7367F0" />
          <Text style={styles.loadingText}>Loading payment records...</Text>
        </View>
      ) : (
        <FlatList
          data={filteredPayments}
          keyExtractor={(item) => item.id.toString()}
          renderItem={renderPaymentItem}
          contentContainerStyle={styles.listContent}
          refreshControl={
            <RefreshControl refreshing={refreshing} onRefresh={onRefresh} colors={["#7367F0"]} />
          }
          ListEmptyComponent={
            <View style={styles.emptyContainer}>
              <MaterialCommunityIcons
                name="cash-multiple"
                size={56}
                color="#D1D5DB"
              />
              <Text style={styles.emptyTitle}>No Payment Records</Text>
              <Text style={styles.emptySubtitle}>
                You have not submitted any payment collections yet.
              </Text>
            </View>
          }
        />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#F3F4F6" },
  topSection: { paddingHorizontal: 14, paddingTop: 12 },

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
  searchInput: { flex: 1, fontSize: 14, color: "#1F2937", fontWeight: "600" },

  filterRow: { flexDirection: "row", gap: 8, marginBottom: 10 },
  filterChip: {
    paddingHorizontal: 14,
    paddingVertical: 6,
    borderRadius: 20,
    backgroundColor: "#FFFFFF",
    borderWidth: 1,
    borderColor: "#E5E7EB",
  },
  filterChipActive: { backgroundColor: "#7367F0", borderColor: "#7367F0" },
  filterChipText: { fontSize: 12, fontWeight: "700", color: "#6B7280" },
  filterChipTextActive: { color: "#FFFFFF" },

  statsCard: {
    backgroundColor: "#FFFFFF",
    borderRadius: 12,
    paddingVertical: 10,
    paddingHorizontal: 14,
    flexDirection: "row",
    alignItems: "center",
    marginBottom: 10,
    elevation: 2,
    shadowColor: "#000",
    shadowOpacity: 0.04,
    shadowRadius: 6,
  },
  statsCol: { flex: 1, alignItems: "center" },
  statsNumber: { fontSize: 15, fontWeight: "800", color: "#7367F0" },
  statsLabel: { fontSize: 10, fontWeight: "600", color: "#6B7280", marginTop: 2 },
  statsDivider: { width: 1, height: 28, backgroundColor: "#E5E7EB" },

  listContent: { paddingHorizontal: 14, paddingBottom: 30 },

  card: {
    backgroundColor: "#FFFFFF",
    borderRadius: 14,
    marginBottom: 12,
    overflow: "hidden",
    flexDirection: "row",
    elevation: 3,
    shadowColor: "#000",
    shadowOpacity: 0.05,
    shadowRadius: 8,
  },
  cardAccent: { width: 5, backgroundColor: "#F59E0B" },
  cardContent: { flex: 1, padding: 14 },
  cardHeader: { flexDirection: "row", justifyContent: "space-between", alignItems: "flex-start" },
  receiptNo: {
    fontSize: 14,
    fontWeight: "800",
    color: "#1F2937",
    fontFamily: Platform.OS === "ios" ? "Courier" : "monospace",
  },
  partyRow: { flexDirection: "row", alignItems: "center", gap: 4, marginTop: 3 },
  partyNameText: { fontSize: 12, fontWeight: "600", color: "#4B5563" },

  statusBadge: {
    backgroundColor: "#FEF3C7",
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 4,
  },
  statusConfirmed: { backgroundColor: "#D1FAE5" },
  statusRejected: { backgroundColor: "#FEE2E2" },
  statusBadgeText: { fontSize: 9, fontWeight: "800", color: "#D97706" },
  statusConfirmedText: { color: "#059669" },
  statusRejectedText: { color: "#DC2626" },

  divider: { height: 1, backgroundColor: "#F3F4F6", marginVertical: 10 },

  cardFooter: { flexDirection: "row", justifyContent: "space-between", alignItems: "center" },
  metaLabel: { fontSize: 10, fontWeight: "600", color: "#9CA3AF", marginBottom: 2 },
  metaValue: { fontSize: 12, fontWeight: "700", color: "#374151" },
  totalAmount: { fontSize: 15, fontWeight: "800", color: "#10B981" },

  refRow: { flexDirection: "row", alignItems: "center", marginTop: 8, paddingTop: 6, borderTopWidth: 1, borderTopColor: "#F9FAFB" },
  refLabel: { fontSize: 11, color: "#6B7280", fontWeight: "600" },
  refValue: { fontSize: 11, color: "#1F2937", fontWeight: "700" },

  rejectionBox: {
    backgroundColor: "#FEF2F2",
    padding: 8,
    borderRadius: 6,
    marginTop: 8,
  },
  rejectionLabel: { fontSize: 10, fontWeight: "700", color: "#DC2626" },
  rejectionText: { fontSize: 11, color: "#B91C1C", marginTop: 2 },

  loadingContainer: { flex: 1, alignItems: "center", justifyContent: "center", paddingVertical: 40 },
  loadingText: { marginTop: 10, fontSize: 13, fontWeight: "600", color: "#6B7280" },
  emptyContainer: { alignItems: "center", justifyContent: "center", paddingVertical: 60 },
  emptyTitle: { fontSize: 16, fontWeight: "800", color: "#374151", marginTop: 12 },
  emptySubtitle: { fontSize: 12, color: "#9CA3AF", marginTop: 4, textAlign: "center" },
});
