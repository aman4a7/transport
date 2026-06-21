import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useDrivers, useDeleteDriver } from '../hooks/useDrivers';
import type { Driver, DriverFilters } from '../types/driver';
import { DriverStatus } from '../types/driver';
import { PageContainer } from '@/shared/components/ui/PageContainer';
import { DataTable } from '@/shared/components/ui/DataTable';
import { StatusBadge } from '@/shared/components/ui/StatusBadge';
import { ConfirmDialog } from '@/shared/components/ui/ConfirmDialog';
import { useAppShell } from '@/shared/layouts/appShellContext';
import { useDebounce } from '@/shared/hooks/useDebounce';
import type { ColumnDef, ActionDef } from '@/shared/types/table';

export function DriverList() {
  const navigate = useNavigate();
  const { setPageTitle } = useAppShell();
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState<string>('');
  const [page, setPage] = useState(1);
  const [deleteId, setDeleteId] = useState<number | null>(null);
  const debouncedSearch = useDebounce(search, 300);

  const filters: DriverFilters = {
    page,
    per_page: 15,
    search: debouncedSearch || undefined,
    status: (statusFilter || undefined) as DriverStatus | undefined,
  };

  const { data, isLoading, isError, refetch } = useDrivers(filters);
  const deleteMutation = useDeleteDriver();

  useEffect(() => {
    setPageTitle('Drivers');
  }, [setPageTitle]);

  const drivers = data?.data ?? [];
  const pagination = data?.meta?.pagination;

  const columns: ColumnDef<Driver>[] = [
    { header: 'License Number', accessorKey: 'license_number' },
    { header: 'License Category', accessorKey: 'license_category' },
    {
      header: 'License Expiry',
      accessorKey: 'license_expiry',
    },
    { header: 'Assigned Vehicle', accessorKey: 'assigned_vehicle_id', cell: ({ row }) => row.original.assigned_vehicle?.plate_number ?? '-' },
    {
      header: 'Status',
      accessorKey: 'status',
      cell: ({ getValue }) => <StatusBadge status={getValue() as string} />,
    },
  ];

  const actions: ActionDef<Driver>[] = [
    {
      label: 'View',
      onClick: (row) => navigate(`/app/drivers/${row.id}`),
      variant: 'primary',
    },
    {
      label: 'Edit',
      onClick: (row) => navigate(`/app/drivers/${row.id}/edit`),
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
      title="Drivers"
      description="Manage driver records and licenses"
      actions={
        <button className="btn btn-primary" onClick={() => navigate('/app/drivers/new')}>
          Add Driver
        </button>
      }
    >
      <div style={{ display: 'flex', gap: 'var(--space-2)', marginBottom: 'var(--space-4)' }}>
        <select
          className="form-input"
          value={statusFilter}
          onChange={(e) => { setStatusFilter(e.target.value); setPage(1); }}
          style={{ maxWidth: 200 }}
          aria-label="Filter by status"
        >
          <option value="">All Statuses</option>
          <option value={DriverStatus.Active}>Active</option>
          <option value={DriverStatus.Suspended}>Suspended</option>
          <option value={DriverStatus.Expired}>Expired</option>
          <option value={DriverStatus.Inactive}>Inactive</option>
        </select>
      </div>

      <DataTable<Driver>
        data={drivers}
        columns={columns}
        actions={actions}
        isLoading={isLoading}
        isError={isError}
        errorMessage="Failed to load drivers"
        onRetry={() => refetch()}
        emptyTitle="No drivers found"
        emptyDescription="Get started by adding your first driver."
        emptyActionLabel="Add Driver"
        emptyAction={() => navigate('/app/drivers/new')}
        searchPlaceholder="Search drivers..."
        onSearch={(q) => { setSearch(q); setPage(1); }}
        searchQuery={search}
        page={pagination?.current_page}
        pageCount={pagination?.last_page}
        total={pagination?.total}
        onPageChange={setPage}
      />

      <ConfirmDialog
        open={deleteId !== null}
        title="Delete Driver"
        message="Are you sure you want to delete this driver? This action cannot be undone."
        confirmLabel="Delete"
        onConfirm={handleDelete}
        onCancel={() => setDeleteId(null)}
        isLoading={deleteMutation.isPending}
      />
    </PageContainer>
  );
}
