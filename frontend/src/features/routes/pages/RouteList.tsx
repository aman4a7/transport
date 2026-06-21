import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useRoutes, useDeleteRoute } from '../hooks/useRoutes';
import type { Route, RouteFilters } from '../types/route';
import { RouteStatus } from '../types/route';
import { PageContainer } from '@/shared/components/ui/PageContainer';
import { DataTable } from '@/shared/components/ui/DataTable';
import { StatusBadge } from '@/shared/components/ui/StatusBadge';
import { ConfirmDialog } from '@/shared/components/ui/ConfirmDialog';
import { useAppShell } from '@/shared/layouts/appShellContext';
import { useDebounce } from '@/shared/hooks/useDebounce';
import type { ColumnDef, ActionDef } from '@/shared/types/table';

export function RouteList() {
  const navigate = useNavigate();
  const { setPageTitle } = useAppShell();
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState<string>('');
  const [page, setPage] = useState(1);
  const [deleteId, setDeleteId] = useState<number | null>(null);
  const debouncedSearch = useDebounce(search, 300);

  const filters: RouteFilters = {
    page,
    per_page: 15,
    search: debouncedSearch || undefined,
    status: (statusFilter || undefined) as RouteStatus | undefined,
  };

  const { data, isLoading, isError, refetch } = useRoutes(filters);
  const deleteMutation = useDeleteRoute();

  useEffect(() => {
    setPageTitle('Routes');
  }, [setPageTitle]);

  const routes = data?.data ?? [];
  const pagination = data?.meta?.pagination;

  const columns: ColumnDef<Route>[] = [
    { header: 'Name', accessorKey: 'name' },
    { header: 'Code', accessorKey: 'code' },
    { header: 'Origin', accessorKey: 'origin' },
    { header: 'Destination', accessorKey: 'destination' },
    {
      header: 'Distance (km)',
      accessorKey: 'distance_km',
      cell: ({ getValue }) => {
        const val = getValue() as number | null;
        return val ? `${val.toFixed(1)} km` : '-';
      },
    },
    {
      header: 'Status',
      accessorKey: 'status',
      cell: ({ getValue }) => {
        const val = getValue() as RouteStatus;
        return <StatusBadge status={val} />;
      },
    },
  ];

  const actions: ActionDef<Route>[] = [
    {
      label: 'View',
      onClick: (row) => navigate(`/app/routes/${row.id}`),
      variant: 'primary',
    },
    {
      label: 'Edit',
      onClick: (row) => navigate(`/app/routes/${row.id}/edit`),
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
      title="Routes"
      description="Manage transport routes"
      actions={
        <button className="btn btn-primary" onClick={() => navigate('/app/routes/new')}>
          Add Route
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
          <option value={RouteStatus.Active}>Active</option>
          <option value={RouteStatus.Inactive}>Inactive</option>
        </select>
      </div>

      <DataTable<Route>
        data={routes}
        columns={columns}
        actions={actions}
        isLoading={isLoading}
        isError={isError}
        errorMessage="Failed to load routes"
        onRetry={() => refetch()}
        emptyTitle="No routes found"
        emptyDescription="Get started by adding your first route."
        emptyActionLabel="Add Route"
        emptyAction={() => navigate('/app/routes/new')}
        searchPlaceholder="Search routes..."
        onSearch={(q) => { setSearch(q); setPage(1); }}
        searchQuery={search}
        page={pagination?.current_page}
        pageCount={pagination?.last_page}
        total={pagination?.total}
        onPageChange={setPage}
      />

      <ConfirmDialog
        open={deleteId !== null}
        title="Delete Route"
        message="Are you sure you want to delete this route? This action cannot be undone."
        confirmLabel="Delete"
        onConfirm={handleDelete}
        onCancel={() => setDeleteId(null)}
        isLoading={deleteMutation.isPending}
      />
    </PageContainer>
  );
}
