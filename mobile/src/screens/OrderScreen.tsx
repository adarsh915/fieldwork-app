import { MaterialIcons, MaterialCommunityIcons } from "@expo/vector-icons";
import { useNavigation, useRoute, RouteProp } from "@react-navigation/native";
import type { NativeStackNavigationProp } from "@react-navigation/native-stack";
import React, { useEffect, useState, useCallback } from "react";
import {
  ActivityIndicator,
  Alert,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import KeyboardAwareScreen from "../components/KeyboardAwareScreen";
import ScreenHeader from "../components/ScreenHeader";
import DatePickerModal from "../components/DatePickerModal";
import ProductSelectionModal from "./ProductSelectionModal";
import { submitOrder, OrderPayload } from "../api/order";
import { getProducts, Product, ProductUnit } from "../api/product";
import { getPartyDetails, PartyProfile } from "../api/party";

type OrderScreenRouteProp = RouteProp<
  {
    Order: {
      visitId?: number | null;
      partyId: number;
      partyName?: string;
      partyMobile?: string | null;
      partyAddress?: string | null;
      partyCity?: string | null;
      isDirectPhoneOrder?: boolean;
    };
  },
  "Order"
>;

export interface CartItem {
  product: Product;
  unit: ProductUnit;
  quantity: number;
}

export default function OrderScreen() {
  const navigation = useNavigation<NativeStackNavigationProp<any>>();
  const route = useRoute<OrderScreenRouteProp>();
  const insets = useSafeAreaInsets();
  const {
    visitId,
    partyId,
    partyName: initialPartyName,
    partyMobile: initialPartyMobile,
    partyAddress: initialPartyAddress,
    partyCity: initialPartyCity,
    isDirectPhoneOrder,
  } = route.params;

  const isPhoneOrder = !visitId || isDirectPhoneOrder;

  // Party state (Autofilled)
  const [party, setParty] = useState<PartyProfile | null>(null);
  const [loadingParty, setLoadingParty] = useState(false);

  // Products & Cart
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [products, setProducts] = useState<Product[]>([]);
  const [cart, setCart] = useState<CartItem[]>([]);
  const [showProductModal, setShowProductModal] = useState(false);

  // Date State & Picker
  const getTodayStr = () => {
    const d = new Date();
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, "0");
    const day = String(d.getDate()).padStart(2, "0");
    return `${y}-${m}-${day}`;
  };

  const [reportDate, setReportDate] = useState<string>(getTodayStr());
  const [showDatePicker, setShowDatePicker] = useState(false);
  const [dateError, setDateError] = useState<string | null>(null);

  // Notes / Remarks
  const [remarks, setRemarks] = useState("");

  // Load products and party details
  useEffect(() => {
    const fetchData = async () => {
      try {
        setLoading(true);
        const [prodRes, partyRes] = await Promise.all([
          getProducts(),
          partyId ? getPartyDetails(partyId).catch(() => null) : Promise.resolve(null),
        ]);

        if (prodRes.success) {
          setProducts(prodRes.data);
        }
        if (partyRes && partyRes.success) {
          setParty(partyRes.data);
        }
      } catch (err: any) {
        Alert.alert("Error", "Failed to load products list");
      } finally {
        setLoading(false);
      }
    };
    fetchData();
  }, [partyId]);

  // Handle adding products from selection modal
  const handleAddProduct = (item: CartItem) => {
    setCart((prev) => {
      const existingIndex = prev.findIndex(
        (c) => c.product.id === item.product.id && c.unit.id === item.unit.id,
      );
      if (existingIndex > -1) {
        const updated = [...prev];
        updated[existingIndex].quantity += item.quantity;
        return updated;
      }
      return [...prev, item];
    });
  };

  const handleUpdateQuantity = (index: number, newQty: number) => {
    if (newQty <= 0) {
      handleRemoveItem(index);
      return;
    }
    setCart((prev) => {
      const updated = [...prev];
      updated[index] = { ...updated[index], quantity: newQty };
      return updated;
    });
  };

  const handleRemoveItem = (index: number) => {
    setCart((prev) => prev.filter((_, i) => i !== index));
  };

  const handleDateChange = (newDate: string) => {
    const today = getTodayStr();
    if (newDate > today) {
      setDateError("Report date cannot be in the future");
      Alert.alert("Invalid Date", "Report date cannot be in the future.");
      return;
    }
    setDateError(null);
    setReportDate(newDate);
  };

  const totalQuantity = cart.reduce((sum, item) => sum + item.quantity, 0);
  const totalPrice = cart.reduce(
    (sum, item) => sum + item.quantity * (Number(item.unit.price) || 0),
    0,
  );

  // Validate and Submit Report
  const handleSubmit = async () => {
    if (cart.length === 0) {
      Alert.alert(
        "Product Required",
        "Please add at least one product with quantity to this report.",
      );
      return;
    }

    if (!reportDate) {
      Alert.alert("Validation Error", "Please select a valid report date.");
      return;
    }

    setSubmitting(true);
    const payload: OrderPayload = {
      visit_id: visitId || null,
      party_id: partyId,
      order_source: isPhoneOrder ? "phone" : "visit",
      order_date: reportDate,
      order_type: "Visit Report",
      payment_mode: "Report",
      remarks: remarks.trim() || undefined,
      items: cart.map((c) => ({
        product_id: c.product.id,
        product_unit_id: c.unit.id,
        quantity: c.quantity,
        price: c.unit.price || 0,
      })),
    };

    try {
      const res = await submitOrder(payload);
      if (res.success) {
        Alert.alert(
          "Report Submitted",
          "Visit product report has been saved successfully without stock deduction.",
          [{ text: "OK", onPress: () => navigation.goBack() }],
        );
      } else {
        Alert.alert("Error", res.message || "Failed to submit report");
      }
    } catch (err: any) {
      Alert.alert("Error", err.message || "Failed to submit product report");
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) {
    return (
      <View style={{ flex: 1, backgroundColor: "#F8F9FA" }}>
        <ScreenHeader
          title="Visit Product Report"
          onBack={() => navigation.goBack()}
        />
        <View style={styles.centerLoading}>
          <ActivityIndicator size="large" color="#7367F0" />
          <Text style={styles.loadingText}>Loading products & party details...</Text>
        </View>
      </View>
    );
  }

  const partyName = party?.name || initialPartyName || "Selected Party";
  const partyMobile = party?.mobile || initialPartyMobile;
  const partyAddress = party?.address || initialPartyAddress;
  const partyCity = party?.city || initialPartyCity;

  return (
    <View style={{ flex: 1, backgroundColor: "#F4F5FA" }}>
      <ScreenHeader
        title="Visit Product Report"
        onBack={() => navigation.goBack()}
      />

      <KeyboardAwareScreen
        contentStyle={[
          styles.container,
          { paddingBottom: Math.max(insets.bottom, 12) + 90 },
        ]}
        extraScrollHeight={120}
      >
        {/* Source Badge (Visit vs Direct) */}
        {isPhoneOrder && (
          <View style={styles.phoneOrderBanner}>
            <MaterialIcons name="phone-in-talk" size={20} color="#7367F0" />
            <View style={{ flex: 1, marginLeft: 10 }}>
              <Text style={styles.phoneOrderTitle}>Direct / Phone Entry</Text>
              <Text style={styles.phoneOrderSubtitle}>
                Creating a product report without an active check-in visit
              </Text>
            </View>
          </View>
        )}

        {/* 1. Autofilled Party Details Card */}
        <View style={styles.card}>
          <View style={styles.cardHeader}>
            <View style={styles.cardHeaderLeft}>
              <View style={styles.partyIconWrap}>
                <MaterialCommunityIcons name="store" size={20} color="#7367F0" />
              </View>
              <Text style={styles.cardTitle}>Party Details</Text>
            </View>
            <View style={styles.autofillBadge}>
              <MaterialIcons name="check-circle" size={13} color="#10B981" />
              <Text style={styles.autofillBadgeText}>Autofilled</Text>
            </View>
          </View>

          <View style={styles.partyInfoBody}>
            <Text style={styles.partyNameText}>{partyName}</Text>

            {partyMobile ? (
              <View style={styles.partyMetaRow}>
                <MaterialIcons name="phone" size={16} color="#6B7280" />
                <Text style={styles.partyMetaText}>{partyMobile}</Text>
              </View>
            ) : null}

            {(partyAddress || partyCity) ? (
              <View style={styles.partyMetaRow}>
                <MaterialIcons name="location-on" size={16} color="#6B7280" />
                <Text style={styles.partyMetaText}>
                  {[partyAddress, partyCity].filter(Boolean).join(", ")}
                </Text>
              </View>
            ) : null}

            {party?.party_type ? (
              <View style={styles.partyTypeTag}>
                <Text style={styles.partyTypeTagText}>{party.party_type}</Text>
              </View>
            ) : null}
          </View>
        </View>

        {/* 2. Date Picker Card with Validation */}
        <View style={styles.card}>
          <View style={styles.cardHeader}>
            <View style={styles.cardHeaderLeft}>
              <View style={[styles.partyIconWrap, { backgroundColor: "#FEF3C7" }]}>
                <MaterialCommunityIcons name="calendar-month" size={20} color="#D97706" />
              </View>
              <Text style={styles.cardTitle}>Report Date</Text>
            </View>
            {reportDate === getTodayStr() ? (
              <View style={[styles.autofillBadge, { backgroundColor: "#ECFDF5" }]}>
                <Text style={[styles.autofillBadgeText, { color: "#059669" }]}>Today</Text>
              </View>
            ) : null}
          </View>

          <TouchableOpacity
            style={[styles.dateSelectorBtn, dateError ? styles.dateSelectorError : null]}
            activeOpacity={0.8}
            onPress={() => setShowDatePicker(true)}
          >
            <View style={styles.dateSelectorLeft}>
              <MaterialCommunityIcons name="calendar-clock" size={22} color="#7367F0" />
              <View style={{ marginLeft: 10 }}>
                <Text style={styles.dateLabel}>Selected Date</Text>
                <Text style={styles.dateValue}>{reportDate}</Text>
              </View>
            </View>
            <View style={styles.changeDateBtn}>
              <Text style={styles.changeDateBtnText}>Change</Text>
              <MaterialIcons name="chevron-right" size={18} color="#7367F0" />
            </View>
          </TouchableOpacity>

          {dateError ? (
            <Text style={styles.errorText}>{dateError}</Text>
          ) : null}
        </View>

        {/* 3. Product Selection & Quantities Card */}
        <View style={styles.card}>
          <View style={styles.cardHeader}>
            <View style={styles.cardHeaderLeft}>
              <View style={[styles.partyIconWrap, { backgroundColor: "#EEF2FF" }]}>
                <MaterialCommunityIcons name="package-variant" size={20} color="#7367F0" />
              </View>
              <Text style={styles.cardTitle}>Products ({cart.length})</Text>
            </View>
            <TouchableOpacity
              style={styles.addProductBtn}
              activeOpacity={0.85}
              onPress={() => setShowProductModal(true)}
            >
              <MaterialIcons name="add" size={18} color="#FFFFFF" />
              <Text style={styles.addProductBtnText}>Add Products</Text>
            </TouchableOpacity>
          </View>

          {cart.length === 0 ? (
            <TouchableOpacity
              style={styles.emptyProductBox}
              activeOpacity={0.8}
              onPress={() => setShowProductModal(true)}
            >
              <MaterialCommunityIcons
                name="cart-plus"
                size={42}
                color="#9CA3AF"
              />
              <Text style={styles.emptyProductTitle}>No Products Selected</Text>
              <Text style={styles.emptyProductSub}>
                Tap here to search and select products & quantities
              </Text>
            </TouchableOpacity>
          ) : (
            <View style={styles.productList}>
              {cart.map((item, index) => {
                const itemRate = Number(item.unit.price) || 0;
                const itemTotal = item.quantity * itemRate;
                return (
                  <View key={`${item.product.id}-${item.unit.id}`} style={styles.productRowCard}>
                    <View style={styles.productRowMain}>
                      <View style={{ flex: 1 }}>
                        <Text style={styles.productRowName}>{item.product.name}</Text>
                        <View style={styles.productRowMeta}>
                          <Text style={styles.productUnitBadge}>
                            {item.unit.unit_name || "Standard Unit"}
                          </Text>
                          {item.product.code ? (
                            <Text style={styles.productSkuText}>
                              SKU: {item.product.code}
                            </Text>
                          ) : null}
                        </View>
                      </View>

                      {/* Delete Item */}
                      <TouchableOpacity
                        onPress={() => handleRemoveItem(index)}
                        style={styles.deleteItemBtn}
                        hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }}
                      >
                        <MaterialIcons name="delete-outline" size={22} color="#EF4444" />
                      </TouchableOpacity>
                    </View>

                    {/* Quantity Stepper & Manual Input */}
                    <View style={styles.qtyControlRow}>
                      <Text style={styles.qtyLabel}>Quantity:</Text>
                      <View style={styles.stepperContainer}>
                        <TouchableOpacity
                          style={styles.stepBtn}
                          onPress={() => handleUpdateQuantity(index, item.quantity - 1)}
                          hitSlop={{ top: 6, bottom: 6, left: 6, right: 6 }}
                        >
                          <MaterialIcons name="remove" size={18} color="#4B5563" />
                        </TouchableOpacity>

                        <TextInput
                          style={styles.qtyInput}
                          keyboardType="numeric"
                          value={String(item.quantity)}
                          onChangeText={(txt) => {
                            const val = parseInt(txt, 10);
                            if (!isNaN(val)) {
                              handleUpdateQuantity(index, val);
                            } else if (txt === "") {
                              handleUpdateQuantity(index, 0);
                            }
                          }}
                        />

                        <TouchableOpacity
                          style={[styles.stepBtn, styles.stepBtnAdd]}
                          onPress={() => handleUpdateQuantity(index, item.quantity + 1)}
                          hitSlop={{ top: 6, bottom: 6, left: 6, right: 6 }}
                        >
                          <MaterialIcons name="add" size={18} color="#FFFFFF" />
                        </TouchableOpacity>
                      </View>
                    </View>

                    {/* Line item rate & total price */}
                    <View style={styles.itemPriceRow}>
                      <View style={styles.itemRateWrap}>
                        <Text style={styles.itemRateLabel}>Rate:</Text>
                        <Text style={styles.itemRateVal}>₹{itemRate.toFixed(2)}</Text>
                      </View>
                      <View style={styles.itemTotalWrap}>
                        <Text style={styles.itemTotalLabel}>Total:</Text>
                        <Text style={styles.itemTotalVal}>₹{itemTotal.toFixed(2)}</Text>
                      </View>
                    </View>
                  </View>
                );
              })}

              {/* Summary of quantities & total amount */}
              <View style={styles.summaryRow}>
                <View style={styles.summaryCol}>
                  <Text style={styles.summaryLabel}>Total Items: {cart.length}</Text>
                  <Text style={styles.summaryValue}>
                    Total Quantity: <Text style={{ color: "#7367F0", fontWeight: "700" }}>{totalQuantity}</Text>
                  </Text>
                </View>
                <View style={[styles.summaryCol, { alignItems: "flex-end" }]}>
                  <Text style={styles.summaryLabel}>Total Amount</Text>
                  <Text style={styles.summaryTotalPrice}>₹{totalPrice.toFixed(2)}</Text>
                </View>
              </View>
            </View>
          )}
        </View>

        {/* 4. Visit Notes / Remarks */}
        <View style={styles.card}>
          <View style={styles.cardHeader}>
            <View style={styles.cardHeaderLeft}>
              <View style={[styles.partyIconWrap, { backgroundColor: "#F3F4F6" }]}>
                <MaterialCommunityIcons name="note-text-outline" size={20} color="#4B5563" />
              </View>
              <Text style={styles.cardTitle}>Visit Notes / Remarks</Text>
            </View>
          </View>
          <TextInput
            style={styles.textArea}
            placeholder="Enter discussion notes, requirements, or feedback from party..."
            placeholderTextColor="#9CA3AF"
            multiline
            numberOfLines={3}
            value={remarks}
            onChangeText={setRemarks}
          />
        </View>
      </KeyboardAwareScreen>

      {/* Floating Bottom Submit Button */}
      <View
        style={[
          styles.bottomBar,
          { paddingBottom: Math.max(insets.bottom, 12) + 8 },
        ]}
      >
        <View style={styles.bottomBarInfo}>
          <Text style={styles.bottomBarPriceVal}>₹{totalPrice.toFixed(2)}</Text>
          <Text style={styles.bottomBarQtyVal}>
            {totalQuantity} units • {cart.length} items
          </Text>
        </View>

        <TouchableOpacity
          style={[styles.submitBtn, submitting && styles.submitBtnDisabled]}
          activeOpacity={0.85}
          disabled={submitting}
          onPress={handleSubmit}
        >
          {submitting ? (
            <ActivityIndicator size="small" color="#FFFFFF" />
          ) : (
            <>
              <MaterialIcons name="send" size={20} color="#FFFFFF" style={{ marginRight: 6 }} />
              <Text style={styles.submitBtnText}>Submit Report</Text>
            </>
          )}
        </TouchableOpacity>
      </View>

      {/* Product Selection Modal */}
      <ProductSelectionModal
        visible={showProductModal}
        products={products}
        onClose={() => setShowProductModal(false)}
        onAddToCart={handleAddProduct}
      />

      {/* Date Picker Modal */}
      <DatePickerModal
        visible={showDatePicker}
        value={reportDate}
        maxDate={getTodayStr()}
        title="Select Report Date"
        onChange={handleDateChange}
        onClose={() => setShowDatePicker(false)}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    padding: 14,
    gap: 12,
  },
  centerLoading: {
    flex: 1,
    justifyContent: "center",
    alignItems: "center",
    gap: 10,
  },
  loadingText: {
    color: "#6B7280",
    fontSize: 14,
  },
  phoneOrderBanner: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#F5F3FF",
    borderLeftWidth: 4,
    borderLeftColor: "#7367F0",
    borderRadius: 12,
    padding: 12,
  },
  phoneOrderTitle: {
    fontSize: 14,
    fontWeight: "700",
    color: "#5B21B6",
  },
  phoneOrderSubtitle: {
    fontSize: 12,
    color: "#6D28D9",
    marginTop: 2,
  },
  card: {
    backgroundColor: "#FFFFFF",
    borderRadius: 16,
    padding: 16,
    shadowColor: "#000",
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.04,
    shadowRadius: 8,
    elevation: 2,
  },
  cardHeader: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 12,
  },
  cardHeaderLeft: {
    flexDirection: "row",
    alignItems: "center",
    gap: 8,
  },
  partyIconWrap: {
    width: 32,
    height: 32,
    borderRadius: 8,
    backgroundColor: "#F5F3FF",
    justifyContent: "center",
    alignItems: "center",
  },
  cardTitle: {
    fontSize: 15,
    fontWeight: "700",
    color: "#111827",
  },
  autofillBadge: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#ECFDF5",
    paddingVertical: 4,
    paddingHorizontal: 8,
    borderRadius: 12,
    gap: 4,
  },
  autofillBadgeText: {
    fontSize: 11,
    fontWeight: "700",
    color: "#059669",
  },
  partyInfoBody: {
    gap: 6,
  },
  partyNameText: {
    fontSize: 16,
    fontWeight: "700",
    color: "#1F2937",
  },
  partyMetaRow: {
    flexDirection: "row",
    alignItems: "center",
    gap: 6,
  },
  partyMetaText: {
    fontSize: 13,
    color: "#4B5563",
  },
  partyTypeTag: {
    alignSelf: "flex-start",
    backgroundColor: "#F3F4F6",
    paddingVertical: 3,
    paddingHorizontal: 8,
    borderRadius: 6,
    marginTop: 4,
  },
  partyTypeTagText: {
    fontSize: 11,
    fontWeight: "600",
    color: "#4B5563",
  },
  dateSelectorBtn: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    backgroundColor: "#F9FAFB",
    borderWidth: 1,
    borderColor: "#E5E7EB",
    borderRadius: 12,
    padding: 12,
  },
  dateSelectorError: {
    borderColor: "#EF4444",
  },
  dateSelectorLeft: {
    flexDirection: "row",
    alignItems: "center",
  },
  dateLabel: {
    fontSize: 11,
    color: "#6B7280",
    fontWeight: "600",
    textTransform: "uppercase",
  },
  dateValue: {
    fontSize: 15,
    fontWeight: "700",
    color: "#111827",
    marginTop: 2,
  },
  changeDateBtn: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#EEF2FF",
    paddingVertical: 6,
    paddingHorizontal: 10,
    borderRadius: 8,
  },
  changeDateBtnText: {
    fontSize: 12,
    fontWeight: "700",
    color: "#7367F0",
  },
  errorText: {
    color: "#EF4444",
    fontSize: 12,
    marginTop: 6,
    fontWeight: "500",
  },
  addProductBtn: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#7367F0",
    paddingVertical: 6,
    paddingHorizontal: 12,
    borderRadius: 10,
    gap: 4,
  },
  addProductBtnText: {
    color: "#FFFFFF",
    fontSize: 13,
    fontWeight: "700",
  },
  emptyProductBox: {
    borderWidth: 1.5,
    borderColor: "#E5E7EB",
    borderStyle: "dashed",
    borderRadius: 12,
    padding: 24,
    alignItems: "center",
    justifyContent: "center",
    gap: 6,
  },
  emptyProductTitle: {
    fontSize: 15,
    fontWeight: "700",
    color: "#374151",
    marginTop: 4,
  },
  emptyProductSub: {
    fontSize: 12,
    color: "#9CA3AF",
    textAlign: "center",
  },
  productList: {
    gap: 10,
  },
  productRowCard: {
    backgroundColor: "#F9FAFB",
    borderRadius: 12,
    padding: 12,
    borderWidth: 1,
    borderColor: "#E5E7EB",
  },
  productRowMain: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "flex-start",
  },
  productRowName: {
    fontSize: 14,
    fontWeight: "700",
    color: "#1F2937",
  },
  productRowMeta: {
    flexDirection: "row",
    alignItems: "center",
    gap: 8,
    marginTop: 4,
  },
  productUnitBadge: {
    fontSize: 11,
    fontWeight: "600",
    color: "#7367F0",
    backgroundColor: "#EEF2FF",
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 4,
  },
  productSkuText: {
    fontSize: 11,
    color: "#6B7280",
  },
  deleteItemBtn: {
    padding: 4,
  },
  qtyControlRow: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginTop: 10,
    paddingTop: 8,
    borderTopWidth: 1,
    borderTopColor: "#E5E7EB",
  },
  qtyLabel: {
    fontSize: 13,
    fontWeight: "600",
    color: "#4B5563",
  },
  stepperContainer: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#FFFFFF",
    borderRadius: 8,
    borderWidth: 1,
    borderColor: "#D1D5DB",
    overflow: "hidden",
  },
  stepBtn: {
    width: 32,
    height: 32,
    justifyContent: "center",
    alignItems: "center",
    backgroundColor: "#F3F4F6",
  },
  stepBtnAdd: {
    backgroundColor: "#7367F0",
  },
  qtyInput: {
    width: 48,
    height: 32,
    textAlign: "center",
    fontSize: 14,
    fontWeight: "700",
    color: "#111827",
    padding: 0,
  },
  itemPriceRow: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    marginTop: 8,
    paddingTop: 8,
    borderTopWidth: 1,
    borderTopColor: "#F3F4F6",
  },
  itemRateWrap: {
    flexDirection: "row",
    alignItems: "center",
    gap: 4,
  },
  itemRateLabel: {
    fontSize: 12,
    color: "#6B7280",
    fontWeight: "600",
  },
  itemRateVal: {
    fontSize: 12,
    color: "#374151",
    fontWeight: "700",
  },
  itemTotalWrap: {
    flexDirection: "row",
    alignItems: "center",
    gap: 4,
  },
  itemTotalLabel: {
    fontSize: 12,
    color: "#6B7280",
    fontWeight: "600",
  },
  itemTotalVal: {
    fontSize: 14,
    color: "#7367F0",
    fontWeight: "800",
  },
  summaryRow: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    paddingTop: 10,
    marginTop: 6,
    borderTopWidth: 1,
    borderTopColor: "#E5E7EB",
  },
  summaryCol: {
    justifyContent: "center",
  },
  summaryLabel: {
    fontSize: 12,
    color: "#6B7280",
    fontWeight: "600",
  },
  summaryValue: {
    fontSize: 13,
    color: "#374151",
    fontWeight: "600",
    marginTop: 2,
  },
  summaryTotalPrice: {
    fontSize: 16,
    fontWeight: "800",
    color: "#7367F0",
    marginTop: 2,
  },
  textArea: {
    backgroundColor: "#F9FAFB",
    borderWidth: 1,
    borderColor: "#E5E7EB",
    borderRadius: 12,
    padding: 12,
    fontSize: 14,
    color: "#1F2937",
    minHeight: 80,
    textAlignVertical: "top",
  },
  bottomBar: {
    position: "absolute",
    bottom: 0,
    left: 0,
    right: 0,
    backgroundColor: "#FFFFFF",
    borderTopWidth: 1,
    borderTopColor: "#E5E7EB",
    paddingVertical: 12,
    paddingHorizontal: 16,
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    shadowColor: "#000",
    shadowOffset: { width: 0, height: -3 },
    shadowOpacity: 0.06,
    shadowRadius: 8,
    elevation: 8,
  },
  bottomBarInfo: {
    justifyContent: "center",
  },
  bottomBarPriceVal: {
    fontSize: 18,
    fontWeight: "800",
    color: "#7367F0",
  },
  bottomBarQtyLabel: {
    fontSize: 11,
    color: "#6B7280",
    fontWeight: "600",
    textTransform: "uppercase",
  },
  bottomBarQtyVal: {
    fontSize: 12,
    fontWeight: "600",
    color: "#6B7280",
    marginTop: 1,
  },
  submitBtn: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#7367F0",
    paddingVertical: 12,
    paddingHorizontal: 22,
    borderRadius: 12,
    shadowColor: "#7367F0",
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 8,
    elevation: 4,
  },
  submitBtnDisabled: {
    opacity: 0.6,
  },
  submitBtnText: {
    color: "#FFFFFF",
    fontSize: 15,
    fontWeight: "700",
  },
});
