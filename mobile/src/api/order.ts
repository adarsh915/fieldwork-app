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

export const getOrders = async (partyId?: number): Promise<any> => {
  const url = partyId ? `/orders?party_id=${partyId}` : "/orders";
  return await requestJson(url, { method: "GET" });
};

export const getOrderConfig = async (): Promise<{ success: boolean; data?: { allow_phone_orders: boolean } }> => {
  return await requestJson("/orders/config", { method: "GET" });
};
