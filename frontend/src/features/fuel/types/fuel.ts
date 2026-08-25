export interface FuelTransaction {
  id: number;
  vehicle_id: number | null;
  fuel_type: string;
  quantity: number;
  transaction_type: 'issue' | 'restock' | 'adjustment';
  unit_cost: number | null;
  total_cost: number | null;
  issued_by: number | null;
  notes: string | null;
  created_at: string;
  vehicle?: { id: number; plate_number: string } | null;
  issuer?: { id: number; name: string } | null;
  [key: string]: unknown;
}

export interface FuelStock {
  id: number;
  fuel_type: string;
  current_quantity: number;
  minimum_quantity: number;
  last_restocked_at: string | null;
  notes: string | null;
}

export interface IssueFuelData {
  vehicle_id: number;
  fuel_type: string;
  quantity: number;
  unit_cost?: number | null;
  notes?: string | null;
}

export interface RestockFuelData {
  fuel_type: string;
  quantity: number;
  unit_cost?: number | null;
  notes?: string | null;
}

export interface AdjustFuelData {
  fuel_type: string;
  quantity: number;
  notes: string;
}

export interface FuelFilters {
  page?: number;
  per_page?: number;
  transaction_type?: string;
  vehicle_id?: number;
  fuel_type?: string;
  date_from?: string;
  date_to?: string;
  sort_by?: string;
  sort_dir?: 'asc' | 'desc';
}
