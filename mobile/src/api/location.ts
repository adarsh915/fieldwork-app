import { requestJson } from "./http";

export interface LocationPoint {
  latitude: number;
  longitude: number;
  recorded_at: string; // ISO date string
  battery_level?: number | null;
  accuracy?: number | null;
}

export interface BulkLocationUploadRequest {
  locations: LocationPoint[];
}

export interface BulkLocationUploadResponse {
  success: boolean;
  message: string;
  inserted: number;
  skipped: number;
  total: number;
}

export interface SingleLocationUploadResponse {
  success: boolean;
  message: string;
  location: {
    id: number;
    user_id: number;
    latitude: number;
    longitude: number;
    recorded_at: string;
    battery_level: number | null;
    accuracy: number | null;
    created_at: string;
    updated_at: string;
  };
}

/**
 * Upload multiple location points in batch (up to 500)
 */
export async function uploadBulkLocations(
  locations: LocationPoint[]
): Promise<BulkLocationUploadResponse> {
  return requestJson<BulkLocationUploadResponse>("/location/bulk-update", {
    method: "POST",
    body: { locations },
  });
}

/**
 * Upload a single location point immediately
 */
export async function uploadSingleLocation(
  location: LocationPoint
): Promise<SingleLocationUploadResponse> {
  return requestJson<SingleLocationUploadResponse>("/location/single", {
    method: "POST",
    body: location,
  });
}

/**
 * Get my latest location
 */
export async function getMyLatestLocation(): Promise<any> {
  return requestJson("/location/my-latest", { method: "GET" });
}

/**
 * Get location tracking settings from admin
 */
export async function getTrackingSettings(): Promise<any> {
  return requestJson("/location/tracking-settings", { method: "GET" });
}
