import { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useTrip, useDeleteTrip, useStartTrip, useCompleteTrip, useCancelTrip } from '../hooks/useTrips';
import { TripStatus, type TripAssignment } from '../types/trip';
import { PageContainer } from '@/shared/components/ui/PageContainer';
import { StatusBadge } from '@/shared/components/ui/StatusBadge';
import { LoadingState } from '@/shared/components/ui/LoadingState';
import { EmptyState } from '@/shared/components/ui/EmptyState';
import { ConfirmDialog } from '@/shared/components/ui/ConfirmDialog';
import { useAppShell } from '@/shared/layouts/appShellContext';
import { KpiCard } from '@/shared/components/ui/KpiCard';
import { MapPin, Truck, CalendarDays, Clock, Users } from 'lucide-react';

const statusLabel: Record<string, string> = {
  scheduled: 'Scheduled',
  in_progress: 'In Progress',
  completed: 'Completed',
  cancelled: 'Cancelled',
};

export function TripDetail() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const { setPageTitle } = useAppShell();
  const [showDelete, setShowDelete] = useState(false);

  const { data: trip, isLoading, isError } = useTrip(id ? Number(id) : 0);
  const deleteMutation = useDeleteTrip();
  const startMutation = useStartTrip();
  const completeMutation = useCompleteTrip();
  const cancelMutation = useCancelTrip();

  useEffect(() => {
    if (trip) {
      setPageTitle(`Trip - ${trip.route?.name ?? '#' + trip.id}`);
    }
  }, [setPageTitle, trip]);

  async function handleDelete() {
    try {
      await deleteMutation.mutateAsync(Number(id));
      navigate('/app/trips');
    } finally {
      setShowDelete(false);
    }
  }

  if (isLoading) {
    return <PageContainer title="Trip Details"><LoadingState type="card" rows={3} /></PageContainer>;
  }

  if (isError || !trip) {
    return (
      <PageContainer title="Trip Details">
        <EmptyState
          title="Trip not found"
          description="The trip you are looking for does not exist or has been removed."
          actionLabel="Back to Trips"
          onAction={() => navigate('/app/trips')}
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
      title={`Trip - ${trip.route?.name ?? '#' + trip.id}`}
      actions={
        <div style={{ display: 'flex', gap: 'var(--space-2)' }}>
          {trip.status === TripStatus.Scheduled && (
            <>
              <button className="btn btn-primary" onClick={() => startMutation.mutate(Number(id))}>
                Start Trip
              </button>
              <button className="btn btn-danger" onClick={() => cancelMutation.mutate(Number(id))}>
                Cancel
              </button>
            </>
          )}
          {trip.status === TripStatus.InProgress && (
            <button className="btn btn-primary" onClick={() => completeMutation.mutate(Number(id))}>
              Complete Trip
            </button>
          )}
          <button className="btn btn-secondary" onClick={() => navigate(`/app/trips/${trip.id}/edit`)}>
            Edit
          </button>
          {trip.status !== TripStatus.InProgress && trip.status !== TripStatus.Completed && (
            <button className="btn btn-danger" onClick={() => setShowDelete(true)}>
              Delete
            </button>
          )}
        </div>
      }
    >
      <div style={{ marginBottom: 'var(--space-6)' }}>
        <StatusBadge status={trip.status} label={statusLabel[trip.status] ?? trip.status} />
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: 'var(--space-4)', marginBottom: 'var(--space-6)' }}>
        <KpiCard label="Route" value={trip.route?.name ?? '-'} icon={MapPin} />
        <KpiCard label="Vehicle" value={trip.vehicle?.plate_number ?? '-'} icon={Truck} />
        <KpiCard label="Date" value={new Date(trip.scheduled_date).toLocaleDateString()} icon={CalendarDays} />
        <KpiCard label="Departure" value={trip.departure_time} icon={Clock} />
      </div>

      <div className="card" style={{ padding: 'var(--space-6)', marginBottom: 'var(--space-4)' }}>
        <h3 style={{ fontSize: 'var(--text-lg)', marginBottom: 'var(--space-4)' }}>Trip Information</h3>
        {infoRow('Route', trip.route?.name ?? '-')}
        {infoRow('Vehicle', trip.vehicle?.plate_number ?? '-')}
        {infoRow('Scheduled Date', trip.scheduled_date)}
        {infoRow('Departure Time', trip.departure_time)}
        {infoRow('Estimated Arrival', trip.estimated_arrival_time)}
        {infoRow('Actual Departure', trip.actual_departure_time ? new Date(trip.actual_departure_time).toLocaleString() : null)}
        {infoRow('Actual Arrival', trip.actual_arrival_time ? new Date(trip.actual_arrival_time).toLocaleString() : null)}
        {infoRow('Notes', trip.notes)}
      </div>

      <div className="card" style={{ padding: 'var(--space-6)' }}>
        <h3 style={{ fontSize: 'var(--text-lg)', marginBottom: 'var(--space-4)' }}>
          Passengers ({trip.assignments?.length ?? 0})
        </h3>
        {(!trip.assignments || trip.assignments.length === 0) && (
          <span style={{ fontSize: 'var(--text-sm)', color: 'var(--color-text-secondary)' }}>No passengers assigned.</span>
        )}
        {trip.assignments?.map((a: TripAssignment) => (
          <div key={a.id} style={{ display: 'flex', gap: 'var(--space-2)', padding: 'var(--space-2) 0', borderBottom: '1px solid var(--color-border)', alignItems: 'center' }}>
            <Users size={16} />
            <span style={{ fontSize: 'var(--text-sm)' }}>
              {a.passenger ? `${a.passenger.first_name} ${a.passenger.last_name}` : `Passenger #${a.passenger_id}`}
            </span>
            <StatusBadge status={a.status} />
          </div>
        ))}
      </div>

      <ConfirmDialog
        open={showDelete}
        title="Delete Trip"
        message="Are you sure you want to delete this trip? This action cannot be undone."
        confirmLabel="Delete"
        onConfirm={handleDelete}
        onCancel={() => setShowDelete(false)}
        isLoading={deleteMutation.isPending}
      />
    </PageContainer>
  );
}
