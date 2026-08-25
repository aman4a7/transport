import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { useContract, useDeleteContract, useActivateContract, useTerminateContract } from '../hooks/useContracts';
import { PageContainer } from '@/shared/components/ui/PageContainer';
import { LoadingState } from '@/shared/components/ui/LoadingState';
import { EmptyState } from '@/shared/components/ui/EmptyState';
import { KpiCard } from '@/shared/components/ui/KpiCard';
import { ConfirmDialog } from '@/shared/components/ui/ConfirmDialog';
import { useAppShell } from '@/shared/layouts/appShellContext';
import { Calendar, DollarSign, FileText, AlertCircle } from 'lucide-react';

const statusLabels: Record<string, string> = {
  active: 'Active',
  expired: 'Expired',
  terminated: 'Terminated',
  cancelled: 'Cancelled',
};

export function ContractDetail() {
  const navigate = useNavigate();
  const { id } = useParams<{ id: string }>();
  const { setPageTitle } = useAppShell();
  const [confirmDelete, setConfirmDelete] = useState(false);
  const [actionError, setActionError] = useState<string | null>(null);

  const { data: contract, isLoading, isError, refetch } = useContract(id ? Number(id) : 0);
  const deleteMutation = useDeleteContract();
  const activateMutation = useActivateContract();
  const terminateMutation = useTerminateContract();

  useEffect(() => {
    if (contract) {
      setPageTitle(`Contract - ${contract.contract_number}`);
    }
  }, [setPageTitle, contract]);

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
    return <PageContainer title="Contract"><LoadingState variant="skeleton" type="card" rows={3} /></PageContainer>;
  }

  if (isError || !contract) {
    return (
      <PageContainer title="Contract">
        <EmptyState title="Contract not found" description="The contract could not be found." actionLabel="Back to Contracts" onAction={() => navigate('/app/contracts')} />
      </PageContainer>
    );
  }

  return (
    <PageContainer
      title={`Contract - ${contract.contract_number}`}
      actions={
        <div style={{ display: 'flex', gap: 'var(--space-2)' }}>
          <button className="btn btn-secondary" onClick={() => navigate(`/app/contracts/${id}/edit`)}>
            Edit
          </button>
          {contract.status === 'active' && (
            <button className="btn btn-danger" onClick={() => handleAction(() => terminateMutation.mutateAsync(contract.id))} disabled={terminateMutation.isPending}>
              Terminate
            </button>
          )}
          {contract.status !== 'active' && (
            <button className="btn btn-primary" onClick={() => handleAction(() => activateMutation.mutateAsync(contract.id))} disabled={activateMutation.isPending}>
              Activate
            </button>
          )}
          <button className="btn btn-danger" onClick={() => setConfirmDelete(true)}>
            Delete
          </button>
        </div>
      }
    >
      {actionError && (
        <div role="alert" style={{ padding: 'var(--space-3)', marginBottom: 'var(--space-4)', background: 'var(--color-danger-light)', border: '1px solid var(--red-200)', borderRadius: 'var(--radius-md)', fontSize: 'var(--text-sm)', color: 'var(--color-danger)' }}>
          {actionError}
        </div>
      )}

      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: 'var(--space-4)', marginBottom: 'var(--space-6)' }}>
        <KpiCard label="Contract #" value={contract.contract_number} icon={FileText} />
        <KpiCard label="Status" value={statusLabels[contract.status] ?? contract.status} icon={AlertCircle} />
        <KpiCard label="Period" value={`${new Date(contract.start_date).toLocaleDateString()} - ${new Date(contract.end_date).toLocaleDateString()}`} icon={Calendar} />
        <KpiCard label="Value" value={contract.contract_value != null ? `ETB ${contract.contract_value.toLocaleString()}` : 'Not set'} icon={DollarSign} />
      </div>

      <div className="card" style={{ padding: 'var(--space-6)' }}>
        <div style={{ display: 'grid', gap: 'var(--space-4)' }}>
          <div>
            <span style={{ fontSize: 'var(--text-sm)', color: 'var(--color-text-secondary)', display: 'block' }}>Vehicle</span>
            <span>{contract.vehicle?.plate_number ?? '-'}</span>
          </div>
          <div>
            <span style={{ fontSize: 'var(--text-sm)', color: 'var(--color-text-secondary)', display: 'block' }}>Contractor</span>
            <span>{contract.owner?.company_name ?? '-'}</span>
          </div>
          {contract.payment_terms && (
            <div>
              <span style={{ fontSize: 'var(--text-sm)', color: 'var(--color-text-secondary)', display: 'block' }}>Payment Terms</span>
              <span>{contract.payment_terms}</span>
            </div>
          )}
          {contract.notes && (
            <div>
              <span style={{ fontSize: 'var(--text-sm)', color: 'var(--color-text-secondary)', display: 'block' }}>Notes</span>
              <span>{contract.notes}</span>
            </div>
          )}
          <div>
            <span style={{ fontSize: 'var(--text-sm)', color: 'var(--color-text-secondary)', display: 'block' }}>Created</span>
            <span>{new Date(contract.created_at).toLocaleString()}</span>
          </div>
        </div>
      </div>

      <ConfirmDialog
        open={confirmDelete}
        onCancel={() => setConfirmDelete(false)}
        title="Delete Contract"
        message="Are you sure you want to delete this contract? This action cannot be undone."
        confirmLabel="Delete"
        cancelLabel="Cancel"
        variant="danger"
        onConfirm={() => {
          deleteMutation.mutate(contract.id, {
            onSuccess: () => navigate('/app/contracts'),
          });
        }}
      />
    </PageContainer>
  );
}
