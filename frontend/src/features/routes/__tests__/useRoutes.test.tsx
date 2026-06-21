import { renderHook, waitFor } from '@testing-library/react';
import { describe, test, expect, vi, beforeEach } from 'vitest';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import type { ReactNode } from 'react';
import { useRoutes } from '../hooks/useRoutes';

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
    { id: 1, name: 'Addis - Bahir Dar', code: 'RTE-001', origin: 'Addis Ababa', destination: 'Bahir Dar', distance_km: 560.5, estimated_duration_minutes: 420, capacity: 40, status: 'active', description: null, created_at: '', updated_at: '' },
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

describe('useRoutes', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  test('returns expected shape on success', async () => {
    mockAxiosClient.get.mockResolvedValue({ data: mockData });

    const { result } = renderHook(
      () => useRoutes({ page: 1, per_page: 15 }),
      { wrapper: createWrapper() },
    );

    await waitFor(() => expect(result.current.isSuccess).toBe(true));

    expect(result.current.data?.success).toBe(true);
    expect(result.current.data?.data).toHaveLength(1);
    expect(result.current.data?.data[0].name).toBe('Addis - Bahir Dar');
    expect(result.current.data?.meta.pagination.total).toBe(1);
  });

  test('returns error state on failure', async () => {
    mockAxiosClient.get.mockRejectedValue(new Error('Network error'));

    const { result } = renderHook(
      () => useRoutes({ page: 1, per_page: 15 }),
      { wrapper: createWrapper() },
    );

    await waitFor(() => expect(result.current.isError).toBe(true));

    expect(result.current.error).toBeTruthy();
  });
});
