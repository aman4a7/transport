import { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { usePassenger, useDeletePassenger } from '../hooks/usePassengers';
import { PageContainer } from '@/shared/components/ui/PageContainer';
import { StatusBadge } from '@/shared/components/ui/StatusBadge';
import { LoadingState } from '@/shared/components/ui/LoadingState';
import { EmptyState } from '@/shared/components/ui/EmptyState';
import { ConfirmDialog } from '@/shared/components/ui/ConfirmDialog';
import { useAppShell } from '@/shared/layouts/appShellContext';
import { KpiCard } from '@/shared/components/ui/KpiCard';
import { User, Mail, Phone, Building2 } from 'lucide-react';

export function PassengerDetail() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const { setPageTitle } = useAppShell();
  const [showDelete, setShowDelete] = useState(false);

  const { data: passenger, isLoading, isError } = usePassenger(id ? Number(id) : 0);
  const deleteMutation = useDeletePassenger();

  useEffect(() => {
    if (passenger) {
      setPageTitle(`Passenger - ${passenger.first_name} ${passenger.last_name}`);
    }
  }, [setPageTitle, passenger]);

  async function handleDelete() {
    try {
      await deleteMutation.mutateAsync(Number(id));
      navigate('/app/passengers');
    } finally {
      setShowDelete(false);
    }
  }

  if (isLoading) {
    return <PageContainer title="Passenger Details"><LoadingState type="card" rows={3} /></PageContainer>;
  }

  if (isError || !passenger) {
    return (
      <PageContainer title="Passenger Details">
        <EmptyState
          title="Passenger not found"
          description="The passenger you are looking for does not exist or has been removed."
          actionLabel="Back to Passengers"
          onAction={() => navigate('/app/passengers')}
        />
      </PageContainer>
    );
  }

  const infoRow = (label: string, value: string | number | null | undefined) => (
    <div style={{ display: 'flex', gap: 'var(--space-2)', padding: 'var(--space-2) 0', borderBottom: '1px solid var(--color-border)' }}>
      <span style={{ minWidth: 160, fontWeight: 500, fontSize: 'var(--text-sm)', color: 'var(--color-text-secondary)' }}>{label}</span>
      <span style={{ fontSize: 'var(--text-sm)' }}>{value ?? '-'}</span>
    </div>
  );

  return (
    <PageContainer
      title={`Passenger - ${passenger.first_name} ${passenger.last_name}`}
      actions={
        <div style={{ display: 'flex', gap: 'var(--space-2)' }}>
          <button className="btn btn-secondary" onClick={() => navigate(`/app/passengers/${passenger.id}/edit`)}>
            Edit
          </button>
          <button className="btn btn-danger" onClick={() => setShowDelete(true)}>
            Delete
          </button>
        </div>
      }
    >
      <div style={{ marginBottom: 'var(--space-6)' }}>
        <StatusBadge status={passenger.status} />
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: 'var(--space-4)', marginBottom: 'var(--space-6)' }}>
        <KpiCard label="Name" value={`${passenger.first_name} ${passenger.last_name}`} icon={User} />
        <KpiCard label="Email" value={passenger.email} icon={Mail} />
        <KpiCard label="Phone" value={passenger.phone ?? 'N/A'} icon={Phone} />
        <KpiCard label="Department" value={passenger.department ?? 'N/A'} icon={Building2} />
      </div>

      <div className="card" style={{ padding: 'var(--space-6)' }}>
        <h3 style={{ fontSize: 'var(--text-lg)', marginBottom: 'var(--space-4)' }}>Passenger Information</h3>
        {infoRow('First Name', passenger.first_name)}
        {infoRow('Last Name', passenger.last_name)}
        {infoRow('Email', passenger.email)}
        {infoRow('Phone', passenger.phone)}
        {infoRow('Employee ID', passenger.employee_id)}
        {infoRow('Department', passenger.department)}
        {infoRow('Status', passenger.status)}
      </div>

      <ConfirmDialog
        open={showDelete}
        title="Delete Passenger"
        message={`Are you sure you want to delete passenger ${passenger.first_name} ${passenger.last_name}? This action cannot be undone.`}
        confirmLabel="Delete"
        onConfirm={handleDelete}
        onCancel={() => setShowDelete(false)}
        isLoading={deleteMutation.isPending}
      />
    </PageContainer>
  );
}
