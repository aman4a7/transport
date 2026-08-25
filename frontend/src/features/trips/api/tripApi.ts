import axiosClient from '@/shared/api/axiosClient';
import type { ApiResponse, PaginatedResponse } from '@/shared/types/api';
import type { Trip, TripFilters, CreateTripData, UpdateTripData } from '../types/trip';

export const tripApi = {
  list: (params: TripFilters) =>
    axiosClient.get<PaginatedResponse<Trip>>('/trips', { params }),
  get: (id: number) =>
    axiosClient.get<ApiResponse<Trip>>(`/trips/${id}`),
  create: (data: CreateTripData) =>
    axiosClient.post<ApiResponse<Trip>>('/trips', data),
  update: (id: number, data: UpdateTripData) =>
    axiosClient.put<ApiResponse<Trip>>(`/trips/${id}`, data),
  delete: (id: number) =>
    axiosClient.delete<ApiResponse<void>>(`/trips/${id}`),
  start: (id: number) =>
    axiosClient.post<ApiResponse<Trip>>(`/trips/${id}/start`),
  complete: (id: number) =>
    axiosClient.post<ApiResponse<Trip>>(`/trips/${id}/complete`),
  cancel: (id: number) =>
    axiosClient.post<ApiResponse<Trip>>(`/trips/${id}/cancel`),
};
