import { z } from 'zod';

export const routeSchema = z.object({
  name: z.string().min(1, 'Name is required').max(255),
  code: z.string().max(20).optional().nullable(),
  description: z.string().optional().nullable(),
  origin: z.string().min(1, 'Origin is required').max(255),
  destination: z.string().min(1, 'Destination is required').max(255),
  distance_km: z.coerce.number().positive('Must be positive').optional().nullable(),
  estimated_duration_minutes: z.coerce.number().int().positive('Must be positive').optional().nullable(),
  capacity: z.coerce.number().int().positive('Must be positive').optional().nullable(),
  status: z.enum(['active', 'inactive']).default('active'),
});

export type RouteFormData = z.infer<typeof routeSchema>;
