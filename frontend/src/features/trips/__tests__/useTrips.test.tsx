import { renderHook, waitFor } from '@testing-library/react';
import { describe, test, expect, vi, beforeEach } from 'vitest';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import type { ReactNode } from 'react';
import { useTrips } from '../hooks/useTrips';

const mockAxiosClient = vi.hoisted(() => ({
  get: vi.fn(),
  post: vi.fn(),
  put: vi.fn(),
  delete: vi.fn(),
}));

vi.mock('@/shared/api/axiosClient', () => ({
  default: mockAxiosClient,
}));

const mockData = {
  success: true,
  data: [
    { id: 1, route_id: 1, vehicle_id: 1, driver_id: 1, scheduled_date: '2026-07-01', departure_time: '08:00', estimated_arrival_time: '12:00', status: 'scheduled', notes: null, route: { id: 1, name: 'Addis - Bahir Dar' }, vehicle: { id: 1, plate_number: 'DEF-001' }, driver: { id: 1 }, assignments: [], created_at: '', updated_at: '' },
  ],
  meta: { pagination: { current_page: 1, total: 1, per_page: 15, last_page: 1 } },
};

function createWrapper() {
  const qc = new QueryClient({
    defaultOptions: {
      queries: { retry: false },
    },
  });
  return function Wrapper({ children }: { children: ReactNode }) {
    return <QueryClientProvider client={qc}>{children}</QueryClientProvider>;
  };
}

describe('useTrips', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  test('returns expected shape on success', async () => {
    mockAxiosClient.get.mockResolvedValue({ data: mockData });

    const { result } = renderHook(
      () => useTrips({ page: 1, per_page: 15 }),
      { wrapper: createWrapper() },
    );

    await waitFor(() => expect(result.current.isSuccess).toBe(true));

    expect(result.current.data?.success).toBe(true);
    expect(result.current.data?.data).toHaveLength(1);
    expect(result.current.data?.data[0].route?.name).toBe('Addis - Bahir Dar');
    expect(result.current.data?.meta.pagination.total).toBe(1);
  });

  test('returns error state on failure', async () => {
    mockAxiosClient.get.mockRejectedValue(new Error('Network error'));

    const { result } = renderHook(
      () => useTrips({ page: 1, per_page: 15 }),
      { wrapper: createWrapper() },
    );

    await waitFor(() => expect(result.current.isError).toBe(true));

    expect(result.current.error).toBeTruthy();
  });
});
