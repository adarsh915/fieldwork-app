import { requestJson } from "./http";
import { API_BASE_URL, API_PREFIX } from "./config";
import AsyncStorage from "@react-native-async-storage/async-storage";
import { getAuthToken } from "../auth/tokenStore";

export interface PaymentPayload {
  party_id: number;
  amount: number;
  payment_mode: "cash" | "upi" | "bank_transfer" | "cheque";
  reference_number?: string;
  proof_photo_uri?: string;
  visit_id?: number;
  collection_latitude?: number;
  collection_longitude?: number;
  allocated_invoice_id?: number;
  notes?: string;
  idempotency_key?: string;
}

export interface PaymentRecord {
  id: number;
  receipt_no: string;
  party_id: number;
  collected_by: number;
  visit_id?: number | null;
  amount: number | string;
  payment_mode: "cash" | "upi" | "bank_transfer" | "cheque";
  reference_number?: string | null;
  proof_photo_url?: string | null;
  collection_latitude?: number | null;
  collection_longitude?: number | null;
  collection_datetime: string;
  status: "pending" | "confirmed" | "rejected";
  confirmed_by?: number | null;
  confirmed_at?: string | null;
  rejection_reason?: string | null;
  notes?: string | null;
  created_at: string;
  party?: {
    id: number;
    name: string;
    mobile?: string;
    address?: string;
  };
  confirmed_by_user?: {
    id: number;
    name: string;
  };
}

export interface DealerPaymentSummary {
  dealer: {
    id: number;
    name: string;
    mobile?: string;
    address?: string;
    credit_limit: number;
    opening_balance: number;
    outstanding_balance: number;
    pending_amount: number;
  };
  data: PaymentRecord[];
}

export interface PaymentSettings {
  company_upi_id: string;
  company_name: string;
  company_bank_details: {
    bank_name: string;
    account_no: string;
    ifsc_code: string;
    branch: string;
  };
}

/**
 * Submit a payment collection record (supports multipart for photo upload)
 */
export async function submitPayment(payload: PaymentPayload): Promise<any> {
  const token = await getAuthToken();
  const url = `${API_BASE_URL}${API_PREFIX}/payments/collect`;

  const formData = new FormData();
  formData.append("party_id", String(payload.party_id));
  formData.append("amount", String(payload.amount));
  formData.append("payment_mode", payload.payment_mode);

  if (payload.reference_number) {
    formData.append("reference_number", payload.reference_number);
  }
  if (payload.visit_id) {
    formData.append("visit_id", String(payload.visit_id));
  }
  if (payload.collection_latitude !== undefined && payload.collection_latitude !== null) {
    formData.append("collection_latitude", String(payload.collection_latitude));
  }
  if (payload.collection_longitude !== undefined && payload.collection_longitude !== null) {
    formData.append("collection_longitude", String(payload.collection_longitude));
  }
  if (payload.allocated_invoice_id) {
    formData.append("allocated_invoice_id", String(payload.allocated_invoice_id));
  }
  if (payload.notes) {
    formData.append("notes", payload.notes);
  }
  if (payload.idempotency_key) {
    formData.append("idempotency_key", payload.idempotency_key);
  }

  if (payload.proof_photo_uri) {
    const filename = payload.proof_photo_uri.split("/").pop() || "payment_proof.jpg";
    const match = /\.(\w+)$/.exec(filename);
    const type = match ? `image/${match[1]}` : "image/jpeg";
    formData.append("proof_photo", {
      uri: payload.proof_photo_uri,
      name: filename,
      type,
    } as any);
  }

  const response = await fetch(url, {
    method: "POST",
    headers: {
      Accept: "application/json",
      Authorization: `Bearer ${token}`,
    },
    body: formData,
  });

  const resJson = await response.json();
  if (!response.ok) {
    throw new Error(resJson.message || "Failed to submit payment");
  }

  return resJson;
}

/**
 * Get field rep's own collections
 */
export async function getMyCollections(status?: string, partyId?: number): Promise<{ success: boolean; data: PaymentRecord[] }> {
  let url = "/payments/my-collections?";
  if (status && status !== "all") url += `status=${status}&`;
  if (partyId) url += `party_id=${partyId}&`;
  return requestJson(url);
}

/**
 * Get payment history + dealer ledger balance for a specific dealer
 */
export async function getPartyPayments(partyId: number): Promise<DealerPaymentSummary> {
  return requestJson(`/parties/${partyId}/payments`);
}

/**
 * Fetch official company payment settings
 */
export async function getPaymentSettings(): Promise<{ success: boolean; data: PaymentSettings }> {
  return requestJson("/payments/settings");
}
