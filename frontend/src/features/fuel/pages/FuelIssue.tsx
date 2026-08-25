import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Loader2 } from 'lucide-react';
import { issueFuelSchema, type IssueFuelFormData } from '../schemas/fuelSchema';
import { useIssueFuel } from '../hooks/useFuel';
import { useVehicles } from '@/features/vehicles/hooks/useVehicles';
import { PageContainer } from '@/shared/components/ui/PageContainer';
import { LoadingState } from '@/shared/components/ui/LoadingState';
import { useAppShell } from '@/shared/layouts/appShellContext';

export function FuelIssue() {
  const navigate = useNavigate();
  const { setPageTitle } = useAppShell();
  const [serverError, setServerError] = useState<string | null>(null);

  const issueMutation = useIssueFuel();
  const { data: vehiclesData, isLoading: loadingVehicles } = useVehicles({ per_page: 100, status: 'active' });
  const defenceVehicles = (vehiclesData?.data ?? []).filter((v) => v.category === 'defence_plated');

  const {
    register,
    handleSubmit,
    formState: { errors },
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  } = useForm<any>({
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    resolver: zodResolver(issueFuelSchema) as any,
  });

  useEffect(() => {
    setPageTitle('Issue Fuel');
  }, [setPageTitle]);

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
      const data = raw as unknown as IssueFuelFormData;
      await issueMutation.mutateAsync({
        vehicle_id: data.vehicle_id,
        fuel_type: data.fuel_type,
        quantity: data.quantity,
        unit_cost: data.unit_cost || undefined,
        notes: data.notes || undefined,
      });
      navigate('/app/fuel');
    } catch (err: unknown) {
      const errorData =
        err && typeof err === 'object' && 'response' in err
          ? (err as { response: { data: { message?: string } } }).response.data
          : undefined;
      setServerError(errorData?.message ?? 'An error occurred');
    }
  }

  if (loadingVehicles) {
    return <PageContainer title="Issue Fuel"><LoadingState /></PageContainer>;
  }

  return (
    <PageContainer title="Issue Fuel">
      <div className="card" style={{ padding: 'var(--space-6)', maxWidth: 600 }}>
        {serverError && (
          <div role="alert" style={{ padding: 'var(--space-3)', marginBottom: 'var(--space-4)', background: 'var(--color-danger-light)', border: '1px solid var(--red-200)', borderRadius: 'var(--radius-md)', fontSize: 'var(--text-sm)', color: 'var(--color-danger)' }}>
            {serverError}
          </div>
        )}

        <form onSubmit={handleSubmit(onSubmit)} noValidate>
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 'var(--space-4) var(--space-6)' }}>
            <div style={{ gridColumn: '1 / -1' }}>
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
              <label htmlFor="fuel_type" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                Fuel Type <span style={{ color: 'var(--color-danger)' }}>*</span>
              </label>
              <select id="fuel_type" {...register('fuel_type')} style={selectStyle(!!fieldError('fuel_type'))}>
                <option value="">Select type</option>
                <option value="diesel">Diesel</option>
                <option value="petrol">Petrol</option>
              </select>
              {fieldError('fuel_type') && <p role="alert" style={{ fontSize: 'var(--text-xs)', color: 'var(--color-danger)', marginTop: 2 }}>{fieldError('fuel_type')}</p>}
            </div>

            <div>
              <label htmlFor="quantity" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                Quantity (L) <span style={{ color: 'var(--color-danger)' }}>*</span>
              </label>
              <input id="quantity" type="number" step="0.1" min="0" {...register('quantity')} style={inputStyle(!!fieldError('quantity'))} />
              {fieldError('quantity') && <p role="alert" style={{ fontSize: 'var(--text-xs)', color: 'var(--color-danger)', marginTop: 2 }}>{fieldError('quantity')}</p>}
            </div>

            <div>
              <label htmlFor="unit_cost" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                Unit Cost (optional)
              </label>
              <input id="unit_cost" type="number" step="0.01" min="0" {...register('unit_cost')} style={inputStyle(!!fieldError('unit_cost'))} />
            </div>
          </div>

          <div style={{ marginTop: 'var(--space-4)' }}>
            <label htmlFor="notes" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
              Notes
            </label>
            <textarea id="notes" rows={2} {...register('notes')} style={inputStyle(!!fieldError('notes'))} />
          </div>

          <div style={{ display: 'flex', gap: 'var(--space-3)', justifyContent: 'flex-end', paddingTop: 'var(--space-6)', borderTop: '1px solid var(--color-border)', marginTop: 'var(--space-6)' }}>
            <button type="button" className="btn btn-secondary" onClick={() => navigate('/app/fuel')} disabled={issueMutation.isPending}>
              Cancel
            </button>
            <button type="submit" className="btn btn-primary" disabled={issueMutation.isPending}>
              {issueMutation.isPending && <Loader2 size={16} className="spin" />}
              Issue Fuel
            </button>
          </div>
        </form>
      </div>
    </PageContainer>
  );
}
