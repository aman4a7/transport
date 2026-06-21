import { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Loader2 } from 'lucide-react';
import { routeSchema, type RouteFormData } from '../schemas/routeSchema';
import { useRoute, useCreateRoute, useUpdateRoute } from '../hooks/useRoutes';
import type { CreateRouteData } from '../types/route';
import { PageContainer } from '@/shared/components/ui/PageContainer';
import { LoadingState } from '@/shared/components/ui/LoadingState';
import { useAppShell } from '@/shared/layouts/appShellContext';

export function RouteForm() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const { setPageTitle } = useAppShell();
  const isEditing = !!id;
  const [serverError, setServerError] = useState<string | null>(null);
  const [serverFieldErrors, setServerFieldErrors] = useState<Record<string, string> | null>(null);

  const { data: route, isLoading: isLoadingRoute } = useRoute(id ? Number(id) : 0);
  const createMutation = useCreateRoute();
  const updateMutation = useUpdateRoute(id ? Number(id) : 0);

  const {
    register,
    handleSubmit,
    setValue,
    formState: { errors },
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  } = useForm<any>({
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    resolver: zodResolver(routeSchema) as any,
    defaultValues: {
      status: 'active',
    },
  });

  useEffect(() => {
    setPageTitle(isEditing ? 'Edit Route' : 'Add Route');
  }, [setPageTitle, isEditing]);

  useEffect(() => {
    if (route && isEditing) {
      setValue('name', route.name);
      setValue('code', route.code ?? '');
      setValue('description', route.description ?? '');
      setValue('origin', route.origin);
      setValue('destination', route.destination);
      setValue('distance_km', route.distance_km ?? undefined);
      setValue('estimated_duration_minutes', route.estimated_duration_minutes ?? undefined);
      setValue('capacity', route.capacity ?? undefined);
      setValue('status', route.status);
    }
  }, [route, isEditing, setValue]);

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

  async function onSubmit(raw: Record<string, unknown>) {
    setServerError(null);
    setServerFieldErrors(null);
    try {
      const data = raw as unknown as RouteFormData;
      const payload: CreateRouteData = {
        name: data.name,
        origin: data.origin,
        destination: data.destination,
        status: data.status ?? 'active',
        code: data.code || undefined,
        description: data.description || undefined,
        distance_km: data.distance_km || undefined,
        estimated_duration_minutes: data.estimated_duration_minutes || undefined,
        capacity: data.capacity || undefined,
      };
      if (isEditing) {
        await updateMutation.mutateAsync(payload);
      } else {
        await createMutation.mutateAsync(payload);
      }
      navigate('/app/routes');
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

  if (isEditing && isLoadingRoute) {
    return <PageContainer title="Loading..."><LoadingState /></PageContainer>;
  }

  const selectStyle = (hasError: boolean): React.CSSProperties => ({
    ...inputStyle(hasError),
    appearance: 'none',
  });

  return (
    <PageContainer title={isEditing ? 'Edit Route' : 'Add Route'}>
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
              <label htmlFor="name" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                Name <span style={{ color: 'var(--color-danger)' }}>*</span>
              </label>
              <input id="name" type="text" {...register('name')} style={inputStyle(!!fieldError('name'))} aria-invalid={!!fieldError('name')} />
              {fieldError('name') && <p role="alert" style={{ fontSize: 'var(--text-xs)', color: 'var(--color-danger)', marginTop: 2 }}>{fieldError('name')}</p>}
            </div>

            <div>
              <label htmlFor="code" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                Code
              </label>
              <input id="code" type="text" {...register('code')} style={inputStyle(!!fieldError('code'))} />
            </div>

            <div>
              <label htmlFor="origin" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                Origin <span style={{ color: 'var(--color-danger)' }}>*</span>
              </label>
              <input id="origin" type="text" {...register('origin')} style={inputStyle(!!fieldError('origin'))} aria-invalid={!!fieldError('origin')} />
              {fieldError('origin') && <p role="alert" style={{ fontSize: 'var(--text-xs)', color: 'var(--color-danger)', marginTop: 2 }}>{fieldError('origin')}</p>}
            </div>

            <div>
              <label htmlFor="destination" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                Destination <span style={{ color: 'var(--color-danger)' }}>*</span>
              </label>
              <input id="destination" type="text" {...register('destination')} style={inputStyle(!!fieldError('destination'))} aria-invalid={!!fieldError('destination')} />
              {fieldError('destination') && <p role="alert" style={{ fontSize: 'var(--text-xs)', color: 'var(--color-danger)', marginTop: 2 }}>{fieldError('destination')}</p>}
            </div>

            <div>
              <label htmlFor="distance_km" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                Distance (km)
              </label>
              <input id="distance_km" type="number" step="0.01" {...register('distance_km')} style={inputStyle(!!fieldError('distance_km'))} />
            </div>

            <div>
              <label htmlFor="estimated_duration_minutes" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                Duration (minutes)
              </label>
              <input id="estimated_duration_minutes" type="number" {...register('estimated_duration_minutes')} style={inputStyle(!!fieldError('estimated_duration_minutes'))} />
            </div>

            <div>
              <label htmlFor="capacity" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                Capacity
              </label>
              <input id="capacity" type="number" {...register('capacity')} style={inputStyle(!!fieldError('capacity'))} />
            </div>

            <div>
              <label htmlFor="status" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                Status
              </label>
              <select id="status" {...register('status')} style={selectStyle(!!fieldError('status'))}>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
          </div>

          <div style={{ marginTop: 'var(--space-4)' }}>
            <label htmlFor="description" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
              Description
            </label>
            <textarea id="description" rows={3} {...register('description')} style={inputStyle(!!fieldError('description'))} />
          </div>

          <div style={{ display: 'flex', gap: 'var(--space-3)', justifyContent: 'flex-end', paddingTop: 'var(--space-6)', borderTop: '1px solid var(--color-border)', marginTop: 'var(--space-6)' }}>
            <button type="button" className="btn btn-secondary" onClick={() => navigate('/app/routes')} disabled={isPending}>
              Cancel
            </button>
            <button type="submit" className="btn btn-primary" disabled={isPending}>
              {isPending && <Loader2 size={16} className="spin" />}
              {isEditing ? 'Update Route' : 'Create Route'}
            </button>
          </div>
        </form>
      </div>
    </PageContainer>
  );
}
