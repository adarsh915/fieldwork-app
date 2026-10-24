import { requestJson } from "./http";

// ─── Types ────────────────────────────────────────────────────────────────────

export interface LeaveType {
  id: number;
  name: string;
  code: string;
  days_per_year: number;
  accrual_frequency?: "annual" | "monthly";
  days_per_month?: number;
}

export interface Leave {
  id: number;
  leave_type_id: number;
  leave_type?: LeaveType;
  from_date: string;
  to_date: string;
  duration: "full" | "half_am" | "half_pm";
  reason?: string | null;
  status: "pending" | "approved" | "rejected";
  approved_at?: string | null;
  rejected_reason?: string | null;
  days_count: number;
  created_at: string;
}

export interface LeaveBalance {
  leave_type: LeaveType;
  allocated: number;
  used: number;
  available: number;
  carried_forward: number;
  pending_days?: number;
  effective_available?: number;
}

export interface ApplyLeavePayload {
  leave_type_id: number;
  from_date: string; // YYYY-MM-DD
  to_date: string;   // YYYY-MM-DD
  duration?: "full" | "half_am" | "half_pm";
  reason?: string;
}

// ─── API Calls ────────────────────────────────────────────────────────────────

export const getLeaveTypes = (): Promise<{ success: boolean; data: LeaveType[] }> =>
  requestJson("/leave-types");

export interface GetLeavesParams {
  page?: number;
  per_page?: number;
  status?: string;
  search?: string;
  leave_type_id?: number | string;
}

export interface GetLeavesResponse {
  success: boolean;
  data: Leave[];
  current_page?: number;
  last_page?: number;
  per_page?: number;
  total?: number;
  has_more?: boolean;
  counts?: {
    all: number;
    pending: number;
    approved: number;
    rejected: number;
    cancelled: number;
  };
}

export const getMyLeaves = (
  params?: GetLeavesParams
): Promise<GetLeavesResponse> => {
  const queryParams = new URLSearchParams();
  if (params?.page !== undefined) queryParams.append("page", String(params.page));
  if (params?.per_page !== undefined) queryParams.append("per_page", String(params.per_page));
  if (params?.status) queryParams.append("status", params.status);
  if (params?.search) queryParams.append("search", params.search);
  if (params?.leave_type_id !== undefined) queryParams.append("leave_type_id", String(params.leave_type_id));

  const qs = queryParams.toString();
  const url = qs ? `/leaves?${qs}` : "/leaves";
  return requestJson<GetLeavesResponse>(url);
};

export const getLeaveBalance = (): Promise<{ success: boolean; data: LeaveBalance[] }> =>
  requestJson("/leaves/balance");

export const applyLeave = (
  payload: ApplyLeavePayload
): Promise<{ success: boolean; message: string; data: Leave }> =>
  requestJson("/leaves/apply", { method: "POST", body: payload });

export const cancelLeave = (
  id: number
): Promise<{ success: boolean; message: string }> =>
  requestJson(`/leaves/${id}/cancel`, { method: "DELETE" });

export const requestLeaveCancellation = (
  id: number
): Promise<{ success: boolean; message: string }> =>
  requestJson(`/leaves/${id}/request-cancellation`, { method: "POST" });
