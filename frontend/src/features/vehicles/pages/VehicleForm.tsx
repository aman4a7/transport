import { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Loader2 } from 'lucide-react';
import { vehicleSchema, type VehicleFormData } from '../schemas/vehicleSchema';
import { useVehicle, useCreateVehicle, useUpdateVehicle } from '../hooks/useVehicles';
import { VehicleCategory, FuelType, type VehicleStatus, type CreateVehicleData } from '../types/vehicle';
import { PageContainer } from '@/shared/components/ui/PageContainer';
import { LoadingState } from '@/shared/components/ui/LoadingState';
import { useAppShell } from '@/shared/layouts/appShellContext';

export function VehicleForm() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const { setPageTitle } = useAppShell();
  const isEditing = !!id;
  const [serverError, setServerError] = useState<string | null>(null);
  const [serverFieldErrors, setServerFieldErrors] = useState<Record<string, string> | null>(null);

  const { data: vehicle, isLoading: isLoadingVehicle } = useVehicle(id ? Number(id) : 0);
  const createMutation = useCreateVehicle();
  const updateMutation = useUpdateVehicle(id ? Number(id) : 0);

  const {
    register,
    handleSubmit,
    watch,
    setValue,
    formState: { errors },
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  } = useForm<any>({
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    resolver: zodResolver(vehicleSchema) as any,
    defaultValues: {
      category: VehicleCategory.DefencePlated,
      fuel_type: FuelType.Diesel,
      status: 'active',
    },
  });

  const selectedCategory = watch('category');

  useEffect(() => {
    setPageTitle(isEditing ? 'Edit Vehicle' : 'Add Vehicle');
  }, [setPageTitle, isEditing]);

  useEffect(() => {
    if (vehicle && isEditing) {
      setValue('plate_number', vehicle.plate_number);
      setValue('make', vehicle.make);
      setValue('model', vehicle.model);
      setValue('year', vehicle.year);
      setValue('category', vehicle.category);
      setValue('owner_id', vehicle.owner_id);
      setValue('color', vehicle.color ?? '');
      setValue('vin', vehicle.vin ?? '');
      setValue('engine_number', vehicle.engine_number ?? '');
      setValue('seating_capacity', vehicle.seating_capacity ?? undefined);
      setValue('fuel_type', vehicle.fuel_type);
      setValue('status', vehicle.status);
      setValue('registration_expiry', vehicle.registration_expiry ?? '');
      setValue('insurance_expiry', vehicle.insurance_expiry ?? '');
    }
  }, [vehicle, isEditing, setValue]);

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
      const data = raw as unknown as VehicleFormData;
      const payload: CreateVehicleData = {
        plate_number: data.plate_number,
        make: data.make,
        model: data.model,
        year: data.year,
        category: data.category as VehicleCategory,
        fuel_type: (data.fuel_type ?? 'diesel') as FuelType,
        status: (data.status ?? 'active') as VehicleStatus,
        owner_id: data.category === VehicleCategory.DefencePlated ? null : (data.owner_id ?? null),
        color: data.color || undefined,
        vin: data.vin || undefined,
        engine_number: data.engine_number || undefined,
        seating_capacity: data.seating_capacity || undefined,
        registration_expiry: data.registration_expiry || undefined,
        insurance_expiry: data.insurance_expiry || undefined,
      };
      if (isEditing) {
        await updateMutation.mutateAsync(payload);
      } else {
        await createMutation.mutateAsync(payload);
      }
      navigate('/app/vehicles');
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

  if (isEditing && isLoadingVehicle) {
    return <PageContainer title="Loading..."><LoadingState /></PageContainer>;
  }

  const selectStyle = (hasError: boolean): React.CSSProperties => ({
    ...inputStyle(hasError),
    appearance: 'none',
  });

  return (
    <PageContainer title={isEditing ? 'Edit Vehicle' : 'Add Vehicle'}>
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
              <label htmlFor="plate_number" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                Plate Number <span style={{ color: 'var(--color-danger)' }}>*</span>
              </label>
              <input id="plate_number" type="text" {...register('plate_number')} style={inputStyle(!!fieldError('plate_number'))} aria-invalid={!!fieldError('plate_number')} />
              {fieldError('plate_number') && <p role="alert" style={{ fontSize: 'var(--text-xs)', color: 'var(--color-danger)', marginTop: 2 }}>{fieldError('plate_number')}</p>}
            </div>

            <div>
              <label htmlFor="year" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                Year <span style={{ color: 'var(--color-danger)' }}>*</span>
              </label>
              <input id="year" type="number" {...register('year')} style={inputStyle(!!fieldError('year'))} aria-invalid={!!fieldError('year')} />
              {fieldError('year') && <p role="alert" style={{ fontSize: 'var(--text-xs)', color: 'var(--color-danger)', marginTop: 2 }}>{fieldError('year')}</p>}
            </div>

            <div>
              <label htmlFor="make" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                Make <span style={{ color: 'var(--color-danger)' }}>*</span>
              </label>
              <input id="make" type="text" {...register('make')} style={inputStyle(!!fieldError('make'))} aria-invalid={!!fieldError('make')} />
              {fieldError('make') && <p role="alert" style={{ fontSize: 'var(--text-xs)', color: 'var(--color-danger)', marginTop: 2 }}>{fieldError('make')}</p>}
            </div>

            <div>
              <label htmlFor="model" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                Model <span style={{ color: 'var(--color-danger)' }}>*</span>
              </label>
              <input id="model" type="text" {...register('model')} style={inputStyle(!!fieldError('model'))} aria-invalid={!!fieldError('model')} />
              {fieldError('model') && <p role="alert" style={{ fontSize: 'var(--text-xs)', color: 'var(--color-danger)', marginTop: 2 }}>{fieldError('model')}</p>}
            </div>

            <div>
              <label htmlFor="category" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                Category <span style={{ color: 'var(--color-danger)' }}>*</span>
              </label>
              <select id="category" {...register('category')} style={selectStyle(!!fieldError('category'))}>
                <option value={VehicleCategory.DefencePlated}>Defence Plated</option>
                <option value={VehicleCategory.ContractedPrivate}>Contracted Private</option>
              </select>
              {fieldError('category') && <p role="alert" style={{ fontSize: 'var(--text-xs)', color: 'var(--color-danger)', marginTop: 2 }}>{fieldError('category')}</p>}
            </div>

            <div>
              <label htmlFor="fuel_type" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                Fuel Type
              </label>
              <select id="fuel_type" {...register('fuel_type')} style={selectStyle(!!fieldError('fuel_type'))}>
                <option value={FuelType.Diesel}>Diesel</option>
                <option value={FuelType.Petrol}>Petrol</option>
                <option value={FuelType.Electric}>Electric</option>
                <option value={FuelType.Hybrid}>Hybrid</option>
              </select>
            </div>

            <div>
              <label htmlFor="status" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                Status
              </label>
              <select id="status" {...register('status')} style={selectStyle(!!fieldError('status'))}>
                <option value="active">Active</option>
                <option value="in_maintenance">In Maintenance</option>
                <option value="suspended">Suspended</option>
                <option value="decommissioned">Decommissioned</option>
              </select>
            </div>

            <div>
              <label htmlFor="seating_capacity" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                Seating Capacity
              </label>
              <input id="seating_capacity" type="number" {...register('seating_capacity')} style={inputStyle(!!fieldError('seating_capacity'))} />
            </div>

            {selectedCategory === VehicleCategory.ContractedPrivate && (
              <div>
                <label htmlFor="owner_id" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                  Owner ID
                </label>
                <input id="owner_id" type="number" {...register('owner_id')} style={inputStyle(!!fieldError('owner_id'))} placeholder="Enter owner ID" />
                {fieldError('owner_id') && <p role="alert" style={{ fontSize: 'var(--text-xs)', color: 'var(--color-danger)', marginTop: 2 }}>{fieldError('owner_id')}</p>}
              </div>
            )}
          </div>

          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 'var(--space-4) var(--space-6)', marginTop: 'var(--space-4)' }}>
            <div>
              <label htmlFor="color" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                Color
              </label>
              <input id="color" type="text" {...register('color')} style={inputStyle(!!fieldError('color'))} />
            </div>

            <div>
              <label htmlFor="vin" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                VIN
              </label>
              <input id="vin" type="text" {...register('vin')} style={inputStyle(!!fieldError('vin'))} />
            </div>

            <div>
              <label htmlFor="engine_number" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                Engine Number
              </label>
              <input id="engine_number" type="text" {...register('engine_number')} style={inputStyle(!!fieldError('engine_number'))} />
            </div>

            <div>
              <label htmlFor="registration_expiry" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                Registration Expiry
              </label>
              <input id="registration_expiry" type="date" {...register('registration_expiry')} style={inputStyle(!!fieldError('registration_expiry'))} />
            </div>

            <div>
              <label htmlFor="insurance_expiry" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                Insurance Expiry
              </label>
              <input id="insurance_expiry" type="date" {...register('insurance_expiry')} style={inputStyle(!!fieldError('insurance_expiry'))} />
            </div>
          </div>

          <div style={{ display: 'flex', gap: 'var(--space-3)', justifyContent: 'flex-end', paddingTop: 'var(--space-6)', borderTop: '1px solid var(--color-border)', marginTop: 'var(--space-6)' }}>
            <button type="button" className="btn btn-secondary" onClick={() => navigate('/app/vehicles')} disabled={isPending}>
              Cancel
            </button>
            <button type="submit" className="btn btn-primary" disabled={isPending}>
              {isPending && <Loader2 size={16} className="spin" />}
              {isEditing ? 'Update Vehicle' : 'Create Vehicle'}
            </button>
          </div>
        </form>
      </div>
    </PageContainer>
  );
}
