import { render, screen } from '@testing-library/react';
import { describe, test, expect, vi } from 'vitest';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { MemoryRouter } from 'react-router-dom';
import { NotificationCenter } from '../pages/NotificationCenter';

vi.mock('../hooks/useNotifications', () => ({
  useNotifications: vi.fn(),
  useMarkNotificationRead: vi.fn(() => ({ mutate: vi.fn(), isPending: false })),
  useMarkAllRead: vi.fn(() => ({ mutate: vi.fn(), isPending: false })),
}));

vi.mock('@/shared/layouts/appShellContext', () => ({
  useAppShell: vi.fn(() => ({ setPageTitle: vi.fn() })),
}));

import { useNotifications } from '../hooks/useNotifications';

function renderWithProviders(ui: React.ReactElement) {
  const qc = new QueryClient({ defaultOptions: { queries: { retry: false } } });
  return render(
    <QueryClientProvider client={qc}>
      <MemoryRouter>{ui}</MemoryRouter>
    </QueryClientProvider>,
  );
}

function mockData() {
  return {
    data: {
      success: true,
      data: [
        {
          id: 1,
          user_id: 1,
          type: 'compliance_expiring',
          title: 'Compliance expiring: DEF-001',
          body: 'The compliance document expires on 2026-09-01.',
          data: null,
          read_at: null,
          created_at: '2026-08-20T08:00:00Z',
        },
        {
          id: 2,
          user_id: 1,
          type: 'fuel_low_stock',
          title: 'Fuel stock low: DIESEL',
          body: 'Current stock is 100 L.',
          data: null,
          read_at: '2026-08-20T09:00:00Z',
          created_at: '2026-08-20T08:30:00Z',
        },
      ],
      meta: {
        pagination: { current_page: 1, total: 2, per_page: 15, last_page: 1 },
        unread_count: 1,
      },
    },
    isLoading: false,
    isError: false,
    refetch: vi.fn(),
  } as never;
}

describe('NotificationCenter', () => {
  test('renders page title and description', () => {
    vi.mocked(useNotifications).mockReturnValue(mockData());
    renderWithProviders(<NotificationCenter />);
    expect(screen.getByText('Notifications')).toBeTruthy();
    expect(
      screen.getByText(
        'Review system notifications about compliance, contracts, maintenance, and fuel stock',
      ),
    ).toBeTruthy();
  });

  test('renders status filter dropdown and mark all read button', () => {
    vi.mocked(useNotifications).mockReturnValue(mockData());
    renderWithProviders(<NotificationCenter />);
    expect(screen.getByLabelText('Filter by status')).toBeTruthy();
    expect(screen.getByText('Mark all read')).toBeTruthy();
  });

  test('shows mark-as-read action only for unread notifications', () => {
    vi.mocked(useNotifications).mockReturnValue(mockData());
    renderWithProviders(<NotificationCenter />);
    expect(screen.getAllByLabelText('Mark as read')).toHaveLength(1);
    expect(screen.getByText('Compliance expiring: DEF-001')).toBeTruthy();
    expect(screen.getByText('Fuel stock low: DIESEL')).toBeTruthy();
  });
});