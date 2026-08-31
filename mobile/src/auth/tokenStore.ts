/**
 * Token Storage
 *
 * Persists the Sanctum Bearer token securely using Expo SecureStore
 * (Keychain on iOS, EncryptedSharedPreferences on Android).
 * Includes an AsyncStorage fallback for web environments.
 *
 * Usage:
 *   setAuthToken(token)   – call after successful login
 *   getAuthToken()        – call before every API request
 *   clearAuthToken()      – call on logout
 */

import AsyncStorage from "@react-native-async-storage/async-storage";
import * as SecureStore from "expo-secure-store";
import { Platform } from "react-native";

const TOKEN_KEY = "fieldwork_auth_token";

/** Retrieve the saved token (null if not logged in). */
export async function getAuthToken(): Promise<string | null> {
  try {
    if (Platform.OS === "web") {
      return await AsyncStorage.getItem(TOKEN_KEY);
    }
    return await SecureStore.getItemAsync(TOKEN_KEY);
  } catch {
    return null;
  }
}

/** Persist the token after a successful login. */
export async function setAuthToken(token: string): Promise<void> {
  try {
    if (Platform.OS === "web") {
      await AsyncStorage.setItem(TOKEN_KEY, token);
    } else {
      await SecureStore.setItemAsync(TOKEN_KEY, token);
    }
  } catch (err) {
    console.error("Failed to save auth token securely", err);
  }
}

/** Remove the token on logout. */
export async function clearAuthToken(): Promise<void> {
  try {
    if (Platform.OS === "web") {
      await AsyncStorage.removeItem(TOKEN_KEY);
    } else {
      await SecureStore.deleteItemAsync(TOKEN_KEY);
    }
  } catch (err) {
    console.error("Failed to delete auth token", err);
  }
}
