import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useFuelTransactions } from '../hooks/useFuel';
import type { FuelTransaction, FuelFilters } from '../types/fuel';
import { PageContainer } from '@/shared/components/ui/PageContainer';
import { DataTable } from '@/shared/components/ui/DataTable';
import { StatusBadge } from '@/shared/components/ui/StatusBadge';
import { useAppShell } from '@/shared/layouts/appShellContext';
import type { ColumnDef, ActionDef } from '@/shared/types/table';

const typeLabel: Record<string, string> = {
  issue: 'Issue',
  restock: 'Restock',
  adjustment: 'Adjustment',
};

const typeVariant: Record<string, 'success' | 'danger' | 'info'> = {
  issue: 'danger',
  restock: 'success',
  adjustment: 'info',
};

export function FuelList() {
  const navigate = useNavigate();
  const { setPageTitle } = useAppShell();
  const [typeFilter, setTypeFilter] = useState<string>('');
  const [page, setPage] = useState(1);

  const filters: FuelFilters = {
    page,
    per_page: 15,
    transaction_type: typeFilter || undefined,
  };

  const { data, isLoading, isError, refetch } = useFuelTransactions(filters);

  useEffect(() => {
    setPageTitle('Fuel Transactions');
  }, [setPageTitle]);

  const transactions = data?.data ?? [];
  const pagination = data?.meta?.pagination;

  const columns: ColumnDef<FuelTransaction>[] = [
    {
      header: 'Date',
      accessorKey: 'created_at',
      cell: ({ getValue }) => new Date(getValue() as string).toLocaleString(),
    },
    {
      header: 'Type',
      accessorKey: 'transaction_type',
      cell: ({ getValue }) => {
        const val = getValue() as string;
        return <StatusBadge status={val} label={typeLabel[val] ?? val} variant={typeVariant[val]} />;
      },
    },
    {
      header: 'Fuel',
      accessorKey: 'fuel_type',
      cell: ({ getValue }) => (getValue() as string)?.toUpperCase(),
    },
    {
      header: 'Quantity',
      accessorKey: 'quantity',
      cell: ({ getValue }) => {
        const qty = getValue() as number;
        return <span style={{ color: qty < 0 ? 'var(--color-danger)' : 'var(--color-success)' }}>{qty.toFixed(1)} L</span>;
      },
    },
    {
      header: 'Vehicle',
      accessorKey: 'vehicle',
      cell: ({ row }) => row.original.vehicle?.plate_number ?? '-',
    },
    {
      header: 'Issuer',
      accessorKey: 'issuer',
      cell: ({ row }) => row.original.issuer?.name ?? '-',
    },
  ];

  const actions: ActionDef<FuelTransaction>[] = [
    {
      label: 'View',
      onClick: () => {},
      variant: 'primary',
    },
  ];

  return (
    <PageContainer
      title="Fuel Transactions"
      description="Track fuel issuance, restocking, and adjustments"
      actions={
        <div style={{ display: 'flex', gap: 'var(--space-2)' }}>
          <button className="btn btn-primary" onClick={() => navigate('/app/fuel/issue')}>
            Issue Fuel
          </button>
          <button className="btn btn-secondary" onClick={() => navigate('/app/fuel/restock')}>
            Restock
          </button>
          <button className="btn btn-secondary" onClick={() => navigate('/app/fuel/stock')}>
            Stock Levels
          </button>
        </div>
      }
    >
      <div style={{ display: 'flex', gap: 'var(--space-2)', marginBottom: 'var(--space-4)' }}>
        <select
          className="form-input"
          value={typeFilter}
          onChange={(e) => { setTypeFilter(e.target.value); setPage(1); }}
          style={{ maxWidth: 200 }}
          aria-label="Filter by type"
        >
          <option value="">All Types</option>
          <option value="issue">Issue</option>
          <option value="restock">Restock</option>
          <option value="adjustment">Adjustment</option>
        </select>
      </div>

      <DataTable<FuelTransaction>
        data={transactions}
        columns={columns}
        actions={actions}
        isLoading={isLoading}
        isError={isError}
        errorMessage="Failed to load transactions"
        onRetry={() => refetch()}
        emptyTitle="No transactions found"
        emptyDescription="Issue fuel or restock to get started."
        emptyActionLabel="Issue Fuel"
        emptyAction={() => navigate('/app/fuel/issue')}
        page={pagination?.current_page}
        pageCount={pagination?.last_page}
        total={pagination?.total}
        onPageChange={setPage}
      />
    </PageContainer>
  );
}
