export type BadgeVariant = 'success' | 'warning' | 'danger' | 'info' | 'neutral';

const variantStyles: Record<BadgeVariant, { bg: string; color: string; border: string }> = {
  success: { bg: 'var(--color-success-light)', color: 'var(--color-success)', border: 'var(--green-200)' },
  warning: { bg: 'var(--color-warning-light)', color: 'var(--color-warning)', border: 'var(--yellow-200)' },
  danger: { bg: 'var(--color-danger-light)', color: 'var(--color-danger)', border: 'var(--red-200)' },
  info: { bg: 'var(--color-info-light)', color: 'var(--color-info)', border: 'var(--blue-200)' },
  neutral: { bg: 'var(--gray-100)', color: 'var(--color-text-secondary)', border: 'var(--gray-200)' },
};

interface StatusBadgeProps {
  variant?: BadgeVariant;
  status?: string;
  label?: string;
}

const statusToVariant: Record<string, BadgeVariant> = {
  active: 'success',
  inactive: 'neutral',
  pending: 'warning',
  approved: 'success',
  rejected: 'danger',
  expired: 'danger',
  suspended: 'warning',
  defence_plated: 'info',
  contracted_private: 'neutral',
};

export function StatusBadge({ variant, status, label }: StatusBadgeProps) {
  const resolvedVariant: BadgeVariant = variant ?? (status ? (statusToVariant[status] ?? 'neutral') : 'neutral');
  const styles = variantStyles[resolvedVariant];

  return (
    <span
      style={{
        display: 'inline-flex',
        alignItems: 'center',
        gap: 'var(--space-1)',
        padding: '2px 8px',
        borderRadius: 'var(--radius-sm)',
        fontSize: 'var(--text-xs)',
        fontWeight: 500,
        background: styles.bg,
        color: styles.color,
        border: `1px solid ${styles.border}`,
      }}
    >
      {label ?? status ?? resolvedVariant}
    </span>
  );
}
