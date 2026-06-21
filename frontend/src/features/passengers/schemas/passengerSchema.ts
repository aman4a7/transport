import { z } from 'zod';

export const passengerSchema = z.object({
  first_name: z.string().min(1, 'First name is required').max(100),
  last_name: z.string().min(1, 'Last name is required').max(100),
  email: z.string().email('Invalid email').min(1, 'Email is required').max(100),
  status: z.enum(['active', 'inactive']).default('active'),
  employee_id: z.string().max(50).optional().nullable(),
  phone: z.string().max(50).optional().nullable(),
  department: z.string().max(100).optional().nullable(),
  user_id: z.coerce.number().int().positive().optional().nullable(),
});

export type PassengerFormData = z.infer<typeof passengerSchema>;
