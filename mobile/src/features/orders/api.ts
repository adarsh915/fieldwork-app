import type { Order } from "./types";
import { requestJson } from "../../api/http";

// Placeholder endpoints (define these in Laravel when you implement Orders).
export async function listOrders(): Promise<Order[]> {
  return requestJson<Order[]>("/v1/orders");
}
