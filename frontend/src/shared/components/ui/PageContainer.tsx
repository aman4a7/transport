import { type ReactNode } from 'react';

interface PageContainerProps {
  title: string;
  description?: string;
  actions?: ReactNode;
  children: ReactNode;
  isLoading?: boolean;
}

export function PageContainer({ title, description, actions, children, isLoading }: PageContainerProps) {
  return (
    <div style={{ display: 'flex', flexDirection: 'column', gap: 'var(--space-6)' }}>
      <div
        style={{
          display: 'flex',
          justifyContent: 'space-between',
          alignItems: 'flex-start',
          flexWrap: 'wrap',
          gap: 'var(--space-4)',
        }}
      >
        <div>
          <h1 style={{ fontSize: 'var(--text-2xl)', fontWeight: 600 }}>{title}</h1>
          {description && (
            <p style={{ color: 'var(--color-text-secondary)', marginTop: 'var(--space-1)' }}>
              {description}
            </p>
          )}
        </div>
        {actions && <div style={{ display: 'flex', gap: 'var(--space-2)' }}>{actions}</div>}
      </div>
      {isLoading ? (
        <div style={{ padding: 'var(--space-10)', textAlign: 'center', color: 'var(--color-text-muted)' }}>
          Loading...
        </div>
      ) : (
        children
      )}
    </div>
  );
}
