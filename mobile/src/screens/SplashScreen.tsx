/**
 * Splash Screen
 *
 * Shows the app logo while AuthContext checks for a saved token.
 * - If a valid token exists  → goes directly to Dashboard
 * - If no token / expired   → goes to Login
 *
 * This screen should be shown only while isLoading = true.
 * Navigation is driven by RootNavigator based on auth state.
 */

import { MaterialIcons } from "@expo/vector-icons";
import { StyleSheet, Text, View, ActivityIndicator } from "react-native";

export default function SplashScreen() {
  return (
    <View style={styles.container}>
      <View style={styles.logoWrap}>
        <MaterialIcons name="terrain" size={54} color="#FFFFFF" />
      </View>
      <Text style={styles.title}>AMPL SOLUTION</Text>
      <Text style={styles.subtitle}>Fieldwork App</Text>
      <ActivityIndicator
        color="rgba(255,255,255,0.7)"
        size="small"
        style={styles.spinner}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    alignItems: "center",
    justifyContent: "center",
    backgroundColor: "#7367F0",
  },
  logoWrap: {
    width: 110,
    height: 110,
    borderRadius: 28,
    backgroundColor: "rgba(255,255,255,0.18)",
    alignItems: "center",
    justifyContent: "center",
    marginBottom: 18,
  },
  title: {
    color: "#FFFFFF",
    fontSize: 22,
    fontWeight: "800",
    letterSpacing: 0.8,
  },
  subtitle: {
    marginTop: 6,
    color: "rgba(255,255,255,0.86)",
    fontSize: 13,
    fontWeight: "600",
  },
  spinner: {
    marginTop: 36,
  },
});
