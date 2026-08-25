import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Loader2 } from 'lucide-react';
import { createMaintenanceSchema, type CreateMaintenanceFormData } from '../schemas/garageSchema';
import { useCreateMaintenanceRecord, useMaintenanceRecord, useUpdateMaintenanceRecord } from '../hooks/useGarage';
import { useVehicles } from '@/features/vehicles/hooks/useVehicles';
import { PageContainer } from '@/shared/components/ui/PageContainer';
import { LoadingState } from '@/shared/components/ui/LoadingState';
import { useAppShell } from '@/shared/layouts/appShellContext';

export function GarageForm() {
  const navigate = useNavigate();
  const { id } = useParams<{ id: string }>();
  const { setPageTitle } = useAppShell();
  const isEditing = !!id;
  const [serverError, setServerError] = useState<string | null>(null);

  const createMutation = useCreateMaintenanceRecord();
  const updateMutation = useUpdateMaintenanceRecord();
  const { data: existingRecord, isLoading: loadingRecord } = useMaintenanceRecord(id ? Number(id) : 0);
  const { data: vehiclesData, isLoading: loadingVehicles } = useVehicles({ per_page: 100, status: 'active' });
  const defenceVehicles = (vehiclesData?.data ?? []).filter((v) => v.category === 'defence_plated');

  const {
    register,
    handleSubmit,
    formState: { errors },
    reset,
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  } = useForm<any>({
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    resolver: zodResolver(createMaintenanceSchema) as any,
  });

  useEffect(() => {
    setPageTitle(isEditing ? 'Edit Maintenance Record' : 'New Maintenance Record');
  }, [setPageTitle, isEditing]);

  useEffect(() => {
    if (existingRecord) {
      reset({
        vehicle_id: existingRecord.vehicle_id,
        maintenance_type: existingRecord.maintenance_type,
        description: existingRecord.description,
        scheduled_date: existingRecord.scheduled_date,
        cost: existingRecord.cost,
        notes: existingRecord.notes,
        performed_by: existingRecord.performed_by,
      });
    }
  }, [existingRecord, reset]);

  function fieldError(name: string): string | undefined {
    return (errors[name] as { message?: string } | undefined)?.message;
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
  });

  const selectStyle = (hasError: boolean): React.CSSProperties => ({
    ...inputStyle(hasError),
    appearance: 'none',
  });

  async function onSubmit(raw: Record<string, unknown>) {
    setServerError(null);
    try {
      const data = raw as unknown as CreateMaintenanceFormData;
      if (isEditing && id) {
        await updateMutation.mutateAsync({
          id: Number(id),
          data: {
            vehicle_id: data.vehicle_id,
            maintenance_type: data.maintenance_type,
            description: data.description,
            scheduled_date: data.scheduled_date,
            cost: data.cost || undefined,
            notes: data.notes || undefined,
            performed_by: data.performed_by || undefined,
          },
        });
      } else {
        await createMutation.mutateAsync({
          vehicle_id: data.vehicle_id,
          maintenance_type: data.maintenance_type,
          description: data.description,
          scheduled_date: data.scheduled_date,
          cost: data.cost || undefined,
          notes: data.notes || undefined,
          performed_by: data.performed_by || undefined,
        });
      }
      navigate('/app/garage');
    } catch (err: unknown) {
      const errorData =
        err && typeof err === 'object' && 'response' in err
          ? (err as { response: { data: { message?: string } } }).response.data
          : undefined;
      setServerError(errorData?.message ?? 'An error occurred');
    }
  }

  if (loadingRecord || loadingVehicles) {
    return <PageContainer title={isEditing ? 'Edit Maintenance Record' : 'New Maintenance Record'}><LoadingState /></PageContainer>;
  }

  return (
    <PageContainer title={isEditing ? 'Edit Maintenance Record' : 'New Maintenance Record'}>
      <div className="card" style={{ padding: 'var(--space-6)', maxWidth: 700 }}>
        {serverError && (
          <div role="alert" style={{ padding: 'var(--space-3)', marginBottom: 'var(--space-4)', background: 'var(--color-danger-light)', border: '1px solid var(--red-200)', borderRadius: 'var(--radius-md)', fontSize: 'var(--text-sm)', color: 'var(--color-danger)' }}>
            {serverError}
          </div>
        )}

        <form onSubmit={handleSubmit(onSubmit)} noValidate>
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 'var(--space-4) var(--space-6)' }}>
            <div>
              <label htmlFor="vehicle_id" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                Vehicle <span style={{ color: 'var(--color-danger)' }}>*</span>
              </label>
              <select id="vehicle_id" {...register('vehicle_id')} style={selectStyle(!!fieldError('vehicle_id'))}>
                <option value="">Select vehicle</option>
                {defenceVehicles.map((v) => (
                  <option key={v.id} value={v.id}>{v.plate_number} - {v.make} {v.model}</option>
                ))}
              </select>
              {fieldError('vehicle_id') && <p role="alert" style={{ fontSize: 'var(--text-xs)', color: 'var(--color-danger)', marginTop: 2 }}>{fieldError('vehicle_id')}</p>}
            </div>

            <div>
              <label htmlFor="maintenance_type" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                Type <span style={{ color: 'var(--color-danger)' }}>*</span>
              </label>
              <select id="maintenance_type" {...register('maintenance_type')} style={selectStyle(!!fieldError('maintenance_type'))}>
                <option value="">Select type</option>
                <option value="scheduled">Scheduled</option>
                <option value="repair">Repair</option>
                <option value="inspection">Inspection</option>
                <option value="other">Other</option>
              </select>
              {fieldError('maintenance_type') && <p role="alert" style={{ fontSize: 'var(--text-xs)', color: 'var(--color-danger)', marginTop: 2 }}>{fieldError('maintenance_type')}</p>}
            </div>

            <div>
              <label htmlFor="scheduled_date" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                Scheduled Date <span style={{ color: 'var(--color-danger)' }}>*</span>
              </label>
              <input id="scheduled_date" type="date" {...register('scheduled_date')} style={inputStyle(!!fieldError('scheduled_date'))} />
              {fieldError('scheduled_date') && <p role="alert" style={{ fontSize: 'var(--text-xs)', color: 'var(--color-danger)', marginTop: 2 }}>{fieldError('scheduled_date')}</p>}
            </div>

            <div>
              <label htmlFor="cost" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                Cost (ETB)
              </label>
              <input id="cost" type="number" step="0.01" min="0" {...register('cost')} style={inputStyle(!!fieldError('cost'))} />
            </div>
          </div>

          <div style={{ marginTop: 'var(--space-4)' }}>
            <label htmlFor="description" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
              Description <span style={{ color: 'var(--color-danger)' }}>*</span>
            </label>
            <textarea id="description" rows={3} {...register('description')} style={inputStyle(!!fieldError('description'))} placeholder="Describe the maintenance work needed" />
            {fieldError('description') && <p role="alert" style={{ fontSize: 'var(--text-xs)', color: 'var(--color-danger)', marginTop: 2 }}>{fieldError('description')}</p>}
          </div>

          <div style={{ marginTop: 'var(--space-4)' }}>
            <label htmlFor="notes" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
              Notes
            </label>
            <textarea id="notes" rows={2} {...register('notes')} style={inputStyle(!!fieldError('notes'))} />
          </div>

          <div style={{ display: 'flex', gap: 'var(--space-3)', justifyContent: 'flex-end', paddingTop: 'var(--space-6)', borderTop: '1px solid var(--color-border)', marginTop: 'var(--space-6)' }}>
            <button type="button" className="btn btn-secondary" onClick={() => navigate('/app/garage')} disabled={createMutation.isPending || updateMutation.isPending}>
              Cancel
            </button>
            <button type="submit" className="btn btn-primary" disabled={createMutation.isPending || updateMutation.isPending}>
              {(createMutation.isPending || updateMutation.isPending) && <Loader2 size={16} className="spin" />}
              {isEditing ? 'Update Record' : 'Create Record'}
            </button>
          </div>
        </form>
      </div>
    </PageContainer>
  );
}
