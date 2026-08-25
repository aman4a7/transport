import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { contractApi } from '../api';
import type { Contract, ContractFilters, CreateContractData, UpdateContractData } from '../types';
import type { PaginatedResponse } from '@/shared/types/api';

export function useContracts(filters: ContractFilters) {
  return useQuery<PaginatedResponse<Contract>>({
    queryKey: ['contracts', filters],
    queryFn: () => contractApi.list(filters).then((r) => r.data),
    placeholderData: (prev) => prev,
  });
}

export function useContract(id: number) {
  return useQuery({
    queryKey: ['contract', id],
    queryFn: () => contractApi.get(id).then((r) => r.data.data),
    enabled: !!id,
  });
}

export function useCreateContract() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: CreateContractData) => contractApi.create(data).then((r) => r.data.data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['contracts'] });
    },
  });
}

export function useUpdateContract() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: UpdateContractData }) =>
      contractApi.update(id, data).then((r) => r.data.data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['contracts'] });
      qc.invalidateQueries({ queryKey: ['contract'] });
    },
  });
}

export function useDeleteContract() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => contractApi.delete(id).then((r) => r.data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['contracts'] });
    },
  });
}

export function useActivateContract() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => contractApi.activate(id).then((r) => r.data.data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['contracts'] });
      qc.invalidateQueries({ queryKey: ['contract'] });
    },
  });
}

export function useTerminateContract() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => contractApi.terminate(id).then((r) => r.data.data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['contracts'] });
      qc.invalidateQueries({ queryKey: ['contract'] });
    },
  });
}
