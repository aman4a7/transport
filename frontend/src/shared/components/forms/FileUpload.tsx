import { useRef } from 'react';
import { Upload } from 'lucide-react';

interface FileUploadProps {
  accept?: string;
  maxSizeMB?: number;
  multiple?: boolean;
  onChange?: (files: FileList | null) => void;
  error?: string;
}

export function FileUpload({
  accept = '.pdf,.jpg,.png',
  maxSizeMB = 10,
  multiple = false,
  onChange,
  error,
}: FileUploadProps) {
  const inputRef = useRef<HTMLInputElement>(null);

  return (
    <div>
      <div
        style={{
          border: `2px dashed ${error ? 'var(--color-danger)' : 'var(--color-border)'}`,
          borderRadius: 'var(--radius-md)',
          padding: 'var(--space-lg)',
          textAlign: 'center',
          cursor: 'pointer',
        }}
        onClick={() => inputRef.current?.click()}
        role="button"
        tabIndex={0}
        onKeyDown={(e) => e.key === 'Enter' && inputRef.current?.click()}
      >
        <Upload size={24} style={{ color: 'var(--color-text-secondary)', marginBottom: 'var(--space-sm)' }} />
        <p style={{ fontSize: 'var(--text-sm)', color: 'var(--color-text-secondary)' }}>
          Click to upload or drag and drop
        </p>
        <p style={{ fontSize: 'var(--text-xs)', color: 'var(--color-text-secondary)', marginTop: 'var(--space-xs)' }}>
          Accepted: {accept}. Max size: {maxSizeMB}MB.
        </p>
      </div>
      <input
        ref={inputRef}
        type="file"
        accept={accept}
        multiple={multiple}
        onChange={(e) => onChange?.(e.target.files)}
        style={{ display: 'none' }}
      />
      {error && (
        <p role="alert" style={{ fontSize: 'var(--text-xs)', color: 'var(--color-danger)', marginTop: 2 }}>
          {error}
        </p>
      )}
    </div>
  );
}
