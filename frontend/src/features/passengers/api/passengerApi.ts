import axiosClient from '@/shared/api/axiosClient';
import type { ApiResponse, PaginatedResponse } from '@/shared/types/api';
import type { Passenger, PassengerFilters, CreatePassengerData, UpdatePassengerData } from '../types/passenger';

export const passengerApi = {
  list: (params: PassengerFilters) =>
    axiosClient.get<PaginatedResponse<Passenger>>('/passengers', { params }),
  get: (id: number) =>
    axiosClient.get<ApiResponse<Passenger>>(`/passengers/${id}`),
  create: (data: CreatePassengerData) =>
    axiosClient.post<ApiResponse<Passenger>>('/passengers', data),
  update: (id: number, data: UpdatePassengerData) =>
    axiosClient.put<ApiResponse<Passenger>>(`/passengers/${id}`, data),
  delete: (id: number) =>
    axiosClient.delete<ApiResponse<void>>(`/passengers/${id}`),
};
