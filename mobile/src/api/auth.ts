/**
 * Auth API
 *
 * All calls to /api/auth/* endpoints live here.
 * Import these functions from the app — never call fetch() directly.
 *
 * Endpoints (see backend routes/api.php):
 *   POST /api/auth/login   → loginWithEmailPassword()
 *   GET  /api/auth/me      → fetchMe()
 *   POST /api/auth/logout  → logout()
 */

import { requestJson } from "./http";
import { clearAuthToken, setAuthToken } from "../auth/tokenStore";

// ─── Response types ────────────────────────────────────────────────────────────

export type ApiUser = {
  id: number;
  name: string;
  email: string;
  phone: string | null;
  employee_id: string | null;
  department: string | null;
  position: string | null;
  is_active: boolean;
  roles: string[];
  permissions: string[];
};

export type LoginResponse = {
  success: boolean;
  message: string;
  data: {
    token: string;
    user: ApiUser;
  };
};

export type MeResponse = {
  success: boolean;
  data: {
    user: ApiUser;
  };
};

// ─── API calls ─────────────────────────────────────────────────────────────────

/**
 * Login with email + password.
 * Saves the token to AsyncStorage automatically.
 *
 * @throws HttpError if credentials are wrong (status 401) or account inactive (403)
 */
export async function loginWithEmailPassword(
  email: string,
  password: string,
): Promise<LoginResponse> {
  const res = await requestJson<LoginResponse>("/auth/login", {
    method: "POST",
    body: { email, password },
  });

  // Persist token for future requests
  await setAuthToken(res.data.token);
  return res;
}

/**
 * Fetch the currently authenticated user profile.
 * Called on app boot to restore session.
 */
export async function fetchMe(): Promise<ApiUser> {
  const res = await requestJson<MeResponse>("/auth/me");
  return res.data.user;
}

/**
 * Logout: revoke current device token on the server.
 * Clears local token regardless of server response.
 */
export async function logout(): Promise<void> {
  try {
    await requestJson("/auth/logout", { method: "POST" });
  } finally {
    await clearAuthToken();
  }
}

/**
 * Register device push notification token (FCM or Expo push token) with backend.
 */
export async function updateFcmToken(fcmToken: string): Promise<void> {
  await requestJson("/auth/fcm-token", {
    method: "POST",
    body: { fcm_token: fcmToken },
  });
}

