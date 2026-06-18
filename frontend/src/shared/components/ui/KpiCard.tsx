import type { LucideIcon } from 'lucide-react';

interface KpiCardProps {
  label: string;
  value: string | number;
  icon?: LucideIcon;
  trend?: { value: number; isPositive: boolean };
  onClick?: () => void;
  isLoading?: boolean;
}

export function KpiCard({ label, value, icon: Icon, trend, onClick, isLoading }: KpiCardProps) {
  if (isLoading) {
    return (
      <div className="summary-card" aria-busy="true">
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start' }}>
          <div style={{ flex: 1 }}>
            <div className="skeleton" style={{ width: '60%', height: 12, marginBottom: 8 }} />
            <div className="skeleton" style={{ width: '40%', height: 24 }} />
          </div>
          {Icon && <div className="skeleton" style={{ width: 24, height: 24, borderRadius: 'var(--radius-md)' }} />}
        </div>
        {trend && <div className="skeleton" style={{ width: '30%', height: 10, marginTop: 8 }} />}
      </div>
    );
  }

  return (
    <div
      className="summary-card"
      onClick={onClick}
      style={onClick ? { cursor: 'pointer' } : undefined}
      role={onClick ? 'button' : undefined}
      tabIndex={onClick ? 0 : undefined}
      onKeyDown={onClick ? (e) => e.key === 'Enter' && onClick() : undefined}
    >
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start' }}>
        <div>
          <span className="summary-card-label">{label}</span>
          <span className="summary-card-value">{value}</span>
        </div>
        {Icon && <Icon size={24} style={{ color: 'var(--color-primary)', opacity: 0.7 }} aria-hidden="true" />}
      </div>
      {trend && (
        <div
          style={{
            marginTop: 'var(--space-2)',
            fontSize: 'var(--text-xs)',
            color: trend.isPositive ? 'var(--color-success)' : 'var(--color-danger)',
          }}
        >
          {trend.isPositive ? '+' : ''}
          {trend.value}%
        </div>
      )}
    </div>
  );
}
