/**
 * Token Storage
 *
 * Persists the Sanctum Bearer token using AsyncStorage so the user
 * stays logged in across app restarts.
 *
 * Usage:
 *   setAuthToken(token)   – call after successful login
 *   getAuthToken()        – call before every API request
 *   clearAuthToken()      – call on logout
 */

import AsyncStorage from "@react-native-async-storage/async-storage";

const TOKEN_KEY = "@fieldwork_auth_token";

/** Retrieve the saved token (null if not logged in). */
export async function getAuthToken(): Promise<string | null> {
  try {
    return await AsyncStorage.getItem(TOKEN_KEY);
  } catch {
    return null;
  }
}

/** Persist the token after a successful login. */
export async function setAuthToken(token: string): Promise<void> {
  await AsyncStorage.setItem(TOKEN_KEY, token);
}

/** Remove the token on logout. */
export async function clearAuthToken(): Promise<void> {
  await AsyncStorage.removeItem(TOKEN_KEY);
}
