import { z } from 'zod';

const maintenanceTypeEnum = z.enum(['scheduled', 'repair', 'inspection', 'other']);

export const createMaintenanceSchema = z.object({
  vehicle_id: z.coerce.number().int().positive('Vehicle is required'),
  maintenance_type: maintenanceTypeEnum,
  description: z.string().min(1, 'Description is required').max(2000),
  scheduled_date: z.string().min(1, 'Scheduled date is required'),
  cost: z.coerce.number().min(0).optional().nullable(),
  notes: z.string().max(2000).optional().nullable(),
  performed_by: z.coerce.number().int().positive().optional().nullable(),
});

export const updateMaintenanceSchema = z.object({
  vehicle_id: z.coerce.number().int().positive().optional(),
  maintenance_type: maintenanceTypeEnum.optional(),
  description: z.string().min(1).max(2000).optional(),
  scheduled_date: z.string().min(1).optional(),
  cost: z.coerce.number().min(0).optional().nullable(),
  notes: z.string().max(2000).optional().nullable(),
  performed_by: z.coerce.number().int().positive().optional().nullable(),
});

export type CreateMaintenanceFormData = z.infer<typeof createMaintenanceSchema>;
export type UpdateMaintenanceFormData = z.infer<typeof updateMaintenanceSchema>;
