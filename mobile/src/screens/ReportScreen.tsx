import React, { useState, useCallback } from "react";
import {
  ActivityIndicator,
  FlatList,
  Modal,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from "react-native";
import { MaterialCommunityIcons, MaterialIcons } from "@expo/vector-icons";
import { useNavigation, useFocusEffect } from "@react-navigation/native";
import { useSafeAreaInsets } from "react-native-safe-area-context";

import ScreenHeader from "../components/ScreenHeader";
import DatePickerModal from "../components/DatePickerModal";
import CachedImage from "../components/CachedImage";
import {
  fetchReportSummary,
  type ReportPeriod,
  type ReportSummaryResponse,
  type TeamMember,
  type TeamMemberBreakdown,
  type RecentActivityItem,
} from "../api/report";

const PURPLE = "#7367F0";

const PERIODS: { key: ReportPeriod; label: string; icon: keyof typeof MaterialIcons.glyphMap }[] = [
  { key: "today", label: "Today", icon: "today" },
  { key: "week",  label: "This Week", icon: "date-range" },
  { key: "month", label: "This Month", icon: "calendar-month" },
  { key: "year",  label: "Yearly", icon: "timeline" },
  { key: "custom", label: "Custom 📅", icon: "edit-calendar" },
];

export default function ReportScreen() {
  const navigation = useNavigation();
  const insets = useSafeAreaInsets();

  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [period, setPeriod] = useState<ReportPeriod>("today");
  const [selectedUserId, setSelectedUserId] = useState<number | "all">("all");
  const [customStartDate, setCustomStartDate] = useState<string>("");
  const [customEndDate, setCustomEndDate] = useState<string>("");

  // Modals state
  const [showTeamModal, setShowTeamModal] = useState(false);
  const [datePickerType, setDatePickerType] = useState<"start" | "end" | null>(null);
  const [previewPhoto, setPreviewPhoto] = useState<string | null>(null);
  const [activeTab, setActiveTab] = useState<"visits" | "team">("visits");

  // Data
  const [reportData, setReportData] = useState<ReportSummaryResponse | null>(null);

  const loadReport = useCallback(async () => {
    try {
      const data = await fetchReportSummary({
        period,
        user_id: selectedUserId,
        start_date: period === "custom" ? customStartDate : undefined,
        end_date: period === "custom" ? customEndDate : undefined,
      });
      setReportData(data);
    } catch (err) {
      console.log("[ReportScreen] load error:", err);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [period, selectedUserId, customStartDate, customEndDate]);

  useFocusEffect(
    useCallback(() => {
      loadReport();
    }, [loadReport])
  );

  function handlePeriodSelect(p: ReportPeriod) {
    if (p === "custom") {
      setPeriod("custom");
      if (!customStartDate) {
        const today = new Date().toISOString().split("T")[0];
        setCustomStartDate(today);
        setCustomEndDate(today);
      }
    } else {
      setPeriod(p);
    }
  }

  const selectedMemberName =
    selectedUserId === "all"
      ? "All Team Members"
      : reportData?.team_members.find((m) => m.id === selectedUserId)?.name || "Selected Employee";

  const kpis = reportData?.kpis;
  const isManagerOrVP = reportData && (reportData.scope === "team" || reportData.scope === "all");

  return (
    <View style={styles.container}>
      {/* ── Seamless Header ── */}
      <ScreenHeader
        title="Reports"
        onBack={() => navigation.goBack()}
        bgColor={PURPLE}
      />

      <ScrollView
        contentContainerStyle={[
          styles.scrollContent,
          { paddingBottom: Math.max(insets.bottom, 14) + 20 },
        ]}
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={() => {
              setRefreshing(true);
              loadReport();
            }}
            colors={[PURPLE]}
          />
        }
      >
        {/* ── Period Selector Pills ── */}
        <ScrollView
          horizontal
          showsHorizontalScrollIndicator={false}
          contentContainerStyle={styles.periodPillsWrap}
        >
          {PERIODS.map((item) => {
            const active = period === item.key;
            return (
              <TouchableOpacity
                key={item.key}
                style={[styles.periodChip, active && styles.periodChipActive]}
                onPress={() => handlePeriodSelect(item.key)}
                activeOpacity={0.8}
              >
                <MaterialIcons
                  name={item.icon}
                  size={16}
                  color={active ? "#FFFFFF" : "#64748B"}
                />
                <Text style={[styles.periodChipText, active && styles.periodChipTextActive]}>
                  {item.label}
                </Text>
              </TouchableOpacity>
            );
          })}
        </ScrollView>

        {/* ── Custom Date Pickers (if period === 'custom') ── */}
        {period === "custom" && (
          <View style={styles.customDateBox}>
            <TouchableOpacity
              style={styles.datePickerBtn}
              onPress={() => setDatePickerType("start")}
              activeOpacity={0.8}
            >
              <Text style={styles.datePickerLabel}>From Date</Text>
              <View style={styles.datePickerRow}>
                <MaterialIcons name="calendar-today" size={16} color={PURPLE} />
                <Text style={styles.datePickerVal}>{customStartDate || "Select"}</Text>
              </View>
            </TouchableOpacity>

            <MaterialIcons name="arrow-forward" size={18} color="#94A3B8" />

            <TouchableOpacity
              style={styles.datePickerBtn}
              onPress={() => setDatePickerType("end")}
              activeOpacity={0.8}
            >
              <Text style={styles.datePickerLabel}>To Date</Text>
              <View style={styles.datePickerRow}>
                <MaterialIcons name="event" size={16} color={PURPLE} />
                <Text style={styles.datePickerVal}>{customEndDate || "Select"}</Text>
              </View>
            </TouchableOpacity>
          </View>
        )}

        {/* ── Scope / Team Selector for Managers / VPs ── */}
        {isManagerOrVP && (
          <View style={styles.teamCard}>
            <View style={styles.teamHeaderRow}>
              <View style={styles.teamBadge}>
                <MaterialCommunityIcons
                  name={reportData.scope === "all" ? "shield-crown" : "account-group"}
                  size={16}
                  color={PURPLE}
                />
                <Text style={styles.teamBadgeText}>
                  {reportData.scope === "all" ? "Company-wide Scope" : "Team Scope"}
                </Text>
              </View>
              <Text style={styles.teamCountText}>
                {reportData.team_members.length} Subordinates
              </Text>
            </View>

            <TouchableOpacity
              style={styles.dropdownBtn}
              onPress={() => setShowTeamModal(true)}
              activeOpacity={0.85}
            >
              <MaterialIcons name="person-pin" size={22} color={PURPLE} />
              <Text style={styles.dropdownBtnText} numberOfLines={1}>
                {selectedMemberName}
              </Text>
              <MaterialIcons name="keyboard-arrow-down" size={22} color="#64748B" />
            </TouchableOpacity>
          </View>
        )}

        {/* ── Selected Period Label ── */}
        <View style={styles.metaRow}>
          <Text style={styles.metaLabel}>Reporting Window:</Text>
          <View style={styles.periodBadge}>
            <MaterialIcons name="access-time" size={14} color="#1E1B4B" />
            <Text style={styles.periodBadgeText}>
              {reportData?.period_label || "Loading..."}
            </Text>
          </View>
        </View>

        {loading ? (
          <View style={styles.loaderWrap}>
            <ActivityIndicator size="large" color={PURPLE} />
            <Text style={styles.loaderText}>Calculating metrics...</Text>
          </View>
        ) : (
          <>
            {/* ── KPI Grid ── */}
            <View style={styles.kpiGrid}>
              {/* Card 1: Customer Visits */}
              <View style={[styles.kpiCard, { borderLeftColor: "#3B82F6" }]}>
                <View style={[styles.kpiIconWrap, { backgroundColor: "#DBEAFE" }]}>
                  <MaterialCommunityIcons name="map-marker-check" size={22} color="#3B82F6" />
                </View>
                <Text style={styles.kpiNumber}>{kpis?.visits_count ?? 0}</Text>
                <Text style={styles.kpiTitle}>Visits Completed</Text>
                <Text style={styles.kpiSub}>
                  {kpis?.completed_visits_count ?? 0} full check-outs
                </Text>
              </View>

              {/* Card 2: Expenses */}
              <View style={[styles.kpiCard, { borderLeftColor: "#EF4444" }]}>
                <View style={[styles.kpiIconWrap, { backgroundColor: "#FEE2E2" }]}>
                  <MaterialCommunityIcons name="receipt-text-outline" size={22} color="#EF4444" />
                </View>
                <Text style={styles.kpiNumber}>
                  ₹ {Number(kpis?.expenses_total || 0).toLocaleString("en-IN")}
                </Text>
                <Text style={styles.kpiTitle}>Total Expenses</Text>
                <Text style={styles.kpiSub}>
                  ₹ {Number(kpis?.approved_expenses_total || 0).toLocaleString("en-IN")} approved
                </Text>
              </View>

              {/* Card 3: Distance & Attendance (Full Width) */}
              <View style={[styles.kpiCardFull, { borderLeftColor: "#F59E0B" }]}>
                <View style={styles.kpiFullRow}>
                  <View style={[styles.kpiIconWrap, { backgroundColor: "#FEF3C7", marginBottom: 0 }]}>
                    <MaterialCommunityIcons name="car-speed-limiter" size={22} color="#F59E0B" />
                  </View>
                  <View style={{ flex: 1, marginLeft: 12 }}>
                    <Text style={styles.kpiNumber}>{kpis?.km_traveled ?? 0} KM</Text>
                    <Text style={styles.kpiTitle}>Distance Traveled</Text>
                  </View>
                  <View style={styles.kpiFullSubBadge}>
                    <MaterialCommunityIcons name="calendar-check" size={14} color="#B45309" />
                    <Text style={styles.kpiFullSubText}>
                      {isManagerOrVP && selectedUserId === "all"
                        ? `${kpis?.active_fieldworkers ?? 0} active today`
                        : `${kpis?.working_days ?? 0} active days`}
                    </Text>
                  </View>
                </View>
              </View>
            </View>

            {/* ── Breakdown / Visits Section ── */}
            <View style={styles.sectionCard}>
              {reportData?.is_multi_user ? (
                // Multi-User Switcher Tabs
                <View style={styles.tabSwitcher}>
                  <TouchableOpacity
                    style={[styles.tabBtn, activeTab === "visits" && styles.tabBtnActive]}
                    activeOpacity={0.8}
                    onPress={() => setActiveTab("visits")}
                  >
                    <MaterialCommunityIcons
                      name="history"
                      size={16}
                      color={activeTab === "visits" ? PURPLE : "#64748B"}
                    />
                    <Text
                      style={[
                        styles.tabBtnText,
                        activeTab === "visits" && styles.tabBtnTextActive,
                      ]}
                    >
                      Visits & Activity ({((reportData?.activities ?? [])).length})
                    </Text>
                  </TouchableOpacity>

                  <TouchableOpacity
                    style={[styles.tabBtn, activeTab === "team" && styles.tabBtnActive]}
                    activeOpacity={0.8}
                    onPress={() => setActiveTab("team")}
                  >
                    <MaterialCommunityIcons
                      name="account-group-outline"
                      size={16}
                      color={activeTab === "team" ? PURPLE : "#64748B"}
                    />
                    <Text
                      style={[
                        styles.tabBtnText,
                        activeTab === "team" && styles.tabBtnTextActive,
                      ]}
                    >
                      Team Summary ({((reportData?.breakdown ?? []) as TeamMemberBreakdown[]).length})
                    </Text>
                  </TouchableOpacity>
                </View>
              ) : (
                <View style={styles.sectionHeaderRow}>
                  <MaterialCommunityIcons name="history" size={20} color={PURPLE} />
                  <Text style={styles.sectionTitle}>
                    Activity & Visits History ({(reportData?.activities ?? (reportData?.breakdown as RecentActivityItem[]) ?? []).length})
                  </Text>
                </View>
              )}

              {/* Multi-User: Team Summary View */}
              {reportData?.is_multi_user && activeTab === "team" ? (
                (reportData.breakdown as TeamMemberBreakdown[]).length === 0 ? (
                  <View style={styles.emptyWrap}>
                    <MaterialCommunityIcons name="chart-box-outline" size={44} color="#CBD5E1" />
                    <Text style={styles.emptyTitle}>No records in this period</Text>
                    <Text style={styles.emptySub}>
                      Try switching to another date filter like This Month or Yearly.
                    </Text>
                  </View>
                ) : (
                  (reportData.breakdown as TeamMemberBreakdown[]).map((emp) => (
                    <TouchableOpacity
                      key={emp.user_id}
                      style={styles.empCard}
                      activeOpacity={0.85}
                      onPress={() => {
                        setSelectedUserId(emp.user_id);
                        setActiveTab("visits");
                      }}
                    >
                      {/* Top Header: Avatar, Name & Position, Status Pill */}
                      <View style={styles.empCardHeader}>
                        <View style={styles.empAvatar}>
                          <Text style={styles.empAvatarText}>
                            {emp.name.substring(0, 2).toUpperCase()}
                          </Text>
                        </View>

                        <View style={styles.empInfo}>
                          <Text style={styles.empName} numberOfLines={1}>
                            {emp.name}
                          </Text>
                          <Text style={styles.empPosition} numberOfLines={1}>
                            {emp.position || "Field Worker"}
                            {emp.employee_id ? ` • ${emp.employee_id}` : ""}
                          </Text>
                        </View>

                        <View
                          style={[
                            styles.statusPill,
                            emp.day_status === "Working" && styles.statusWorking,
                            emp.day_status === "Completed" && styles.statusCompleted,
                          ]}
                        >
                          <View
                            style={[
                              styles.statusDot,
                              emp.day_status === "Working" && { backgroundColor: "#2563EB" },
                              emp.day_status === "Completed" && { backgroundColor: "#059669" },
                              emp.day_status === "Not Started" && { backgroundColor: "#94A3B8" },
                            ]}
                          />
                          <Text
                            style={[
                              styles.statusPillText,
                              emp.day_status === "Working" && styles.statusWorkingText,
                              emp.day_status === "Completed" && styles.statusCompletedText,
                            ]}
                          >
                            {emp.day_status}
                          </Text>
                        </View>
                      </View>

                      {/* Metrics: Visits and Expenses (Orders Hidden) */}
                      <View style={styles.empMetricsRow}>
                        <View style={styles.empMetricCol}>
                          <View style={styles.empMetricHeader}>
                            <MaterialCommunityIcons name="map-marker-check-outline" size={15} color="#3B82F6" />
                            <Text style={styles.empMetricLabel}>Visits</Text>
                          </View>
                          <Text style={styles.empMetricVal}>{emp.visits_count}</Text>
                        </View>

                        <View style={styles.empMetricDivider} />

                        <View style={styles.empMetricCol}>
                          <View style={styles.empMetricHeader}>
                            <MaterialCommunityIcons name="receipt-text-outline" size={15} color="#EA580C" />
                            <Text style={styles.empMetricLabel}>Expenses</Text>
                          </View>
                          <Text style={styles.empMetricVal}>₹ {(emp.expenses_total || 0).toLocaleString("en-IN")}</Text>
                        </View>
                      </View>

                      {/* Footer Hint */}
                      <View style={styles.empCardFooter}>
                        <Text style={styles.empCardFooterText}>View Worker's Visits</Text>
                        <MaterialIcons name="chevron-right" size={16} color={PURPLE} />
                      </View>
                    </TouchableOpacity>
                  ))
                )
              ) : (
                // Visits & Activity List with Sale / Visit Notes
                (() => {
                  const items: RecentActivityItem[] =
                    reportData?.activities ??
                    (!reportData?.is_multi_user ? (reportData?.breakdown as RecentActivityItem[]) : []);

                  if (items.length === 0) {
                    return (
                      <View style={styles.emptyWrap}>
                        <MaterialCommunityIcons name="storefront-outline" size={44} color="#CBD5E1" />
                        <Text style={styles.emptyTitle}>No visits found in this period</Text>
                        <Text style={styles.emptySub}>
                          Visits and consultation notes logged by workers will show up here.
                        </Text>
                      </View>
                    );
                  }

                  return items.map((act) => (
                    <View key={act.id} style={styles.visitCard}>
                      {/* Header: Party Name & Status */}
                      <View style={styles.visitHeaderRow}>
                        <View style={styles.visitIconWrap}>
                          <MaterialCommunityIcons name="storefront-outline" size={20} color={PURPLE} />
                        </View>
                        <View style={styles.visitPartyInfo}>
                          <Text style={styles.visitPartyName} numberOfLines={1}>
                            {act.party_name || act.title}
                          </Text>
                          {act.party_city ? (
                            <Text style={styles.visitPartyCity}>📍 {act.party_city}</Text>
                          ) : null}
                        </View>
                        <View
                          style={[
                            styles.activityBadge,
                            act.is_success ? styles.badgeSuccess : styles.badgePending,
                          ]}
                        >
                          <MaterialCommunityIcons
                            name={act.is_success ? "check-circle" : "clock-outline"}
                            size={13}
                            color={act.is_success ? "#059669" : "#D97706"}
                          />
                          <Text
                            style={[
                              styles.activityBadgeText,
                              act.is_success ? styles.badgeSuccessText : styles.badgePendingText,
                            ]}
                          >
                            {act.status}
                          </Text>
                        </View>
                      </View>

                      {/* Meta Info Grid: Worker, Time, Purpose */}
                      <View style={styles.visitMetaGrid}>
                        {act.user_name ? (
                          <View style={styles.visitMetaItem}>
                            <MaterialCommunityIcons name="account" size={13} color="#64748B" />
                            <Text style={styles.visitMetaText} numberOfLines={1}>
                              {act.user_name}
                            </Text>
                          </View>
                        ) : null}

                        <View style={styles.visitMetaItem}>
                          <MaterialCommunityIcons name="calendar-clock" size={13} color="#64748B" />
                          <Text style={styles.visitMetaText}>
                            {act.date} • In: {act.time}
                            {act.check_out_time ? ` • Out: ${act.check_out_time}` : ""}
                          </Text>
                        </View>

                        {act.purpose ? (
                          <View style={styles.visitMetaItem}>
                            <MaterialCommunityIcons name="target" size={13} color="#64748B" />
                            <Text style={styles.visitMetaText} numberOfLines={1}>
                              {act.purpose}
                            </Text>
                          </View>
                        ) : null}
                      </View>

                      {/* Sale / Visit Notes Box */}
                      <View style={styles.notesContainer}>
                        <View style={styles.notesHeader}>
                          <MaterialCommunityIcons name="comment-text-outline" size={13} color={PURPLE} />
                          <Text style={styles.notesLabel}>Sale / Visit Notes</Text>
                        </View>
                        {act.notes && act.notes.trim() ? (
                          <Text style={styles.notesContent}>{act.notes.trim()}</Text>
                        ) : (
                          <Text style={styles.notesPlaceholder}>— No notes recorded for this visit —</Text>
                        )}
                      </View>

                      {/* Check-In Photo Thumbnail */}
                      {act.check_in_photo ? (
                        <View style={styles.photoRow}>
                          <TouchableOpacity
                            style={styles.photoBtn}
                            activeOpacity={0.8}
                            onPress={() => setPreviewPhoto(act.check_in_photo!)}
                          >
                            <CachedImage
                              source={{ uri: act.check_in_photo }}
                              style={styles.photoThumbnail}
                            />
                            <View style={styles.photoBadge}>
                              <MaterialCommunityIcons name="camera" size={11} color="#fff" />
                              <Text style={styles.photoBadgeText}>Check-in Photo</Text>
                            </View>
                          </TouchableOpacity>
                        </View>
                      ) : null}
                    </View>
                  ));
                })()
              )}
            </View>
          </>
        )}
      </ScrollView>

      {/* ── Team Member Selection Modal ── */}
      <Modal visible={showTeamModal} transparent animationType="slide">
        <View style={styles.modalOverlay}>
          <View style={styles.modalSheet}>
            <ScreenHeader
              title="Select Team Member"
              onBack={() => setShowTeamModal(false)}
              bgColor={PURPLE}
            />

            <FlatList
              data={[{ id: "all" as const, name: "All Team Members (Aggregate Totals)" }, ...(reportData?.team_members || [])]}
              keyExtractor={(item) => String(item.id)}
              contentContainerStyle={{ padding: 14 }}
              renderItem={({ item }) => {
                const isSelected = selectedUserId === item.id;
                return (
                  <TouchableOpacity
                    style={[styles.memberItem, isSelected && styles.memberItemSelected]}
                    onPress={() => {
                      setSelectedUserId(item.id);
                      setShowTeamModal(false);
                    }}
                  >
                    <View style={styles.memberItemIcon}>
                      <MaterialIcons
                        name={item.id === "all" ? "groups" : "person"}
                        size={20}
                        color={isSelected ? "#FFFFFF" : PURPLE}
                      />
                    </View>
                    <View style={{ flex: 1 }}>
                      <Text style={[styles.memberItemName, isSelected && styles.memberItemNameSelected]}>
                        {item.name}
                      </Text>
                      {"position" in item && item.position ? (
                        <Text style={styles.memberItemSub}>{item.position}</Text>
                      ) : null}
                    </View>
                    {isSelected && (
                      <MaterialIcons name="check-circle" size={20} color={PURPLE} />
                    )}
                  </TouchableOpacity>
                );
              }}
            />
          </View>
        </View>
      </Modal>

      {/* ── Date Picker Modals for Custom Range ── */}
      <DatePickerModal
        visible={datePickerType === "start"}
        value={customStartDate}
        title="Select Start Date"
        onChange={(d) => {
          setCustomStartDate(d);
          setDatePickerType(null);
        }}
        onClose={() => setDatePickerType(null)}
      />

      <DatePickerModal
        visible={datePickerType === "end"}
        value={customEndDate}
        title="Select End Date"
        onChange={(d) => {
          setCustomEndDate(d);
          setDatePickerType(null);
        }}
        onClose={() => setDatePickerType(null)}
      />

      {/* ── Photo Preview Lightbox Modal ── */}
      <Modal visible={!!previewPhoto} transparent animationType="fade">
        <View style={styles.lightboxOverlay}>
          <TouchableOpacity
            style={styles.lightboxCloseBtn}
            onPress={() => setPreviewPhoto(null)}
          >
            <MaterialIcons name="close" size={28} color="#fff" />
          </TouchableOpacity>
          {previewPhoto ? (
            <CachedImage
              source={{ uri: previewPhoto }}
              style={styles.lightboxImage}
              resizeMode="contain"
            />
          ) : null}
        </View>
      </Modal>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: "#F4F4F8",
  },
  scrollContent: {
    padding: 12,
  },
  periodPillsWrap: {
    paddingVertical: 8,
    gap: 8,
  },
  periodChip: {
    flexDirection: "row",
    alignItems: "center",
    gap: 6,
    backgroundColor: "#FFFFFF",
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: 20,
    borderWidth: 1,
    borderColor: "#E2E8F0",
    shadowColor: "#000",
    shadowOpacity: 0.03,
    shadowRadius: 4,
    elevation: 1,
  },
  periodChipActive: {
    backgroundColor: PURPLE,
    borderColor: PURPLE,
  },
  periodChipText: {
    fontSize: 12,
    fontWeight: "700",
    color: "#475569",
  },
  periodChipTextActive: {
    color: "#FFFFFF",
  },
  customDateBox: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    backgroundColor: "#FFFFFF",
    borderRadius: 12,
    padding: 12,
    marginTop: 8,
    borderWidth: 1,
    borderColor: "#E2E8F0",
  },
  datePickerBtn: {
    flex: 1,
  },
  datePickerLabel: {
    fontSize: 10,
    fontWeight: "700",
    color: "#64748B",
    marginBottom: 4,
  },
  datePickerRow: {
    flexDirection: "row",
    alignItems: "center",
    gap: 6,
  },
  datePickerVal: {
    fontSize: 13,
    fontWeight: "800",
    color: "#1E293B",
  },
  teamCard: {
    backgroundColor: "#FFFFFF",
    borderRadius: 14,
    padding: 12,
    marginTop: 10,
    borderWidth: 1,
    borderColor: "#E2E8F0",
    elevation: 2,
    shadowColor: "#000",
    shadowOpacity: 0.04,
    shadowRadius: 6,
  },
  teamHeaderRow: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    marginBottom: 8,
  },
  teamBadge: {
    flexDirection: "row",
    alignItems: "center",
    gap: 5,
    backgroundColor: "#EDE9FE",
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 6,
  },
  teamBadgeText: {
    fontSize: 11,
    fontWeight: "800",
    color: PURPLE,
  },
  teamCountText: {
    fontSize: 11,
    fontWeight: "600",
    color: "#64748B",
  },
  dropdownBtn: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#F8FAFC",
    borderWidth: 1,
    borderColor: "#CBD5E1",
    borderRadius: 10,
    paddingHorizontal: 12,
    paddingVertical: 10,
    gap: 8,
  },
  dropdownBtnText: {
    flex: 1,
    fontSize: 14,
    fontWeight: "800",
    color: "#1E293B",
  },
  metaRow: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    marginTop: 12,
    marginBottom: 10,
    paddingHorizontal: 4,
  },
  metaLabel: {
    fontSize: 12,
    fontWeight: "600",
    color: "#64748B",
  },
  periodBadge: {
    flexDirection: "row",
    alignItems: "center",
    gap: 4,
    backgroundColor: "#E2E8F0",
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 6,
  },
  periodBadgeText: {
    fontSize: 11,
    fontWeight: "700",
    color: "#1E1B4B",
  },
  loaderWrap: {
    alignItems: "center",
    justifyContent: "center",
    paddingVertical: 40,
  },
  loaderText: {
    marginTop: 10,
    fontSize: 13,
    color: "#64748B",
    fontWeight: "600",
  },
  kpiGrid: {
    flexDirection: "row",
    flexWrap: "wrap",
    gap: 10,
  },
  kpiCard: {
    width: "48.5%",
    backgroundColor: "#FFFFFF",
    borderRadius: 14,
    padding: 12,
    borderWidth: 1,
    borderColor: "#E2E8F0",
    borderLeftWidth: 4,
    elevation: 2,
    shadowColor: "#000",
    shadowOpacity: 0.04,
    shadowRadius: 6,
  },
  kpiCardFull: {
    width: "100%",
    backgroundColor: "#FFFFFF",
    borderRadius: 14,
    padding: 12,
    borderWidth: 1,
    borderColor: "#E2E8F0",
    borderLeftWidth: 4,
    elevation: 2,
    shadowColor: "#000",
    shadowOpacity: 0.04,
    shadowRadius: 6,
  },
  kpiFullRow: {
    flexDirection: "row",
    alignItems: "center",
  },
  kpiFullSubBadge: {
    flexDirection: "row",
    alignItems: "center",
    gap: 4,
    backgroundColor: "#FEF3C7",
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 6,
  },
  kpiFullSubText: {
    fontSize: 11,
    fontWeight: "700",
    color: "#B45309",
  },
  kpiIconWrap: {
    width: 36,
    height: 36,
    borderRadius: 9,
    alignItems: "center",
    justifyContent: "center",
    marginBottom: 8,
  },
  kpiNumber: {
    fontSize: 17,
    fontWeight: "900",
    color: "#0F172A",
  },
  kpiTitle: {
    fontSize: 12,
    fontWeight: "700",
    color: "#475569",
    marginTop: 2,
  },
  kpiSub: {
    fontSize: 10,
    fontWeight: "600",
    color: "#94A3B8",
    marginTop: 4,
  },
  sectionCard: {
    backgroundColor: "#FFFFFF",
    borderRadius: 14,
    padding: 14,
    marginTop: 14,
    borderWidth: 1,
    borderColor: "#E2E8F0",
    elevation: 2,
    shadowColor: "#000",
    shadowOpacity: 0.04,
    shadowRadius: 6,
  },
  sectionHeaderRow: {
    flexDirection: "row",
    alignItems: "center",
    gap: 8,
    marginBottom: 12,
    paddingBottom: 8,
    borderBottomWidth: 1,
    borderBottomColor: "#F1F5F9",
  },
  sectionTitle: {
    fontSize: 14,
    fontWeight: "800",
    color: "#1E293B",
  },
  emptyWrap: {
    alignItems: "center",
    paddingVertical: 24,
  },
  emptyTitle: {
    fontSize: 14,
    fontWeight: "700",
    color: "#475569",
    marginTop: 8,
  },
  emptySub: {
    fontSize: 12,
    color: "#94A3B8",
    textAlign: "center",
    marginTop: 4,
    maxWidth: 240,
  },
  empCard: {
    backgroundColor: "#FFFFFF",
    borderRadius: 12,
    padding: 12,
    marginBottom: 10,
    borderWidth: 1,
    borderColor: "#E2E8F0",
    shadowColor: "#000",
    shadowOpacity: 0.03,
    shadowRadius: 5,
    elevation: 1,
  },
  empCardHeader: {
    flexDirection: "row",
    alignItems: "center",
    marginBottom: 10,
  },
  empAvatar: {
    width: 38,
    height: 38,
    borderRadius: 19,
    backgroundColor: "#EDE9FE",
    alignItems: "center",
    justifyContent: "center",
    marginRight: 10,
  },
  empAvatarText: {
    fontSize: 14,
    fontWeight: "900",
    color: PURPLE,
  },
  empInfo: {
    flex: 1,
    marginRight: 8,
  },
  empName: {
    fontSize: 14,
    fontWeight: "800",
    color: "#1E293B",
  },
  empPosition: {
    fontSize: 11,
    color: "#64748B",
    marginTop: 2,
  },
  statusPill: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#F1F5F9",
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 6,
  },
  statusDot: {
    width: 6,
    height: 6,
    borderRadius: 3,
    marginRight: 5,
    backgroundColor: "#94A3B8",
  },
  statusWorking: {
    backgroundColor: "#DBEAFE",
  },
  statusCompleted: {
    backgroundColor: "#D1FAE5",
  },
  statusPillText: {
    fontSize: 10,
    fontWeight: "700",
    color: "#64748B",
  },
  statusWorkingText: {
    color: "#2563EB",
  },
  statusCompletedText: {
    color: "#059669",
  },
  empMetricsRow: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#F8FAFC",
    borderRadius: 8,
    paddingVertical: 8,
    paddingHorizontal: 12,
  },
  empMetricCol: {
    flex: 1,
    alignItems: "center",
  },
  empMetricHeader: {
    flexDirection: "row",
    alignItems: "center",
    gap: 4,
    marginBottom: 2,
  },
  empMetricLabel: {
    fontSize: 10,
    color: "#64748B",
    fontWeight: "600",
  },
  empMetricVal: {
    fontSize: 13,
    fontWeight: "800",
    color: "#0F172A",
  },
  empMetricDivider: {
    width: 1,
    height: 24,
    backgroundColor: "#E2E8F0",
    marginHorizontal: 8,
  },
  empCardFooter: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "flex-end",
    gap: 4,
    marginTop: 8,
    paddingTop: 6,
    borderTopWidth: 1,
    borderTopColor: "#F1F5F9",
  },
  empCardFooterText: {
    fontSize: 10,
    fontWeight: "700",
    color: PURPLE,
  },
  activityBadge: {
    flexDirection: "row",
    alignItems: "center",
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 6,
    alignSelf: "flex-start",
  },
  badgeSuccess: {
    backgroundColor: "#D1FAE5",
  },
  badgePending: {
    backgroundColor: "#FEF3C7",
  },
  activityBadgeText: {
    fontSize: 10,
    fontWeight: "700",
    marginLeft: 4,
  },
  badgeSuccessText: {
    color: "#059669",
  },
  badgePendingText: {
    color: "#D97706",
  },
  modalOverlay: {
    flex: 1,
    backgroundColor: "rgba(0,0,0,0.5)",
    justifyContent: "flex-end",
  },
  modalSheet: {
    flex: 1,
    backgroundColor: "#F4F4F8",
  },
  memberItem: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#FFFFFF",
    padding: 14,
    borderRadius: 12,
    marginBottom: 8,
    borderWidth: 1,
    borderColor: "#E2E8F0",
  },
  memberItemSelected: {
    borderColor: PURPLE,
    backgroundColor: "#F5F3FF",
  },
  memberItemIcon: {
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: "#EDE9FE",
    alignItems: "center",
    justifyContent: "center",
    marginRight: 10,
  },
  memberItemName: {
    fontSize: 14,
    fontWeight: "800",
    color: "#1E293B",
  },
  memberItemNameSelected: {
    color: PURPLE,
  },
  memberItemSub: {
    fontSize: 11,
    color: "#64748B",
    marginTop: 2,
  },
  tabSwitcher: {
    flexDirection: "row",
    backgroundColor: "#F1F5F9",
    borderRadius: 10,
    padding: 3,
    marginBottom: 12,
  },
  tabBtn: {
    flex: 1,
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    paddingVertical: 8,
    borderRadius: 8,
    gap: 6,
  },
  tabBtnActive: {
    backgroundColor: "#FFFFFF",
    shadowColor: "#000",
    shadowOpacity: 0.08,
    shadowRadius: 4,
    elevation: 2,
  },
  tabBtnText: {
    fontSize: 11,
    fontWeight: "700",
    color: "#64748B",
  },
  tabBtnTextActive: {
    color: PURPLE,
    fontWeight: "800",
  },
  visitCard: {
    backgroundColor: "#FFFFFF",
    borderRadius: 12,
    padding: 12,
    marginBottom: 10,
    borderWidth: 1,
    borderColor: "#E2E8F0",
    shadowColor: "#000",
    shadowOpacity: 0.03,
    shadowRadius: 6,
    elevation: 1,
  },
  visitHeaderRow: {
    flexDirection: "row",
    alignItems: "center",
    marginBottom: 8,
  },
  visitIconWrap: {
    width: 34,
    height: 34,
    borderRadius: 10,
    backgroundColor: "#EDE9FE",
    alignItems: "center",
    justifyContent: "center",
    marginRight: 10,
  },
  visitPartyInfo: {
    flex: 1,
    marginRight: 8,
  },
  visitPartyName: {
    fontSize: 14,
    fontWeight: "800",
    color: "#1E293B",
  },
  visitPartyCity: {
    fontSize: 11,
    color: "#64748B",
    marginTop: 1,
  },
  visitMetaGrid: {
    flexDirection: "row",
    flexWrap: "wrap",
    gap: 8,
    backgroundColor: "#F8FAFC",
    paddingHorizontal: 8,
    paddingVertical: 6,
    borderRadius: 8,
    marginBottom: 8,
  },
  visitMetaItem: {
    flexDirection: "row",
    alignItems: "center",
    gap: 4,
    marginRight: 6,
  },
  visitMetaText: {
    fontSize: 11,
    color: "#475569",
    fontWeight: "600",
  },
  notesContainer: {
    backgroundColor: "#F5F3FF",
    borderLeftWidth: 3,
    borderLeftColor: PURPLE,
    borderRadius: 6,
    paddingHorizontal: 10,
    paddingVertical: 8,
    marginTop: 2,
  },
  notesHeader: {
    flexDirection: "row",
    alignItems: "center",
    gap: 5,
    marginBottom: 4,
  },
  notesLabel: {
    fontSize: 10,
    fontWeight: "800",
    color: PURPLE,
    textTransform: "uppercase",
    letterSpacing: 0.5,
  },
  notesContent: {
    fontSize: 12,
    color: "#1E1B4B",
    lineHeight: 18,
    fontWeight: "600",
  },
  notesPlaceholder: {
    fontSize: 11,
    color: "#94A3B8",
    fontStyle: "italic",
  },
  photoRow: {
    marginTop: 8,
    alignItems: "flex-start",
  },
  photoBtn: {
    position: "relative",
    borderRadius: 8,
    overflow: "hidden",
  },
  photoThumbnail: {
    width: 80,
    height: 60,
    borderRadius: 8,
    backgroundColor: "#E2E8F0",
  },
  photoBadge: {
    position: "absolute",
    bottom: 0,
    left: 0,
    right: 0,
    backgroundColor: "rgba(0,0,0,0.6)",
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    paddingVertical: 2,
    gap: 3,
  },
  photoBadgeText: {
    color: "#fff",
    fontSize: 9,
    fontWeight: "700",
  },
  lightboxOverlay: {
    flex: 1,
    backgroundColor: "rgba(0,0,0,0.92)",
    justifyContent: "center",
    alignItems: "center",
  },
  lightboxCloseBtn: {
    position: "absolute",
    top: 40,
    right: 20,
    padding: 10,
    zIndex: 10,
  },
  lightboxImage: {
    width: "90%",
    height: "80%",
  },
});
