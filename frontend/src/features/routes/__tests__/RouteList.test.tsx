import { render, screen } from '@testing-library/react';
import { describe, test, expect, vi } from 'vitest';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { MemoryRouter } from 'react-router-dom';
import { RouteList } from '../pages/RouteList';

const mockRoutes = [
  { id: 1, name: 'Addis - Bahir Dar', code: 'RTE-001', origin: 'Addis Ababa', destination: 'Bahir Dar', distance_km: 560.5, estimated_duration_minutes: 420, capacity: 40, status: 'active', description: null, created_at: '', updated_at: '' },
  { id: 2, name: 'Addis - Gondar', code: 'RTE-002', origin: 'Addis Ababa', destination: 'Gondar', distance_km: 730.2, estimated_duration_minutes: 540, capacity: 35, status: 'active', description: null, created_at: '', updated_at: '' },
];

vi.mock('../hooks/useRoutes', () => ({
  useRoutes: vi.fn(),
  useDeleteRoute: vi.fn(() => ({
    mutateAsync: vi.fn(),
    isPending: false,
  })),
}));

vi.mock('@/shared/layouts/appShellContext', () => ({
  useAppShell: vi.fn(() => ({ setPageTitle: vi.fn() })),
}));

import { useRoutes } from '../hooks/useRoutes';

function renderWithProviders(ui: React.ReactElement) {
  const qc = new QueryClient({ defaultOptions: { queries: { retry: false } } });
  return render(
    <QueryClientProvider client={qc}>
      <MemoryRouter>{ui}</MemoryRouter>
    </QueryClientProvider>,
  );
}

function mockWithData() {
  return {
    data: { success: true, data: mockRoutes, meta: { pagination: { current_page: 1, total: 2, per_page: 15, last_page: 1 } } },
    isLoading: false,
    isError: false,
    refetch: vi.fn(),
  } as never;
}

function mockWithEmpty() {
  return {
    data: { success: true, data: [], meta: { pagination: { current_page: 1, total: 0, per_page: 15, last_page: 1 } } },
    isLoading: false,
    isError: false,
    refetch: vi.fn(),
  } as never;
}

describe('RouteList', () => {
  test('renders page title and description', () => {
    vi.mocked(useRoutes).mockReturnValue(mockWithEmpty());
    renderWithProviders(<RouteList />);
    expect(screen.getByText('Routes')).toBeTruthy();
    expect(screen.getByText('Manage transport routes')).toBeTruthy();
  });

  test('renders search input and status filter when data is present', () => {
    vi.mocked(useRoutes).mockReturnValue(mockWithData());
    renderWithProviders(<RouteList />);
    expect(screen.getByPlaceholderText('Search routes...')).toBeTruthy();
    expect(screen.getByLabelText('Filter by status')).toBeTruthy();
  });

  test('renders Add Route buttons', () => {
    vi.mocked(useRoutes).mockReturnValue(mockWithEmpty());
    renderWithProviders(<RouteList />);
    const addButtons = screen.getAllByText('Add Route');
    expect(addButtons.length).toBeGreaterThanOrEqual(1);
  });

  test('renders distance with one decimal place', () => {
    vi.mocked(useRoutes).mockReturnValue(mockWithData());
    renderWithProviders(<RouteList />);
    expect(screen.getByText('560.5 km')).toBeTruthy();
    expect(screen.getByText('730.2 km')).toBeTruthy();
  });
});
