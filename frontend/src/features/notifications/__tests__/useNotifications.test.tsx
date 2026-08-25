import { renderHook, waitFor } from '@testing-library/react';
import { describe, test, expect, vi, beforeEach } from 'vitest';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import type { ReactNode } from 'react';
import { useNotifications } from '../hooks/useNotifications';

const mockAxiosClient = vi.hoisted(() => ({
  get: vi.fn(),
  patch: vi.fn(),
}));

vi.mock('@/shared/api/axiosClient', () => ({
  default: mockAxiosClient,
}));

const mockData = {
  success: true,
  data: [
    {
      id: 1,
      user_id: 1,
      type: 'compliance_expiring',
      title: 'Compliance expiring: DEF-001',
      body: 'Expires soon.',
      data: { entity_type: 'compliance_document', entity_id: 1 },
      read_at: null,
      created_at: '2026-08-20T08:00:00Z',
    },
  ],
  meta: {
    pagination: { current_page: 1, total: 1, per_page: 15, last_page: 1 },
    unread_count: 1,
  },
};

function createWrapper() {
  const qc = new QueryClient({ defaultOptions: { queries: { retry: false } } });
  return function Wrapper({ children }: { children: ReactNode }) {
    return <QueryClientProvider client={qc}>{children}</QueryClientProvider>;
  };
}

describe('useNotifications', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  test('returns notifications and unread count on success', async () => {
    mockAxiosClient.get.mockResolvedValue({ data: mockData });

    const { result } = renderHook(() => useNotifications({ page: 1, per_page: 15 }), {
      wrapper: createWrapper(),
    });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));

    expect(result.current.data?.success).toBe(true);
    expect(result.current.data?.data).toHaveLength(1);
    expect(result.current.data?.data[0].type).toBe('compliance_expiring');
    expect(result.current.data?.meta.unread_count).toBe(1);
  });

  test('returns error state on failure', async () => {
    mockAxiosClient.get.mockRejectedValue(new Error('Network error'));

    const { result } = renderHook(() => useNotifications({ page: 1, per_page: 15 }), {
      wrapper: createWrapper(),
    });

    await waitFor(() => expect(result.current.isError).toBe(true));

    expect(result.current.error).toBeTruthy();
  });
});