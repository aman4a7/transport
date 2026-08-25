import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { CheckCheck, Settings } from 'lucide-react';
import { useAppShell } from '@/shared/layouts/appShellContext';
import { PageContainer } from '@/shared/components/ui/PageContainer';
import { DataTable } from '@/shared/components/ui/DataTable';
import { StatusBadge } from '@/shared/components/ui/StatusBadge';
import { useMarkAllRead, useMarkNotificationRead, useNotifications } from '../hooks/useNotifications';
import type { AppNotification, NotificationFilters, NotificationType } from '../types/notification';
import type { ActionDef, ColumnDef } from '@/shared/types/table';

const typeLabel: Record<NotificationType, string> = {
  compliance_expiring: 'Compliance',
  contract_expiring: 'Contract',
  maintenance_due: 'Maintenance',
  fuel_low_stock: 'Fuel',
};

const typeVariant: Record<NotificationType, 'warning' | 'info' | 'danger'> = {
  compliance_expiring: 'warning',
  contract_expiring: 'warning',
  maintenance_due: 'info',
  fuel_low_stock: 'danger',
};

export function NotificationCenter() {
  const { setPageTitle } = useAppShell();
  const [statusFilter, setStatusFilter] = useState<'' | 'unread' | 'read'>('');
  const [page, setPage] = useState(1);

  const filters: NotificationFilters = {
    page,
    per_page: 15,
    status: statusFilter || undefined,
  };

  const { data, isLoading, isError, refetch } = useNotifications(filters);
  const markRead = useMarkNotificationRead();
  const markAllRead = useMarkAllRead();

  useEffect(() => {
    setPageTitle('Notifications');
  }, [setPageTitle]);

  const notifications = data?.data ?? [];
  const pagination = data?.meta?.pagination;

  const columns: ColumnDef<AppNotification>[] = [
    {
      header: 'Type',
      accessorKey: 'type',
      cell: ({ getValue }) => {
        const val = getValue() as NotificationType;
        return <StatusBadge status={val} label={typeLabel[val]} variant={typeVariant[val]} />;
      },
    },
    {
      header: 'Title',
      accessorKey: 'title',
      cell: ({ getValue, row }) => (
        <span style={row.original.read_at ? undefined : { fontWeight: 600 }}>
          {getValue() as string}
        </span>
      ),
    },
    {
      header: 'Message',
      accessorKey: 'body',
      cell: ({ getValue }) => {
        const body = getValue() as string | null;
        return body && body.length > 80 ? `${body.slice(0, 80)}…` : (body ?? '');
      },
    },
    {
      header: 'Received',
      accessorKey: 'created_at',
      cell: ({ getValue }) => new Date(getValue() as string).toLocaleString(),
    },
    {
      header: 'Status',
      accessorKey: 'read_at',
      cell: ({ row }) =>
        row.original.read_at ? (
          <StatusBadge status="read" label="Read" variant="neutral" />
        ) : (
          <StatusBadge status="unread" label="Unread" variant="info" />
        ),
    },
  ];

  const actions: ActionDef<AppNotification>[] = [
    {
      label: 'Mark as read',
      onClick: (row) => markRead.mutate(row.id),
      variant: 'primary',
      show: (row) => row.read_at === null,
    },
  ];

  return (
    <PageContainer
      title="Notifications"
      description="Review system notifications about compliance, contracts, maintenance, and fuel stock"
      actions={
        <div style={{ display: 'flex', gap: 'var(--space-2)' }}>
          <button
            className="btn btn-secondary"
            onClick={() => markAllRead.mutate()}
            disabled={markAllRead.isPending}
          >
            <CheckCheck size={16} />
            Mark all read
          </button>
          <Link to="/app/notifications/settings" className="btn btn-secondary">
            <Settings size={16} />
            Settings
          </Link>
        </div>
      }
    >
      <div style={{ display: 'flex', gap: 'var(--space-2)', marginBottom: 'var(--space-4)' }}>
        <select
          className="form-input"
          value={statusFilter}
          onChange={(e) => {
            setStatusFilter(e.target.value as '' | 'unread' | 'read');
            setPage(1);
          }}
          style={{ maxWidth: 200 }}
          aria-label="Filter by status"
        >
          <option value="">All Status</option>
          <option value="unread">Unread</option>
          <option value="read">Read</option>
        </select>
      </div>

      <DataTable<AppNotification>
        data={notifications}
        columns={columns}
        actions={actions}
        isLoading={isLoading}
        isError={isError}
        errorMessage="Failed to load notifications"
        onRetry={() => refetch()}
        emptyTitle="No notifications found"
        emptyDescription="You are all caught up."
        page={pagination?.current_page}
        pageCount={pagination?.last_page}
        total={pagination?.total}
        onPageChange={setPage}
      />
    </PageContainer>
  );
}