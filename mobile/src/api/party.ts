import { requestJson } from "./http";

export interface PartyProfile {
  id: number;
  party_code: string;
  name: string;
  owner_name: string;
  mobile: string;
  email: string;
  address: string;
  city: string;
  state: string;
  pincode: string;
  party_type: string;
  category: string;
  credit_limit: number;
  credit_days: number;
  opening_balance: number;
  is_active: boolean;
}

export const getPartyDetails = async (id: number): Promise<{ success: boolean; data: PartyProfile }> => {
  return await requestJson<{ success: boolean; data: PartyProfile }>(`/master/parties/${id}`);
};
