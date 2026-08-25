import { useEffect, useState } from 'react';
import { Loader2, CheckCircle2 } from 'lucide-react';
import { useAppShell } from '@/shared/layouts/appShellContext';
import { PageContainer } from '@/shared/components/ui/PageContainer';
import { LoadingState } from '@/shared/components/ui/LoadingState';
import { useNotificationPreferences, useUpdateNotificationPreferences } from '../hooks/useNotifications';
import type { NotificationPreferenceMap, NotificationType } from '../types/notification';

interface PreferenceRow {
  key: NotificationType;
  label: string;
  description: string;
}

const preferenceRows: PreferenceRow[] = [
  {
    key: 'compliance_expiring',
    label: 'Compliance expiring',
    description: 'Notify when a driver or vehicle compliance document is nearing expiry.',
  },
  {
    key: 'contract_expiring',
    label: 'Contract expiring',
    description: 'Notify when an active contract is nearing its end date.',
  },
  {
    key: 'maintenance_due',
    label: 'Maintenance due',
    description: 'Notify when a vehicle has pending maintenance that is due soon.',
  },
  {
    key: 'fuel_low_stock',
    label: 'Fuel low stock',
    description: 'Notify when fuel stock falls below its configured minimum.',
  },
];

const defaultPreferences: NotificationPreferenceMap = {
  compliance_expiring: true,
  contract_expiring: true,
  maintenance_due: true,
  fuel_low_stock: true,
};

function PreferencesForm({ initial }: { initial: NotificationPreferenceMap }) {
  const updateMutation = useUpdateNotificationPreferences();

  const [draft, setDraft] = useState<NotificationPreferenceMap>(() => ({
    ...defaultPreferences,
    ...initial,
  }));
  const [saved, setSaved] = useState(false);
  const [serverError, setServerError] = useState<string | null>(null);

  function toggle(key: NotificationType, value: boolean) {
    setSaved(false);
    setServerError(null);
    setDraft((prev) => ({ ...prev, [key]: value }));
  }

  async function handleSave() {
    setSaved(false);
    setServerError(null);
    try {
      await updateMutation.mutateAsync(draft);
      setSaved(true);
    } catch (err: unknown) {
      const errorData =
        err && typeof err === 'object' && 'response' in err
          ? (err as { response: { data: { message?: string } } }).response.data
          : undefined;
      setServerError(errorData?.message ?? 'Failed to save notification preferences.');
    }
  }

  return (
    <div className="card" style={{ padding: 'var(--space-6)', maxWidth: 640 }}>
      {serverError && (
        <div
          role="alert"
          style={{
            padding: 'var(--space-3)',
            marginBottom: 'var(--space-4)',
            background: 'var(--color-danger-light)',
            border: '1px solid var(--red-200)',
            borderRadius: 'var(--radius-md)',
            fontSize: 'var(--text-sm)',
            color: 'var(--color-danger)',
          }}
        >
          {serverError}
        </div>
      )}

      {saved && (
        <div
          role="status"
          style={{
            display: 'flex',
            alignItems: 'center',
            gap: 'var(--space-2)',
            padding: 'var(--space-3)',
            marginBottom: 'var(--space-4)',
            background: 'var(--color-success-light)',
            border: '1px solid var(--green-200)',
            borderRadius: 'var(--radius-md)',
            fontSize: 'var(--text-sm)',
            color: 'var(--color-success)',
          }}
        >
          <CheckCircle2 size={16} />
          Notification preferences saved successfully.
        </div>
      )}

      <div style={{ display: 'flex', flexDirection: 'column', gap: 'var(--space-4)' }}>
        {preferenceRows.map((row) => (
          <label
            key={row.key}
            htmlFor={`pref-${row.key}`}
            style={{
              display: 'flex',
              alignItems: 'flex-start',
              gap: 'var(--space-3)',
              cursor: 'pointer',
            }}
          >
            <input
              id={`pref-${row.key}`}
              type="checkbox"
              checked={draft[row.key]}
              onChange={(e) => toggle(row.key, e.target.checked)}
              style={{ marginTop: 2 }}
            />
            <span>
              <span style={{ display: 'block', fontWeight: 500, fontSize: 'var(--text-sm)' }}>
                {row.label}
              </span>
              <span style={{ fontSize: 'var(--text-xs)', color: 'var(--color-text-secondary)' }}>
                {row.description}
              </span>
            </span>
          </label>
        ))}
      </div>

      <div
        style={{
          display: 'flex',
          gap: 'var(--space-3)',
          justifyContent: 'flex-end',
          paddingTop: 'var(--space-6)',
          borderTop: '1px solid var(--color-border)',
          marginTop: 'var(--space-6)',
        }}
      >
        <button
          type="button"
          className="btn btn-primary"
          onClick={handleSave}
          disabled={updateMutation.isPending}
        >
          {updateMutation.isPending && <Loader2 size={16} className="spin" />}
          Save preferences
        </button>
      </div>
    </div>
  );
}

export function NotificationSettings() {
  const { setPageTitle } = useAppShell();
  const { data, isLoading, isError, refetch } = useNotificationPreferences();

  useEffect(() => {
    setPageTitle('Notification Settings');
  }, [setPageTitle]);

  return (
    <PageContainer
      title="Notification Settings"
      description="Choose which system notifications you want to receive"
    >
      {isLoading && <LoadingState />}

      {isError && (
        <div role="alert" className="card" style={{ padding: 'var(--space-6)', textAlign: 'center' }}>
          <p style={{ color: 'var(--color-danger)', marginBottom: 'var(--space-3)' }}>
            Failed to load notification preferences.
          </p>
          <button className="btn btn-secondary" onClick={() => refetch()}>
            Retry
          </button>
        </div>
      )}

      {!isLoading && !isError && data && <PreferencesForm initial={data.preferences} />}
    </PageContainer>
  );
}