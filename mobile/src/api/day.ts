/**
 * Day API
 * Manages the day lifecycle: start, status check, end.
 * POST/GET /api/day/*
 */
import { API_BASE_URL, API_PREFIX } from "./config";
import { requestJson } from "./http";
import { getAuthToken } from "../auth/tokenStore";

export type DayStartData = {
  id: number;
  date: string;
  status: "submitted" | "approved" | "flagged";
  opening_km: number | null;
  opening_km_image: string | null;
  vehicle_no: string | null;
  tour_purpose: { id: number; name: string } | null;
  tour_type: { id: number; name: string } | null;
  vehicle_type: { id: number; name: string } | null;
  district?: { id: number; name: string } | null;
  area?: { id: number; name: string } | null;
  area_name_custom?: string | null;
  visit_place?: string | null;
  remarks?: string | null;
  start_time?: string | null;
  end_time: string | null;
};

type DayStatusResponse = {
  success: boolean;
  day_started: boolean;
  data: DayStartData | null;
};

type DayStartResponse = {
  success: boolean;
  message: string;
  data: DayStartData;
};

/** GET /api/day/status — check if today's day is started */
export async function getDayStatus(): Promise<DayStatusResponse> {
  return requestJson<DayStatusResponse>("/day/status");
}

/**
 * POST /api/day/start — submit Day Start form.
 * Uses FormData to support image upload.
 */
export async function submitDayStart(payload: {
  tour_purpose_id: number;
  tour_type_id: number;
  vehicle_type_id: number;
  vehicle_no?: string;
  opening_km?: string;
  opening_km_image_uri?: string | null; // local file URI from camera
  district_id?: number;
  area_id?: number;
  area_ids?: number[];
  area_name_custom?: string;
  party_ids?: number[];
  visit_place?: string;
  remarks?: string;
}): Promise<DayStartResponse> {
  const token = await getAuthToken();
  const url = `${API_BASE_URL}${API_PREFIX}/day/start`;

  const form = new FormData();
  form.append("tour_purpose_id", String(payload.tour_purpose_id));
  form.append("tour_type_id", String(payload.tour_type_id));
  form.append("vehicle_type_id", String(payload.vehicle_type_id));

  if (payload.district_id) form.append("district_id", String(payload.district_id));
  if (payload.area_id) form.append("area_id", String(payload.area_id));
  if (payload.area_ids?.length) {
    payload.area_ids.forEach((id) => form.append("area_ids[]", String(id)));
  }
  if (payload.area_name_custom) form.append("area_name_custom", payload.area_name_custom);
  if (payload.vehicle_no) form.append("vehicle_no", payload.vehicle_no);
  if (payload.opening_km) form.append("opening_km", payload.opening_km);
  if (payload.visit_place) form.append("visit_place", payload.visit_place);
  if (payload.remarks) form.append("remarks", payload.remarks);

  if (payload.party_ids?.length) {
    payload.party_ids.forEach((id) => form.append("party_ids[]", String(id)));
  }

  // Attach photo if selected
  if (payload.opening_km_image_uri) {
    const filename =
      payload.opening_km_image_uri.split("/").pop() ?? "km_photo.jpg";
    const ext = filename.split(".").pop()?.toLowerCase() ?? "jpg";
    const mime = ext === "png" ? "image/png" : "image/jpeg";
    form.append("opening_km_photo", {
      uri: payload.opening_km_image_uri,
      name: filename,
      type: mime,
    } as any);
  }

  let res: Response;
  try {
    res = await fetch(url, {
      method: "POST",
      headers: {
        Accept: "application/json",
        Authorization: `Bearer ${token ?? ""}`,
        // Do NOT set Content-Type — let fetch set it for FormData
      },
      body: form,
    });
  } catch (netErr: any) {
    const error: any = new Error("Network error: Unable to connect to server. Please check your internet connection.");
    error.status = 0;
    throw error;
  }

  const data = await res.json().catch(() => null);

  if (!res.ok) {
    let msg = data?.message;
    if (data?.errors && typeof data.errors === "object") {
      const keys = Object.keys(data.errors);
      if (keys.length > 0) {
        const val = data.errors[keys[0]];
        if (Array.isArray(val) && val.length > 0) msg = val[0];
        else if (typeof val === "string") msg = val;
      }
    }
    const error: any = new Error(msg ?? `Request failed: ${res.status}`);
    error.status = res.status;
    error.errors = data?.errors;
    throw error;
  }

  return data as DayStartResponse;
}

/**
 * POST /api/day/end
 * Uses FormData to support image upload.
 */
export async function submitDayEnd(payload: {
  closing_km?: string;
  closing_km_image_uri?: string | null;
  remarks?: string;
}): Promise<{ success: boolean; message: string }> {
  const token = await getAuthToken();
  const url = `${API_BASE_URL}${API_PREFIX}/day/end`;

  const form = new FormData();
  if (payload.closing_km) form.append("closing_km", payload.closing_km);
  if (payload.remarks) form.append("remarks", payload.remarks);

  // Attach photo if selected
  if (payload.closing_km_image_uri) {
    const filename =
      payload.closing_km_image_uri.split("/").pop() ?? "km_closing_photo.jpg";
    const ext = filename.split(".").pop()?.toLowerCase() ?? "jpg";
    const mime = ext === "png" ? "image/png" : "image/jpeg";
    form.append("closing_km_photo", {
      uri: payload.closing_km_image_uri,
      name: filename,
      type: mime,
    } as any);
  }

  let res: Response;
  try {
    res = await fetch(url, {
      method: "POST",
      headers: {
        Accept: "application/json",
        Authorization: `Bearer ${token ?? ""}`,
      },
      body: form,
    });
  } catch (netErr: any) {
    const error: any = new Error("Network error: Unable to connect to server. Please check your internet connection.");
    error.status = 0;
    throw error;
  }

  const data = await res.json().catch(() => null);

  if (!res.ok) {
    let msg = data?.message;
    if (data?.errors && typeof data.errors === "object") {
      const keys = Object.keys(data.errors);
      if (keys.length > 0) {
        const val = data.errors[keys[0]];
        if (Array.isArray(val) && val.length > 0) msg = val[0];
        else if (typeof val === "string") msg = val;
      }
    }
    const error: any = new Error(msg ?? `Request failed: ${res.status}`);
    error.status = res.status;
    error.errors = data?.errors;
    throw error;
  }

  return data;
}
