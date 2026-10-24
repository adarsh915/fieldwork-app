import { requestJson } from "./http";
import { API_BASE_URL, API_PREFIX } from "./config";
import { getAuthToken } from "../auth/tokenStore";

export type Visit = {
  id: number;
  user_id: number;
  day_start_id: number;
  party_id: number;
  check_in_at?: string;
  check_in_time?: string;
  check_in_lat: string | null;
  check_in_lng: string | null;
  check_out_at?: string | null;
  check_out_time?: string | null;
  check_out_lat: string | null;
  check_out_lng: string | null;
  remarks: string | null;
  notes?: string | null;
  purpose?: string | null;
  status: "pending" | "completed" | "in_progress";
  user?: { id: number; name: string };
};

export async function getVisits(): Promise<{
  success: boolean;
  data: Visit[];
}> {
  return requestJson("/visits", { method: "GET" });
}

export async function getPartyVisitStatus(partyId: string | number): Promise<{
  success: boolean;
  active_visit: Visit | null;
  has_other_active_visit: boolean;
  recent_visits?: Visit[];
  total_consultations?: number;
}> {
  return requestJson(`/visits/party/${partyId}/status`, { method: "GET" });
}

export async function getPartyVisitHistory(
  partyId: string | number,
  params?: { page?: number; per_page?: number; search?: string }
): Promise<{
  success: boolean;
  data: Visit[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  has_more: boolean;
}> {
  const queryParams = new URLSearchParams();
  if (params?.page) queryParams.append("page", String(params.page));
  if (params?.per_page) queryParams.append("per_page", String(params.per_page));
  if (params?.search) queryParams.append("search", params.search);

  const qs = queryParams.toString();
  const url = qs ? `/visits/party/${partyId}/history?${qs}` : `/visits/party/${partyId}/history`;
  return requestJson(url, { method: "GET" });
}

export async function checkIn(payload: {
  party_id: number | string;
  lat?: number;
  lng?: number;
  check_in_photo_uri?: string;
  purpose?: string;
  notes?: string;
}): Promise<{ success: boolean; data: Visit; message: string }> {
  const token = await getAuthToken();
  const url = `${API_BASE_URL}${API_PREFIX}/visits/check-in`;

  const form = new FormData();
  form.append("party_id", String(payload.party_id));
  if (payload.lat !== undefined) form.append("lat", String(payload.lat));
  if (payload.lng !== undefined) form.append("lng", String(payload.lng));
  if (payload.purpose) form.append("purpose", payload.purpose);
  if (payload.notes) form.append("notes", payload.notes);

  if (payload.check_in_photo_uri) {
    const filename = payload.check_in_photo_uri.split("/").pop() ?? "party_meet.jpg";
    const ext = filename.split(".").pop()?.toLowerCase() ?? "jpg";
    const mime = ext === "png" ? "image/png" : "image/jpeg";
    form.append("check_in_photo", {
      uri: payload.check_in_photo_uri,
      name: filename,
      type: mime,
    } as any);
  }

  const res = await fetch(url, {
    method: "POST",
    headers: {
      Accept: "application/json",
      Authorization: `Bearer ${token ?? ""}`,
    },
    body: form,
  });

  const data = await res.json().catch(() => null);

  if (!res.ok) {
    const error: any = new Error(data?.message ?? `Request failed: ${res.status}`);
    error.status = res.status;
    error.errors = data?.errors;
    throw error;
  }

  return data as { success: boolean; data: Visit; message: string };
}

export async function checkOut(
  visitId: number | string,
  payload: { lat?: number; lng?: number; remarks?: string; notes?: string },
): Promise<{ success: boolean; data: Visit; message: string }> {
  return requestJson(`/visits/${visitId}/check-out`, {
    method: "PATCH",
    body: {
      ...payload,
      notes: payload.notes ?? payload.remarks,
    },
  });
}

export async function updateVisitNotes(
  visitId: number | string,
  notes: string,
): Promise<{ success: boolean; data: Visit; message: string }> {
  return requestJson(`/visits/${visitId}/notes`, {
    method: "PATCH",
    body: { notes, remarks: notes },
  });
}
