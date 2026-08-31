import { MaterialIcons } from "@expo/vector-icons";
import { useState } from "react";
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

import { useAuth } from "../auth/AuthContext";

export default function LoginScreen() {
  const { login } = useAuth();

  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [showPass, setShowPass] = useState(false);
  const [loading, setLoading] = useState(false);

  const isValid = email.includes("@") && password.length >= 6;

  async function handleLogin() {
    if (!isValid || loading) return;
    setLoading(true);
    try {
      await login(email.trim().toLowerCase(), password);
    } catch (err: any) {
      Alert.alert(
        "Login Failed",
        err?.message ?? "Invalid email or password. Please try again.",
        [{ text: "OK" }],
      );
    } finally {
      setLoading(false);
    }
  }

  return (
    <SafeAreaView style={styles.safe}>
      <KeyboardAvoidingView
        style={styles.flex}
        behavior={Platform.OS === "ios" ? "padding" : "height"}
      >
        <View style={styles.header}>
          <Text style={styles.headerTitle}>AMPL SOLUTION</Text>
        </View>

        <ScrollView
          contentContainerStyle={styles.scrollContent}
          keyboardShouldPersistTaps="handled"
          showsVerticalScrollIndicator={false}
        >
          <View style={styles.card}>
            <View style={styles.heroWrap}>
              <MaterialIcons name="terrain" size={48} color="#7367F0" />
              <Text style={styles.cardTitle}>Welcome back 👋</Text>
              <Text style={styles.cardSubtitle}>Sign in to your account</Text>
            </View>

            <View style={styles.fieldGroup}>
              <Text style={styles.label}>Email Address</Text>
              <View style={styles.inputRow}>
                <MaterialIcons
                  name="email"
                  size={20}
                  color="#7367F0"
                  style={styles.inputIcon}
                />
                <TextInput
                  style={styles.input}
                  placeholder="you@company.com"
                  placeholderTextColor="#9ca3af"
                  keyboardType="email-address"
                  autoCapitalize="none"
                  autoCorrect={false}
                  value={email}
                  onChangeText={setEmail}
                  returnKeyType="next"
                />
              </View>
            </View>

            <View style={styles.fieldGroup}>
              <Text style={styles.label}>Password</Text>
              <View style={styles.inputRow}>
                <MaterialIcons
                  name="lock"
                  size={20}
                  color="#7367F0"
                  style={styles.inputIcon}
                />
                <TextInput
                  style={[styles.input, styles.inputFlex]}
                  placeholder="••••••••"
                  placeholderTextColor="#9ca3af"
                  secureTextEntry={!showPass}
                  value={password}
                  onChangeText={setPassword}
                  returnKeyType="done"
                  onSubmitEditing={handleLogin}
                />
                <TouchableOpacity
                  onPress={() => setShowPass((v) => !v)}
                  style={styles.eyeBtn}
                >
                  <MaterialIcons
                    name={showPass ? "visibility-off" : "visibility"}
                    size={20}
                    color="#9ca3af"
                  />
                </TouchableOpacity>
              </View>
            </View>

            <TouchableOpacity
              style={[styles.btn, (!isValid || loading) && styles.btnDisabled]}
              onPress={handleLogin}
              disabled={!isValid || loading}
              activeOpacity={0.85}
            >
              {loading ? (
                <ActivityIndicator color="#fff" />
              ) : (
                <>
                  <MaterialIcons name="login" size={20} color="#fff" />
                  <Text style={styles.btnText}>Sign In</Text>
                </>
              )}
            </TouchableOpacity>

            <Text style={styles.hint}>
              Contact your admin if you forgot your credentials.
            </Text>
          </View>
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

const PURPLE = "#7367F0";

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: "#F4F4F8" },
  flex: { flex: 1 },
  header: {
    backgroundColor: PURPLE,
    paddingHorizontal: 16,
    paddingVertical: 14,
    alignItems: "center",
    justifyContent: "center",
  },
  headerTitle: {
    color: "#fff",
    fontSize: 18,
    fontWeight: "800",
    letterSpacing: 0.5,
  },
  scrollContent: {
    flexGrow: 1,
    padding: 16,
    paddingBottom: 120,
    justifyContent: "center",
  },
  card: {
    backgroundColor: "#fff",
    borderRadius: 16,
    padding: 24,
    shadowColor: PURPLE,
    shadowOpacity: 0.08,
    shadowRadius: 8,
    elevation: 4,
    borderTopWidth: 4,
    borderTopColor: PURPLE,
  },
  heroWrap: {
    alignItems: "center",
    marginBottom: 24,
  },
  cardTitle: {
    fontSize: 22,
    fontWeight: "800",
    color: "#111827",
    marginTop: 12,
    marginBottom: 4,
  },
  cardSubtitle: {
    fontSize: 14,
    color: "#6b7280",
  },
  fieldGroup: { marginBottom: 16 },
  label: {
    fontSize: 13,
    fontWeight: "600",
    color: "#374151",
    marginBottom: 8,
  },
  inputRow: {
    flexDirection: "row",
    alignItems: "center",
    borderWidth: 1.5,
    borderColor: "#e5e7eb",
    borderRadius: 12,
    backgroundColor: "#f9fafb",
    paddingHorizontal: 14,
  },
  inputIcon: { marginRight: 10 },
  input: {
    flex: 1,
    paddingVertical: 12,
    fontSize: 15,
    color: "#111827",
  },
  inputFlex: { flex: 1 },
  eyeBtn: { padding: 4 },
  btn: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    gap: 8,
    backgroundColor: PURPLE,
    borderRadius: 12,
    paddingVertical: 14,
    marginTop: 12,
  },
  btnDisabled: {
    backgroundColor: "#c7d2fe",
  },
  btnText: {
    color: "#fff",
    fontSize: 16,
    fontWeight: "700",
  },
  hint: {
    marginTop: 20,
    textAlign: "center",
    color: "#9ca3af",
    fontSize: 12,
  },
});
