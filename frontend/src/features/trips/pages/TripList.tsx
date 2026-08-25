import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useTrips, useDeleteTrip, useStartTrip, useCompleteTrip, useCancelTrip } from '../hooks/useTrips';
import type { Trip, TripFilters } from '../types/trip';
import { TripStatus } from '../types/trip';
import { PageContainer } from '@/shared/components/ui/PageContainer';
import { DataTable } from '@/shared/components/ui/DataTable';
import { StatusBadge } from '@/shared/components/ui/StatusBadge';
import { ConfirmDialog } from '@/shared/components/ui/ConfirmDialog';
import { useAppShell } from '@/shared/layouts/appShellContext';
import type { ColumnDef, ActionDef } from '@/shared/types/table';

const statusLabel: Record<string, string> = {
  scheduled: 'Scheduled',
  in_progress: 'In Progress',
  completed: 'Completed',
  cancelled: 'Cancelled',
};

export function TripList() {
  const navigate = useNavigate();
  const { setPageTitle } = useAppShell();
  const [statusFilter, setStatusFilter] = useState<string>('');
  const [page, setPage] = useState(1);
  const [deleteId, setDeleteId] = useState<number | null>(null);

  const filters: TripFilters = {
    page,
    per_page: 15,
    status: (statusFilter || undefined) as TripStatus | undefined,
  };

  const { data, isLoading, isError, refetch } = useTrips(filters);
  const deleteMutation = useDeleteTrip();
  const startMutation = useStartTrip();
  const completeMutation = useCompleteTrip();
  const cancelMutation = useCancelTrip();

  useEffect(() => {
    setPageTitle('Trips');
  }, [setPageTitle]);

  const trips = data?.data ?? [];
  const pagination = data?.meta?.pagination;

  const columns: ColumnDef<Trip>[] = [
    {
      header: 'Date',
      accessorKey: 'scheduled_date',
      cell: ({ getValue }) => new Date(getValue() as string).toLocaleDateString(),
    },
    {
      header: 'Route',
      accessorKey: 'route',
      cell: ({ row }) => row.original.route?.name ?? '-',
    },
    {
      header: 'Vehicle',
      accessorKey: 'vehicle',
      cell: ({ row }) => row.original.vehicle?.plate_number ?? '-',
    },
    { header: 'Departure', accessorKey: 'departure_time' },
    {
      header: 'Status',
      accessorKey: 'status',
      cell: ({ getValue }) => {
        const val = getValue() as TripStatus;
        return <StatusBadge status={val} label={statusLabel[val] ?? val} />;
      },
    },
  ];

  const actions: ActionDef<Trip>[] = [
    {
      label: 'View',
      onClick: (row) => navigate(`/app/trips/${row.id}`),
      variant: 'primary',
    },
    {
      label: 'Start',
      onClick: (row: Trip) => startMutation.mutate(row.id),
      variant: 'primary',
      show: (row: Trip) => row.status === TripStatus.Scheduled,
    },
    {
      label: 'Complete',
      onClick: (row: Trip) => completeMutation.mutate(row.id),
      variant: 'primary',
      show: (row: Trip) => row.status === TripStatus.InProgress,
    },
    {
      label: 'Cancel',
      onClick: (row: Trip) => cancelMutation.mutate(row.id),
      variant: 'danger',
      show: (row: Trip) => row.status === TripStatus.Scheduled || row.status === TripStatus.InProgress,
    },
    {
      label: 'Edit',
      onClick: (row) => navigate(`/app/trips/${row.id}/edit`),
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
      title="Trips"
      description="Manage transport trips"
      actions={
        <button className="btn btn-primary" onClick={() => navigate('/app/trips/new')}>
          Schedule Trip
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
          <option value={TripStatus.Scheduled}>Scheduled</option>
          <option value={TripStatus.InProgress}>In Progress</option>
          <option value={TripStatus.Completed}>Completed</option>
          <option value={TripStatus.Cancelled}>Cancelled</option>
        </select>
      </div>

      <DataTable<Trip>
        data={trips}
        columns={columns}
        actions={actions}
        isLoading={isLoading}
        isError={isError}
        errorMessage="Failed to load trips"
        onRetry={() => refetch()}
        emptyTitle="No trips found"
        emptyDescription="Get started by scheduling your first trip."
        emptyActionLabel="Schedule Trip"
        emptyAction={() => navigate('/app/trips/new')}
        page={pagination?.current_page}
        pageCount={pagination?.last_page}
        total={pagination?.total}
        onPageChange={setPage}
      />

      <ConfirmDialog
        open={deleteId !== null}
        title="Delete Trip"
        message="Are you sure you want to delete this trip? This action cannot be undone."
        confirmLabel="Delete"
        onConfirm={handleDelete}
        onCancel={() => setDeleteId(null)}
        isLoading={deleteMutation.isPending}
      />
    </PageContainer>
  );
}
