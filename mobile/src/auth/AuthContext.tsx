/**
 * Auth Context
 *
 * Global auth state management. Wrap your app with <AuthProvider>
 * and consume with useAuth() hook anywhere in the component tree.
 *
 * State managed:
 *   - user         : logged-in user object (null = not logged in)
 *   - isLoading    : true while checking saved token on startup
 *   - login()      : call with email+password → hits API → stores token
 *   - logout()     : revokes token on server + clears local storage
 */

import React, { createContext, useContext, useEffect, useState } from "react";

import { clearAuthToken, getAuthToken } from "./tokenStore";
import {
  loginWithEmailPassword,
  logout as apiLogout,
  fetchMe,
} from "../api/auth";

// ─── Types ─────────────────────────────────────────────────────────────────────

export type AuthUser = {
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

type AuthState = {
  user: AuthUser | null;
  isLoading: boolean;
  error: string | null;
  login: (email: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
};

// ─── Context ───────────────────────────────────────────────────────────────────

const AuthContext = createContext<AuthState | undefined>(undefined);

// ─── Provider ──────────────────────────────────────────────────────────────────

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<AuthUser | null>(null);
  const [isLoading, setIsLoading] = useState(true); // true on first boot
  const [error, setError] = useState<string | null>(null);

  /**
   * On app boot: check if there is a saved token.
   * If yes → fetch the current user profile from the server.
   * If no  → user needs to login.
   */
  useEffect(() => {
    (async () => {
      try {
        const token = await getAuthToken();
        if (token) {
          const data = await fetchMe(); // GET /api/auth/me
          setUser(data as AuthUser);
        }
      } catch {
        // Token expired or invalid → clear it
        await clearAuthToken();
        setUser(null);
      } finally {
        setIsLoading(false);
      }
    })();
  }, []);

  /** Login with email + password. Throws on failure so caller can show error. */
  async function login(email: string, password: string) {
    setError(null);
    try {
      const res = await loginWithEmailPassword(email, password);
      setUser(res.data.user as AuthUser);
    } catch (err: any) {
      const msg = err?.message ?? "Login failed. Please try again.";
      setError(msg);
      throw new Error(msg);
    }
  }

  /** Logout: revokes server token + clears local storage. */
  async function logout() {
    try {
      await apiLogout();
    } catch {
      // Even if server call fails, clear local token
    } finally {
      setUser(null);
    }
  }

  return (
    <AuthContext.Provider value={{ user, isLoading, error, login, logout }}>
      {children}
    </AuthContext.Provider>
  );
}

// ─── Hook ──────────────────────────────────────────────────────────────────────

/** Access auth state from any component. Must be inside <AuthProvider>. */
export function useAuth(): AuthState {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error("useAuth must be used inside <AuthProvider>");
  return ctx;
}
