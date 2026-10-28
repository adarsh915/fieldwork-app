import { requestJson } from "./http";

export interface ApprovalCounts {
  can_approve: boolean;
  can_approve_day: boolean;
  can_temp_approve_expense: boolean;
  can_final_approve_expense: boolean;
  pending_day_starts: number;
  pending_expenses_l1: number;
  pending_expenses_l2: number;
  total_pending: number;
}

export interface LinkedDayExpense {
  id: number;
  amount: number;
  amount_formatted: string;
  category_name: string;
  is_system_generated: boolean;
  status: "pending" | "temp_approved" | "approved" | "rejected" | "manager_rejected";
  bill_photo_url: string | null;
  is_after_day_end: boolean;
  items_count: number;
}

export interface DayStartApprovalItem {
  id: number;
  user_id: number;
  employee_name: string;
  employee_role: string;
  employee_id: string;
  date: string | null;
  date_formatted: string;
  start_time: string | null;
  end_time: string | null;
  tour_purpose: string;
  tour_type: string | null;
  vehicle_type: string | null;
  vehicle_no: string | null;
  visit_place: string | null;
  district: string | null;
  area: string | null;
  party_name: string | null;
  opening_km: number | null;
  opening_km_photo_url: string | null;
  closing_km: number | null;
  closing_km_photo_url: string | null;
  distance_km?: number | null;
  applied_rate_per_km?: number | null;
  travel_allowance_amount?: number | null;
  day_expenses?: LinkedDayExpense[];
  total_day_expenses_amount?: number;
  status: "pending" | "submitted" | "approved" | "rejected";
  approved_by_name: string | null;
  remarks: string | null;
  end_remarks?: string | null;
  can_action: boolean;
  can_edit: boolean;
  created_at: string;
}

export interface ExpenseItemBreakdown {
  item_name: string;
  quantity: number;
  unit_price: number;
  total_price: number;
}

export interface ExpenseApprovalItem {
  id: number;
  user_id: number;
  employee_name: string;
  employee_role: string;
  employee_id: string;
  amount: number;
  amount_formatted: string;
  category_name: string;
  is_system_generated: boolean;
  day_start_id?: number | null;
  opening_km?: number | null;
  opening_km_photo_url?: string | null;
  closing_km?: number | null;
  closing_km_photo_url?: string | null;
  distance_km?: number | null;
  applied_rate_per_km?: number | null;
  vehicle_type?: string | null;
  vehicle_no?: string | null;
  tour_purpose?: string | null;
  visit_place?: string | null;
  party_name?: string | null;
  date: string | null;
  date_formatted: string;
  bill_photo_url: string | null;
  is_after_day_end: boolean;
  status: "pending" | "temp_approved" | "approved" | "rejected" | "manager_rejected";
  temp_approved_by: string | null;
  temp_approved_at: string | null;
  temp_approved_note: string | null;
  approved_by: string | null;
  approved_at: string | null;
  final_note: string | null;
  rejected_by: string | null;
  rejected_at: string | null;
  rejection_reason: string | null;
  items: ExpenseItemBreakdown[];
  can_temp_approve: boolean;
  can_manager_reject: boolean;
  can_final_approve: boolean;
  can_final_reject: boolean;
  can_edit: boolean;
  created_at: string;
}

export interface PaginatedResponse<T> {
  success: boolean;
  data: T[];
  current_page: number;
  last_page: number;
  total: number;
  has_more: boolean;
  message?: string;
}

export interface TeamMember {
  id: number;
  name: string;
  employee_id: string | null;
  department: string | null;
  position: string | null;
}

/**
 * Fetch counts of pending approvals visible to current user
 */
export async function getApprovalCounts(): Promise<{
  success: boolean;
  data: ApprovalCounts;
}> {
  return requestJson<{ success: boolean; data: ApprovalCounts }>("/admin/approvals/counts");
}

/**
 * Fetch scoped team subordinates / visible members for employee filter
 */
export async function getApprovalTeamMembers(params?: {
  module?: "day-start" | "expenses";
  search?: string;
}): Promise<{
  success: boolean;
  data: TeamMember[];
}> {
  const query = new URLSearchParams();
  if (params?.module) query.append("module", params.module);
  if (params?.search) query.append("search", params.search);
  const qs = query.toString();
  return requestJson<{ success: boolean; data: TeamMember[] }>(
    `/admin/approvals/team${qs ? `?${qs}` : ""}`
  );
}

/**
 * Fetch paginated list of Day Start / Tour Plan requests
 */
export async function getDayStartApprovals(params?: {
  page?: number;
  per_page?: number;
  status?: "pending" | "approved" | "rejected" | "all";
  user_id?: number | string;
  search?: string;
}): Promise<PaginatedResponse<DayStartApprovalItem>> {
  const query = new URLSearchParams();
  if (params?.page) query.append("page", String(params.page));
  if (params?.per_page) query.append("per_page", String(params.per_page));
  if (params?.status) query.append("status", params.status);
  if (params?.user_id && params.user_id !== "all") query.append("user_id", String(params.user_id));
  if (params?.search) query.append("search", params.search);

  const qs = query.toString();
  return requestJson<PaginatedResponse<DayStartApprovalItem>>(
    `/admin/approvals/day-starts${qs ? `?${qs}` : ""}`
  );
}

/**
 * Approve or Reject a Day Start
 */
export async function actOnDayStart(
  id: number,
  payload: {
    status: "approved" | "rejected";
    reason?: string;
  }
): Promise<{ success: boolean; status: string; message: string }> {
  return requestJson<{ success: boolean; status: string; message: string }>(
    `/admin/approvals/day-starts/${id}/action`,
    {
      method: "POST",
      body: payload,
    }
  );
}

/**
 * Fetch paginated list of Expense claims
 */
export async function getExpenseApprovals(params?: {
  page?: number;
  per_page?: number;
  status?: "pending" | "temp_approved" | "approved" | "rejected" | "all";
  user_id?: number | string;
  search?: string;
}): Promise<PaginatedResponse<ExpenseApprovalItem>> {
  const query = new URLSearchParams();
  if (params?.page) query.append("page", String(params.page));
  if (params?.per_page) query.append("per_page", String(params.per_page));
  if (params?.status) query.append("status", params.status);
  if (params?.user_id && params.user_id !== "all") query.append("user_id", String(params.user_id));
  if (params?.search) query.append("search", params.search);

  const qs = query.toString();
  return requestJson<PaginatedResponse<ExpenseApprovalItem>>(
    `/admin/approvals/expenses${qs ? `?${qs}` : ""}`
  );
}

/**
 * Level 1 / Level 2 approve or reject an Expense claim
 */
export async function actOnExpense(
  id: number,
  payload: {
    action: "temp_approve" | "final_approve" | "manager_reject" | "reject";
    note?: string;
  }
): Promise<{ success: boolean; status: string; message: string }> {
  return requestJson<{ success: boolean; status: string; message: string }>(
    `/admin/approvals/expenses/${id}/action`,
    {
      method: "POST",
      body: payload,
    }
  );
}

export interface UpdateDayStartPayload {
  opening_km?: number | null;
  closing_km?: number | null;
  vehicle_type?: string | null;
  vehicle_no?: string | null;
  visit_place?: string | null;
  tour_purpose?: string | null;
  tour_type?: string | null;
  remarks?: string | null;
  reason?: string | null;
}

/**
 * Edit / adjust a subordinate's tour plan
 */
export async function updateDayStartApproval(
  id: number,
  payload: UpdateDayStartPayload
): Promise<{ success: boolean; message: string; data?: any }> {
  return requestJson<{ success: boolean; message: string; data?: any }>(
    `/admin/approvals/day-starts/${id}`,
    {
      method: "PATCH",
      body: payload,
    }
  );
}

export interface UpdateExpensePayload {
  amount: number;
  category_id?: number | null;
  date?: string | null;
  reason?: string | null;
  items?: Array<{
    item_name: string;
    quantity?: number;
    unit_price?: number;
    total_price?: number;
  }>;
}

/**
 * Edit / adjust a subordinate's expense claim
 */
export async function updateExpenseApproval(
  id: number,
  payload: UpdateExpensePayload
): Promise<{ success: boolean; message: string; data?: any }> {
  return requestJson<{ success: boolean; message: string; data?: any }>(
    `/admin/approvals/expenses/${id}`,
    {
      method: "PATCH",
      body: payload,
    }
  );
}
