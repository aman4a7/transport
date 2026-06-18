import { useQuery, type UseQueryOptions } from '@tanstack/react-query';
import { vehicleApi } from '../api/vehicleApi';
import type { Vehicle, VehicleFilters } from '../types/vehicle';
import type { PaginatedResponse, ApiResponse } from '@/shared/types/api';

/**
 * EXAMPLE: Vehicle query hooks using the react-frontend api-hook template.
 *
 * Demonstrates:
 * - Typed query keys for cache management
 * - Paginated list query with filter params
 * - Single entity query with conditional enabling
 * - placeholderData for smooth pagination transitions
 */

/**
 * Fetch a paginated list of vehicles.
 *
 * @example
 * const { data, isLoading } = useVehicles({ page: 1, category: 'defence_plated' });
 */
export function useVehicles(filters: VehicleFilters) {
  return useQuery<PaginatedResponse<Vehicle>>({
    queryKey: ['vehicles', filters],
    queryFn: () => vehicleApi.list(filters).then((r) => r.data),
    placeholderData: (prev) => prev,
  });
}

/**
 * Fetch a single vehicle by ID.
 *
 * @example
 * const { data } = useVehicle(42);
 * const { data } = useVehicle(id, { enabled: !!id }); // conditional
 */
export function useVehicle(
  id: number,
  options?: Partial<UseQueryOptions<ApiResponse<Vehicle>>>,
) {
  return useQuery<ApiResponse<Vehicle>>({
    queryKey: ['vehicles', id],
    queryFn: () => vehicleApi.get(id).then((r) => r.data),
    enabled: !!id,
    ...options,
  });
}
