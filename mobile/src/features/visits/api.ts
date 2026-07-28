import type { Visit } from "./types";
import { requestJson } from "../../api/http";

// Placeholder endpoints (define these in Laravel when you implement Visits).
export async function listVisits(): Promise<Visit[]> {
  return requestJson<Visit[]>("/v1/visits");
}
