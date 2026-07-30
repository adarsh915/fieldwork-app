import { requestJson } from "./http";

export interface ExpenseCategory {
  id: number;
  name: string;
}

export interface ExpenseBill {
  id: number;
  category_id: number;
  amount: string | number;
  date: string;
  bill_photo: string | null;
  status: 'pending' | 'approved' | 'rejected';
  is_system_generated: boolean;
  category?: ExpenseCategory;
  created_at: string;
}

export const getExpenses = async (): Promise<{ success: boolean; data: ExpenseBill[] }> => {
  return await requestJson<{ success: boolean; data: ExpenseBill[] }>('/expenses');
};

export const getExpenseCategories = async (): Promise<{ success: boolean; data: ExpenseCategory[] }> => {
  return await requestJson<{ success: boolean; data: ExpenseCategory[] }>('/expenses/categories');
};

import { API_BASE_URL, API_PREFIX } from "./config";
import { getAuthToken } from "../auth/tokenStore";

export const submitExpense = async (categoryId: number, amount: string, photoUri: string): Promise<any> => {
  const token = await getAuthToken();
  const form = new FormData();
  form.append("category_id", categoryId.toString());
  form.append("amount", amount);
  
  const filename = photoUri.split("/").pop() ?? "expense_photo.jpg";
  const ext = filename.split(".").pop()?.toLowerCase() ?? "jpg";
  const mime = ext === "png" ? "image/png" : "image/jpeg";
  
  form.append("bill_photo", {
    uri: photoUri,
    name: filename,
    type: mime,
  } as any);

  const res = await fetch(`${API_BASE_URL}${API_PREFIX}/expenses`, {
    method: "POST",
    headers: {
      Authorization: `Bearer ${token}`,
      Accept: "application/json",
    },
    body: form,
  });

  if (!res.ok) {
    const errorData = await res.json().catch(() => ({}));
    throw new Error(errorData.message || "Failed to submit expense.");
  }
  
  return await res.json();
};
