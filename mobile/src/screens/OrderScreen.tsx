import { MaterialIcons, MaterialCommunityIcons } from "@expo/vector-icons";
import { useNavigation, useRoute, RouteProp } from "@react-navigation/native";
import type { NativeStackNavigationProp } from "@react-navigation/native-stack";
import React, { useEffect, useState } from "react";
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
import { submitOrder, OrderPayload } from "../api/order";
import { getPartyDetails, PartyProfile } from "../api/party";
import { Product, ProductUnit } from "../api/product";

export interface CartItem {
  product?: Product;
  unit?: ProductUnit;
  quantity?: number;
  product_id?: number;
  product_name?: string;
  sku_code?: string;
  unit_name?: string;
  unit_conversion?: number;
  rate?: number;
  total_amount?: number;
}

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

const PHONE_QUICK_TAGS = [
  "Confirmed Verbal Order",
  "Rate & Discount Inquiry",
  "Stock Availability Check",
  "Payment Follow-up",
  "Will Reorder Next Week",
  "Samples / Catalog Requested",
];

const VISIT_QUICK_TAGS = [
  "Confirmed Verbal Order",
  "Stock Refill Order",
  "Urgent Delivery Required",
  "Rate & Discount Inquiry",
  "Samples / Catalog Given",
  "Payment Collected",
];

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
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);

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

  // Order Amount & Notes
  const [phoneAmount, setPhoneAmount] = useState<string>("");
  const [remarks, setRemarks] = useState("");

  // Load party details
  useEffect(() => {
    const fetchData = async () => {
      try {
        setLoading(true);
        if (partyId) {
          const partyRes = await getPartyDetails(partyId).catch(() => null);
          if (partyRes && partyRes.success) {
            setParty(partyRes.data);
          }
        }
      } catch (err: any) {
        console.warn("Failed to load details", err);
      } finally {
        setLoading(false);
      }
    };
    fetchData();
  }, [partyId]);

  const handleDateChange = (newDate: string) => {
    const today = getTodayStr();
    if (newDate > today) {
      setDateError("Order date cannot be in the future");
      Alert.alert("Invalid Date", "Order date cannot be in the future.");
      return;
    }
    setDateError(null);
    setReportDate(newDate);
  };

  // Validate and Submit Order
  const handleSubmit = async () => {
    if (!remarks.trim() && !phoneAmount) {
      Alert.alert(
        "Notes Required",
        "Please enter order items or discussion notes in remarks.",
      );
      return;
    }

    if (!reportDate) {
      Alert.alert("Validation Error", "Please select a valid order date.");
      return;
    }

    setSubmitting(true);
    const parsedAmount = phoneAmount ? parseFloat(phoneAmount) : 0;
    const finalAmount = isNaN(parsedAmount) ? 0 : parsedAmount;

    const payload: OrderPayload = {
      visit_id: visitId || null,
      party_id: partyId,
      order_source: isPhoneOrder ? "phone" : "visit",
      order_date: reportDate,
      order_type: isPhoneOrder ? "Phone Order" : "Store Order",
      payment_mode: isPhoneOrder ? "Phone" : "Visit",
      remarks: remarks.trim() || undefined,
      total_amount: finalAmount,
      items: [],
    };

    try {
      const res = await submitOrder(payload);
      if (res.success) {
        Alert.alert(
          isPhoneOrder ? "Phone Order Saved" : "Order Saved",
          isPhoneOrder
            ? "Phone order note has been recorded and saved to party history."
            : "Store order note has been recorded and saved successfully.",
          [{ text: "OK", onPress: () => navigation.goBack() }],
        );
      } else {
        Alert.alert("Error", res.message || "Failed to submit order");
      }
    } catch (err: any) {
      Alert.alert("Error", err.message || "Failed to submit order");
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) {
    return (
      <View style={{ flex: 1, backgroundColor: "#F8F9FA" }}>
        <ScreenHeader
          title={isPhoneOrder ? "Phone Order Note" : "Order Note"}
          onBack={() => navigation.goBack()}
        />
        <View style={styles.centerLoading}>
          <ActivityIndicator size="large" color="#7367F0" />
          <Text style={styles.loadingText}>Loading party details...</Text>
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
        title={isPhoneOrder ? "Phone Order Note" : "Order Note"}
        onBack={() => navigation.goBack()}
      />

      <KeyboardAwareScreen
        contentStyle={[
          styles.container,
          { paddingBottom: Math.max(insets.bottom, 12) + 90 },
        ]}
        extraScrollHeight={120}
      >
        {/* Source Banner */}
        <View style={styles.phoneOrderBanner}>
          <MaterialIcons
            name={isPhoneOrder ? "phone-in-talk" : "storefront"}
            size={22}
            color="#7367F0"
          />
          <View style={{ flex: 1, marginLeft: 10 }}>
            <Text style={styles.phoneOrderTitle}>
              {isPhoneOrder ? "Direct Phone Order" : "Store Visit Order"}
            </Text>
            <Text style={styles.phoneOrderSubtitle}>
              {isPhoneOrder
                ? "Record order details & consultation notes discussed over the phone"
                : "Record order items, quantities and notes agreed during store visit"}
            </Text>
          </View>
        </View>

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
              <Text style={styles.cardTitle}>Order Date</Text>
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

        {/* 3. Order Notes & Details Card */}
        <View style={styles.card}>
          <View style={styles.cardHeader}>
            <View style={styles.cardHeaderLeft}>
              <View style={[styles.partyIconWrap, { backgroundColor: "#EEF2FF" }]}>
                <MaterialCommunityIcons
                  name={isPhoneOrder ? "phone-log" : "clipboard-text-outline"}
                  size={20}
                  color="#7367F0"
                />
              </View>
              <Text style={styles.cardTitle}>
                {isPhoneOrder ? "Phone Order Details" : "Order Notes & Details"}
              </Text>
            </View>
          </View>

          {/* Optional Estimated Order Value */}
          <View style={{ marginBottom: 14 }}>
            <Text style={styles.fieldLabel}>Estimated Order Value (₹) - Optional</Text>
            <View style={styles.amountInputWrap}>
              <Text style={styles.amountPrefix}>₹</Text>
              <TextInput
                style={styles.amountInput}
                placeholder="0.00"
                placeholderTextColor="#9CA3AF"
                keyboardType="numeric"
                value={phoneAmount}
                onChangeText={setPhoneAmount}
              />
              {phoneAmount ? (
                <TouchableOpacity
                  onPress={() => setPhoneAmount("")}
                  style={{ padding: 6 }}
                >
                  <MaterialIcons name="cancel" size={18} color="#9CA3AF" />
                </TouchableOpacity>
              ) : null}
            </View>
          </View>

          {/* Quick Suggestion Chips */}
          <View style={{ marginBottom: 10 }}>
            <Text style={styles.fieldLabel}>Quick Suggestion Chips</Text>
            <View style={styles.quickTagsWrap}>
              {(isPhoneOrder ? PHONE_QUICK_TAGS : VISIT_QUICK_TAGS).map((tag) => (
                <TouchableOpacity
                  key={tag}
                  style={styles.quickTagBtn}
                  activeOpacity={0.7}
                  onPress={() => {
                    setRemarks((prev) =>
                      prev ? `${prev.trim()}\n• ${tag}` : `• ${tag}`
                    );
                  }}
                >
                  <MaterialIcons name="add" size={14} color="#7367F0" />
                  <Text style={styles.quickTagText}>{tag}</Text>
                </TouchableOpacity>
              ))}
            </View>
          </View>

          {/* Discussion Remarks / Items Note */}
          <View>
            <Text style={styles.fieldLabel}>Order Items / Discussion Notes *</Text>
            <TextInput
              style={styles.phoneTextArea}
              placeholder="Enter items ordered, quantities, agreed rates, delivery dates or discussion notes..."
              placeholderTextColor="#9CA3AF"
              multiline
              numberOfLines={4}
              value={remarks}
              onChangeText={setRemarks}
            />
          </View>
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
          <Text style={styles.bottomBarPriceVal}>
            {phoneAmount && !isNaN(Number(phoneAmount))
              ? `₹${Number(phoneAmount).toLocaleString("en-IN")}`
              : isPhoneOrder
              ? "Phone Order"
              : "Store Order"}
          </Text>
          <Text style={styles.bottomBarQtyVal}>
            {remarks.trim() ? "Notes recorded" : "Simple note entry"}
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
              <MaterialIcons
                name={isPhoneOrder ? "phone-in-talk" : "check-circle"}
                size={20}
                color="#FFFFFF"
                style={{ marginRight: 6 }}
              />
              <Text style={styles.submitBtnText}>
                {isPhoneOrder ? "Save Phone Order" : "Save Store Order"}
              </Text>
            </>
          )}
        </TouchableOpacity>
      </View>

      {/* Date Picker Modal */}
      <DatePickerModal
        visible={showDatePicker}
        value={reportDate}
        maxDate={getTodayStr()}
        title="Select Order Date"
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
  fieldLabel: {
    fontSize: 12,
    fontWeight: "700",
    color: "#4B5563",
    marginBottom: 6,
    textTransform: "uppercase",
  },
  amountInputWrap: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#F9FAFB",
    borderWidth: 1,
    borderColor: "#E5E7EB",
    borderRadius: 12,
    paddingHorizontal: 12,
    height: 48,
  },
  amountPrefix: {
    fontSize: 16,
    fontWeight: "700",
    color: "#7367F0",
    marginRight: 6,
  },
  amountInput: {
    flex: 1,
    fontSize: 15,
    fontWeight: "700",
    color: "#1F2937",
    padding: 0,
  },
  quickTagsWrap: {
    flexDirection: "row",
    flexWrap: "wrap",
    gap: 8,
  },
  quickTagBtn: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#EEF2FF",
    borderWidth: 1,
    borderColor: "#C7D2FE",
    paddingVertical: 6,
    paddingHorizontal: 10,
    borderRadius: 16,
    gap: 4,
  },
  quickTagText: {
    fontSize: 12,
    fontWeight: "600",
    color: "#4F46E5",
  },
  phoneTextArea: {
    backgroundColor: "#F9FAFB",
    borderWidth: 1,
    borderColor: "#E5E7EB",
    borderRadius: 12,
    padding: 12,
    fontSize: 14,
    color: "#1F2937",
    minHeight: 110,
    textAlignVertical: "top",
  },
});
