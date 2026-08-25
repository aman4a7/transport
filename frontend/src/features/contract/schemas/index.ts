import { z } from 'zod';

export const createContractSchema = z.object({
  vehicle_id: z.coerce.number().int().positive('Vehicle is required'),
  owner_id: z.coerce.number().int().positive('Contractor is required'),
  start_date: z.string().min(1, 'Start date is required'),
  end_date: z.string().min(1, 'End date is required'),
  contract_value: z.coerce.number().min(0).optional().nullable(),
  payment_terms: z.string().max(2000).optional().nullable(),
  notes: z.string().max(5000).optional().nullable(),
});

export const updateContractSchema = z.object({
  vehicle_id: z.coerce.number().int().positive().optional(),
  owner_id: z.coerce.number().int().positive().optional(),
  start_date: z.string().min(1).optional(),
  end_date: z.string().min(1).optional(),
  contract_value: z.coerce.number().min(0).optional().nullable(),
  payment_terms: z.string().max(2000).optional().nullable(),
  notes: z.string().max(5000).optional().nullable(),
});

export type CreateContractFormData = z.infer<typeof createContractSchema>;
export type UpdateContractFormData = z.infer<typeof updateContractSchema>;
