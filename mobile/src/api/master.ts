/**
 * Master Data API
 * Loads dropdown/picker data from the backend.
 * GET /api/master/*
 */
import { requestJson } from "./http";

export type MasterItem = { id: number; name: string };
export type TourPurpose = MasterItem & { requires_party: boolean };
export type VehicleType = MasterItem & { requires_vehicle_no: boolean };
export type Party = {
  id: number;
  name: string;
  mobile: string | null;
  address: string | null;
};

type ListResponse<T> = { success: boolean; data: T[] };

export const getMasterTourPurposes = () =>
  requestJson<ListResponse<TourPurpose>>("/master/tour-purposes");
export const getMasterTourTypes = () =>
  requestJson<ListResponse<MasterItem>>("/master/tour-types");
export const getMasterVehicleTypes = () =>
  requestJson<ListResponse<VehicleType>>("/master/vehicle-types");
export const getMasterParties = () =>
  requestJson<ListResponse<Party>>("/master/parties");
