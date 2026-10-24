import { requestJson } from "./http";

export interface ExpenseCategory {
  id: number;
  name: string;
}

export interface ExpenseItem {
  id: number;
  expense_bill_id: number;
  item_name: string;
  quantity: number;
  unit_price: number | string;
  total_price: number | string;
}

export interface ExpenseItemInput {
  item_name: string;
  quantity: number;
  unit_price: number;
  total_price: number;
}

export interface ExpenseBill {
  id: number;
  category_id: number;
  amount: string | number;
  date: string;
  bill_photo: string | null;
  bill_photo_url?: string | null;
  status: 'pending' | 'temp_approved' | 'approved' | 'manager_rejected' | 'rejected';
  is_system_generated: boolean;
  is_after_day_end?: boolean;
  category?: ExpenseCategory;
  items?: ExpenseItem[];
  rejection_reason?: string | null;
  rejected_by?: { id: number; name: string } | null;
  rejected_at?: string | null;
  temp_approved_by?: { id: number; name: string } | null;
  temp_approved_at?: string | null;
  temp_approved_note?: string | null;
  approved_by?: { id: number; name: string } | null;
  approved_at?: string | null;
  final_note?: string | null;
  created_at: string;
}

export interface GetExpensesParams {
  page?: number;
  per_page?: number;
  status?: string;
  search?: string;
  category_id?: number | string;
}

export interface GetExpensesResponse {
  success: boolean;
  data: ExpenseBill[];
  current_page?: number;
  last_page?: number;
  per_page?: number;
  total?: number;
  has_more?: boolean;
  counts?: {
    all: number;
    pending: number;
    approved: number;
    rejected: number;
  };
}

export const getExpenses = async (
  params?: GetExpensesParams
): Promise<GetExpensesResponse> => {
  const queryParams = new URLSearchParams();
  if (params?.page !== undefined) queryParams.append("page", String(params.page));
  if (params?.per_page !== undefined) queryParams.append("per_page", String(params.per_page));
  if (params?.status) queryParams.append("status", params.status);
  if (params?.search) queryParams.append("search", params.search);
  if (params?.category_id !== undefined) queryParams.append("category_id", String(params.category_id));

  const qs = queryParams.toString();
  const url = qs ? `/expenses?${qs}` : "/expenses";
  return await requestJson<GetExpensesResponse>(url);
};

export interface MonthlyExpenseSummary {
  month: string; // YYYY-MM
  total_approved: string | number;
}

export const getMonthlyExpenses = async (): Promise<{ success: boolean; data: MonthlyExpenseSummary[] }> => {
  return await requestJson<{ success: boolean; data: MonthlyExpenseSummary[] }>('/expenses/monthly-summary');
};

export interface ExpenseStatementRow {
  date: string;
  food: number;
  hotel: number;
  travel: number;
  misc: number;
  other: number;
  system_travel: number;
  day_total: number;
}

export interface ExpenseStatementResponse {
  success: boolean;
  data: {
    statementData: ExpenseStatementRow[];
    totals: Omit<ExpenseStatementRow, 'date'> & { grand_total: number };
  }
}

export const getMonthlyStatement = async (month: string): Promise<ExpenseStatementResponse> => {
  return await requestJson<ExpenseStatementResponse>(`/expenses/statement?month=${month}`);
};

export interface ExpenseCategoriesResponse {
  success: boolean;
  data: ExpenseCategory[];
  past_days_allowed?: number;
}

export const getExpenseCategories = async (): Promise<ExpenseCategoriesResponse> => {
  return await requestJson<ExpenseCategoriesResponse>('/expenses/categories');
};

import { API_BASE_URL, API_PREFIX } from "./config";
import { getAuthToken } from "../auth/tokenStore";

export const submitExpense = async (
  categoryId: number,
  amount: string,
  photoUri: string,
  items?: ExpenseItemInput[],
  date?: string
): Promise<any> => {
  const token = await getAuthToken();
  const form = new FormData();
  form.append("category_id", categoryId.toString());
  form.append("amount", amount);
  if (date) {
    form.append("date", date);
  }
  if (items && items.length > 0) {
    form.append("items_json", JSON.stringify(items));
  }
  
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

export const updateExpense = async (
  id: number,
  categoryId: number,
  amount: string,
  photoUri?: string | null,
  items?: ExpenseItemInput[],
  date?: string
): Promise<any> => {
  const token = await getAuthToken();
  const form = new FormData();
  form.append("category_id", categoryId.toString());
  form.append("amount", amount);
  if (date) {
    form.append("date", date);
  }
  if (items && items.length > 0) {
    form.append("items_json", JSON.stringify(items));
  }
  
  if (photoUri && !photoUri.startsWith("http")) {
    const filename = photoUri.split("/").pop() ?? "expense_photo.jpg";
    const ext = filename.split(".").pop()?.toLowerCase() ?? "jpg";
    const mime = ext === "png" ? "image/png" : "image/jpeg";
    
    form.append("bill_photo", {
      uri: photoUri,
      name: filename,
      type: mime,
    } as any);
  }

  const res = await fetch(`${API_BASE_URL}${API_PREFIX}/expenses/${id}`, {
    method: "POST",
    headers: {
      Authorization: `Bearer ${token}`,
      Accept: "application/json",
    },
    body: form,
  });

  if (!res.ok) {
    const errorData = await res.json().catch(() => ({}));
    throw new Error(errorData.message || "Failed to update expense.");
  }
  
  return await res.json();
};
