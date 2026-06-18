import type { ReactNode } from 'react';

interface Breadcrumb {
  label: string;
  href?: string;
}

interface PageContainerProps {
  title: string;
  children: ReactNode;
  actions?: ReactNode;
  breadcrumbs?: Breadcrumb[];
}

export function PageContainer({ title, children, actions, breadcrumbs }: PageContainerProps) {
  return (
    <div>
      {breadcrumbs && breadcrumbs.length > 0 && (
        <nav aria-label="Breadcrumb" style={{ marginBottom: 'var(--space-3)' }}>
          <ol
            style={{
              display: 'flex',
              alignItems: 'center',
              gap: 'var(--space-2)',
              fontSize: 'var(--text-xs)',
              color: 'var(--color-text-secondary)',
              listStyle: 'none',
              padding: 0,
              margin: 0,
            }}
          >
            {breadcrumbs.map((crumb, idx) => {
              const isLast = idx === breadcrumbs.length - 1;
              return (
                <li key={idx} style={{ display: 'flex', alignItems: 'center', gap: 'var(--space-2)' }}>
                  {idx > 0 && (
                    <span aria-hidden="true" style={{ color: 'var(--color-text-muted)' }}>
                      /
                    </span>
                  )}
                  {crumb.href && !isLast ? (
                    <a
                      href={crumb.href}
                      style={{ color: 'var(--color-primary)', textDecoration: 'none' }}
                    >
                      {crumb.label}
                    </a>
                  ) : (
                    <span aria-current={isLast ? 'page' : undefined} style={{ fontWeight: isLast ? 600 : 400 }}>
                      {crumb.label}
                    </span>
                  )}
                </li>
              );
            })}
          </ol>
        </nav>
      )}

      <div className="page-header">
        <h1 style={{ fontSize: 'var(--text-2xl)', fontWeight: 'var(--font-semibold)', margin: 0 }}>{title}</h1>
        {actions && <div style={{ display: 'flex', gap: 'var(--space-2)' }}>{actions}</div>}
      </div>

      <div>{children}</div>
    </div>
  );
}
