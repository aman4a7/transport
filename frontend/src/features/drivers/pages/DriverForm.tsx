import { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Loader2 } from 'lucide-react';
import { driverSchema, type DriverFormData } from '../schemas/driverSchema';
import { useDriver, useCreateDriver, useUpdateDriver } from '../hooks/useDrivers';
import type { DriverStatus } from '../types/driver';
import { PageContainer } from '@/shared/components/ui/PageContainer';
import { LoadingState } from '@/shared/components/ui/LoadingState';
import { useAppShell } from '@/shared/layouts/appShellContext';

export function DriverForm() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const { setPageTitle } = useAppShell();
  const isEditing = !!id;
  const [serverError, setServerError] = useState<string | null>(null);
  const [serverFieldErrors, setServerFieldErrors] = useState<Record<string, string> | null>(null);

  const { data: driver, isLoading: isLoadingDriver } = useDriver(id ? Number(id) : 0);
  const createMutation = useCreateDriver();
  const updateMutation = useUpdateDriver(id ? Number(id) : 0);

  const {
    register,
    handleSubmit,
    setValue,
    formState: { errors },
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  } = useForm<any>({
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    resolver: zodResolver(driverSchema) as any,
    defaultValues: { status: 'active' },
  });

  useEffect(() => {
    setPageTitle(isEditing ? 'Edit Driver' : 'Add Driver');
  }, [setPageTitle, isEditing]);

  useEffect(() => {
    if (driver && isEditing) {
      setValue('license_number', driver.license_number);
      setValue('license_category', driver.license_category);
      setValue('license_expiry', driver.license_expiry);
      setValue('status', driver.status);
      setValue('user_id', driver.user_id);
      setValue('medical_expiry', driver.medical_expiry ?? '');
      setValue('assigned_vehicle_id', driver.assigned_vehicle_id);
    }
  }, [driver, isEditing, setValue]);

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

  const selectStyle = (hasError: boolean): React.CSSProperties => ({
    ...inputStyle(hasError),
    appearance: 'none',
  });

  async function onSubmit(raw: Record<string, unknown>) {
    setServerError(null);
    setServerFieldErrors(null);
    try {
      const data = raw as unknown as DriverFormData;
      const payload = {
        ...data,
        status: data.status as DriverStatus,
      };
      if (isEditing) {
        await updateMutation.mutateAsync(payload);
      } else {
        await createMutation.mutateAsync(payload);
      }
      navigate('/app/drivers');
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

  if (isEditing && isLoadingDriver) {
    return <PageContainer title="Loading..."><LoadingState /></PageContainer>;
  }

  return (
    <PageContainer title={isEditing ? 'Edit Driver' : 'Add Driver'}>
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
              <label htmlFor="license_number" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                License Number <span style={{ color: 'var(--color-danger)' }}>*</span>
              </label>
              <input id="license_number" type="text" {...register('license_number')} style={inputStyle(!!fieldError('license_number'))} aria-invalid={!!fieldError('license_number')} />
              {fieldError('license_number') && <p role="alert" style={{ fontSize: 'var(--text-xs)', color: 'var(--color-danger)', marginTop: 2 }}>{fieldError('license_number')}</p>}
            </div>

            <div>
              <label htmlFor="license_category" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                License Category <span style={{ color: 'var(--color-danger)' }}>*</span>
              </label>
              <select id="license_category" {...register('license_category')} style={selectStyle(!!fieldError('license_category'))} aria-invalid={!!fieldError('license_category')}>
                <option value="">Select...</option>
                <option value="light">Light</option>
                <option value="medium">Medium</option>
                <option value="heavy">Heavy</option>
                <option value="trailer">Trailer</option>
              </select>
              {fieldError('license_category') && <p role="alert" style={{ fontSize: 'var(--text-xs)', color: 'var(--color-danger)', marginTop: 2 }}>{fieldError('license_category')}</p>}
            </div>

            <div>
              <label htmlFor="license_expiry" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                License Expiry <span style={{ color: 'var(--color-danger)' }}>*</span>
              </label>
              <input id="license_expiry" type="date" {...register('license_expiry')} style={inputStyle(!!fieldError('license_expiry'))} aria-invalid={!!fieldError('license_expiry')} />
              {fieldError('license_expiry') && <p role="alert" style={{ fontSize: 'var(--text-xs)', color: 'var(--color-danger)', marginTop: 2 }}>{fieldError('license_expiry')}</p>}
            </div>

            <div>
              <label htmlFor="medical_expiry" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                Medical Expiry
              </label>
              <input id="medical_expiry" type="date" {...register('medical_expiry')} style={inputStyle(!!fieldError('medical_expiry'))} />
            </div>

            <div>
              <label htmlFor="status" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                Status
              </label>
              <select id="status" {...register('status')} style={selectStyle(!!fieldError('status'))}>
                <option value="active">Active</option>
                <option value="suspended">Suspended</option>
                <option value="expired">Expired</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>

            <div>
              <label htmlFor="assigned_vehicle_id" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                Assigned Vehicle ID
              </label>
              <input id="assigned_vehicle_id" type="number" {...register('assigned_vehicle_id')} style={inputStyle(!!fieldError('assigned_vehicle_id'))} placeholder="Vehicle ID" />
            </div>

            <div>
              <label htmlFor="user_id" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                User ID
              </label>
              <input id="user_id" type="number" {...register('user_id')} style={inputStyle(!!fieldError('user_id'))} placeholder="Associated user ID" />
            </div>
          </div>

          <div style={{ display: 'flex', gap: 'var(--space-3)', justifyContent: 'flex-end', paddingTop: 'var(--space-6)', borderTop: '1px solid var(--color-border)', marginTop: 'var(--space-6)' }}>
            <button type="button" className="btn btn-secondary" onClick={() => navigate('/app/drivers')} disabled={isPending}>
              Cancel
            </button>
            <button type="submit" className="btn btn-primary" disabled={isPending}>
              {isPending && <Loader2 size={16} className="spin" />}
              {isEditing ? 'Update Driver' : 'Create Driver'}
            </button>
          </div>
        </form>
      </div>
    </PageContainer>
  );
}
