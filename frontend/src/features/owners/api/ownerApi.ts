import axiosClient from '@/shared/api/axiosClient';
import type { ApiResponse, PaginatedResponse } from '@/shared/types/api';
import type { Owner, OwnerFilters, CreateOwnerData, UpdateOwnerData } from '../types/owner';

export const ownerApi = {
  list: (params: OwnerFilters) =>
    axiosClient.get<PaginatedResponse<Owner>>('/owners', { params }),
  get: (id: number) =>
    axiosClient.get<ApiResponse<Owner>>(`/owners/${id}`),
  create: (data: CreateOwnerData) =>
    axiosClient.post<ApiResponse<Owner>>('/owners', data),
  update: (id: number, data: UpdateOwnerData) =>
    axiosClient.put<ApiResponse<Owner>>(`/owners/${id}`, data),
  delete: (id: number) =>
    axiosClient.delete<ApiResponse<void>>(`/owners/${id}`),
};
