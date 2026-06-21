import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { passengerApi } from '../api/passengerApi';
import type { Passenger, PassengerFilters, CreatePassengerData, UpdatePassengerData } from '../types/passenger';
import type { PaginatedResponse } from '@/shared/types/api';

export function usePassengers(filters: PassengerFilters) {
  return useQuery<PaginatedResponse<Passenger>>({
    queryKey: ['passengers', filters],
    queryFn: () => passengerApi.list(filters).then((r) => r.data),
    placeholderData: (prev) => prev,
  });
}

export function usePassenger(id: number) {
  return useQuery({
    queryKey: ['passengers', id],
    queryFn: () => passengerApi.get(id).then((r) => r.data.data),
    enabled: !!id,
  });
}

export function useCreatePassenger() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: CreatePassengerData) => passengerApi.create(data).then((r) => r.data.data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['passengers'] }),
  });
}

export function useUpdatePassenger(id: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: UpdatePassengerData) => passengerApi.update(id, data).then((r) => r.data.data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['passengers'] }),
  });
}

export function useDeletePassenger() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => passengerApi.delete(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['passengers'] }),
  });
}
