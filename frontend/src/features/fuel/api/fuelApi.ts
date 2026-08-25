import axiosClient from '@/shared/api/axiosClient';
import type { ApiResponse, PaginatedResponse } from '@/shared/types/api';
import type { FuelTransaction, FuelStock, FuelFilters, IssueFuelData, RestockFuelData, AdjustFuelData } from '../types/fuel';

export const fuelApi = {
  listTransactions: (params: FuelFilters) =>
    axiosClient.get<PaginatedResponse<FuelTransaction>>('/fuel/transactions', { params }),
  getTransaction: (id: number) =>
    axiosClient.get<ApiResponse<FuelTransaction>>(`/fuel/transactions/${id}`),
  issue: (data: IssueFuelData) =>
    axiosClient.post<ApiResponse<FuelTransaction>>('/fuel/issue', data),
  restock: (data: RestockFuelData) =>
    axiosClient.post<ApiResponse<FuelTransaction>>('/fuel/restock', data),
  adjust: (data: AdjustFuelData) =>
    axiosClient.post<ApiResponse<FuelTransaction>>('/fuel/adjust', data),
  listStocks: () =>
    axiosClient.get<ApiResponse<FuelStock[]>>('/fuel/stocks'),
  getStock: (fuelType: string) =>
    axiosClient.get<ApiResponse<FuelStock>>(`/fuel/stocks/${fuelType}`),
};
