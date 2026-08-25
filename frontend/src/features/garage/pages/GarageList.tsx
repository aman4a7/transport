import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useMaintenanceRecords, useDeleteMaintenanceRecord, useStartMaintenance, useCompleteMaintenance, useCancelMaintenance } from '../hooks/useGarage';
import type { MaintenanceRecord, GarageFilters } from '../types/garage';
import { PageContainer } from '@/shared/components/ui/PageContainer';
import { DataTable } from '@/shared/components/ui/DataTable';
import { StatusBadge } from '@/shared/components/ui/StatusBadge';
import { ConfirmDialog } from '@/shared/components/ui/ConfirmDialog';
import { useAppShell } from '@/shared/layouts/appShellContext';
import type { ColumnDef, ActionDef } from '@/shared/types/table';
import { toNumber } from '@/shared/utils/formatters';

const statusVariant: Record<string, 'info' | 'warning' | 'success' | 'neutral'> = {
  pending: 'info',
  in_progress: 'warning',
  completed: 'success',
  cancelled: 'neutral',
};

const typeLabels: Record<string, string> = {
  scheduled: 'Scheduled',
  repair: 'Repair',
  inspection: 'Inspection',
  other: 'Other',
};

export function GarageList() {
  const navigate = useNavigate();
  const { setPageTitle } = useAppShell();
  const [statusFilter, setStatusFilter] = useState<string>('');
  const [typeFilter, setTypeFilter] = useState<string>('');
  const [page, setPage] = useState(1);
  const [confirmDelete, setConfirmDelete] = useState<number | null>(null);

  const deleteMutation = useDeleteMaintenanceRecord();
  const startMutation = useStartMaintenance();
  const completeMutation = useCompleteMaintenance();
  const cancelMutation = useCancelMaintenance();

  const filters: GarageFilters = {
    page,
    per_page: 15,
    status: statusFilter || undefined,
    maintenance_type: typeFilter || undefined,
  };

  const { data, isLoading, isError, refetch } = useMaintenanceRecords(filters);

  useEffect(() => {
    setPageTitle('Garage — Maintenance Records');
  }, [setPageTitle]);

  const records = data?.data ?? [];
  const pagination = data?.meta?.pagination;

  const columns: ColumnDef<MaintenanceRecord>[] = [
    {
      header: 'Date',
      accessorKey: 'scheduled_date',
      cell: ({ getValue }) => new Date(getValue() as string).toLocaleDateString(),
    },
    {
      header: 'Vehicle',
      accessorKey: 'vehicle',
      cell: ({ row }) => row.original.vehicle?.plate_number ?? '-',
    },
    {
      header: 'Type',
      accessorKey: 'maintenance_type',
      cell: ({ getValue }) => typeLabels[getValue() as string] ?? (getValue() as string),
    },
    {
      header: 'Status',
      accessorKey: 'status',
      cell: ({ getValue }) => {
        const val = getValue() as string;
        return <StatusBadge status={val} variant={statusVariant[val]} label={val.replace('_', ' ')} />;
      },
    },
    {
      header: 'Description',
      accessorKey: 'description',
      cell: ({ getValue }) => {
        const desc = getValue() as string;
        return desc.length > 60 ? `${desc.slice(0, 60)}...` : desc;
      },
    },
    {
      header: 'Cost',
      accessorKey: 'cost',
      cell: ({ getValue }) => {
        const cost = getValue() as number | string | null;
        return cost != null && cost !== '' ? `ETB ${toNumber(cost).toFixed(2)}` : '-';
      },
    },
  ];

  const actions: ActionDef<MaintenanceRecord>[] = [
    {
      label: 'Start',
      onClick: (row) => startMutation.mutate(row.id),
      show: (row) => row.status === 'pending',
      variant: 'primary',
    },
    {
      label: 'Complete',
      onClick: (row) => completeMutation.mutate(row.id),
      show: (row) => row.status === 'in_progress',
      variant: 'primary',
    },
    {
      label: 'Cancel',
      onClick: (row) => cancelMutation.mutate(row.id),
      show: (row) => row.status === 'pending' || row.status === 'in_progress',
      variant: 'danger',
    },
    {
      label: 'Delete',
      onClick: (row) => setConfirmDelete(row.id),
      show: (row) => row.status !== 'in_progress',
      variant: 'danger',
    },
  ];

  return (
    <PageContainer
      title="Garage"
      description="Manage vehicle maintenance, repairs, and service history"
      actions={
        <div style={{ display: 'flex', gap: 'var(--space-2)' }}>
          <button className="btn btn-primary" onClick={() => navigate('/app/garage/new')}>
            New Record
          </button>
        </div>
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
          <option value="pending">Pending</option>
          <option value="in_progress">In Progress</option>
          <option value="completed">Completed</option>
          <option value="cancelled">Cancelled</option>
        </select>

        <select
          className="form-input"
          value={typeFilter}
          onChange={(e) => { setTypeFilter(e.target.value); setPage(1); }}
          style={{ maxWidth: 200 }}
          aria-label="Filter by type"
        >
          <option value="">All Types</option>
          <option value="scheduled">Scheduled</option>
          <option value="repair">Repair</option>
          <option value="inspection">Inspection</option>
          <option value="other">Other</option>
        </select>
      </div>

      <DataTable<MaintenanceRecord>
        data={records}
        columns={columns}
        actions={actions}
        isLoading={isLoading}
        isError={isError}
        errorMessage="Failed to load maintenance records"
        onRetry={() => refetch()}
        emptyTitle="No maintenance records found"
        emptyDescription="Create a new maintenance record to get started."
        emptyActionLabel="New Record"
        emptyAction={() => navigate('/app/garage/new')}
        page={pagination?.current_page}
        pageCount={pagination?.last_page}
        total={pagination?.total}
        onPageChange={setPage}
        onRowClick={(row) => navigate(`/app/garage/${row.id}`)}
      />

      <ConfirmDialog
        open={confirmDelete !== null}
        onCancel={() => setConfirmDelete(null)}
        title="Delete Maintenance Record"
        message="Are you sure you want to delete this maintenance record? This action cannot be undone."
        confirmLabel="Delete"
        cancelLabel="Cancel"
        variant="danger"
        onConfirm={() => {
          if (confirmDelete !== null) {
            deleteMutation.mutate(confirmDelete, {
              onSuccess: () => setConfirmDelete(null),
            });
          }
        }}
      />
    </PageContainer>
  );
}
