import React from "react";
import { StyleSheet, Text, View } from "react-native";
import { AttendanceStatus } from "../api/attendance";

const CONFIG: Record<
  AttendanceStatus,
  { bg: string; text: string; icon: string }
> = {
  present:    { bg: "#16a34a22", text: "#22c55e", icon: "✓" },
  on_leave:   { bg: "#d9770622", text: "#f59e0b", icon: "🏖" },
  holiday:    { bg: "#2563eb22", text: "#60a5fa", icon: "⚑" },
  weekly_off: { bg: "#7c3aed22", text: "#a78bfa", icon: "☽" },
  absent:     { bg: "#dc262622", text: "#f87171", icon: "✗" },
  not_yet:    { bg: "#37415133", text: "#64748b", icon: "·" },
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
        <Text style={[styles.icon, { color: cfg.text }]}>{cfg.icon}</Text>
      </View>
    );
  }

  return (
    <View style={[styles.pill, { backgroundColor: cfg.bg }]}>
      <Text style={[styles.pillText, { color: cfg.text }]}>
        {cfg.icon}  {label ?? status.replace("_", " ").toUpperCase()}
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
  icon: {
    fontSize: 12,
    fontWeight: "700",
  },
  pill: {
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
