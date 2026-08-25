import { renderHook, waitFor } from '@testing-library/react';
import { describe, test, expect, vi, beforeEach } from 'vitest';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import type { ReactNode } from 'react';
import { useMaintenanceRecords } from '../hooks/useGarage';

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
    { id: 1, vehicle_id: 1, maintenance_type: 'scheduled', status: 'pending', description: 'Oil change', scheduled_date: '2026-07-15', cost: null, notes: null, created_at: '2026-07-01T08:00:00Z', vehicle: { id: 1, plate_number: 'DEF-001' } },
  ],
  meta: { pagination: { current_page: 1, total: 1, per_page: 15, last_page: 1 } },
};

function createWrapper() {
  const qc = new QueryClient({ defaultOptions: { queries: { retry: false } } });
  return function Wrapper({ children }: { children: ReactNode }) {
    return <QueryClientProvider client={qc}>{children}</QueryClientProvider>;
  };
}

describe('useMaintenanceRecords', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  test('returns expected shape on success', async () => {
    mockAxiosClient.get.mockResolvedValue({ data: mockData });

    const { result } = renderHook(
      () => useMaintenanceRecords({ page: 1, per_page: 15 }),
      { wrapper: createWrapper() },
    );

    await waitFor(() => expect(result.current.isSuccess).toBe(true));

    expect(result.current.data?.success).toBe(true);
    expect(result.current.data?.data).toHaveLength(1);
    expect(result.current.data?.data[0].maintenance_type).toBe('scheduled');
  });

  test('returns error state on failure', async () => {
    mockAxiosClient.get.mockRejectedValue(new Error('Network error'));

    const { result } = renderHook(
      () => useMaintenanceRecords({ page: 1, per_page: 15 }),
      { wrapper: createWrapper() },
    );

    await waitFor(() => expect(result.current.isError).toBe(true));

    expect(result.current.error).toBeTruthy();
  });
});
