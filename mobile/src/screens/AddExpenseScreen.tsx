import { MaterialCommunityIcons, MaterialIcons } from "@expo/vector-icons";
import { useNavigation, useRoute, type RouteProp } from "@react-navigation/native";
import type { NativeStackNavigationProp } from "@react-navigation/native-stack";
import * as ImagePicker from "expo-image-picker";
import { useEffect, useMemo, useRef, useState } from "react";
import {
  ActivityIndicator,
  Alert,
  Image,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from "react-native";
import KeyboardAwareScreen from "../components/KeyboardAwareScreen";
import ScreenHeader from "../components/ScreenHeader";
import { optimizeImageForUpload } from "../utils/imageOptimizer";

import { submitExpense, updateExpense, getExpenseCategories, type ExpenseCategory } from "../api/expense";
import { API_BASE_URL } from "../api/config";
import { useAlert } from "../context/AlertModalContext";
import type { AppStackParamList } from "../navigation/RootNavigator";

type Nav = NativeStackNavigationProp<AppStackParamList, "AddExpense">;
type RouteProps = RouteProp<AppStackParamList, "AddExpense">;

function formatYmd(d: Date): string {
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, "0");
  const day = String(d.getDate()).padStart(2, "0");
  return `${year}-${month}-${day}`;
}

export default function AddExpenseScreen() {
  const navigation = useNavigation<Nav>();
  const route = useRoute<RouteProps>();
  const { showSuccess, showError, showWarning } = useAlert();
  const editingExpense = route.params?.expense;
  const isEditing = !!editingExpense;

  const todayStr = useMemo(() => formatYmd(new Date()), []);
  const initialDate = useMemo(() => {
    if (editingExpense?.date) {
      return typeof editingExpense.date === "string"
        ? editingExpense.date.split("T")[0]
        : formatYmd(new Date(editingExpense.date));
    }
    return todayStr;
  }, [editingExpense, todayStr]);

  const [selectedDate, setSelectedDate] = useState<string>(initialDate);
  const [pastDaysAllowed, setPastDaysAllowed] = useState<number>(7);

  const [categories, setCategories] = useState<ExpenseCategory[]>([]);
  const [selectedCategory, setSelectedCategory] = useState<number | null>(editingExpense?.category_id ?? null);
  const [amount, setAmount] = useState(editingExpense?.amount ? String(editingExpense.amount) : "");

  const initialPhotoUri = useMemo(() => {
    if (!editingExpense) return null;
    if (editingExpense.bill_photo_url) return editingExpense.bill_photo_url;
    if (editingExpense.bill_photo) return `${API_BASE_URL}/storage/${editingExpense.bill_photo}`;
    return null;
  }, [editingExpense]);

  const [photoUri, setPhotoUri] = useState<string | null>(initialPhotoUri);
  const [submitting, setSubmitting] = useState(false);

  // Stationery Items State & refs
  const [items, setItems] = useState<{ item_name: string; quantity: number; unit_price: number; total_price: number }[]>(
    editingExpense?.items?.map((it) => ({
      item_name: it.item_name,
      quantity: it.quantity,
      unit_price: Number(it.unit_price),
      total_price: Number(it.total_price),
    })) ?? []
  );
  const [newItemName, setNewItemName] = useState("");
  const [newItemQty, setNewItemQty] = useState("");
  const [newItemPrice, setNewItemPrice] = useState("");

  const itemQtyRef = useRef<TextInput>(null);
  const itemPriceRef = useRef<TextInput>(null);

  useEffect(() => {
    getExpenseCategories()
      .then((res) => {
        if (res.success && res.data) {
          setCategories(res.data);
        }
        if (typeof res.past_days_allowed === "number") {
          setPastDaysAllowed(res.past_days_allowed);
        }
      })
      .catch(() => {});
  }, []);

  useEffect(() => {
    if (editingExpense) {
      if (editingExpense.category_id) setSelectedCategory(editingExpense.category_id);
      if (editingExpense.amount) setAmount(String(editingExpense.amount));
      if (editingExpense.date) {
        setSelectedDate(
          typeof editingExpense.date === "string"
            ? editingExpense.date.split("T")[0]
            : formatYmd(new Date(editingExpense.date))
        );
      }
      const pUrl =
        editingExpense.bill_photo_url ||
        (editingExpense.bill_photo ? `${API_BASE_URL}/storage/${editingExpense.bill_photo}` : null);
      if (pUrl) setPhotoUri(pUrl);
      if (editingExpense.items && editingExpense.items.length > 0) {
        setItems(
          editingExpense.items.map((it) => ({
            item_name: it.item_name,
            quantity: it.quantity,
            unit_price: Number(it.unit_price),
            total_price: Number(it.total_price),
          }))
        );
      }
    }
  }, [editingExpense]);

  const availableDates = useMemo(() => {
    const list: { dateStr: string; label: string; isPast: boolean }[] = [];
    const now = new Date();

    for (let i = 0; i <= pastDaysAllowed; i++) {
      const d = new Date();
      d.setDate(now.getDate() - i);
      const dateStr = formatYmd(d);

      let label = "";
      if (i === 0) {
        label = `Today (${d.toLocaleDateString(undefined, { day: "numeric", month: "short" })})`;
      } else if (i === 1) {
        label = `Yesterday (${d.toLocaleDateString(undefined, { day: "numeric", month: "short" })})`;
      } else {
        label = d.toLocaleDateString(undefined, { day: "numeric", month: "short" });
      }

      list.push({ dateStr, label, isPast: i > 0 });
    }

    if (selectedDate && !list.some((it) => it.dateStr === selectedDate)) {
      list.push({
        dateStr: selectedDate,
        label: new Date(selectedDate).toLocaleDateString(undefined, { day: "numeric", month: "short" }),
        isPast: true,
      });
    }

    return list;
  }, [pastDaysAllowed, selectedDate]);

  const selectedCategoryName = useMemo(() => {
    return categories.find((c) => c.id === selectedCategory)?.name?.trim().toLowerCase();
  }, [categories, selectedCategory]);

  const isStationery = useMemo(() => {
    return selectedCategoryName === 'stationery' || selectedCategoryName === 'stationary';
  }, [selectedCategoryName]);

  function handleAddItem() {
    if (!newItemName.trim() || !newItemQty || !newItemPrice) {
      showWarning("Required Fields", "Please fill item name, quantity, and price.");
      return;
    }
    const qty = parseInt(newItemQty, 10);
    const price = parseFloat(newItemPrice);
    if (isNaN(qty) || isNaN(price)) {
      showWarning("Invalid Values", "Please enter valid numbers for quantity and price.");
      return;
    }
    const total = qty * price;
    setItems((prev) => [...prev, { item_name: newItemName.trim(), quantity: qty, unit_price: price, total_price: total }]);
    
    // Update total amount
    const newItems = [...items, { item_name: newItemName.trim(), quantity: qty, unit_price: price, total_price: total }];
    const totalAmount = newItems.reduce((sum, i) => sum + i.total_price, 0);
    setAmount(totalAmount.toString());

    setNewItemName("");
    setNewItemQty("");
    setNewItemPrice("");
  }

  function handleRemoveItem(index: number) {
    const newItems = items.filter((_, i) => i !== index);
    setItems(newItems);
    const totalAmount = newItems.reduce((sum, i) => sum + i.total_price, 0);
    setAmount(totalAmount > 0 ? totalAmount.toString() : "");
  }

  async function showPhotoOptions() {
    Alert.alert("Upload Bill", "Choose option", [
      { text: "Camera", onPress: pickFromCamera },
      { text: "Gallery", onPress: pickFromGallery },
      { text: "Cancel", style: "cancel" },
    ]);
  }

  async function pickFromCamera() {
    const { status } = await ImagePicker.requestCameraPermissionsAsync();
    if (status !== "granted") {
      Alert.alert("Permission Denied", "Camera permission is required.");
      return;
    }
    const result = await ImagePicker.launchCameraAsync({ quality: 0.7 });
    if (!result.canceled && result.assets[0]) {
      const optimized = await optimizeImageForUpload(result.assets[0].uri);
      setPhotoUri(optimized);
    }
  }

  async function pickFromGallery() {
    const result = await ImagePicker.launchImageLibraryAsync({ quality: 0.7 });
    if (!result.canceled && result.assets[0]) {
      const optimized = await optimizeImageForUpload(result.assets[0].uri);
      setPhotoUri(optimized);
    }
  }

  async function handleSubmit() {
    if (!selectedCategory) {
      showWarning("Category Required", "Please select an expense category.");
      return;
    }
    if (isStationery && items.length === 0) {
      showWarning("Missing Items", "Please add at least one stationery item.");
      return;
    }
    if (!amount || isNaN(parseFloat(amount)) || parseFloat(amount) <= 0) {
      showWarning("Amount Required", "Please enter a valid expense amount.");
      return;
    }
    if (!photoUri) {
      showWarning("Receipt Photo Required", "Photo proof is mandatory for expense claims.");
      return;
    }

    setSubmitting(true);
    try {
      if (isEditing && editingExpense) {
        const res = await updateExpense(
          editingExpense.id,
          selectedCategory,
          amount,
          photoUri,
          isStationery ? items : undefined,
          selectedDate
        );
        showSuccess(
          "Expense Updated",
          res.message || "Your expense claim has been updated and resubmitted!",
          () => navigation.goBack()
        );
      } else {
        const res = await submitExpense(
          selectedCategory,
          amount,
          photoUri,
          isStationery ? items : undefined,
          selectedDate
        );

        showSuccess(
          "Added Successfully!",
          res.message || "Your expense claim has been submitted for review.",
          () => navigation.goBack()
        );
      }
    } catch (err: any) {
      showError(
        "Submission Error",
        err?.message || (isEditing ? "Failed to update expense." : "Failed to submit expense.")
      );
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <View style={{ flex: 1, backgroundColor: "#F3F4F6" }}>
      <ScreenHeader title={isEditing ? "Edit Expense" : "Add Expense"} onBack={() => navigation.goBack()} />

      <KeyboardAwareScreen contentStyle={{ padding: 16, paddingBottom: 60 }} backgroundColor="#F3F4F6">
        <View style={styles.card}>
          {isEditing && (editingExpense?.rejection_reason || editingExpense?.status === "rejected" || editingExpense?.status === "manager_rejected") && (
            <View style={styles.rejectionBanner}>
              <View style={styles.rejectionHeaderRow}>
                <MaterialCommunityIcons name="alert-octagon" size={20} color="#DC2626" />
                <Text style={styles.rejectionHeaderTitle}>Expense Rejected</Text>
              </View>
              {editingExpense.rejection_reason ? (
                <Text style={styles.rejectionReasonText}>
                  Reason: {editingExpense.rejection_reason}
                </Text>
              ) : null}
              {editingExpense.rejected_by?.name ? (
                <Text style={styles.rejectedByText}>
                  Rejected by: {editingExpense.rejected_by.name}
                </Text>
              ) : null}
              <Text style={styles.rejectionHint}>
                Please update your expense details or bill photo below and resubmit for approval.
              </Text>
            </View>
          )}

          {/* Date Selector */}
          <Text style={styles.fieldLabel}>Expense Date</Text>
          <ScrollView
            horizontal
            showsHorizontalScrollIndicator={false}
            style={styles.dateScrollRow}
            contentContainerStyle={styles.dateScrollContent}
          >
            {availableDates.map((item) => (
              <TouchableOpacity
                key={item.dateStr}
                style={[styles.dateChip, selectedDate === item.dateStr && styles.dateChipActive]}
                onPress={() => setSelectedDate(item.dateStr)}
              >
                <MaterialCommunityIcons
                  name={item.dateStr === todayStr ? "calendar-today" : "calendar-clock"}
                  size={15}
                  color={selectedDate === item.dateStr ? "#7367F0" : "#6B7280"}
                />
                <Text style={[styles.dateChipText, selectedDate === item.dateStr && styles.dateChipTextActive]}>
                  {item.label}
                </Text>
              </TouchableOpacity>
            ))}
          </ScrollView>

          {selectedDate !== todayStr && (
            <View style={styles.backdateNotice}>
              <MaterialCommunityIcons name="alert-circle-outline" size={18} color="#D97706" />
              <Text style={styles.backdateNoticeText}>
                Backdated expense will be flagged as "After Day End" for HR audit.
              </Text>
            </View>
          )}

          <Text style={[styles.fieldLabel, { marginTop: 16 }]}>Category</Text>
          <View style={styles.chipRow}>
            {categories.map((cat) => (
              <TouchableOpacity
                key={cat.id}
                style={[styles.chip, selectedCategory === cat.id && styles.chipActive]}
                onPress={() => setSelectedCategory(cat.id)}
              >
                <Text style={[styles.chipText, selectedCategory === cat.id && styles.chipTextActive]}>
                  {cat.name}
                </Text>
              </TouchableOpacity>
            ))}
          </View>

          {isStationery && (
            <View style={styles.stationeryCard}>
              <Text style={styles.stationeryTitle}>Items List</Text>
              
              {items.map((item, idx) => (
                <View key={idx} style={styles.itemRow}>
                  <View style={{ flex: 1 }}>
                    <Text style={styles.itemNameText}>{item.item_name}</Text>
                    <Text style={styles.itemSubText}>{item.quantity} × ₹{item.unit_price.toFixed(2)}</Text>
                  </View>
                  <Text style={styles.itemTotalText}>₹{item.total_price.toFixed(2)}</Text>
                  <TouchableOpacity onPress={() => handleRemoveItem(idx)} style={styles.removeItemBtn}>
                    <MaterialCommunityIcons name="trash-can-outline" size={20} color="#EF4444" />
                  </TouchableOpacity>
                </View>
              ))}

              <Text style={[styles.fieldLabel, { marginTop: 12 }]}>Add New Item</Text>
              <TextInput
                style={styles.itemInput}
                value={newItemName}
                onChangeText={setNewItemName}
                placeholder="Item name (e.g. Pen)"
                placeholderTextColor="#A5B4FC"
                returnKeyType="next"
                onSubmitEditing={() => itemQtyRef.current?.focus()}
              />
              <View style={styles.itemInputsRow}>
                <TextInput
                  ref={itemQtyRef}
                  style={[styles.itemInput, { flex: 1 }]}
                  value={newItemQty}
                  onChangeText={(v) => setNewItemQty(v.replace(/[^0-9]/g, ""))}
                  keyboardType="number-pad"
                  placeholder="Qty"
                  placeholderTextColor="#A5B4FC"
                  returnKeyType="next"
                  onSubmitEditing={() => itemPriceRef.current?.focus()}
                />
                <TextInput
                  ref={itemPriceRef}
                  style={[styles.itemInput, { flex: 1.5 }]}
                  value={newItemPrice}
                  onChangeText={(v) => setNewItemPrice(v.replace(/[^0-9.]/g, ""))}
                  keyboardType="numeric"
                  placeholder="Price per unit"
                  placeholderTextColor="#A5B4FC"
                  returnKeyType="done"
                  onSubmitEditing={handleAddItem}
                />
                <TouchableOpacity style={styles.addItemRowBtn} onPress={handleAddItem}>
                  <MaterialIcons name="add" size={24} color="#fff" />
                </TouchableOpacity>
              </View>
            </View>
          )}

          <Text style={[styles.fieldLabel, { marginTop: 16 }]}>Amount (₹)</Text>
          <TextInput
            style={[styles.input, isStationery && styles.inputDisabled]}
            value={amount}
            onChangeText={(v) => !isStationery && setAmount(v.replace(/[^0-9.]/g, ""))}
            keyboardType="numeric"
            placeholder={isStationery ? "Auto-calculated" : "e.g. 150"}
            placeholderTextColor={isStationery ? "#A5B4FC" : "#C7D2FE"}
            editable={!isStationery}
          />

          <Text style={[styles.fieldLabel, { marginTop: 16 }]}>Bill Photo (Mandatory)</Text>
          <TouchableOpacity style={styles.cameraWrap} activeOpacity={0.85} onPress={showPhotoOptions}>
            {photoUri ? (
              <View style={styles.photoPreviewWrap}>
                <Image source={{ uri: photoUri }} style={styles.photoPreview} />
                <View style={styles.retakeBtn}>
                  <Text style={styles.retakeText}>Change Photo</Text>
                </View>
              </View>
            ) : (
              <View style={styles.cameraPlaceholder}>
                <MaterialCommunityIcons name="camera-plus" size={40} color="#7367F0" />
                <Text style={styles.cameraHint}>Take a clear photo of the bill</Text>
              </View>
            )}
          </TouchableOpacity>

          <TouchableOpacity
            style={[styles.startBtn, submitting && { opacity: 0.6 }]}
            onPress={handleSubmit}
            disabled={submitting}
          >
            {submitting ? (
              <ActivityIndicator color="#fff" />
            ) : (
              <Text style={styles.startBtnText}>
                {isEditing ? "Update & Resubmit Claim" : "Submit Expense"}
              </Text>
            )}
          </TouchableOpacity>
        </View>
      </KeyboardAwareScreen>
    </View>
  );
}

const styles = StyleSheet.create({
  header: {
    backgroundColor: "#7367F0",
    paddingHorizontal: 14,
    paddingVertical: 14,
    flexDirection: "row",
    alignItems: "center",
  },
  headerIconBtn: { width: 40, height: 40, justifyContent: "center", alignItems: "flex-start" },
  headerTitle: { flex: 1, textAlign: "center", color: "#fff", fontSize: 18, fontWeight: "800" },
  scrollContent: { padding: 16, paddingBottom: 40 },
  card: { backgroundColor: "#fff", borderRadius: 16, padding: 16, elevation: 3 },
  fieldLabel: { color: "#374151", fontWeight: "800", fontSize: 13, marginBottom: 8 },
  
  chipRow: { flexDirection: "row", flexWrap: "wrap", gap: 8 },
  chip: {
    paddingHorizontal: 16,
    paddingVertical: 10,
    borderRadius: 20,
    backgroundColor: "#F3F4F6",
    borderWidth: 1.5,
    borderColor: "transparent",
  },
  chipActive: {
    backgroundColor: "#EEECFE",
    borderColor: "#7367F0",
  },
  chipText: {
    color: "#6B7280",
    fontWeight: "700",
    fontSize: 14,
  },
  chipTextActive: {
    color: "#7367F0",
  },

  input: {
    borderWidth: 1.5,
    borderColor: "#7367F0",
    borderRadius: 12,
    height: 50,
    paddingHorizontal: 16,
    fontSize: 18,
    fontWeight: "700",
    color: "#7367F0",
  },

  cameraWrap: {
    borderWidth: 1.5,
    borderColor: "#7367F0",
    borderRadius: 12,
    borderStyle: "dashed",
    marginTop: 4,
    marginBottom: 24,
    overflow: "hidden",
    backgroundColor: "#F9FAFB",
  },
  cameraPlaceholder: { alignItems: "center", paddingVertical: 24 },
  cameraHint: { fontSize: 13, fontWeight: "600", color: "#7367F0", marginTop: 8 },
  photoPreviewWrap: { position: "relative" },
  photoPreview: { width: "100%", height: 180, resizeMode: "cover" },
  retakeBtn: {
    position: "absolute",
    bottom: 8,
    right: 8,
    backgroundColor: "rgba(115,103,240,0.85)",
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 8,
  },
  retakeText: { color: "#fff", fontSize: 12, fontWeight: "700" },

  startBtn: {
    backgroundColor: "#7367F0",
    borderRadius: 12,
    paddingVertical: 16,
    alignItems: "center",
  },
  startBtnText: { color: "#fff", fontWeight: "900", fontSize: 16 },
  stationeryCard: {
    backgroundColor: "#F9FAFB",
    borderRadius: 12,
    padding: 12,
    borderWidth: 1.5,
    borderColor: "#E5E7EB",
    marginTop: 16,
  },
  stationeryTitle: {
    color: "#374151",
    fontWeight: "800",
    fontSize: 14,
    marginBottom: 8,
    borderBottomWidth: 1,
    borderBottomColor: "#E5E7EB",
    paddingBottom: 4,
  },
  itemRow: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#fff",
    borderRadius: 8,
    padding: 8,
    marginBottom: 8,
    borderWidth: 1,
    borderColor: "#F3F4F6",
  },
  itemNameText: {
    fontSize: 14,
    fontWeight: "700",
    color: "#1F2937",
  },
  itemSubText: {
    fontSize: 12,
    fontWeight: "600",
    color: "#6B7280",
    marginTop: 2,
  },
  itemTotalText: {
    fontSize: 14,
    fontWeight: "800",
    color: "#10B981",
    marginRight: 8,
  },
  removeItemBtn: {
    padding: 4,
  },
  itemInput: {
    borderWidth: 1,
    borderColor: "#C7D2FE",
    borderRadius: 8,
    height: 40,
    paddingHorizontal: 12,
    fontSize: 14,
    color: "#374151",
    backgroundColor: "#fff",
    marginBottom: 8,
  },
  itemInputsRow: {
    flexDirection: "row",
    gap: 8,
    alignItems: "center",
  },
  addItemRowBtn: {
    backgroundColor: "#10B981",
    width: 40,
    height: 40,
    borderRadius: 8,
    alignItems: "center",
    justifyContent: "center",
    marginBottom: 8,
  },
  inputDisabled: {
    backgroundColor: "#EEF2F6",
    borderColor: "#CBD5E1",
    color: "#64748B",
  },
  dateScrollRow: {
    marginHorizontal: -4,
    marginBottom: 4,
  },
  dateScrollContent: {
    paddingHorizontal: 4,
    gap: 8,
    flexDirection: "row",
    alignItems: "center",
  },
  dateChip: {
    flexDirection: "row",
    alignItems: "center",
    gap: 6,
    paddingHorizontal: 14,
    paddingVertical: 9,
    borderRadius: 20,
    backgroundColor: "#F3F4F6",
    borderWidth: 1.5,
    borderColor: "transparent",
  },
  dateChipActive: {
    backgroundColor: "#EEECFE",
    borderColor: "#7367F0",
  },
  dateChipText: {
    color: "#4B5563",
    fontWeight: "700",
    fontSize: 13,
  },
  dateChipTextActive: {
    color: "#7367F0",
  },
  backdateNotice: {
    flexDirection: "row",
    alignItems: "center",
    gap: 8,
    backgroundColor: "#FEF3C7",
    borderWidth: 1,
    borderColor: "#FCD34D",
    borderRadius: 10,
    paddingHorizontal: 12,
    paddingVertical: 8,
    marginTop: 8,
  },
  backdateNoticeText: {
    flex: 1,
    fontSize: 12,
    fontWeight: "600",
    color: "#92400E",
  },
  rejectionBanner: {
    backgroundColor: "#FEF2F2",
    borderColor: "#FCA5A5",
    borderWidth: 1.5,
    borderRadius: 12,
    padding: 12,
    marginBottom: 16,
  },
  rejectionHeaderRow: {
    flexDirection: "row",
    alignItems: "center",
    gap: 6,
    marginBottom: 4,
  },
  rejectionHeaderTitle: {
    fontSize: 14,
    fontWeight: "800",
    color: "#DC2626",
  },
  rejectionReasonText: {
    fontSize: 13,
    fontWeight: "700",
    color: "#991B1B",
    marginTop: 2,
    lineHeight: 18,
  },
  rejectedByText: {
    fontSize: 12,
    fontWeight: "600",
    color: "#B91C1C",
    marginTop: 4,
  },
  rejectionHint: {
    fontSize: 11,
    color: "#6B7280",
    marginTop: 6,
    fontStyle: "italic",
  },
});
