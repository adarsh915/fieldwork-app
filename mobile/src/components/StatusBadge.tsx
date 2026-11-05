import React from "react";
import { StyleSheet, Text, View } from "react-native";
import { MaterialCommunityIcons } from "@expo/vector-icons";
import { AttendanceStatus } from "../api/attendance";

const CONFIG: Record<
  AttendanceStatus,
  { bg: string; text: string; icon: keyof typeof MaterialCommunityIcons.glyphMap }
> = {
  present:    { bg: "#16a34a22", text: "#22c55e", icon: "check-circle-outline" },
  on_leave:   { bg: "#d9770622", text: "#f59e0b", icon: "beach" },
  holiday:    { bg: "#2563eb22", text: "#60a5fa", icon: "flag-variant" },
  weekly_off: { bg: "#7c3aed22", text: "#a78bfa", icon: "weather-night" },
  absent:     { bg: "#dc262622", text: "#f87171", icon: "close-circle-outline" },
  not_yet:    { bg: "#37415133", text: "#64748b", icon: "circle-small" },
};

interface Props {
  status: AttendanceStatus;
  label?: string;
  compact?: boolean;
}

/**
 * StatusBadge — reusable attendance status indicator.
 * Used in both the calendar day cells and the leave list rows.
 */
export default function StatusBadge({ status, label, compact = false }: Props) {
  const cfg = CONFIG[status] ?? CONFIG.not_yet;

  if (compact) {
    return (
      <View style={[styles.badge, { backgroundColor: cfg.bg }]}>
        <MaterialCommunityIcons name={cfg.icon} size={13} color={cfg.text} />
      </View>
    );
  }

  return (
    <View style={[styles.pill, { backgroundColor: cfg.bg }]}>
      <MaterialCommunityIcons name={cfg.icon} size={13} color={cfg.text} style={{ marginRight: 4 }} />
      <Text style={[styles.pillText, { color: cfg.text }]}>
        {label ?? status.replace("_", " ").toUpperCase()}
      </Text>
    </View>
  );
}

const styles = StyleSheet.create({
  badge: {
    width: 22,
    height: 22,
    borderRadius: 11,
    alignItems: "center",
    justifyContent: "center",
  },
  pill: {
    flexDirection: "row",
    alignItems: "center",
    borderRadius: 6,
    paddingHorizontal: 8,
    paddingVertical: 3,
    alignSelf: "flex-start",
  },
  pillText: {
    fontSize: 12,
    fontWeight: "600",
    textTransform: "capitalize",
  },
});
