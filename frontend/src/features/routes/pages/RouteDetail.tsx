import { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useRoute, useDeleteRoute } from '../hooks/useRoutes';
import { PageContainer } from '@/shared/components/ui/PageContainer';
import { StatusBadge } from '@/shared/components/ui/StatusBadge';
import { LoadingState } from '@/shared/components/ui/LoadingState';
import { EmptyState } from '@/shared/components/ui/EmptyState';
import { ConfirmDialog } from '@/shared/components/ui/ConfirmDialog';
import { useAppShell } from '@/shared/layouts/appShellContext';
import { KpiCard } from '@/shared/components/ui/KpiCard';
import { MapPin, Navigation, Clock, Users } from 'lucide-react';

export function RouteDetail() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const { setPageTitle } = useAppShell();
  const [showDelete, setShowDelete] = useState(false);

  const { data: route, isLoading, isError } = useRoute(id ? Number(id) : 0);
  const deleteMutation = useDeleteRoute();

  useEffect(() => {
    if (route) {
      setPageTitle(`Route - ${route.name}`);
    }
  }, [setPageTitle, route]);

  async function handleDelete() {
    try {
      await deleteMutation.mutateAsync(Number(id));
      navigate('/app/routes');
    } finally {
      setShowDelete(false);
    }
  }

  if (isLoading) {
    return <PageContainer title="Route Details"><LoadingState type="card" rows={3} /></PageContainer>;
  }

  if (isError || !route) {
    return (
      <PageContainer title="Route Details">
        <EmptyState
          title="Route not found"
          description="The route you are looking for does not exist or has been removed."
          actionLabel="Back to Routes"
          onAction={() => navigate('/app/routes')}
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
      title={`Route - ${route.name}`}
      actions={
        <div style={{ display: 'flex', gap: 'var(--space-2)' }}>
          <button className="btn btn-secondary" onClick={() => navigate(`/app/routes/${route.id}/edit`)}>
            Edit
          </button>
          <button className="btn btn-danger" onClick={() => setShowDelete(true)}>
            Delete
          </button>
        </div>
      }
    >
      <div style={{ marginBottom: 'var(--space-6)' }}>
        <StatusBadge status={route.status} />
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: 'var(--space-4)', marginBottom: 'var(--space-6)' }}>
        <KpiCard label="Origin" value={route.origin} icon={MapPin} />
        <KpiCard label="Destination" value={route.destination} icon={Navigation} />
        <KpiCard label="Distance" value={route.distance_km ? `${route.distance_km.toFixed(1)} km` : 'N/A'} icon={Navigation} />
        <KpiCard label="Duration" value={route.estimated_duration_minutes ? `${route.estimated_duration_minutes} min` : 'N/A'} icon={Clock} />
        <KpiCard label="Capacity" value={route.capacity ?? 'N/A'} icon={Users} />
      </div>

      <div className="card" style={{ padding: 'var(--space-6)' }}>
        <h3 style={{ fontSize: 'var(--text-lg)', marginBottom: 'var(--space-4)' }}>Route Information</h3>
        {infoRow('Name', route.name)}
        {infoRow('Code', route.code)}
        {infoRow('Origin', route.origin)}
        {infoRow('Destination', route.destination)}
        {infoRow('Distance (km)', route.distance_km)}
        {infoRow('Estimated Duration (min)', route.estimated_duration_minutes)}
        {infoRow('Capacity', route.capacity)}
        {infoRow('Description', route.description)}
      </div>

      <ConfirmDialog
        open={showDelete}
        title="Delete Route"
        message={`Are you sure you want to delete route "${route.name}"? This action cannot be undone.`}
        confirmLabel="Delete"
        onConfirm={handleDelete}
        onCancel={() => setShowDelete(false)}
        isLoading={deleteMutation.isPending}
      />
    </PageContainer>
  );
}
