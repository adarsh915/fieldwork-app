import { MaterialIcons } from "@expo/vector-icons";
import React, { useState, useEffect, useMemo } from "react";
import {
  Modal,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from "react-native";

interface DatePickerModalProps {
  visible: boolean;
  value: string; // "YYYY-MM-DD"
  onChange: (date: string) => void;
  onClose: () => void;
  title?: string;
  maxDate?: string; // "YYYY-MM-DD"
  minDate?: string; // "YYYY-MM-DD"
}

const MONTH_NAMES = [
  "January", "February", "March", "April", "May", "June",
  "July", "August", "September", "October", "November", "December",
];

const WEEK_DAYS = ["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa"];

export default function DatePickerModal({
  visible,
  value,
  onChange,
  onClose,
  title = "Select Date",
  maxDate,
  minDate,
}: DatePickerModalProps) {
  // Parse initial date
  const parseDate = (dStr?: string) => {
    if (!dStr) return new Date();
    const parts = dStr.split("-").map(Number);
    if (parts.length === 3 && !isNaN(parts[0]) && !isNaN(parts[1]) && !isNaN(parts[2])) {
      return new Date(parts[0], parts[1] - 1, parts[2]);
    }
    return new Date();
  };

  const formatDateStr = (d: Date): string => {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, "0");
    const day = String(d.getDate()).padStart(2, "0");
    return `${y}-${m}-${day}`;
  };

  const initialDate = parseDate(value);
  const [viewYear, setViewYear] = useState(initialDate.getFullYear());
  const [viewMonth, setViewMonth] = useState(initialDate.getMonth()); // 0-indexed
  const [tempSelected, setTempSelected] = useState<string>(value || formatDateStr(new Date()));

  useEffect(() => {
    if (visible) {
      const d = parseDate(value);
      setViewYear(d.getFullYear());
      setViewMonth(d.getMonth());
      setTempSelected(value || formatDateStr(new Date()));
    }
  }, [visible, value]);

  const todayStr = formatDateStr(new Date());

  const isDateDisabled = (dateStr: string) => {
    if (maxDate && dateStr > maxDate) return true;
    if (minDate && dateStr < minDate) return true;
    return false;
  };

  const calendarDays = useMemo(() => {
    const firstDayIndex = new Date(viewYear, viewMonth, 1).getDay();
    const daysInMonth = new Date(viewYear, viewMonth + 1, 0).getDate();
    const daysInPrevMonth = new Date(viewYear, viewMonth, 0).getDate();

    const cells: Array<{
      day: number;
      dateStr: string;
      isCurrentMonth: boolean;
      disabled: boolean;
    }> = [];

    // Prev month padding
    for (let i = firstDayIndex - 1; i >= 0; i--) {
      const day = daysInPrevMonth - i;
      const prevM = viewMonth === 0 ? 11 : viewMonth - 1;
      const prevY = viewMonth === 0 ? viewYear - 1 : viewYear;
      const mStr = String(prevM + 1).padStart(2, "0");
      const dStr = String(day).padStart(2, "0");
      const dateStr = `${prevY}-${mStr}-${dStr}`;
      cells.push({
        day,
        dateStr,
        isCurrentMonth: false,
        disabled: isDateDisabled(dateStr),
      });
    }

    // Current month days
    for (let day = 1; day <= daysInMonth; day++) {
      const mStr = String(viewMonth + 1).padStart(2, "0");
      const dStr = String(day).padStart(2, "0");
      const dateStr = `${viewYear}-${mStr}-${dStr}`;
      cells.push({
        day,
        dateStr,
        isCurrentMonth: true,
        disabled: isDateDisabled(dateStr),
      });
    }

    // Next month padding to fill row (multiples of 7)
    const remaining = 7 - (cells.length % 7);
    if (remaining < 7) {
      for (let day = 1; day <= remaining; day++) {
        const nextM = viewMonth === 11 ? 0 : viewMonth + 1;
        const nextY = viewMonth === 11 ? viewYear + 1 : viewYear;
        const mStr = String(nextM + 1).padStart(2, "0");
        const dStr = String(day).padStart(2, "0");
        const dateStr = `${nextY}-${mStr}-${dStr}`;
        cells.push({
          day,
          dateStr,
          isCurrentMonth: false,
          disabled: isDateDisabled(dateStr),
        });
      }
    }

    return cells;
  }, [viewYear, viewMonth, maxDate, minDate]);

  const handlePrevMonth = () => {
    if (viewMonth === 0) {
      setViewMonth(11);
      setViewYear((y) => y - 1);
    } else {
      setViewMonth((m) => m - 1);
    }
  };

  const handleNextMonth = () => {
    if (viewMonth === 11) {
      setViewMonth(0);
      setViewYear((y) => y + 1);
    } else {
      setViewMonth((m) => m + 1);
    }
  };

  const handleQuickSelect = (type: "today" | "yesterday") => {
    const d = new Date();
    if (type === "yesterday") {
      d.setDate(d.getDate() - 1);
    }
    const dStr = formatDateStr(d);
    if (!isDateDisabled(dStr)) {
      setTempSelected(dStr);
      setViewYear(d.getFullYear());
      setViewMonth(d.getMonth());
    }
  };

  const handleConfirm = () => {
    onChange(tempSelected);
    onClose();
  };

  return (
    <Modal visible={visible} transparent animationType="fade" onRequestClose={onClose}>
      <View style={styles.overlay}>
        <View style={styles.dialog}>
          {/* Header */}
          <View style={styles.header}>
            <Text style={styles.title}>{title}</Text>
            <TouchableOpacity onPress={onClose} hitSlop={{ top: 10, bottom: 10, left: 10, right: 10 }}>
              <MaterialIcons name="close" size={22} color="#6B7280" />
            </TouchableOpacity>
          </View>

          {/* Quick Presets */}
          <View style={styles.presetsRow}>
            <TouchableOpacity
              style={[
                styles.presetBtn,
                tempSelected === todayStr && styles.presetBtnActive,
              ]}
              onPress={() => handleQuickSelect("today")}
            >
              <Text
                style={[
                  styles.presetBtnText,
                  tempSelected === todayStr && styles.presetBtnTextActive,
                ]}
              >
                Today
              </Text>
            </TouchableOpacity>

            <TouchableOpacity
              style={[
                styles.presetBtn,
                tempSelected ===
                  formatDateStr(new Date(Date.now() - 86400000)) &&
                  styles.presetBtnActive,
              ]}
              onPress={() => handleQuickSelect("yesterday")}
            >
              <Text
                style={[
                  styles.presetBtnText,
                  tempSelected ===
                    formatDateStr(new Date(Date.now() - 86400000)) &&
                    styles.presetBtnTextActive,
                ]}
              >
                Yesterday
              </Text>
            </TouchableOpacity>
          </View>

          {/* Month / Year Navigator */}
          <View style={styles.monthNav}>
            <TouchableOpacity onPress={handlePrevMonth} style={styles.navBtn}>
              <MaterialIcons name="chevron-left" size={26} color="#111827" />
            </TouchableOpacity>
            <Text style={styles.monthYearText}>
              {MONTH_NAMES[viewMonth]} {viewYear}
            </Text>
            <TouchableOpacity onPress={handleNextMonth} style={styles.navBtn}>
              <MaterialIcons name="chevron-right" size={26} color="#111827" />
            </TouchableOpacity>
          </View>

          {/* Weekday headers */}
          <View style={styles.weekRow}>
            {WEEK_DAYS.map((w, idx) => (
              <Text key={idx} style={styles.weekDayText}>
                {w}
              </Text>
            ))}
          </View>

          {/* Days Grid */}
          <View style={styles.grid}>
            {calendarDays.map((cell, idx) => {
              const isSelected = cell.dateStr === tempSelected;
              const isToday = cell.dateStr === todayStr;

              return (
                <TouchableOpacity
                  key={idx}
                  disabled={cell.disabled}
                  style={[
                    styles.cell,
                    isSelected && styles.cellSelected,
                    !cell.isCurrentMonth && styles.cellOtherMonth,
                    cell.disabled && styles.cellDisabled,
                  ]}
                  onPress={() => setTempSelected(cell.dateStr)}
                >
                  <Text
                    style={[
                      styles.cellText,
                      !cell.isCurrentMonth && styles.cellTextOtherMonth,
                      isToday && !isSelected && styles.cellTextToday,
                      isSelected && styles.cellTextSelected,
                      cell.disabled && styles.cellTextDisabled,
                    ]}
                  >
                    {cell.day}
                  </Text>
                  {isToday && !isSelected && <View style={styles.todayDot} />}
                </TouchableOpacity>
              );
            })}
          </View>

          {/* Selected Date Summary & Actions */}
          <View style={styles.footer}>
            <View>
              <Text style={styles.selectedLabel}>Selected Date</Text>
              <Text style={styles.selectedVal}>{tempSelected}</Text>
            </View>
            <View style={styles.actionButtons}>
              <TouchableOpacity style={styles.cancelBtn} onPress={onClose}>
                <Text style={styles.cancelBtnText}>Cancel</Text>
              </TouchableOpacity>
              <TouchableOpacity style={styles.confirmBtn} onPress={handleConfirm}>
                <Text style={styles.confirmBtnText}>Confirm</Text>
              </TouchableOpacity>
            </View>
          </View>
        </View>
      </View>
    </Modal>
  );
}

const styles = StyleSheet.create({
  overlay: {
    flex: 1,
    backgroundColor: "rgba(0, 0, 0, 0.55)",
    justifyContent: "center",
    alignItems: "center",
    padding: 20,
  },
  dialog: {
    backgroundColor: "#FFFFFF",
    borderRadius: 20,
    width: "100%",
    maxWidth: 360,
    padding: 18,
    shadowColor: "#000",
    shadowOffset: { width: 0, height: 6 },
    shadowOpacity: 0.15,
    shadowRadius: 16,
    elevation: 8,
  },
  header: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 12,
  },
  title: {
    fontSize: 17,
    fontWeight: "700",
    color: "#111827",
  },
  presetsRow: {
    flexDirection: "row",
    gap: 8,
    marginBottom: 14,
  },
  presetBtn: {
    paddingVertical: 6,
    paddingHorizontal: 14,
    borderRadius: 20,
    backgroundColor: "#F3F4F6",
    borderWidth: 1,
    borderColor: "#E5E7EB",
  },
  presetBtnActive: {
    backgroundColor: "#EEF2FF",
    borderColor: "#7367F0",
  },
  presetBtnText: {
    fontSize: 13,
    fontWeight: "600",
    color: "#4B5563",
  },
  presetBtnTextActive: {
    color: "#7367F0",
  },
  monthNav: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 10,
    paddingHorizontal: 4,
  },
  navBtn: {
    padding: 4,
    borderRadius: 8,
  },
  monthYearText: {
    fontSize: 16,
    fontWeight: "700",
    color: "#1F2937",
  },
  weekRow: {
    flexDirection: "row",
    justifyContent: "space-around",
    marginBottom: 6,
  },
  weekDayText: {
    width: 38,
    textAlign: "center",
    fontSize: 12,
    fontWeight: "600",
    color: "#9CA3AF",
  },
  grid: {
    flexDirection: "row",
    flexWrap: "wrap",
    justifyContent: "space-around",
  },
  cell: {
    width: 38,
    height: 38,
    justifyContent: "center",
    alignItems: "center",
    marginVertical: 2,
    borderRadius: 19,
  },
  cellOtherMonth: {
    opacity: 0.35,
  },
  cellDisabled: {
    opacity: 0.2,
  },
  cellSelected: {
    backgroundColor: "#7367F0",
  },
  cellText: {
    fontSize: 14,
    fontWeight: "600",
    color: "#1F2937",
  },
  cellTextOtherMonth: {
    color: "#9CA3AF",
  },
  cellTextToday: {
    color: "#7367F0",
    fontWeight: "700",
  },
  cellTextSelected: {
    color: "#FFFFFF",
    fontWeight: "700",
  },
  cellTextDisabled: {
    color: "#D1D5DB",
  },
  todayDot: {
    position: "absolute",
    bottom: 4,
    width: 4,
    height: 4,
    borderRadius: 2,
    backgroundColor: "#7367F0",
  },
  footer: {
    marginTop: 18,
    paddingTop: 12,
    borderTopWidth: 1,
    borderTopColor: "#F3F4F6",
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
  },
  selectedLabel: {
    fontSize: 11,
    color: "#6B7280",
    textTransform: "uppercase",
    fontWeight: "600",
  },
  selectedVal: {
    fontSize: 14,
    fontWeight: "700",
    color: "#111827",
  },
  actionButtons: {
    flexDirection: "row",
    gap: 8,
  },
  cancelBtn: {
    paddingVertical: 8,
    paddingHorizontal: 14,
    borderRadius: 8,
  },
  cancelBtnText: {
    fontSize: 14,
    fontWeight: "600",
    color: "#6B7280",
  },
  confirmBtn: {
    backgroundColor: "#7367F0",
    paddingVertical: 8,
    paddingHorizontal: 18,
    borderRadius: 8,
  },
  confirmBtnText: {
    fontSize: 14,
    fontWeight: "700",
    color: "#FFFFFF",
  },
});
