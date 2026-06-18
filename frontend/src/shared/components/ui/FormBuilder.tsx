import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Loader2 } from 'lucide-react';
import type { FormBuilderProps } from '@/shared/types/form';
import type { FieldValues } from 'react-hook-form';

export function FormBuilder<T extends FieldValues>({
  fields,
  schema,
  onSubmit,
  defaultValues,
  submitLabel = 'Submit',
  cancelLabel = 'Cancel',
  onCancel,
  isLoading = false,
  layout = 'single',
}: FormBuilderProps<T>) {
  const {
    register,
    handleSubmit,
    formState: { errors },
  } = useForm<T>({
    resolver: zodResolver(schema as never),
    defaultValues: defaultValues as never,
  });

  return (
    <form onSubmit={handleSubmit(onSubmit as never)} noValidate>
      <div
        style={{
          display: 'grid',
          gridTemplateColumns: layout === 'grid' ? 'repeat(2, 1fr)' : '1fr',
          gap: 'var(--space-4)',
        }}
      >
        {fields.map((field) => {
          const error = errors[field.name];
          const hasError = !!error;

          return (
            <div
              key={field.name as string}
              style={field.cols && layout === 'grid' ? { gridColumn: `span ${field.cols}` } : undefined}
            >
              <label
                htmlFor={field.name as string}
                style={{
                  display: 'block',
                  fontSize: 'var(--text-sm)',
                  fontWeight: 500,
                  marginBottom: 'var(--space-1)',
                  color: 'var(--color-text)',
                }}
              >
                {field.label}
                {field.required && <span style={{ color: 'var(--color-danger)', marginLeft: 2 }}>*</span>}
              </label>

              {field.type === 'select' ? (
                <select
                  id={field.name as string}
                  className="form-input"
                  {...register(field.name)}
                  disabled={field.disabled || isLoading}
                  aria-invalid={hasError}
                  style={{ width: '100%' }}
                >
                  <option value="">Select...</option>
                  {field.options?.map((opt) => (
                    <option key={opt.value} value={opt.value}>
                      {opt.label}
                    </option>
                  ))}
                </select>
              ) : field.type === 'textarea' ? (
                <textarea
                  id={field.name as string}
                  className="form-input"
                  {...register(field.name)}
                  placeholder={field.placeholder}
                  disabled={field.disabled || isLoading}
                  aria-invalid={hasError}
                  style={{ width: '100%', minHeight: 80, resize: 'vertical' }}
                />
              ) : (
                <input
                  id={field.name as string}
                  type={field.type}
                  className="form-input"
                  {...register(field.name)}
                  placeholder={field.placeholder}
                  disabled={field.disabled || isLoading}
                  aria-invalid={hasError}
                  style={{ width: '100%' }}
                />
              )}

              {field.helpText && !hasError && (
                <p style={{ fontSize: 'var(--text-xs)', color: 'var(--color-text-muted)', marginTop: 2 }}>
                  {field.helpText}
                </p>
              )}

              {hasError && (
                <p role="alert" style={{ fontSize: 'var(--text-xs)', color: 'var(--color-danger)', marginTop: 2 }}>
                  {error?.message as string}
                </p>
              )}
            </div>
          );
        })}
      </div>

      <div
        style={{
          display: 'flex',
          justifyContent: 'flex-end',
          gap: 'var(--space-3)',
          marginTop: 'var(--space-6)',
        }}
      >
        {onCancel && (
          <button type="button" className="btn btn-secondary" onClick={onCancel} disabled={isLoading}>
            {cancelLabel}
          </button>
        )}
        <button type="submit" className="btn btn-primary" disabled={isLoading}>
          {isLoading ? (
            <>
              <Loader2 size={16} className="spin" />
              Submitting...
            </>
          ) : (
            submitLabel
          )}
        </button>
      </div>
    </form>
  );
}
