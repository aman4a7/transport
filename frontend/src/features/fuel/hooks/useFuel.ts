import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { fuelApi } from '../api/fuelApi';
import type { FuelTransaction, FuelFilters, IssueFuelData, RestockFuelData, AdjustFuelData } from '../types/fuel';
import type { PaginatedResponse } from '@/shared/types/api';

export function useFuelTransactions(filters: FuelFilters) {
  return useQuery<PaginatedResponse<FuelTransaction>>({
    queryKey: ['fuelTransactions', filters],
    queryFn: () => fuelApi.listTransactions(filters).then((r) => r.data),
    placeholderData: (prev) => prev,
  });
}

export function useFuelStock() {
  return useQuery({
    queryKey: ['fuelStock'],
    queryFn: () => fuelApi.listStocks().then((r) => r.data.data),
  });
}

export function useIssueFuel() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: IssueFuelData) => fuelApi.issue(data).then((r) => r.data.data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['fuelTransactions'] });
      qc.invalidateQueries({ queryKey: ['fuelStock'] });
    },
  });
}

export function useRestockFuel() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: RestockFuelData) => fuelApi.restock(data).then((r) => r.data.data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['fuelTransactions'] });
      qc.invalidateQueries({ queryKey: ['fuelStock'] });
    },
  });
}

export function useAdjustFuel() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: AdjustFuelData) => fuelApi.adjust(data).then((r) => r.data.data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['fuelTransactions'] });
      qc.invalidateQueries({ queryKey: ['fuelStock'] });
    },
  });
}
