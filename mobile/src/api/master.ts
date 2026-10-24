/**
 * Master Data API
 * Loads dropdown/picker data from the backend.
 * GET /api/master/*
 */
import { requestJson } from "./http";

export type MasterItem = { id: number; name: string };
export type TourPurpose = MasterItem & { requires_party: boolean };
export type VehicleType = MasterItem & { requires_vehicle_no: boolean };

export type Area = {
  id: number;
  district_id: number;
  name: string;
  pincode?: string | null;
  is_active?: boolean;
};

export type District = {
  id: number;
  name: string;
  state?: string | null;
  code?: string | null;
  areas?: Area[];
};

export type Party = {
  id: number;
  name: string;
  mobile: string | null;
  address: string | null;
  city?: string | null;
  district_id?: number | null;
  area_id?: number | null;
  approval_status?: "pending" | "approved" | "rejected";
};

export type ListResponse<T> = { success: boolean; data: T[] };

export type PaginatedPartiesResponse = {
  success: boolean;
  data: Party[];
  current_page: number;
  last_page: number;
  total: number;
  has_more: boolean;
};

export const getMasterTourPurposes = () =>
  requestJson<ListResponse<TourPurpose>>("/master/tour-purposes");
export const getMasterTourTypes = () =>
  requestJson<ListResponse<MasterItem>>("/master/tour-types");
export const getMasterVehicleTypes = () =>
  requestJson<ListResponse<VehicleType>>("/master/vehicle-types");
export const getMasterLocations = () =>
  requestJson<ListResponse<District>>("/master/locations");
export const getMasterParties = (params?: { district_id?: number; area_id?: number }) => {
  const query = new URLSearchParams();
  if (params?.district_id) query.append("district_id", String(params.district_id));
  if (params?.area_id) query.append("area_id", String(params.area_id));
  const qs = query.toString();
  return requestJson<ListResponse<Party>>(`/parties${qs ? `?${qs}` : ""}`);
};
export const getMasterPartiesPaginated = (params?: {
  page?: number;
  per_page?: number;
  search?: string;
  district_id?: number;
  area_id?: number;
}) => {
  const query = new URLSearchParams();
  if (params?.page) query.append("page", String(params.page));
  if (params?.per_page) query.append("per_page", String(params.per_page));
  if (params?.search && params.search.trim()) query.append("search", params.search.trim());
  if (params?.district_id) query.append("district_id", String(params.district_id));
  if (params?.area_id) query.append("area_id", String(params.area_id));

  const qs = query.toString();
  return requestJson<PaginatedPartiesResponse>(`/parties${qs ? `?${qs}` : ""}`);
};
