import axiosClient from '@/shared/api/axiosClient';
import type { ApiResponse, PaginatedResponse } from '@/shared/types/api';
import type { Driver, DriverFilters, CreateDriverData, UpdateDriverData } from '../types/driver';

export const driverApi = {
  list: (params: DriverFilters) =>
    axiosClient.get<PaginatedResponse<Driver>>('/drivers', { params }),
  get: (id: number) =>
    axiosClient.get<ApiResponse<Driver>>(`/drivers/${id}`),
  create: (data: CreateDriverData) =>
    axiosClient.post<ApiResponse<Driver>>('/drivers', data),
  update: (id: number, data: UpdateDriverData) =>
    axiosClient.put<ApiResponse<Driver>>(`/drivers/${id}`, data),
  delete: (id: number) =>
    axiosClient.delete<ApiResponse<void>>(`/drivers/${id}`),
};
