import { requestJson } from "./http";

export interface ProductUnit {
  id: number;
  product_id: number;
  unit_name: string;
  price: number;
  is_default: boolean;
}

export interface Product {
  id: number;
  name: string;
  code: string;
  category: string;
  description: string;
  status: string;
  units: ProductUnit[];
}

export const getProducts = async (): Promise<{
  success: boolean;
  data: Product[];
}> => {
  const data = await requestJson<{ success: boolean; data: Product[] }>(
    "/products",
  );
  return data;
};
