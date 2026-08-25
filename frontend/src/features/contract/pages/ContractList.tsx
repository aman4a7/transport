import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useContracts, useDeleteContract, useActivateContract, useTerminateContract } from '../hooks/useContracts';
import type { Contract, ContractFilters } from '../types';
import { PageContainer } from '@/shared/components/ui/PageContainer';
import { DataTable } from '@/shared/components/ui/DataTable';
import { StatusBadge } from '@/shared/components/ui/StatusBadge';
import { ConfirmDialog } from '@/shared/components/ui/ConfirmDialog';
import { useAppShell } from '@/shared/layouts/appShellContext';
import type { ColumnDef, ActionDef } from '@/shared/types/table';

const statusVariant: Record<string, 'info' | 'warning' | 'success' | 'neutral' | 'danger'> = {
  active: 'success',
  expired: 'neutral',
  terminated: 'danger',
  cancelled: 'warning',
};

const statusLabels: Record<string, string> = {
  active: 'Active',
  expired: 'Expired',
  terminated: 'Terminated',
  cancelled: 'Cancelled',
};

export function ContractList() {
  const navigate = useNavigate();
  const { setPageTitle } = useAppShell();
  const [statusFilter, setStatusFilter] = useState<string>('');
  const [page, setPage] = useState(1);
  const [confirmDelete, setConfirmDelete] = useState<number | null>(null);

  const deleteMutation = useDeleteContract();
  const activateMutation = useActivateContract();
  const terminateMutation = useTerminateContract();

  const filters: ContractFilters = {
    page,
    per_page: 15,
    status: statusFilter || undefined,
  };

  const { data, isLoading, isError, refetch } = useContracts(filters);

  useEffect(() => {
    setPageTitle('Contracts');
  }, [setPageTitle]);

  const records = data?.data ?? [];
  const pagination = data?.meta?.pagination;

  const columns: ColumnDef<Contract>[] = [
    {
      header: 'Contract #',
      accessorKey: 'contract_number',
    },
    {
      header: 'Vehicle',
      accessorKey: 'vehicle',
      cell: ({ row }) => row.original.vehicle?.plate_number ?? '-',
    },
    {
      header: 'Contractor',
      accessorKey: 'owner',
      cell: ({ row }) => row.original.owner?.company_name ?? '-',
    },
    {
      header: 'Start',
      accessorKey: 'start_date',
      cell: ({ getValue }) => new Date(getValue() as string).toLocaleDateString(),
    },
    {
      header: 'End',
      accessorKey: 'end_date',
      cell: ({ getValue }) => new Date(getValue() as string).toLocaleDateString(),
    },
    {
      header: 'Status',
      accessorKey: 'status',
      cell: ({ getValue }) => {
        const val = getValue() as string;
        return <StatusBadge status={val} variant={statusVariant[val]} label={statusLabels[val] ?? val} />;
      },
    },
    {
      header: 'Value',
      accessorKey: 'contract_value',
      cell: ({ getValue }) => {
        const val = getValue() as number | null;
        return val != null ? `ETB ${val.toLocaleString()}` : '-';
      },
    },
  ];

  const actions: ActionDef<Contract>[] = [
    {
      label: 'Activate',
      onClick: (row) => activateMutation.mutate(row.id),
      show: (row) => row.status !== 'active',
      variant: 'primary',
    },
    {
      label: 'Terminate',
      onClick: (row) => terminateMutation.mutate(row.id),
      show: (row) => row.status === 'active',
      variant: 'danger',
    },
    {
      label: 'Delete',
      onClick: (row) => setConfirmDelete(row.id),
      show: () => true,
      variant: 'danger',
    },
  ];

  return (
    <PageContainer
      title="Contracts"
      description="Manage contractor vehicle service agreements"
      actions={
        <div style={{ display: 'flex', gap: 'var(--space-2)' }}>
          <button className="btn btn-primary" onClick={() => navigate('/app/contracts/new')}>
            New Contract
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
          <option value="active">Active</option>
          <option value="expired">Expired</option>
          <option value="terminated">Terminated</option>
          <option value="cancelled">Cancelled</option>
        </select>
      </div>

      <DataTable<Contract>
        data={records}
        columns={columns}
        actions={actions}
        isLoading={isLoading}
        isError={isError}
        errorMessage="Failed to load contracts"
        onRetry={() => refetch()}
        emptyTitle="No contracts found"
        emptyDescription="Create a new contract to get started."
        emptyActionLabel="New Contract"
        emptyAction={() => navigate('/app/contracts/new')}
        page={pagination?.current_page}
        pageCount={pagination?.last_page}
        total={pagination?.total}
        onPageChange={setPage}
        onRowClick={(row) => navigate(`/app/contracts/${row.id}`)}
      />

      <ConfirmDialog
        open={confirmDelete !== null}
        onCancel={() => setConfirmDelete(null)}
        title="Delete Contract"
        message="Are you sure you want to delete this contract? This action cannot be undone."
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
