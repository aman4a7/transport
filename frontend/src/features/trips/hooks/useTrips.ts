import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { tripApi } from '../api/tripApi';
import type { Trip, TripFilters, CreateTripData, UpdateTripData } from '../types/trip';
import type { PaginatedResponse } from '@/shared/types/api';

export function useTrips(filters: TripFilters) {
  return useQuery<PaginatedResponse<Trip>>({
    queryKey: ['trips', filters],
    queryFn: () => tripApi.list(filters).then((r) => r.data),
    placeholderData: (prev) => prev,
  });
}

export function useTrip(id: number) {
  return useQuery({
    queryKey: ['trips', id],
    queryFn: () => tripApi.get(id).then((r) => r.data.data),
    enabled: !!id,
  });
}

export function useCreateTrip() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: CreateTripData) => tripApi.create(data).then((r) => r.data.data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['trips'] }),
  });
}

export function useUpdateTrip(id: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: UpdateTripData) => tripApi.update(id, data).then((r) => r.data.data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['trips'] }),
  });
}

export function useDeleteTrip() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => tripApi.delete(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['trips'] }),
  });
}

export function useStartTrip() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => tripApi.start(id).then((r) => r.data.data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['trips'] }),
  });
}

export function useCompleteTrip() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => tripApi.complete(id).then((r) => r.data.data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['trips'] }),
  });
}

export function useCancelTrip() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => tripApi.cancel(id).then((r) => r.data.data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['trips'] }),
  });
}
