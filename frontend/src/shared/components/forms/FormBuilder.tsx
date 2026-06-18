import { useForm, type FieldValues } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Loader2 } from 'lucide-react';
import type { FieldConfig, FormBuilderProps, SelectOption } from '@/shared/types/form';
import type { ReactNode } from 'react';

function FieldWrapper({
  label,
  required,
  error,
  helpText,
  children,
  name,
}: {
  label: string;
  required?: boolean;
  error?: { message?: string };
  helpText?: string;
  children: ReactNode;
  name: string;
}) {
  const errorId = `${name}-error`;
  return (
    <div style={{ marginBottom: 'var(--space-4)' }}>
      <label
        htmlFor={name}
        style={{
          display: 'block',
          fontSize: 'var(--text-sm)',
          fontWeight: 500,
          marginBottom: 'var(--space-1)',
          color: 'var(--color-text)',
        }}
      >
        {label}
        {required && <span style={{ color: 'var(--color-danger)', marginLeft: 2 }}>*</span>}
      </label>
      {children}
      {helpText && !error && (
        <p style={{ fontSize: 'var(--text-xs)', color: 'var(--color-text-secondary)', marginTop: 2 }}>
          {helpText}
        </p>
      )}
      {error && (
        <p id={errorId} role="alert" style={{ fontSize: 'var(--text-xs)', color: 'var(--color-danger)', marginTop: 2 }}>
          {error.message}
        </p>
      )}
    </div>
  );
}

function renderField<T extends FieldValues>(
  field: FieldConfig<T>,
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  register: any,
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  error?: any,
) {
  const inputStyle = {
    width: '100%',
    padding: 'var(--space-2) var(--space-3)',
    fontSize: 'var(--text-sm)',
    color: 'var(--color-text)',
    background: 'var(--color-surface)',
    border: `1px solid ${error ? 'var(--color-danger)' : 'var(--color-border)'}`,
    borderRadius: 'var(--radius-md)',
    outline: 'none',
    transition: 'border-color var(--transition-fast)',
  };

  const sharedProps = {
    id: field.name,
    disabled: field.disabled,
    placeholder: field.placeholder,
    'aria-invalid': !!error,
    'aria-describedby': error ? `${field.name}-error` : undefined,
  };

  switch (field.type) {
    case 'textarea':
      return (
        <textarea
          {...register(field.name)}
          className={error ? 'form-input--error' : undefined}
          style={{ ...inputStyle, minHeight: 100, resize: 'vertical' }}
          {...sharedProps}
        />
      );

    case 'select':
      return (
        <select
          {...register(field.name)}
          style={{ ...inputStyle, appearance: 'none' }}
          {...sharedProps}
        >
          <option value="">Select...</option>
          {field.options?.map((opt: SelectOption) => (
            <option key={String(opt.value)} value={opt.value}>
              {opt.label}
            </option>
          ))}
        </select>
      );

    case 'checkbox':
      return (
        <input
          type="checkbox"
          {...register(field.name)}
          style={{ width: 16, height: 16, accentColor: 'var(--color-primary)' }}
          id={field.name}
          disabled={field.disabled}
          aria-invalid={!!error}
          aria-describedby={error ? `${field.name}-error` : undefined}
        />
      );

    default:
      return (
        <input
          type={field.type}
          {...register(field.name)}
          style={inputStyle}
          {...sharedProps}
        />
      );
  }
}

export function FormBuilder<T extends FieldValues>({
  fields,
  schema,
  onSubmit,
  defaultValues,
  submitLabel = 'Save',
  cancelLabel = 'Cancel',
  onCancel,
  isLoading,
  layout = 'single',
}: FormBuilderProps<T>) {
  const {
    register,
    handleSubmit,
    formState: { errors },
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  } = useForm<any>({
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    resolver: zodResolver(schema as any) as any,
    defaultValues: defaultValues as Record<string, unknown>,
  });

  const gridCols = layout === 'grid' ? 2 : 1;

  return (
    <form onSubmit={handleSubmit((data) => onSubmit(data as T))} noValidate>
      <div
        style={{
          display: 'grid',
          gridTemplateColumns: `repeat(${gridCols}, 1fr)`,
          gap: 'var(--space-4) var(--space-6)',
        }}
      >
        {fields.map((field) => {
          const fieldName = field.name as string;
          const fieldError = errors[fieldName] as { message?: string } | undefined;
          return (
            <div
              key={field.name as string}
              style={field.cols ? { gridColumn: `span ${field.cols}` } : undefined}
            >
              <FieldWrapper
                label={field.label}
                required={field.required}
                error={fieldError}
                helpText={field.helpText}
                name={field.name as string}
              >
                {renderField(field, register, fieldError)}
              </FieldWrapper>
            </div>
          );
        })}
      </div>

      <div
        style={{
          display: 'flex',
          gap: 'var(--space-3)',
          justifyContent: 'flex-end',
          paddingTop: 'var(--space-6)',
          borderTop: '1px solid var(--color-border)',
          marginTop: 'var(--space-6)',
        }}
      >
        {onCancel && (
          <button type="button" className="btn btn-secondary" onClick={onCancel} disabled={isLoading}>
            {cancelLabel}
          </button>
        )}
        <button type="submit" className="btn btn-primary" disabled={isLoading}>
          {isLoading && <Loader2 size={16} className="spin" />}
          {submitLabel}
        </button>
      </div>
    </form>
  );
}
