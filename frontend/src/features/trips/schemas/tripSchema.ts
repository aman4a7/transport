import { z } from 'zod';

export const tripSchema = z.object({
  route_id: z.coerce.number().int().positive('Route is required'),
  vehicle_id: z.coerce.number().int().positive('Vehicle is required'),
  driver_id: z.coerce.number().int().positive('Driver is required'),
  scheduled_date: z.string().min(1, 'Scheduled date is required'),
  departure_time: z.string().min(1, 'Departure time is required'),
  estimated_arrival_time: z.string().optional().nullable(),
  notes: z.string().max(1000).optional().nullable(),
  passenger_ids: z.array(z.coerce.number().int().positive()).optional().nullable(),
});

export type TripFormData = z.infer<typeof tripSchema>;
