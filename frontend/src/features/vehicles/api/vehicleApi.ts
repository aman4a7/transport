import axiosClient from '@/shared/api/axiosClient';
import type { ApiResponse, PaginatedResponse } from '@/shared/types/api';
import type { Vehicle, VehicleFilters, CreateVehicleData, UpdateVehicleData } from '../types/vehicle';

export const vehicleApi = {
  list: (params: VehicleFilters) =>
    axiosClient.get<PaginatedResponse<Vehicle>>('/vehicles', { params }),
  get: (id: number) =>
    axiosClient.get<ApiResponse<Vehicle>>(`/vehicles/${id}`),
  create: (data: CreateVehicleData) =>
    axiosClient.post<ApiResponse<Vehicle>>('/vehicles', data),
  update: (id: number, data: UpdateVehicleData) =>
    axiosClient.put<ApiResponse<Vehicle>>(`/vehicles/${id}`, data),
  delete: (id: number) =>
    axiosClient.delete<ApiResponse<void>>(`/vehicles/${id}`),
};
