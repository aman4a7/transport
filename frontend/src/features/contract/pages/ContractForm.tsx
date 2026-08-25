import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Loader2 } from 'lucide-react';
import { createContractSchema, type CreateContractFormData } from '../schemas';
import { useCreateContract, useContract, useUpdateContract } from '../hooks/useContracts';
import { useVehicles } from '@/features/vehicles/hooks/useVehicles';
import { useOwners } from '@/features/owners/hooks/useOwners';
import { PageContainer } from '@/shared/components/ui/PageContainer';
import { LoadingState } from '@/shared/components/ui/LoadingState';
import { useAppShell } from '@/shared/layouts/appShellContext';

export function ContractForm() {
  const navigate = useNavigate();
  const { id } = useParams<{ id: string }>();
  const { setPageTitle } = useAppShell();
  const isEditing = !!id;
  const [serverError, setServerError] = useState<string | null>(null);

  const createMutation = useCreateContract();
  const updateMutation = useUpdateContract();
  const { data: existingContract, isLoading: loadingContract } = useContract(id ? Number(id) : 0);
  const { data: vehiclesData, isLoading: loadingVehicles } = useVehicles({ per_page: 100, status: 'active' });
  const { data: ownersData, isLoading: loadingOwners } = useOwners({ per_page: 100 });

  const contractedVehicles = (vehiclesData?.data ?? []).filter((v) => v.category === 'contracted_private');

  const {
    register,
    handleSubmit,
    formState: { errors },
    reset,
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  } = useForm<any>({
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    resolver: zodResolver(createContractSchema) as any,
  });

  useEffect(() => {
    setPageTitle(isEditing ? 'Edit Contract' : 'New Contract');
  }, [setPageTitle, isEditing]);

  useEffect(() => {
    if (existingContract) {
      reset({
        vehicle_id: existingContract.vehicle_id,
        owner_id: existingContract.owner_id,
        start_date: existingContract.start_date,
        end_date: existingContract.end_date,
        contract_value: existingContract.contract_value,
        payment_terms: existingContract.payment_terms,
        notes: existingContract.notes,
      });
    }
  }, [existingContract, reset]);

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
      const data = raw as unknown as CreateContractFormData;
      if (isEditing && id) {
        await updateMutation.mutateAsync({
          id: Number(id),
          data: {
            vehicle_id: data.vehicle_id,
            owner_id: data.owner_id,
            start_date: data.start_date,
            end_date: data.end_date,
            contract_value: data.contract_value || undefined,
            payment_terms: data.payment_terms || undefined,
            notes: data.notes || undefined,
          },
        });
      } else {
        await createMutation.mutateAsync({
          vehicle_id: data.vehicle_id,
          owner_id: data.owner_id,
          start_date: data.start_date,
          end_date: data.end_date,
          contract_value: data.contract_value || undefined,
          payment_terms: data.payment_terms || undefined,
          notes: data.notes || undefined,
        });
      }
      navigate('/app/contracts');
    } catch (err: unknown) {
      const errorData =
        err && typeof err === 'object' && 'response' in err
          ? (err as { response: { data: { message?: string } } }).response.data
          : undefined;
      setServerError(errorData?.message ?? 'An error occurred');
    }
  }

  if (loadingContract || loadingVehicles || loadingOwners) {
    return <PageContainer title={isEditing ? 'Edit Contract' : 'New Contract'}><LoadingState /></PageContainer>;
  }

  return (
    <PageContainer title={isEditing ? 'Edit Contract' : 'New Contract'}>
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
                {contractedVehicles.map((v) => (
                  <option key={v.id} value={v.id}>{v.plate_number} - {v.make} {v.model}</option>
                ))}
              </select>
              {fieldError('vehicle_id') && <p role="alert" style={{ fontSize: 'var(--text-xs)', color: 'var(--color-danger)', marginTop: 2 }}>{fieldError('vehicle_id')}</p>}
            </div>

            <div>
              <label htmlFor="owner_id" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                Contractor <span style={{ color: 'var(--color-danger)' }}>*</span>
              </label>
              <select id="owner_id" {...register('owner_id')} style={selectStyle(!!fieldError('owner_id'))}>
                <option value="">Select contractor</option>
                {ownersData?.data?.map((o) => (
                  <option key={o.id} value={o.id}>{o.company_name}</option>
                ))}
              </select>
              {fieldError('owner_id') && <p role="alert" style={{ fontSize: 'var(--text-xs)', color: 'var(--color-danger)', marginTop: 2 }}>{fieldError('owner_id')}</p>}
            </div>

            <div>
              <label htmlFor="start_date" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                Start Date <span style={{ color: 'var(--color-danger)' }}>*</span>
              </label>
              <input id="start_date" type="date" {...register('start_date')} style={inputStyle(!!fieldError('start_date'))} />
              {fieldError('start_date') && <p role="alert" style={{ fontSize: 'var(--text-xs)', color: 'var(--color-danger)', marginTop: 2 }}>{fieldError('start_date')}</p>}
            </div>

            <div>
              <label htmlFor="end_date" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                End Date <span style={{ color: 'var(--color-danger)' }}>*</span>
              </label>
              <input id="end_date" type="date" {...register('end_date')} style={inputStyle(!!fieldError('end_date'))} />
              {fieldError('end_date') && <p role="alert" style={{ fontSize: 'var(--text-xs)', color: 'var(--color-danger)', marginTop: 2 }}>{fieldError('end_date')}</p>}
            </div>

            <div>
              <label htmlFor="contract_value" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                Contract Value (ETB)
              </label>
              <input id="contract_value" type="number" step="0.01" min="0" {...register('contract_value')} style={inputStyle(!!fieldError('contract_value'))} />
            </div>
          </div>

          <div style={{ marginTop: 'var(--space-4)' }}>
            <label htmlFor="payment_terms" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
              Payment Terms
            </label>
            <textarea id="payment_terms" rows={2} {...register('payment_terms')} style={inputStyle(!!fieldError('payment_terms'))} placeholder="Describe payment terms and schedule" />
          </div>

          <div style={{ marginTop: 'var(--space-4)' }}>
            <label htmlFor="notes" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
              Notes
            </label>
            <textarea id="notes" rows={3} {...register('notes')} style={inputStyle(!!fieldError('notes'))} placeholder="Additional notes about this contract" />
          </div>

          <div style={{ display: 'flex', gap: 'var(--space-3)', justifyContent: 'flex-end', paddingTop: 'var(--space-6)', borderTop: '1px solid var(--color-border)', marginTop: 'var(--space-6)' }}>
            <button type="button" className="btn btn-secondary" onClick={() => navigate('/app/contracts')} disabled={createMutation.isPending || updateMutation.isPending}>
              Cancel
            </button>
            <button type="submit" className="btn btn-primary" disabled={createMutation.isPending || updateMutation.isPending}>
              {(createMutation.isPending || updateMutation.isPending) && <Loader2 size={16} className="spin" />}
              {isEditing ? 'Update Contract' : 'Create Contract'}
            </button>
          </div>
        </form>
      </div>
    </PageContainer>
  );
}
