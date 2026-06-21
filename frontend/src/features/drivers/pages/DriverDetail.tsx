import { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useDriver, useDeleteDriver } from '../hooks/useDrivers';
import { PageContainer } from '@/shared/components/ui/PageContainer';
import { StatusBadge } from '@/shared/components/ui/StatusBadge';
import { LoadingState } from '@/shared/components/ui/LoadingState';
import { EmptyState } from '@/shared/components/ui/EmptyState';
import { ConfirmDialog } from '@/shared/components/ui/ConfirmDialog';
import { useAppShell } from '@/shared/layouts/appShellContext';
import { KpiCard } from '@/shared/components/ui/KpiCard';
import { IdCard, CalendarDays, Truck } from 'lucide-react';

export function DriverDetail() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const { setPageTitle } = useAppShell();
  const [showDelete, setShowDelete] = useState(false);

  const { data: driver, isLoading, isError } = useDriver(id ? Number(id) : 0);
  const deleteMutation = useDeleteDriver();

  useEffect(() => {
    if (driver) {
      setPageTitle(`Driver - ${driver.license_number}`);
    }
  }, [setPageTitle, driver]);

  async function handleDelete() {
    try {
      await deleteMutation.mutateAsync(Number(id));
      navigate('/app/drivers');
    } finally {
      setShowDelete(false);
    }
  }

  if (isLoading) {
    return <PageContainer title="Driver Details"><LoadingState type="card" rows={3} /></PageContainer>;
  }

  if (isError || !driver) {
    return (
      <PageContainer title="Driver Details">
        <EmptyState
          title="Driver not found"
          description="The driver you are looking for does not exist or has been removed."
          actionLabel="Back to Drivers"
          onAction={() => navigate('/app/drivers')}
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
      title={`Driver - ${driver.license_number}`}
      actions={
        <div style={{ display: 'flex', gap: 'var(--space-2)' }}>
          <button className="btn btn-secondary" onClick={() => navigate(`/app/drivers/${driver.id}/edit`)}>
            Edit
          </button>
          <button className="btn btn-danger" onClick={() => setShowDelete(true)}>
            Delete
          </button>
        </div>
      }
    >
      <div style={{ marginBottom: 'var(--space-6)' }}>
        <StatusBadge status={driver.status} />
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: 'var(--space-4)', marginBottom: 'var(--space-6)' }}>
        <KpiCard label="License Number" value={driver.license_number} icon={IdCard} />
        <KpiCard label="License Category" value={driver.license_category} />
        <KpiCard label="License Expiry" value={driver.license_expiry} icon={CalendarDays} />
        <KpiCard label="Assigned Vehicle" value={driver.assigned_vehicle?.plate_number ?? 'Not assigned'} icon={Truck} />
      </div>

      <div className="card" style={{ padding: 'var(--space-6)' }}>
        <h3 style={{ fontSize: 'var(--text-lg)', marginBottom: 'var(--space-4)' }}>Driver Information</h3>
        {infoRow('License Number', driver.license_number)}
        {infoRow('License Category', driver.license_category)}
        {infoRow('License Expiry', driver.license_expiry)}
        {infoRow('Medical Expiry', driver.medical_expiry)}
        {infoRow('Status', driver.status)}
        {driver.assigned_vehicle && infoRow('Assigned Vehicle', driver.assigned_vehicle.plate_number)}
        {driver.user && infoRow('User', `${driver.user.name} (${driver.user.email})`)}
      </div>

      <ConfirmDialog
        open={showDelete}
        title="Delete Driver"
        message={`Are you sure you want to delete driver ${driver.license_number}? This action cannot be undone.`}
        confirmLabel="Delete"
        onConfirm={handleDelete}
        onCancel={() => setShowDelete(false)}
        isLoading={deleteMutation.isPending}
      />
    </PageContainer>
  );
}
