import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { complianceApi } from '../api/complianceApi';
import type { ComplianceDocument, ComplianceFilters } from '../types/compliance';
import type { PaginatedResponse } from '@/shared/types/api';

export function useComplianceDocuments(filters: ComplianceFilters) {
  return useQuery<PaginatedResponse<ComplianceDocument>>({
    queryKey: ['compliance-documents', filters],
    queryFn: () => complianceApi.list(filters).then((r) => r.data),
    placeholderData: (prev) => prev,
  });
}

export function useComplianceDocument(id: number) {
  return useQuery({
    queryKey: ['compliance-documents', id],
    queryFn: () => complianceApi.get(id).then((r) => r.data.data),
    enabled: !!id,
  });
}

export function useUploadComplianceDocument() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: FormData) => complianceApi.upload(data).then((r) => r.data.data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['compliance-documents'] }),
  });
}

export function useApproveComplianceDocument() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => complianceApi.approve(id).then((r) => r.data.data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['compliance-documents'] }),
  });
}

export function useRejectComplianceDocument() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, reason }: { id: number; reason: string }) =>
      complianceApi.reject(id, reason).then((r) => r.data.data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['compliance-documents'] }),
  });
}
