import { Loader2 } from 'lucide-react';

interface LoadingStateProps {
  variant?: 'spinner' | 'skeleton';
  message?: string;
  rows?: number;
  type?: 'table' | 'card' | 'text';
}

function SkeletonRow() {
  return (
    <div style={{ display: 'flex', gap: 'var(--space-4)', padding: 'var(--space-3) 0' }}>
      <div className="skeleton" style={{ flex: 2, height: 14 }} />
      <div className="skeleton" style={{ flex: 1, height: 14 }} />
      <div className="skeleton" style={{ flex: 1, height: 14 }} />
      <div className="skeleton" style={{ flex: 1, height: 14 }} />
    </div>
  );
}

function SkeletonCard() {
  return (
    <div className="card" style={{ padding: 'var(--space-6)' }}>
      <div className="skeleton" style={{ width: '40%', height: 16, marginBottom: 12 }} />
      <div className="skeleton" style={{ width: '100%', height: 12, marginBottom: 8 }} />
      <div className="skeleton" style={{ width: '80%', height: 12, marginBottom: 8 }} />
      <div className="skeleton" style={{ width: '60%', height: 12 }} />
    </div>
  );
}

function SkeletonText() {
  return (
    <div style={{ padding: 'var(--space-4) 0' }}>
      <div className="skeleton" style={{ width: '100%', height: 12, marginBottom: 8 }} />
      <div className="skeleton" style={{ width: '90%', height: 12, marginBottom: 8 }} />
      <div className="skeleton" style={{ width: '75%', height: 12 }} />
    </div>
  );
}

export function LoadingState({ variant = 'spinner', message, rows = 5, type = 'text' }: LoadingStateProps) {
  if (variant === 'spinner') {
    return (
      <div
        style={{
          display: 'flex',
          flexDirection: 'column',
          alignItems: 'center',
          justifyContent: 'center',
          padding: 'var(--space-10)',
          gap: 'var(--space-4)',
        }}
        role="status"
      >
        <Loader2 size={24} className="spin" aria-hidden="true" />
        {message && <p style={{ color: 'var(--color-text-secondary)', fontSize: 'var(--text-sm)' }}>{message}</p>}
        <span className="sr-only">Loading...</span>
      </div>
    );
  }

  return (
    <div role="status" aria-busy="true" style={{ width: '100%' }}>
      {message && (
        <p style={{ color: 'var(--color-text-secondary)', fontSize: 'var(--text-sm)', marginBottom: 'var(--space-3)' }}>
          {message}
        </p>
      )}
      {Array.from({ length: rows }, (_, i) => (
        <div key={i}>
          {type === 'table' && <SkeletonRow />}
          {type === 'card' && <SkeletonCard />}
          {type === 'text' && <SkeletonText />}
        </div>
      ))}
      <span className="sr-only">Loading...</span>
    </div>
  );
}
