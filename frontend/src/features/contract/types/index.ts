export interface Contract {
  id: number;
  vehicle_id: number;
  owner_id: number;
  contract_number: string;
  start_date: string;
  end_date: string;
  status: string;
  contract_value: number | null;
  payment_terms: string | null;
  notes: string | null;
  created_at: string;
  updated_at: string;
  vehicle?: { id: number; plate_number: string } | null;
  owner?: { id: number; company_name: string } | null;
  [key: string]: unknown;
}

export interface CreateContractData {
  vehicle_id: number;
  owner_id: number;
  start_date: string;
  end_date: string;
  contract_value?: number | null;
  payment_terms?: string | null;
  notes?: string | null;
}

export interface UpdateContractData {
  vehicle_id?: number;
  owner_id?: number;
  start_date?: string;
  end_date?: string;
  contract_value?: number | null;
  payment_terms?: string | null;
  notes?: string | null;
}

export interface ContractFilters {
  page?: number;
  per_page?: number;
  status?: string;
  vehicle_id?: number;
  owner_id?: number;
  date_from?: string;
  date_to?: string;
  sort_by?: string;
  sort_dir?: 'asc' | 'desc';
}
