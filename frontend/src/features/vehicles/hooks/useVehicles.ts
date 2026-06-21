import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { vehicleApi } from '../api/vehicleApi';
import type { Vehicle, VehicleFilters, CreateVehicleData, UpdateVehicleData } from '../types/vehicle';
import type { PaginatedResponse } from '@/shared/types/api';

export function useVehicles(filters: VehicleFilters) {
  return useQuery<PaginatedResponse<Vehicle>>({
    queryKey: ['vehicles', filters],
    queryFn: () => vehicleApi.list(filters).then((r) => r.data),
    placeholderData: (prev) => prev,
  });
}

export function useVehicle(id: number) {
  return useQuery({
    queryKey: ['vehicles', id],
    queryFn: () => vehicleApi.get(id).then((r) => r.data.data),
    enabled: !!id,
  });
}

export function useCreateVehicle() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: CreateVehicleData) => vehicleApi.create(data).then((r) => r.data.data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['vehicles'] }),
  });
}

export function useUpdateVehicle(id: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: UpdateVehicleData) => vehicleApi.update(id, data).then((r) => r.data.data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['vehicles'] }),
  });
}

export function useDeleteVehicle() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => vehicleApi.delete(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['vehicles'] }),
  });
}
