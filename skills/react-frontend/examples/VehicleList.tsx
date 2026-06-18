import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import {
  useReactTable,
  getCoreRowModel,
  getSortedRowModel,
  type ColumnDef,
  type SortingState,
  flexRender,
} from '@tanstack/react-table';
import { useVehicles } from '../hooks/useVehicles';
import type { Vehicle, VehicleFilters } from '../types/vehicle';
import { VehicleCategory, VehicleStatus } from '../types/vehicle';
import { StatusBadge } from '@/shared/components/ui/StatusBadge';
import { LoadingSpinner } from '@/shared/components/ui/LoadingSpinner';
import { EmptyState } from '@/shared/components/ui/EmptyState';
import { useDebounce } from '@/shared/hooks/useDebounce';

/**
 * EXAMPLE: Vehicle list page using the react-frontend list-page template.
 *
 * Demonstrates:
 * - TanStack Table with Vehicle-specific columns
 * - Category and status filter dropdowns
 * - Defence-plated / contracted-private category badges
 * - Status badge with text + icon (accessible)
 * - All four UI states
 */
export function VehicleList() {
  const navigate = useNavigate();

  // --- Filters ---
  const [search, setSearch] = useState('');
  const debouncedSearch = useDebounce(search, 300);
  const [filters, setFilters] = useState<VehicleFilters>({
    page: 1,
    per_page: 15,
  });

  const activeFilters: VehicleFilters = {
    ...filters,
    search: debouncedSearch,
  };

  // --- Data ---
  const { data, isLoading, isError, error, refetch } = useVehicles(activeFilters);

  // --- Table ---
  const [sorting, setSorting] = useState<SortingState>([]);

  const columns: ColumnDef<Vehicle>[] = [
    {
      accessorKey: 'plate_number',
      header: 'Plate Number',
    },
    {
      accessorKey: 'make',
      header: 'Make',
    },
    {
      accessorKey: 'model',
      header: 'Model',
    },
    {
      accessorKey: 'year',
      header: 'Year',
    },
    {
      accessorKey: 'category',
      header: 'Category',
      cell: ({ getValue }) => {
        const category = getValue<string>();
        return (
          <span className={`category-tag category-${category}`}>
            {category === VehicleCategory.DefencePlated
              ? 'Defence Plated'
              : 'Contracted Private'}
          </span>
        );
      },
    },
    {
      accessorKey: 'status',
      header: 'Status',
      cell: ({ getValue }) => <StatusBadge status={getValue<string>()} />,
    },
  ];

  const table = useReactTable({
    data: data?.data ?? [],
    columns,
    state: { sorting },
    onSortingChange: setSorting,
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    manualPagination: true,
    pageCount: data?.meta.pagination.last_page ?? -1,
  });

  // --- Render ---
  return (
    <div className="vehicle-list-page">
      {/* Page Header */}
      <div className="page-header">
        <h1>Vehicles</h1>
        <button
          className="btn btn-primary"
          onClick={() => navigate('/app/vehicles/new')}
        >
          Add Vehicle
        </button>
      </div>

      {/* Search and Filters */}
      <div className="filters-bar">
        <input
          type="search"
          placeholder="Search by plate number, make, or model..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          className="search-input"
          aria-label="Search vehicles"
        />

        <select
          value={filters.category ?? ''}
          onChange={(e) =>
            setFilters((f) => ({
              ...f,
              category: e.target.value || undefined,
              page: 1,
            }))
          }
          aria-label="Filter by category"
        >
          <option value="">All Categories</option>
          <option value={VehicleCategory.DefencePlated}>Defence Plated</option>
          <option value={VehicleCategory.ContractedPrivate}>Contracted Private</option>
        </select>

        <select
          value={filters.status ?? ''}
          onChange={(e) =>
            setFilters((f) => ({
              ...f,
              status: e.target.value || undefined,
              page: 1,
            }))
          }
          aria-label="Filter by status"
        >
          <option value="">All Statuses</option>
          <option value={VehicleStatus.Active}>Active</option>
          <option value={VehicleStatus.InMaintenance}>In Maintenance</option>
          <option value={VehicleStatus.Suspended}>Suspended</option>
          <option value={VehicleStatus.Decommissioned}>Decommissioned</option>
        </select>
      </div>

      {/* Data States */}
      {isLoading && <LoadingSpinner />}

      {isError && (
        <div className="error-state" role="alert">
          <p>Failed to load vehicles: {error?.message}</p>
          <button onClick={() => refetch()} className="btn btn-secondary">
            Retry
          </button>
        </div>
      )}

      {!isLoading && !isError && data?.data.length === 0 && (
        <EmptyState
          title="No vehicles found"
          description="Get started by registering the first vehicle in the fleet."
          actionLabel="Add Vehicle"
          onAction={() => navigate('/app/vehicles/new')}
        />
      )}

      {!isLoading && !isError && data && data.data.length > 0 && (
        <>
          <div className="table-container">
            <table className="data-table">
              <thead>
                {table.getHeaderGroups().map((headerGroup) => (
                  <tr key={headerGroup.id}>
                    {headerGroup.headers.map((header) => (
                      <th
                        key={header.id}
                        scope="col"
                        onClick={header.column.getToggleSortingHandler()}
                        className={header.column.getCanSort() ? 'sortable' : ''}
                        aria-sort={
                          header.column.getIsSorted() === 'asc'
                            ? 'ascending'
                            : header.column.getIsSorted() === 'desc'
                              ? 'descending'
                              : 'none'
                        }
                      >
                        {flexRender(header.column.columnDef.header, header.getContext())}
                      </th>
                    ))}
                    <th scope="col">Actions</th>
                  </tr>
                ))}
              </thead>
              <tbody>
                {table.getRowModel().rows.map((row) => (
                  <tr key={row.id}>
                    {row.getVisibleCells().map((cell) => (
                      <td key={cell.id}>
                        {flexRender(cell.column.columnDef.cell, cell.getContext())}
                      </td>
                    ))}
                    <td>
                      <div className="row-actions">
                        <button
                          onClick={() => navigate(`/app/vehicles/${row.original.id}`)}
                          className="btn btn-sm"
                          aria-label={`View vehicle ${row.original.plate_number}`}
                        >
                          View
                        </button>
                        <button
                          onClick={() => navigate(`/app/vehicles/${row.original.id}/edit`)}
                          className="btn btn-sm"
                          aria-label={`Edit vehicle ${row.original.plate_number}`}
                        >
                          Edit
                        </button>
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          {/* Pagination */}
          <div className="pagination-controls">
            <button
              onClick={() => setFilters((f) => ({ ...f, page: (f.page ?? 1) - 1 }))}
              disabled={data.meta.pagination.current_page <= 1}
            >
              Previous
            </button>
            <span>
              Page {data.meta.pagination.current_page} of {data.meta.pagination.last_page}
            </span>
            <button
              onClick={() => setFilters((f) => ({ ...f, page: (f.page ?? 1) + 1 }))}
              disabled={data.meta.pagination.current_page >= data.meta.pagination.last_page}
            >
              Next
            </button>
          </div>
        </>
      )}
    </div>
  );
}
