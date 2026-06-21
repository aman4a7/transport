import { z } from 'zod';

export const driverSchema = z.object({
  license_number: z.string().min(1, 'License number is required').max(50),
  license_category: z.enum(['light', 'medium', 'heavy', 'trailer'], { message: 'License category is required' }),
  license_expiry: z.string().min(1, 'License expiry is required'),
  status: z.enum(['active', 'suspended', 'expired', 'inactive']).default('active'),
  user_id: z.coerce.number().int().positive().optional().nullable(),
  medical_expiry: z.string().optional().nullable(),
  assigned_vehicle_id: z.coerce.number().int().positive().optional().nullable(),
});

export type DriverFormData = z.infer<typeof driverSchema>;
