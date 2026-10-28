import { MaterialCommunityIcons, MaterialIcons } from "@expo/vector-icons";
import { useNavigation } from "@react-navigation/native";
import type { NativeStackNavigationProp } from "@react-navigation/native-stack";
import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import {
  ActivityIndicator,
  Alert,
  Image,
  Modal,
  Platform,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from "react-native";
import { SafeAreaView, useSafeAreaInsets } from "react-native-safe-area-context";

import ScreenHeader from "../../components/ScreenHeader";
import {
  actOnDayStart,
  actOnExpense,
  getApprovalCounts,
  getApprovalTeamMembers,
  getDayStartApprovals,
  getExpenseApprovals,
  updateDayStartApproval,
  updateExpenseApproval,
  type ApprovalCounts,
  type DayStartApprovalItem,
  type ExpenseApprovalItem,
  type TeamMember,
} from "../../api/approvals";
import { getMasterVehicleTypes } from "../../api/master";
import { getExpenseCategories, type ExpenseCategory } from "../../api/expense";
import type { AppStackParamList } from "../../navigation/RootNavigator";

type Nav = NativeStackNavigationProp<AppStackParamList>;

const PURPLE = "#7367F0";
const BRAND_BG = "#F8F9FA";

export default function ApprovalsHubScreen() {
  const navigation = useNavigation<Nav>();
  const insets = useSafeAreaInsets();
  const drawerBottomPadding = Math.max(insets.bottom, 20) + 12;

  // Active Main Tab: 'day_start' | 'expenses'
  const [activeTab, setActiveTab] = useState<"day_start" | "expenses">("day_start");

  // Approval badge counts
  const [counts, setCounts] = useState<ApprovalCounts>({
    can_approve: false,
    can_approve_day: false,
    can_temp_approve_expense: false,
    can_final_approve_expense: false,
    pending_day_starts: 0,
    pending_expenses_l1: 0,
    pending_expenses_l2: 0,
    total_pending: 0,
  });

  // ─── Day Start (Tour Plans) State ─────────────────────────────────────────────
  const [dayStarts, setDayStarts] = useState<DayStartApprovalItem[]>([]);
  const [dsPage, setDsPage] = useState(1);
  const [dsPerPage, setDsPerPage] = useState(15);
  const [dsLastPage, setDsLastPage] = useState(1);
  const [dsTotal, setDsTotal] = useState(0);
  const [dsLoading, setDsLoading] = useState(true);
  const [dsRefreshing, setDsRefreshing] = useState(false);
  const [dsStatus, setDsStatus] = useState<"pending" | "approved" | "rejected" | "all">("pending");

  // ─── Expense State ────────────────────────────────────────────────────────────
  const [expenses, setExpenses] = useState<ExpenseApprovalItem[]>([]);
  const [expPage, setExpPage] = useState(1);
  const [expPerPage, setExpPerPage] = useState(15);
  const [expLastPage, setExpLastPage] = useState(1);
  const [expTotal, setExpTotal] = useState(0);
  const [expLoading, setExpLoading] = useState(true);
  const [expRefreshing, setExpRefreshing] = useState(false);
  const [expStatus, setExpStatus] = useState<"pending" | "temp_approved" | "approved" | "rejected" | "all">("pending");

  // Search input state (immediate UI text + 350ms debounced value for backend query)
  const [searchInput, setSearchInput] = useState("");
  const [debouncedSearch, setDebouncedSearch] = useState("");

  // 350ms Debounce effect on searchInput -> debouncedSearch
  useEffect(() => {
    const timer = setTimeout(() => {
      setDebouncedSearch(searchInput.trim());
    }, 350);
    return () => clearTimeout(timer);
  }, [searchInput]);

  // ─── Details Drawer / Modal State ─────────────────────────────────────────────
  const [selectedDayStart, setSelectedDayStart] = useState<DayStartApprovalItem | null>(null);
  const [selectedExpense, setSelectedExpense] = useState<ExpenseApprovalItem | null>(null);

  // ─── Action Confirmation Modal State ──────────────────────────────────────────
  const [actionModalVisible, setActionModalVisible] = useState(false);
  const [actionTarget, setActionTarget] = useState<{
    type: "day_start" | "expense";
    id: number;
    action: "approve" | "reject" | "temp_approve" | "final_approve" | "manager_reject";
    title: string;
    description: string;
    requiresReason: boolean;
  } | null>(null);
  const [actionNote, setActionNote] = useState("");
  const [submittingAction, setSubmittingAction] = useState(false);

  // ─── Image Viewer Modal State ────────────────────────────────────────────────
  const [photoViewerVisible, setPhotoViewerVisible] = useState(false);
  const [selectedPhotoUrl, setSelectedPhotoUrl] = useState<string | null>(null);
  const [selectedPhotoTitle, setSelectedPhotoTitle] = useState<string>("");

  // ─── Master Data Lists for Edit Modals ───────────────────────────────────────
  const [vehicleTypesList, setVehicleTypesList] = useState<string[]>([
    "2 Wheeler",
    "4 Wheeler",
    "Public Transport",
    "Company Vehicle",
  ]);
  const [expenseCategoriesList, setExpenseCategoriesList] = useState<ExpenseCategory[]>([]);

  // ─── Team Members / Employee Filter State ────────────────────────────────────
  const [teamMembers, setTeamMembers] = useState<TeamMember[]>([]);
  const [selectedUserId, setSelectedUserId] = useState<number | "all">("all");
  const [employeeModalVisible, setEmployeeModalVisible] = useState(false);
  const [employeeSearch, setEmployeeSearch] = useState("");

  // ─── Day Start Edit Modal State ──────────────────────────────────────────────
  const [editDayStartModalVisible, setEditDayStartModalVisible] = useState(false);
  const [editingDayStart, setEditingDayStart] = useState<DayStartApprovalItem | null>(null);
  const [editDsOpeningKm, setEditDsOpeningKm] = useState("");
  const [editDsClosingKm, setEditDsClosingKm] = useState("");
  const [editDsVehicleType, setEditDsVehicleType] = useState("");
  const [editDsVehicleNo, setEditDsVehicleNo] = useState("");
  const [editDsVisitPlace, setEditDsVisitPlace] = useState("");
  const [editDsTourPurpose, setEditDsTourPurpose] = useState("");
  const [editDsRemarks, setEditDsRemarks] = useState("");
  const [editDsReason, setEditDsReason] = useState("");
  const [savingDsEdit, setSavingDsEdit] = useState(false);

  // ─── Expense Edit Modal State ────────────────────────────────────────────────
  const [editExpenseModalVisible, setEditExpenseModalVisible] = useState(false);
  const [editingExpense, setEditingExpense] = useState<ExpenseApprovalItem | null>(null);
  const [editExpAmount, setEditExpAmount] = useState("");
  const [editExpCategoryId, setEditExpCategoryId] = useState<number | null>(null);
  const [editExpDate, setEditExpDate] = useState("");
  const [editExpReason, setEditExpReason] = useState("");
  const [savingExpEdit, setSavingExpEdit] = useState(false);

  // ─── Load Badge Counts ───────────────────────────────────────────────────────
  const loadCounts = useCallback(async () => {
    try {
      const res = await getApprovalCounts();
      if (res.success && res.data) {
        setCounts(res.data);
      }
    } catch (err) {
      console.warn("Failed to fetch approval counts:", err);
    }
  }, []);

  // ─── Fetch Day Starts ────────────────────────────────────────────────────────
  const fetchDayStarts = useCallback(
    async (
      pageToLoad: number,
      statusFilter: string,
      search: string,
      userId: number | "all",
      perPageCount: number,
      isRefresh = false
    ) => {
      if (isRefresh) setDsRefreshing(true);
      else setDsLoading(true);

      try {
        const res = await getDayStartApprovals({
          page: pageToLoad,
          per_page: perPageCount,
          status: statusFilter as any,
          user_id: userId !== "all" ? userId : undefined,
          search: search.trim(),
        });

        if (res.success) {
          setDayStarts(res.data);
          setDsPage(res.current_page ?? pageToLoad);
          setDsLastPage(res.last_page ?? 1);
          setDsTotal(res.total ?? res.data.length);
        }
      } catch (err: any) {
        console.warn("Error fetching day starts:", err);
      } finally {
        setDsLoading(false);
        setDsRefreshing(false);
      }
    },
    []
  );

  // ─── Fetch Expenses ──────────────────────────────────────────────────────────
  const fetchExpenses = useCallback(
    async (
      pageToLoad: number,
      statusFilter: string,
      search: string,
      userId: number | "all",
      perPageCount: number,
      isRefresh = false
    ) => {
      if (isRefresh) setExpRefreshing(true);
      else setExpLoading(true);

      try {
        const res = await getExpenseApprovals({
          page: pageToLoad,
          per_page: perPageCount,
          status: statusFilter as any,
          user_id: userId !== "all" ? userId : undefined,
          search: search.trim(),
        });

        if (res.success) {
          setExpenses(res.data);
          setExpPage(res.current_page ?? pageToLoad);
          setExpLastPage(res.last_page ?? 1);
          setExpTotal(res.total ?? res.data.length);
        }
      } catch (err: any) {
        console.warn("Error fetching expenses:", err);
      } finally {
        setExpLoading(false);
        setExpRefreshing(false);
      }
    },
    []
  );

  // Initial load
  useEffect(() => {
    loadCounts();
  }, [loadCounts]);

  // Load master data for edit pickers
  useEffect(() => {
    (async () => {
      try {
        const [vRes, cRes] = await Promise.all([
          getMasterVehicleTypes().catch(() => null),
          getExpenseCategories().catch(() => null),
        ]);
        if (vRes?.data && Array.isArray(vRes.data) && vRes.data.length > 0) {
          setVehicleTypesList(vRes.data.map((v: any) => v.name));
        }
        if (cRes?.data && Array.isArray(cRes.data) && cRes.data.length > 0) {
          setExpenseCategoriesList(cRes.data);
        }
      } catch (err) {
        console.warn("Failed loading master data for edits:", err);
      }
    })();
  }, []);

  // Fetch team members based on active approval module
  const loadTeamMembers = useCallback(async () => {
    try {
      const module = activeTab === "day_start" ? "day-start" : "expenses";
      const tRes = await getApprovalTeamMembers({ module }).catch(() => null);
      if (tRes?.data && Array.isArray(tRes.data)) {
        setTeamMembers(tRes.data);
      }
    } catch (err) {
      console.warn("Failed loading team members:", err);
    }
  }, [activeTab]);

  useEffect(() => {
    loadTeamMembers();
  }, [loadTeamMembers]);

  // Reset page to 1 whenever filters, search, or employee selection change
  useEffect(() => {
    setDsPage(1);
  }, [dsStatus, selectedUserId, debouncedSearch, dsPerPage]);

  useEffect(() => {
    setExpPage(1);
  }, [expStatus, selectedUserId, debouncedSearch, expPerPage]);

  // Load Day Starts when filter/tab/page/employee/search changes
  useEffect(() => {
    if (activeTab === "day_start") {
      fetchDayStarts(dsPage, dsStatus, debouncedSearch, selectedUserId, dsPerPage);
    }
  }, [activeTab, dsPage, dsStatus, debouncedSearch, selectedUserId, dsPerPage, fetchDayStarts]);

  // Load Expenses when filter/tab/page/employee/search changes
  useEffect(() => {
    if (activeTab === "expenses") {
      fetchExpenses(expPage, expStatus, debouncedSearch, selectedUserId, expPerPage);
    }
  }, [activeTab, expPage, expStatus, debouncedSearch, selectedUserId, expPerPage, fetchExpenses]);

  // Handle employee filter selection
  function handleSelectEmployee(userId: number | "all") {
    setSelectedUserId(userId);
    setEmployeeModalVisible(false);
    setEmployeeSearch("");
  }

  const selectedMember = teamMembers.find((m) => m.id === selectedUserId);
  const selectedMemberName = selectedMember ? selectedMember.name : null;

  const filteredTeamMembers = useMemo(() => {
    if (!employeeSearch.trim()) return teamMembers;
    const q = employeeSearch.toLowerCase().trim();
    return teamMembers.filter(
      (m) =>
        m.name.toLowerCase().includes(q) ||
        (m.employee_id && m.employee_id.toLowerCase().includes(q)) ||
        (m.position && m.position.toLowerCase().includes(q))
    );
  }, [teamMembers, employeeSearch]);

  // ─── Open Action Confirmation Dialog ─────────────────────────────────────────
  function openActionDialog(
    type: "day_start" | "expense",
    id: number,
    action: "approve" | "reject" | "temp_approve" | "final_approve" | "manager_reject",
    employeeName: string
  ) {
    let title = "";
    let description = "";
    let requiresReason = false;

    if (type === "day_start") {
      if (action === "approve") {
        title = "Approve Tour Plan";
        description = `Authorize tour plan submitted by ${employeeName}.`;
      } else {
        title = "Reject Tour Plan";
        description = `Specify why you are rejecting the tour plan for ${employeeName}.`;
        requiresReason = true;
      }
    } else {
      if (action === "temp_approve") {
        title = "Verify Claim (Level 1)";
        description = `Verify expense claim of ${employeeName} and forward for final approval.`;
      } else if (action === "final_approve") {
        title = "Final Approve Expense";
        description = `Grant final reimbursement authorization for ${employeeName}.`;
      } else if (action === "manager_reject") {
        title = "Reject Claim (Level 1)";
        description = `Reject expense claim for ${employeeName}. A reason is required.`;
        requiresReason = true;
      } else {
        title = "Final Reject Claim";
        description = `Final reject reimbursement claim for ${employeeName}. A reason is required.`;
        requiresReason = true;
      }
    }

    setActionNote("");
    setActionTarget({
      type,
      id,
      action,
      title,
      description,
      requiresReason,
    });
    setActionModalVisible(true);
  }

  // ─── Submit Action to Backend ─────────────────────────────────────────────────
  async function submitAction() {
    if (!actionTarget) return;

    if (actionTarget.requiresReason && !actionNote.trim()) {
      Alert.alert("Reason Required", "Please provide a reason before rejecting.");
      return;
    }

    setSubmittingAction(true);
    try {
      if (actionTarget.type === "day_start") {
        const res = await actOnDayStart(actionTarget.id, {
          status: actionTarget.action === "approve" ? "approved" : "rejected",
          reason: actionNote.trim() || undefined,
        });

        if (res.success) {
          setActionModalVisible(false);
          setSelectedDayStart(null);
          loadCounts();
          fetchDayStarts(dsPage, dsStatus, debouncedSearch, selectedUserId, dsPerPage);
          Alert.alert("Success", res.message || "Tour plan updated successfully.");
        }
      } else {
        const res = await actOnExpense(actionTarget.id, {
          action: actionTarget.action as any,
          note: actionNote.trim() || undefined,
        });

        if (res.success) {
          setActionModalVisible(false);
          setSelectedExpense(null);
          loadCounts();
          fetchExpenses(expPage, expStatus, debouncedSearch, selectedUserId, expPerPage);
          Alert.alert("Success", res.message || "Expense status updated successfully.");
        }
      }
    } catch (err: any) {
      Alert.alert("Action Failed", err?.message ?? "An error occurred while performing the action.");
    } finally {
      setSubmittingAction(false);
    }
  }

  // Open Fullscreen Photo Viewer
  function openPhotoViewer(url: string | null, title: string) {
    if (!url) return;
    setSelectedPhotoUrl(url);
    setSelectedPhotoTitle(title);
    setPhotoViewerVisible(true);
  }

  // ─── Day Start Edit Handlers ────────────────────────────────────────────────
  function openEditDayStart(item: DayStartApprovalItem) {
    setEditingDayStart(item);
    setEditDsOpeningKm(item.opening_km !== null ? String(item.opening_km) : "");
    setEditDsClosingKm(item.closing_km !== null ? String(item.closing_km) : "");
    setEditDsVehicleType(item.vehicle_type || "");
    setEditDsVehicleNo(item.vehicle_no || "");
    setEditDsVisitPlace(item.visit_place || item.area || "");
    setEditDsTourPurpose(item.tour_purpose || "");
    setEditDsRemarks(item.remarks || "");
    setEditDsReason("");
    setEditDayStartModalVisible(true);
  }

  async function handleSaveDayStartEdit() {
    if (!editingDayStart) return;

    const opKm = editDsOpeningKm.trim() ? parseFloat(editDsOpeningKm) : null;
    const clKm = editDsClosingKm.trim() ? parseFloat(editDsClosingKm) : null;

    if (opKm !== null && isNaN(opKm)) {
      Alert.alert("Validation Error", "Opening KM must be a valid number.");
      return;
    }
    if (clKm !== null && isNaN(clKm)) {
      Alert.alert("Validation Error", "Closing KM must be a valid number.");
      return;
    }
    if (opKm !== null && clKm !== null && clKm < opKm) {
      Alert.alert("Validation Error", "Closing KM cannot be less than Opening KM.");
      return;
    }

    setSavingDsEdit(true);
    try {
      const res = await updateDayStartApproval(editingDayStart.id, {
        opening_km: opKm,
        closing_km: clKm,
        vehicle_type: editDsVehicleType.trim() || null,
        vehicle_no: editDsVehicleNo.trim() || null,
        visit_place: editDsVisitPlace.trim() || null,
        tour_purpose: editDsTourPurpose.trim() || null,
        remarks: editDsRemarks.trim() || null,
        reason: editDsReason.trim() || undefined,
      });

      if (res.success) {
        setEditDayStartModalVisible(false);
        setEditingDayStart(null);
        loadCounts();
        fetchDayStarts(dsPage, dsStatus, debouncedSearch, selectedUserId, dsPerPage);
        Alert.alert("Success", res.message || "Tour plan updated successfully.");
      }
    } catch (err: any) {
      Alert.alert("Update Failed", err?.message ?? "An error occurred while updating the tour plan.");
    } finally {
      setSavingDsEdit(false);
    }
  }

  // ─── Expense Edit Handlers ──────────────────────────────────────────────────
  function openEditExpense(item: ExpenseApprovalItem) {
    setEditingExpense(item);
    setEditExpAmount(String(item.amount));
    const foundCat = expenseCategoriesList.find(
      (c) => c.name.toLowerCase() === (item.category_name || "").toLowerCase()
    );
    setEditExpCategoryId(foundCat ? foundCat.id : null);
    setEditExpDate(item.date || "");
    setEditExpReason("");
    setEditExpenseModalVisible(true);
  }

  async function handleSaveExpenseEdit() {
    if (!editingExpense) return;

    const amt = parseFloat(editExpAmount);
    if (isNaN(amt) || amt < 0) {
      Alert.alert("Validation Error", "Please enter a valid expense amount.");
      return;
    }

    setSavingExpEdit(true);
    try {
      const res = await updateExpenseApproval(editingExpense.id, {
        amount: amt,
        category_id: editExpCategoryId,
        date: editExpDate.trim() || null,
        reason: editExpReason.trim() || undefined,
      });

      if (res.success) {
        setEditExpenseModalVisible(false);
        setEditingExpense(null);
        loadCounts();
        fetchExpenses(expPage, expStatus, debouncedSearch, selectedUserId, expPerPage);
        Alert.alert("Success", res.message || "Expense claim updated successfully.");
      }
    } catch (err: any) {
      Alert.alert("Update Failed", err?.message ?? "An error occurred while updating the expense claim.");
    } finally {
      setSavingExpEdit(false);
    }
  }

  // ─── Status Badge Component ───────────────────────────────────────────────────
  function renderStatusPill(status: string) {
    let bg = "#F3F4F6";
    let text = "#4B5563";
    let label = status.toUpperCase();

    if (status === "approved") {
      bg = "#DCFCE7";
      text = "#16A34A";
      label = "APPROVED";
    } else if (status === "pending" || status === "submitted") {
      bg = "#FEF3C7";
      text = "#D97706";
      label = "PENDING";
    } else if (status === "temp_approved") {
      bg = "#E0E7FF";
      text = "#4338CA";
      label = "VERIFIED L1";
    } else if (status === "rejected" || status === "manager_rejected") {
      bg = "#FEE2E2";
      text = "#DC2626";
      label = status === "manager_rejected" ? "MGR REJECT" : "REJECTED";
    }

    return (
      <View style={[styles.statusPill, { backgroundColor: bg }]}>
        <Text style={[styles.statusPillText, { color: text }]}>{label}</Text>
      </View>
    );
  }

  // ─── Pagination Bar Component ─────────────────────────────────────────────────
  function renderPaginationBar(
    currentPage: number,
    lastPage: number,
    totalRecords: number,
    perPage: number,
    onPageChange: (newPage: number) => void,
    onPerPageChange: (newPerPage: number) => void
  ) {
    const from = totalRecords === 0 ? 0 : (currentPage - 1) * perPage + 1;
    const to = Math.min(currentPage * perPage, totalRecords);

    return (
      <View style={[styles.paginationContainer, { paddingBottom: Math.max(insets.bottom > 0 ? 10 : 8, 8) }]}>
        {/* Record count info & rows-per-page */}
        <View style={styles.paginationTopRow}>
          <Text style={styles.paginationInfoText}>
            Showing <Text style={styles.boldText}>{from}</Text> - <Text style={styles.boldText}>{to}</Text> of{" "}
            <Text style={styles.boldText}>{totalRecords}</Text>
          </Text>

          {/* Per Page Selector */}
          <View style={styles.perPageWrap}>
            <Text style={styles.perPageLabel}>Rows:</Text>
            {[10, 15, 25, 50].map((num) => (
              <TouchableOpacity
                key={num}
                style={[styles.perPageBtn, perPage === num && styles.perPageBtnActive]}
                onPress={() => onPerPageChange(num)}
              >
                <Text style={[styles.perPageBtnText, perPage === num && styles.perPageBtnTextActive]}>
                  {num}
                </Text>
              </TouchableOpacity>
            ))}
          </View>
        </View>

        <View style={styles.paginationBtnGroup}>
          {/* Jump to First Page */}
          <TouchableOpacity
            style={[styles.pageBtn, styles.pageBtnSquare, currentPage <= 1 && styles.pageBtnDisabled]}
            disabled={currentPage <= 1}
            onPress={() => onPageChange(1)}
            activeOpacity={0.7}
          >
            <MaterialIcons
              name="first-page"
              size={18}
              color={currentPage <= 1 ? "#9CA3AF" : PURPLE}
            />
          </TouchableOpacity>

          {/* Previous Page Button */}
          <TouchableOpacity
            style={[styles.pageBtn, currentPage <= 1 && styles.pageBtnDisabled]}
            disabled={currentPage <= 1}
            onPress={() => onPageChange(currentPage - 1)}
            activeOpacity={0.7}
          >
            <MaterialIcons
              name="chevron-left"
              size={18}
              color={currentPage <= 1 ? "#9CA3AF" : PURPLE}
            />
            <Text style={[styles.pageBtnText, currentPage <= 1 && styles.pageBtnTextDisabled]}>
              Prev
            </Text>
          </TouchableOpacity>

          {/* Page Indicator */}
          <View style={styles.pageNumberBadge}>
            <Text style={styles.pageNumberText}>
              {currentPage} / {Math.max(1, lastPage)}
            </Text>
          </View>

          {/* Next Page Button */}
          <TouchableOpacity
            style={[styles.pageBtn, currentPage >= lastPage && styles.pageBtnDisabled]}
            disabled={currentPage >= lastPage}
            onPress={() => onPageChange(currentPage + 1)}
            activeOpacity={0.7}
          >
            <Text style={[styles.pageBtnText, currentPage >= lastPage && styles.pageBtnTextDisabled]}>
              Next
            </Text>
            <MaterialIcons
              name="chevron-right"
              size={18}
              color={currentPage >= lastPage ? "#9CA3AF" : PURPLE}
            />
          </TouchableOpacity>

          {/* Jump to Last Page */}
          <TouchableOpacity
            style={[styles.pageBtn, styles.pageBtnSquare, currentPage >= lastPage && styles.pageBtnDisabled]}
            disabled={currentPage >= lastPage}
            onPress={() => onPageChange(lastPage)}
            activeOpacity={0.7}
          >
            <MaterialIcons
              name="last-page"
              size={18}
              color={currentPage >= lastPage ? "#9CA3AF" : PURPLE}
            />
          </TouchableOpacity>
        </View>
      </View>
    );
  }

  return (
    <View style={styles.safe}>
      {/* ── Screen Header ── */}
      <ScreenHeader
        title="Approvals Hub"
        onBack={() => navigation.goBack()}
      />

      {/* ── Segmented Main Tabs ── */}
      <View style={styles.mainTabContainer}>
        {/* Day Starts Tab */}
        <TouchableOpacity
          style={[styles.mainTab, activeTab === "day_start" && styles.mainTabActive]}
          activeOpacity={0.85}
          onPress={() => {
            setActiveTab("day_start");
            setSearchInput("");
            setDebouncedSearch("");
          }}
        >
          <MaterialCommunityIcons
            name="map-marker-path"
            size={18}
            color={activeTab === "day_start" ? "#FFFFFF" : "#6B7280"}
          />
          <Text style={[styles.mainTabText, activeTab === "day_start" && styles.mainTabTextActive]}>
            Tour Plans
          </Text>
          {counts.pending_day_starts > 0 && (
            <View
              style={[
                styles.tabBadge,
                activeTab === "day_start" && { backgroundColor: "#FFFFFF" },
              ]}
            >
              <Text
                style={[
                  styles.tabBadgeText,
                  activeTab === "day_start" && { color: PURPLE },
                ]}
              >
                {counts.pending_day_starts}
              </Text>
            </View>
          )}
        </TouchableOpacity>

        {/* Expenses Tab */}
        <TouchableOpacity
          style={[styles.mainTab, activeTab === "expenses" && styles.mainTabActive]}
          activeOpacity={0.85}
          onPress={() => {
            setActiveTab("expenses");
            setSearchInput("");
            setDebouncedSearch("");
          }}
        >
          <MaterialCommunityIcons
            name="receipt"
            size={18}
            color={activeTab === "expenses" ? "#FFFFFF" : "#6B7280"}
          />
          <Text style={[styles.mainTabText, activeTab === "expenses" && styles.mainTabTextActive]}>
            Expenses
          </Text>
          {counts.pending_expenses_l1 + counts.pending_expenses_l2 > 0 && (
            <View
              style={[
                styles.tabBadge,
                activeTab === "expenses" && { backgroundColor: "#FFFFFF" },
              ]}
            >
              <Text
                style={[
                  styles.tabBadgeText,
                  activeTab === "expenses" && { color: PURPLE },
                ]}
              >
                {counts.pending_expenses_l1 + counts.pending_expenses_l2}
              </Text>
            </View>
          )}
        </TouchableOpacity>
      </View>

      {/* ── Search Bar (Backend Debounced Query) ── */}
      <View style={styles.searchContainer}>
        <MaterialIcons name="search" size={20} color="#9CA3AF" style={styles.searchIcon} />
        <TextInput
          style={styles.searchInput}
          placeholder="Search worker name, code, place or amount..."
          placeholderTextColor="#9CA3AF"
          value={searchInput}
          onChangeText={setSearchInput}
          clearButtonMode="while-editing"
        />
        {searchInput.length > 0 && (
          <TouchableOpacity
            onPress={() => {
              setSearchInput("");
              setDebouncedSearch("");
            }}
          >
            <MaterialIcons name="close" size={18} color="#9CA3AF" />
          </TouchableOpacity>
        )}
      </View>

      {/* ── Employee Filter Section (Horizontal Quick Chips + Dropdown Picker) ── */}
      <View style={styles.employeeFilterSection}>
        <View style={styles.employeeFilterHeader}>
          <TouchableOpacity
            style={[styles.employeePickerBtn, selectedUserId !== "all" && styles.employeePickerBtnActive]}
            activeOpacity={0.8}
            onPress={() => setEmployeeModalVisible(true)}
          >
            <MaterialCommunityIcons
              name="account-filter-outline"
              size={17}
              color={selectedUserId !== "all" ? PURPLE : "#4B5563"}
            />
            <Text
              style={[styles.employeePickerText, selectedUserId !== "all" && styles.employeePickerTextActive]}
              numberOfLines={1}
            >
              {selectedUserId === "all"
                ? `All Employees (${teamMembers.length})`
                : selectedMemberName || `Worker #${selectedUserId}`}
            </Text>
            <MaterialIcons
              name="arrow-drop-down"
              size={20}
              color={selectedUserId !== "all" ? PURPLE : "#6B7280"}
            />
          </TouchableOpacity>

          {selectedUserId !== "all" && (
            <TouchableOpacity
              style={styles.clearEmployeeBtn}
              onPress={() => handleSelectEmployee("all")}
            >
              <MaterialIcons name="close" size={15} color="#DC2626" />
              <Text style={styles.clearEmployeeText}>Reset All</Text>
            </TouchableOpacity>
          )}
        </View>

        {/* Horizontal Scroll of Quick Employee Chips */}
        {teamMembers.length > 0 && (
          <ScrollView
            horizontal
            showsHorizontalScrollIndicator={false}
            contentContainerStyle={styles.employeeChipsScroll}
          >
            <TouchableOpacity
              style={[
                styles.empQuickChip,
                selectedUserId === "all" && styles.empQuickChipActive,
              ]}
              onPress={() => handleSelectEmployee("all")}
              activeOpacity={0.8}
            >
              <Text
                style={[
                  styles.empQuickChipText,
                  selectedUserId === "all" && styles.empQuickChipTextActive,
                ]}
              >
                All ({teamMembers.length})
              </Text>
            </TouchableOpacity>

            {teamMembers.map((member) => {
              const isSelected = selectedUserId === member.id;
              return (
                <TouchableOpacity
                  key={member.id}
                  style={[styles.empQuickChip, isSelected && styles.empQuickChipActive]}
                  onPress={() => handleSelectEmployee(member.id)}
                  activeOpacity={0.8}
                >
                  <MaterialCommunityIcons
                    name="account-outline"
                    size={14}
                    color={isSelected ? "#FFFFFF" : "#6B7280"}
                  />
                  <Text
                    style={[
                      styles.empQuickChipText,
                      isSelected && styles.empQuickChipTextActive,
                    ]}
                    numberOfLines={1}
                  >
                    {member.name}
                  </Text>
                </TouchableOpacity>
              );
            })}
          </ScrollView>
        )}
      </View>

      {/* ── Sub-Filter Chips ── */}
      <View style={styles.filterScrollContainer}>
        <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.filterScroll}>
          {activeTab === "day_start" ? (
            <>
              {(["pending", "approved", "rejected", "all"] as const).map((st) => (
                <TouchableOpacity
                  key={st}
                  style={[styles.chip, dsStatus === st && styles.chipActive]}
                  activeOpacity={0.8}
                  onPress={() => {
                    setDsStatus(st);
                    setDsPage(1);
                  }}
                >
                  <Text style={[styles.chipText, dsStatus === st && styles.chipTextActive]}>
                    {st === "pending" ? "Pending Approval" : st.charAt(0).toUpperCase() + st.slice(1)}
                  </Text>
                </TouchableOpacity>
              ))}
            </>
          ) : (
            <>
              {(["pending", "temp_approved", "approved", "rejected", "all"] as const).map((st) => (
                <TouchableOpacity
                  key={st}
                  style={[styles.chip, expStatus === st && styles.chipActive]}
                  activeOpacity={0.8}
                  onPress={() => {
                    setExpStatus(st);
                    setExpPage(1);
                  }}
                >
                  <Text style={[styles.chipText, expStatus === st && styles.chipTextActive]}>
                    {st === "pending"
                      ? "Pending (L1)"
                      : st === "temp_approved"
                      ? "Verified (L2)"
                      : st.charAt(0).toUpperCase() + st.slice(1)}
                  </Text>
                </TouchableOpacity>
              ))}
            </>
          )}
        </ScrollView>
      </View>

      {/* ── DATA TABLE SECTION (Horizontal Scrolling Table) ── */}
      <View style={[styles.tableCardContainer, { marginBottom: Math.max(insets.bottom, 10) }]}>
        {activeTab === "day_start" ? (
          /* ──────── Tour Plans Data Table ──────── */
          dsLoading ? (
            <View style={styles.tableLoader}>
              <ActivityIndicator size="large" color={PURPLE} />
              <Text style={styles.loaderText}>Loading tour plans...</Text>
            </View>
          ) : dayStarts.length === 0 ? (
            <View style={styles.emptyTable}>
              <MaterialCommunityIcons name="calendar-blank-outline" size={48} color="#D1D5DB" />
              <Text style={styles.emptyTitle}>No Tour Plans Found</Text>
              <Text style={styles.emptySub}>
                {dsStatus === "pending" ? "All team tour plans have been reviewed!" : "No records match this filter."}
              </Text>
            </View>
          ) : (
            <ScrollView
              style={styles.tableVerticalScroll}
              contentContainerStyle={{ flexGrow: 1, paddingBottom: 10 }}
              refreshControl={
                <RefreshControl
                  refreshing={dsRefreshing}
                  onRefresh={() => {
                    loadCounts();
                    fetchDayStarts(dsPage, dsStatus, debouncedSearch, selectedUserId, dsPerPage, true);
                  }}
                  colors={[PURPLE]}
                  tintColor={PURPLE}
                />
              }
            >
              <ScrollView horizontal showsHorizontalScrollIndicator={true} style={styles.tableHorizontalScroll}>
                <View>
                  {/* Table Header */}
                  <View style={styles.tableHeaderRow}>
                    <Text style={[styles.thCell, { width: 140 }]}>EMPLOYEE</Text>
                    <Text style={[styles.thCell, { width: 95 }]}>DATE</Text>
                    <Text style={[styles.thCell, { width: 175 }]}>VISIT / TOUR PLAN</Text>
                    <Text style={[styles.thCell, { width: 120 }]}>VEHICLE</Text>
                    <Text style={[styles.thCell, { width: 110, textAlign: "right" }]}>DISTANCE</Text>
                    <Text style={[styles.thCell, { width: 100, textAlign: "center" }]}>STATUS</Text>
                    <Text style={[styles.thCell, { width: 155, textAlign: "center" }]}>ACTIONS</Text>
                  </View>

                  {/* Table Rows */}
                  {dayStarts.map((item, index) => {
                    const totalKm =
                      item.opening_km !== null && item.closing_km !== null
                        ? Math.max(0, item.closing_km - item.opening_km)
                        : null;

                    return (
                      <TouchableOpacity
                        key={item.id}
                        style={[styles.tableRow, index % 2 === 1 && styles.tableRowAlt]}
                        activeOpacity={0.75}
                        onPress={() => setSelectedDayStart(item)}
                      >
                        {/* Employee Cell */}
                        <View style={[styles.tdCell, { width: 140 }]}>
                          <Text style={styles.tdPrimaryText} numberOfLines={1}>
                            {item.employee_name}
                          </Text>
                          <Text style={styles.tdSecondaryText}>
                            {item.employee_id} • {item.employee_role}
                          </Text>
                        </View>

                        {/* Date Cell */}
                        <View style={[styles.tdCell, { width: 95 }]}>
                          <Text style={styles.tdPrimaryText}>{item.date_formatted}</Text>
                          <Text style={styles.tdSecondaryText}>{item.start_time || "—"}</Text>
                        </View>

                        {/* Visit / Purpose & Tour Plan Cell */}
                        <View style={[styles.tdCell, { width: 175 }]}>
                          <Text style={styles.tdPrimaryText} numberOfLines={1}>
                            {item.tour_purpose}
                          </Text>
                          <Text style={styles.tdSecondaryText} numberOfLines={1}>
                            {item.visit_place || item.area || item.party_name || "—"}
                          </Text>
                          {Boolean(item.remarks) && (
                            <Text
                              style={{
                                fontSize: 11,
                                color: "#B45309",
                                fontWeight: "600",
                                marginTop: 2,
                              }}
                              numberOfLines={1}
                            >
                              📋 {item.remarks}
                            </Text>
                          )}
                        </View>

                        {/* Vehicle Cell */}
                        <View style={[styles.tdCell, { width: 120 }]}>
                          <Text style={styles.tdPrimaryText} numberOfLines={1}>
                            {item.vehicle_type || "—"}
                          </Text>
                          <Text style={styles.tdSecondaryText} numberOfLines={1}>
                            {item.vehicle_no || "No Reg"}
                          </Text>
                        </View>

                        {/* Distance Cell */}
                        <View style={[styles.tdCell, { width: 110, alignItems: "flex-end" }]}>
                          <Text style={[styles.tdPrimaryText, { color: PURPLE, fontWeight: "800" }]}>
                            {totalKm !== null ? `${totalKm.toFixed(1)} KM` : item.closing_km ? "Done" : "Active"}
                          </Text>
                          <Text style={styles.tdSecondaryText}>
                            {item.opening_km !== null ? `${item.opening_km} st` : "—"}
                          </Text>
                        </View>

                        {/* Status Cell */}
                        <View style={[styles.tdCell, { width: 100, alignItems: "center" }]}>
                          {renderStatusPill(item.status)}
                        </View>

                        {/* Actions Cell */}
                        <View style={[styles.tdCell, styles.actionCellGroup, { width: 155 }]}>
                          {item.can_action ? (
                            <>
                              <TouchableOpacity
                                style={[styles.actionMiniBtn, styles.actionMiniApprove]}
                                activeOpacity={0.8}
                                onPress={() => openActionDialog("day_start", item.id, "approve", item.employee_name)}
                              >
                                <MaterialIcons name="check" size={16} color="#FFFFFF" />
                              </TouchableOpacity>

                              <TouchableOpacity
                                style={[styles.actionMiniBtn, styles.actionMiniReject]}
                                activeOpacity={0.8}
                                onPress={() => openActionDialog("day_start", item.id, "reject", item.employee_name)}
                              >
                                <MaterialIcons name="close" size={16} color="#DC2626" />
                              </TouchableOpacity>
                            </>
                          ) : null}

                          {/* Edit / Adjust Button */}
                          {item.can_edit && (
                            <TouchableOpacity
                              style={[styles.actionMiniBtn, styles.actionMiniEdit]}
                              activeOpacity={0.8}
                              onPress={() => openEditDayStart(item)}
                            >
                              <MaterialIcons name="edit" size={15} color="#7367F0" />
                            </TouchableOpacity>
                          )}

                          {/* Info/View Details Button */}
                          <TouchableOpacity
                            style={[styles.actionMiniBtn, styles.actionMiniView]}
                            activeOpacity={0.8}
                            onPress={() => setSelectedDayStart(item)}
                          >
                            <MaterialIcons name="visibility" size={16} color="#4B5563" />
                          </TouchableOpacity>
                        </View>
                      </TouchableOpacity>
                    );
                  })}
                </View>
              </ScrollView>
            </ScrollView>
          )
        ) : (
          /* ──────── Expenses Data Table ──────── */
          expLoading ? (
            <View style={styles.tableLoader}>
              <ActivityIndicator size="large" color={PURPLE} />
              <Text style={styles.loaderText}>Loading expenses...</Text>
            </View>
          ) : expenses.length === 0 ? (
            <View style={styles.emptyTable}>
              <MaterialCommunityIcons name="receipt-text-outline" size={48} color="#D1D5DB" />
              <Text style={styles.emptyTitle}>No Expenses Found</Text>
              <Text style={styles.emptySub}>
                {expStatus === "pending" ? "All expense claims have been processed!" : "No records match this filter."}
              </Text>
            </View>
          ) : (
            <ScrollView
              style={styles.tableVerticalScroll}
              contentContainerStyle={{ flexGrow: 1, paddingBottom: 10 }}
              refreshControl={
                <RefreshControl
                  refreshing={expRefreshing}
                  onRefresh={() => {
                    loadCounts();
                    fetchExpenses(expPage, expStatus, debouncedSearch, selectedUserId, expPerPage, true);
                  }}
                  colors={[PURPLE]}
                  tintColor={PURPLE}
                />
              }
            >
              <ScrollView horizontal showsHorizontalScrollIndicator={true} style={styles.tableHorizontalScroll}>
                <View>
                  {/* Table Header */}
                  <View style={styles.tableHeaderRow}>
                    <Text style={[styles.thCell, { width: 140 }]}>EMPLOYEE</Text>
                    <Text style={[styles.thCell, { width: 95 }]}>DATE</Text>
                    <Text style={[styles.thCell, { width: 140 }]}>CATEGORY</Text>
                    <Text style={[styles.thCell, { width: 110, textAlign: "right" }]}>AMOUNT</Text>
                    <Text style={[styles.thCell, { width: 75, textAlign: "center" }]}>BILL</Text>
                    <Text style={[styles.thCell, { width: 105, textAlign: "center" }]}>STATUS</Text>
                    <Text style={[styles.thCell, { width: 155, textAlign: "center" }]}>ACTIONS</Text>
                  </View>

                  {/* Table Rows */}
                  {expenses.map((item, index) => {
                    return (
                      <TouchableOpacity
                        key={item.id}
                        style={[styles.tableRow, index % 2 === 1 && styles.tableRowAlt]}
                        activeOpacity={0.75}
                        onPress={() => setSelectedExpense(item)}
                      >
                        {/* Employee Cell */}
                        <View style={[styles.tdCell, { width: 140 }]}>
                          <Text style={styles.tdPrimaryText} numberOfLines={1}>
                            {item.employee_name}
                          </Text>
                          <Text style={styles.tdSecondaryText}>
                            {item.employee_id} • {item.employee_role}
                          </Text>
                        </View>

                        {/* Date Cell */}
                        <View style={[styles.tdCell, { width: 95 }]}>
                          <Text style={styles.tdPrimaryText}>{item.date_formatted}</Text>
                          <Text style={styles.tdSecondaryText}>{item.created_at}</Text>
                        </View>

                        {/* Category Cell */}
                        <View style={[styles.tdCell, { width: 140 }]}>
                          <Text style={styles.tdPrimaryText} numberOfLines={1}>
                            {item.category_name}
                          </Text>
                          {item.is_system_generated && (
                            <Text style={[styles.tdSecondaryText, { color: "#7367F0" }]}>Auto Travel</Text>
                          )}
                        </View>

                        {/* Amount Cell */}
                        <View style={[styles.tdCell, { width: 110, alignItems: "flex-end" }]}>
                          <Text style={[styles.tdPrimaryText, { color: PURPLE, fontWeight: "800" }]}>
                            ₹{item.amount_formatted}
                          </Text>
                        </View>

                        {/* Bill Photo Thumbnail Cell */}
                        <View style={[styles.tdCell, { width: 75, alignItems: "center" }]}>
                          {item.bill_photo_url ? (
                            <TouchableOpacity
                              style={styles.tableThumbBtn}
                              onPress={() =>
                                openPhotoViewer(item.bill_photo_url, `${item.employee_name} - Expense Bill`)
                              }
                            >
                              <Image source={{ uri: item.bill_photo_url }} style={styles.tableThumbImg} />
                            </TouchableOpacity>
                          ) : (
                            <Text style={styles.tdSecondaryText}>No Bill</Text>
                          )}
                        </View>

                        {/* Status Cell */}
                        <View style={[styles.tdCell, { width: 105, alignItems: "center" }]}>
                          {renderStatusPill(item.status)}
                        </View>

                        {/* Actions Cell */}
                        <View style={[styles.tdCell, styles.actionCellGroup, { width: 155 }]}>
                          {item.can_temp_approve && (
                            <TouchableOpacity
                              style={[styles.actionMiniBtn, styles.actionMiniVerify]}
                              activeOpacity={0.8}
                              onPress={() => openActionDialog("expense", item.id, "temp_approve", item.employee_name)}
                            >
                              <MaterialIcons name="done" size={16} color="#FFFFFF" />
                            </TouchableOpacity>
                          )}

                          {item.can_final_approve && (
                            <TouchableOpacity
                              style={[styles.actionMiniBtn, styles.actionMiniApprove]}
                              activeOpacity={0.8}
                              onPress={() => openActionDialog("expense", item.id, "final_approve", item.employee_name)}
                            >
                              <MaterialIcons name="check" size={16} color="#FFFFFF" />
                            </TouchableOpacity>
                          )}

                          {(item.can_temp_approve || item.can_final_approve) && (
                            <TouchableOpacity
                              style={[styles.actionMiniBtn, styles.actionMiniReject]}
                              activeOpacity={0.8}
                              onPress={() =>
                                openActionDialog(
                                  "expense",
                                  item.id,
                                  item.can_temp_approve ? "manager_reject" : "reject",
                                  item.employee_name
                                )
                              }
                            >
                              <MaterialIcons name="close" size={16} color="#DC2626" />
                            </TouchableOpacity>
                          )}

                          {/* Edit / Adjust Button */}
                          {item.can_edit && (
                            <TouchableOpacity
                              style={[styles.actionMiniBtn, styles.actionMiniEdit]}
                              activeOpacity={0.8}
                              onPress={() => openEditExpense(item)}
                            >
                              <MaterialIcons name="edit" size={15} color="#7367F0" />
                            </TouchableOpacity>
                          )}

                          {/* Info/View Details Button */}
                          <TouchableOpacity
                            style={[styles.actionMiniBtn, styles.actionMiniView]}
                            activeOpacity={0.8}
                            onPress={() => setSelectedExpense(item)}
                          >
                            <MaterialIcons name="visibility" size={16} color="#4B5563" />
                          </TouchableOpacity>
                        </View>
                      </TouchableOpacity>
                    );
                  })}
                </View>
              </ScrollView>
            </ScrollView>
          )
        )}

        {/* ── Proper Pagination Controls Bar ── */}
        {activeTab === "day_start"
          ? renderPaginationBar(dsPage, dsLastPage, dsTotal, dsPerPage, (p) => setDsPage(p), (pp) => setDsPerPage(pp))
          : renderPaginationBar(expPage, expLastPage, expTotal, expPerPage, (p) => setExpPage(p), (pp) => setExpPerPage(pp))}
      </View>

      {/* ── TOUR PLAN DETAILS MODAL ── */}
      <Modal
        visible={!!selectedDayStart}
        transparent
        animationType="slide"
        onRequestClose={() => setSelectedDayStart(null)}
      >
        <View style={styles.drawerBackdrop}>
          <View style={styles.drawerCard}>
            <View style={styles.drawerHeader}>
              <View style={{ flex: 1 }}>
                <Text style={styles.drawerTitle}>Tour Plan Details</Text>
                <Text style={styles.drawerSubtitle}>
                  {selectedDayStart?.employee_name} ({selectedDayStart?.employee_id})
                </Text>
              </View>
              <TouchableOpacity style={styles.drawerCloseBtn} onPress={() => setSelectedDayStart(null)}>
                <MaterialIcons name="close" size={24} color="#6B7280" />
              </TouchableOpacity>
            </View>

            <ScrollView
              style={styles.drawerBody}
              contentContainerStyle={{ paddingBottom: 40 }}
              showsVerticalScrollIndicator={false}
            >
              {selectedDayStart && (
                <>
                  <View style={styles.drawerGrid}>
                    <View style={styles.drawerInfoRow}>
                      <Text style={styles.drawerInfoLabel}>Date:</Text>
                      <Text style={styles.drawerInfoVal}>{selectedDayStart.date_formatted}</Text>
                    </View>
                    <View style={styles.drawerInfoRow}>
                      <Text style={styles.drawerInfoLabel}>Status:</Text>
                      {renderStatusPill(selectedDayStart.status)}
                    </View>
                    <View style={styles.drawerInfoRow}>
                      <Text style={styles.drawerInfoLabel}>Purpose:</Text>
                      <Text style={styles.drawerInfoVal}>
                        {selectedDayStart.tour_purpose} {selectedDayStart.tour_type ? `(${selectedDayStart.tour_type})` : ""}
                      </Text>
                    </View>
                    <View style={styles.drawerInfoRow}>
                      <Text style={styles.drawerInfoLabel}>Place:</Text>
                      <Text style={styles.drawerInfoVal}>
                        {selectedDayStart.visit_place || selectedDayStart.area || selectedDayStart.district || "—"}
                      </Text>
                    </View>
                    {selectedDayStart.vehicle_type && (
                      <View style={styles.drawerInfoRow}>
                        <Text style={styles.drawerInfoLabel}>Vehicle:</Text>
                        <Text style={styles.drawerInfoVal}>
                          {selectedDayStart.vehicle_type} • {selectedDayStart.vehicle_no || "No Reg"}
                        </Text>
                      </View>
                    )}
                    {selectedDayStart.party_name && (
                      <View style={styles.drawerInfoRow}>
                        <Text style={styles.drawerInfoLabel}>Client/Party:</Text>
                        <Text style={styles.drawerInfoVal}>{selectedDayStart.party_name}</Text>
                      </View>
                    )}
                  </View>

                  {/* Odometer KM Readings & Photos */}
                  <Text style={styles.drawerSectionHeader}>Meter Readings & Photos</Text>
                  <View style={styles.drawerKmWrap}>
                    <View style={styles.drawerKmCol}>
                      <Text style={styles.drawerKmLabel}>Opening KM</Text>
                      <Text style={styles.drawerKmVal}>
                        {selectedDayStart.opening_km !== null ? `${selectedDayStart.opening_km} KM` : "—"}
                      </Text>
                      {selectedDayStart.opening_km_photo_url ? (
                        <TouchableOpacity
                          style={styles.drawerThumbBtn}
                          onPress={() =>
                            openPhotoViewer(selectedDayStart.opening_km_photo_url, "Opening Meter Photo")
                          }
                        >
                          <Image source={{ uri: selectedDayStart.opening_km_photo_url }} style={styles.drawerThumbImg} />
                          <View style={styles.zoomBadge}>
                            <MaterialIcons name="zoom-in" size={12} color="#fff" />
                          </View>
                        </TouchableOpacity>
                      ) : (
                        <Text style={styles.noImgText}>No photo</Text>
                      )}
                    </View>

                    <View style={styles.drawerKmCol}>
                      <Text style={styles.drawerKmLabel}>Closing KM</Text>
                      <Text style={styles.drawerKmVal}>
                        {selectedDayStart.closing_km !== null ? `${selectedDayStart.closing_km} KM` : "Active"}
                      </Text>
                      {selectedDayStart.closing_km_photo_url ? (
                        <TouchableOpacity
                          style={styles.drawerThumbBtn}
                          onPress={() =>
                            openPhotoViewer(selectedDayStart.closing_km_photo_url, "Closing Meter Photo")
                          }
                        >
                          <Image source={{ uri: selectedDayStart.closing_km_photo_url }} style={styles.drawerThumbImg} />
                          <View style={styles.zoomBadge}>
                            <MaterialIcons name="zoom-in" size={12} color="#fff" />
                          </View>
                        </TouchableOpacity>
                      ) : (
                        <Text style={styles.noImgText}>Pending</Text>
                      )}
                    </View>
                  </View>

                  {/* Remarks / Tour Plan */}
                  {selectedDayStart.remarks && (
                    <View style={styles.remarksBox}>
                      <Text style={styles.remarksTitle}>📋 Tour Plan / Start Notes:</Text>
                      <Text style={styles.remarksContent}>{selectedDayStart.remarks}</Text>
                    </View>
                  )}

                  {/* Day End Remarks / Summary */}
                  {selectedDayStart.end_remarks && (
                    <View style={[styles.remarksBox, { backgroundColor: "#FEF3C7", borderColor: "#FDE68A" }]}>
                      <Text style={[styles.remarksTitle, { color: "#92400E" }]}>🏁 Day End Summary / Closing Notes:</Text>
                      <Text style={[styles.remarksContent, { color: "#78350F" }]}>{selectedDayStart.end_remarks}</Text>
                    </View>
                  )}

                  {/* Travel & Fuel Allowance Calculation Card */}
                  <View style={styles.travelCalcCard}>
                    <View style={styles.travelCalcCol}>
                      <Text style={styles.travelCalcLabel}>Distance</Text>
                      <Text style={styles.travelCalcVal}>
                        {selectedDayStart.distance_km !== null && selectedDayStart.distance_km !== undefined
                          ? `${selectedDayStart.distance_km} KM`
                          : (selectedDayStart.closing_km !== null && selectedDayStart.opening_km !== null
                            ? `${Math.max(0, Math.round(selectedDayStart.closing_km - selectedDayStart.opening_km))} KM`
                            : "—")}
                      </Text>
                    </View>
                    <View style={styles.travelCalcDivider} />
                    <View style={styles.travelCalcCol}>
                      <Text style={styles.travelCalcLabel}>Fuel Rate</Text>
                      <Text style={styles.travelCalcVal}>
                        {selectedDayStart.applied_rate_per_km
                          ? `₹${selectedDayStart.applied_rate_per_km.toFixed(2)}/km`
                          : "Standard"}
                      </Text>
                    </View>
                    <View style={styles.travelCalcDivider} />
                    <View style={styles.travelCalcCol}>
                      <Text style={styles.travelCalcLabel}>Travel Claim</Text>
                      <Text style={[styles.travelCalcVal, { color: "#059669", fontWeight: "800" }]}>
                        {selectedDayStart.travel_allowance_amount
                          ? `₹${selectedDayStart.travel_allowance_amount.toFixed(2)}`
                          : "—"}
                      </Text>
                    </View>
                  </View>

                  {/* Expenses Claimed on this Day Section (Matching Admin Panel) */}
                  <View style={styles.dayExpensesHeaderWrap}>
                    <Text style={styles.drawerSectionHeader}>Expenses Claimed Today</Text>
                    {selectedDayStart.total_day_expenses_amount !== undefined && selectedDayStart.total_day_expenses_amount > 0 && (
                      <View style={styles.dayExpensesTotalBadge}>
                        <Text style={styles.dayExpensesTotalText}>
                          Total: ₹{selectedDayStart.total_day_expenses_amount.toFixed(2)}
                        </Text>
                      </View>
                    )}
                  </View>

                  {selectedDayStart.day_expenses && selectedDayStart.day_expenses.length > 0 ? (
                    <View style={styles.dayExpensesListWrap}>
                      {selectedDayStart.day_expenses.map((exp) => (
                        <View key={exp.id} style={styles.dayExpenseCard}>
                          <View style={styles.dayExpenseIconWrap}>
                            <MaterialIcons
                              name={exp.is_system_generated ? "directions-car" : "receipt"}
                              size={20}
                              color={exp.is_system_generated ? PURPLE : "#059669"}
                            />
                          </View>
                          <View style={styles.dayExpenseInfo}>
                            <View style={styles.dayExpenseTopRow}>
                              <Text style={styles.dayExpenseCatName} numberOfLines={1}>
                                {exp.category_name}
                                {exp.items_count > 0 ? ` (${exp.items_count} items)` : ""}
                              </Text>
                              <Text style={styles.dayExpenseAmount}>₹{exp.amount_formatted}</Text>
                            </View>
                            <View style={styles.dayExpenseBtmRow}>
                              <Text style={styles.dayExpenseTiming}>
                                {exp.is_after_day_end ? "⚠️ After Day End" : "✓ During Shift"}
                              </Text>
                              {renderStatusPill(exp.status)}
                            </View>
                          </View>
                          {exp.bill_photo_url && (
                            <TouchableOpacity
                              style={styles.dayExpenseThumbBtn}
                              onPress={() => openPhotoViewer(exp.bill_photo_url, `${exp.category_name} Bill`)}
                            >
                              <Image source={{ uri: exp.bill_photo_url }} style={styles.dayExpenseThumbImg} />
                            </TouchableOpacity>
                          )}
                        </View>
                      ))}
                    </View>
                  ) : (
                    <View style={styles.dayExpensesEmptyBox}>
                      <Text style={styles.dayExpensesEmptyText}>No expenses recorded for this day yet.</Text>
                    </View>
                  )}
                </>
              )}
            </ScrollView>

            {/* Modal Bottom Action Buttons */}
            {selectedDayStart && (
              <View style={[styles.drawerActionRow, { paddingBottom: Math.max(insets.bottom, 16) + 12 }]}>
                {selectedDayStart.can_action && (
                  <TouchableOpacity
                    style={[styles.modalActionBtn, styles.modalRejectBtnLarge]}
                    onPress={() => {
                      openActionDialog("day_start", selectedDayStart.id, "reject", selectedDayStart.employee_name);
                    }}
                  >
                    <MaterialIcons name="close" size={18} color="#DC2626" />
                    <Text style={styles.modalRejectTextLarge}>Reject</Text>
                  </TouchableOpacity>
                )}

                {selectedDayStart.can_edit && (
                  <TouchableOpacity
                    style={[styles.modalActionBtn, styles.modalEditBtnLarge]}
                    onPress={() => {
                      const ds = selectedDayStart;
                      setSelectedDayStart(null);
                      openEditDayStart(ds);
                    }}
                  >
                    <MaterialIcons name="edit" size={18} color="#7367F0" />
                    <Text style={styles.modalEditTextLarge}>Edit Tour Plan</Text>
                  </TouchableOpacity>
                )}

                {selectedDayStart.can_action && (
                  <TouchableOpacity
                    style={[styles.modalActionBtn, styles.modalApproveBtnLarge]}
                    onPress={() => {
                      openActionDialog("day_start", selectedDayStart.id, "approve", selectedDayStart.employee_name);
                    }}
                  >
                    <MaterialIcons name="check" size={18} color="#FFFFFF" />
                    <Text style={styles.modalApproveTextLarge}>Approve</Text>
                  </TouchableOpacity>
                )}
              </View>
            )}
          </View>
        </View>
      </Modal>

      {/* ── EXPENSE DETAILS MODAL ── */}
      <Modal
        visible={!!selectedExpense}
        transparent
        animationType="slide"
        onRequestClose={() => setSelectedExpense(null)}
      >
        <View style={styles.drawerBackdrop}>
          <View style={styles.drawerCard}>
            <View style={styles.drawerHeader}>
              <View style={{ flex: 1 }}>
                <Text style={styles.drawerTitle}>Expense Claim Details</Text>
                <Text style={styles.drawerSubtitle}>
                  {selectedExpense?.employee_name} ({selectedExpense?.employee_id})
                </Text>
              </View>
              <TouchableOpacity style={styles.drawerCloseBtn} onPress={() => setSelectedExpense(null)}>
                <MaterialIcons name="close" size={24} color="#6B7280" />
              </TouchableOpacity>
            </View>

            <ScrollView
              style={styles.drawerBody}
              contentContainerStyle={{ paddingBottom: 40 }}
              showsVerticalScrollIndicator={false}
            >
              {selectedExpense && (
                <>
                  {/* Amount Hero Banner */}
                  <View style={styles.expHeroBox}>
                    <Text style={styles.expHeroLabel}>TOTAL REIMBURSEMENT CLAIM</Text>
                    <Text style={styles.expHeroAmount}>₹{selectedExpense.amount_formatted}</Text>
                    <Text style={styles.expHeroCategory}>{selectedExpense.category_name}</Text>
                  </View>

                  <View style={styles.drawerGrid}>
                    <View style={styles.drawerInfoRow}>
                      <Text style={styles.drawerInfoLabel}>Date:</Text>
                      <Text style={styles.drawerInfoVal}>{selectedExpense.date_formatted}</Text>
                    </View>
                    <View style={styles.drawerInfoRow}>
                      <Text style={styles.drawerInfoLabel}>Status:</Text>
                      {renderStatusPill(selectedExpense.status)}
                    </View>
                    {selectedExpense.is_system_generated && (
                      <View style={styles.drawerInfoRow}>
                        <Text style={styles.drawerInfoLabel}>Source:</Text>
                        <Text style={[styles.drawerInfoVal, { color: PURPLE }]}>Automated Travel Allowance</Text>
                      </View>
                    )}
                  </View>

                  {/* Automated Travel Allowance Meter Verification (When system generated) */}
                  {selectedExpense.is_system_generated ? (
                    <View style={styles.systemTravelCard}>
                      <View style={styles.systemTravelHeader}>
                        <MaterialIcons name="directions-car" size={18} color={PURPLE} />
                        <Text style={styles.systemTravelTitle}>Trip & Meter Verification</Text>
                      </View>

                      {/* Vehicle & Route info */}
                      <View style={styles.systemTravelInfoGrid}>
                        {selectedExpense.vehicle_type ? (
                          <View style={styles.systemTravelInfoItem}>
                            <Text style={styles.systemTravelInfoLabel}>Vehicle</Text>
                            <Text style={styles.systemTravelInfoVal}>
                              {selectedExpense.vehicle_type} {selectedExpense.vehicle_no ? `(${selectedExpense.vehicle_no})` : ""}
                            </Text>
                          </View>
                        ) : null}
                        {selectedExpense.tour_purpose ? (
                          <View style={styles.systemTravelInfoItem}>
                            <Text style={styles.systemTravelInfoLabel}>Tour Purpose</Text>
                            <Text style={styles.systemTravelInfoVal}>{selectedExpense.tour_purpose}</Text>
                          </View>
                        ) : null}
                        {selectedExpense.visit_place ? (
                          <View style={styles.systemTravelInfoItem}>
                            <Text style={styles.systemTravelInfoLabel}>Destination</Text>
                            <Text style={styles.systemTravelInfoVal}>{selectedExpense.visit_place}</Text>
                          </View>
                        ) : null}
                        {selectedExpense.party_name ? (
                          <View style={styles.systemTravelInfoItem}>
                            <Text style={styles.systemTravelInfoLabel}>Client/Party</Text>
                            <Text style={styles.systemTravelInfoVal}>{selectedExpense.party_name}</Text>
                          </View>
                        ) : null}
                      </View>

                      {/* Dual Meter Photos (Start & End) */}
                      <Text style={[styles.drawerSectionHeader, { marginTop: 12, marginBottom: 8 }]}>
                        Odometer Meter Proofs
                      </Text>
                      <View style={styles.drawerKmWrap}>
                        <View style={styles.drawerKmCol}>
                          <Text style={styles.drawerKmLabel}>Start Meter</Text>
                          <Text style={styles.drawerKmVal}>
                            {selectedExpense.opening_km !== null && selectedExpense.opening_km !== undefined
                              ? `${selectedExpense.opening_km} KM`
                              : "—"}
                          </Text>
                          {selectedExpense.opening_km_photo_url ? (
                            <TouchableOpacity
                              style={styles.drawerThumbBtn}
                              onPress={() =>
                                openPhotoViewer(selectedExpense.opening_km_photo_url || null, "Opening Meter Photo")
                              }
                            >
                              <Image
                                source={{ uri: selectedExpense.opening_km_photo_url }}
                                style={styles.drawerThumbImg}
                              />
                              <View style={styles.zoomBadge}>
                                <MaterialIcons name="zoom-in" size={12} color="#fff" />
                              </View>
                            </TouchableOpacity>
                          ) : (
                            <Text style={styles.noImgText}>No photo</Text>
                          )}
                        </View>

                        <View style={styles.drawerKmCol}>
                          <Text style={styles.drawerKmLabel}>End Meter</Text>
                          <Text style={styles.drawerKmVal}>
                            {selectedExpense.closing_km !== null && selectedExpense.closing_km !== undefined
                              ? `${selectedExpense.closing_km} KM`
                              : "—"}
                          </Text>
                          {selectedExpense.closing_km_photo_url ? (
                            <TouchableOpacity
                              style={styles.drawerThumbBtn}
                              onPress={() =>
                                openPhotoViewer(selectedExpense.closing_km_photo_url || null, "Closing Meter Photo")
                              }
                            >
                              <Image
                                source={{ uri: selectedExpense.closing_km_photo_url }}
                                style={styles.drawerThumbImg}
                              />
                              <View style={styles.zoomBadge}>
                                <MaterialIcons name="zoom-in" size={12} color="#fff" />
                              </View>
                            </TouchableOpacity>
                          ) : (
                            <Text style={styles.noImgText}>No photo</Text>
                          )}
                        </View>
                      </View>

                      {/* Calculation Breakdown Banner */}
                      <View style={styles.travelCalcCard}>
                        <View style={styles.travelCalcCol}>
                          <Text style={styles.travelCalcLabel}>Distance</Text>
                          <Text style={styles.travelCalcVal}>
                            {selectedExpense.distance_km !== null && selectedExpense.distance_km !== undefined
                              ? `${selectedExpense.distance_km} KM`
                              : "—"}
                          </Text>
                        </View>
                        <View style={styles.travelCalcDivider} />
                        <View style={styles.travelCalcCol}>
                          <Text style={styles.travelCalcLabel}>Rate</Text>
                          <Text style={styles.travelCalcVal}>
                            {selectedExpense.applied_rate_per_km
                              ? `₹${selectedExpense.applied_rate_per_km.toFixed(2)}/km`
                              : "Standard"}
                          </Text>
                        </View>
                        <View style={styles.travelCalcDivider} />
                        <View style={styles.travelCalcCol}>
                          <Text style={styles.travelCalcLabel}>Claimed Amount</Text>
                          <Text style={[styles.travelCalcVal, { color: "#059669", fontWeight: "800" }]}>
                            ₹{selectedExpense.amount_formatted}
                          </Text>
                        </View>
                      </View>
                    </View>
                  ) : (
                    /* Regular Manual Bill Photo */
                    selectedExpense.bill_photo_url ? (
                      <>
                        <Text style={styles.drawerSectionHeader}>Receipt Voucher / Bill</Text>
                        <TouchableOpacity
                          style={styles.expBillViewer}
                          onPress={() =>
                            openPhotoViewer(selectedExpense.bill_photo_url, "Expense Receipt")
                          }
                        >
                          <Image source={{ uri: selectedExpense.bill_photo_url }} style={styles.expBillImg} />
                          <View style={styles.billZoomBanner}>
                            <MaterialIcons name="zoom-in" size={14} color="#fff" />
                            <Text style={styles.billZoomText}>Tap to Zoom Full Receipt</Text>
                          </View>
                        </TouchableOpacity>
                      </>
                    ) : null
                  )}

                  {/* Itemized Table */}
                  {selectedExpense.items && selectedExpense.items.length > 0 && (
                    <>
                      <Text style={styles.drawerSectionHeader}>Itemized Breakdown</Text>
                      <View style={styles.itemsBreakdownWrap}>
                        {selectedExpense.items.map((it, idx) => (
                          <View key={idx} style={styles.itemRowSplit}>
                            <Text style={styles.itemRowName}>
                              {it.item_name} <Text style={{ color: "#6B7280" }}>(x{it.quantity})</Text>
                            </Text>
                            <Text style={styles.itemRowAmount}>₹{it.total_price.toFixed(2)}</Text>
                          </View>
                        ))}
                      </View>
                    </>
                  )}

                  {/* Audit Trail Notes */}
                  {selectedExpense.temp_approved_by && (
                    <View style={styles.auditCard}>
                      <MaterialIcons name="verified-user" size={16} color="#4338CA" />
                      <Text style={styles.auditCardText}>
                        Verified by {selectedExpense.temp_approved_by} on {selectedExpense.temp_approved_at || "—"}
                        {selectedExpense.temp_approved_note ? `\n"${selectedExpense.temp_approved_note}"` : ""}
                      </Text>
                    </View>
                  )}

                  {selectedExpense.rejection_reason && (
                    <View style={[styles.auditCard, { backgroundColor: "#FEE2E2" }]}>
                      <MaterialIcons name="error-outline" size={16} color="#DC2626" />
                      <Text style={[styles.auditCardText, { color: "#B91C1C" }]}>
                        Rejection Reason: {selectedExpense.rejection_reason}
                      </Text>
                    </View>
                  )}
                </>
              )}
            </ScrollView>

            {/* Modal Bottom Action Buttons */}
            {selectedExpense && (
              <View style={[styles.drawerActionRow, { paddingBottom: Math.max(insets.bottom, 16) + 12 }]}>
                {(selectedExpense.can_temp_approve || selectedExpense.can_final_approve) && (
                  <TouchableOpacity
                    style={[styles.modalActionBtn, styles.modalRejectBtnLarge]}
                    onPress={() => {
                      openActionDialog(
                        "expense",
                        selectedExpense.id,
                        selectedExpense.can_temp_approve ? "manager_reject" : "reject",
                        selectedExpense.employee_name
                      );
                    }}
                  >
                    <MaterialIcons name="close" size={18} color="#DC2626" />
                    <Text style={styles.modalRejectTextLarge}>Reject</Text>
                  </TouchableOpacity>
                )}

                {selectedExpense.can_edit && (
                  <TouchableOpacity
                    style={[styles.modalActionBtn, styles.modalEditBtnLarge]}
                    onPress={() => {
                      const exp = selectedExpense;
                      setSelectedExpense(null);
                      openEditExpense(exp);
                    }}
                  >
                    <MaterialIcons name="edit" size={18} color="#7367F0" />
                    <Text style={styles.modalEditTextLarge}>Edit Claim</Text>
                  </TouchableOpacity>
                )}

                {selectedExpense.can_temp_approve && (
                  <TouchableOpacity
                    style={[styles.modalActionBtn, styles.modalVerifyBtnLarge]}
                    onPress={() => {
                      openActionDialog("expense", selectedExpense.id, "temp_approve", selectedExpense.employee_name);
                    }}
                  >
                    <MaterialIcons name="done-all" size={18} color="#FFFFFF" />
                    <Text style={styles.modalApproveTextLarge}>Verify (L1)</Text>
                  </TouchableOpacity>
                )}

                {selectedExpense.can_final_approve && (
                  <TouchableOpacity
                    style={[styles.modalActionBtn, styles.modalApproveBtnLarge]}
                    onPress={() => {
                      openActionDialog("expense", selectedExpense.id, "final_approve", selectedExpense.employee_name);
                    }}
                  >
                    <MaterialIcons name="check-circle" size={18} color="#FFFFFF" />
                    <Text style={styles.modalApproveTextLarge}>Approve (L2)</Text>
                  </TouchableOpacity>
                )}
              </View>
            )}
          </View>
        </View>
      </Modal>

      {/* ── ACTION CONFIRMATION MODAL ── */}
      <Modal
        visible={actionModalVisible}
        transparent
        animationType="fade"
        onRequestClose={() => setActionModalVisible(false)}
      >
        <View style={styles.modalBackdrop}>
          <View style={styles.modalContent}>
            <Text style={styles.modalTitle}>{actionTarget?.title}</Text>
            <Text style={styles.modalDesc}>{actionTarget?.description}</Text>

            <TextInput
              style={styles.modalTextInput}
              placeholder={
                actionTarget?.requiresReason
                  ? "Enter rejection reason (Required)..."
                  : "Optional note or remarks..."
              }
              placeholderTextColor="#9CA3AF"
              value={actionNote}
              onChangeText={setActionNote}
              multiline
              numberOfLines={3}
            />

            <View style={styles.modalActions}>
              <TouchableOpacity
                style={[styles.modalBtn, styles.modalCancelBtn]}
                onPress={() => setActionModalVisible(false)}
                disabled={submittingAction}
              >
                <Text style={styles.modalCancelText}>Cancel</Text>
              </TouchableOpacity>

              <TouchableOpacity
                style={[
                  styles.modalBtn,
                  actionTarget?.requiresReason ? styles.modalRejectBtn : styles.modalApproveBtn,
                ]}
                onPress={submitAction}
                disabled={submittingAction}
              >
                {submittingAction ? (
                  <ActivityIndicator size="small" color="#fff" />
                ) : (
                  <Text style={styles.modalConfirmText}>
                    {actionTarget?.requiresReason ? "Confirm Rejection" : "Confirm Action"}
                  </Text>
                )}
              </TouchableOpacity>
            </View>
          </View>
        </View>
      </Modal>

      {/* ── HIGH-RES PHOTO VIEWER ── */}
      <Modal
        visible={photoViewerVisible}
        transparent
        animationType="fade"
        onRequestClose={() => setPhotoViewerVisible(false)}
      >
        <View style={styles.photoViewerBackdrop}>
          <SafeAreaView style={styles.photoViewerSafe}>
            <View style={styles.photoViewerHeader}>
              <Text style={styles.photoViewerTitle} numberOfLines={1}>
                {selectedPhotoTitle}
              </Text>
              <TouchableOpacity style={styles.photoCloseBtn} onPress={() => setPhotoViewerVisible(false)}>
                <MaterialIcons name="close" size={24} color="#FFFFFF" />
              </TouchableOpacity>
            </View>

            <View style={styles.photoViewerBody}>
              {selectedPhotoUrl && (
                <Image source={{ uri: selectedPhotoUrl }} style={styles.fullScreenImg} resizeMode="contain" />
              )}
            </View>
          </SafeAreaView>
        </View>
      </Modal>

      {/* ── TOUR PLAN EDIT MODAL ── */}
      <Modal
        visible={editDayStartModalVisible}
        transparent
        animationType="slide"
        onRequestClose={() => setEditDayStartModalVisible(false)}
      >
        <View style={styles.drawerBackdrop}>
          <View style={styles.drawerCard}>
            <View style={styles.drawerHeader}>
              <View style={{ flex: 1 }}>
                <Text style={styles.drawerTitle}>Edit Tour Plan</Text>
                <Text style={styles.drawerSubtitle}>
                  {editingDayStart?.employee_name} ({editingDayStart?.employee_id}) • {editingDayStart?.date_formatted}
                </Text>
              </View>
              <TouchableOpacity style={styles.drawerCloseBtn} onPress={() => setEditDayStartModalVisible(false)}>
                <MaterialIcons name="close" size={24} color="#6B7280" />
              </TouchableOpacity>
            </View>

            <ScrollView
              style={styles.drawerBody}
              contentContainerStyle={{ paddingBottom: 24 }}
              showsVerticalScrollIndicator={false}
              keyboardShouldPersistTaps="handled"
            >
              {/* Kilometer Meter Readings Row */}
              <Text style={styles.formSectionTitle}>Odometer Readings (KM)</Text>
              <View style={styles.formRowSplit}>
                <View style={styles.formFieldHalf}>
                  <Text style={styles.formFieldLabel}>Opening KM</Text>
                  <TextInput
                    style={styles.formInput}
                    keyboardType="numeric"
                    placeholder="e.g. 10250"
                    placeholderTextColor="#9CA3AF"
                    value={editDsOpeningKm}
                    onChangeText={setEditDsOpeningKm}
                  />
                </View>
                <View style={styles.formFieldHalf}>
                  <Text style={styles.formFieldLabel}>Closing KM</Text>
                  <TextInput
                    style={styles.formInput}
                    keyboardType="numeric"
                    placeholder="e.g. 10320"
                    placeholderTextColor="#9CA3AF"
                    value={editDsClosingKm}
                    onChangeText={setEditDsClosingKm}
                  />
                </View>
              </View>

              {/* Vehicle Selection */}
              <Text style={styles.formSectionTitle}>Vehicle Details</Text>
              <Text style={styles.formFieldLabel}>Vehicle Type</Text>
              <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.vehicleChipScroll}>
                {vehicleTypesList.map((v) => (
                  <TouchableOpacity
                    key={v}
                    style={[styles.vehicleChip, editDsVehicleType === v && styles.vehicleChipActive]}
                    onPress={() => setEditDsVehicleType(v)}
                  >
                    <Text style={[styles.vehicleChipText, editDsVehicleType === v && styles.vehicleChipTextActive]}>
                      {v}
                    </Text>
                  </TouchableOpacity>
                ))}
              </ScrollView>

              <Text style={[styles.formFieldLabel, { marginTop: 10 }]}>Vehicle Reg. Number</Text>
              <TextInput
                style={styles.formInput}
                placeholder="e.g. MH-12-AB-1234"
                placeholderTextColor="#9CA3AF"
                value={editDsVehicleNo}
                onChangeText={setEditDsVehicleNo}
                autoCapitalize="characters"
              />

              {/* Visit Details */}
              <Text style={styles.formSectionTitle}>Visit & Tour Details</Text>
              <Text style={styles.formFieldLabel}>Visit Place / Areas</Text>
              <TextInput
                style={styles.formInput}
                placeholder="e.g. Pune Central, Market Yard"
                placeholderTextColor="#9CA3AF"
                value={editDsVisitPlace}
                onChangeText={setEditDsVisitPlace}
              />

              <Text style={[styles.formFieldLabel, { marginTop: 10 }]}>Tour Purpose</Text>
              <TextInput
                style={styles.formInput}
                placeholder="e.g. Dealer Visit & Field Audit"
                placeholderTextColor="#9CA3AF"
                value={editDsTourPurpose}
                onChangeText={setEditDsTourPurpose}
              />

              <Text style={[styles.formFieldLabel, { marginTop: 10 }]}>Remarks</Text>
              <TextInput
                style={[styles.formInput, { minHeight: 60, textAlignVertical: "top" }]}
                placeholder="Subordinate or manager remarks..."
                placeholderTextColor="#9CA3AF"
                value={editDsRemarks}
                onChangeText={setEditDsRemarks}
                multiline
                numberOfLines={2}
              />

              {/* Audit Reason */}
              <Text style={styles.formSectionTitle}>Manager Audit Reason</Text>
              <TextInput
                style={[styles.formInput, { minHeight: 60, textAlignVertical: "top", borderColor: "#C7D2FE", backgroundColor: "#F5F3FF" }]}
                placeholder="Reason for adjustment (e.g. Adjusted closing KM as per meter image)..."
                placeholderTextColor="#9CA3AF"
                value={editDsReason}
                onChangeText={setEditDsReason}
                multiline
                numberOfLines={2}
              />
              <View style={{ height: 20 }} />
            </ScrollView>

            <View style={[styles.drawerActionRow, { paddingBottom: Math.max(insets.bottom, 16) + 12 }]}>
              <TouchableOpacity
                style={[styles.modalActionBtn, styles.modalRejectBtnLarge]}
                onPress={() => setEditDayStartModalVisible(false)}
                disabled={savingDsEdit}
              >
                <Text style={styles.modalRejectTextLarge}>Cancel</Text>
              </TouchableOpacity>

              <TouchableOpacity
                style={[styles.modalActionBtn, styles.modalApproveBtnLarge]}
                onPress={handleSaveDayStartEdit}
                disabled={savingDsEdit}
              >
                {savingDsEdit ? (
                  <ActivityIndicator size="small" color="#FFFFFF" />
                ) : (
                  <>
                    <MaterialIcons name="save" size={18} color="#FFFFFF" />
                    <Text style={styles.modalApproveTextLarge}>Save Changes</Text>
                  </>
                )}
              </TouchableOpacity>
            </View>
          </View>
        </View>
      </Modal>

      {/* ── EXPENSE EDIT MODAL ── */}
      <Modal
        visible={editExpenseModalVisible}
        transparent
        animationType="slide"
        onRequestClose={() => setEditExpenseModalVisible(false)}
      >
        <View style={styles.drawerBackdrop}>
          <View style={styles.drawerCard}>
            <View style={styles.drawerHeader}>
              <View style={{ flex: 1 }}>
                <Text style={styles.drawerTitle}>Edit Expense Claim</Text>
                <Text style={styles.drawerSubtitle}>
                  {editingExpense?.employee_name} ({editingExpense?.employee_id}) • {editingExpense?.date_formatted}
                </Text>
              </View>
              <TouchableOpacity style={styles.drawerCloseBtn} onPress={() => setEditExpenseModalVisible(false)}>
                <MaterialIcons name="close" size={24} color="#6B7280" />
              </TouchableOpacity>
            </View>

            <ScrollView
              style={styles.drawerBody}
              contentContainerStyle={{ paddingBottom: 24 }}
              showsVerticalScrollIndicator={false}
              keyboardShouldPersistTaps="handled"
            >
              {editingExpense?.is_system_generated && (
                <View style={styles.systemGenNotice}>
                  <MaterialIcons name="info" size={16} color="#7367F0" />
                  <Text style={styles.systemGenNoticeText}>
                    System-generated travel allowance. Changing amount will update linked daily report automatically.
                  </Text>
                </View>
              )}

              {/* Claim Amount */}
              <Text style={styles.formSectionTitle}>Claim Amount (₹)</Text>
              <TextInput
                style={[styles.formInput, { fontSize: 18, fontWeight: "800", color: PURPLE }]}
                keyboardType="numeric"
                placeholder="0.00"
                placeholderTextColor="#9CA3AF"
                value={editExpAmount}
                onChangeText={setEditExpAmount}
              />

              {/* Category Selection */}
              {!editingExpense?.is_system_generated && expenseCategoriesList.length > 0 && (
                <>
                  <Text style={styles.formSectionTitle}>Expense Category</Text>
                  <View style={styles.categoryChipWrap}>
                    {expenseCategoriesList.map((cat) => (
                      <TouchableOpacity
                        key={cat.id}
                        style={[styles.categoryChip, editExpCategoryId === cat.id && styles.categoryChipActive]}
                        onPress={() => setEditExpCategoryId(cat.id)}
                      >
                        <Text style={[styles.categoryChipText, editExpCategoryId === cat.id && styles.categoryChipTextActive]}>
                          {cat.name}
                        </Text>
                      </TouchableOpacity>
                    ))}
                  </View>
                </>
              )}

              {/* Expense Date */}
              <Text style={styles.formSectionTitle}>Claim Date</Text>
              <TextInput
                style={styles.formInput}
                placeholder="YYYY-MM-DD"
                placeholderTextColor="#9CA3AF"
                value={editExpDate}
                onChangeText={setEditExpDate}
              />

              {/* Manager Audit Note */}
              <Text style={styles.formSectionTitle}>Manager Audit Reason</Text>
              <TextInput
                style={[styles.formInput, { minHeight: 60, textAlignVertical: "top", borderColor: "#C7D2FE", backgroundColor: "#F5F3FF" }]}
                placeholder="Reason for adjustment (e.g. Corrected food bill rate per policy)..."
                placeholderTextColor="#9CA3AF"
                value={editExpReason}
                onChangeText={setEditExpReason}
                multiline
                numberOfLines={2}
              />
              <View style={{ height: 20 }} />
            </ScrollView>

            <View style={[styles.drawerActionRow, { paddingBottom: Math.max(insets.bottom, 16) + 12 }]}>
              <TouchableOpacity
                style={[styles.modalActionBtn, styles.modalRejectBtnLarge]}
                onPress={() => setEditExpenseModalVisible(false)}
                disabled={savingExpEdit}
              >
                <Text style={styles.modalRejectTextLarge}>Cancel</Text>
              </TouchableOpacity>

              <TouchableOpacity
                style={[styles.modalActionBtn, styles.modalApproveBtnLarge]}
                onPress={handleSaveExpenseEdit}
                disabled={savingExpEdit}
              >
                {savingExpEdit ? (
                  <ActivityIndicator size="small" color="#FFFFFF" />
                ) : (
                  <>
                    <MaterialIcons name="save" size={18} color="#FFFFFF" />
                    <Text style={styles.modalApproveTextLarge}>Save Changes</Text>
                  </>
                )}
              </TouchableOpacity>
            </View>
          </View>
        </View>
      </Modal>

      {/* ── EMPLOYEE SELECTOR MODAL ── */}
      <Modal
        visible={employeeModalVisible}
        transparent
        animationType="slide"
        onRequestClose={() => {
          setEmployeeModalVisible(false);
          setEmployeeSearch("");
        }}
      >
        <View style={styles.drawerBackdrop}>
          <View style={[styles.drawerCard, { maxHeight: "75%", paddingBottom: drawerBottomPadding }]}>
            <View style={styles.drawerHeader}>
              <View style={{ flex: 1 }}>
                <Text style={styles.drawerTitle}>Filter by Team Employee</Text>
                <Text style={styles.drawerSubtitle}>
                  {teamMembers.length} team subordinates available
                </Text>
              </View>
              <TouchableOpacity
                style={styles.drawerCloseBtn}
                onPress={() => {
                  setEmployeeModalVisible(false);
                  setEmployeeSearch("");
                }}
              >
                <MaterialIcons name="close" size={24} color="#6B7280" />
              </TouchableOpacity>
            </View>

            {/* Sub-search inside employee list */}
            {teamMembers.length > 4 && (
              <View style={styles.employeeSearchWrap}>
                <MaterialIcons name="search" size={18} color="#9CA3AF" />
                <TextInput
                  style={styles.employeeSearchInput}
                  placeholder="Filter subordinates by name, code..."
                  placeholderTextColor="#9CA3AF"
                  value={employeeSearch}
                  onChangeText={setEmployeeSearch}
                  clearButtonMode="while-editing"
                />
              </View>
            )}

            <ScrollView
              style={styles.drawerBody}
              contentContainerStyle={{ paddingBottom: 24 }}
              showsVerticalScrollIndicator={false}
              keyboardShouldPersistTaps="handled"
            >
              {/* Option: All Employees */}
              <TouchableOpacity
                style={[
                  styles.employeeOptionRow,
                  selectedUserId === "all" && styles.employeeOptionRowActive,
                ]}
                activeOpacity={0.75}
                onPress={() => handleSelectEmployee("all")}
              >
                <View style={[styles.employeeAvatar, selectedUserId === "all" && styles.employeeAvatarActive]}>
                  <MaterialIcons
                    name="people"
                    size={20}
                    color={selectedUserId === "all" ? "#FFFFFF" : "#6B7280"}
                  />
                </View>
                <View style={{ flex: 1 }}>
                  <Text
                    style={[
                      styles.employeeOptionName,
                      selectedUserId === "all" && styles.employeeOptionNameActive,
                    ]}
                  >
                    All Team Members
                  </Text>
                  <Text style={styles.employeeOptionSub}>Show approval claims from all team subordinates</Text>
                </View>
                {selectedUserId === "all" && (
                  <MaterialIcons name="check-circle" size={20} color={PURPLE} />
                )}
              </TouchableOpacity>

              {/* Individual Subordinates */}
              {filteredTeamMembers.map((member) => {
                const isSelected = selectedUserId === member.id;
                const initials = member.name
                  .split(" ")
                  .map((n) => n[0])
                  .filter(Boolean)
                  .slice(0, 2)
                  .join("")
                  .toUpperCase();

                return (
                  <TouchableOpacity
                    key={member.id}
                    style={[
                      styles.employeeOptionRow,
                      isSelected && styles.employeeOptionRowActive,
                    ]}
                    activeOpacity={0.75}
                    onPress={() => handleSelectEmployee(member.id)}
                  >
                    <View style={[styles.employeeAvatar, isSelected && styles.employeeAvatarActive]}>
                      <Text style={[styles.employeeAvatarText, isSelected && styles.employeeAvatarTextActive]}>
                        {initials || "FW"}
                      </Text>
                    </View>
                    <View style={{ flex: 1 }}>
                      <Text style={[styles.employeeOptionName, isSelected && styles.employeeOptionNameActive]}>
                        {member.name}
                      </Text>
                      <Text style={styles.employeeOptionSub}>
                        {member.employee_id || "ID: —"} • {member.position || member.department || "Subordinate"}
                      </Text>
                    </View>
                    {isSelected && (
                      <MaterialIcons name="check-circle" size={20} color={PURPLE} />
                    )}
                  </TouchableOpacity>
                );
              })}

              {filteredTeamMembers.length === 0 && (
                <View style={styles.emptyEmployeeList}>
                  <Text style={styles.emptyEmployeeText}>No team subordinates found matching "{employeeSearch}"</Text>
                </View>
              )}
            </ScrollView>
          </View>
        </View>
      </Modal>
    </View>
  );
}

const styles = StyleSheet.create({
  safe: {
    flex: 1,
    backgroundColor: BRAND_BG,
  },
  // ── Segmented Main Tabs ──
  mainTabContainer: {
    flexDirection: "row",
    backgroundColor: "#FFFFFF",
    paddingHorizontal: 16,
    paddingVertical: 10,
    gap: 12,
    borderBottomWidth: 1,
    borderBottomColor: "#E5E7EB",
  },
  mainTab: {
    flex: 1,
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    paddingVertical: 10,
    paddingHorizontal: 12,
    borderRadius: 12,
    backgroundColor: "#F3F4F6",
    gap: 8,
  },
  mainTabActive: {
    backgroundColor: PURPLE,
    shadowColor: PURPLE,
    shadowOffset: { width: 0, height: 3 },
    shadowOpacity: 0.25,
    shadowRadius: 5,
    elevation: 3,
  },
  mainTabText: {
    fontSize: 14,
    fontWeight: "700",
    color: "#4B5563",
  },
  mainTabTextActive: {
    color: "#FFFFFF",
  },
  tabBadge: {
    backgroundColor: PURPLE,
    borderRadius: 10,
    paddingHorizontal: 6,
    paddingVertical: 2,
    minWidth: 20,
    alignItems: "center",
    justifyContent: "center",
  },
  tabBadgeText: {
    color: "#FFFFFF",
    fontSize: 11,
    fontWeight: "800",
  },

  // ── Search Bar ──
  searchContainer: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#FFFFFF",
    marginHorizontal: 16,
    marginTop: 8,
    marginBottom: 6,
    paddingHorizontal: 12,
    paddingVertical: Platform.OS === "ios" ? 10 : 6,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: "#E5E7EB",
  },
  searchIcon: {
    marginRight: 8,
  },
  searchInput: {
    flex: 1,
    fontSize: 14,
    color: "#1F2937",
    padding: 0,
  },

  // ── Filter Chips ──
  filterScrollContainer: {
    marginBottom: 6,
  },
  filterScroll: {
    paddingHorizontal: 16,
    gap: 8,
    flexDirection: "row",
  },
  chip: {
    paddingHorizontal: 14,
    paddingVertical: 7,
    borderRadius: 20,
    backgroundColor: "#FFFFFF",
    borderWidth: 1,
    borderColor: "#E5E7EB",
  },
  chipActive: {
    backgroundColor: "#EDE9FE",
    borderColor: PURPLE,
  },
  chipText: {
    fontSize: 12,
    fontWeight: "600",
    color: "#6B7280",
  },
  chipTextActive: {
    color: PURPLE,
    fontWeight: "700",
  },

  // ── Table Card Container ──
  tableCardContainer: {
    flex: 1,
    backgroundColor: "#FFFFFF",
    marginHorizontal: 16,
    marginBottom: 16,
    borderRadius: 14,
    borderWidth: 1,
    borderColor: "#E5E7EB",
    overflow: "hidden",
  },
  tableVerticalScroll: {
    flex: 1,
  },
  tableHorizontalScroll: {
    flex: 1,
  },
  tableHeaderRow: {
    flexDirection: "row",
    backgroundColor: "#F9FAFB",
    borderBottomWidth: 1.5,
    borderBottomColor: "#E5E7EB",
    paddingVertical: 12,
    paddingHorizontal: 12,
  },
  thCell: {
    fontSize: 11,
    fontWeight: "800",
    color: "#4B5563",
    letterSpacing: 0.5,
    paddingHorizontal: 4,
  },
  tableRow: {
    flexDirection: "row",
    alignItems: "center",
    borderBottomWidth: 1,
    borderBottomColor: "#F3F4F6",
    paddingVertical: 10,
    paddingHorizontal: 12,
    backgroundColor: "#FFFFFF",
  },
  tableRowAlt: {
    backgroundColor: "#FAFAFA",
  },
  tdCell: {
    paddingHorizontal: 4,
    justifyContent: "center",
  },
  tdPrimaryText: {
    fontSize: 13,
    fontWeight: "700",
    color: "#111827",
  },
  tdSecondaryText: {
    fontSize: 11,
    color: "#6B7280",
    marginTop: 2,
  },
  actionCellGroup: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    gap: 6,
  },
  actionMiniBtn: {
    width: 32,
    height: 32,
    borderRadius: 8,
    alignItems: "center",
    justifyContent: "center",
  },
  actionMiniApprove: {
    backgroundColor: "#10B981",
  },
  actionMiniVerify: {
    backgroundColor: "#3B82F6",
  },
  actionMiniReject: {
    backgroundColor: "#FEE2E2",
    borderWidth: 1,
    borderColor: "#FECACA",
  },
  actionMiniView: {
    backgroundColor: "#F3F4F6",
  },
  tableThumbBtn: {
    width: 36,
    height: 36,
    borderRadius: 6,
    overflow: "hidden",
    borderWidth: 1,
    borderColor: "#E5E7EB",
  },
  tableThumbImg: {
    width: "100%",
    height: "100%",
  },

  // ── Status Pill ──
  statusPill: {
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 6,
  },
  statusPillText: {
    fontSize: 9,
    fontWeight: "800",
    letterSpacing: 0.4,
  },

  // ── Table Loaders & Empty ──
  tableLoader: {
    flex: 1,
    alignItems: "center",
    justifyContent: "center",
    paddingVertical: 60,
    gap: 10,
  },
  loaderText: {
    fontSize: 13,
    color: "#6B7280",
    fontWeight: "500",
  },
  emptyTable: {
    flex: 1,
    alignItems: "center",
    justifyContent: "center",
    paddingVertical: 60,
    paddingHorizontal: 24,
    gap: 8,
  },
  emptyTitle: {
    fontSize: 15,
    fontWeight: "800",
    color: "#374151",
  },
  emptySub: {
    fontSize: 12,
    color: "#9CA3AF",
    textAlign: "center",
  },

  // ── Pagination Controls Bar ──
  paginationContainer: {
    paddingHorizontal: 14,
    paddingVertical: 10,
    backgroundColor: "#F9FAFB",
    borderTopWidth: 1,
    borderTopColor: "#E5E7EB",
    gap: 8,
  },
  paginationTopRow: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
  },
  paginationInfoText: {
    fontSize: 12,
    color: "#4B5563",
  },
  boldText: {
    fontWeight: "700",
    color: "#111827",
  },
  perPageWrap: {
    flexDirection: "row",
    alignItems: "center",
    gap: 4,
  },
  perPageLabel: {
    fontSize: 11,
    color: "#6B7280",
    fontWeight: "600",
  },
  perPageBtn: {
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 6,
    backgroundColor: "#FFFFFF",
    borderWidth: 1,
    borderColor: "#D1D5DB",
  },
  perPageBtnActive: {
    backgroundColor: PURPLE,
    borderColor: PURPLE,
  },
  perPageBtnText: {
    fontSize: 11,
    fontWeight: "700",
    color: "#4B5563",
  },
  perPageBtnTextActive: {
    color: "#FFFFFF",
  },
  paginationBtnGroup: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    gap: 6,
  },
  pageBtn: {
    flexDirection: "row",
    alignItems: "center",
    paddingHorizontal: 10,
    paddingVertical: 6,
    borderRadius: 8,
    backgroundColor: "#FFFFFF",
    borderWidth: 1,
    borderColor: "#D1D5DB",
    gap: 2,
  },
  pageBtnSquare: {
    paddingHorizontal: 7,
  },
  pageBtnDisabled: {
    backgroundColor: "#F3F4F6",
    borderColor: "#E5E7EB",
  },
  pageBtnText: {
    fontSize: 12,
    fontWeight: "700",
    color: PURPLE,
  },
  pageBtnTextDisabled: {
    color: "#9CA3AF",
  },
  pageNumberBadge: {
    paddingHorizontal: 10,
    paddingVertical: 5,
    backgroundColor: "#FFFFFF",
    borderRadius: 8,
    borderWidth: 1,
    borderColor: "#E5E7EB",
  },
  pageNumberText: {
    fontSize: 12,
    fontWeight: "700",
    color: "#374151",
  },

  // ── Detail Modal / Drawer ──
  drawerBackdrop: {
    flex: 1,
    backgroundColor: "rgba(0,0,0,0.5)",
    justifyContent: "flex-end",
  },
  drawerCard: {
    backgroundColor: "#FFFFFF",
    borderTopLeftRadius: 22,
    borderTopRightRadius: 22,
    maxHeight: "85%",
    paddingBottom: Platform.OS === "ios" ? 34 : 20,
  },
  drawerHeader: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    paddingHorizontal: 20,
    paddingVertical: 16,
    borderBottomWidth: 1,
    borderBottomColor: "#E5E7EB",
  },
  drawerTitle: {
    fontSize: 17,
    fontWeight: "800",
    color: "#111827",
  },
  drawerSubtitle: {
    fontSize: 12,
    color: "#6B7280",
    marginTop: 2,
  },
  drawerCloseBtn: {
    padding: 6,
  },
  drawerBody: {
    paddingHorizontal: 20,
    paddingVertical: 14,
  },
  drawerGrid: {
    backgroundColor: "#F9FAFB",
    borderRadius: 12,
    padding: 12,
    gap: 8,
    borderWidth: 1,
    borderColor: "#E5E7EB",
    marginBottom: 14,
  },
  drawerInfoRow: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
  },
  drawerInfoLabel: {
    fontSize: 12,
    fontWeight: "600",
    color: "#6B7280",
    width: 90,
  },
  drawerInfoVal: {
    fontSize: 13,
    fontWeight: "700",
    color: "#111827",
    flex: 1,
    textAlign: "right",
  },
  drawerSectionHeader: {
    fontSize: 13,
    fontWeight: "800",
    color: "#374151",
    marginBottom: 8,
    marginTop: 4,
  },
  drawerKmWrap: {
    flexDirection: "row",
    gap: 12,
    marginBottom: 14,
  },
  drawerKmCol: {
    flex: 1,
    alignItems: "center",
    backgroundColor: "#F9FAFB",
    padding: 12,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: "#E5E7EB",
  },
  drawerKmLabel: {
    fontSize: 11,
    fontWeight: "600",
    color: "#6B7280",
    marginBottom: 2,
  },
  drawerKmVal: {
    fontSize: 14,
    fontWeight: "800",
    color: "#111827",
    marginBottom: 8,
  },
  drawerThumbBtn: {
    width: 70,
    height: 70,
    borderRadius: 8,
    overflow: "hidden",
    position: "relative",
    borderWidth: 1,
    borderColor: "#D1D5DB",
  },
  drawerThumbImg: {
    width: "100%",
    height: "100%",
  },
  zoomBadge: {
    position: "absolute",
    bottom: 2,
    right: 2,
    backgroundColor: "rgba(0,0,0,0.6)",
    borderRadius: 4,
    padding: 2,
  },
  noImgText: {
    fontSize: 11,
    color: "#9CA3AF",
    fontStyle: "italic",
    marginTop: 10,
  },
  remarksBox: {
    backgroundColor: "#F9FAFB",
    padding: 12,
    borderRadius: 10,
    borderWidth: 1,
    borderColor: "#E5E7EB",
    marginBottom: 14,
  },
  remarksTitle: {
    fontSize: 11,
    fontWeight: "700",
    color: "#4B5563",
    marginBottom: 4,
  },
  remarksContent: {
    fontSize: 12,
    color: "#1F2937",
    lineHeight: 16,
  },
  drawerActionRow: {
    flexDirection: "row",
    gap: 12,
    paddingHorizontal: 20,
    paddingTop: 14,
    paddingBottom: 8,
    borderTopWidth: 1,
    borderTopColor: "#E5E7EB",
  },
  modalActionBtn: {
    flex: 1,
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    paddingVertical: 12,
    borderRadius: 12,
    gap: 6,
  },
  modalApproveBtnLarge: {
    backgroundColor: "#10B981",
  },
  modalVerifyBtnLarge: {
    backgroundColor: "#3B82F6",
  },
  modalRejectBtnLarge: {
    backgroundColor: "#FEE2E2",
    borderWidth: 1,
    borderColor: "#FECACA",
  },
  modalApproveTextLarge: {
    color: "#FFFFFF",
    fontSize: 14,
    fontWeight: "700",
  },
  modalRejectTextLarge: {
    color: "#DC2626",
    fontSize: 14,
    fontWeight: "700",
  },

  // ── Expense Drawer Details ──
  expHeroBox: {
    alignItems: "center",
    backgroundColor: "#EDE9FE",
    padding: 16,
    borderRadius: 14,
    marginBottom: 14,
  },
  expHeroLabel: {
    fontSize: 10,
    fontWeight: "800",
    color: PURPLE,
    letterSpacing: 0.6,
  },
  expHeroAmount: {
    fontSize: 26,
    fontWeight: "900",
    color: PURPLE,
    marginVertical: 4,
  },
  expHeroCategory: {
    fontSize: 13,
    fontWeight: "700",
    color: "#4338CA",
  },
  expBillViewer: {
    borderRadius: 12,
    overflow: "hidden",
    borderWidth: 1,
    borderColor: "#E5E7EB",
    marginBottom: 14,
  },
  expBillImg: {
    width: "100%",
    height: 180,
    backgroundColor: "#F3F4F6",
  },
  billZoomBanner: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "center",
    backgroundColor: "rgba(0,0,0,0.7)",
    paddingVertical: 6,
    gap: 4,
  },
  billZoomText: {
    fontSize: 11,
    color: "#fff",
    fontWeight: "700",
  },
  itemsBreakdownWrap: {
    backgroundColor: "#F9FAFB",
    padding: 12,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: "#E5E7EB",
    marginBottom: 14,
    gap: 6,
  },
  itemRowSplit: {
    flexDirection: "row",
    justifyContent: "space-between",
  },
  itemRowName: {
    fontSize: 12,
    color: "#374151",
    fontWeight: "600",
  },
  itemRowAmount: {
    fontSize: 12,
    fontWeight: "800",
    color: "#111827",
  },
  auditCard: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#EEF2FF",
    padding: 10,
    borderRadius: 10,
    gap: 8,
    marginBottom: 12,
  },
  auditCardText: {
    fontSize: 11,
    color: "#3730A3",
    fontWeight: "600",
    flex: 1,
  },

  // ── Confirmation Modal ──
  modalBackdrop: {
    flex: 1,
    backgroundColor: "rgba(0,0,0,0.5)",
    alignItems: "center",
    justifyContent: "center",
    padding: 20,
  },
  modalContent: {
    backgroundColor: "#FFFFFF",
    borderRadius: 18,
    padding: 20,
    width: "100%",
    maxWidth: 400,
    shadowColor: "#000",
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.2,
    shadowRadius: 10,
    elevation: 8,
  },
  modalTitle: {
    fontSize: 17,
    fontWeight: "800",
    color: "#111827",
    marginBottom: 6,
  },
  modalDesc: {
    fontSize: 13,
    color: "#4B5563",
    lineHeight: 18,
    marginBottom: 16,
  },
  modalTextInput: {
    backgroundColor: "#F9FAFB",
    borderWidth: 1,
    borderColor: "#E5E7EB",
    borderRadius: 10,
    padding: 12,
    fontSize: 13,
    color: "#111827",
    textAlignVertical: "top",
    minHeight: 70,
    marginBottom: 18,
  },
  modalActions: {
    flexDirection: "row",
    gap: 10,
  },
  modalBtn: {
    flex: 1,
    paddingVertical: 12,
    borderRadius: 10,
    alignItems: "center",
    justifyContent: "center",
  },
  modalCancelBtn: {
    backgroundColor: "#F3F4F6",
  },
  modalApproveBtn: {
    backgroundColor: PURPLE,
  },
  modalRejectBtn: {
    backgroundColor: "#DC2626",
  },
  modalCancelText: {
    fontSize: 14,
    fontWeight: "700",
    color: "#4B5563",
  },
  modalConfirmText: {
    fontSize: 14,
    fontWeight: "700",
    color: "#FFFFFF",
  },

  // ── High-Res Photo Viewer ──
  photoViewerBackdrop: {
    flex: 1,
    backgroundColor: "#000000",
  },
  photoViewerSafe: {
    flex: 1,
  },
  photoViewerHeader: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    paddingHorizontal: 16,
    paddingVertical: 12,
    borderBottomWidth: 1,
    borderBottomColor: "#262626",
  },
  photoViewerTitle: {
    fontSize: 15,
    fontWeight: "700",
    color: "#FFFFFF",
    flex: 1,
    marginRight: 10,
  },
  photoCloseBtn: {
    padding: 4,
  },
  photoViewerBody: {
    flex: 1,
    alignItems: "center",
    justifyContent: "center",
  },
  fullScreenImg: {
    width: "100%",
    height: "100%",
  },

  // ── Edit Action and Form Styles ──
  actionMiniEdit: {
    backgroundColor: "#EDE9FE",
    borderWidth: 1,
    borderColor: "#DDD6FE",
  },
  modalEditBtnLarge: {
    backgroundColor: "#EDE9FE",
    borderWidth: 1,
    borderColor: "#DDD6FE",
  },
  modalEditTextLarge: {
    color: PURPLE,
    fontSize: 14,
    fontWeight: "700",
  },
  formSectionTitle: {
    fontSize: 12,
    fontWeight: "800",
    color: "#374151",
    letterSpacing: 0.3,
    marginTop: 12,
    marginBottom: 6,
    textTransform: "uppercase",
  },
  formRowSplit: {
    flexDirection: "row",
    gap: 12,
  },
  formFieldHalf: {
    flex: 1,
  },
  formFieldLabel: {
    fontSize: 11,
    fontWeight: "600",
    color: "#6B7280",
    marginBottom: 4,
  },
  formInput: {
    backgroundColor: "#F9FAFB",
    borderWidth: 1,
    borderColor: "#E5E7EB",
    borderRadius: 10,
    paddingHorizontal: 12,
    paddingVertical: Platform.OS === "ios" ? 10 : 8,
    fontSize: 13,
    color: "#111827",
  },
  vehicleChipScroll: {
    gap: 8,
    paddingVertical: 2,
  },
  vehicleChip: {
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 8,
    backgroundColor: "#F3F4F6",
    borderWidth: 1,
    borderColor: "#E5E7EB",
  },
  vehicleChipActive: {
    backgroundColor: "#EDE9FE",
    borderColor: PURPLE,
  },
  vehicleChipText: {
    fontSize: 12,
    fontWeight: "600",
    color: "#4B5563",
  },
  vehicleChipTextActive: {
    color: PURPLE,
    fontWeight: "800",
  },
  categoryChipWrap: {
    flexDirection: "row",
    flexWrap: "wrap",
    gap: 8,
  },
  categoryChip: {
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 8,
    backgroundColor: "#F3F4F6",
    borderWidth: 1,
    borderColor: "#E5E7EB",
  },
  categoryChipActive: {
    backgroundColor: "#EDE9FE",
    borderColor: PURPLE,
  },
  categoryChipText: {
    fontSize: 12,
    fontWeight: "600",
    color: "#4B5563",
  },
  categoryChipTextActive: {
    color: PURPLE,
    fontWeight: "800",
  },
  systemGenNotice: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#EDE9FE",
    padding: 10,
    borderRadius: 10,
    gap: 8,
    marginBottom: 8,
  },
  systemGenNoticeText: {
    fontSize: 11,
    color: "#4338CA",
    fontWeight: "600",
    flex: 1,
  },

  // ── Employee Filter Section ──
  employeeFilterSection: {
    marginHorizontal: 16,
    marginBottom: 8,
  },
  employeeFilterHeader: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    gap: 8,
    marginBottom: 6,
  },
  employeePickerBtn: {
    flex: 1,
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#FFFFFF",
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: 10,
    borderWidth: 1,
    borderColor: "#E5E7EB",
    gap: 6,
  },
  employeePickerBtnActive: {
    backgroundColor: "#EDE9FE",
    borderColor: PURPLE,
  },
  employeePickerText: {
    flex: 1,
    fontSize: 13,
    fontWeight: "600",
    color: "#374151",
  },
  employeePickerTextActive: {
    color: PURPLE,
    fontWeight: "700",
  },
  clearEmployeeBtn: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#FEE2E2",
    paddingHorizontal: 10,
    paddingVertical: 8,
    borderRadius: 10,
    gap: 4,
    borderWidth: 1,
    borderColor: "#FECACA",
  },
  clearEmployeeText: {
    fontSize: 12,
    fontWeight: "700",
    color: "#DC2626",
  },
  employeeChipsScroll: {
    gap: 6,
    flexDirection: "row",
  },
  empQuickChip: {
    flexDirection: "row",
    alignItems: "center",
    paddingHorizontal: 10,
    paddingVertical: 5,
    borderRadius: 16,
    backgroundColor: "#FFFFFF",
    borderWidth: 1,
    borderColor: "#E5E7EB",
    gap: 4,
  },
  empQuickChipActive: {
    backgroundColor: PURPLE,
    borderColor: PURPLE,
  },
  empQuickChipText: {
    fontSize: 11,
    fontWeight: "600",
    color: "#4B5563",
  },
  empQuickChipTextActive: {
    color: "#FFFFFF",
    fontWeight: "700",
  },
  employeeSearchWrap: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#F9FAFB",
    borderWidth: 1,
    borderColor: "#E5E7EB",
    borderRadius: 10,
    marginHorizontal: 20,
    marginTop: 12,
    marginBottom: 6,
    paddingHorizontal: 10,
    paddingVertical: Platform.OS === "ios" ? 8 : 4,
    gap: 6,
  },
  employeeSearchInput: {
    flex: 1,
    fontSize: 13,
    color: "#111827",
    padding: 0,
  },
  employeeOptionRow: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#FFFFFF",
    paddingVertical: 10,
    paddingHorizontal: 12,
    borderRadius: 12,
    marginBottom: 8,
    borderWidth: 1,
    borderColor: "#F3F4F6",
    gap: 12,
  },
  employeeOptionRowActive: {
    backgroundColor: "#F5F3FF",
    borderColor: "#C7D2FE",
  },
  employeeAvatar: {
    width: 38,
    height: 38,
    borderRadius: 19,
    backgroundColor: "#F3F4F6",
    alignItems: "center",
    justifyContent: "center",
  },
  employeeAvatarActive: {
    backgroundColor: PURPLE,
  },
  employeeAvatarText: {
    fontSize: 13,
    fontWeight: "800",
    color: "#4B5563",
  },
  employeeAvatarTextActive: {
    color: "#FFFFFF",
  },
  employeeOptionName: {
    fontSize: 14,
    fontWeight: "700",
    color: "#111827",
  },
  employeeOptionNameActive: {
    color: PURPLE,
    fontWeight: "800",
  },
  employeeOptionSub: {
    fontSize: 11,
    color: "#6B7280",
    marginTop: 2,
  },
  emptyEmployeeList: {
    paddingVertical: 24,
    alignItems: "center",
    justifyContent: "center",
  },
  emptyEmployeeText: {
    fontSize: 12,
    color: "#9CA3AF",
  },

  // Travel calculation highlight card
  travelCalcCard: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-around",
    backgroundColor: "#EEF2FF",
    borderWidth: 1,
    borderColor: "#C7D2FE",
    borderRadius: 12,
    paddingVertical: 10,
    paddingHorizontal: 8,
    marginTop: 10,
    marginBottom: 8,
  },
  travelCalcCol: {
    alignItems: "center",
    flex: 1,
  },
  travelCalcLabel: {
    fontSize: 10,
    fontWeight: "700",
    color: "#6B7280",
    textTransform: "uppercase",
    marginBottom: 2,
  },
  travelCalcVal: {
    fontSize: 13,
    fontWeight: "800",
    color: "#1E1B4B",
  },
  travelCalcDivider: {
    width: 1,
    height: 24,
    backgroundColor: "#CBD5E1",
  },

  // Day Expenses Claimed Section
  dayExpensesHeaderWrap: {
    flexDirection: "row",
    alignItems: "center",
    justifyContent: "space-between",
    marginTop: 16,
    marginBottom: 8,
  },
  dayExpensesTotalBadge: {
    backgroundColor: "#ECFDF5",
    borderWidth: 1,
    borderColor: "#A7F3D0",
    borderRadius: 8,
    paddingHorizontal: 8,
    paddingVertical: 3,
  },
  dayExpensesTotalText: {
    fontSize: 11,
    fontWeight: "800",
    color: "#059669",
  },
  dayExpensesListWrap: {
    gap: 8,
    marginTop: 4,
  },
  dayExpenseCard: {
    flexDirection: "row",
    alignItems: "center",
    backgroundColor: "#FFFFFF",
    borderWidth: 1,
    borderColor: "#E5E7EB",
    borderRadius: 10,
    padding: 10,
    gap: 10,
  },
  dayExpenseIconWrap: {
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: "#F3F4F6",
    alignItems: "center",
    justifyContent: "center",
  },
  dayExpenseInfo: {
    flex: 1,
  },
  dayExpenseTopRow: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
    marginBottom: 3,
  },
  dayExpenseCatName: {
    fontSize: 13,
    fontWeight: "700",
    color: "#1F2937",
    flex: 1,
    marginRight: 6,
  },
  dayExpenseAmount: {
    fontSize: 13,
    fontWeight: "800",
    color: "#059669",
  },
  dayExpenseBtmRow: {
    flexDirection: "row",
    justifyContent: "space-between",
    alignItems: "center",
  },
  dayExpenseTiming: {
    fontSize: 10,
    color: "#6B7280",
    fontWeight: "500",
  },
  dayExpenseThumbBtn: {
    width: 40,
    height: 40,
    borderRadius: 6,
    overflow: "hidden",
    borderWidth: 1,
    borderColor: "#E5E7EB",
  },
  dayExpenseThumbImg: {
    width: "100%",
    height: "100%",
  },
  dayExpensesEmptyBox: {
    backgroundColor: "#F9FAFB",
    borderWidth: 1,
    borderColor: "#E5E7EB",
    borderStyle: "dashed",
    borderRadius: 10,
    paddingVertical: 14,
    alignItems: "center",
    justifyContent: "center",
    marginTop: 4,
  },
  dayExpensesEmptyText: {
    fontSize: 12,
    color: "#9CA3AF",
    fontStyle: "italic",
  },

  // System Travel Card inside Expense Modal
  systemTravelCard: {
    backgroundColor: "#F9FAFB",
    borderWidth: 1,
    borderColor: "#E5E7EB",
    borderRadius: 12,
    padding: 12,
    marginTop: 12,
  },
  systemTravelHeader: {
    flexDirection: "row",
    alignItems: "center",
    gap: 6,
    marginBottom: 8,
    borderBottomWidth: 1,
    borderBottomColor: "#E5E7EB",
    paddingBottom: 6,
  },
  systemTravelTitle: {
    fontSize: 13,
    fontWeight: "800",
    color: "#1F2937",
  },
  systemTravelInfoGrid: {
    flexDirection: "row",
    flexWrap: "wrap",
    gap: 8,
    marginBottom: 6,
  },
  systemTravelInfoItem: {
    width: "48%",
    backgroundColor: "#FFFFFF",
    borderRadius: 8,
    padding: 8,
    borderWidth: 1,
    borderColor: "#F3F4F6",
  },
  systemTravelInfoLabel: {
    fontSize: 10,
    fontWeight: "700",
    color: "#6B7280",
    textTransform: "uppercase",
    marginBottom: 2,
  },
  systemTravelInfoVal: {
    fontSize: 12,
    fontWeight: "700",
    color: "#111827",
  },
});
