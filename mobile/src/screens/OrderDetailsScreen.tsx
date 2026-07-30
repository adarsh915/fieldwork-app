import { MaterialIcons } from "@expo/vector-icons";
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
import { SafeAreaView } from "react-native-safe-area-context";

import type { AppStackParamList } from "../navigation/RootNavigator";

type Nav = NativeStackNavigationProp<AppStackParamList, "OrderDetails">;
type RouteType = RouteProp<AppStackParamList, "OrderDetails">;

export default function OrderDetailsScreen() {
  const navigation = useNavigation<Nav>();
  const route = useRoute<RouteType>();
  const { order } = route.params;

  return (
    <SafeAreaView style={styles.safe}>
      {/* Header */}
      <View style={styles.header}>
        <TouchableOpacity
          style={styles.headerIconBtn}
          onPress={() => navigation.goBack()}
        >
          <MaterialIcons name="arrow-back-ios-new" size={20} color="#fff" />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Order Details</Text>
        <View style={{ width: 40 }} />
      </View>

      <ScrollView contentContainerStyle={styles.scrollContent}>
        {/* Order Info Card */}
        <View style={styles.card}>
          <Text style={styles.sectionTitle}>Order Information</Text>
          <View style={styles.row}>
            <Text style={styles.label}>Order No:</Text>
            <Text style={styles.value}>{order.order_no}</Text>
          </View>
          <View style={styles.row}>
            <Text style={styles.label}>Date:</Text>
            <Text style={styles.value}>
              {new Date(order.created_at).toLocaleDateString()}
            </Text>
          </View>
          <View style={styles.row}>
            <Text style={styles.label}>Status:</Text>
            <Text
              style={[
                styles.value,
                order.status === "completed" ? { color: "#10B981" } : { color: "#F59E0B" },
              ]}
            >
              {(order.status || "pending").toUpperCase()}
            </Text>
          </View>
          <View style={styles.row}>
            <Text style={styles.label}>Total Amount:</Text>
            <Text style={[styles.value, { fontWeight: "800", color: "#7367F0" }]}>
              ₹ {Number(order.total_amount).toFixed(2)}
            </Text>
          </View>
        </View>

        {/* Order Items */}
        <View style={styles.card}>
          <Text style={styles.sectionTitle}>Items ({order.items?.length || 0})</Text>
          {order.items && order.items.length > 0 ? (
            order.items.map((item: any, index: number) => (
              <View key={item.id || index} style={styles.itemRow}>
                <View style={styles.itemHeader}>
                  <Text style={styles.itemName}>
                    {item.product?.name || `Product ID: ${item.product_id}`}
                  </Text>
                </View>
                <View style={styles.itemBody}>
                  <Text style={styles.itemDetail}>
                    Qty: {item.qty}
                  </Text>
                  <Text style={styles.itemDetail}>
                    Rate: ₹ {Number(item.rate).toFixed(2)}
                  </Text>
                  <Text style={styles.itemTotal}>
                    ₹ {Number(item.total).toFixed(2)}
                  </Text>
                </View>
              </View>
            ))
          ) : (
            <Text style={styles.emptyText}>No items found.</Text>
          )}
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: "#EDEDED" },
  header: {
    backgroundColor: "#7367F0",
    paddingHorizontal: 14,
    paddingVertical: 14,
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
    color: "#fff",
    fontSize: 18,
    fontWeight: "800",
  },
  scrollContent: { padding: 14, paddingBottom: 40 },
  card: {
    backgroundColor: "#fff",
    borderRadius: 16,
    padding: 16,
    marginBottom: 16,
    elevation: 4,
  },
  sectionTitle: {
    fontSize: 16,
    fontWeight: "800",
    color: "#111827",
    marginBottom: 12,
  },
  row: {
    flexDirection: "row",
    justifyContent: "space-between",
    paddingVertical: 6,
    borderBottomWidth: 1,
    borderBottomColor: "#f3f4f6",
  },
  label: {
    fontSize: 14,
    color: "#6b7280",
    fontWeight: "600",
  },
  value: {
    fontSize: 14,
    color: "#111827",
    fontWeight: "700",
  },
  itemRow: {
    paddingVertical: 12,
    borderBottomWidth: 1,
    borderBottomColor: "#f3f4f6",
  },
  itemHeader: {
    marginBottom: 4,
  },
  itemName: {
    fontSize: 14,
    fontWeight: "700",
    color: "#374151",
  },
  itemBody: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
  },
  itemDetail: {
    fontSize: 13,
    color: "#6b7280",
    fontWeight: "600",
  },
  itemTotal: {
    fontSize: 14,
    fontWeight: "800",
    color: "#111827",
  },
  emptyText: {
    color: "#8e8e8e",
    textAlign: "center",
    paddingVertical: 10,
    fontWeight: "600",
  },
});

