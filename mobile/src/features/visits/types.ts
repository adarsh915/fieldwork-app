export type Visit = {
  id: number;
  customerId: number;
  visitedAt: string; // ISO string
  notes?: string | null;
};
