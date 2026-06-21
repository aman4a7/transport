import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { ownerApi } from '../api/ownerApi';
import type { Owner, OwnerFilters, CreateOwnerData, UpdateOwnerData } from '../types/owner';
import type { PaginatedResponse } from '@/shared/types/api';

export function useOwners(filters: OwnerFilters) {
  return useQuery<PaginatedResponse<Owner>>({
    queryKey: ['owners', filters],
    queryFn: () => ownerApi.list(filters).then((r) => r.data),
    placeholderData: (prev) => prev,
  });
}

export function useOwner(id: number) {
  return useQuery({
    queryKey: ['owners', id],
    queryFn: () => ownerApi.get(id).then((r) => r.data.data),
    enabled: !!id,
  });
}

export function useCreateOwner() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: CreateOwnerData) => ownerApi.create(data).then((r) => r.data.data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['owners'] }),
  });
}

export function useUpdateOwner(id: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: UpdateOwnerData) => ownerApi.update(id, data).then((r) => r.data.data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['owners'] }),
  });
}

export function useDeleteOwner() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => ownerApi.delete(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['owners'] }),
  });
}
