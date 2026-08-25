import { z } from 'zod';

const fuelTypeEnum = z.enum(['diesel', 'petrol']);

export const issueFuelSchema = z.object({
  vehicle_id: z.coerce.number().int().positive('Vehicle is required'),
  fuel_type: fuelTypeEnum,
  quantity: z.coerce.number().positive('Quantity must be positive'),
  unit_cost: z.coerce.number().min(0).optional().nullable(),
  notes: z.string().max(1000).optional().nullable(),
});

export const restockFuelSchema = z.object({
  fuel_type: fuelTypeEnum,
  quantity: z.coerce.number().positive('Quantity must be positive'),
  unit_cost: z.coerce.number().min(0).optional().nullable(),
  notes: z.string().max(1000).optional().nullable(),
});

export const adjustFuelSchema = z.object({
  fuel_type: fuelTypeEnum,
  quantity: z.coerce.number({ error: 'Quantity is required' }),
  notes: z.string().min(10, 'Reason must be at least 10 characters').max(1000),
});

export type IssueFuelFormData = z.infer<typeof issueFuelSchema>;
export type RestockFuelFormData = z.infer<typeof restockFuelSchema>;
export type AdjustFuelFormData = z.infer<typeof adjustFuelSchema>;
