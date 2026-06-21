import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useOwners, useDeleteOwner } from '../hooks/useOwners';
import type { Owner, OwnerFilters } from '../types/owner';
import { OwnerStatus } from '../types/owner';
import { PageContainer } from '@/shared/components/ui/PageContainer';
import { DataTable } from '@/shared/components/ui/DataTable';
import { StatusBadge } from '@/shared/components/ui/StatusBadge';
import { ConfirmDialog } from '@/shared/components/ui/ConfirmDialog';
import { useAppShell } from '@/shared/layouts/appShellContext';
import { useDebounce } from '@/shared/hooks/useDebounce';
import type { ColumnDef, ActionDef } from '@/shared/types/table';

export function OwnerList() {
  const navigate = useNavigate();
  const { setPageTitle } = useAppShell();
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState<string>('');
  const [page, setPage] = useState(1);
  const [deleteId, setDeleteId] = useState<number | null>(null);
  const debouncedSearch = useDebounce(search, 300);

  const filters: OwnerFilters = {
    page,
    per_page: 15,
    search: debouncedSearch || undefined,
    status: (statusFilter || undefined) as OwnerStatus | undefined,
  };

  const { data, isLoading, isError, refetch } = useOwners(filters);
  const deleteMutation = useDeleteOwner();

  useEffect(() => {
    setPageTitle('Contractors');
  }, [setPageTitle]);

  const owners = data?.data ?? [];
  const pagination = data?.meta?.pagination;

  const columns: ColumnDef<Owner>[] = [
    { header: 'Company Name', accessorKey: 'company_name' },
    { header: 'Contact Person', accessorKey: 'contact_person' },
    { header: 'Phone', accessorKey: 'phone' },
    { header: 'Email', accessorKey: 'email' },
    {
      header: 'Status',
      accessorKey: 'status',
      cell: ({ getValue }) => <StatusBadge status={getValue() as string} />,
    },
  ];

  const actions: ActionDef<Owner>[] = [
    {
      label: 'View',
      onClick: (row) => navigate(`/app/contractors/${row.id}`),
      variant: 'primary',
    },
    {
      label: 'Edit',
      onClick: (row) => navigate(`/app/contractors/${row.id}/edit`),
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
      title="Contractors"
      description="Manage contracted private vehicle owners"
      actions={
        <button className="btn btn-primary" onClick={() => navigate('/app/contractors/new')}>
          Add Contractor
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
          <option value={OwnerStatus.Active}>Active</option>
          <option value={OwnerStatus.Inactive}>Inactive</option>
          <option value={OwnerStatus.Suspended}>Suspended</option>
        </select>
      </div>

      <DataTable<Owner>
        data={owners}
        columns={columns}
        actions={actions}
        isLoading={isLoading}
        isError={isError}
        errorMessage="Failed to load contractors"
        onRetry={() => refetch()}
        emptyTitle="No contractors found"
        emptyDescription="Get started by adding your first contractor."
        emptyActionLabel="Add Contractor"
        emptyAction={() => navigate('/app/contractors/new')}
        searchPlaceholder="Search contractors..."
        onSearch={(q) => { setSearch(q); setPage(1); }}
        searchQuery={search}
        page={pagination?.current_page}
        pageCount={pagination?.last_page}
        total={pagination?.total}
        onPageChange={setPage}
      />

      <ConfirmDialog
        open={deleteId !== null}
        title="Delete Contractor"
        message="Are you sure you want to delete this contractor? This action cannot be undone."
        confirmLabel="Delete"
        onConfirm={handleDelete}
        onCancel={() => setDeleteId(null)}
        isLoading={deleteMutation.isPending}
      />
    </PageContainer>
  );
}
