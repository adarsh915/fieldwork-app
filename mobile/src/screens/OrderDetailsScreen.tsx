import { MaterialCommunityIcons, MaterialIcons } from "@expo/vector-icons";
import { useNavigation, useRoute } from "@react-navigation/native";
import type { RouteProp } from "@react-navigation/native";
import type { NativeStackNavigationProp } from "@react-navigation/native-stack";
import {
  ScrollView,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from "react-native";
import ScreenHeader from "../components/ScreenHeader";
import type { AppStackParamList } from "../navigation/RootNavigator";

type Nav = NativeStackNavigationProp<AppStackParamList, "OrderDetails">;
type RouteType = RouteProp<AppStackParamList, "OrderDetails">;

export default function OrderDetailsScreen() {
  const navigation = useNavigation<Nav>();
  const route = useRoute<RouteType>();
  const { order } = route.params;

  const status = (order?.status || "pending").toLowerCase();
  const isCompleted = status === "completed" || status === "approved";
  const isCancelled = status === "cancelled" || status === "rejected";

  return (
    <View style={styles.container}>
      <ScreenHeader title="Order Details" onBack={() => navigation.goBack()} />

      <ScrollView contentContainerStyle={styles.scrollContent} showsVerticalScrollIndicator={false}>
        {/* Status & Order Summary Card */}
        <View style={styles.card}>
          <View style={styles.orderTopRow}>
            <View style={{ flex: 1 }}>
              <Text style={styles.orderLabel}>ORDER NUMBER</Text>
              <Text style={styles.orderNo}>{order.order_no}</Text>
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
                {(order.status || "pending").toUpperCase()}
              </Text>
            </View>
          </View>

          <View style={styles.divider} />

          <View style={styles.metaGrid}>
            <View style={styles.metaItem}>
              <MaterialCommunityIcons name="calendar-range" size={16} color="#6B7280" />
              <View>
                <Text style={styles.metaItemLabel}>Order Date</Text>
                <Text style={styles.metaItemValue}>
                  {new Date(order.created_at || order.order_date).toLocaleDateString()}
                </Text>
              </View>
            </View>

            <View style={styles.metaItem}>
              <MaterialCommunityIcons name="cash-multiple" size={16} color="#6B7280" />
              <View>
                <Text style={styles.metaItemLabel}>Payment Mode</Text>
                <Text style={styles.metaItemValue}>{order.payment_mode || "Credit"}</Text>
              </View>
            </View>

            <View style={styles.metaItem}>
              <MaterialCommunityIcons name="tag-outline" size={16} color="#6B7280" />
              <View>
                <Text style={styles.metaItemLabel}>Order Type</Text>
                <Text style={styles.metaItemValue}>{order.order_type || "Standard"}</Text>
              </View>
            </View>

            <View style={styles.metaItem}>
              <MaterialCommunityIcons name="currency-inr" size={16} color="#6B7280" />
              <View>
                <Text style={styles.metaItemLabel}>Total Amount</Text>
                <Text style={[styles.metaItemValue, { color: "#10B981", fontWeight: "800" }]}>
                  ₹ {Number(order.total_amount).toFixed(2)}
                </Text>
              </View>
            </View>
          </View>
        </View>

        {/* Party / Customer Information Card */}
        {order.party && (
          <View style={styles.card}>
            <View style={styles.sectionHeader}>
              <MaterialCommunityIcons name="store" size={20} color="#7367F0" />
              <Text style={styles.sectionTitle}>Customer Information</Text>
            </View>

            <Text style={styles.partyName}>{order.party.name}</Text>
            {order.party.mobile ? (
              <View style={styles.infoRow}>
                <MaterialCommunityIcons name="phone" size={16} color="#6B7280" />
                <Text style={styles.infoText}>{order.party.mobile}</Text>
              </View>
            ) : null}
            {order.party.address ? (
              <View style={styles.infoRow}>
                <MaterialCommunityIcons name="map-marker-outline" size={16} color="#6B7280" />
                <Text style={styles.infoText}>{order.party.address}</Text>
              </View>
            ) : null}
          </View>
        )}

        {/* Logistics & Dispatch Card */}
        {(order.delivery_destination ||
          order.preferred_transport ||
          order.vehicle_no ||
          order.dispatch_date ||
          order.schema ||
          order.remarks) && (
          <View style={styles.card}>
            <View style={styles.sectionHeader}>
              <MaterialCommunityIcons name="truck-delivery-outline" size={20} color="#7367F0" />
              <Text style={styles.sectionTitle}>Dispatch & Delivery Details</Text>
            </View>

            {order.delivery_destination ? (
              <View style={styles.detailRow}>
                <Text style={styles.detailLabel}>Destination:</Text>
                <Text style={styles.detailValue}>{order.delivery_destination}</Text>
              </View>
            ) : null}

            {order.preferred_transport ? (
              <View style={styles.detailRow}>
                <Text style={styles.detailLabel}>Transport:</Text>
                <Text style={styles.detailValue}>{order.preferred_transport}</Text>
              </View>
            ) : null}

            {order.vehicle_no ? (
              <View style={styles.detailRow}>
                <Text style={styles.detailLabel}>Vehicle No:</Text>
                <Text style={styles.detailValue}>{order.vehicle_no}</Text>
              </View>
            ) : null}

            {order.dispatch_date ? (
              <View style={styles.detailRow}>
                <Text style={styles.detailLabel}>Dispatch Date:</Text>
                <Text style={styles.detailValue}>
                  {new Date(order.dispatch_date).toLocaleDateString()}
                </Text>
              </View>
            ) : null}

            {order.schema ? (
              <View style={styles.detailRow}>
                <Text style={styles.detailLabel}>Schema / Offer:</Text>
                <Text style={styles.detailValue}>{order.schema}</Text>
              </View>
            ) : null}

            {order.remarks ? (
              <View style={styles.detailRow}>
                <Text style={styles.detailLabel}>Remarks:</Text>
                <Text style={styles.detailValue}>{order.remarks}</Text>
              </View>
            ) : null}
          </View>
        )}

        {/* Order Items */}
        <View style={styles.card}>
          <View style={styles.sectionHeader}>
            <MaterialCommunityIcons name="package-variant-closed" size={20} color="#7367F0" />
            <Text style={styles.sectionTitle}>Order Items ({order.items?.length || 0})</Text>
          </View>

          {order.items && order.items.length > 0 ? (
            order.items.map((item: any, index: number) => (
              <View key={item.id || index} style={styles.itemRow}>
                <View style={styles.itemHeader}>
                  <Text style={styles.itemName}>
                    {item.product?.name || `Product ID: ${item.product_id}`}
                  </Text>
                  <Text style={styles.itemTotal}>
                    ₹ {Number(item.total || item.qty * item.rate).toFixed(2)}
                  </Text>
                </View>

                <View style={styles.itemFooter}>
                  <Text style={styles.itemQuantity}>
                    Qty: <Text style={{ fontWeight: "700", color: "#1F2937" }}>{item.qty || item.quantity}</Text>
                  </Text>
                  <Text style={styles.itemRate}>
                    Rate: ₹ {Number(item.rate || item.price).toFixed(2)}
                  </Text>
                </View>
              </View>
            ))
          ) : (
            <Text style={styles.emptyText}>No items found in this order.</Text>
          )}

          {/* Grand Total Summary */}
          <View style={styles.totalSummaryBox}>
            <View style={styles.totalRow}>
              <Text style={styles.totalLabel}>Grand Total</Text>
              <Text style={styles.grandTotalAmount}>
                ₹ {Number(order.total_amount).toFixed(2)}
              </Text>
            </View>
          </View>
        </View>
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: "#F3F4F6",
  },
  scrollContent: {
    padding: 14,
    paddingBottom: 40,
  },
  card: {
    backgroundColor: "#FFFFFF",
    borderRadius: 14,
    padding: 16,
    marginBottom: 12,
    shadowColor: "#000",
    shadowOpacity: 0.05,
    shadowRadius: 8,
    elevation: 3,
  },
  orderTopRow: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "flex-start",
  },
  orderLabel: {
    fontSize: 10,
    fontWeight: "700",
    color: "#9CA3AF",
    letterSpacing: 0.5,
  },
  orderNo: {
    fontSize: 16,
    fontWeight: "900",
    color: "#1F2937",
    marginTop: 2,
  },
  statusBadge: {
    backgroundColor: "#FEF3C7",
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 6,
  },
  statusCompleted: {
    backgroundColor: "#D1FAE5",
  },
  statusCancelled: {
    backgroundColor: "#FEE2E2",
  },
  statusText: {
    fontSize: 11,
    fontWeight: "800",
    color: "#D97706",
  },
  statusCompletedText: {
    color: "#059669",
  },
  statusCancelledText: {
    color: "#DC2626",
  },
  divider: {
    height: 1,
    backgroundColor: "#F3F4F6",
    marginVertical: 12,
  },
  metaGrid: {
    flexDirection: "row",
    flexWrap: "wrap",
    rowGap: 12,
  },
  metaItem: {
    width: "50%",
    flexDirection: "row",
    alignItems: "center",
    gap: 8,
  },
  metaItemLabel: {
    fontSize: 10,
    fontWeight: "600",
    color: "#9CA3AF",
  },
  metaItemValue: {
    fontSize: 13,
    fontWeight: "700",
    color: "#1F2937",
    marginTop: 1,
  },
  sectionHeader: {
    flexDirection: "row",
    alignItems: "center",
    gap: 8,
    marginBottom: 12,
  },
  sectionTitle: {
    fontSize: 15,
    fontWeight: "800",
    color: "#1F2937",
  },
  partyName: {
    fontSize: 15,
    fontWeight: "800",
    color: "#1F2937",
    marginBottom: 6,
  },
  infoRow: {
    flexDirection: "row",
    alignItems: "center",
    gap: 6,
    marginBottom: 4,
  },
  infoText: {
    fontSize: 13,
    color: "#4B5563",
    fontWeight: "600",
    flexShrink: 1,
  },
  detailRow: {
    flexDirection: "row",
    justifyContent: "space-between",
    paddingVertical: 4,
  },
  detailLabel: {
    fontSize: 13,
    fontWeight: "600",
    color: "#6B7280",
  },
  detailValue: {
    fontSize: 13,
    fontWeight: "700",
    color: "#1F2937",
    flexShrink: 1,
    textAlign: "right",
    marginLeft: 12,
  },
  itemRow: {
    paddingVertical: 10,
    borderBottomWidth: 1,
    borderBottomColor: "#F3F4F6",
  },
  itemHeader: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 4,
  },
  itemName: {
    fontSize: 14,
    fontWeight: "700",
    color: "#1F2937",
    flex: 1,
    marginRight: 8,
  },
  itemTotal: {
    fontSize: 14,
    fontWeight: "800",
    color: "#1F2937",
  },
  itemFooter: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
  },
  itemQuantity: {
    fontSize: 12,
    color: "#6B7280",
  },
  itemRate: {
    fontSize: 12,
    color: "#6B7280",
  },
  totalSummaryBox: {
    backgroundColor: "#F9FAFB",
    borderRadius: 10,
    padding: 12,
    marginTop: 12,
  },
  totalRow: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
  },
  totalLabel: {
    fontSize: 15,
    fontWeight: "800",
    color: "#374151",
  },
  grandTotalAmount: {
    fontSize: 18,
    fontWeight: "900",
    color: "#7367F0",
  },
  emptyText: {
    color: "#9CA3AF",
    textAlign: "center",
    paddingVertical: 14,
    fontWeight: "600",
  },
});
