export const DriverStatus = {
  Active: 'active',
  Suspended: 'suspended',
  Expired: 'expired',
  Inactive: 'inactive',
} as const;

export type DriverStatus = (typeof DriverStatus)[keyof typeof DriverStatus];

export interface Driver {
  id: number;
  user_id: number | null;
  license_number: string;
  license_category: string;
  license_expiry: string;
  status: DriverStatus;
  medical_expiry: string | null;
  assigned_vehicle_id: number | null;
  user?: { id: number; name: string; email: string } | null;
  assigned_vehicle?: { id: number; plate_number: string } | null;
  created_at: string;
  updated_at: string;
  [key: string]: unknown;
}

export interface CreateDriverData {
  user_id?: number | null;
  license_number: string;
  license_category: string;
  license_expiry: string;
  status?: DriverStatus;
  medical_expiry?: string | null;
  assigned_vehicle_id?: number | null;
}

export type UpdateDriverData = Partial<CreateDriverData>;

export interface DriverFilters {
  page?: number;
  per_page?: number;
  search?: string;
  status?: DriverStatus;
  sort_by?: string;
  sort_dir?: 'asc' | 'desc';
}
