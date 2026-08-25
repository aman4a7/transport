import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { describe, test, expect, vi, beforeEach } from 'vitest';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { MemoryRouter } from 'react-router-dom';
import { NotificationSettings } from '../pages/NotificationSettings';

const mutateAsync = vi.hoisted(() => vi.fn());

vi.mock('../hooks/useNotifications', () => ({
  useNotificationPreferences: vi.fn(),
  useUpdateNotificationPreferences: vi.fn(() => ({
    mutateAsync,
    isPending: false,
  })),
}));

vi.mock('@/shared/layouts/appShellContext', () => ({
  useAppShell: vi.fn(() => ({ setPageTitle: vi.fn() })),
}));

import { useNotificationPreferences } from '../hooks/useNotifications';

function renderWithProviders() {
  const qc = new QueryClient({ defaultOptions: { queries: { retry: false } } });
  return render(
    <QueryClientProvider client={qc}>
      <MemoryRouter>
        <NotificationSettings />
      </MemoryRouter>
    </QueryClientProvider>,
  );
}

const defaultPrefs = {
  compliance_expiring: true,
  contract_expiring: true,
  maintenance_due: true,
  fuel_low_stock: true,
};

describe('NotificationSettings', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mutateAsync.mockResolvedValue({ id: 1, user_id: 1, preferences: defaultPrefs });
  });

  test('renders page title and description', () => {
    vi.mocked(useNotificationPreferences).mockReturnValue({
      data: { id: 1, user_id: 1, preferences: defaultPrefs },
      isLoading: false,
      isError: false,
      refetch: vi.fn(),
    } as never);

    renderWithProviders();

    expect(screen.getByText('Notification Settings')).toBeTruthy();
    expect(
      screen.getByText('Choose which system notifications you want to receive'),
    ).toBeTruthy();
  });

  test('renders a toggle for each notification type', () => {
    vi.mocked(useNotificationPreferences).mockReturnValue({
      data: { id: 1, user_id: 1, preferences: defaultPrefs },
      isLoading: false,
      isError: false,
      refetch: vi.fn(),
    } as never);

    renderWithProviders();

    expect(screen.getByRole('checkbox', { name: /Compliance expiring/i })).toBeTruthy();
    expect(screen.getByRole('checkbox', { name: /Contract expiring/i })).toBeTruthy();
    expect(screen.getByRole('checkbox', { name: /Maintenance due/i })).toBeTruthy();
    expect(screen.getByRole('checkbox', { name: /Fuel low stock/i })).toBeTruthy();
  });

  test('shows loading state while fetching preferences', () => {
    vi.mocked(useNotificationPreferences).mockReturnValue({
      data: undefined,
      isLoading: true,
      isError: false,
      refetch: vi.fn(),
    } as never);

    renderWithProviders();

    expect(screen.getByText('Loading...')).toBeTruthy();
  });

  test('shows error state with retry on failure', () => {
    const refetch = vi.fn();
    vi.mocked(useNotificationPreferences).mockReturnValue({
      data: undefined,
      isLoading: false,
      isError: true,
      refetch,
    } as never);

    renderWithProviders();

    expect(screen.getByText('Failed to load notification preferences.')).toBeTruthy();
    fireEvent.click(screen.getByText('Retry'));
    expect(refetch).toHaveBeenCalled();
  });

  test('toggling a checkbox off and saving persists the change', async () => {
    vi.mocked(useNotificationPreferences).mockReturnValue({
      data: { id: 1, user_id: 1, preferences: defaultPrefs },
      isLoading: false,
      isError: false,
      refetch: vi.fn(),
    } as never);

    renderWithProviders();

    const fuelCheckbox = screen.getByRole('checkbox', { name: /Fuel low stock/i }) as HTMLInputElement;
    expect(fuelCheckbox.checked).toBe(true);

    fireEvent.click(fuelCheckbox);
    expect(fuelCheckbox.checked).toBe(false);

    fireEvent.click(screen.getByText('Save preferences'));

    await waitFor(() => {
      expect(mutateAsync).toHaveBeenCalledWith({
        compliance_expiring: true,
        contract_expiring: true,
        maintenance_due: true,
        fuel_low_stock: false,
      });
    });
  });

  test('shows success message after saving', async () => {
    vi.mocked(useNotificationPreferences).mockReturnValue({
      data: { id: 1, user_id: 1, preferences: defaultPrefs },
      isLoading: false,
      isError: false,
      refetch: vi.fn(),
    } as never);

    renderWithProviders();

    fireEvent.click(screen.getByText('Save preferences'));

    await waitFor(() => {
      expect(screen.getByText('Notification preferences saved successfully.')).toBeTruthy();
    });
  });

  test('shows error message when saving fails', async () => {
    mutateAsync.mockRejectedValue({
      response: { data: { message: 'Unsupported notification type.' } },
    });
    vi.mocked(useNotificationPreferences).mockReturnValue({
      data: { id: 1, user_id: 1, preferences: defaultPrefs },
      isLoading: false,
      isError: false,
      refetch: vi.fn(),
    } as never);

    renderWithProviders();

    fireEvent.click(screen.getByText('Save preferences'));

    await waitFor(() => {
      expect(screen.getByText('Unsupported notification type.')).toBeTruthy();
    });
  });
});