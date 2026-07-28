import type { Customer } from "./types";
import { requestJson } from "../../api/http";

// Placeholder endpoints (define these in Laravel when you implement Customers).
export async function listCustomers(): Promise<Customer[]> {
  return requestJson<Customer[]>("/v1/customers");
}
