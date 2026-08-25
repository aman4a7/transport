export interface MaintenanceRecord {
  id: number;
  vehicle_id: number;
  maintenance_type: string;
  status: string;
  description: string;
  scheduled_date: string;
  started_at: string | null;
  completed_at: string | null;
  cost: number | string | null;
  notes: string | null;
  performed_by: number | null;
  created_at: string;
  updated_at: string;
  vehicle?: { id: number; plate_number: string } | null;
  performed_by_user?: { id: number; name: string } | null;
  [key: string]: unknown;
}

export interface CreateMaintenanceData {
  vehicle_id: number;
  maintenance_type: string;
  description: string;
  scheduled_date: string;
  cost?: number | null;
  notes?: string | null;
  performed_by?: number | null;
}

export interface UpdateMaintenanceData {
  vehicle_id?: number;
  maintenance_type?: string;
  description?: string;
  scheduled_date?: string;
  cost?: number | null;
  notes?: string | null;
  performed_by?: number | null;
}

export interface GarageFilters {
  page?: number;
  per_page?: number;
  status?: string;
  maintenance_type?: string;
  vehicle_id?: number;
  date_from?: string;
  date_to?: string;
  sort_by?: string;
  sort_dir?: 'asc' | 'desc';
}
