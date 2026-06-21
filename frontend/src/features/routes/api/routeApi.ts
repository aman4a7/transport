import axiosClient from '@/shared/api/axiosClient';
import type { ApiResponse, PaginatedResponse } from '@/shared/types/api';
import type { Route, RouteFilters, CreateRouteData, UpdateRouteData } from '../types/route';

export const routeApi = {
  list: (params: RouteFilters) =>
    axiosClient.get<PaginatedResponse<Route>>('/routes', { params }),
  get: (id: number) =>
    axiosClient.get<ApiResponse<Route>>(`/routes/${id}`),
  create: (data: CreateRouteData) =>
    axiosClient.post<ApiResponse<Route>>('/routes', data),
  update: (id: number, data: UpdateRouteData) =>
    axiosClient.put<ApiResponse<Route>>(`/routes/${id}`, data),
  delete: (id: number) =>
    axiosClient.delete<ApiResponse<void>>(`/routes/${id}`),
};
