import type { LucideIcon } from 'lucide-react';
import { Inbox } from 'lucide-react';

interface EmptyStateProps {
  icon?: LucideIcon;
  title: string;
  description?: string;
  actionLabel?: string;
  onAction?: () => void;
}

export function EmptyState({ icon: Icon = Inbox, title, description, actionLabel, onAction }: EmptyStateProps) {
  return (
    <div
      style={{
        display: 'flex',
        flexDirection: 'column',
        alignItems: 'center',
        justifyContent: 'center',
        padding: 'var(--space-12)',
        textAlign: 'center',
        gap: 'var(--space-4)',
      }}
    >
      <Icon size={48} style={{ color: 'var(--color-text-secondary)', opacity: 0.5 }} aria-hidden="true" />
      <h3 style={{ fontSize: 'var(--text-lg)' }}>{title}</h3>
      {description && <p style={{ color: 'var(--color-text-secondary)', maxWidth: 400 }}>{description}</p>}
      {actionLabel && onAction && (
        <button className="btn btn-primary" onClick={onAction} style={{ marginTop: 'var(--space-2)' }}>
          {actionLabel}
        </button>
      )}
    </div>
  );
}
