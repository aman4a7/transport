import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { driverApi } from '../api/driverApi';
import type { Driver, DriverFilters, CreateDriverData, UpdateDriverData } from '../types/driver';
import type { PaginatedResponse } from '@/shared/types/api';

export function useDrivers(filters: DriverFilters) {
  return useQuery<PaginatedResponse<Driver>>({
    queryKey: ['drivers', filters],
    queryFn: () => driverApi.list(filters).then((r) => r.data),
    placeholderData: (prev) => prev,
  });
}

export function useDriver(id: number) {
  return useQuery({
    queryKey: ['drivers', id],
    queryFn: () => driverApi.get(id).then((r) => r.data.data),
    enabled: !!id,
  });
}

export function useCreateDriver() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: CreateDriverData) => driverApi.create(data).then((r) => r.data.data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['drivers'] }),
  });
}

export function useUpdateDriver(id: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: UpdateDriverData) => driverApi.update(id, data).then((r) => r.data.data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['drivers'] }),
  });
}

export function useDeleteDriver() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => driverApi.delete(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['drivers'] }),
  });
}
