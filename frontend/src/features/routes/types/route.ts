export const RouteStatus = {
  Active: 'active',
  Inactive: 'inactive',
} as const;

export type RouteStatus = (typeof RouteStatus)[keyof typeof RouteStatus];

export interface Route {
  id: number;
  name: string;
  code: string | null;
  description: string | null;
  origin: string;
  destination: string;
  distance_km: number | null;
  estimated_duration_minutes: number | null;
  capacity: number | null;
  status: RouteStatus;
  created_at: string;
  updated_at: string;
  [key: string]: unknown;
}

export interface CreateRouteData {
  name: string;
  code?: string | null;
  description?: string | null;
  origin: string;
  destination: string;
  distance_km?: number | null;
  estimated_duration_minutes?: number | null;
  capacity?: number | null;
  status?: RouteStatus;
}

export type UpdateRouteData = Partial<CreateRouteData>;

export interface RouteFilters {
  page?: number;
  per_page?: number;
  search?: string;
  status?: RouteStatus;
  sort_by?: string;
  sort_dir?: 'asc' | 'desc';
}
