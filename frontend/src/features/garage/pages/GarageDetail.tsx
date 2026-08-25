import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { useMaintenanceRecord, useDeleteMaintenanceRecord, useStartMaintenance, useCompleteMaintenance, useCancelMaintenance } from '../hooks/useGarage';
import { PageContainer } from '@/shared/components/ui/PageContainer';
import { LoadingState } from '@/shared/components/ui/LoadingState';
import { EmptyState } from '@/shared/components/ui/EmptyState';
import { KpiCard } from '@/shared/components/ui/KpiCard';
import { ConfirmDialog } from '@/shared/components/ui/ConfirmDialog';
import { useAppShell } from '@/shared/layouts/appShellContext';
import { toNumber } from '@/shared/utils/formatters';
import { Calendar, Wrench, DollarSign, AlertCircle } from 'lucide-react';

const typeLabels: Record<string, string> = {
  scheduled: 'Scheduled',
  repair: 'Repair',
  inspection: 'Inspection',
  other: 'Other',
};

export function GarageDetail() {
  const navigate = useNavigate();
  const { id } = useParams<{ id: string }>();
  const { setPageTitle } = useAppShell();
  const [confirmDelete, setConfirmDelete] = useState(false);
  const [actionError, setActionError] = useState<string | null>(null);

  const { data: record, isLoading, isError, refetch } = useMaintenanceRecord(id ? Number(id) : 0);
  const deleteMutation = useDeleteMaintenanceRecord();
  const startMutation = useStartMaintenance();
  const completeMutation = useCompleteMaintenance();
  const cancelMutation = useCancelMaintenance();

  useEffect(() => {
    if (record) {
      setPageTitle(`Maintenance - ${record.vehicle?.plate_number ?? 'Record'}`);
    }
  }, [setPageTitle, record]);

  async function handleAction(action: () => Promise<unknown>) {
    setActionError(null);
    try {
      await action();
      refetch();
    } catch {
      setActionError('Action failed. Please try again.');
    }
  }

  if (isLoading) {
    return <PageContainer title="Maintenance Record"><LoadingState variant="skeleton" type="card" rows={3} /></PageContainer>;
  }

  if (isError || !record) {
    return (
      <PageContainer title="Maintenance Record">
        <EmptyState title="Record not found" description="The maintenance record could not be found." actionLabel="Back to Garage" onAction={() => navigate('/app/garage')} />
      </PageContainer>
    );
  }

  return (
    <PageContainer
      title={`Maintenance - ${record.vehicle?.plate_number ?? 'Record'}`}
      actions={
        <div style={{ display: 'flex', gap: 'var(--space-2)' }}>
          <button className="btn btn-secondary" onClick={() => navigate(`/app/garage/${id}/edit`)}>
            Edit
          </button>
          {record.status === 'pending' && (
            <button className="btn btn-primary" onClick={() => handleAction(() => startMutation.mutateAsync(record.id))} disabled={startMutation.isPending}>
              Start Maintenance
            </button>
          )}
          {record.status === 'in_progress' && (
            <button className="btn btn-primary" onClick={() => handleAction(() => completeMutation.mutateAsync(record.id))} disabled={completeMutation.isPending}>
              Complete
            </button>
          )}
          {(record.status === 'pending' || record.status === 'in_progress') && (
            <button className="btn btn-danger" onClick={() => handleAction(() => cancelMutation.mutateAsync(record.id))} disabled={cancelMutation.isPending}>
              Cancel
            </button>
          )}
          {record.status !== 'in_progress' && (
            <button className="btn btn-danger" onClick={() => setConfirmDelete(true)}>
              Delete
            </button>
          )}
        </div>
      }
    >
      {actionError && (
        <div role="alert" style={{ padding: 'var(--space-3)', marginBottom: 'var(--space-4)', background: 'var(--color-danger-light)', border: '1px solid var(--red-200)', borderRadius: 'var(--radius-md)', fontSize: 'var(--text-sm)', color: 'var(--color-danger)' }}>
          {actionError}
        </div>
      )}

      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: 'var(--space-4)', marginBottom: 'var(--space-6)' }}>
        <KpiCard label="Status" value={record.status.replace('_', ' ')} icon={AlertCircle} />
        <KpiCard label="Type" value={typeLabels[record.maintenance_type] ?? record.maintenance_type} icon={Wrench} />
        <KpiCard label="Scheduled Date" value={new Date(record.scheduled_date).toLocaleDateString()} icon={Calendar} />
        <KpiCard label="Cost" value={record.cost != null && record.cost !== '' ? `ETB ${toNumber(record.cost).toFixed(2)}` : 'Not set'} icon={DollarSign} />
      </div>

      <div className="card" style={{ padding: 'var(--space-6)' }}>
        <div style={{ display: 'grid', gap: 'var(--space-4)' }}>
          <div>
            <span style={{ fontSize: 'var(--text-sm)', color: 'var(--color-text-secondary)', display: 'block' }}>Vehicle</span>
            <span>{record.vehicle?.plate_number ?? '-'}</span>
          </div>
          <div>
            <span style={{ fontSize: 'var(--text-sm)', color: 'var(--color-text-secondary)', display: 'block' }}>Description</span>
            <span>{record.description}</span>
          </div>
          {record.notes && (
            <div>
              <span style={{ fontSize: 'var(--text-sm)', color: 'var(--color-text-secondary)', display: 'block' }}>Notes</span>
              <span>{record.notes}</span>
            </div>
          )}
          {record.started_at && (
            <div>
              <span style={{ fontSize: 'var(--text-sm)', color: 'var(--color-text-secondary)', display: 'block' }}>Started At</span>
              <span>{new Date(record.started_at).toLocaleString()}</span>
            </div>
          )}
          {record.completed_at && (
            <div>
              <span style={{ fontSize: 'var(--text-sm)', color: 'var(--color-text-secondary)', display: 'block' }}>Completed At</span>
              <span>{new Date(record.completed_at).toLocaleString()}</span>
            </div>
          )}
          <div>
            <span style={{ fontSize: 'var(--text-sm)', color: 'var(--color-text-secondary)', display: 'block' }}>Created</span>
            <span>{new Date(record.created_at).toLocaleString()}</span>
          </div>
        </div>
      </div>

      <ConfirmDialog
        open={confirmDelete}
        onCancel={() => setConfirmDelete(false)}
        title="Delete Maintenance Record"
        message="Are you sure you want to delete this maintenance record? This action cannot be undone."
        confirmLabel="Delete"
        cancelLabel="Cancel"
        variant="danger"
        onConfirm={() => {
          deleteMutation.mutate(record.id, {
            onSuccess: () => navigate('/app/garage'),
          });
        }}
      />
    </PageContainer>
  );
}
