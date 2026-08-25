import { render, screen } from '@testing-library/react';
import { describe, test, expect, vi } from 'vitest';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { MemoryRouter } from 'react-router-dom';
import { GarageList } from '../pages/GarageList';

vi.mock('../hooks/useGarage', () => ({
  useMaintenanceRecords: vi.fn(),
  useDeleteMaintenanceRecord: vi.fn(() => ({ mutate: vi.fn(), isPending: false })),
  useStartMaintenance: vi.fn(() => ({ mutate: vi.fn(), isPending: false })),
  useCompleteMaintenance: vi.fn(() => ({ mutate: vi.fn(), isPending: false })),
  useCancelMaintenance: vi.fn(() => ({ mutate: vi.fn(), isPending: false })),
}));

vi.mock('@/shared/layouts/appShellContext', () => ({
  useAppShell: vi.fn(() => ({ setPageTitle: vi.fn() })),
}));

import { useMaintenanceRecords } from '../hooks/useGarage';

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

function mockWithRecords(records: unknown[]) {
  return {
    data: { success: true, data: records, meta: { pagination: { current_page: 1, total: records.length, per_page: 15, last_page: 1 } } },
    isLoading: false,
    isError: false,
    refetch: vi.fn(),
  } as never;
}

describe('GarageList', () => {
  test('renders page title and description', () => {
    vi.mocked(useMaintenanceRecords).mockReturnValue(mockEmpty());
    renderWithProviders(<GarageList />);
    expect(screen.getByText('Garage')).toBeTruthy();
    expect(screen.getByText('Manage vehicle maintenance, repairs, and service history')).toBeTruthy();
  });

  test('renders status filter dropdown', () => {
    vi.mocked(useMaintenanceRecords).mockReturnValue(mockEmpty());
    renderWithProviders(<GarageList />);
    expect(screen.getByLabelText('Filter by status')).toBeTruthy();
  });

  test('renders type filter dropdown', () => {
    vi.mocked(useMaintenanceRecords).mockReturnValue(mockEmpty());
    renderWithProviders(<GarageList />);
    expect(screen.getByLabelText('Filter by type')).toBeTruthy();
  });

  test('renders new record button', () => {
    vi.mocked(useMaintenanceRecords).mockReturnValue(mockEmpty());
    renderWithProviders(<GarageList />);
    expect(screen.getAllByText('New Record').length).toBeGreaterThanOrEqual(1);
  });

  test('renders cost formatted from string values', () => {
    vi.mocked(useMaintenanceRecords).mockReturnValue(
      mockWithRecords([
        {
          id: 1,
          vehicle_id: 1,
          maintenance_type: 'repair',
          status: 'completed',
          description: 'Brake replacement',
          scheduled_date: '2026-08-01',
          started_at: null,
          completed_at: null,
          cost: '750.00',
          notes: null,
          performed_by: null,
          created_at: '',
          updated_at: '',
          vehicle: { id: 1, plate_number: 'DEF-001' },
        },
      ]),
    );
    renderWithProviders(<GarageList />);
    expect(screen.getByText('ETB 750.00')).toBeTruthy();
  });
});
