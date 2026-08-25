export const TripStatus = {
  Scheduled: 'scheduled',
  InProgress: 'in_progress',
  Completed: 'completed',
  Cancelled: 'cancelled',
} as const;

export type TripStatus = (typeof TripStatus)[keyof typeof TripStatus];

export const TripAssignmentStatus = {
  Confirmed: 'confirmed',
  Cancelled: 'cancelled',
  Boarded: 'boarded',
  NoShow: 'no_show',
} as const;

export type TripAssignmentStatus = (typeof TripAssignmentStatus)[keyof typeof TripAssignmentStatus];

export interface TripAssignment {
  id: number;
  trip_id: number;
  passenger_id: number;
  status: TripAssignmentStatus;
  passenger?: { id: number; first_name: string; last_name: string };
}

export interface Trip {
  id: number;
  route_id: number;
  vehicle_id: number;
  driver_id: number;
  scheduled_date: string;
  departure_time: string;
  estimated_arrival_time: string | null;
  actual_departure_time: string | null;
  actual_arrival_time: string | null;
  status: TripStatus;
  notes: string | null;
  route?: { id: number; name: string };
  vehicle?: { id: number; plate_number: string };
  driver?: { id: number };
  assignments?: TripAssignment[];
  created_at: string;
  updated_at: string;
  [key: string]: unknown;
}

export interface CreateTripData {
  route_id: number;
  vehicle_id: number;
  driver_id: number;
  scheduled_date: string;
  departure_time: string;
  estimated_arrival_time?: string | null;
  notes?: string | null;
  passenger_ids?: number[];
}

export type UpdateTripData = Partial<CreateTripData>;

export interface TripFilters {
  page?: number;
  per_page?: number;
  status?: TripStatus;
  route_id?: number;
  vehicle_id?: number;
  date_from?: string;
  date_to?: string;
  sort_by?: string;
  sort_dir?: 'asc' | 'desc';
}
