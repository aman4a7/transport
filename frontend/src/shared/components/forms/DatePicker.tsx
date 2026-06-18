interface DatePickerProps {
  value?: string;
  onChange?: (value: string) => void;
  error?: string;
  id?: string;
  label?: string;
}

export function DatePicker({ value, onChange, error, id, label }: DatePickerProps) {
  return (
    <div>
      <input
        id={id}
        type="date"
        value={value ?? ''}
        onChange={(e) => onChange?.(e.target.value)}
        style={{
          width: '100%',
          padding: 'var(--space-sm) var(--space-md)',
          border: `1px solid ${error ? 'var(--color-danger)' : 'var(--color-border)'}`,
          borderRadius: 'var(--radius-md)',
          fontSize: 'var(--text-sm)',
        }}
        aria-label={label}
        aria-invalid={!!error}
      />
    </div>
  );
}
