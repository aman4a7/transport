import { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useVehicle, useDeleteVehicle } from '../hooks/useVehicles';
import { VehicleCategory } from '../types/vehicle';
import { PageContainer } from '@/shared/components/ui/PageContainer';
import { StatusBadge } from '@/shared/components/ui/StatusBadge';
import { LoadingState } from '@/shared/components/ui/LoadingState';
import { EmptyState } from '@/shared/components/ui/EmptyState';
import { ConfirmDialog } from '@/shared/components/ui/ConfirmDialog';
import { useAppShell } from '@/shared/layouts/appShellContext';
import { KpiCard } from '@/shared/components/ui/KpiCard';
import { Truck, Fuel, Users, CalendarDays } from 'lucide-react';

export function VehicleDetail() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const { setPageTitle } = useAppShell();
  const [showDelete, setShowDelete] = useState(false);

  const { data: vehicle, isLoading, isError } = useVehicle(id ? Number(id) : 0);
  const deleteMutation = useDeleteVehicle();

  useEffect(() => {
    if (vehicle) {
      setPageTitle(`Vehicle - ${vehicle.plate_number}`);
    }
  }, [setPageTitle, vehicle]);

  async function handleDelete() {
    try {
      await deleteMutation.mutateAsync(Number(id));
      navigate('/app/vehicles');
    } finally {
      setShowDelete(false);
    }
  }

  if (isLoading) {
    return <PageContainer title="Vehicle Details"><LoadingState type="card" rows={3} /></PageContainer>;
  }

  if (isError || !vehicle) {
    return (
      <PageContainer title="Vehicle Details">
        <EmptyState
          title="Vehicle not found"
          description="The vehicle you are looking for does not exist or has been removed."
          actionLabel="Back to Vehicles"
          onAction={() => navigate('/app/vehicles')}
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

  const categoryLabel = vehicle.category === VehicleCategory.DefencePlated ? 'Defence Plated' : 'Contracted Private';

  return (
    <PageContainer
      title={`Vehicle - ${vehicle.plate_number}`}
      actions={
        <div style={{ display: 'flex', gap: 'var(--space-2)' }}>
          <button className="btn btn-secondary" onClick={() => navigate(`/app/vehicles/${vehicle.id}/edit`)}>
            Edit
          </button>
          <button className="btn btn-danger" onClick={() => setShowDelete(true)}>
            Delete
          </button>
        </div>
      }
    >
      <div style={{ display: 'flex', gap: 'var(--space-2)', alignItems: 'center', marginBottom: 'var(--space-6)' }}>
        <StatusBadge status={vehicle.status} />
        <StatusBadge status={vehicle.category} label={categoryLabel} />
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: 'var(--space-4)', marginBottom: 'var(--space-6)' }}>
        <KpiCard label="Make / Model" value={`${vehicle.make} ${vehicle.model}`} icon={Truck} />
        <KpiCard label="Year" value={vehicle.year} icon={CalendarDays} />
        <KpiCard label="Fuel Type" value={vehicle.fuel_type.charAt(0).toUpperCase() + vehicle.fuel_type.slice(1)} icon={Fuel} />
        <KpiCard label="Seating Capacity" value={vehicle.seating_capacity ?? 'N/A'} icon={Users} />
      </div>

      <div className="card" style={{ padding: 'var(--space-6)' }}>
        <h3 style={{ fontSize: 'var(--text-lg)', marginBottom: 'var(--space-4)' }}>Vehicle Information</h3>
        {infoRow('Plate Number', vehicle.plate_number)}
        {infoRow('Make', vehicle.make)}
        {infoRow('Model', vehicle.model)}
        {infoRow('Year', vehicle.year)}
        {infoRow('Color', vehicle.color)}
        {infoRow('VIN', vehicle.vin)}
        {infoRow('Engine Number', vehicle.engine_number)}
        {infoRow('Seating Capacity', vehicle.seating_capacity)}
        {infoRow('Fuel Type', vehicle.fuel_type)}
        {infoRow('Registration Expiry', vehicle.registration_expiry)}
        {infoRow('Insurance Expiry', vehicle.insurance_expiry)}
        {vehicle.owner && infoRow('Owner', vehicle.owner.company_name)}
      </div>

      <ConfirmDialog
        open={showDelete}
        title="Delete Vehicle"
        message={`Are you sure you want to delete vehicle ${vehicle.plate_number}? This action cannot be undone.`}
        confirmLabel="Delete"
        onConfirm={handleDelete}
        onCancel={() => setShowDelete(false)}
        isLoading={deleteMutation.isPending}
      />
    </PageContainer>
  );
}
