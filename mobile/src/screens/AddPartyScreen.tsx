import { MaterialCommunityIcons } from "@expo/vector-icons";
import { useNavigation } from "@react-navigation/native";
import type { NativeStackNavigationProp } from "@react-navigation/native-stack";
import { useRef, useState } from "react";
import {
  ActivityIndicator,
  Alert,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from "react-native";
import KeyboardAwareScreen from "../components/KeyboardAwareScreen";
import ScreenHeader from "../components/ScreenHeader";

import { submitParty } from "../api/party";
import type { RootStackParamList } from "../navigation/RootNavigator";

type Nav = NativeStackNavigationProp<RootStackParamList, "AddParty">;

const PARTY_TYPES = ["Retailer", "Distributor", "Wholesaler", "Dealer"];

export default function AddPartyScreen() {
  const navigation = useNavigation<Nav>();

  // Input refs for smooth chained navigation
  const mobileRef = useRef<TextInput>(null);
  const ownerNameRef = useRef<TextInput>(null);
  const addressRef = useRef<TextInput>(null);
  const cityRef = useRef<TextInput>(null);
  const stateRef = useRef<TextInput>(null);
  const pincodeRef = useRef<TextInput>(null);
  const emailRef = useRef<TextInput>(null);
  const gstRef = useRef<TextInput>(null);

  // Required
  const [name, setName] = useState("");
  const [mobile, setMobile] = useState("");
  const [partyType, setPartyType] = useState("Retailer");

  // Optional
  const [ownerName, setOwnerName] = useState("");
  const [address, setAddress] = useState("");
  const [city, setCity] = useState("");
  const [state, setState] = useState("");
  const [pincode, setPincode] = useState("");
  const [email, setEmail] = useState("");
  const [gstNumber, setGstNumber] = useState("");

  const [submitting, setSubmitting] = useState(false);

  async function handleSubmit() {
    if (!name.trim()) { Alert.alert("Required", "Party name is required."); return; }
    if (!mobile.trim()) { Alert.alert("Required", "Mobile number is required."); return; }
    if (mobile.trim().length < 10) { Alert.alert("Invalid", "Enter a valid 10-digit mobile number."); return; }
    if (pincode.trim() && pincode.trim().length !== 6) {
      Alert.alert("Invalid Pincode", "Pincode must be exactly 6 digits.");
      return;
    }
    if (gstNumber.trim()) {
      const gstRegex = /^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/;
      if (!gstRegex.test(gstNumber.trim().toUpperCase())) {
        Alert.alert("Invalid GST", "Please enter a valid 15-character GST number (e.g. 22AAAAA0000A1Z5).");
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
        address: address.trim() || undefined,
        city: city.trim() || undefined,
        state: state.trim() || undefined,
        pincode: pincode.trim() || undefined,
        email: email.trim() || undefined,
        gst_number: gstNumber.trim() || undefined,
      });

      if (res.success) {
        Alert.alert(
          "Submitted! ✅",
          "Party submitted for admin approval. It will appear in your list once approved.",
          [{ text: "OK", onPress: () => navigation.goBack() }]
        );
      } else {
        Alert.alert("Error", res.message || "Failed to submit party.");
      }
    } catch {
      Alert.alert("Error", "Network error. Please try again.");
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <View style={{ flex: 1, backgroundColor: "#F4F4F8" }}>
      <ScreenHeader title="Add New Party" onBack={() => navigation.goBack()} />

      <KeyboardAwareScreen contentStyle={{ padding: 16, paddingBottom: 60 }}>
        {/* Notice */}
        <View style={styles.notice}>
          <MaterialCommunityIcons name="information-outline" size={16} color="#7367F0" />
          <Text style={styles.noticeText}>
            Party will be visible after admin approval.
          </Text>
        </View>

        {/* ── Required Fields ─────────────────────────────────────── */}
        <Text style={styles.sectionLabel}>BASIC INFO <Text style={styles.required}>*</Text></Text>

        <View style={styles.field}>
          <Text style={styles.label}>Party / Shop Name <Text style={styles.required}>*</Text></Text>
          <TextInput
            style={styles.input}
            placeholder="e.g. Sharma General Store"
            placeholderTextColor="#9ca3af"
            value={name}
            onChangeText={setName}
            returnKeyType="next"
            onSubmitEditing={() => mobileRef.current?.focus()}
          />
        </View>

        <View style={styles.field}>
          <Text style={styles.label}>Mobile Number <Text style={styles.required}>*</Text></Text>
          <TextInput
            ref={mobileRef}
            style={styles.input}
            placeholder="10-digit mobile"
            placeholderTextColor="#9ca3af"
            value={mobile}
            onChangeText={setMobile}
            keyboardType="phone-pad"
            maxLength={10}
            returnKeyType="next"
            onSubmitEditing={() => ownerNameRef.current?.focus()}
          />
        </View>

        {/* Party Type Picker */}
        <View style={styles.field}>
          <Text style={styles.label}>Party Type</Text>
          <View style={styles.typeRow}>
            {PARTY_TYPES.map((t) => (
              <TouchableOpacity
                key={t}
                style={[styles.typeChip, partyType === t && styles.typeChipActive]}
                onPress={() => setPartyType(t)}
              >
                <Text style={[styles.typeChipText, partyType === t && styles.typeChipTextActive]}>
                  {t}
                </Text>
              </TouchableOpacity>
            ))}
          </View>
        </View>

        {/* ── Optional Fields ─────────────────────────────────────── */}
        <Text style={[styles.sectionLabel, { marginTop: 16 }]}>CONTACT & ADDRESS</Text>

        <View style={styles.field}>
          <Text style={styles.label}>Owner Name</Text>
          <TextInput
            ref={ownerNameRef}
            style={styles.input}
            placeholder="Owner / Contact person"
            placeholderTextColor="#9ca3af"
            value={ownerName}
            onChangeText={setOwnerName}
            returnKeyType="next"
            onSubmitEditing={() => addressRef.current?.focus()}
          />
        </View>

        <View style={styles.field}>
          <Text style={styles.label}>Address</Text>
          <TextInput
            ref={addressRef}
            style={[styles.input, styles.textarea]}
            placeholder="Shop address"
            placeholderTextColor="#9ca3af"
            value={address}
            onChangeText={setAddress}
            multiline
            numberOfLines={3}
          />
        </View>

        <View style={styles.row}>
          <View style={[styles.field, { flex: 1, marginRight: 8 }]}>
            <Text style={styles.label}>City</Text>
            <TextInput
              ref={cityRef}
              style={styles.input}
              placeholder="City"
              placeholderTextColor="#9ca3af"
              value={city}
              onChangeText={setCity}
              returnKeyType="next"
              onSubmitEditing={() => stateRef.current?.focus()}
            />
          </View>
          <View style={[styles.field, { flex: 1 }]}>
            <Text style={styles.label}>State</Text>
            <TextInput
              ref={stateRef}
              style={styles.input}
              placeholder="State"
              placeholderTextColor="#9ca3af"
              value={state}
              onChangeText={setState}
              returnKeyType="next"
              onSubmitEditing={() => pincodeRef.current?.focus()}
            />
          </View>
        </View>

        <View style={styles.field}>
          <Text style={styles.label}>Pincode</Text>
          <TextInput
            ref={pincodeRef}
            style={styles.input}
            placeholder="6-digit pincode"
            placeholderTextColor="#9ca3af"
            value={pincode}
            onChangeText={setPincode}
            keyboardType="number-pad"
            maxLength={6}
            returnKeyType="next"
            onSubmitEditing={() => emailRef.current?.focus()}
          />
        </View>

        <Text style={[styles.sectionLabel, { marginTop: 16 }]}>BUSINESS DETAILS</Text>

        <View style={styles.field}>
          <Text style={styles.label}>Email</Text>
          <TextInput
            ref={emailRef}
            style={styles.input}
            placeholder="email@example.com"
            placeholderTextColor="#9ca3af"
            value={email}
            onChangeText={setEmail}
            keyboardType="email-address"
            autoCapitalize="none"
            returnKeyType="next"
            onSubmitEditing={() => gstRef.current?.focus()}
          />
        </View>

        <View style={styles.field}>
          <Text style={styles.label}>GST Number</Text>
          <TextInput
            ref={gstRef}
            style={styles.input}
            placeholder="GST number (optional)"
            placeholderTextColor="#9ca3af"
            value={gstNumber}
            onChangeText={setGstNumber}
            autoCapitalize="characters"
            maxLength={15}
            returnKeyType="done"
            onSubmitEditing={handleSubmit}
          />
        </View>

        <View style={{ height: 20 }} />

        {/* Submit Button Inside Form */}
        <TouchableOpacity
          style={[styles.submitBtn, submitting && styles.submitBtnDisabled]}
          onPress={handleSubmit}
          disabled={submitting}
          activeOpacity={0.85}
        >
          {submitting ? (
            <ActivityIndicator color="#fff" />
          ) : (
            <>
              <MaterialCommunityIcons name="send-outline" size={20} color="#fff" style={{ marginRight: 8 }} />
              <Text style={styles.submitText}>Submit for Approval</Text>
            </>
          )}
        </TouchableOpacity>

        <View style={{ height: 20 }} />
      </KeyboardAwareScreen>
    </View>
  );
}

const PURPLE = "#7367F0";

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: "#F4F4F8" },

  header: {
    backgroundColor: PURPLE,
    flexDirection: "row",
    alignItems: "center",
    paddingHorizontal: 14,
    paddingVertical: 14,
  },
  backBtn: { width: 40, height: 40, justifyContent: "center", alignItems: "flex-start" },
  headerTitle: {
    flex: 1,
    color: "#fff",
    fontSize: 18,
    fontWeight: "800",
    textAlign: "center",
  },

  notice: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#EDE9FE",
    paddingHorizontal: 14,
    paddingVertical: 10,
    gap: 8,
  },
  noticeText: {
    flex: 1,
    fontSize: 12,
    color: PURPLE,
    fontWeight: "600",
  },

  form: { flexGrow: 1, padding: 16, paddingBottom: 20 },

  sectionLabel: {
    fontSize: 11,
    fontWeight: "800",
    color: "#9ca3af",
    letterSpacing: 0.8,
    marginBottom: 10,
  },
  required: { color: "#ef4444" },

  field: { marginBottom: 14 },
  label: { fontSize: 12, fontWeight: "700", color: "#374151", marginBottom: 6 },
  input: {
    backgroundColor: "#fff",
    borderRadius: 10,
    borderWidth: 1,
    borderColor: "#e5e7eb",
    paddingHorizontal: 14,
    paddingVertical: 11,
    fontSize: 14,
    color: "#111827",
  },
  textarea: { height: 80, textAlignVertical: "top" },

  row: { flexDirection: "row" },

  typeRow: { flexDirection: "row", flexWrap: "wrap", gap: 8 },
  typeChip: {
    paddingHorizontal: 14,
    paddingVertical: 7,
    borderRadius: 20,
    borderWidth: 1.5,
    borderColor: "#e5e7eb",
    backgroundColor: "#fff",
  },
  typeChipActive: { borderColor: PURPLE, backgroundColor: PURPLE },
  typeChipText: { fontSize: 12, fontWeight: "700", color: "#6b7280" },
  typeChipTextActive: { color: "#fff" },

  footer: {
    padding: 16,
    paddingBottom: 24,
    backgroundColor: "#fff",
    borderTopWidth: 1,
    borderTopColor: "#e5e7eb",
  },
  submitBtn: {
    backgroundColor: PURPLE,
    borderRadius: 12,
    paddingVertical: 14,
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    shadowColor: PURPLE,
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.3,
    shadowRadius: 8,
    elevation: 6,
  },
  submitBtnDisabled: { opacity: 0.7 },
  submitText: { color: "#fff", fontSize: 15, fontWeight: "800" },
});
