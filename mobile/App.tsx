import { NavigationContainer } from "@react-navigation/native";
import { StatusBar } from "expo-status-bar";

import { AuthProvider } from "./src/auth/AuthContext";
import RootNavigator from "./src/navigation/RootNavigator";

/**
 * App Entry Point
 *
 * Wraps the entire app with:
 *   <AuthProvider>   – provides global auth state (user, login, logout)
 *   <NavigationContainer> – required by React Navigation
 */
export default function App() {
  return (
    <AuthProvider>
      <NavigationContainer>
        <RootNavigator />
        <StatusBar style="auto" />
      </NavigationContainer>
    </AuthProvider>
  );
}
