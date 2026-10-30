import { requestJson } from "./http";

export interface PartyProfile {
  id: number;
  party_code: string;
  name: string;
  owner_name: string;
  mobile: string;
  email: string;
  address: string;
  district_id?: number | null;
  area_id?: number | null;
  city: string;
  state: string;
  pincode: string;
  party_type: string;
  category: string;
  credit_limit: number;
  credit_days: number;
  opening_balance: number;
  is_active: boolean;
  approval_status?: "pending" | "approved" | "rejected";
}

export interface NewPartyPayload {
  name: string;
  mobile: string;
  party_type?: string;
  owner_name?: string;
  district_id?: number;
  district?: string;
  area_id?: number;
  area?: string;
  new_area?: string;
  address?: string;
  city?: string;
  state?: string;
  pincode?: string;
  email?: string;
  gst_number?: string;
}

export const getPartyDetails = async (id: number): Promise<{ success: boolean; data: PartyProfile }> => {
  return await requestJson<{ success: boolean; data: PartyProfile }>(`/master/parties/${id}`);
};

export const submitParty = async (payload: NewPartyPayload): Promise<{ success: boolean; message: string; data?: any }> => {
  return await requestJson<{ success: boolean; message: string; data?: any }>("/parties", {
    method: "POST",
    body: payload,
  });
};

