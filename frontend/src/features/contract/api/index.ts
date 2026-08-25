import axiosClient from '@/shared/api/axiosClient';
import type { ApiResponse, PaginatedResponse } from '@/shared/types/api';
import type { Contract, CreateContractData, UpdateContractData, ContractFilters } from '../types';

export const contractApi = {
  list: (params: ContractFilters) =>
    axiosClient.get<PaginatedResponse<Contract>>('/contracts', { params }),
  get: (id: number) =>
    axiosClient.get<ApiResponse<Contract>>(`/contracts/${id}`),
  create: (data: CreateContractData) =>
    axiosClient.post<ApiResponse<Contract>>('/contracts', data),
  update: (id: number, data: UpdateContractData) =>
    axiosClient.put<ApiResponse<Contract>>(`/contracts/${id}`, data),
  delete: (id: number) =>
    axiosClient.delete<ApiResponse<null>>(`/contracts/${id}`),
  activate: (id: number) =>
    axiosClient.post<ApiResponse<Contract>>(`/contracts/${id}/activate`),
  terminate: (id: number) =>
    axiosClient.post<ApiResponse<Contract>>(`/contracts/${id}/terminate`),
};
