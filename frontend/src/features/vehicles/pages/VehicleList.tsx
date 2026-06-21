import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useVehicles, useDeleteVehicle } from '../hooks/useVehicles';
import type { Vehicle, VehicleFilters } from '../types/vehicle';
import { VehicleCategory, VehicleStatus } from '../types/vehicle';
import { PageContainer } from '@/shared/components/ui/PageContainer';
import { DataTable } from '@/shared/components/ui/DataTable';
import { StatusBadge } from '@/shared/components/ui/StatusBadge';
import { ConfirmDialog } from '@/shared/components/ui/ConfirmDialog';
import { useAppShell } from '@/shared/layouts/appShellContext';
import { useDebounce } from '@/shared/hooks/useDebounce';
import type { ColumnDef, ActionDef } from '@/shared/types/table';

export function VehicleList() {
  const navigate = useNavigate();
  const { setPageTitle } = useAppShell();
  const [search, setSearch] = useState('');
  const [categoryFilter, setCategoryFilter] = useState<string>('');
  const [statusFilter, setStatusFilter] = useState<string>('');
  const [page, setPage] = useState(1);
  const [deleteId, setDeleteId] = useState<number | null>(null);
  const debouncedSearch = useDebounce(search, 300);

  const filters: VehicleFilters = {
    page,
    per_page: 15,
    search: debouncedSearch || undefined,
    category: (categoryFilter || undefined) as VehicleCategory | undefined,
    status: (statusFilter || undefined) as VehicleStatus | undefined,
  };

  const { data, isLoading, isError, refetch } = useVehicles(filters);
  const deleteMutation = useDeleteVehicle();

  useEffect(() => {
    setPageTitle('Vehicles');
  }, [setPageTitle]);

  const vehicles = data?.data ?? [];
  const pagination = data?.meta?.pagination;

  const columns: ColumnDef<Vehicle>[] = [
    { header: 'Plate Number', accessorKey: 'plate_number' },
    { header: 'Make', accessorKey: 'make' },
    { header: 'Model', accessorKey: 'model' },
    { header: 'Year', accessorKey: 'year' },
    {
      header: 'Category',
      accessorKey: 'category',
      cell: ({ getValue }) => {
        const val = getValue() as VehicleCategory;
        const label = val === VehicleCategory.DefencePlated ? 'Defence Plated' : 'Contracted Private';
        return <StatusBadge status={val} label={label} />;
      },
    },
    {
      header: 'Status',
      accessorKey: 'status',
      cell: ({ getValue }) => {
        const val = getValue() as VehicleStatus;
        return <StatusBadge status={val} />;
      },
    },
  ];

  const actions: ActionDef<Vehicle>[] = [
    {
      label: 'View',
      onClick: (row) => navigate(`/app/vehicles/${row.id}`),
      variant: 'primary',
    },
    {
      label: 'Edit',
      onClick: (row) => navigate(`/app/vehicles/${row.id}/edit`),
    },
    {
      label: 'Delete',
      onClick: (row) => setDeleteId(row.id),
      variant: 'danger',
    },
  ];

  async function handleDelete() {
    if (!deleteId) return;
    try {
      await deleteMutation.mutateAsync(deleteId);
    } finally {
      setDeleteId(null);
    }
  }

  return (
    <PageContainer
      title="Vehicles"
      description="Manage your fleet vehicles"
      actions={
        <button className="btn btn-primary" onClick={() => navigate('/app/vehicles/new')}>
          Add Vehicle
        </button>
      }
    >
      <div style={{ display: 'flex', gap: 'var(--space-2)', marginBottom: 'var(--space-4)' }}>
        <select
          className="form-input"
          value={categoryFilter}
          onChange={(e) => { setCategoryFilter(e.target.value); setPage(1); }}
          style={{ maxWidth: 200 }}
          aria-label="Filter by category"
        >
          <option value="">All Categories</option>
          <option value={VehicleCategory.DefencePlated}>Defence Plated</option>
          <option value={VehicleCategory.ContractedPrivate}>Contracted Private</option>
        </select>
        <select
          className="form-input"
          value={statusFilter}
          onChange={(e) => { setStatusFilter(e.target.value); setPage(1); }}
          style={{ maxWidth: 200 }}
          aria-label="Filter by status"
        >
          <option value="">All Statuses</option>
          <option value={VehicleStatus.Active}>Active</option>
          <option value={VehicleStatus.InMaintenance}>In Maintenance</option>
          <option value={VehicleStatus.Suspended}>Suspended</option>
          <option value={VehicleStatus.Decommissioned}>Decommissioned</option>
        </select>
      </div>

      <DataTable<Vehicle>
        data={vehicles}
        columns={columns}
        actions={actions}
        isLoading={isLoading}
        isError={isError}
        errorMessage="Failed to load vehicles"
        onRetry={() => refetch()}
        emptyTitle="No vehicles found"
        emptyDescription="Get started by adding your first vehicle."
        emptyActionLabel="Add Vehicle"
        emptyAction={() => navigate('/app/vehicles/new')}
        searchPlaceholder="Search vehicles..."
        onSearch={(q) => { setSearch(q); setPage(1); }}
        searchQuery={search}
        page={pagination?.current_page}
        pageCount={pagination?.last_page}
        total={pagination?.total}
        onPageChange={setPage}
      />

      <ConfirmDialog
        open={deleteId !== null}
        title="Delete Vehicle"
        message="Are you sure you want to delete this vehicle? This action cannot be undone."
        confirmLabel="Delete"
        onConfirm={handleDelete}
        onCancel={() => setDeleteId(null)}
        isLoading={deleteMutation.isPending}
      />
    </PageContainer>
  );
}
