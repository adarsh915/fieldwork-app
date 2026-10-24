import { requestJson } from "./http";

export interface OrderItemPayload {
  product_id: number;
  product_unit_id: number;
  quantity: number;
  price: number;
}

export interface OrderPayload {
  visit_id?: number | null;
  party_id: number;
  order_source?: "visit" | "phone";
  order_date?: string;
  delivery_destination?: string;
  preferred_transport?: string;
  vehicle_no?: string;
  dispatch_date?: string;
  remarks?: string;
  schema?: string;
  order_type?: string;
  payment_mode?: string;
  items: OrderItemPayload[];
}

export const submitOrder = async (payload: OrderPayload): Promise<any> => {
  const data = await requestJson("/orders", { method: "POST", body: payload });
  return data;
};

export interface GetOrdersParams {
  partyId?: number | string;
  page?: number;
  per_page?: number;
  search?: string;
}

export const getOrders = async (
  partyIdOrParams?: number | string | GetOrdersParams,
  params?: { page?: number; per_page?: number; search?: string }
): Promise<any> => {
  let partyId: number | string | undefined;
  let page: number | undefined;
  let per_page: number | undefined;
  let search: string | undefined;

  if (typeof partyIdOrParams === "object" && partyIdOrParams !== null) {
    partyId = partyIdOrParams.partyId;
    page = partyIdOrParams.page;
    per_page = partyIdOrParams.per_page;
    search = partyIdOrParams.search;
  } else {
    partyId = partyIdOrParams;
    if (params) {
      page = params.page;
      per_page = params.per_page;
      search = params.search;
    }
  }

  const queryParams = new URLSearchParams();
  if (partyId !== undefined) queryParams.append("party_id", String(partyId));
  if (page !== undefined) queryParams.append("page", String(page));
  if (per_page !== undefined) queryParams.append("per_page", String(per_page));
  if (search) queryParams.append("search", search);

  const qs = queryParams.toString();
  const url = qs ? `/orders?${qs}` : "/orders";
  return await requestJson(url, { method: "GET" });
};

export const getOrderConfig = async (): Promise<{ success: boolean; data?: { allow_phone_orders: boolean } }> => {
  return await requestJson("/orders/config", { method: "GET" });
};
