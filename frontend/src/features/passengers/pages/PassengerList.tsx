import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { usePassengers, useDeletePassenger } from '../hooks/usePassengers';
import type { Passenger, PassengerFilters } from '../types/passenger';
import { PassengerStatus } from '../types/passenger';
import { PageContainer } from '@/shared/components/ui/PageContainer';
import { DataTable } from '@/shared/components/ui/DataTable';
import { StatusBadge } from '@/shared/components/ui/StatusBadge';
import { ConfirmDialog } from '@/shared/components/ui/ConfirmDialog';
import { useAppShell } from '@/shared/layouts/appShellContext';
import { useDebounce } from '@/shared/hooks/useDebounce';
import type { ColumnDef, ActionDef } from '@/shared/types/table';

export function PassengerList() {
  const navigate = useNavigate();
  const { setPageTitle } = useAppShell();
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState<string>('');
  const [page, setPage] = useState(1);
  const [deleteId, setDeleteId] = useState<number | null>(null);
  const debouncedSearch = useDebounce(search, 300);

  const filters: PassengerFilters = {
    page,
    per_page: 15,
    search: debouncedSearch || undefined,
    status: (statusFilter || undefined) as PassengerStatus | undefined,
  };

  const { data, isLoading, isError, refetch } = usePassengers(filters);
  const deleteMutation = useDeletePassenger();

  useEffect(() => {
    setPageTitle('Passengers');
  }, [setPageTitle]);

  const passengers = data?.data ?? [];
  const pagination = data?.meta?.pagination;

  const columns: ColumnDef<Passenger>[] = [
    { header: 'Name', accessorKey: 'first_name', cell: ({ row }) => `${row.original.first_name} ${row.original.last_name}` },
    { header: 'Email', accessorKey: 'email' },
    { header: 'Employee ID', accessorKey: 'employee_id' },
    { header: 'Department', accessorKey: 'department' },
    {
      header: 'Status',
      accessorKey: 'status',
      cell: ({ getValue }) => <StatusBadge status={getValue() as string} />,
    },
  ];

  const actions: ActionDef<Passenger>[] = [
    {
      label: 'View',
      onClick: (row) => navigate(`/app/passengers/${row.id}`),
      variant: 'primary',
    },
    {
      label: 'Edit',
      onClick: (row) => navigate(`/app/passengers/${row.id}/edit`),
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
      title="Passengers"
      description="Manage university transport passengers"
      actions={
        <button className="btn btn-primary" onClick={() => navigate('/app/passengers/new')}>
          Add Passenger
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
          <option value={PassengerStatus.Active}>Active</option>
          <option value={PassengerStatus.Inactive}>Inactive</option>
        </select>
      </div>

      <DataTable<Passenger>
        data={passengers}
        columns={columns}
        actions={actions}
        isLoading={isLoading}
        isError={isError}
        errorMessage="Failed to load passengers"
        onRetry={() => refetch()}
        emptyTitle="No passengers found"
        emptyDescription="Get started by adding your first passenger."
        emptyActionLabel="Add Passenger"
        emptyAction={() => navigate('/app/passengers/new')}
        searchPlaceholder="Search passengers..."
        onSearch={(q) => { setSearch(q); setPage(1); }}
        searchQuery={search}
        page={pagination?.current_page}
        pageCount={pagination?.last_page}
        total={pagination?.total}
        onPageChange={setPage}
      />

      <ConfirmDialog
        open={deleteId !== null}
        title="Delete Passenger"
        message="Are you sure you want to delete this passenger? This action cannot be undone."
        confirmLabel="Delete"
        onConfirm={handleDelete}
        onCancel={() => setDeleteId(null)}
        isLoading={deleteMutation.isPending}
      />
    </PageContainer>
  );
}
