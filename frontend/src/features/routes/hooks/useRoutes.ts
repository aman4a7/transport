import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { routeApi } from '../api/routeApi';
import type { Route, RouteFilters, CreateRouteData, UpdateRouteData } from '../types/route';
import type { PaginatedResponse } from '@/shared/types/api';

export function useRoutes(filters: RouteFilters) {
  return useQuery<PaginatedResponse<Route>>({
    queryKey: ['routes', filters],
    queryFn: () => routeApi.list(filters).then((r) => r.data),
    placeholderData: (prev) => prev,
  });
}

export function useRoute(id: number) {
  return useQuery({
    queryKey: ['routes', id],
    queryFn: () => routeApi.get(id).then((r) => r.data.data),
    enabled: !!id,
  });
}

export function useCreateRoute() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: CreateRouteData) => routeApi.create(data).then((r) => r.data.data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['routes'] }),
  });
}

export function useUpdateRoute(id: number) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: UpdateRouteData) => routeApi.update(id, data).then((r) => r.data.data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['routes'] }),
  });
}

export function useDeleteRoute() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => routeApi.delete(id),
    onSuccess: () => qc.invalidateQueries({ queryKey: ['routes'] }),
  });
}
