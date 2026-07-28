export type Order = {
  id: number;
  customerId: number;
  createdAt: string; // ISO string
  totalAmount: number;
  status?: "draft" | "submitted" | "approved" | "rejected";
};
