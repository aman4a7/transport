import { render, screen } from '@testing-library/react';
import { describe, test, expect, vi } from 'vitest';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { MemoryRouter } from 'react-router-dom';
import { FuelStock } from '../pages/FuelStock';

vi.mock('../hooks/useFuel', () => ({
  useFuelStock: vi.fn(),
  useRestockFuel: vi.fn(() => ({ mutateAsync: vi.fn(), isPending: false })),
  useAdjustFuel: vi.fn(() => ({ mutateAsync: vi.fn(), isPending: false })),
}));

vi.mock('@/shared/layouts/appShellContext', () => ({
  useAppShell: vi.fn(() => ({ setPageTitle: vi.fn() })),
}));

import { useFuelStock } from '../hooks/useFuel';

function renderWithProviders(ui: React.ReactElement) {
  const qc = new QueryClient({ defaultOptions: { queries: { retry: false } } });
  return render(
    <QueryClientProvider client={qc}>
      <MemoryRouter>{ui}</MemoryRouter>
    </QueryClientProvider>,
  );
}

function mockStocks(stocks: unknown[]) {
  return {
    data: stocks,
    isLoading: false,
  } as never;
}

describe('FuelStock', () => {
  test('shows low stock warning when current quantity is below minimum', () => {
    vi.mocked(useFuelStock).mockReturnValue(
      mockStocks([
        { id: 1, fuel_type: 'diesel', current_quantity: 50, minimum_quantity: 100, last_restocked_at: null, notes: null },
      ]),
    );
    renderWithProviders(<FuelStock />);
    expect(screen.getByText('Below minimum')).toBeTruthy();
  });

  test('does not show low stock warning when current quantity is at or above minimum', () => {
    vi.mocked(useFuelStock).mockReturnValue(
      mockStocks([
        { id: 1, fuel_type: 'diesel', current_quantity: 150, minimum_quantity: 100, last_restocked_at: null, notes: null },
      ]),
    );
    renderWithProviders(<FuelStock />);
    expect(screen.queryByText('Below minimum')).toBeNull();
  });

  test('renders numeric quantities without crashing', () => {
    vi.mocked(useFuelStock).mockReturnValue(
      mockStocks([
        { id: 1, fuel_type: 'diesel', current_quantity: 50.5, minimum_quantity: 100, last_restocked_at: null, notes: null },
      ]),
    );
    renderWithProviders(<FuelStock />);
    expect(screen.getByText('50.5 L')).toBeTruthy();
    expect(screen.getByText('Min: 100.0 L')).toBeTruthy();
  });

  test('renders empty state when no stock data', () => {
    vi.mocked(useFuelStock).mockReturnValue(mockStocks([]));
    renderWithProviders(<FuelStock />);
    expect(screen.getByText('No stock data')).toBeTruthy();
  });
});