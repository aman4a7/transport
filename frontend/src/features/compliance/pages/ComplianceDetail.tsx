import { useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useComplianceDocument } from '../hooks/useComplianceDocuments';
import { PageContainer } from '@/shared/components/ui/PageContainer';
import { StatusBadge } from '@/shared/components/ui/StatusBadge';
import { LoadingState } from '@/shared/components/ui/LoadingState';
import { EmptyState } from '@/shared/components/ui/EmptyState';
import { useAppShell } from '@/shared/layouts/appShellContext';

const statusVariant: Record<string, 'success' | 'warning' | 'danger' | 'info' | 'neutral'> = {
  approved: 'success',
  pending: 'info',
  rejected: 'danger',
  expired: 'warning',
};

const typeLabels: Record<string, string> = {
  vehicle_registration: 'Vehicle Registration',
  insurance: 'Insurance',
  driver_license: 'Driver License',
  contract_document: 'Contract',
  other: 'Other',
};

export function ComplianceDetail() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const { setPageTitle } = useAppShell();

  const { data: document, isLoading, isError } = useComplianceDocument(id ? Number(id) : 0);

  useEffect(() => {
    if (document) {
      setPageTitle(`Compliance - ${typeLabels[document.type] ?? document.type}`);
    }
  }, [setPageTitle, document]);

  if (isLoading) {
    return <PageContainer title="Compliance Document"><LoadingState type="card" rows={3} /></PageContainer>;
  }

  if (isError || !document) {
    return (
      <PageContainer title="Compliance Document">
        <EmptyState
          title="Document not found"
          description="The compliance document you are looking for does not exist."
          actionLabel="Back to Compliance"
          onAction={() => navigate('/app/compliance')}
        />
      </PageContainer>
    );
  }

  const infoRow = (label: string, value: string | number | null | undefined) => (
    <div style={{ display: 'flex', gap: 'var(--space-2)', padding: 'var(--space-2) 0', borderBottom: '1px solid var(--color-border)' }}>
      <span style={{ minWidth: 160, fontWeight: 500, fontSize: 'var(--text-sm)', color: 'var(--color-text-secondary)' }}>{label}</span>
      <span style={{ fontSize: 'var(--text-sm)' }}>{value ?? '-'}</span>
    </div>
  );

  return (
    <PageContainer
      title={`Compliance - ${typeLabels[document.type] ?? document.type}`}
      actions={
        document.status === 'pending' ? (
          <div style={{ display: 'flex', gap: 'var(--space-2)' }}>
            <button className="btn btn-primary" onClick={() => navigate(`/app/compliance/${document.id}/review`)}>
              Review
            </button>
          </div>
        ) : null
      }
    >
      <div style={{ display: 'flex', gap: 'var(--space-2)', alignItems: 'center', marginBottom: 'var(--space-6)' }}>
        <StatusBadge status={document.status} variant={statusVariant[document.status] ?? 'neutral'} />
      </div>

      <div className="card" style={{ padding: 'var(--space-6)' }}>
        <h3 style={{ fontSize: 'var(--text-lg)', marginBottom: 'var(--space-4)' }}>Document Information</h3>
        {infoRow('Type', typeLabels[document.type] ?? document.type)}
        {infoRow('Status', document.status)}
        {infoRow('Filename', document.original_filename)}
        {infoRow('MIME Type', document.mime_type)}
        {infoRow('File Size', document.file_size ? `${(document.file_size / 1024).toFixed(1)} KB` : '-')}
        {infoRow('Issued At', document.issued_at ?? '-')}
        {infoRow('Expires At', document.expires_at ?? '-')}
        {infoRow('Submitted By', document.submitted_by?.name ?? '-')}
        {infoRow('Reviewed By', document.reviewed_by?.name ?? '-')}
        {infoRow('Reviewed At', document.reviewed_at ?? '-')}
        {document.rejection_reason && infoRow('Rejection Reason', document.rejection_reason)}
      </div>
    </PageContainer>
  );
}
