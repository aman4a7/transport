import { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Loader2 } from 'lucide-react';
import { ownerSchema, type OwnerFormData } from '../schemas/ownerSchema';
import { useOwner, useCreateOwner, useUpdateOwner } from '../hooks/useOwners';
import type { OwnerStatus } from '../types/owner';
import { PageContainer } from '@/shared/components/ui/PageContainer';
import { LoadingState } from '@/shared/components/ui/LoadingState';
import { useAppShell } from '@/shared/layouts/appShellContext';

export function OwnerForm() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const { setPageTitle } = useAppShell();
  const isEditing = !!id;
  const [serverError, setServerError] = useState<string | null>(null);
  const [serverFieldErrors, setServerFieldErrors] = useState<Record<string, string> | null>(null);

  const { data: owner, isLoading: isLoadingOwner } = useOwner(id ? Number(id) : 0);
  const createMutation = useCreateOwner();
  const updateMutation = useUpdateOwner(id ? Number(id) : 0);

  const {
    register,
    handleSubmit,
    setValue,
    formState: { errors },
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  } = useForm<any>({
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    resolver: zodResolver(ownerSchema) as any,
    defaultValues: { status: 'active' },
  });

  useEffect(() => {
    setPageTitle(isEditing ? 'Edit Contractor' : 'Add Contractor');
  }, [setPageTitle, isEditing]);

  useEffect(() => {
    if (owner && isEditing) {
      setValue('company_name', owner.company_name);
      setValue('contact_person', owner.contact_person);
      setValue('phone', owner.phone);
      setValue('email', owner.email);
      setValue('address', owner.address ?? '');
      setValue('status', owner.status);
      setValue('user_id', owner.user_id);
    }
  }, [owner, isEditing, setValue]);

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
      const data = raw as unknown as OwnerFormData;
      const payload = {
        ...data,
        status: data.status as OwnerStatus,
      };
      if (isEditing) {
        await updateMutation.mutateAsync(payload);
      } else {
        await createMutation.mutateAsync(payload);
      }
      navigate('/app/contractors');
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

  if (isEditing && isLoadingOwner) {
    return <PageContainer title="Loading..."><LoadingState /></PageContainer>;
  }

  return (
    <PageContainer title={isEditing ? 'Edit Contractor' : 'Add Contractor'}>
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
              <label htmlFor="company_name" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                Company Name <span style={{ color: 'var(--color-danger)' }}>*</span>
              </label>
              <input id="company_name" type="text" {...register('company_name')} style={inputStyle(!!fieldError('company_name'))} aria-invalid={!!fieldError('company_name')} />
              {fieldError('company_name') && <p role="alert" style={{ fontSize: 'var(--text-xs)', color: 'var(--color-danger)', marginTop: 2 }}>{fieldError('company_name')}</p>}
            </div>

            <div>
              <label htmlFor="contact_person" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                Contact Person <span style={{ color: 'var(--color-danger)' }}>*</span>
              </label>
              <input id="contact_person" type="text" {...register('contact_person')} style={inputStyle(!!fieldError('contact_person'))} aria-invalid={!!fieldError('contact_person')} />
              {fieldError('contact_person') && <p role="alert" style={{ fontSize: 'var(--text-xs)', color: 'var(--color-danger)', marginTop: 2 }}>{fieldError('contact_person')}</p>}
            </div>

            <div>
              <label htmlFor="phone" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                Phone <span style={{ color: 'var(--color-danger)' }}>*</span>
              </label>
              <input id="phone" type="text" {...register('phone')} style={inputStyle(!!fieldError('phone'))} aria-invalid={!!fieldError('phone')} />
              {fieldError('phone') && <p role="alert" style={{ fontSize: 'var(--text-xs)', color: 'var(--color-danger)', marginTop: 2 }}>{fieldError('phone')}</p>}
            </div>

            <div>
              <label htmlFor="email" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                Email <span style={{ color: 'var(--color-danger)' }}>*</span>
              </label>
              <input id="email" type="email" {...register('email')} style={inputStyle(!!fieldError('email'))} aria-invalid={!!fieldError('email')} />
              {fieldError('email') && <p role="alert" style={{ fontSize: 'var(--text-xs)', color: 'var(--color-danger)', marginTop: 2 }}>{fieldError('email')}</p>}
            </div>

            <div>
              <label htmlFor="address" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                Address
              </label>
              <input id="address" type="text" {...register('address')} style={inputStyle(!!fieldError('address'))} />
            </div>

            <div>
              <label htmlFor="status" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                Status
              </label>
              <select id="status" {...register('status')} style={selectStyle(!!fieldError('status'))}>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="suspended">Suspended</option>
              </select>
            </div>

            <div>
              <label htmlFor="user_id" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                User ID
              </label>
              <input id="user_id" type="number" {...register('user_id')} style={inputStyle(!!fieldError('user_id'))} placeholder="Associated user ID" />
            </div>
          </div>

          <div style={{ display: 'flex', gap: 'var(--space-3)', justifyContent: 'flex-end', paddingTop: 'var(--space-6)', borderTop: '1px solid var(--color-border)', marginTop: 'var(--space-6)' }}>
            <button type="button" className="btn btn-secondary" onClick={() => navigate('/app/contractors')} disabled={isPending}>
              Cancel
            </button>
            <button type="submit" className="btn btn-primary" disabled={isPending}>
              {isPending && <Loader2 size={16} className="spin" />}
              {isEditing ? 'Update Contractor' : 'Create Contractor'}
            </button>
          </div>
        </form>
      </div>
    </PageContainer>
  );
}
