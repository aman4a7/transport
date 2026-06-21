import { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useComplianceDocument, useApproveComplianceDocument, useRejectComplianceDocument } from '../hooks/useComplianceDocuments';
import { PageContainer } from '@/shared/components/ui/PageContainer';
import { StatusBadge } from '@/shared/components/ui/StatusBadge';
import { LoadingState } from '@/shared/components/ui/LoadingState';
import { EmptyState } from '@/shared/components/ui/EmptyState';
import { ConfirmDialog } from '@/shared/components/ui/ConfirmDialog';
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

export function ComplianceReview() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const { setPageTitle } = useAppShell();

  const [rejectReason, setRejectReason] = useState('');
  const [showRejectDialog, setShowRejectDialog] = useState(false);
  const [rejectError, setRejectError] = useState<string | null>(null);

  const { data: document, isLoading, isError } = useComplianceDocument(id ? Number(id) : 0);
  const approveMutation = useApproveComplianceDocument();
  const rejectMutation = useRejectComplianceDocument();

  useEffect(() => {
    if (document) {
      setPageTitle(`Review - ${typeLabels[document.type] ?? document.type}`);
    }
  }, [setPageTitle, document]);

  async function handleApprove() {
    if (!id) return;
    try {
      await approveMutation.mutateAsync(Number(id));
      navigate(`/app/compliance/${id}`);
    } catch {
      // error handled by mutation
    }
  }

  async function handleReject() {
    if (!id) return;
    if (!rejectReason || rejectReason.length < 10) {
      setRejectError('Rejection reason must be at least 10 characters.');
      return;
    }
    try {
      await rejectMutation.mutateAsync({ id: Number(id), reason: rejectReason });
      setShowRejectDialog(false);
      navigate(`/app/compliance/${id}`);
    } catch {
      setRejectError('Failed to reject document.');
    }
  }

  if (isLoading) {
    return <PageContainer title="Review Document"><LoadingState type="card" rows={3} /></PageContainer>;
  }

  if (isError || !document) {
    return (
      <PageContainer title="Review Document">
        <EmptyState
          title="Document not found"
          description="The compliance document you are looking for does not exist."
          actionLabel="Back to Compliance"
          onAction={() => navigate('/app/compliance')}
        />
      </PageContainer>
    );
  }

  if (document.status !== 'pending') {
    return (
      <PageContainer title="Review Document">
        <EmptyState
          title="Document already reviewed"
          description={`This document has already been ${document.status}. Only pending documents can be reviewed.`}
          actionLabel="View Document"
          onAction={() => navigate(`/app/compliance/${id}`)}
        />
      </PageContainer>
    );
  }

  return (
    <PageContainer
      title={`Review - ${typeLabels[document.type] ?? document.type}`}
      actions={
        <button className="btn btn-secondary" onClick={() => navigate(`/app/compliance/${id}`)}>
          Cancel
        </button>
      }
    >
      <div style={{ display: 'flex', gap: 'var(--space-2)', alignItems: 'center', marginBottom: 'var(--space-6)' }}>
        <StatusBadge status={document.status} variant={statusVariant[document.status] ?? 'neutral'} />
      </div>

      <div className="card" style={{ padding: 'var(--space-6)', marginBottom: 'var(--space-6)' }}>
        <h3 style={{ fontSize: 'var(--text-lg)', marginBottom: 'var(--space-4)' }}>Document Details</h3>
        <div style={{ display: 'flex', gap: 'var(--space-2)', padding: 'var(--space-2) 0', borderBottom: '1px solid var(--color-border)' }}>
          <span style={{ minWidth: 160, fontWeight: 500, fontSize: 'var(--text-sm)', color: 'var(--color-text-secondary)' }}>Type</span>
          <span style={{ fontSize: 'var(--text-sm)' }}>{typeLabels[document.type] ?? document.type}</span>
        </div>
        <div style={{ display: 'flex', gap: 'var(--space-2)', padding: 'var(--space-2) 0', borderBottom: '1px solid var(--color-border)' }}>
          <span style={{ minWidth: 160, fontWeight: 500, fontSize: 'var(--text-sm)', color: 'var(--color-text-secondary)' }}>Filename</span>
          <span style={{ fontSize: 'var(--text-sm)' }}>{document.original_filename}</span>
        </div>
        <div style={{ display: 'flex', gap: 'var(--space-2)', padding: 'var(--space-2) 0', borderBottom: '1px solid var(--color-border)' }}>
          <span style={{ minWidth: 160, fontWeight: 500, fontSize: 'var(--text-sm)', color: 'var(--color-text-secondary)' }}>Submitted By</span>
          <span style={{ fontSize: 'var(--text-sm)' }}>{document.submitted_by?.name ?? '-'}</span>
        </div>
        <div style={{ display: 'flex', gap: 'var(--space-2)', padding: 'var(--space-2) 0' }}>
          <span style={{ minWidth: 160, fontWeight: 500, fontSize: 'var(--text-sm)', color: 'var(--color-text-secondary)' }}>Expires</span>
          <span style={{ fontSize: 'var(--text-sm)' }}>{document.expires_at ?? 'No expiry'}</span>
        </div>
      </div>

      <div style={{ display: 'flex', gap: 'var(--space-3)' }}>
        <button
          className="btn btn-primary"
          onClick={handleApprove}
          disabled={approveMutation.isPending}
        >
          {approveMutation.isPending ? 'Approving...' : 'Approve'}
        </button>
        <button
          className="btn btn-danger"
          onClick={() => setShowRejectDialog(true)}
          disabled={rejectMutation.isPending}
        >
          {rejectMutation.isPending ? 'Rejecting...' : 'Reject'}
        </button>
      </div>

      <ConfirmDialog
        open={showRejectDialog}
        title="Reject Document"
        message={
          <div>
            <p style={{ marginBottom: 'var(--space-3)' }}>Are you sure you want to reject this document? Provide a reason:</p>
            {rejectError && (
              <p role="alert" style={{ fontSize: 'var(--text-xs)', color: 'var(--color-danger)', marginBottom: 'var(--space-2)' }}>
                {rejectError}
              </p>
            )}
            <textarea
              className="form-input"
              value={rejectReason}
              onChange={(e) => { setRejectReason(e.target.value); setRejectError(null); }}
              rows={4}
              placeholder="Enter rejection reason (min 10 characters)..."
              style={{ width: '100%', minHeight: 80, resize: 'vertical' }}
            />
          </div>
        }
        confirmLabel="Reject"
        onConfirm={handleReject}
        onCancel={() => { setShowRejectDialog(false); setRejectReason(''); setRejectError(null); }}
        isLoading={rejectMutation.isPending}
      />
    </PageContainer>
  );
}
