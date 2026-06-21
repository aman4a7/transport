import { z } from 'zod';

export const complianceUploadSchema = z.object({
  documentable_type: z.enum([
    'App\\Domain\\Vehicle\\Models\\Vehicle',
    'App\\Domain\\Driver\\Models\\Driver',
    'App\\Domain\\Owner\\Models\\Owner',
  ]),
  documentable_id: z.coerce.number().int().positive(),
  type: z.enum([
    'vehicle_registration',
    'insurance',
    'driver_license',
    'contract_document',
    'other',
  ]),
  file: z
    .any()
    .refine((v) => v instanceof File, 'File is required')
    .refine((v) => v instanceof File && v.size <= 10 * 1024 * 1024, 'File must not exceed 10MB')
    .refine(
      (v) => v instanceof File && ['application/pdf', 'image/jpeg', 'image/png'].includes(v.type),
      'File must be PDF, JPEG, or PNG',
    ),
  issued_at: z.string().optional(),
  expires_at: z.string().optional(),
}).refine(
  (data) => !data.issued_at || !data.expires_at || data.expires_at >= data.issued_at,
  { message: 'Expiry date must be after issue date', path: ['expires_at'] },
);

export type ComplianceUploadFormData = z.infer<typeof complianceUploadSchema>;
