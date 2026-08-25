import { renderHook, waitFor } from '@testing-library/react';
import { describe, test, expect, vi, beforeEach } from 'vitest';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import type { ReactNode } from 'react';
import { useNotificationPreferences, useUpdateNotificationPreferences } from '../hooks/useNotifications';

const mockAxiosClient = vi.hoisted(() => ({
  get: vi.fn(),
  patch: vi.fn(),
}));

vi.mock('@/shared/api/axiosClient', () => ({
  default: mockAxiosClient,
}));

const mockPreference = {
  id: 1,
  user_id: 1,
  preferences: {
    compliance_expiring: true,
    contract_expiring: true,
    maintenance_due: true,
    fuel_low_stock: true,
  },
  created_at: '2026-08-20T08:00:00Z',
  updated_at: '2026-08-20T08:00:00Z',
};

function createWrapper() {
  const qc = new QueryClient({ defaultOptions: { queries: { retry: false } } });
  return function Wrapper({ children }: { children: ReactNode }) {
    return <QueryClientProvider client={qc}>{children}</QueryClientProvider>;
  };
}

describe('useNotificationPreferences', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  test('returns preferences on success', async () => {
    mockAxiosClient.get.mockResolvedValue({ data: { success: true, data: mockPreference } });

    const { result } = renderHook(() => useNotificationPreferences(), {
      wrapper: createWrapper(),
    });

    await waitFor(() => expect(result.current.isSuccess).toBe(true));

    expect(result.current.data?.preferences.compliance_expiring).toBe(true);
    expect(result.current.data?.user_id).toBe(1);
  });

  test('returns error state on failure', async () => {
    mockAxiosClient.get.mockRejectedValue(new Error('Network error'));

    const { result } = renderHook(() => useNotificationPreferences(), {
      wrapper: createWrapper(),
    });

    await waitFor(() => expect(result.current.isError).toBe(true));

    expect(result.current.error).toBeTruthy();
  });
});

describe('useUpdateNotificationPreferences', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  test('sends the updated preferences and returns saved record', async () => {
    mockAxiosClient.patch.mockResolvedValue({
      data: { success: true, data: mockPreference, message: 'Notification preferences updated successfully.' },
    });

    const { result } = renderHook(() => useUpdateNotificationPreferences(), {
      wrapper: createWrapper(),
    });

    result.current.mutate(mockPreference.preferences);

    await waitFor(() => expect(result.current.isSuccess).toBe(true));

    expect(mockAxiosClient.patch).toHaveBeenCalledWith('/notifications/preferences', {
      preferences: mockPreference.preferences,
    });
    expect(result.current.data?.preferences.fuel_low_stock).toBe(true);
  });
});