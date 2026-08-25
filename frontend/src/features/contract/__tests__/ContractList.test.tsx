import { render, screen } from '@testing-library/react';
import { describe, test, expect, vi } from 'vitest';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { MemoryRouter } from 'react-router-dom';
import { ContractList } from '../pages/ContractList';

vi.mock('../hooks/useContracts', () => ({
  useContracts: vi.fn(),
  useDeleteContract: vi.fn(() => ({ mutate: vi.fn(), isPending: false })),
  useActivateContract: vi.fn(() => ({ mutate: vi.fn(), isPending: false })),
  useTerminateContract: vi.fn(() => ({ mutate: vi.fn(), isPending: false })),
}));

vi.mock('@/shared/layouts/appShellContext', () => ({
  useAppShell: vi.fn(() => ({ setPageTitle: vi.fn() })),
}));

import { useContracts } from '../hooks/useContracts';

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

describe('ContractList', () => {
  test('renders page title and description', () => {
    vi.mocked(useContracts).mockReturnValue(mockEmpty());
    renderWithProviders(<ContractList />);
    expect(screen.getByText('Contracts')).toBeTruthy();
    expect(screen.getByText('Manage contractor vehicle service agreements')).toBeTruthy();
  });

  test('renders status filter dropdown', () => {
    vi.mocked(useContracts).mockReturnValue(mockEmpty());
    renderWithProviders(<ContractList />);
    expect(screen.getByLabelText('Filter by status')).toBeTruthy();
  });

  test('renders new contract button', () => {
    vi.mocked(useContracts).mockReturnValue(mockEmpty());
    renderWithProviders(<ContractList />);
    expect(screen.getAllByText('New Contract').length).toBeGreaterThanOrEqual(1);
  });
});
