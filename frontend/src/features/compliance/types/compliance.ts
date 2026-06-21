export const ComplianceStatus = {
  Pending: 'pending',
  Approved: 'approved',
  Rejected: 'rejected',
  Expired: 'expired',
} as const;

export type ComplianceStatus = (typeof ComplianceStatus)[keyof typeof ComplianceStatus];

export const ComplianceDocumentType = {
  VehicleRegistration: 'vehicle_registration',
  Insurance: 'insurance',
  DriverLicense: 'driver_license',
  ContractDocument: 'contract_document',
  Other: 'other',
} as const;

export type ComplianceDocumentType = (typeof ComplianceDocumentType)[keyof typeof ComplianceDocumentType];

export interface ComplianceDocument {
  id: number;
  documentable_type: string;
  documentable_id: number;
  type: ComplianceDocumentType;
  status: ComplianceStatus;
  file_path: string;
  original_filename: string;
  mime_type: string;
  file_size: number;
  issued_at: string | null;
  expires_at: string | null;
  submitted_by: { id: number; name: string } | null;
  reviewed_by: { id: number; name: string } | null;
  reviewed_at: string | null;
  rejection_reason: string | null;
  documentable?: Record<string, unknown> | null;
  created_at: string;
  updated_at: string;
  [key: string]: unknown;
}

export interface ComplianceFilters {
  page?: number;
  per_page?: number;
  status?: ComplianceStatus;
  type?: ComplianceDocumentType;
  documentable_type?: string;
  documentable_id?: number;
  sort_by?: string;
  sort_dir?: 'asc' | 'desc';
}
