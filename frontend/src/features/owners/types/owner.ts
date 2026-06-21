export const OwnerStatus = {
  Active: 'active',
  Inactive: 'inactive',
  Suspended: 'suspended',
} as const;

export type OwnerStatus = (typeof OwnerStatus)[keyof typeof OwnerStatus];

export interface Owner {
  id: number;
  user_id: number | null;
  company_name: string;
  contact_person: string;
  phone: string;
  email: string;
  address: string | null;
  status: OwnerStatus;
  user?: { id: number; name: string; email: string } | null;
  vehicles?: { id: number; plate_number: string }[];
  created_at: string;
  updated_at: string;
  [key: string]: unknown;
}

export interface CreateOwnerData {
  user_id?: number | null;
  company_name: string;
  contact_person: string;
  phone: string;
  email: string;
  address?: string | null;
  status?: OwnerStatus;
}

export type UpdateOwnerData = Partial<CreateOwnerData>;

export interface OwnerFilters {
  page?: number;
  per_page?: number;
  search?: string;
  status?: OwnerStatus;
  sort_by?: string;
  sort_dir?: 'asc' | 'desc';
}
