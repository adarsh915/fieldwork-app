/**
 * API Configuration
 *
 * Single source of truth for the backend URL.
 *
 * ┌──────────────────────────────────────────────────────────────┐
 * │  ENVIRONMENT GUIDE                                           │
 * │  Android Emulator  → use  http://10.0.2.2:8000              │
 * │  Physical Device   → use your LAN IP e.g. http://192.168.x.x:8000 │
 * │  Expo Web / PC     → use  http://127.0.0.1:8000             │
 * └──────────────────────────────────────────────────────────────┘
 */

// =====================================================================
// UNCOMMENT ONLY ONE OF THE FOLLOWING DEPENDING ON YOUR CURRENT SETUP:
// =====================================================================

import { Platform } from "react-native";

/**
 * API Base URL Configuration
 *
 * Automatically switches target backend host:
 *  - Web Browser   → http://127.0.0.1:8000
 *  - Mobile Device → EXPO_PUBLIC_API_URL or LAN IP (http://192.168.1.14:8000)
 */
export const API_BASE_URL =
    process.env.EXPO_PUBLIC_API_URL ?? "http://192.168.1.20:8000";

/** Laravel API prefix */
export const API_PREFIX = "/api";
