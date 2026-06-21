import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Loader2 } from 'lucide-react';
import { complianceUploadSchema } from '../schemas/complianceSchema';
import { useUploadComplianceDocument } from '../hooks/useComplianceDocuments';
import { PageContainer } from '@/shared/components/ui/PageContainer';
import { useAppShell } from '@/shared/layouts/appShellContext';

export function ComplianceUpload() {
  const navigate = useNavigate();
  const { setPageTitle } = useAppShell();
  const [serverError, setServerError] = useState<string | null>(null);

  const uploadMutation = useUploadComplianceDocument();

  const {
    register,
    handleSubmit,
    watch,
    formState: { errors },
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
  } = useForm<any>({
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    resolver: zodResolver(complianceUploadSchema) as any,
    defaultValues: {
      documentable_type: 'App\\Domain\\Vehicle\\Models\\Vehicle',
      type: 'vehicle_registration',
    },
  });

  useEffect(() => {
    setPageTitle('Upload Document');
  }, [setPageTitle]);

  function fieldError(name: string): string | undefined {
    const fieldErr = errors[name] as { message?: string } | undefined;
    return fieldErr?.message;
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
    try {
      const formData = new FormData();
      formData.append('documentable_type', raw.documentable_type as string);
      formData.append('documentable_id', String(raw.documentable_id));
      formData.append('type', raw.type as string);
      formData.append('file', raw.file as File);
      if (raw.issued_at) formData.append('issued_at', raw.issued_at as string);
      if (raw.expires_at) formData.append('expires_at', raw.expires_at as string);

      await uploadMutation.mutateAsync(formData);
      navigate('/app/compliance');
    } catch (err: unknown) {
      const errorData =
        err && typeof err === 'object' && 'response' in err
          ? (err as { response: { data: { message?: string } } }).response.data
          : undefined;
      setServerError(errorData?.message ?? 'Failed to upload document');
    }
  }

  const selectedType = watch('type');

  return (
    <PageContainer title="Upload Compliance Document">
      <div className="card" style={{ padding: 'var(--space-6)', maxWidth: 640 }}>
        {serverError && (
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
              <label htmlFor="documentable_type" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                Document For <span style={{ color: 'var(--color-danger)' }}>*</span>
              </label>
              <select id="documentable_type" {...register('documentable_type')} style={selectStyle(!!fieldError('documentable_type'))}>
                <option value="App\Domain\Vehicle\Models\Vehicle">Vehicle</option>
                <option value="App\Domain\Driver\Models\Driver">Driver</option>
                <option value="App\Domain\Owner\Models\Owner">Owner</option>
              </select>
              {fieldError('documentable_type') && <p role="alert" style={{ fontSize: 'var(--text-xs)', color: 'var(--color-danger)', marginTop: 2 }}>{fieldError('documentable_type')}</p>}
            </div>

            <div>
              <label htmlFor="documentable_id" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                {selectedType === 'vehicle_registration' || selectedType === 'insurance' ? 'Vehicle ID' : selectedType === 'driver_license' ? 'Driver ID' : 'Entity ID'} <span style={{ color: 'var(--color-danger)' }}>*</span>
              </label>
              <input id="documentable_id" type="number" {...register('documentable_id')} style={inputStyle(!!fieldError('documentable_id'))} placeholder="Enter ID" />
              {fieldError('documentable_id') && <p role="alert" style={{ fontSize: 'var(--text-xs)', color: 'var(--color-danger)', marginTop: 2 }}>{fieldError('documentable_id')}</p>}
            </div>

            <div>
              <label htmlFor="type" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                Document Type <span style={{ color: 'var(--color-danger)' }}>*</span>
              </label>
              <select id="type" {...register('type')} style={selectStyle(!!fieldError('type'))}>
                <option value="vehicle_registration">Vehicle Registration</option>
                <option value="insurance">Insurance</option>
                <option value="driver_license">Driver License</option>
                <option value="contract_document">Contract</option>
                <option value="other">Other</option>
              </select>
              {fieldError('type') && <p role="alert" style={{ fontSize: 'var(--text-xs)', color: 'var(--color-danger)', marginTop: 2 }}>{fieldError('type')}</p>}
            </div>

            <div>
              <label htmlFor="file" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                File <span style={{ color: 'var(--color-danger)' }}>*</span>
              </label>
              <input
                id="file"
                type="file"
                accept=".pdf,.jpg,.jpeg,.png"
                {...register('file')}
                style={inputStyle(!!fieldError('file'))}
                aria-invalid={!!fieldError('file')}
              />
              {fieldError('file') && <p role="alert" style={{ fontSize: 'var(--text-xs)', color: 'var(--color-danger)', marginTop: 2 }}>{fieldError('file')}</p>}
              <p style={{ fontSize: 'var(--text-xs)', color: 'var(--color-text-secondary)', marginTop: 2 }}>Accepted: PDF, JPG, JPEG, PNG (max 10MB)</p>
            </div>

            <div>
              <label htmlFor="issued_at" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                Issue Date
              </label>
              <input id="issued_at" type="date" {...register('issued_at')} style={inputStyle(!!fieldError('issued_at'))} />
            </div>

            <div>
              <label htmlFor="expires_at" style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                Expiry Date
              </label>
              <input id="expires_at" type="date" {...register('expires_at')} style={inputStyle(!!fieldError('expires_at'))} />
              {fieldError('expires_at') && <p role="alert" style={{ fontSize: 'var(--text-xs)', color: 'var(--color-danger)', marginTop: 2 }}>{fieldError('expires_at')}</p>}
            </div>
          </div>

          <div style={{ display: 'flex', gap: 'var(--space-3)', justifyContent: 'flex-end', paddingTop: 'var(--space-6)', borderTop: '1px solid var(--color-border)', marginTop: 'var(--space-6)' }}>
            <button type="button" className="btn btn-secondary" onClick={() => navigate('/app/compliance')} disabled={uploadMutation.isPending}>
              Cancel
            </button>
            <button type="submit" className="btn btn-primary" disabled={uploadMutation.isPending}>
              {uploadMutation.isPending && <Loader2 size={16} className="spin" />}
              Upload Document
            </button>
          </div>
        </form>
      </div>
    </PageContainer>
  );
}
