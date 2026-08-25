import axiosClient from '@/shared/api/axiosClient';
import type { ApiResponse, PaginatedResponse } from '@/shared/types/api';
import type { MaintenanceRecord, CreateMaintenanceData, UpdateMaintenanceData, GarageFilters } from '../types/garage';

export const garageApi = {
  list: (params: GarageFilters) =>
    axiosClient.get<PaginatedResponse<MaintenanceRecord>>('/maintenance', { params }),
  get: (id: number) =>
    axiosClient.get<ApiResponse<MaintenanceRecord>>(`/maintenance/${id}`),
  create: (data: CreateMaintenanceData) =>
    axiosClient.post<ApiResponse<MaintenanceRecord>>('/maintenance', data),
  update: (id: number, data: UpdateMaintenanceData) =>
    axiosClient.put<ApiResponse<MaintenanceRecord>>(`/maintenance/${id}`, data),
  delete: (id: number) =>
    axiosClient.delete<ApiResponse<null>>(`/maintenance/${id}`),
  start: (id: number) =>
    axiosClient.post<ApiResponse<MaintenanceRecord>>(`/maintenance/${id}/start`),
  complete: (id: number) =>
    axiosClient.post<ApiResponse<MaintenanceRecord>>(`/maintenance/${id}/complete`),
  cancel: (id: number) =>
    axiosClient.post<ApiResponse<MaintenanceRecord>>(`/maintenance/${id}/cancel`),
};
