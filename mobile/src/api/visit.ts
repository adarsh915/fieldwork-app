import { requestJson } from "./http";

export type Visit = {
  id: number;
  user_id: number;
  day_start_id: number;
  party_id: number;
  check_in_at: string;
  check_in_lat: string | null;
  check_in_lng: string | null;
  check_out_at: string | null;
  check_out_lat: string | null;
  check_out_lng: string | null;
  remarks: string | null;
  status: "in_progress" | "completed";
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
}> {
  return requestJson(`/visits/party/${partyId}/status`, { method: "GET" });
}

export async function checkIn(payload: {
  party_id: number | string;
  lat?: number;
  lng?: number;
}): Promise<{ success: boolean; data: Visit; message: string }> {
  return requestJson("/visits/check-in", { method: "POST", body: payload });
}

export async function checkOut(
  visitId: number | string,
  payload: { lat?: number; lng?: number; remarks?: string },
): Promise<{ success: boolean; data: Visit; message: string }> {
  return requestJson(`/visits/${visitId}/check-out`, {
    method: "PATCH",
    body: payload,
  });
}
