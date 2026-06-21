import { render, screen } from '@testing-library/react';
import { describe, test, expect, vi } from 'vitest';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { MemoryRouter } from 'react-router-dom';
import { VehicleList } from '../pages/VehicleList';

const mockVehicles = [
  { id: 1, plate_number: 'DEF-001', make: 'Toyota', model: 'Hiace', year: 2023, category: 'defence_plated', status: 'active', fuel_type: 'diesel', color: null, vin: null, engine_number: null, seating_capacity: null, owner_id: null, registration_expiry: null, insurance_expiry: null, created_at: '', updated_at: '', owner: null },
  { id: 2, plate_number: 'PRV-001', make: 'Isuzu', model: 'Fuso', year: 2022, category: 'contracted_private', status: 'active', fuel_type: 'diesel', color: null, vin: null, engine_number: null, seating_capacity: null, owner_id: 1, registration_expiry: null, insurance_expiry: null, created_at: '', updated_at: '', owner: { id: 1, company_name: 'Test Owner' } },
];

vi.mock('../hooks/useVehicles', () => ({
  useVehicles: vi.fn(),
  useDeleteVehicle: vi.fn(() => ({
    mutateAsync: vi.fn(),
    isPending: false,
  })),
}));

vi.mock('@/shared/layouts/appShellContext', () => ({
  useAppShell: vi.fn(() => ({ setPageTitle: vi.fn() })),
}));

import { useVehicles } from '../hooks/useVehicles';

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
    data: { success: true, data: mockVehicles, meta: { pagination: { current_page: 1, total: 2, per_page: 15, last_page: 1 } } },
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

describe('VehicleList', () => {
  test('renders page title and description', () => {
    vi.mocked(useVehicles).mockReturnValue(mockWithEmpty());
    renderWithProviders(<VehicleList />);
    expect(screen.getByText('Vehicles')).toBeTruthy();
    expect(screen.getByText('Manage your fleet vehicles')).toBeTruthy();
  });

  test('renders search input and filter dropdowns when data is present', () => {
    vi.mocked(useVehicles).mockReturnValue(mockWithData());
    renderWithProviders(<VehicleList />);
    expect(screen.getByPlaceholderText('Search vehicles...')).toBeTruthy();
    expect(screen.getByLabelText('Filter by category')).toBeTruthy();
    expect(screen.getByLabelText('Filter by status')).toBeTruthy();
  });

  test('renders Add Vehicle buttons', () => {
    vi.mocked(useVehicles).mockReturnValue(mockWithEmpty());
    renderWithProviders(<VehicleList />);
    const addButtons = screen.getAllByText('Add Vehicle');
    expect(addButtons.length).toBeGreaterThanOrEqual(1);
  });

  test('shows category filter options', () => {
    vi.mocked(useVehicles).mockReturnValue(mockWithEmpty());
    renderWithProviders(<VehicleList />);
    const categoryFilter = screen.getByLabelText('Filter by category');
    expect(categoryFilter.querySelector('option[value="defence_plated"]')).toBeTruthy();
    expect(categoryFilter.querySelector('option[value="contracted_private"]')).toBeTruthy();
  });
});
