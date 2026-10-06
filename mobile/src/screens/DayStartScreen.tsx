import { MaterialCommunityIcons, MaterialIcons } from "@expo/vector-icons";
import { useNavigation } from "@react-navigation/native";
import type { NativeStackNavigationProp } from "@react-navigation/native-stack";
import * as ImagePicker from "expo-image-picker";
import { useEffect, useMemo, useState } from "react";
import {
  ActivityIndicator,
  Alert,
  Image,
  Modal,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from "react-native";
import KeyboardAwareScreen from "../components/KeyboardAwareScreen";
import ScreenHeader from "../components/ScreenHeader";

import { submitDayStart } from "../api/day";
import {
  getMasterTourPurposes,
  getMasterTourTypes,
  getMasterVehicleTypes,
  getMasterParties,
} from "../api/master";
import type {
  MasterItem,
  TourPurpose,
  VehicleType,
  Party,
} from "../api/master";
import type { RootStackParamList } from "../navigation/RootNavigator";

type Nav = NativeStackNavigationProp<RootStackParamList, "DayStart">;
type PickerKey = "tourPurpose" | "tourType" | "vehicleType";

export default function DayStartScreen() {
  const navigation = useNavigation<Nav>();

  // ── Master data from API ──────────────────────────────────────────────────
  const [tourPurposes, setTourPurposes] = useState<TourPurpose[]>([]);
  const [tourTypes, setTourTypes] = useState<MasterItem[]>([]);
  const [vehicleTypes, setVehicleTypes] = useState<VehicleType[]>([]);
  const [parties, setParties] = useState<Party[]>([]);
  const [masterLoading, setMasterLoading] = useState(true);

  // ── Form state ────────────────────────────────────────────────────────────
  const [tourPurpose, setTourPurpose] = useState<TourPurpose | null>(null);
  const [tourType, setTourType] = useState<MasterItem | null>(null);
  const [vehicleType, setVehicleType] = useState<VehicleType | null>(null);
  const [vehicleNo, setVehicleNo] = useState("");
  const [openingKm, setOpeningKm] = useState("");
  const [kmPhotoUri, setKmPhotoUri] = useState<string | null>(null);
  const [selectedPartyIds, setSelectedPartyIds] = useState<
    Record<number, boolean>
  >({});

  // ── UI state ──────────────────────────────────────────────────────────────
  const [pickerOpen, setPickerOpen] = useState<PickerKey | null>(null);
  const [partyModalOpen, setPartyModalOpen] = useState(false);
  const [partySearch, setPartySearch] = useState("");
  const [submitting, setSubmitting] = useState(false);

  // ── Load master data on mount ─────────────────────────────────────────────
  useEffect(() => {
    (async () => {
      setMasterLoading(true);
      try {
        const [p, t, v, parties] = await Promise.all([
          getMasterTourPurposes(),
          getMasterTourTypes(),
          getMasterVehicleTypes(),
          getMasterParties(),
        ]);
        setTourPurposes(p.data);
        setTourTypes(t.data);
        setVehicleTypes(v.data);
        setParties(parties.data);

        // Set defaults
        if (p.data.length) setTourPurpose(p.data[0]);
        if (t.data.length) setTourType(t.data[0]);
        if (v.data.length) setVehicleType(v.data[0]);
      } catch (e: any) {
        Alert.alert(
          "Error",
          "Failed to load form data. Please check your connection.",
        );
      } finally {
        setMasterLoading(false);
      }
    })();
  }, []);

  // ── Camera / Gallery ──────────────────────────────────────────────────────
  async function openCamera() {
    const { status } = await ImagePicker.requestCameraPermissionsAsync();
    if (status !== "granted") {
      Alert.alert(
        "Permission needed",
        "Camera access is required to take KM photo.",
      );
      return;
    }
    const result = await ImagePicker.launchCameraAsync({
      mediaTypes: ImagePicker.MediaTypeOptions.Images,
      quality: 0.7,
      allowsEditing: false,
    });
    if (!result.canceled && result.assets.length > 0) {
      setKmPhotoUri(result.assets[0].uri);
    }
  }

  // Removed openGallery for fraud prevention

  function showPhotoOptions() {
    openCamera();
  }

  // ── Parties ───────────────────────────────────────────────────────────────
  const filteredParties = useMemo(() => {
    const activeOnly = parties.filter((p) => p.approval_status !== "pending" && p.approval_status !== "rejected");
    const q = partySearch.trim().toLowerCase();
    if (!q) return activeOnly;
    return activeOnly.filter(
      (p) => p.name.toLowerCase().includes(q) || (p.mobile ?? "").includes(q),
    );
  }, [parties, partySearch]);

  const selectedCount = useMemo(
    () => Object.values(selectedPartyIds).filter(Boolean).length,
    [selectedPartyIds],
  );

  // ── Submit ────────────────────────────────────────────────────────────────
  async function handleSubmit() {
    if (!tourPurpose || !tourType || !vehicleType) {
      Alert.alert(
        "Incomplete",
        "Please select Tour Purpose, Tour Type and Vehicle Type.",
      );
      return;
    }

    setSubmitting(true);
    try {
      await submitDayStart({
        tour_purpose_id: tourPurpose.id,
        tour_type_id: tourType.id,
        vehicle_type_id: vehicleType.id,
        vehicle_no: vehicleNo || undefined,
        opening_km: openingKm || undefined,
        opening_km_image_uri: kmPhotoUri ?? undefined,
        party_ids: Object.entries(selectedPartyIds)
          .filter(([, v]) => v)
          .map(([k]) => Number(k)),
      });

      Alert.alert(
        "🎉 Day Started!",
        "Your day has been started successfully.",
        [{ text: "OK", onPress: () => navigation.goBack() }],
      );
    } catch (err: any) {
      if (err?.status === 409) {
        Alert.alert(
          "Already Started",
          "You have already started today's day.",
          [{ text: "OK", onPress: () => navigation.goBack() }],
        );
      } else {
        Alert.alert(
          "Error",
          err?.message ?? "Failed to start day. Please try again.",
        );
      }
    } finally {
      setSubmitting(false);
    }
  }

  // ── Loading state ─────────────────────────────────────────────────────────
  if (masterLoading) {
    return (
      <View style={{ flex: 1, backgroundColor: "#F4F4F8" }}>
        <ScreenHeader title="Day Start" onBack={() => navigation.goBack()} />
        <View style={styles.center}>
          <ActivityIndicator size="large" color="#7367F0" />
          <Text style={styles.loadingText}>Loading form data…</Text>
        </View>
      </View>
    );
  }

  return (
    <View style={{ flex: 1, backgroundColor: "#F4F4F8" }}>
      <ScreenHeader title="Day Start" onBack={() => navigation.goBack()} />

      <KeyboardAwareScreen contentStyle={styles.scrollContent}>
        <View style={styles.card}>
          {/* Tour Purpose */}
          <FieldSelect
            label="Tour Purpose"
            value={tourPurpose?.name ?? "—"}
            onPress={() => setPickerOpen("tourPurpose")}
          />

          {/* Tour Type */}
          <FieldSelect
            label="Tour Type"
            value={tourType?.name ?? "—"}
            onPress={() => setPickerOpen("tourType")}
          />

          {/* Vehicle Type */}
          <FieldSelect
            label="Vehicle Type"
            value={vehicleType?.name ?? "—"}
            onPress={() => setPickerOpen("vehicleType")}
          />

          {/* Vehicle No — only if vehicle type requires it */}
          {vehicleType?.requires_vehicle_no && (
            <FieldInput
              label="Vehicle No."
              value={vehicleNo}
              onChangeText={setVehicleNo}
              placeholder="e.g. GJ01AB1234"
            />
          )}

          {/* Parties to Visit */}
          <View style={styles.field}>
            <Text style={styles.fieldLabel}>Place To Visit (Parties)</Text>
            <TouchableOpacity
              style={styles.fieldRow}
              onPress={() => setPartyModalOpen(true)}
            >
              <Text
                style={[
                  styles.fieldValue,
                  !selectedCount && styles.fieldPlaceholder,
                ]}
              >
                {selectedCount
                  ? `${selectedCount} party(s) selected`
                  : "Tap to select parties"}
              </Text>
              <MaterialIcons
                name="keyboard-arrow-down"
                size={22}
                color="#7367F0"
              />
            </TouchableOpacity>
          </View>

          <TouchableOpacity
            style={styles.bigButton}
            activeOpacity={0.92}
            onPress={() => setPartyModalOpen(true)}
          >
            <Text style={styles.bigButtonText}>Select Parties To Visit</Text>
          </TouchableOpacity>

          {/* Opening KM */}
          <FieldInput
            label="Opening K.M."
            value={openingKm}
            onChangeText={(v) => setOpeningKm(v.replace(/[^0-9.]/g, ""))}
            keyboardType="numeric"
            placeholder="e.g. 12345"
          />

          {/* KM Photo */}
          <TouchableOpacity
            style={styles.cameraWrap}
            activeOpacity={0.85}
            onPress={showPhotoOptions}
          >
            {kmPhotoUri ? (
              <View style={styles.photoPreviewWrap}>
                <Image
                  source={{ uri: kmPhotoUri }}
                  style={styles.photoPreview}
                />
                <TouchableOpacity
                  style={styles.retakeBtn}
                  onPress={showPhotoOptions}
                >
                  <MaterialIcons name="camera-alt" size={16} color="#fff" />
                  <Text style={styles.retakeText}>Retake</Text>
                </TouchableOpacity>
              </View>
            ) : (
              <View style={styles.cameraPlaceholder}>
                <MaterialCommunityIcons
                  name="camera-plus-outline"
                  size={48}
                  color="#7367F0"
                />
                <Text style={styles.cameraHint}>
                  Tap to take Opening KM photo
                </Text>
              </View>
            )}
          </TouchableOpacity>

          {/* Action Buttons */}
          <View style={styles.actionRow}>
            <TouchableOpacity
              style={[styles.actionBtn, styles.cancelBtn]}
              onPress={() => navigation.goBack()}
              disabled={submitting}
            >
              <Text style={[styles.actionText, { color: "#7367F0" }]}>
                Cancel
              </Text>
            </TouchableOpacity>
            <TouchableOpacity
              style={[
                styles.actionBtn,
                styles.startBtn,
                submitting && styles.btnDisabled,
              ]}
              onPress={handleSubmit}
              disabled={submitting}
            >
              {submitting ? (
                <ActivityIndicator color="#fff" size="small" />
              ) : (
                <Text style={styles.actionText}>Day Start Now</Text>
              )}
            </TouchableOpacity>
          </View>
        </View>
      </KeyboardAwareScreen>

      {/* Tour Purpose Picker */}
      <PickerModal
        visible={pickerOpen === "tourPurpose"}
        title="Tour Purpose"
        options={tourPurposes.map((x) => x.name)}
        onCancel={() => setPickerOpen(null)}
        onSelect={(val) => {
          setTourPurpose(tourPurposes.find((x) => x.name === val) ?? null);
          setPickerOpen(null);
        }}
      />

      {/* Tour Type Picker */}
      <PickerModal
        visible={pickerOpen === "tourType"}
        title="Tour Type"
        options={tourTypes.map((x) => x.name)}
        onCancel={() => setPickerOpen(null)}
        onSelect={(val) => {
          setTourType(tourTypes.find((x) => x.name === val) ?? null);
          setPickerOpen(null);
        }}
      />

      {/* Vehicle Type Picker */}
      <PickerModal
        visible={pickerOpen === "vehicleType"}
        title="Vehicle Type"
        options={vehicleTypes.map((x) => x.name)}
        onCancel={() => setPickerOpen(null)}
        onSelect={(val) => {
          setVehicleType(vehicleTypes.find((x) => x.name === val) ?? null);
          setPickerOpen(null);
        }}
      />

      {/* Party Select Modal */}
      <PartySelectModal
        visible={partyModalOpen}
        query={partySearch}
        onQueryChange={setPartySearch}
        items={filteredParties}
        selected={selectedPartyIds}
        onToggle={(id) => setSelectedPartyIds((s) => ({ ...s, [id]: !s[id] }))}
        onDone={() => setPartyModalOpen(false)}
        onCancel={() => setPartyModalOpen(false)}
      />
    </View>
  );
}

// ─── Sub-components ────────────────────────────────────────────────────────────

function FieldSelect({
  label,
  value,
  onPress,
}: {
  label: string;
  value: string;
  onPress: () => void;
}) {
  return (
    <TouchableOpacity
      style={styles.field}
      activeOpacity={0.9}
      onPress={onPress}
    >
      <Text style={styles.fieldLabel}>{label}</Text>
      <View style={styles.fieldRow}>
        <Text style={styles.fieldValue}>{value}</Text>
        <MaterialIcons name="keyboard-arrow-down" size={22} color="#7367F0" />
      </View>
    </TouchableOpacity>
  );
}

function FieldInput({
  label,
  value,
  onChangeText,
  keyboardType,
  placeholder,
}: {
  label: string;
  value: string;
  onChangeText: (v: string) => void;
  keyboardType?: "default" | "numeric";
  placeholder?: string;
}) {
  return (
    <View style={styles.field}>
      <Text style={styles.fieldLabel}>{label}</Text>
      <TextInput
        style={styles.input}
        value={value}
        onChangeText={onChangeText}
        keyboardType={keyboardType}
        placeholder={placeholder}
        placeholderTextColor="#c7d2fe"
      />
    </View>
  );
}

function PickerModal({
  visible,
  title,
  options,
  onCancel,
  onSelect,
}: {
  visible: boolean;
  title: string;
  options: string[];
  onCancel: () => void;
  onSelect: (v: string) => void;
}) {
  return (
    <Modal visible={visible} animationType="slide" transparent>
      <View style={styles.modalOverlay}>
        <View style={styles.modalSheet}>
          <View style={styles.modalHeader}>
            <TouchableOpacity style={styles.modalBack} onPress={onCancel}>
              <MaterialIcons name="arrow-back-ios-new" size={18} color="#fff" />
            </TouchableOpacity>
            <Text style={styles.modalTitle}>{title}</Text>
            <View style={{ width: 40 }} />
          </View>
          <ScrollView contentContainerStyle={styles.modalList}>
            {options.map((opt) => (
              <TouchableOpacity
                key={opt}
                style={styles.modalItem}
                onPress={() => onSelect(opt)}
              >
                <Text style={styles.modalItemText}>{opt}</Text>
              </TouchableOpacity>
            ))}
          </ScrollView>
          <TouchableOpacity style={styles.modalCancelBtn} onPress={onCancel}>
            <Text style={styles.modalCancelText}>Cancel</Text>
          </TouchableOpacity>
        </View>
      </View>
    </Modal>
  );
}

function PartySelectModal({
  visible,
  query,
  onQueryChange,
  items,
  selected,
  onToggle,
  onDone,
  onCancel,
}: {
  visible: boolean;
  query: string;
  onQueryChange: (v: string) => void;
  items: Party[];
  selected: Record<number, boolean>;
  onToggle: (id: number) => void;
  onDone: () => void;
  onCancel: () => void;
}) {
  return (
    <Modal visible={visible} animationType="slide" transparent>
      <View style={styles.modalOverlay}>
        <View style={styles.modalSheet}>
          <View style={styles.modalHeader}>
            <TouchableOpacity style={styles.modalBack} onPress={onCancel}>
              <MaterialIcons name="arrow-back-ios-new" size={18} color="#fff" />
            </TouchableOpacity>
            <Text style={styles.modalTitle}>Select Parties</Text>
            <TouchableOpacity style={styles.modalDoneBtn} onPress={onDone}>
              <Text style={styles.modalDoneText}>Done</Text>
            </TouchableOpacity>
          </View>
          <View style={styles.partySearchWrap}>
            <TextInput
              style={styles.partySearchInput}
              placeholder="Search party name or mobile"
              placeholderTextColor="#8E8E8E"
              value={query}
              onChangeText={onQueryChange}
            />
            <MaterialIcons name="search" size={22} color="#7367F0" />
          </View>
          <ScrollView contentContainerStyle={styles.partyList}>
            {items.length === 0 ? (
              <Text style={styles.noParties}>No parties found</Text>
            ) : (
              items.map((item) => {
                const checked = !!selected[item.id];
                return (
                  <TouchableOpacity
                    key={item.id}
                    style={styles.partyRowItem}
                    onPress={() => onToggle(item.id)}
                  >
                    <MaterialCommunityIcons
                      name={
                        checked
                          ? "checkbox-marked-outline"
                          : "checkbox-blank-outline"
                      }
                      size={22}
                      color="#7367F0"
                    />
                    <View style={{ flex: 1 }}>
                      <Text style={styles.partyRowText}>{item.name}</Text>
                      {item.mobile ? (
                        <Text style={styles.partyRowSub}>{item.mobile}</Text>
                      ) : null}
                    </View>
                  </TouchableOpacity>
                );
              })
            )}
          </ScrollView>
        </View>
      </View>
    </Modal>
  );
}

// ─── Styles ────────────────────────────────────────────────────────────────────
const PURPLE = "#7367F0";

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: "#EDEDED" },
  center: { flex: 1, alignItems: "center", justifyContent: "center" },
  loadingText: { marginTop: 12, color: "#6b7280", fontSize: 14 },

  header: {
    backgroundColor: PURPLE,
    paddingHorizontal: 14,
    paddingVertical: 14,
    flexDirection: "row",
    alignItems: "center",
  },
  backBtn: { width: 40, height: 40, justifyContent: "center", alignItems: "flex-start" },
  headerTitle: {
    flex: 1,
    textAlign: "center",
    color: "#fff",
    fontSize: 18,
    fontWeight: "800",
  },

  scrollContent: { padding: 14, paddingBottom: 220 },
  card: {
    backgroundColor: "#fff",
    borderRadius: 18,
    padding: 14,
    elevation: 6,
  },

  field: {
    borderWidth: 1.5,
    borderColor: PURPLE,
    borderRadius: 12,
    paddingHorizontal: 14,
    paddingTop: 10,
    paddingBottom: 10,
    marginBottom: 12,
  },
  fieldLabel: {
    color: PURPLE,
    fontWeight: "800",
    fontSize: 12,
    marginBottom: 6,
  },
  fieldRow: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
  },
  fieldValue: { color: PURPLE, fontWeight: "800", fontSize: 16, flex: 1 },
  fieldPlaceholder: { color: "#c7d2fe", fontWeight: "600" },
  input: { height: 40, fontSize: 16, fontWeight: "700", color: PURPLE },

  bigButton: {
    backgroundColor: PURPLE,
    borderRadius: 12,
    paddingVertical: 14,
    alignItems: "center",
    marginBottom: 12,
  },
  bigButtonText: { color: "#fff", fontWeight: "900", fontSize: 16 },

  // Camera
  cameraWrap: {
    borderWidth: 1.5,
    borderColor: PURPLE,
    borderRadius: 14,
    borderStyle: "dashed",
    marginBottom: 16,
    overflow: "hidden",
  },
  cameraPlaceholder: { alignItems: "center", paddingVertical: 24 },
  cameraHint: { color: PURPLE, fontSize: 13, fontWeight: "600", marginTop: 8 },
  photoPreviewWrap: { position: "relative" },
  photoPreview: { width: "100%", height: 160 },
  retakeBtn: {
    position: "absolute",
    bottom: 8,
    right: 8,
    backgroundColor: "rgba(115,103,240,0.85)",
    borderRadius: 8,
    flexDirection: "row",
    alignItems: "center",
    gap: 4,
    paddingHorizontal: 10,
    paddingVertical: 6,
  },
  retakeText: { color: "#fff", fontSize: 12, fontWeight: "700" },

  // Action
  actionRow: { flexDirection: "row", gap: 12 },
  actionBtn: {
    flex: 1,
    borderRadius: 12,
    paddingVertical: 16,
    alignItems: "center",
    justifyContent: "center",
  },
  cancelBtn: {
    backgroundColor: "#EDE9FE",
    borderWidth: 1.5,
    borderColor: PURPLE,
  },
  startBtn: { backgroundColor: PURPLE },
  btnDisabled: { opacity: 0.6 },
  actionText: { color: "#fff", fontWeight: "900", fontSize: 16 },

  // Modals
  modalOverlay: { flex: 1, backgroundColor: "rgba(0,0,0,0.15)" },
  modalSheet: { flex: 1, backgroundColor: "#fff" },
  modalHeader: {
    backgroundColor: PURPLE,
    paddingHorizontal: 14,
    paddingVertical: 14,
    flexDirection: "row",
    alignItems: "center",
  },
  modalBack: {
    width: 40,
    height: 40,
    borderRadius: 14,
    alignItems: "center",
    justifyContent: "center",
  },
  modalTitle: {
    flex: 1,
    textAlign: "center",
    color: "#fff",
    fontSize: 18,
    fontWeight: "800",
  },
  modalDoneBtn: {
    backgroundColor: "rgba(255,255,255,0.2)",
    borderRadius: 8,
    paddingHorizontal: 12,
    paddingVertical: 6,
  },
  modalDoneText: { color: "#fff", fontWeight: "800", fontSize: 14 },
  modalList: { paddingHorizontal: 16, paddingTop: 12, paddingBottom: 24 },
  modalItem: {
    paddingVertical: 18,
    borderBottomWidth: 1,
    borderBottomColor: "#EDEDED",
  },
  modalItemText: { color: PURPLE, fontWeight: "800", fontSize: 18 },
  modalCancelBtn: {
    backgroundColor: PURPLE,
    marginHorizontal: 16,
    marginBottom: 24,
    borderRadius: 14,
    paddingVertical: 16,
    alignItems: "center",
  },
  modalCancelText: { color: "#fff", fontWeight: "900", fontSize: 16 },

  // Party select
  partySearchWrap: {
    margin: 14,
    borderWidth: 1.5,
    borderColor: PURPLE,
    borderRadius: 12,
    paddingHorizontal: 14,
    flexDirection: "row",
    alignItems: "center",
    height: 48,
  },
  partySearchInput: { flex: 1, fontSize: 15, fontWeight: "700", color: PURPLE },
  partyList: { paddingHorizontal: 14, paddingBottom: 24 },
  partyRowItem: {
    flexDirection: "row",
    alignItems: "center",
    gap: 10,
    paddingVertical: 14,
    borderBottomWidth: 1,
    borderBottomColor: "#EDEDED",
  },
  partyRowText: {
    fontSize: 13,
    fontWeight: "800",
    color: "#111827",
    lineHeight: 17,
  },
  partyRowSub: { fontSize: 11, color: "#6b7280", marginTop: 2 },
  noParties: {
    textAlign: "center",
    color: "#9ca3af",
    fontSize: 14,
    marginTop: 40,
  },
});
