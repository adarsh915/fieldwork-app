import { requestJson } from "./http";

export type ReportPeriod = "today" | "week" | "month" | "year" | "custom";
export type ScopeType = "own" | "team" | "all";

export interface TeamMember {
  id: number;
  name: string;
  employee_id?: string;
  department?: string;
  position?: string;
}

export interface ReportKPIs {
  visits_count: number;
  completed_visits_count: number;
  orders_count: number;
  orders_value: number;
  payments_count: number;
  payments_total: number;
  confirmed_payments_total: number;
  expenses_count?: number;
  expenses_total?: number;
  approved_expenses_total?: number;
  pending_expenses_total?: number;
  km_traveled: number;
  working_days: number;
  active_fieldworkers: number;
  total_team_count: number;
}

export interface TeamMemberBreakdown {
  user_id: number;
  name: string;
  employee_id: string;
  position: string;
  day_status: "Working" | "Completed" | "Not Started";
  visits_count: number;
  orders_count: number;
  orders_value: number;
  payments_total: number;
  expenses_total?: number;
  expenses_count?: number;
}

export interface RecentActivityItem {
  type: "visit" | "order" | "payment";
  id: number;
  title: string;
  subtitle: string;
  time: string;
  date: string;
  status: string;
  is_success: boolean;
  notes?: string | null;
  user_name?: string | null;
  party_name?: string | null;
  party_city?: string | null;
  purpose?: string | null;
  check_out_time?: string | null;
  check_in_photo?: string | null;
}

export interface ReportSummaryResponse {
  scope: ScopeType;
  is_multi_user: boolean;
  period: ReportPeriod;
  period_label: string;
  start_date: string;
  end_date: string;
  team_members: TeamMember[];
  kpis: ReportKPIs;
  breakdown: (TeamMemberBreakdown | RecentActivityItem)[];
  activities?: RecentActivityItem[];
}

/**
 * Fetch report summary from backend with DataScoping and period filter
 */
export async function fetchReportSummary(params: {
  period?: ReportPeriod;
  user_id?: number | "all";
  start_date?: string;
  end_date?: string;
}): Promise<ReportSummaryResponse> {
  const queryParams = new URLSearchParams();
  if (params.period) queryParams.append("period", params.period);
  if (params.user_id !== undefined) queryParams.append("user_id", String(params.user_id));
  if (params.start_date) queryParams.append("start_date", params.start_date);
  if (params.end_date) queryParams.append("end_date", params.end_date);

  const res = await requestJson<{ success: boolean; data: ReportSummaryResponse }>(
    `/reports/mobile-summary?${queryParams.toString()}`,
    { method: "GET" }
  );

  return res.data;
}
