export const VehicleCategory = {
  DefencePlated: 'defence_plated',
  ContractedPrivate: 'contracted_private',
} as const;

export type VehicleCategory = (typeof VehicleCategory)[keyof typeof VehicleCategory];

export const VehicleStatus = {
  Active: 'active',
  InMaintenance: 'in_maintenance',
  Suspended: 'suspended',
  Decommissioned: 'decommissioned',
} as const;

export type VehicleStatus = (typeof VehicleStatus)[keyof typeof VehicleStatus];

export const FuelType = {
  Diesel: 'diesel',
  Petrol: 'petrol',
  Electric: 'electric',
  Hybrid: 'hybrid',
} as const;

export type FuelType = (typeof FuelType)[keyof typeof FuelType];

export interface Vehicle {
  id: number;
  plate_number: string;
  make: string;
  model: string;
  year: number;
  color: string | null;
  vin: string | null;
  engine_number: string | null;
  seating_capacity: number | null;
  fuel_type: FuelType;
  category: VehicleCategory;
  status: VehicleStatus;
  registration_expiry: string | null;
  insurance_expiry: string | null;
  owner_id: number | null;
  owner?: { id: number; company_name: string } | null;
  created_at: string;
  updated_at: string;
  [key: string]: unknown;
}

export interface CreateVehicleData {
  plate_number: string;
  make: string;
  model: string;
  year: number;
  category: VehicleCategory;
  owner_id?: number | null;
  color?: string;
  vin?: string;
  engine_number?: string;
  seating_capacity?: number;
  fuel_type?: FuelType;
  status?: VehicleStatus;
  registration_expiry?: string;
  insurance_expiry?: string;
}

export type UpdateVehicleData = Partial<CreateVehicleData>;

export interface VehicleFilters {
  page?: number;
  per_page?: number;
  search?: string;
  category?: VehicleCategory;
  status?: VehicleStatus;
  sort_by?: string;
  sort_dir?: 'asc' | 'desc';
}
