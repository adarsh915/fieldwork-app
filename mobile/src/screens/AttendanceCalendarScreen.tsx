import React, { useCallback, useEffect, useState } from "react";
import {
  ActivityIndicator,
  FlatList,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from "react-native";
import { MaterialCommunityIcons } from "@expo/vector-icons";
import ScreenHeader from "../components/ScreenHeader";
import StatusBadge from "../components/StatusBadge";
import {
  AttendanceSummary,
  DayAttendance,
  getMyAttendanceCalendar,
} from "../api/attendance";
import { HttpError } from "../api/http";
import { type NativeStackScreenProps } from "@react-navigation/native-stack";
import { type AppStackParamList } from "../navigation/RootNavigator";

type Props = NativeStackScreenProps<AppStackParamList, "AttendanceCalendar">;

// Ordered weekday header labels
const WEEK_DAYS = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];

const MONTH_NAMES = [
  "January", "February", "March", "April", "May", "June",
  "July", "August", "September", "October", "November", "December",
];

interface SummaryCardProps {
  label: string;
  count: number;
  color: string;
}

function SummaryCard({ label, count, color }: SummaryCardProps) {
  return (
    <View style={styles.summaryCard}>
      <Text style={[styles.summaryCount, { color }]}>{count}</Text>
      <Text style={styles.summaryLabel}>{label}</Text>
    </View>
  );
}

export default function AttendanceCalendarScreen({ navigation }: Props) {
  const today = new Date();
  const [year, setYear]       = useState(today.getFullYear());
  const [month, setMonth]     = useState(today.getMonth() + 1); // 1-indexed
  const [calendarData, setCalendarData] = useState<Record<string, DayAttendance>>({});
  const [summary, setSummary]           = useState<AttendanceSummary | null>(null);
  const [loading, setLoading]           = useState(false);
  const [error, setError]               = useState<string | null>(null);

  const loadCalendar = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await getMyAttendanceCalendar(year, month);
      setCalendarData(res.calendar);
      setSummary(res.summary);
    } catch (e) {
      const err = e as HttpError;
      setError(err.message ?? "Failed to load attendance.");
    } finally {
      setLoading(false);
    }
  }, [year, month]);

  useEffect(() => { loadCalendar(); }, [loadCalendar]);

  const changeMonth = (dir: number) => {
    let m = month + dir;
    let y = year;
    if (m > 12) { m = 1; y++; }
    if (m < 1)  { m = 12; y--; }
    setMonth(m);
    setYear(y);
  };

  // Build grid: pad the first week with empty slots based on weekday of 1st
  const buildGrid = () => {
    const entries = Object.entries(calendarData); // already sorted by date
    if (!entries.length) return [];

    const firstDay = new Date(entries[0][0]).getDay(); // 0=Sun
    const grid: Array<{ date: string; data: DayAttendance } | null> = [
      ...Array(firstDay).fill(null),
      ...entries.map(([date, data]) => ({ date, data })),
    ];
    return grid;
  };

  const grid = buildGrid();
  const isCurrentMonth = year === today.getFullYear() && month === today.getMonth() + 1;

  return (
    <View style={styles.container}>
      <ScreenHeader
        title="Attendance"
        onBack={() => navigation.goBack()}
        bgColor="#7367F0"
      />

      {/* Month navigator */}
      <View style={styles.navRow}>
        <TouchableOpacity
          onPress={() => changeMonth(-1)}
          style={styles.navBtn}
          accessibilityLabel="Previous month"
        >
          <MaterialCommunityIcons name="chevron-left" size={28} color="#fff" />
        </TouchableOpacity>

        <View style={styles.navCenter}>
          <Text style={styles.monthLabel}>
            {MONTH_NAMES[month - 1]} {year}
          </Text>
          {isCurrentMonth && (
            <View style={styles.currentMonthDot} />
          )}
        </View>

        <TouchableOpacity
          onPress={() => changeMonth(1)}
          style={styles.navBtn}
          accessibilityLabel="Next month"
        >
          <MaterialCommunityIcons name="chevron-right" size={28} color="#fff" />
        </TouchableOpacity>
      </View>

      {/* Summary bar */}
      {summary && (
        <View style={styles.summaryRow}>
          <SummaryCard label="Present"   count={summary.present}    color="#22c55e" />
          <SummaryCard label="Leave"     count={summary.on_leave}   color="#f59e0b" />
          <SummaryCard label="Holiday"   count={summary.holiday}    color="#60a5fa" />
          <SummaryCard label="Week Off"  count={summary.weekly_off} color="#a78bfa" />
          <SummaryCard label="Absent"    count={summary.absent}     color="#f87171" />
        </View>
      )}

      {/* Weekday headers */}
      <View style={styles.weekRow}>
        {WEEK_DAYS.map((d) => (
          <Text key={d} style={[styles.weekDay, d === "Sun" && styles.sunday]}>
            {d}
          </Text>
        ))}
      </View>

      {/* Calendar grid */}
      {loading ? (
        <ActivityIndicator color="#7367F0" size="large" style={{ marginTop: 40 }} />
      ) : error ? (
        <View style={styles.errorBox}>
          <Text style={styles.errorText}>{error}</Text>
          <TouchableOpacity onPress={loadCalendar} style={styles.retryBtn}>
            <Text style={styles.retryText}>Retry</Text>
          </TouchableOpacity>
        </View>
      ) : (
        <FlatList
          data={grid}
          keyExtractor={(_, i) => String(i)}
          numColumns={7}
          contentContainerStyle={styles.gridContainer}
          renderItem={({ item }) => {
            if (!item) {
              return <View style={styles.emptyCell} />;
            }
            const d = new Date(item.date);
            const isToday = item.date === today.toISOString().slice(0, 10);
            return (
              <View
                style={[
                  styles.dayCell,
                  isToday && styles.todayCell,
                ]}
              >
                <Text style={[styles.dayNum, isToday && styles.todayNum]}>
                  {d.getDate()}
                </Text>
                <StatusBadge status={item.data.status} compact />
                <Text
                  style={[styles.dayLabel, { color: getStatusColor(item.data.status) }]}
                  numberOfLines={1}
                >
                  {shortLabel(item.data)}
                </Text>
              </View>
            );
          }}
        />
      )}
    </View>
  );
}

// ─── Helpers ─────────────────────────────────────────────────────────────────

function shortLabel(day: DayAttendance): string {
  switch (day.status) {
    case "present":    return "P";
    case "on_leave":   return day.label.replace("On Leave ", "");
    case "holiday":    return day.label.length > 6 ? day.label.slice(0, 6) + "." : day.label;
    case "weekly_off": return "Off";
    case "absent":     return "A";
    default:           return "";
  }
}

function getStatusColor(status: string): string {
  const map: Record<string, string> = {
    present: "#22c55e", on_leave: "#f59e0b", holiday: "#60a5fa",
    weekly_off: "#a78bfa", absent: "#f87171", not_yet: "#374151",
  };
  return map[status] ?? "#374151";
}

// ─── Styles ───────────────────────────────────────────────────────────────────

const styles = StyleSheet.create({
  container:      { flex: 1, backgroundColor: "#0f172a" },
  navRow:         { flexDirection: "row", alignItems: "center", justifyContent: "space-between", paddingHorizontal: 8, paddingVertical: 12, backgroundColor: "#1e293b" },
  navBtn:         { padding: 6 },
  navCenter:      { alignItems: "center" },
  monthLabel:     { color: "#f1f5f9", fontSize: 17, fontWeight: "700" },
  currentMonthDot:{ width: 5, height: 5, borderRadius: 3, backgroundColor: "#7367F0", marginTop: 3 },
  summaryRow:     { flexDirection: "row", backgroundColor: "#1e293b", borderTopWidth: 1, borderColor: "#334155", paddingVertical: 10 },
  summaryCard:    { flex: 1, alignItems: "center" },
  summaryCount:   { fontSize: 20, fontWeight: "800" },
  summaryLabel:   { color: "#64748b", fontSize: 10, marginTop: 2 },
  weekRow:        { flexDirection: "row", paddingHorizontal: 4, paddingVertical: 6, backgroundColor: "#0f172a" },
  weekDay:        { flex: 1, textAlign: "center", color: "#64748b", fontSize: 11, fontWeight: "600" },
  sunday:         { color: "#f87171" },
  gridContainer:  { paddingHorizontal: 4, paddingBottom: 20 },
  emptyCell:      { flex: 1, aspectRatio: 0.85, margin: 2 },
  dayCell:        { flex: 1, aspectRatio: 0.85, margin: 2, backgroundColor: "#1e293b", borderRadius: 8, alignItems: "center", justifyContent: "center", paddingVertical: 4, paddingHorizontal: 2 },
  todayCell:      { borderWidth: 1.5, borderColor: "#7367F0" },
  dayNum:         { color: "#94a3b8", fontSize: 12, fontWeight: "600" },
  todayNum:       { color: "#7367F0" },
  dayLabel:       { fontSize: 8, marginTop: 2, fontWeight: "600" },
  errorBox:       { flex: 1, alignItems: "center", justifyContent: "center", padding: 24 },
  errorText:      { color: "#f87171", textAlign: "center", marginBottom: 12 },
  retryBtn:       { backgroundColor: "#7367F0", paddingHorizontal: 20, paddingVertical: 10, borderRadius: 8 },
  retryText:      { color: "#fff", fontWeight: "700" },
});
