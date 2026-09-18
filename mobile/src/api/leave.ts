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

export const getMyLeaves = (): Promise<{ success: boolean; data: Leave[] }> =>
  requestJson("/leaves");

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
