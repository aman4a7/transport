import { z } from 'zod';

export const vehicleSchema = z.object({
  plate_number: z.string().min(1, 'Plate number is required').max(20),
  make: z.string().min(1, 'Make is required').max(100),
  model: z.string().min(1, 'Model is required').max(100),
  year: z.coerce.number().int().min(1900).max(2100),
  category: z.enum(['defence_plated', 'contracted_private']),
  owner_id: z.coerce.number().int().positive().optional().nullable(),
  color: z.string().max(30).optional().nullable(),
  vin: z.string().max(17).optional().nullable(),
  engine_number: z.string().max(50).optional().nullable(),
  seating_capacity: z.coerce.number().int().positive().optional().nullable(),
  fuel_type: z.enum(['diesel', 'petrol', 'electric', 'hybrid']).default('diesel'),
  status: z.enum(['active', 'in_maintenance', 'suspended', 'decommissioned']).default('active'),
  registration_expiry: z.string().optional().nullable(),
  insurance_expiry: z.string().optional().nullable(),
}).superRefine((data, ctx) => {
  if (data.category === 'contracted_private' && !data.owner_id) {
    ctx.addIssue({ code: z.ZodIssueCode.custom, message: 'Owner is required for contracted private vehicles', path: ['owner_id'] });
  }
});

export type VehicleFormData = z.infer<typeof vehicleSchema>;
