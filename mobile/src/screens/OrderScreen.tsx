import { MaterialIcons, MaterialCommunityIcons } from "@expo/vector-icons";
import { useNavigation, useRoute, RouteProp } from "@react-navigation/native";
import type { NativeStackNavigationProp } from "@react-navigation/native-stack";
import React, { useEffect, useState, useCallback } from "react";
import {
  ActivityIndicator,
  Alert,
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from "react-native";
import { SafeAreaView } from "react-native-safe-area-context";

import ProductSelectionModal from "./ProductSelectionModal";
import { submitOrder, OrderPayload, OrderItemPayload } from "../api/order";
import { getProducts, Product, ProductUnit } from "../api/product";
import type { AppStackParamList } from "../navigation/RootNavigator";

type Nav = NativeStackNavigationProp<AppStackParamList, "Order">;
type RouteType = RouteProp<AppStackParamList, "Order">;

export interface CartItem {
  product: Product;
  unit: ProductUnit;
  quantity: number;
}

export default function OrderScreen() {
  const navigation = useNavigation<Nav>();
  const route = useRoute<RouteType>();
  const { visitId, partyId } = route.params;

  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [products, setProducts] = useState<Product[]>([]);
  const [cart, setCart] = useState<CartItem[]>([]);
  const [showProductModal, setShowProductModal] = useState(false);

  // Form Fields
  const [deliveryDestination, setDeliveryDestination] = useState("");
  const [preferredTransport, setPreferredTransport] = useState("");
  const [vehicleNo, setVehicleNo] = useState("");
  const [dispatchDate, setDispatchDate] = useState("");
  const [remarks, setRemarks] = useState("");
  const [schema, setSchema] = useState("");
  const [orderType, setOrderType] = useState("Sales Order");
  const [paymentMode, setPaymentMode] = useState("Cash");

  useEffect(() => {
    const fetchProducts = async () => {
      try {
        const res = await getProducts();
        if (res.success) {
          setProducts(res.data);
        }
      } catch (err: any) {
        Alert.alert("Error", "Failed to load products");
      } finally {
        setLoading(false);
      }
    };

    fetchProducts();
  }, []);

  const handleAddToCart = (item: CartItem) => {
    setCart((prev) => {
      const existing = prev.findIndex(
        (c) => c.product.id === item.product.id && c.unit.id === item.unit.id,
      );
      if (existing >= 0) {
        const newCart = [...prev];
        newCart[existing].quantity += item.quantity;
        return newCart;
      }
      return [...prev, item];
    });
  };

  const handleRemoveItem = (index: number) => {
    setCart((prev) => prev.filter((_, i) => i !== index));
  };

  const calculateTotal = () => {
    return cart.reduce(
      (total, item) => total + item.unit.price * item.quantity,
      0,
    );
  };

  const handleSubmit = async () => {
    if (cart.length === 0) {
      Alert.alert(
        "Validation Error",
        "Please add at least one product to the order.",
      );
      return;
    }

    setSubmitting(true);
    const payload: OrderPayload = {
      visit_id: visitId,
      party_id: partyId,
      delivery_destination: deliveryDestination,
      preferred_transport: preferredTransport,
      vehicle_no: vehicleNo,
      dispatch_date: dispatchDate,
      remarks,
      schema,
      order_type: orderType,
      payment_mode: paymentMode,
      items: cart.map((c) => ({
        product_id: c.product.id,
        product_unit_id: c.unit.id,
        quantity: c.quantity,
        price: c.unit.price,
      })),
    };

    try {
      const res = await submitOrder(payload);
      if (res.success) {
        Alert.alert("Success", "Order created successfully!", [
          { text: "OK", onPress: () => navigation.goBack() },
        ]);
      }
    } catch (err: any) {
      Alert.alert("Error", err.message || "Failed to submit order");
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) {
    return (
      <SafeAreaView style={styles.safe}>
        <ActivityIndicator
          size="large"
          color="#7367F0"
          style={{ marginTop: 40 }}
        />
      </SafeAreaView>
    );
  }

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
        <Text style={styles.headerTitle}>New Order</Text>
        <View style={{ width: 40 }} />
      </View>

      <KeyboardAvoidingView
        style={{ flex: 1 }}
        behavior={Platform.OS === "ios" ? "padding" : undefined}
      >
        <ScrollView contentContainerStyle={styles.scrollContent}>
          {/* Selected Products Section */}
          <View style={styles.card}>
            <View style={styles.cardHeader}>
              <Text style={styles.sectionTitle}>Selected Products</Text>
              <TouchableOpacity
                style={styles.addBtn}
                onPress={() => setShowProductModal(true)}
              >
                <MaterialIcons name="add" size={16} color="#fff" />
                <Text style={styles.addBtnText}>Add Items</Text>
              </TouchableOpacity>
            </View>

            {cart.length === 0 ? (
              <View style={styles.emptyState}>
                <MaterialCommunityIcons
                  name="cart-outline"
                  size={32}
                  color="#ccc"
                />
                <Text style={styles.emptyText}>Cart is empty</Text>
              </View>
            ) : (
              cart.map((item, index) => (
                <View
                  key={`${item.product.id}-${item.unit.id}`}
                  style={styles.cartItem}
                >
                  <View style={{ flex: 1 }}>
                    <Text style={styles.itemName}>{item.product.name}</Text>
                    <Text style={styles.itemVariant}>
                      {item.unit.unit_name} | ₹{item.unit.price} x{" "}
                      {item.quantity}
                    </Text>
                  </View>
                  <Text style={styles.itemTotal}>
                    ₹{(item.unit.price * item.quantity).toFixed(2)}
                  </Text>
                  <TouchableOpacity
                    style={styles.removeBtn}
                    onPress={() => handleRemoveItem(index)}
                  >
                    <MaterialIcons name="close" size={16} color="#ef4444" />
                  </TouchableOpacity>
                </View>
              ))
            )}

            {cart.length > 0 && (
              <View style={styles.totalRow}>
                <Text style={styles.totalLabel}>Grand Total</Text>
                <Text style={styles.totalAmount}>
                  ₹{calculateTotal().toFixed(2)}
                </Text>
              </View>
            )}
          </View>

          {/* Form Fields Section */}
          <View style={styles.card}>
            <Text style={styles.sectionTitle}>Order Details</Text>

            <Text style={styles.label}>Order Type</Text>
            <View style={styles.chipContainer}>
              {["Sales Order", "Pre-Order", "Sample"].map((type) => (
                <TouchableOpacity
                  key={type}
                  style={[styles.chip, orderType === type && styles.chipActive]}
                  onPress={() => setOrderType(type)}
                >
                  <Text
                    style={[
                      styles.chipText,
                      orderType === type && styles.chipTextActive,
                    ]}
                  >
                    {type}
                  </Text>
                </TouchableOpacity>
              ))}
            </View>

            <Text style={styles.label}>Payment Mode</Text>
            <View style={styles.chipContainer}>
              {["Cash", "Credit", "Online", "Cheque"].map((mode) => (
                <TouchableOpacity
                  key={mode}
                  style={[
                    styles.chip,
                    paymentMode === mode && styles.chipActive,
                  ]}
                  onPress={() => setPaymentMode(mode)}
                >
                  <Text
                    style={[
                      styles.chipText,
                      paymentMode === mode && styles.chipTextActive,
                    ]}
                  >
                    {mode}
                  </Text>
                </TouchableOpacity>
              ))}
            </View>

            <Text style={styles.label}>Schema</Text>
            <TextInput
              style={styles.input}
              placeholder="e.g. 10% Discount"
              value={schema}
              onChangeText={setSchema}
            />

            <Text style={styles.label}>Delivery Destination</Text>
            <TextInput
              style={styles.input}
              placeholder="Address..."
              value={deliveryDestination}
              onChangeText={setDeliveryDestination}
            />

            <Text style={styles.label}>Preferred Transport</Text>
            <TextInput
              style={styles.input}
              placeholder="e.g. VRL Logistics"
              value={preferredTransport}
              onChangeText={setPreferredTransport}
            />

            <Text style={styles.label}>Vehicle No</Text>
            <TextInput
              style={styles.input}
              placeholder="e.g. MP 04 AB 1234"
              value={vehicleNo}
              onChangeText={setVehicleNo}
            />

            <Text style={styles.label}>Dispatch Request Date (YYYY-MM-DD)</Text>
            <TextInput
              style={styles.input}
              placeholder="YYYY-MM-DD"
              value={dispatchDate}
              onChangeText={setDispatchDate}
            />

            <Text style={styles.label}>Remarks</Text>
            <TextInput
              style={[styles.input, { height: 80, textAlignVertical: "top" }]}
              placeholder="Additional notes..."
              multiline
              value={remarks}
              onChangeText={setRemarks}
            />
          </View>
        </ScrollView>
      </KeyboardAvoidingView>

      {/* Footer Submit */}
      <View style={styles.footer}>
        <TouchableOpacity
          style={[
            styles.submitBtn,
            (cart.length === 0 || submitting) && { opacity: 0.7 },
          ]}
          onPress={handleSubmit}
          disabled={cart.length === 0 || submitting}
        >
          {submitting ? (
            <ActivityIndicator color="#fff" />
          ) : (
            <Text style={styles.submitBtnText}>Submit Order</Text>
          )}
        </TouchableOpacity>
      </View>

      {/* Modals */}
      <ProductSelectionModal
        visible={showProductModal}
        onClose={() => setShowProductModal(false)}
        products={products}
        onAddToCart={handleAddToCart}
      />
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
  scrollContent: { padding: 14, paddingBottom: 100 },
  card: {
    backgroundColor: "#fff",
    borderRadius: 16,
    padding: 16,
    marginBottom: 16,
    elevation: 4,
  },
  cardHeader: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 16,
  },
  sectionTitle: { fontSize: 16, fontWeight: "800", color: "#111827" },
  addBtn: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#10B981",
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 20,
  },
  addBtnText: {
    color: "#fff",
    fontSize: 12,
    fontWeight: "bold",
    marginLeft: 4,
  },
  emptyState: { alignItems: "center", paddingVertical: 20 },
  emptyText: { color: "#8e8e8e", marginTop: 8, fontWeight: "600" },
  cartItem: {
    flexDirection: "row",
    alignItems: "center",
    paddingVertical: 12,
    borderBottomWidth: 1,
    borderBottomColor: "#f3f4f6",
  },
  itemName: { fontSize: 14, fontWeight: "bold", color: "#374151" },
  itemVariant: { fontSize: 12, color: "#6b7280", marginTop: 2 },
  itemTotal: {
    fontSize: 14,
    fontWeight: "800",
    color: "#111827",
    marginRight: 12,
  },
  removeBtn: { padding: 4, backgroundColor: "#fee2e2", borderRadius: 12 },
  totalRow: {
    flexDirection: "row",
    justifyContent: "space-between",
    paddingTop: 16,
    marginTop: 4,
  },
  totalLabel: { fontSize: 16, fontWeight: "bold", color: "#374151" },
  totalAmount: { fontSize: 18, fontWeight: "900", color: "#10B981" },
  label: {
    fontSize: 13,
    fontWeight: "700",
    color: "#374151",
    marginBottom: 6,
    marginTop: 12,
  },
  input: {
    backgroundColor: "#f9fafb",
    borderWidth: 1,
    borderColor: "#e5e7eb",
    borderRadius: 10,
    paddingHorizontal: 12,
    paddingVertical: 10,
    fontSize: 14,
    color: "#111827",
  },
  chipContainer: { flexDirection: "row", flexWrap: "wrap", gap: 8 },
  chip: {
    paddingHorizontal: 16,
    paddingVertical: 8,
    borderRadius: 20,
    backgroundColor: "#f3f4f6",
    borderWidth: 1,
    borderColor: "#e5e7eb",
  },
  chipActive: { backgroundColor: "#e0e7ff", borderColor: "#7367F0" },
  chipText: { fontSize: 12, fontWeight: "600", color: "#4b5563" },
  chipTextActive: { color: "#7367F0" },
  footer: {
    position: "absolute",
    bottom: 0,
    left: 0,
    right: 0,
    backgroundColor: "#fff",
    padding: 16,
    borderTopWidth: 1,
    borderTopColor: "#e5e7eb",
    elevation: 20,
  },
  submitBtn: {
    backgroundColor: "#7367F0",
    padding: 16,
    borderRadius: 12,
    alignItems: "center",
  },
  submitBtnText: { color: "#fff", fontSize: 16, fontWeight: "bold" },
});
