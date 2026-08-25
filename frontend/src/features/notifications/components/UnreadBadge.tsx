interface UnreadBadgeProps {
  count: number;
}

export function UnreadBadge({ count }: UnreadBadgeProps) {
  if (count <= 0) {
    return null;
  }

  return (
    <span
      className="notification-badge"
      aria-label={`${count} unread notifications`}
      style={{
        position: 'absolute',
        top: -4,
        right: -4,
        minWidth: 18,
        height: 18,
        padding: '0 4px',
        borderRadius: 'var(--radius-full, 999px)',
        background: 'var(--color-danger)',
        color: '#fff',
        fontSize: 'var(--text-xs)',
        fontWeight: 600,
        display: 'inline-flex',
        alignItems: 'center',
        justifyContent: 'center',
        lineHeight: 1,
      }}
    >
      {count > 99 ? '99+' : count}
    </span>
  );
}