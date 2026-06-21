import { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useOwner, useDeleteOwner } from '../hooks/useOwners';
import { PageContainer } from '@/shared/components/ui/PageContainer';
import { StatusBadge } from '@/shared/components/ui/StatusBadge';
import { LoadingState } from '@/shared/components/ui/LoadingState';
import { EmptyState } from '@/shared/components/ui/EmptyState';
import { ConfirmDialog } from '@/shared/components/ui/ConfirmDialog';
import { DataTable } from '@/shared/components/ui/DataTable';
import { useAppShell } from '@/shared/layouts/appShellContext';
import { KpiCard } from '@/shared/components/ui/KpiCard';
import { Building2, User, Phone, Mail } from 'lucide-react';
import type { ColumnDef } from '@/shared/types/table';

export function OwnerDetail() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const { setPageTitle } = useAppShell();
  const [showDelete, setShowDelete] = useState(false);

  const { data: owner, isLoading, isError } = useOwner(id ? Number(id) : 0);
  const deleteMutation = useDeleteOwner();

  useEffect(() => {
    if (owner) {
      setPageTitle(`Contractor - ${owner.company_name}`);
    }
  }, [setPageTitle, owner]);

  async function handleDelete() {
    try {
      await deleteMutation.mutateAsync(Number(id));
      navigate('/app/contractors');
    } finally {
      setShowDelete(false);
    }
  }

  if (isLoading) {
    return <PageContainer title="Contractor Details"><LoadingState type="card" rows={3} /></PageContainer>;
  }

  if (isError || !owner) {
    return (
      <PageContainer title="Contractor Details">
        <EmptyState
          title="Contractor not found"
          description="The contractor you are looking for does not exist or has been removed."
          actionLabel="Back to Contractors"
          onAction={() => navigate('/app/contractors')}
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

  const vehicleColumns: ColumnDef<{ id: number; plate_number: string }>[] = [
    { header: 'ID', accessorKey: 'id' },
    { header: 'Plate Number', accessorKey: 'plate_number' },
  ];

  return (
    <PageContainer
      title={`Contractor - ${owner.company_name}`}
      actions={
        <div style={{ display: 'flex', gap: 'var(--space-2)' }}>
          <button className="btn btn-secondary" onClick={() => navigate(`/app/contractors/${owner.id}/edit`)}>
            Edit
          </button>
          <button className="btn btn-danger" onClick={() => setShowDelete(true)}>
            Delete
          </button>
        </div>
      }
    >
      <div style={{ marginBottom: 'var(--space-6)' }}>
        <StatusBadge status={owner.status} />
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: 'var(--space-4)', marginBottom: 'var(--space-6)' }}>
        <KpiCard label="Company Name" value={owner.company_name} icon={Building2} />
        <KpiCard label="Contact Person" value={owner.contact_person} icon={User} />
        <KpiCard label="Phone" value={owner.phone} icon={Phone} />
        <KpiCard label="Email" value={owner.email} icon={Mail} />
      </div>

      <div className="card" style={{ padding: 'var(--space-6)', marginBottom: 'var(--space-6)' }}>
        <h3 style={{ fontSize: 'var(--text-lg)', marginBottom: 'var(--space-4)' }}>Contractor Information</h3>
        {infoRow('Company Name', owner.company_name)}
        {infoRow('Contact Person', owner.contact_person)}
        {infoRow('Phone', owner.phone)}
        {infoRow('Email', owner.email)}
        {infoRow('Address', owner.address)}
        {infoRow('Status', owner.status)}
      </div>

      {owner.vehicles && owner.vehicles.length > 0 && (
        <div className="card" style={{ padding: 'var(--space-6)' }}>
          <h3 style={{ fontSize: 'var(--text-lg)', marginBottom: 'var(--space-4)' }}>Vehicles ({owner.vehicles.length})</h3>
          <DataTable
            data={owner.vehicles}
            columns={vehicleColumns}
            onRowClick={(v) => navigate(`/app/vehicles/${v.id}`)}
          />
        </div>
      )}

      <ConfirmDialog
        open={showDelete}
        title="Delete Contractor"
        message={`Are you sure you want to delete contractor ${owner.company_name}? This action cannot be undone.`}
        confirmLabel="Delete"
        onConfirm={handleDelete}
        onCancel={() => setShowDelete(false)}
        isLoading={deleteMutation.isPending}
      />
    </PageContainer>
  );
}
