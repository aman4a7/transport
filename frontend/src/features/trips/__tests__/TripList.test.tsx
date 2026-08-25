import { render, screen } from '@testing-library/react';
import { describe, test, expect, vi } from 'vitest';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { MemoryRouter } from 'react-router-dom';
import { TripList } from '../pages/TripList';

const mockTrips = [
  { id: 1, route_id: 1, vehicle_id: 1, driver_id: 1, scheduled_date: '2026-07-01', departure_time: '08:00', estimated_arrival_time: '12:00', status: 'scheduled', notes: null, route: { id: 1, name: 'Addis - Bahir Dar' }, vehicle: { id: 1, plate_number: 'DEF-001' }, driver: { id: 1 }, assignments: [], created_at: '', updated_at: '' },
  { id: 2, route_id: 2, vehicle_id: 2, driver_id: 2, scheduled_date: '2026-07-02', departure_time: '09:00', estimated_arrival_time: '13:00', status: 'in_progress', notes: null, route: { id: 2, name: 'Addis - Gondar' }, vehicle: { id: 2, plate_number: 'DEF-002' }, driver: { id: 2 }, assignments: [], created_at: '', updated_at: '' },
];

vi.mock('../hooks/useTrips', () => ({
  useTrips: vi.fn(),
  useDeleteTrip: vi.fn(() => ({
    mutateAsync: vi.fn(),
    isPending: false,
  })),
  useStartTrip: vi.fn(() => ({
    mutate: vi.fn(),
    isPending: false,
  })),
  useCompleteTrip: vi.fn(() => ({
    mutate: vi.fn(),
    isPending: false,
  })),
  useCancelTrip: vi.fn(() => ({
    mutate: vi.fn(),
    isPending: false,
  })),
}));

vi.mock('@/shared/layouts/appShellContext', () => ({
  useAppShell: vi.fn(() => ({ setPageTitle: vi.fn() })),
}));

import { useTrips } from '../hooks/useTrips';

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
    data: { success: true, data: mockTrips, meta: { pagination: { current_page: 1, total: 2, per_page: 15, last_page: 1 } } },
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

describe('TripList', () => {
  test('renders page title and description', () => {
    vi.mocked(useTrips).mockReturnValue(mockWithEmpty());
    renderWithProviders(<TripList />);
    expect(screen.getByText('Trips')).toBeTruthy();
    expect(screen.getByText('Manage transport trips')).toBeTruthy();
  });

  test('renders status filter dropdown', () => {
    vi.mocked(useTrips).mockReturnValue(mockWithData());
    renderWithProviders(<TripList />);
    expect(screen.getByLabelText('Filter by status')).toBeTruthy();
  });

  test('renders Schedule Trip buttons', () => {
    vi.mocked(useTrips).mockReturnValue(mockWithEmpty());
    renderWithProviders(<TripList />);
    const addButtons = screen.getAllByText('Schedule Trip');
    expect(addButtons.length).toBeGreaterThanOrEqual(1);
  });
});
