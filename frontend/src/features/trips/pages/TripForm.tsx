import { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Loader2 } from 'lucide-react';
import { tripSchema, type TripFormData } from '../schemas/tripSchema';
import { useTrip, useCreateTrip, useUpdateTrip } from '../hooks/useTrips';
import { useRoutes } from '@/features/routes/hooks/useRoutes';
import { useVehicles } from '@/features/vehicles/hooks/useVehicles';
import { useDrivers } from '@/features/drivers/hooks/useDrivers';
import { usePassengers } from '@/features/passengers/hooks/usePassengers';
import type { CreateTripData } from '../types/trip';
import { PageContainer } from '@/shared/components/ui/PageContainer';
import { LoadingState } from '@/shared/components/ui/LoadingState';
import { useAppShell } from '@/shared/layouts/appShellContext';

export function TripForm() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const { setPageTitle } = useAppShell();
  const isEditing = !!id;
  const [serverError, setServerError] = useState<string | null>(null);
  const [serverFieldErrors, setServerFieldErrors] = useState<Record<string, string> | null>(null);
  const { data: trip, isLoading: isLoadingTrip } = useTrip(id ? Number(id) : 0);
  const [selectedPassengers, setSelectedPassengers] = useState<number[]>([]);
  const createMutation = useCreateTrip();
  const updateMutation = useUpdateTrip(id ? Number(id) : 0);

  const { data: routesData } = useRoutes({ per_page: 100 });
  const { data: vehiclesData } = useVehicles({ per_page: 100 });
  const { data: driversData } = useDrivers({ per_page: 100 });
  const { data: passengersData } = usePassengers({ per_page: 100 });

  const routes = routesData?.data ?? [];
  const vehicles = vehiclesData?.data ?? [];
  const drivers = driversData?.data ?? [];
  const passengers = passengersData?.data ?? [];

  const {
    register,
    handleSubmit,
    setValue,
    formState: { errors },
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  } = useForm<any>({
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    resolver: zodResolver(tripSchema) as any,
  });

  useEffect(() => {
    setPageTitle(isEditing ? 'Edit Trip' : 'Schedule Trip');
  }, [setPageTitle, isEditing]);

  useEffect(() => {
    if (trip && isEditing) {
      setValue('route_id', trip.route_id);
      setValue('vehicle_id', trip.vehicle_id);
      setValue('driver_id', trip.driver_id);
      setValue('scheduled_date', trip.scheduled_date);
      setValue('departure_time', trip.departure_time);
      setValue('estimated_arrival_time', trip.estimated_arrival_time ?? '');
      setValue('notes', trip.notes ?? '');
      if (trip.assignments) {
        // eslint-disable-next-line react-hooks/set-state-in-effect
        setSelectedPassengers(trip.assignments.map((a) => a.passenger_id));
      }
    }
    }, [trip, isEditing, setValue, setSelectedPassengers]);

  function fieldError(name: string): string | undefined {
    const fieldErr = errors[name] as { message?: string } | undefined;
    return fieldErr?.message ?? serverFieldErrors?.[name];
  }

  const inputStyle = (hasError: boolean): React.CSSProperties => ({
    width: '100%',
    padding: 'var(--space-2) var(--space-3)',
    fontSize: 'var(--text-sm)',
    color: 'var(--color-text)',
    background: 'var(--color-surface)',
    border: `1px solid ${hasError ? 'var(--color-danger)' : 'var(--color-border)'}`,
    borderRadius: 'var(--radius-md)',
    outline: 'none',
    transition: 'border-color var(--transition-fast)',
  });

  function togglePassenger(passengerId: number) {
    setSelectedPassengers((prev) =>
      prev.includes(passengerId)
        ? prev.filter((id) => id !== passengerId)
        : [...prev, passengerId],
    );
  }

  async function onSubmit(raw: Record<string, unknown>) {
    setServerError(null);
    setServerFieldErrors(null);
    try {
      const data = raw as unknown as TripFormData;
      const payload: CreateTripData = {
        route_id: data.route_id,
        vehicle_id: data.vehicle_id,
        driver_id: data.driver_id,
        scheduled_date: data.scheduled_date,
        departure_time: data.departure_time,
        estimated_arrival_time: data.estimated_arrival_time || undefined,
        notes: data.notes || undefined,
        passenger_ids: selectedPassengers.length > 0 ? selectedPassengers : undefined,
      };
      if (isEditing) {
        await updateMutation.mutateAsync(payload);
      } else {
        await createMutation.mutateAsync(payload);
      }
      navigate('/app/trips');
    } catch (err: unknown) {
      const errorData =
        err && typeof err === 'object' && 'response' in err
          ? (err as { response: { data: { message?: string; errors?: Record<string, string[]> } } }).response
              .data
          : undefined;
      if (errorData?.errors) {
        const flat: Record<string, string> = {};
        for (const [key, msgs] of Object.entries(errorData.errors)) {
          flat[key] = msgs[0];
        }
        setServerFieldErrors(flat);
      }
      setServerError(errorData?.message ?? 'An error occurred');
    }
  }

  const isPending = createMutation.isPending || updateMutation.isPending;

  if (isEditing && isLoadingTrip) {
    return <PageContainer title="Loading..."><LoadingState /></PageContainer>;
  }

  const selectStyle = (hasError: boolean): React.CSSProperties => ({
    ...inputStyle(hasError),
    appearance: 'none',
  });

  return (
    <PageContainer title={isEditing ? 'Edit Trip' : 'Schedule Trip'}>
      <div className="card" style={{ padding: 'var(--space-6)', maxWidth: 720 }}>
        {serverError && !serverFieldErrors && (
          <div
            role="alert"
            style={{
              padding: 'var(--space-3)',
              marginBottom: 'var(--space-4)',
              background: 'var(--color-danger-light)',
              border: '1px solid var(--red-200)',
              borderRadius: 'var(--radius-md)',
              fontSize: 'var(--text-sm)',
              color: 'var(--color-danger)',
            }}
          >
            {serverError}
          </div>
        )}

        <form onSubmit={handleSubmit(onSubmit)} noValidate>
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 'var(--space-4) var(--space-6)' }}>
            <div>
              <label htmlFor="route_id" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                Route <span style={{ color: 'var(--color-danger)' }}>*</span>
              </label>
              <select id="route_id" {...register('route_id')} style={selectStyle(!!fieldError('route_id'))}>
                <option value="">Select route</option>
                {routes.map((r) => (
                  <option key={r.id} value={r.id}>{r.name}</option>
                ))}
              </select>
              {fieldError('route_id') && <p role="alert" style={{ fontSize: 'var(--text-xs)', color: 'var(--color-danger)', marginTop: 2 }}>{fieldError('route_id')}</p>}
            </div>

            <div>
              <label htmlFor="vehicle_id" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                Vehicle <span style={{ color: 'var(--color-danger)' }}>*</span>
              </label>
              <select id="vehicle_id" {...register('vehicle_id')} style={selectStyle(!!fieldError('vehicle_id'))}>
                <option value="">Select vehicle</option>
                {vehicles.map((v) => (
                  <option key={v.id} value={v.id}>{v.plate_number} - {v.make} {v.model}</option>
                ))}
              </select>
              {fieldError('vehicle_id') && <p role="alert" style={{ fontSize: 'var(--text-xs)', color: 'var(--color-danger)', marginTop: 2 }}>{fieldError('vehicle_id')}</p>}
            </div>

            <div>
              <label htmlFor="driver_id" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                Driver <span style={{ color: 'var(--color-danger)' }}>*</span>
              </label>
              <select id="driver_id" {...register('driver_id')} style={selectStyle(!!fieldError('driver_id'))}>
                <option value="">Select driver</option>
                {drivers.map((d) => (
                  <option key={d.id} value={d.id}>Driver #{d.id}</option>
                ))}
              </select>
              {fieldError('driver_id') && <p role="alert" style={{ fontSize: 'var(--text-xs)', color: 'var(--color-danger)', marginTop: 2 }}>{fieldError('driver_id')}</p>}
            </div>

            <div>
              <label htmlFor="scheduled_date" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                Scheduled Date <span style={{ color: 'var(--color-danger)' }}>*</span>
              </label>
              <input id="scheduled_date" type="date" {...register('scheduled_date')} style={inputStyle(!!fieldError('scheduled_date'))} />
              {fieldError('scheduled_date') && <p role="alert" style={{ fontSize: 'var(--text-xs)', color: 'var(--color-danger)', marginTop: 2 }}>{fieldError('scheduled_date')}</p>}
            </div>

            <div>
              <label htmlFor="departure_time" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                Departure Time <span style={{ color: 'var(--color-danger)' }}>*</span>
              </label>
              <input id="departure_time" type="time" {...register('departure_time')} style={inputStyle(!!fieldError('departure_time'))} />
              {fieldError('departure_time') && <p role="alert" style={{ fontSize: 'var(--text-xs)', color: 'var(--color-danger)', marginTop: 2 }}>{fieldError('departure_time')}</p>}
            </div>

            <div>
              <label htmlFor="estimated_arrival_time" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                Estimated Arrival
              </label>
              <input id="estimated_arrival_time" type="time" {...register('estimated_arrival_time')} style={inputStyle(!!fieldError('estimated_arrival_time'))} />
            </div>
          </div>

          <div style={{ marginTop: 'var(--space-4)' }}>
            <label htmlFor="notes" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
              Notes
            </label>
            <textarea id="notes" rows={2} {...register('notes')} style={inputStyle(!!fieldError('notes'))} />
          </div>

          <div style={{ marginTop: 'var(--space-4)' }}>
            <label style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-2)' }}>
              Passengers
            </label>
            <div style={{ display: 'flex', flexWrap: 'wrap', gap: 'var(--space-2)', maxHeight: 200, overflowY: 'auto', padding: 'var(--space-2)', border: '1px solid var(--color-border)', borderRadius: 'var(--radius-md)' }}>
              {passengers.length === 0 && (
                <span style={{ fontSize: 'var(--text-sm)', color: 'var(--color-text-secondary)', padding: 'var(--space-2)' }}>No passengers available</span>
              )}
              {passengers.map((p) => (
                <label
                  key={p.id}
                  style={{
                    display: 'flex',
                    alignItems: 'center',
                    gap: 'var(--space-1)',
                    padding: 'var(--space-1) var(--space-2)',
                    background: selectedPassengers.includes(p.id) ? 'var(--color-primary-light)' : 'var(--color-surface)',
                    border: '1px solid var(--color-border)',
                    borderRadius: 'var(--radius-sm)',
                    cursor: 'pointer',
                    fontSize: 'var(--text-sm)',
                  }}
                >
                  <input
                    type="checkbox"
                    checked={selectedPassengers.includes(p.id)}
                    onChange={() => togglePassenger(p.id)}
                  />
                  {p.first_name} {p.last_name}
                </label>
              ))}
            </div>
          </div>

          <div style={{ display: 'flex', gap: 'var(--space-3)', justifyContent: 'flex-end', paddingTop: 'var(--space-6)', borderTop: '1px solid var(--color-border)', marginTop: 'var(--space-6)' }}>
            <button type="button" className="btn btn-secondary" onClick={() => navigate('/app/trips')} disabled={isPending}>
              Cancel
            </button>
            <button type="submit" className="btn btn-primary" disabled={isPending}>
              {isPending && <Loader2 size={16} className="spin" />}
              {isEditing ? 'Update Trip' : 'Schedule Trip'}
            </button>
          </div>
        </form>
      </div>
    </PageContainer>
  );
}
