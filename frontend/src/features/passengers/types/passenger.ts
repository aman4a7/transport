export const PassengerStatus = {
  Active: 'active',
  Inactive: 'inactive',
} as const;

export type PassengerStatus = (typeof PassengerStatus)[keyof typeof PassengerStatus];

export interface Passenger {
  id: number;
  user_id: number | null;
  employee_id: string | null;
  first_name: string;
  last_name: string;
  email: string;
  phone: string | null;
  department: string | null;
  status: PassengerStatus;
  user?: { id: number; name: string; email: string } | null;
  created_at: string;
  updated_at: string;
  [key: string]: unknown;
}

export interface CreatePassengerData {
  user_id?: number | null;
  employee_id?: string | null;
  first_name: string;
  last_name: string;
  email: string;
  phone?: string | null;
  department?: string | null;
  status?: PassengerStatus;
}

export type UpdatePassengerData = Partial<CreatePassengerData>;

export interface PassengerFilters {
  page?: number;
  per_page?: number;
  search?: string;
  status?: PassengerStatus;
  department?: string;
  sort_by?: string;
  sort_dir?: 'asc' | 'desc';
}
