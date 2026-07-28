import { useNavigation, useRoute } from "@react-navigation/native";
import type { NativeStackNavigationProp } from "@react-navigation/native-stack";
import { useState } from "react";
import {
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from "react-native";

import type { RootStackParamList } from "../navigation/RootNavigator";

type OTPScreenNavigationProp = NativeStackNavigationProp<
  RootStackParamList,
  "Dashboard"
>;

type OTPScreenRouteProp = {
  params: {
    phone: string;
  };
};

export default function OTPScreen() {
  const navigation = useNavigation<OTPScreenNavigationProp>();
  const route = useRoute();
  const phone = (route.params as OTPScreenRouteProp["params"])?.phone ?? "";
  const [otp, setOtp] = useState("");

  const isOtpValid = otp === "123456";

  return (
    <View style={styles.container}>
      <View style={styles.card}>
        <Text style={styles.title}>Enter OTP</Text>
        <Text style={styles.subtitle}>We sent a 6-digit code to</Text>
        <Text style={styles.phone}>{phone}</Text>
        <TextInput
          style={styles.input}
          keyboardType="numeric"
          placeholder="Enter 6-digit OTP"
          value={otp}
          onChangeText={(value) => setOtp(value.replace(/[^0-9]/g, ""))}
          maxLength={6}
        />
        <TouchableOpacity
          style={[styles.button, !isOtpValid && styles.buttonDisabled]}
          onPress={() => navigation.replace("Dashboard")}
          disabled={!isOtpValid}
          activeOpacity={0.9}
        >
          <Text style={styles.buttonText}>Verify OTP</Text>
        </TouchableOpacity>
        <Text style={styles.hint}>Demo OTP: 123456</Text>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: "#EEF2FF",
    padding: 24,
    justifyContent: "center",
  },
  card: {
    backgroundColor: "#ffffff",
    borderRadius: 24,
    padding: 28,
    shadowColor: "#000",
    shadowOpacity: 0.08,
    shadowRadius: 16,
    elevation: 8,
  },
  title: {
    fontSize: 28,
    fontWeight: "700",
    color: "#27272a",
    marginBottom: 10,
    textAlign: "center",
  },
  subtitle: {
    fontSize: 15,
    color: "#6b7280",
    marginBottom: 4,
    textAlign: "center",
  },
  phone: {
    fontSize: 17,
    fontWeight: "600",
    color: "#111827",
    marginBottom: 24,
    textAlign: "center",
  },
  input: {
    borderWidth: 1,
    borderColor: "#d1d5db",
    borderRadius: 16,
    padding: 14,
    fontSize: 16,
    marginBottom: 24,
    backgroundColor: "#f9fafb",
    textAlign: "center",
    letterSpacing: 6,
  },
  button: {
    backgroundColor: "#7367F0",
    borderRadius: 16,
    paddingVertical: 14,
    alignItems: "center",
    justifyContent: "center",
  },
  buttonDisabled: {
    backgroundColor: "#C7D2FE",
  },
  buttonText: {
    color: "#FFFFFF",
    fontWeight: "800",
    fontSize: 16,
  },
  hint: {
    marginTop: 14,
    textAlign: "center",
    color: "#6b7280",
    fontSize: 13,
  },
});
