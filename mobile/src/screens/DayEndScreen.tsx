import { MaterialCommunityIcons, MaterialIcons } from "@expo/vector-icons";
import { useNavigation } from "@react-navigation/native";
import type { NativeStackNavigationProp } from "@react-navigation/native-stack";
import * as ImagePicker from "expo-image-picker";
import { useState } from "react";
import {
  ActivityIndicator,
  Alert,
  Image,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from "react-native";
import KeyboardAwareScreen from "../components/KeyboardAwareScreen";
import ScreenHeader from "../components/ScreenHeader";
import { optimizeImageForUpload } from "../utils/imageOptimizer";

import { submitDayEnd } from "../api/day";
import type { AppStackParamList } from "../navigation/RootNavigator";

type Nav = NativeStackNavigationProp<AppStackParamList, "DayEnd">;

export default function DayEndScreen() {
  const navigation = useNavigation<Nav>();

  const [closingKm, setClosingKm] = useState("");
  const [remarks, setRemarks] = useState("");
  const [kmPhotoUri, setKmPhotoUri] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  // ── Camera / Gallery ──────────────────────────────────────────────────────
  async function showPhotoOptions() {
    Alert.alert("Upload Meter Photo", "Choose option", [
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
      setKmPhotoUri(optimized);
    }
  }

  async function pickFromGallery() {
    const result = await ImagePicker.launchImageLibraryAsync({ quality: 0.7 });
    if (!result.canceled && result.assets[0]) {
      const optimized = await optimizeImageForUpload(result.assets[0].uri);
      setKmPhotoUri(optimized);
    }
  }

  // ── Submit ────────────────────────────────────────────────────────────────
  async function handleSubmit() {
    if (!closingKm.trim()) {
      Alert.alert("Required", "Closing KM reading is required.");
      return;
    }

    const kmVal = parseFloat(closingKm.trim());
    if (isNaN(kmVal) || kmVal < 0) {
      Alert.alert("Invalid KM", "Please enter a valid positive number for closing KM.");
      return;
    }

    setSubmitting(true);
    try {
      await submitDayEnd({
        closing_km: closingKm,
        remarks: remarks || undefined,
        closing_km_image_uri: kmPhotoUri ?? undefined,
      });

      Alert.alert("🎉 Day Ended!", "Your day has been successfully ended.", [
        { text: "OK", onPress: () => navigation.goBack() },
      ]);
    } catch (err: any) {
      Alert.alert(
        "Error",
        err?.message ?? "Failed to end day. Please try again.",
      );
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <View style={{ flex: 1, backgroundColor: "#F4F4F8" }}>
      <ScreenHeader title="Day End" onBack={() => navigation.goBack()} bgColor="#F59E0B" />

      <KeyboardAwareScreen contentStyle={styles.scrollContent}>
        <View style={styles.card}>
          {/* Closing KM */}
          <FieldInput
            label="Closing K.M."
            value={closingKm}
            onChangeText={(v) => setClosingKm(v.replace(/[^0-9.]/g, ""))}
            keyboardType="numeric"
            placeholder="e.g. 12450"
          />

          {/* Remarks */}
          <FieldInput
            label="Remarks (Optional)"
            value={remarks}
            onChangeText={setRemarks}
            placeholder="Any summary of the day..."
            multiline
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
                  color="#F59E0B"
                />
                <Text style={[styles.cameraHint, { color: "#F59E0B" }]}>
                  Tap to take Closing KM photo
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
              <Text style={[styles.actionText, { color: "#F59E0B" }]}>
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
                <Text style={styles.actionText}>End Day Now</Text>
              )}
            </TouchableOpacity>
          </View>
        </View>
      </KeyboardAwareScreen>
    </View>
  );
}

// ─── Sub-components ────────────────────────────────────────────────────────────

function FieldInput({
  label,
  value,
  onChangeText,
  keyboardType,
  placeholder,
  multiline,
}: {
  label: string;
  value: string;
  onChangeText: (v: string) => void;
  keyboardType?: "default" | "numeric";
  placeholder?: string;
  multiline?: boolean;
}) {
  return (
    <View style={styles.field}>
      <Text style={styles.fieldLabel}>{label}</Text>
      <TextInput
        style={[
          styles.input,
          multiline && { height: 80, textAlignVertical: "top" },
        ]}
        value={value}
        onChangeText={onChangeText}
        keyboardType={keyboardType}
        placeholder={placeholder}
        placeholderTextColor="#fcd34d"
        multiline={multiline}
      />
    </View>
  );
}

// ─── Styles ────────────────────────────────────────────────────────────────────
const ORANGE = "#F59E0B";

const styles = StyleSheet.create({
  header: {
    backgroundColor: ORANGE,
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

  scrollContent: { padding: 14, paddingBottom: 40 },
  card: {
    backgroundColor: "#fff",
    borderRadius: 18,
    padding: 14,
    elevation: 6,
  },

  field: {
    borderWidth: 1.5,
    borderColor: ORANGE,
    borderRadius: 12,
    paddingHorizontal: 14,
    paddingTop: 10,
    paddingBottom: 10,
    marginBottom: 12,
  },
  fieldLabel: {
    color: ORANGE,
    fontWeight: "800",
    fontSize: 12,
    marginBottom: 6,
  },
  input: { height: 40, fontSize: 16, fontWeight: "700", color: ORANGE },

  // Camera
  cameraWrap: {
    borderWidth: 1.5,
    borderColor: ORANGE,
    borderRadius: 14,
    borderStyle: "dashed",
    marginBottom: 16,
    overflow: "hidden",
  },
  cameraPlaceholder: { alignItems: "center", paddingVertical: 24 },
  cameraHint: { fontSize: 13, fontWeight: "600", marginTop: 8 },
  photoPreviewWrap: { position: "relative" },
  photoPreview: { width: "100%", height: 160 },
  retakeBtn: {
    position: "absolute",
    bottom: 8,
    right: 8,
    backgroundColor: "rgba(245,158,11,0.85)",
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
    backgroundColor: "#FEF3C7",
    borderWidth: 1.5,
    borderColor: ORANGE,
  },
  startBtn: { backgroundColor: ORANGE },
  btnDisabled: { opacity: 0.6 },
  actionText: { color: "#fff", fontWeight: "900", fontSize: 16 },
});
