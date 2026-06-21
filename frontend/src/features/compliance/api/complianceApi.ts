import axiosClient from '@/shared/api/axiosClient';
import type { ApiResponse, PaginatedResponse } from '@/shared/types/api';
import type { ComplianceDocument, ComplianceFilters } from '../types/compliance';

export const complianceApi = {
  list: (params: ComplianceFilters) =>
    axiosClient.get<PaginatedResponse<ComplianceDocument>>('/compliance/documents', { params }),
  get: (id: number) =>
    axiosClient.get<ApiResponse<ComplianceDocument>>(`/compliance/documents/${id}`),
  upload: (data: FormData) =>
    axiosClient.post<ApiResponse<ComplianceDocument>>('/compliance/documents', data, {
      headers: { 'Content-Type': 'multipart/form-data' },
    }),
  approve: (id: number) =>
    axiosClient.post<ApiResponse<ComplianceDocument>>(`/compliance/documents/${id}/approve`),
  reject: (id: number, reason: string) =>
    axiosClient.post<ApiResponse<ComplianceDocument>>(`/compliance/documents/${id}/reject`, { reason }),
};
