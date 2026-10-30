import { MaterialCommunityIcons, MaterialIcons } from "@expo/vector-icons";
import { useNavigation, useRoute, useFocusEffect } from "@react-navigation/native";
import type { RouteProp } from "@react-navigation/native";
import type { NativeStackNavigationProp } from "@react-navigation/native-stack";
import * as ImagePicker from "expo-image-picker";
import * as Location from "expo-location";
import { useCallback, useState } from "react";
import {
  ActivityIndicator,
  Alert,
  Image,
  Platform,
  RefreshControl,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";
import KeyboardAwareScreen from "../components/KeyboardAwareScreen";
import ScreenHeader from "../components/ScreenHeader";
import { optimizeImageForUpload } from "../utils/imageOptimizer";
import {
  getPartyPayments,
  getPaymentSettings,
  submitPayment,
  type PaymentRecord,
  type PaymentSettings,
} from "../api/payment";
import type { AppStackParamList } from "../navigation/RootNavigator";

type Nav = NativeStackNavigationProp<AppStackParamList, "PaymentCollection">;
type RouteType = RouteProp<AppStackParamList, "PaymentCollection">;

type PaymentMode = "cash" | "upi" | "bank_transfer" | "cheque";

export default function PaymentCollectionScreen() {
  const navigation = useNavigation<Nav>();
  const route = useRoute<RouteType>();
  const insets = useSafeAreaInsets();
  const { partyId, partyName, visitId } = route.params;

  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [refreshing, setRefreshing] = useState(false);

  // Dealer Data & History
  const [dealerInfo, setDealerInfo] = useState<{
    outstanding_balance: number;
    credit_limit: number;
    pending_amount: number;
  } | null>(null);
  const [payments, setPayments] = useState<PaymentRecord[]>([]);
  const [settings, setSettings] = useState<PaymentSettings | null>(null);

  // Form State
  const [amount, setAmount] = useState("");
  const [paymentMode, setPaymentMode] = useState<PaymentMode>("cash");
  const [referenceNumber, setReferenceNumber] = useState("");
  const [proofPhotoUri, setProofPhotoUri] = useState<string | null>(null);
  const [notes, setNotes] = useState("");
  const [showHistory, setShowHistory] = useState(false);

  const loadData = useCallback(async (isRefresh = false) => {
    if (!isRefresh) setLoading(true);
    try {
      const [partyRes, settingsRes] = await Promise.all([
        getPartyPayments(partyId),
        getPaymentSettings().catch(() => ({ success: false, data: null })),
      ]);

      if (partyRes && partyRes.dealer) {
        setDealerInfo({
          outstanding_balance: partyRes.dealer.outstanding_balance || 0,
          credit_limit: partyRes.dealer.credit_limit || 0,
          pending_amount: partyRes.dealer.pending_amount || 0,
        });
        setPayments(partyRes.data || []);
      }

      if (settingsRes && settingsRes.data) {
        setSettings(settingsRes.data);
      }
    } catch (err: any) {
      console.warn("Failed to load payment info", err);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [partyId]);

  useFocusEffect(
    useCallback(() => {
      loadData();
    }, [loadData])
  );

  const onRefresh = () => {
    setRefreshing(true);
    loadData(true);
  };

  // Image Picker (Camera or Gallery)
  const pickProofImage = async (useCamera = false) => {
    try {
      if (useCamera) {
        const { status } = await ImagePicker.requestCameraPermissionsAsync();
        if (status !== "granted") {
          Alert.alert("Permission Required", "Camera permission is needed to capture payment proof.");
          return;
        }
        const result = await ImagePicker.launchCameraAsync({
          mediaTypes: ImagePicker.MediaTypeOptions.Images,
          quality: 0.7,
        });
        if (!result.canceled && result.assets[0]?.uri) {
          const optimized = await optimizeImageForUpload(result.assets[0].uri);
          setProofPhotoUri(optimized);
        }
      } else {
        const { status } = await ImagePicker.requestMediaLibraryPermissionsAsync();
        if (status !== "granted") {
          Alert.alert("Permission Required", "Gallery permission is needed to select payment proof.");
          return;
        }
        const result = await ImagePicker.launchImageLibraryAsync({
          mediaTypes: ImagePicker.MediaTypeOptions.Images,
          quality: 0.7,
        });
        if (!result.canceled && result.assets[0]?.uri) {
          const optimized = await optimizeImageForUpload(result.assets[0].uri);
          setProofPhotoUri(optimized);
        }
      }
    } catch (err: any) {
      Alert.alert("Error", "Could not capture image.");
    }
  };

  const handlePhotoOptions = () => {
    Alert.alert("Upload Proof", "Choose an option", [
      { text: "Camera", onPress: () => pickProofImage(true) },
      { text: "Gallery", onPress: () => pickProofImage(false) },
      { text: "Cancel", style: "cancel" },
    ]);
  };

  const handleSubmit = async () => {
    const numAmount = parseFloat(amount);
    if (!amount || isNaN(numAmount) || numAmount <= 0) {
      Alert.alert("Validation Error", "Please enter a valid amount greater than 0.");
      return;
    }

    if (paymentMode !== "cash" && !referenceNumber.trim()) {
      const fieldName = paymentMode === "cheque" ? "Cheque Number" : paymentMode === "upi" ? "UPI Transaction ID (UTR)" : "Bank Reference / UTR";
      Alert.alert("Validation Error", `Please enter the ${fieldName}.`);
      return;
    }

    if (paymentMode === "cheque" && !proofPhotoUri) {
      Alert.alert("Validation Error", "A clear photo of the cheque is required.");
      return;
    }

    setSubmitting(true);
    try {
      // Auto capture GPS
      let latitude: number | undefined;
      let longitude: number | undefined;
      try {
        const { status } = await Location.requestForegroundPermissionsAsync();
        if (status === "granted") {
          const loc = await Location.getCurrentPositionAsync({ accuracy: Location.Accuracy.Balanced });
          latitude = loc.coords.latitude;
          longitude = loc.coords.longitude;
        }
      } catch (locErr) {
        // Continue even if GPS is temporarily unavailable
      }

      const res = await submitPayment({
        party_id: partyId,
        amount: numAmount,
        payment_mode: paymentMode,
        reference_number: referenceNumber.trim() || undefined,
        proof_photo_uri: proofPhotoUri || undefined,
        visit_id: visitId || undefined,
        collection_latitude: latitude,
        collection_longitude: longitude,
        notes: notes.trim() || undefined,
        idempotency_key: `pay_${partyId}_${numAmount}_${Date.now()}`,
      });

      if (res.success) {
        Alert.alert(
          "Payment Recorded",
          "Payment collection recorded successfully! It will reflect in the dealer's ledger once confirmed by Admin.",
          [
            {
              text: "OK",
              onPress: () => {
                setAmount("");
                setReferenceNumber("");
                setProofPhotoUri(null);
                setNotes("");
                loadData(true);
              },
            },
          ]
        );
      }
    } catch (err: any) {
      Alert.alert("Submission Failed", err.message || "Failed to record payment.");
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <View style={styles.container}>
      <ScreenHeader
        title={`Payment - ${partyName}`}
        onBack={() => navigation.goBack()}
      />

      <KeyboardAwareScreen
        contentStyle={[
          styles.scrollContent,
          { paddingBottom: Math.max(insets.bottom, 20) + 40 },
        ]}
        backgroundColor="#F3F4F6"
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} colors={["#7367F0"]} />}
      >
        {/* Dealer Financial Balance Summary Banner */}
        <View style={styles.dealerCard}>
          <View style={styles.dealerHeader}>
            <MaterialCommunityIcons name="store" size={20} color="#7367F0" />
            <Text style={styles.dealerName} numberOfLines={1}>{partyName}</Text>
          </View>

          <View style={styles.balanceGrid}>
            <View style={styles.balanceCol}>
              <Text style={styles.balanceLabel}>Outstanding Due</Text>
              <Text style={[styles.balanceValue, { color: (dealerInfo?.outstanding_balance ?? 0) > 0 ? "#EF4444" : "#10B981" }]}>
                ₹ {Number(dealerInfo?.outstanding_balance || 0).toFixed(2)}
              </Text>
            </View>

            <View style={styles.balanceDivider} />

            <View style={styles.balanceCol}>
              <Text style={styles.balanceLabel}>Pending Approval</Text>
              <Text style={[styles.balanceValue, { color: "#F59E0B" }]}>
                ₹ {Number(dealerInfo?.pending_amount || 0).toFixed(2)}
              </Text>
            </View>

            <View style={styles.balanceDivider} />

            <View style={styles.balanceCol}>
              <Text style={styles.balanceLabel}>Credit Limit</Text>
              <Text style={styles.balanceValue}>
                ₹ {Number(dealerInfo?.credit_limit || 0).toFixed(2)}
              </Text>
            </View>
          </View>
        </View>

        {/* Payment Collection Form */}
        <View style={styles.formCard}>
          <Text style={styles.sectionTitle}>Record Payment Collection</Text>

          {/* Amount */}
          <Text style={styles.label}>
            Amount (₹) <Text style={styles.required}>*</Text>
          </Text>
          <View style={styles.amountInputWrap}>
            <Text style={styles.rupeeSymbol}>₹</Text>
            <TextInput
              style={styles.amountInput}
              placeholder="0.00"
              placeholderTextColor="#9CA3AF"
              keyboardType="numeric"
              value={amount}
              onChangeText={setAmount}
            />
          </View>

          {/* Payment Mode Selector */}
          <Text style={styles.label}>
            Payment Mode <Text style={styles.required}>*</Text>
          </Text>
          <View style={styles.modeGrid}>
            {[
              { key: "cash", label: "Cash", icon: "cash" },
              { key: "upi", label: "UPI (Company QR)", icon: "qrcode-scan" },
              { key: "bank_transfer", label: "Bank Transfer", icon: "bank" },
              { key: "cheque", label: "Cheque", icon: "checkbook" },
            ].map((mode) => (
              <TouchableOpacity
                key={mode.key}
                style={[
                  styles.modeChip,
                  paymentMode === mode.key && styles.modeChipActive,
                ]}
                activeOpacity={0.8}
                onPress={() => setPaymentMode(mode.key as PaymentMode)}
              >
                <MaterialCommunityIcons
                  name={mode.icon as any}
                  size={18}
                  color={paymentMode === mode.key ? "#7367F0" : "#6B7280"}
                />
                <Text
                  style={[
                    styles.modeChipText,
                    paymentMode === mode.key && styles.modeChipTextActive,
                  ]}
                >
                  {mode.label}
                </Text>
              </TouchableOpacity>
            ))}
          </View>

          {/* Official Company UPI QR Code Banner (Strict Security Rule) */}
          {paymentMode === "upi" && (
            <View style={styles.upiBanner}>
              <View style={styles.upiHeader}>
                <MaterialCommunityIcons name="shield-check" size={18} color="#059669" />
                <Text style={styles.upiTitle}>Official Company UPI Account</Text>
              </View>
              <Text style={styles.upiSub}>Ask dealer to scan or pay directly to the company account below:</Text>

              <View style={styles.upiBox}>
                <Text style={styles.upiIdLabel}>Company UPI ID:</Text>
                <Text style={styles.upiIdValue} selectable>
                  {settings?.company_upi_id || "amplindustries@icici"}
                </Text>
              </View>
              <Text style={styles.upiWarning}>* Never receive money into any personal account.</Text>
            </View>
          )}

          {/* Bank Transfer Details Banner */}
          {paymentMode === "bank_transfer" && settings?.company_bank_details && (
            <View style={styles.bankBanner}>
              <Text style={styles.bankTitle}>Official Company Bank Account</Text>
              <Text style={styles.bankDetail}>Bank: {settings.company_bank_details.bank_name}</Text>
              <Text style={styles.bankDetail}>Account No: {settings.company_bank_details.account_no}</Text>
              <Text style={styles.bankDetail}>IFSC: {settings.company_bank_details.ifsc_code}</Text>
              <Text style={styles.bankDetail}>Branch: {settings.company_bank_details.branch}</Text>
            </View>
          )}

          {/* Reference Number / Cheque No / UTR */}
          {paymentMode !== "cash" && (
            <View style={{ marginTop: 12 }}>
              <Text style={styles.label}>
                {paymentMode === "cheque"
                  ? "Cheque Number"
                  : paymentMode === "upi"
                    ? "UPI UTR / Txn Ref ID"
                    : "Bank Reference (UTR)"}{" "}
                <Text style={styles.required}>*</Text>
              </Text>
              <TextInput
                style={styles.input}
                placeholder="Enter reference / transaction ID..."
                placeholderTextColor="#9CA3AF"
                value={referenceNumber}
                onChangeText={setReferenceNumber}
              />
            </View>
          )}

          {/* Photo Proof Upload */}
          <View style={{ marginTop: 12 }}>
            <Text style={styles.label}>
              Proof Photo {paymentMode === "cheque" ? <Text style={styles.required}>* (Cheque Photo Required)</Text> : "(Optional for Cash/UPI)"}
            </Text>
            {proofPhotoUri ? (
              <View style={styles.proofPreviewWrap}>
                <Image source={{ uri: proofPhotoUri }} style={styles.proofImage} />
                <TouchableOpacity
                  style={styles.removePhotoBtn}
                  onPress={() => setProofPhotoUri(null)}
                >
                  <MaterialIcons name="close" size={16} color="#fff" />
                </TouchableOpacity>
              </View>
            ) : (
              <TouchableOpacity
                style={styles.uploadBox}
                activeOpacity={0.8}
                onPress={handlePhotoOptions}
              >
                <MaterialCommunityIcons name="camera-plus-outline" size={26} color="#7367F0" />
                <Text style={styles.uploadText}>Attach Photo / Cheque Slip</Text>
              </TouchableOpacity>
            )}
          </View>

          {/* Remarks / Notes */}
          <Text style={styles.label}>Notes (Optional)</Text>
          <TextInput
            style={[styles.input, { height: 60, textAlignVertical: "top" }]}
            placeholder="Add any collection remarks..."
            placeholderTextColor="#9CA3AF"
            multiline
            value={notes}
            onChangeText={setNotes}
          />

          {/* Submit Button */}
          <TouchableOpacity
            style={[styles.submitBtn, submitting && { opacity: 0.7 }]}
            activeOpacity={0.85}
            onPress={handleSubmit}
            disabled={submitting}
          >
            {submitting ? (
              <ActivityIndicator color="#fff" size="small" />
            ) : (
              <>
                <MaterialCommunityIcons name="check-circle-outline" size={20} color="#fff" />
                <Text style={styles.submitBtnText}>Record Payment</Text>
              </>
            )}
          </TouchableOpacity>
        </View>

        {/* Payment History Section */}
        <View style={styles.historyCard}>
          <View style={styles.historyHeader}>
            <Text style={styles.sectionTitle}>Payment History ({payments.length})</Text>
            <TouchableOpacity onPress={() => setShowHistory(!showHistory)}>
              <Text style={styles.toggleText}>{showHistory ? "Hide" : "Show All"}</Text>
            </TouchableOpacity>
          </View>

          {loading ? (
            <ActivityIndicator color="#7367F0" style={{ paddingVertical: 20 }} />
          ) : payments.length === 0 ? (
            <View style={styles.emptyWrap}>
              <MaterialCommunityIcons name="clipboard-text-outline" size={36} color="#D1D5DB" />
              <Text style={styles.emptyText}>No payments recorded for this dealer yet.</Text>
            </View>
          ) : (
            (showHistory ? payments : payments.slice(0, 5)).map((item) => {
              const isConfirmed = item.status === "confirmed";
              const isRejected = item.status === "rejected";

              return (
                <View key={item.id} style={styles.paymentItem}>
                  <View style={styles.itemTopRow}>
                    <Text style={styles.receiptNo}>{item.receipt_no}</Text>
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
                        {isConfirmed ? "CONFIRMED" : isRejected ? "REJECTED" : "PENDING CONFIRMATION"}
                      </Text>
                    </View>
                  </View>

                  <View style={styles.itemBodyRow}>
                    <Text style={styles.itemDate}>
                      {new Date(item.collection_datetime || item.created_at).toLocaleDateString()}
                    </Text>
                    <Text style={styles.itemMode}>{item.payment_mode.toUpperCase()}</Text>
                    <Text style={styles.itemAmount}>₹ {Number(item.amount).toFixed(2)}</Text>
                  </View>

                  {isRejected && item.rejection_reason && (
                    <View style={styles.rejectionBox}>
                      <Text style={styles.rejectionLabel}>Rejection Reason:</Text>
                      <Text style={styles.rejectionText}>{item.rejection_reason}</Text>
                    </View>
                  )}
                </View>
              );
            })
          )}
        </View>
      </KeyboardAwareScreen>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: "#F3F4F6" },
  scrollContent: { padding: 14, paddingBottom: 40 },

  dealerCard: {
    backgroundColor: "#FFFFFF",
    borderRadius: 14,
    padding: 14,
    marginBottom: 12,
    elevation: 3,
    shadowColor: "#000",
    shadowOpacity: 0.05,
    shadowRadius: 8,
  },
  dealerHeader: {
    flexDirection: "row",
    alignItems: "center",
    gap: 8,
    marginBottom: 10,
  },
  dealerName: {
    fontSize: 16,
    fontWeight: "800",
    color: "#1F2937",
    flex: 1,
  },
  balanceGrid: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
  },
  balanceCol: {
    flex: 1,
    alignItems: "center",
  },
  balanceLabel: {
    fontSize: 10,
    fontWeight: "700",
    color: "#6B7280",
    textTransform: "uppercase",
  },
  balanceValue: {
    fontSize: 14,
    fontWeight: "900",
    color: "#1F2937",
    marginTop: 2,
  },
  balanceDivider: {
    width: 1,
    height: 28,
    backgroundColor: "#E5E7EB",
  },

  formCard: {
    backgroundColor: "#FFFFFF",
    borderRadius: 14,
    padding: 16,
    marginBottom: 12,
    elevation: 3,
    shadowColor: "#000",
    shadowOpacity: 0.05,
    shadowRadius: 8,
  },
  sectionTitle: {
    fontSize: 16,
    fontWeight: "800",
    color: "#1F2937",
    marginBottom: 12,
  },
  label: {
    fontSize: 12,
    fontWeight: "700",
    color: "#374151",
    marginBottom: 6,
    marginTop: 6,
  },
  required: { color: "#EF4444" },

  amountInputWrap: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#F9FAFB",
    borderWidth: 1,
    borderColor: "#E5E7EB",
    borderRadius: 10,
    paddingHorizontal: 12,
  },
  rupeeSymbol: {
    fontSize: 18,
    fontWeight: "800",
    color: "#7367F0",
    marginRight: 6,
  },
  amountInput: {
    flex: 1,
    fontSize: 18,
    fontWeight: "800",
    color: "#1F2937",
    height: 48,
  },

  modeGrid: {
    flexDirection: "row",
    flexWrap: "wrap",
    gap: 8,
    marginTop: 4,
    marginBottom: 8,
  },
  modeChip: {
    flexDirection: "row",
    alignItems: "center",
    gap: 6,
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: 8,
    backgroundColor: "#F3F4F6",
    borderWidth: 1,
    borderColor: "#E5E7EB",
  },
  modeChipActive: {
    backgroundColor: "#EDE9FE",
    borderColor: "#7367F0",
  },
  modeChipText: {
    fontSize: 12,
    fontWeight: "700",
    color: "#4B5563",
  },
  modeChipTextActive: {
    color: "#7367F0",
  },

  upiBanner: {
    backgroundColor: "#ECFDF5",
    borderWidth: 1,
    borderColor: "#A7F3D0",
    borderRadius: 10,
    padding: 12,
    marginTop: 6,
    marginBottom: 10,
  },
  upiHeader: {
    flexDirection: "row",
    alignItems: "center",
    gap: 6,
    marginBottom: 4,
  },
  upiTitle: {
    fontSize: 13,
    fontWeight: "800",
    color: "#065F46",
  },
  upiSub: {
    fontSize: 11,
    color: "#047857",
    marginBottom: 8,
  },
  upiBox: {
    backgroundColor: "#FFFFFF",
    borderRadius: 8,
    padding: 8,
    borderWidth: 1,
    borderColor: "#A7F3D0",
  },
  upiIdLabel: { fontSize: 10, color: "#6B7280", fontWeight: "600" },
  upiIdValue: { fontSize: 14, fontWeight: "900", color: "#065F46", marginTop: 1 },
  upiWarning: { fontSize: 10, color: "#DC2626", fontWeight: "700", marginTop: 6 },

  bankBanner: {
    backgroundColor: "#EFF6FF",
    borderWidth: 1,
    borderColor: "#BFDBFE",
    borderRadius: 10,
    padding: 10,
    marginTop: 6,
    marginBottom: 10,
  },
  bankTitle: { fontSize: 12, fontWeight: "800", color: "#1E40AF", marginBottom: 4 },
  bankDetail: { fontSize: 11, color: "#1E3A8A", fontWeight: "600" },

  input: {
    backgroundColor: "#F9FAFB",
    borderWidth: 1,
    borderColor: "#E5E7EB",
    borderRadius: 10,
    paddingHorizontal: 12,
    paddingVertical: 8,
    fontSize: 14,
    color: "#1F2937",
  },

  uploadBox: {
    backgroundColor: "#F9FAFB",
    borderWidth: 1.5,
    borderColor: "#EDE9FE",
    borderStyle: "dashed",
    borderRadius: 10,
    paddingVertical: 16,
    alignItems: "center",
    justifyContent: "center",
    gap: 4,
  },
  uploadText: {
    fontSize: 12,
    fontWeight: "700",
    color: "#7367F0",
  },
  proofPreviewWrap: {
    position: "relative",
    width: 100,
    height: 100,
    borderRadius: 10,
    overflow: "hidden",
    marginTop: 4,
  },
  proofImage: { width: "100%", height: "100%" },
  removePhotoBtn: {
    position: "absolute",
    top: 4,
    right: 4,
    backgroundColor: "#EF4444",
    borderRadius: 10,
    width: 20,
    height: 20,
    alignItems: "center",
    justifyContent: "center",
  },

  submitBtn: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    gap: 8,
    backgroundColor: "#7367F0",
    paddingVertical: 14,
    borderRadius: 12,
    marginTop: 18,
    elevation: 4,
    shadowColor: "#7367F0",
    shadowOpacity: 0.25,
    shadowRadius: 8,
  },
  submitBtnText: {
    fontSize: 15,
    fontWeight: "800",
    color: "#FFFFFF",
  },

  historyCard: {
    backgroundColor: "#FFFFFF",
    borderRadius: 14,
    padding: 16,
    elevation: 3,
    shadowColor: "#000",
    shadowOpacity: 0.05,
    shadowRadius: 8,
  },
  historyHeader: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 8,
  },
  toggleText: { fontSize: 12, fontWeight: "700", color: "#7367F0" },

  emptyWrap: {
    alignItems: "center",
    paddingVertical: 20,
  },
  emptyText: {
    fontSize: 12,
    color: "#9CA3AF",
    marginTop: 6,
    fontWeight: "600",
  },

  paymentItem: {
    paddingVertical: 10,
    borderBottomWidth: 1,
    borderBottomColor: "#F3F4F6",
  },
  itemTopRow: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 4,
  },
  receiptNo: {
    fontSize: 13,
    fontWeight: "800",
    color: "#1F2937",
    fontFamily: Platform.OS === "ios" ? "Courier" : "monospace",
  },
  statusBadge: {
    backgroundColor: "#FEF3C7",
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 4,
  },
  statusConfirmed: { backgroundColor: "#D1FAE5" },
  statusRejected: { backgroundColor: "#FEE2E2" },
  statusBadgeText: {
    fontSize: 9,
    fontWeight: "800",
    color: "#D97706",
  },
  statusConfirmedText: { color: "#059669" },
  statusRejectedText: { color: "#DC2626" },

  itemBodyRow: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
  },
  itemDate: { fontSize: 11, color: "#6B7280" },
  itemMode: { fontSize: 10, fontWeight: "700", color: "#4B5563" },
  itemAmount: { fontSize: 14, fontWeight: "800", color: "#10B981" },

  rejectionBox: {
    backgroundColor: "#FEF2F2",
    padding: 6,
    borderRadius: 6,
    marginTop: 4,
  },
  rejectionLabel: { fontSize: 10, fontWeight: "700", color: "#DC2626" },
  rejectionText: { fontSize: 11, color: "#B91C1C", marginTop: 1 },
});
