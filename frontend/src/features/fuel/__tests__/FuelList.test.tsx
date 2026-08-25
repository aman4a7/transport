import { render, screen } from '@testing-library/react';
import { describe, test, expect, vi } from 'vitest';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { MemoryRouter } from 'react-router-dom';
import { FuelList } from '../pages/FuelList';

vi.mock('../hooks/useFuel', () => ({
  useFuelTransactions: vi.fn(),
}));

vi.mock('@/shared/layouts/appShellContext', () => ({
  useAppShell: vi.fn(() => ({ setPageTitle: vi.fn() })),
}));

import { useFuelTransactions } from '../hooks/useFuel';

function renderWithProviders(ui: React.ReactElement) {
  const qc = new QueryClient({ defaultOptions: { queries: { retry: false } } });
  return render(
    <QueryClientProvider client={qc}>
      <MemoryRouter>{ui}</MemoryRouter>
    </QueryClientProvider>,
  );
}

function mockEmpty() {
  return {
    data: { success: true, data: [], meta: { pagination: { current_page: 1, total: 0, per_page: 15, last_page: 1 } } },
    isLoading: false,
    isError: false,
    refetch: vi.fn(),
  } as never;
}

describe('FuelList', () => {
  test('renders page title and description', () => {
    vi.mocked(useFuelTransactions).mockReturnValue(mockEmpty());
    renderWithProviders(<FuelList />);
    expect(screen.getByText('Fuel Transactions')).toBeTruthy();
    expect(screen.getByText('Track fuel issuance, restocking, and adjustments')).toBeTruthy();
  });

  test('renders type filter dropdown', () => {
    vi.mocked(useFuelTransactions).mockReturnValue(mockEmpty());
    renderWithProviders(<FuelList />);
    expect(screen.getByLabelText('Filter by type')).toBeTruthy();
  });

  test('renders action buttons', () => {
    vi.mocked(useFuelTransactions).mockReturnValue(mockEmpty());
    renderWithProviders(<FuelList />);
    expect(screen.getAllByText('Issue Fuel').length).toBeGreaterThanOrEqual(1);
    expect(screen.getAllByText('Restock').length).toBeGreaterThanOrEqual(1);
    expect(screen.getAllByText('Stock Levels').length).toBeGreaterThanOrEqual(1);
  });
});
