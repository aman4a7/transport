import { z } from 'zod';

export const ownerSchema = z.object({
  company_name: z.string().min(1, 'Company name is required').max(200),
  contact_person: z.string().min(1, 'Contact person is required').max(200),
  phone: z.string().min(1, 'Phone is required').max(20),
  email: z.string().email('Invalid email').min(1, 'Email is required').max(255),
  address: z.string().optional().nullable(),
  status: z.enum(['active', 'inactive', 'suspended']).default('active'),
  user_id: z.coerce.number().int().positive().optional().nullable(),
});

export type OwnerFormData = z.infer<typeof ownerSchema>;
