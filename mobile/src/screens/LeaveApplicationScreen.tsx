import React, { useCallback, useEffect, useState } from "react";
import {
  ActivityIndicator,
  Alert,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from "react-native";
import { MaterialCommunityIcons } from "@expo/vector-icons";
import KeyboardAwareScreen from "../components/KeyboardAwareScreen";
import ScreenHeader from "../components/ScreenHeader";
import { applyLeave, getLeaveTypes, LeaveType } from "../api/leave";
import { HttpError } from "../api/http";
import { useAlert } from "../context/AlertModalContext";
import type { NativeStackScreenProps } from "@react-navigation/native-stack";
import type { AppStackParamList } from "../navigation/RootNavigator";

type Props = NativeStackScreenProps<AppStackParamList, "LeaveApplication">;

type Duration = "full" | "half_am" | "half_pm";

const DURATION_OPTIONS: Array<{ key: Duration; label: string }> = [
  { key: "full",    label: "Full Day" },
  { key: "half_am", label: "Half Day (AM)" },
  { key: "half_pm", label: "Half Day (PM)" },
];

/** Format Date to YYYY-MM-DD */
const toDateString = (d: Date) => d.toISOString().slice(0, 10);

/** Format Date for display */
const toDisplayDate = (d: Date) =>
  d.toLocaleDateString("en-IN", { day: "2-digit", month: "short", year: "numeric" });

export default function LeaveApplicationScreen({ navigation }: Props) {
  const { showSuccess, showError, showWarning } = useAlert();
  const [leaveTypes, setLeaveTypes]   = useState<LeaveType[]>([]);
  const [loadingTypes, setLoadingTypes] = useState(true);

  const [selectedType, setSelectedType] = useState<LeaveType | null>(null);
  const [fromDate, setFromDate]         = useState<Date>(new Date());
  const [toDate, setToDate]             = useState<Date>(new Date());
  const [duration, setDuration]         = useState<Duration>("full");
  const [reason, setReason]             = useState("");

  const [submitting, setSubmitting]     = useState(false);
  const [errors, setErrors]             = useState<Record<string, string>>({});

  const fetchTypes = useCallback(async () => {
    setLoadingTypes(true);
    try {
      const res = await getLeaveTypes();
      setLeaveTypes(res.data);
      if (res.data.length > 0) setSelectedType(res.data[0]);
    } catch {
      Alert.alert("Error", "Failed to load leave types.");
    } finally {
      setLoadingTypes(false);
    }
  }, []);

  useEffect(() => { fetchTypes(); }, [fetchTypes]);

  // When duration changes to half-day, set to_date = from_date
  const handleDurationChange = (d: Duration) => {
    setDuration(d);
    if (d !== "full") {
      setToDate(fromDate);
    }
  };

  const handleFromDateChange = (days: number) => {
    const d = new Date(fromDate);
    d.setDate(d.getDate() + days);
    if (d >= new Date(new Date().setHours(0, 0, 0, 0))) {
      setFromDate(d);
      if (d > toDate) setToDate(d);
    }
  };

  const handleToDateChange = (days: number) => {
    const d = new Date(toDate);
    d.setDate(d.getDate() + days);
    if (d >= fromDate) {
      setToDate(d);
    }
  };

  const validate = (): boolean => {
    const e: Record<string, string> = {};
    if (!selectedType) e.leave_type = "Please select a leave type.";
    if (fromDate > toDate) e.date = "End date must be on or after start date.";
    setErrors(e);
    return Object.keys(e).length === 0;
  };

  const handleSubmit = async () => {
    if (!validate()) return;

    setSubmitting(true);
    try {
      await applyLeave({
        leave_type_id: selectedType!.id,
        from_date:     toDateString(fromDate),
        to_date:       toDateString(toDate),
        duration,
        reason:        reason.trim() || undefined,
      });

      showSuccess(
        "Application Submitted!",
        "Your leave application has been submitted successfully for approval.",
        () => navigation.goBack()
      );
    } catch (e) {
      const err = e as HttpError;
      const msg = err.message || "Failed to submit leave.";

      // Show alert dialog with server error message
      showError("Leave Application Error", msg);

      // Also map field errors if present
      const serverErrors = (err.details as any)?.errors;
      if (serverErrors && typeof serverErrors === "object") {
        const mapped: Record<string, string> = {};
        for (const [field, msgs] of Object.entries(serverErrors)) {
          if (Array.isArray(msgs) && msgs.length > 0) {
            mapped[field] = msgs[0];
          }
        }
        setErrors(mapped);
      }
    } finally {
      setSubmitting(false);
    }
  };

  if (loadingTypes) {
    return (
      <View style={styles.container}>
        <ScreenHeader title="Apply Leave" onBack={() => navigation.goBack()} bgColor="#7367F0" />
        <ActivityIndicator color="#7367F0" size="large" style={{ marginTop: 60 }} />
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <ScreenHeader title="Apply Leave" onBack={() => navigation.goBack()} bgColor="#7367F0" />

      <KeyboardAwareScreen contentStyle={styles.scroll} backgroundColor="#F4F4F8">
        {/* ── Leave Type ── */}
        <Text style={styles.sectionLabel}>Leave Type</Text>
        <View style={styles.chipRow}>
          {leaveTypes.map((lt) => {
            const active = selectedType?.id === lt.id;
            return (
              <TouchableOpacity
                key={lt.id}
                style={[styles.chip, active && styles.chipActive]}
                onPress={() => setSelectedType(lt)}
              >
                <Text style={[styles.chipText, active && styles.chipTextActive]}>
                  {lt.code}
                </Text>
                <Text style={[styles.chipSub, active && styles.chipSubActive]}>
                  {lt.name}
                </Text>
              </TouchableOpacity>
            );
          })}
        </View>
        {errors.leave_type && <Text style={styles.fieldError}>{errors.leave_type}</Text>}

        {/* ── Duration ── */}
        <Text style={styles.sectionLabel}>Duration</Text>
        <View style={styles.chipRow}>
          {DURATION_OPTIONS.map((opt) => {
            const active = duration === opt.key;
            return (
              <TouchableOpacity
                key={opt.key}
                style={[styles.chip, active && styles.chipActive]}
                onPress={() => handleDurationChange(opt.key)}
              >
                <Text style={[styles.chipText, active && styles.chipTextActive]}>
                  {opt.label}
                </Text>
              </TouchableOpacity>
            );
          })}
        </View>

        {/* ── From Date ── */}
        <Text style={styles.sectionLabel}>From Date</Text>
        <View style={styles.dateControl}>
          <TouchableOpacity style={styles.dateBtn} onPress={() => handleFromDateChange(-1)}>
            <MaterialCommunityIcons name="chevron-left" size={24} color="#7367F0" />
          </TouchableOpacity>
          <View style={styles.dateDisplay}>
            <MaterialCommunityIcons name="calendar" size={16} color="#7367F0" />
            <Text style={styles.dateText}>{toDisplayDate(fromDate)}</Text>
          </View>
          <TouchableOpacity style={styles.dateBtn} onPress={() => handleFromDateChange(1)}>
            <MaterialCommunityIcons name="chevron-right" size={24} color="#7367F0" />
          </TouchableOpacity>
        </View>

        {/* ── To Date (only for full-day) ── */}
        {duration === "full" && (
          <>
            <Text style={styles.sectionLabel}>To Date</Text>
            <View style={styles.dateControl}>
              <TouchableOpacity style={styles.dateBtn} onPress={() => handleToDateChange(-1)}>
                <MaterialCommunityIcons name="chevron-left" size={24} color="#7367F0" />
              </TouchableOpacity>
              <View style={styles.dateDisplay}>
                <MaterialCommunityIcons name="calendar" size={16} color="#7367F0" />
                <Text style={styles.dateText}>{toDisplayDate(toDate)}</Text>
              </View>
              <TouchableOpacity style={styles.dateBtn} onPress={() => handleToDateChange(1)}>
                <MaterialCommunityIcons name="chevron-right" size={24} color="#7367F0" />
              </TouchableOpacity>
            </View>
          </>
        )}

        {errors.date && <Text style={styles.fieldError}>{errors.date}</Text>}

        {/* Days count preview */}
        <View style={styles.daysPreview}>
          <MaterialCommunityIcons name="information-outline" size={15} color="#7367F0" />
          <Text style={styles.daysPreviewText}>
            {duration !== "full"
              ? "0.5 day"
              : `${Math.max(1, Math.round((toDate.getTime() - fromDate.getTime()) / 86400000) + 1)} day(s)`}
          </Text>
        </View>

        {/* ── Reason ── */}
        <Text style={styles.sectionLabel}>Reason (optional)</Text>
        <TextInput
          style={styles.textArea}
          value={reason}
          onChangeText={setReason}
          placeholder="Describe the reason for leave..."
          placeholderTextColor="#9CA3AF"
          multiline
          numberOfLines={4}
          maxLength={500}
          textAlignVertical="top"
        />
        <Text style={styles.charCount}>{reason.length}/500</Text>

        {/* ── Submit ── */}
        <TouchableOpacity
          style={[styles.submitBtn, submitting && styles.submitBtnDisabled]}
          onPress={handleSubmit}
          disabled={submitting}
          accessibilityLabel="Submit leave application"
        >
          {submitting ? (
            <ActivityIndicator color="#fff" />
          ) : (
            <>
              <MaterialCommunityIcons name="send" size={18} color="#fff" />
              <Text style={styles.submitText}>Submit Application</Text>
            </>
          )}
        </TouchableOpacity>

        <View style={{ height: 20 }} />
      </KeyboardAwareScreen>
    </View>
  );
}

// ─── Styles ───────────────────────────────────────────────────────────────────

const styles = StyleSheet.create({
  container:        { flex: 1, backgroundColor: "#F4F4F8" },
  scroll:           { padding: 20, paddingBottom: 120 },
  sectionLabel:     { color: "#6B7280", fontSize: 12, fontWeight: "700", textTransform: "uppercase", letterSpacing: 0.8, marginBottom: 10, marginTop: 20 },
  chipRow:          { flexDirection: "row", flexWrap: "wrap", gap: 8 },
  chip:             { backgroundColor: "#FFFFFF", borderRadius: 10, paddingHorizontal: 14, paddingVertical: 10, borderWidth: 1, borderColor: "#E5E7EB", alignItems: "center" },
  chipActive:       { backgroundColor: "#EDE9FE", borderColor: "#7367F0" },
  chipText:         { color: "#374151", fontWeight: "700", fontSize: 14 },
  chipTextActive:   { color: "#7367F0", fontWeight: "800" },
  chipSub:          { color: "#6B7280", fontSize: 10, marginTop: 2 },
  chipSubActive:    { color: "#7367F0" },
  dateControl:      { flexDirection: "row", alignItems: "center", backgroundColor: "#FFFFFF", borderRadius: 12, overflow: "hidden", borderWidth: 1, borderColor: "#E5E7EB", shadowColor: "#000", shadowOffset: { width: 0, height: 1 }, shadowOpacity: 0.04, shadowRadius: 2, elevation: 1 },
  dateBtn:          { padding: 14, backgroundColor: "#EDE9FE" },
  dateDisplay:      { flex: 1, flexDirection: "row", alignItems: "center", justifyContent: "center", gap: 8 },
  dateText:         { color: "#111827", fontSize: 15, fontWeight: "600" },
  daysPreview:      { flexDirection: "row", alignItems: "center", gap: 6, marginTop: 8 },
  daysPreviewText:  { color: "#7367F0", fontSize: 13, fontWeight: "600" },
  textArea:         { backgroundColor: "#FFFFFF", borderRadius: 12, padding: 14, color: "#111827", fontSize: 14, borderWidth: 1, borderColor: "#E5E7EB", minHeight: 100 },
  charCount:        { color: "#9CA3AF", fontSize: 11, textAlign: "right", marginTop: 4 },
  submitBtn:        { flexDirection: "row", alignItems: "center", justifyContent: "center", gap: 8, backgroundColor: "#7367F0", borderRadius: 14, paddingVertical: 16, marginTop: 28, shadowColor: "#7367F0", shadowOffset: { width: 0, height: 4 }, shadowOpacity: 0.3, shadowRadius: 8, elevation: 4 },
  submitBtnDisabled:{ opacity: 0.6 },
  submitText:       { color: "#fff", fontWeight: "700", fontSize: 16 },
  fieldError:       { color: "#EF4444", fontSize: 12, marginTop: 6 },
});
