import type { ReactNode } from 'react';

interface FormFieldProps {
  label: string;
  error?: string;
  required?: boolean;
  children: ReactNode;
  helpText?: string;
  id?: string;
}

export function FormField({ label, error, required, children, helpText, id }: FormFieldProps) {
  const fieldId = id ?? label.toLowerCase().replace(/\s+/g, '-');
  const errorId = `${fieldId}-error`;

  return (
    <div style={{ marginBottom: 'var(--space-md)' }}>
      <label
        htmlFor={fieldId}
        style={{
          display: 'block',
          fontSize: 'var(--text-sm)',
          fontWeight: 500,
          marginBottom: 'var(--space-xs)',
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
          {error}
        </p>
      )}
    </div>
  );
}
