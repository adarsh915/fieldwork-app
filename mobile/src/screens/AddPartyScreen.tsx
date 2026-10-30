import { MaterialCommunityIcons } from "@expo/vector-icons";
import { useNavigation } from "@react-navigation/native";
import type { NativeStackNavigationProp } from "@react-navigation/native-stack";
import { useEffect, useRef, useState } from "react";
import {
  ActivityIndicator,
  Modal,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from "react-native";
import { useSafeAreaInsets } from "react-native-safe-area-context";

import KeyboardAwareScreen from "../components/KeyboardAwareScreen";
import ScreenHeader from "../components/ScreenHeader";
import { submitParty } from "../api/party";
import indiaLocationsData from "../data/india_states_districts.json";
import { useAlert } from "../context/AlertModalContext";
import type { RootStackParamList } from "../navigation/RootNavigator";

type Nav = NativeStackNavigationProp<RootStackParamList, "AddParty">;

const PARTY_TYPES: Array<{
  key: string;
  label: string;
  icon: keyof typeof MaterialCommunityIcons.glyphMap;
}> = [
  { key: "Retailer", label: "Retailer", icon: "storefront-outline" },
  { key: "Distributor", label: "Distributor", icon: "truck-delivery-outline" },
  { key: "Wholesaler", label: "Wholesaler", icon: "warehouse" },
  { key: "Dealer", label: "Dealer", icon: "briefcase-outline" },
];

export default function AddPartyScreen() {
  const navigation = useNavigation<Nav>();
  const insets = useSafeAreaInsets();
  const { showSuccess, showError, showWarning } = useAlert();

  // Input refs for smooth chained keyboard navigation
  const mobileRef = useRef<TextInput>(null);
  const ownerNameRef = useRef<TextInput>(null);
  const areaRef = useRef<TextInput>(null);
  const addressRef = useRef<TextInput>(null);
  const pincodeRef = useRef<TextInput>(null);
  const emailRef = useRef<TextInput>(null);
  const gstRef = useRef<TextInput>(null);

  // Locations State (All-India JSON)
  const [selectedState, setSelectedState] = useState<string>("Madhya Pradesh");
  const [selectedDistrictName, setSelectedDistrictName] = useState<string>("");
  const [manualArea, setManualArea] = useState<string>("");
  const [locationPicker, setLocationPicker] = useState<"state" | "district" | null>(null);
  const [locationSearch, setLocationSearch] = useState<string>("");

  // Form State
  const [name, setName] = useState("");
  const [mobile, setMobile] = useState("");
  const [partyType, setPartyType] = useState("Retailer");
  const [ownerName, setOwnerName] = useState("");
  const [address, setAddress] = useState("");
  const [pincode, setPincode] = useState("");
  const [email, setEmail] = useState("");
  const [gstNumber, setGstNumber] = useState("");

  const [focusedField, setFocusedField] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  async function handleSubmit() {
    if (!name.trim()) {
      showWarning("Required Field", "Please enter the Party / Shop Name.");
      return;
    }
    if (!mobile.trim()) {
      showWarning("Required Field", "Please enter the Mobile Number.");
      return;
    }
    if (mobile.trim().length < 10) {
      showWarning("Invalid Mobile", "Please enter a valid 10-digit mobile number.");
      return;
    }
    if (!selectedDistrictName) {
      showWarning("Required Field", "Please select a District.");
      return;
    }
    if (pincode.trim() && pincode.trim().length !== 6) {
      showWarning("Invalid Pincode", "Pincode must be exactly 6 digits.");
      return;
    }
    if (gstNumber.trim()) {
      const gstRegex = /^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/;
      if (!gstRegex.test(gstNumber.trim().toUpperCase())) {
        showWarning("Invalid GSTIN", "Please enter a valid 15-character GST number (e.g. 24AAAAA0000A1Z5).");
        return;
      }
    }

    setSubmitting(true);
    try {
      const res = await submitParty({
        name: name.trim(),
        mobile: mobile.trim(),
        party_type: partyType,
        owner_name: ownerName.trim() || undefined,
        state: selectedState,
        district: selectedDistrictName,
        area: manualArea.trim() || undefined,
        address: address.trim() || undefined,
        city: selectedDistrictName,
        pincode: pincode.trim() || undefined,
        email: email.trim() || undefined,
        gst_number: gstNumber.trim() || undefined,
      });

      if (res.success) {
        showSuccess(
          "Party Registered! 🎉",
          res.message || "New party created and assigned to your territory successfully.",
          () => navigation.goBack()
        );
      } else {
        showError("Registration Failed", res.message || "Failed to submit party. Please verify details.");
      }
    } catch (err: any) {
      showError("Network Error", err.message || "Could not connect to server. Please try again.");
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <View style={styles.container}>
      <ScreenHeader title="Add New Party" onBack={() => navigation.goBack()} bgColor="#7367F0" />

      <KeyboardAwareScreen contentStyle={styles.scrollContent}>
        {/* Top Info Banner */}
        <View style={styles.infoBanner}>
          <View style={styles.infoIconWrap}>
            <MaterialCommunityIcons name="store-plus-outline" size={22} color="#7367F0" />
          </View>
          <View style={styles.infoContent}>
            <Text style={styles.infoTitle}>New Party Registration</Text>
            <Text style={styles.infoText}>
              Add new shop or dealer to assign to your route and manage visits, orders, and payments.
            </Text>
          </View>
        </View>

        {/* ── Card 1: Basic Information ───────────────────────── */}
        <View style={styles.card}>
          <View style={styles.cardHeader}>
            <View style={[styles.headerIconWrap, { backgroundColor: "#EDE9FE" }]}>
              <MaterialCommunityIcons name="storefront-outline" size={20} color="#7367F0" />
            </View>
            <View style={{ flex: 1, marginLeft: 10 }}>
              <Text style={styles.cardTitle}>Basic Information</Text>
              <Text style={styles.cardSubtitle}>Shop name, mobile & business type</Text>
            </View>
          </View>

          {/* Party Name */}
          <View style={styles.field}>
            <Text style={styles.label}>
              Party / Shop Name <Text style={styles.required}>*</Text>
            </Text>
            <View style={[styles.inputWrap, focusedField === "name" && styles.inputWrapFocused]}>
              <MaterialCommunityIcons
                name="store-outline"
                size={18}
                color={focusedField === "name" ? "#7367F0" : "#9CA3AF"}
              />
              <TextInput
                style={styles.inputWithIcon}
                placeholder="e.g. Sharma General Store"
                placeholderTextColor="#9CA3AF"
                value={name}
                onChangeText={setName}
                onFocus={() => setFocusedField("name")}
                onBlur={() => setFocusedField(null)}
                returnKeyType="next"
                onSubmitEditing={() => mobileRef.current?.focus()}
              />
            </View>
          </View>

          {/* Mobile Number */}
          <View style={styles.field}>
            <Text style={styles.label}>
              Mobile Number <Text style={styles.required}>*</Text>
            </Text>
            <View style={[styles.inputWrap, focusedField === "mobile" && styles.inputWrapFocused]}>
              <MaterialCommunityIcons
                name="phone-outline"
                size={18}
                color={focusedField === "mobile" ? "#7367F0" : "#9CA3AF"}
              />
              <TextInput
                ref={mobileRef}
                style={styles.inputWithIcon}
                placeholder="10-digit mobile number"
                placeholderTextColor="#9CA3AF"
                value={mobile}
                onChangeText={(v) => setMobile(v.replace(/[^0-9]/g, ""))}
                keyboardType="phone-pad"
                maxLength={10}
                onFocus={() => setFocusedField("mobile")}
                onBlur={() => setFocusedField(null)}
                returnKeyType="next"
                onSubmitEditing={() => ownerNameRef.current?.focus()}
              />
            </View>
          </View>

          {/* Party Type Chips */}
          <View style={[styles.field, { marginBottom: 4 }]}>
            <Text style={styles.label}>Party Type</Text>
            <View style={styles.typeGrid}>
              {PARTY_TYPES.map((t) => {
                const isActive = partyType === t.key;
                return (
                  <TouchableOpacity
                    key={t.key}
                    style={[styles.typeChip, isActive && styles.typeChipActive]}
                    onPress={() => setPartyType(t.key)}
                    activeOpacity={0.8}
                  >
                    <MaterialCommunityIcons
                      name={t.icon}
                      size={18}
                      color={isActive ? "#7367F0" : "#6B7280"}
                    />
                    <Text style={[styles.typeChipText, isActive && styles.typeChipTextActive]}>
                      {t.label}
                    </Text>
                  </TouchableOpacity>
                );
              })}
            </View>
          </View>
        </View>

        {/* ── Card 2: Contact & Address ───────────────────────── */}
        <View style={styles.card}>
          <View style={styles.cardHeader}>
            <View style={[styles.headerIconWrap, { backgroundColor: "#FEF3C7" }]}>
              <MaterialCommunityIcons name="map-marker-radius-outline" size={20} color="#D97706" />
            </View>
            <View style={{ flex: 1, marginLeft: 10 }}>
              <Text style={styles.cardTitle}>Contact & Location</Text>
              <Text style={styles.cardSubtitle}>Proprietor name & physical address</Text>
            </View>
          </View>

          {/* Owner Name */}
          <View style={styles.field}>
            <Text style={styles.label}>Owner / Contact Person</Text>
            <View style={[styles.inputWrap, focusedField === "owner" && styles.inputWrapFocused]}>
              <MaterialCommunityIcons
                name="account-outline"
                size={18}
                color={focusedField === "owner" ? "#7367F0" : "#9CA3AF"}
              />
              <TextInput
                ref={ownerNameRef}
                style={styles.inputWithIcon}
                placeholder="e.g. Ramesh Sharma"
                placeholderTextColor="#9CA3AF"
                value={ownerName}
                onChangeText={setOwnerName}
                onFocus={() => setFocusedField("owner")}
                onBlur={() => setFocusedField(null)}
                returnKeyType="next"
                onSubmitEditing={() => addressRef.current?.focus()}
              />
            </View>
          </View>

          {/* State & District Selectors (All-India JSON) */}
          <View style={styles.row}>
            {/* State */}
            <View style={[styles.field, { flex: 1, marginRight: 6 }]}>
              <Text style={styles.label}>
                State <Text style={styles.required}>*</Text>
              </Text>
              <TouchableOpacity
                style={styles.selectWrap}
                activeOpacity={0.8}
                onPress={() => {
                  setLocationSearch("");
                  setLocationPicker("state");
                }}
              >
                <MaterialCommunityIcons name="map-outline" size={18} color="#7367F0" />
                <Text
                  numberOfLines={1}
                  style={[
                    styles.selectValueText,
                    !selectedState && styles.selectPlaceholderText,
                  ]}
                >
                  {selectedState || "Select State"}
                </Text>
                <MaterialCommunityIcons name="chevron-down" size={18} color="#9CA3AF" />
              </TouchableOpacity>
            </View>

            {/* District */}
            <View style={[styles.field, { flex: 1, marginLeft: 6 }]}>
              <Text style={styles.label}>
                District / City <Text style={styles.required}>*</Text>
              </Text>
              <TouchableOpacity
                style={styles.selectWrap}
                activeOpacity={0.8}
                onPress={() => {
                  if (selectedState) {
                    setLocationSearch("");
                    setLocationPicker("district");
                  } else {
                    showWarning("Select State", "Please select a State first.");
                  }
                }}
              >
                <MaterialCommunityIcons name="city-variant-outline" size={18} color="#7367F0" />
                <Text
                  numberOfLines={1}
                  style={[
                    styles.selectValueText,
                    !selectedDistrictName && styles.selectPlaceholderText,
                  ]}
                >
                  {selectedDistrictName || "Select District"}
                </Text>
                <MaterialCommunityIcons name="chevron-down" size={18} color="#9CA3AF" />
              </TouchableOpacity>
            </View>
          </View>

          {/* Area / Town / Beat (Manual) */}
          <View style={styles.field}>
            <Text style={styles.label}>Area / Town / Beat</Text>
            <View style={[styles.inputWrap, focusedField === "area" && styles.inputWrapFocused]}>
              <MaterialCommunityIcons
                name="map-marker-radius-outline"
                size={18}
                color={focusedField === "area" ? "#7367F0" : "#9CA3AF"}
              />
              <TextInput
                ref={areaRef}
                style={styles.inputWithIcon}
                placeholder="e.g. Civil Lines, Main Market"
                placeholderTextColor="#9CA3AF"
                value={manualArea}
                onChangeText={setManualArea}
                onFocus={() => setFocusedField("area")}
                onBlur={() => setFocusedField(null)}
                returnKeyType="next"
                onSubmitEditing={() => addressRef.current?.focus()}
              />
            </View>
          </View>

          {/* Address */}
          <View style={styles.field}>
            <Text style={styles.label}>Shop Address</Text>
            <View style={[styles.textareaWrap, focusedField === "address" && styles.inputWrapFocused]}>
              <MaterialCommunityIcons
                name="map-marker-outline"
                size={18}
                color={focusedField === "address" ? "#7367F0" : "#9CA3AF"}
                style={{ marginTop: 2 }}
              />
              <TextInput
                ref={addressRef}
                style={styles.textareaWithIcon}
                placeholder="Shop number, street, landmark, market area..."
                placeholderTextColor="#9CA3AF"
                value={address}
                onChangeText={setAddress}
                multiline
                numberOfLines={3}
                onFocus={() => setFocusedField("address")}
                onBlur={() => setFocusedField(null)}
              />
            </View>
          </View>


          {/* Pincode */}
          <View style={[styles.field, { marginBottom: 4 }]}>
            <Text style={styles.label}>Pincode</Text>
            <View style={[styles.inputWrap, focusedField === "pincode" && styles.inputWrapFocused]}>
              <MaterialCommunityIcons
                name="mailbox-outline"
                size={18}
                color={focusedField === "pincode" ? "#7367F0" : "#9CA3AF"}
              />
              <TextInput
                ref={pincodeRef}
                style={styles.inputWithIcon}
                placeholder="6-digit pincode"
                placeholderTextColor="#9CA3AF"
                value={pincode}
                onChangeText={(v) => setPincode(v.replace(/[^0-9]/g, ""))}
                keyboardType="number-pad"
                maxLength={6}
                onFocus={() => setFocusedField("pincode")}
                onBlur={() => setFocusedField(null)}
                returnKeyType="next"
                onSubmitEditing={() => emailRef.current?.focus()}
              />
            </View>
          </View>
        </View>

        {/* ── Card 3: Business & Tax Details ──────────────────── */}
        <View style={styles.card}>
          <View style={styles.cardHeader}>
            <View style={[styles.headerIconWrap, { backgroundColor: "#EEF2FF" }]}>
              <MaterialCommunityIcons name="file-document-outline" size={20} color="#7367F0" />
            </View>
            <View style={{ flex: 1, marginLeft: 10 }}>
              <Text style={styles.cardTitle}>Business & Tax Details</Text>
              <Text style={styles.cardSubtitle}>Optional email & GSTIN identification</Text>
            </View>
          </View>

          {/* Email */}
          <View style={styles.field}>
            <Text style={styles.label}>Email Address</Text>
            <View style={[styles.inputWrap, focusedField === "email" && styles.inputWrapFocused]}>
              <MaterialCommunityIcons
                name="email-outline"
                size={18}
                color={focusedField === "email" ? "#7367F0" : "#9CA3AF"}
              />
              <TextInput
                ref={emailRef}
                style={styles.inputWithIcon}
                placeholder="dealer@example.com"
                placeholderTextColor="#9CA3AF"
                value={email}
                onChangeText={setEmail}
                keyboardType="email-address"
                autoCapitalize="none"
                onFocus={() => setFocusedField("email")}
                onBlur={() => setFocusedField(null)}
                returnKeyType="next"
                onSubmitEditing={() => gstRef.current?.focus()}
              />
            </View>
          </View>

          {/* GST Number */}
          <View style={[styles.field, { marginBottom: 4 }]}>
            <Text style={styles.label}>GST Number (Optional)</Text>
            <View style={[styles.inputWrap, focusedField === "gst" && styles.inputWrapFocused]}>
              <MaterialCommunityIcons
                name="receipt-text-outline"
                size={18}
                color={focusedField === "gst" ? "#7367F0" : "#9CA3AF"}
              />
              <TextInput
                ref={gstRef}
                style={styles.inputWithIcon}
                placeholder="24AAAAA0000A1Z5"
                placeholderTextColor="#9CA3AF"
                value={gstNumber}
                onChangeText={setGstNumber}
                autoCapitalize="characters"
                maxLength={15}
                onFocus={() => setFocusedField("gst")}
                onBlur={() => setFocusedField(null)}
                returnKeyType="done"
                onSubmitEditing={handleSubmit}
              />
            </View>
          </View>
        </View>

        {/* ── Submit Button ──────────────────────────────────── */}
        <TouchableOpacity
          style={[styles.submitBtn, submitting && styles.submitBtnDisabled]}
          onPress={handleSubmit}
          disabled={submitting}
          activeOpacity={0.85}
        >
          {submitting ? (
            <ActivityIndicator color="#FFFFFF" size="small" />
          ) : (
            <>
              <MaterialCommunityIcons name="check-circle-outline" size={20} color="#FFFFFF" />
              <Text style={styles.submitBtnText}>Register Party</Text>
            </>
          )}
        </TouchableOpacity>

        <View style={{ height: 30 }} />
      </KeyboardAwareScreen>

      {/* Location Picker Modal */}
      <Modal visible={locationPicker !== null} animationType="slide" transparent>
        <View style={styles.modalOverlay}>
          <View style={styles.modalSheet}>
            <ScreenHeader
              title={
                locationPicker === "state"
                  ? "Select State"
                  : `Select District (${selectedState})`
              }
              onBack={() => setLocationPicker(null)}
              bgColor="#7367F0"
            />
            {/* Search Filter Box */}
            <View style={styles.searchBoxWrap}>
              <MaterialCommunityIcons name="magnify" size={20} color="#94A3B8" />
              <TextInput
                style={styles.searchInput}
                placeholder={locationPicker === "state" ? "Search State / UT..." : "Search District..."}
                placeholderTextColor="#94A3B8"
                value={locationSearch}
                onChangeText={setLocationSearch}
                autoCorrect={false}
              />
              {locationSearch.length > 0 ? (
                <TouchableOpacity onPress={() => setLocationSearch("")}>
                  <MaterialCommunityIcons name="close-circle" size={18} color="#94A3B8" />
                </TouchableOpacity>
              ) : null}
            </View>

            <ScrollView
              contentContainerStyle={[
                styles.modalList,
                { paddingBottom: Math.max(insets.bottom, 14) + 24 },
              ]}
              keyboardShouldPersistTaps="handled"
            >
              {locationPicker === "state" ? (
                indiaLocationsData
                  .map((item) => item.state)
                  .filter((s) => s.toLowerCase().includes(locationSearch.trim().toLowerCase()))
                  .map((stateName) => {
                    const isSelected = selectedState === stateName;
                    return (
                      <TouchableOpacity
                        key={stateName}
                        style={[styles.modalItem, isSelected && styles.modalItemSelected]}
                        onPress={() => {
                          setSelectedState(stateName);
                          setSelectedDistrictName("");
                          setLocationPicker(null);
                        }}
                      >
                        <Text style={[styles.modalItemText, isSelected && styles.modalItemTextActive]}>
                          {stateName}
                        </Text>
                        {isSelected ? (
                          <MaterialCommunityIcons name="check-circle" size={20} color="#7367F0" />
                        ) : null}
                      </TouchableOpacity>
                    );
                  })
              ) : (
                (indiaLocationsData.find((item) => item.state === selectedState)?.districts || [])
                  .filter((d) => d.toLowerCase().includes(locationSearch.trim().toLowerCase()))
                  .map((distName) => {
                    const isSelected = selectedDistrictName === distName;
                    return (
                      <TouchableOpacity
                        key={distName}
                        style={[styles.modalItem, isSelected && styles.modalItemSelected]}
                        onPress={() => {
                          setSelectedDistrictName(distName);
                          setLocationPicker(null);
                        }}
                      >
                        <Text style={[styles.modalItemText, isSelected && styles.modalItemTextActive]}>
                          {distName}
                        </Text>
                        {isSelected ? (
                          <MaterialCommunityIcons name="check-circle" size={20} color="#7367F0" />
                        ) : null}
                      </TouchableOpacity>
                    );
                  })
              )}
            </ScrollView>
            <TouchableOpacity
              style={[
                styles.modalCancelBtn,
                { marginBottom: Math.max(insets.bottom, 14) + 14 },
              ]}
              onPress={() => setLocationPicker(null)}
            >
              <Text style={styles.modalCancelText}>Cancel</Text>
            </TouchableOpacity>
          </View>
        </View>
      </Modal>
    </View>
  );
}

const PURPLE = "#7367F0";

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: "#F4F4F8",
  },
  scrollContent: {
    padding: 14,
    paddingBottom: 40,
  },

  // Info Banner
  infoBanner: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#EDE9FE",
    borderWidth: 1,
    borderColor: "#DDD6FE",
    borderRadius: 14,
    padding: 14,
    marginBottom: 14,
  },
  infoIconWrap: {
    width: 38,
    height: 38,
    borderRadius: 10,
    backgroundColor: "#FFFFFF",
    alignItems: "center",
    justifyContent: "center",
    marginRight: 12,
  },
  infoContent: {
    flex: 1,
  },
  infoTitle: {
    fontSize: 14,
    fontWeight: "800",
    color: "#5B21B6",
    marginBottom: 2,
  },
  infoText: {
    fontSize: 12,
    color: "#6D28D9",
    lineHeight: 16,
    fontWeight: "500",
  },

  // Card Structure
  card: {
    backgroundColor: "#FFFFFF",
    borderRadius: 16,
    padding: 16,
    marginBottom: 14,
    shadowColor: "#000",
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.05,
    shadowRadius: 8,
    elevation: 3,
  },
  cardHeader: {
    flexDirection: "row",
    alignItems: "center",
    marginBottom: 14,
    paddingBottom: 10,
    borderBottomWidth: 1,
    borderBottomColor: "#F3F4F6",
  },
  headerIconWrap: {
    width: 36,
    height: 36,
    borderRadius: 10,
    alignItems: "center",
    justifyContent: "center",
  },
  cardTitle: {
    fontSize: 15,
    fontWeight: "800",
    color: "#1F2937",
  },
  cardSubtitle: {
    fontSize: 11,
    color: "#6B7280",
    marginTop: 2,
  },

  // Form Fields
  field: {
    marginBottom: 14,
  },
  label: {
    fontSize: 12,
    fontWeight: "700",
    color: "#374151",
    marginBottom: 6,
  },
  required: {
    color: "#EF4444",
  },
  row: {
    flexDirection: "row",
  },

  // Inputs with Icons
  inputWrap: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#F9FAFB",
    borderWidth: 1.5,
    borderColor: "#E5E7EB",
    borderRadius: 12,
    paddingHorizontal: 12,
    height: 48,
  },
  inputWrapFocused: {
    borderColor: PURPLE,
    backgroundColor: "#FFFFFF",
  },
  inputWithIcon: {
    flex: 1,
    fontSize: 14,
    fontWeight: "600",
    color: "#1F2937",
    marginLeft: 8,
    paddingVertical: 8,
  },

  // Textarea
  textareaWrap: {
    flexDirection: "row",
    backgroundColor: "#F9FAFB",
    borderWidth: 1.5,
    borderColor: "#E5E7EB",
    borderRadius: 12,
    paddingHorizontal: 12,
    paddingVertical: 10,
    minHeight: 76,
  },
  textareaWithIcon: {
    flex: 1,
    fontSize: 14,
    fontWeight: "600",
    color: "#1F2937",
    marginLeft: 8,
    textAlignVertical: "top",
    paddingTop: 0,
    paddingBottom: 0,
  },

  // Chips Grid
  typeGrid: {
    flexDirection: "row",
    flexWrap: "wrap",
    gap: 8,
    marginTop: 2,
  },
  typeChip: {
    flexDirection: "row",
    alignItems: "center",
    gap: 6,
    paddingHorizontal: 13,
    paddingVertical: 9,
    borderRadius: 10,
    backgroundColor: "#F3F4F6",
    borderWidth: 1.5,
    borderColor: "#E5E7EB",
  },
  typeChipActive: {
    backgroundColor: "#EDE9FE",
    borderColor: PURPLE,
  },
  typeChipText: {
    fontSize: 13,
    fontWeight: "700",
    color: "#4B5563",
  },
  typeChipTextActive: {
    color: PURPLE,
    fontWeight: "800",
  },

  // Submit Button
  submitBtn: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    gap: 8,
    backgroundColor: PURPLE,
    paddingVertical: 15,
    borderRadius: 14,
    marginTop: 6,
    elevation: 4,
    shadowColor: PURPLE,
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 8,
  },
  submitBtnDisabled: {
    opacity: 0.7,
  },
  submitBtnText: {
    color: "#FFFFFF",
    fontSize: 16,
    fontWeight: "800",
  },

  // Selectors
  selectWrap: {
    flexDirection: "row",
    alignItems: "center",
    gap: 8,
    backgroundColor: "#F9FAFB",
    borderWidth: 1.5,
    borderColor: "#E5E7EB",
    borderRadius: 12,
    paddingHorizontal: 10,
    paddingVertical: 12,
  },
  selectValueText: {
    flex: 1,
    fontSize: 13,
    fontWeight: "700",
    color: "#1F2937",
  },
  selectPlaceholderText: {
    color: "#9CA3AF",
    fontWeight: "500",
  },

  // Modal Sheet
  modalOverlay: {
    flex: 1,
    backgroundColor: "rgba(15, 23, 42, 0.5)",
    justifyContent: "flex-end",
  },
  modalSheet: {
    backgroundColor: "#fff",
    borderTopLeftRadius: 24,
    borderTopRightRadius: 24,
    maxHeight: "80%",
    overflow: "hidden",
  },
  modalList: {
    padding: 16,
  },
  modalItem: {
    paddingVertical: 14,
    paddingHorizontal: 16,
    borderBottomWidth: 1,
    borderBottomColor: "#F1F5F9",
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
  },
  modalItemText: {
    fontSize: 15,
    fontWeight: "700",
    color: "#1E293B",
  },
  modalItemSub: {
    fontSize: 12,
    color: "#64748B",
    fontWeight: "600",
  },
  modalCancelBtn: {
    marginHorizontal: 16,
    paddingVertical: 12,
    borderRadius: 12,
    backgroundColor: "#F1F5F9",
    alignItems: "center",
  },
  modalCancelText: {
    fontSize: 14,
    fontWeight: "700",
    color: "#475569",
  },
  searchBoxWrap: {
    paddingHorizontal: 16,
    paddingVertical: 10,
    borderBottomWidth: 1,
    borderBottomColor: "#F1F5F9",
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#F8FAFC",
  },
  searchInput: {
    flex: 1,
    marginLeft: 8,
    fontSize: 14,
    color: "#1E293B",
  },
  modalItemSelected: {
    backgroundColor: "#F5F3FF",
  },
  modalItemTextActive: {
    color: "#7367F0",
  },
});
