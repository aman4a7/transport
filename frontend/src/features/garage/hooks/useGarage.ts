import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { garageApi } from '../api/garageApi';
import type { MaintenanceRecord, GarageFilters, CreateMaintenanceData, UpdateMaintenanceData } from '../types/garage';
import type { PaginatedResponse } from '@/shared/types/api';

export function useMaintenanceRecords(filters: GarageFilters) {
  return useQuery<PaginatedResponse<MaintenanceRecord>>({
    queryKey: ['maintenanceRecords', filters],
    queryFn: () => garageApi.list(filters).then((r) => r.data),
    placeholderData: (prev) => prev,
  });
}

export function useMaintenanceRecord(id: number) {
  return useQuery({
    queryKey: ['maintenanceRecord', id],
    queryFn: () => garageApi.get(id).then((r) => r.data.data),
    enabled: !!id,
  });
}

export function useCreateMaintenanceRecord() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: CreateMaintenanceData) => garageApi.create(data).then((r) => r.data.data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['maintenanceRecords'] });
    },
  });
}

export function useUpdateMaintenanceRecord() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ id, data }: { id: number; data: UpdateMaintenanceData }) =>
      garageApi.update(id, data).then((r) => r.data.data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['maintenanceRecords'] });
      qc.invalidateQueries({ queryKey: ['maintenanceRecord'] });
    },
  });
}

export function useDeleteMaintenanceRecord() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => garageApi.delete(id).then((r) => r.data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['maintenanceRecords'] });
    },
  });
}

export function useStartMaintenance() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => garageApi.start(id).then((r) => r.data.data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['maintenanceRecords'] });
      qc.invalidateQueries({ queryKey: ['maintenanceRecord'] });
    },
  });
}

export function useCompleteMaintenance() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => garageApi.complete(id).then((r) => r.data.data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['maintenanceRecords'] });
      qc.invalidateQueries({ queryKey: ['maintenanceRecord'] });
    },
  });
}

export function useCancelMaintenance() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => garageApi.cancel(id).then((r) => r.data.data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['maintenanceRecords'] });
      qc.invalidateQueries({ queryKey: ['maintenanceRecord'] });
    },
  });
}
