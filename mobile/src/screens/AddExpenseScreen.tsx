import { MaterialCommunityIcons, MaterialIcons } from "@expo/vector-icons";
import { useNavigation, useFocusEffect } from "@react-navigation/native";
import type { NativeStackNavigationProp } from "@react-navigation/native-stack";
import * as ImagePicker from "expo-image-picker";
import { useCallback, useState } from "react";
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
import { SafeAreaView } from "react-native-safe-area-context";

import { submitExpense, getExpenseCategories, type ExpenseCategory } from "../api/expense";
import type { AppStackParamList } from "../navigation/RootNavigator";

type Nav = NativeStackNavigationProp<AppStackParamList, "AddExpense">;

export default function AddExpenseScreen() {
  const navigation = useNavigation<Nav>();

  const [categories, setCategories] = useState<ExpenseCategory[]>([]);
  const [selectedCategory, setSelectedCategory] = useState<number | null>(null);
  const [amount, setAmount] = useState("");
  const [photoUri, setPhotoUri] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  useFocusEffect(
    useCallback(() => {
      getExpenseCategories().then(res => {
        if(res.success) {
          setCategories(res.data);
        }
      });
    }, [])
  );

  async function openCamera() {
    const { status } = await ImagePicker.requestCameraPermissionsAsync();
    if (status !== "granted") {
      Alert.alert("Permission needed", "Camera access is required for expense proof.");
      return;
    }
    const result = await ImagePicker.launchCameraAsync({
      mediaTypes: ImagePicker.MediaTypeOptions.Images,
      quality: 0.7,
      allowsEditing: false,
    });
    if (!result.canceled && result.assets.length > 0) {
      setPhotoUri(result.assets[0].uri);
    }
  }

  async function handleSubmit() {
    if (!selectedCategory) return Alert.alert("Missing", "Select a category.");
    if (!amount || Number(amount) <= 0) return Alert.alert("Missing", "Enter valid amount.");
    if (!photoUri) return Alert.alert("Missing", "Photo proof is mandatory for manual expenses.");

    setSubmitting(true);
    try {
      await submitExpense(selectedCategory, amount, photoUri);
      Alert.alert("Success", "Expense submitted for approval.", [
        { text: "OK", onPress: () => navigation.goBack() }
      ]);
    } catch (err: any) {
      Alert.alert("Error", err?.message || "Failed to submit.");
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <SafeAreaView style={styles.safe}>
      <View style={styles.header}>
        <TouchableOpacity style={styles.headerIconBtn} onPress={() => navigation.goBack()}>
          <MaterialIcons name="arrow-back-ios-new" size={20} color="#fff" />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Add Expense</Text>
        <View style={{ width: 40 }} />
      </View>

      <ScrollView contentContainerStyle={styles.scrollContent} keyboardShouldPersistTaps="handled">
        <View style={styles.card}>
          <Text style={styles.fieldLabel}>Category</Text>
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

          <Text style={[styles.fieldLabel, { marginTop: 16 }]}>Amount (₹)</Text>
          <TextInput
            style={styles.input}
            value={amount}
            onChangeText={(v) => setAmount(v.replace(/[^0-9.]/g, ""))}
            keyboardType="numeric"
            placeholder="e.g. 150"
            placeholderTextColor="#C7D2FE"
          />

          <Text style={[styles.fieldLabel, { marginTop: 16 }]}>Bill Photo (Mandatory)</Text>
          <TouchableOpacity style={styles.cameraWrap} activeOpacity={0.85} onPress={openCamera}>
            {photoUri ? (
              <View style={styles.photoPreviewWrap}>
                <Image source={{ uri: photoUri }} style={styles.photoPreview} />
                <View style={styles.retakeBtn}>
                  <Text style={styles.retakeText}>Retake</Text>
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
            {submitting ? <ActivityIndicator color="#fff" /> : <Text style={styles.startBtnText}>Submit Expense</Text>}
          </TouchableOpacity>
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: "#F3F4F6" },
  header: {
    backgroundColor: "#7367F0",
    paddingHorizontal: 14,
    paddingVertical: 14,
    flexDirection: "row",
    alignItems: "center",
  },
  headerIconBtn: { width: 40, height: 40, borderRadius: 14, alignItems: "center", justifyContent: "center" },
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
});
