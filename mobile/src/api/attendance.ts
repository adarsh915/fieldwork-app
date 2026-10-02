import { requestJson } from "./http";

// ─── Types ────────────────────────────────────────────────────────────────────

export type AttendanceStatus =
  | "present"
  | "on_leave"
  | "holiday"
  | "weekly_off"
  | "absent"
  | "not_yet";

export interface DayAttendance {
  status: AttendanceStatus;
  label: string;
}

export interface AttendanceSummary {
  present: number;
  on_leave: number;
  holiday: number;
  weekly_off: number;
  absent: number;
}

export interface MonthlyCalendarResponse {
  success: boolean;
  calendar: Record<string, DayAttendance>;
  summary: AttendanceSummary;
}

export interface Holiday {
  id: number;
  date: string;
  name: string;
  type: "national" | "company";
}

// ─── API Calls ────────────────────────────────────────────────────────────────

export const getMyAttendanceCalendar = (
  year: number,
  month: number
): Promise<MonthlyCalendarResponse> =>
  requestJson(`/attendance/calendar?year=${year}&month=${month}`);

export const getHolidays = (
  year: number
): Promise<{ success: boolean; data: Holiday[] }> =>
  requestJson(`/holidays?year=${year}`);
