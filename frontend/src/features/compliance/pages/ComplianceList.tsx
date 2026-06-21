import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useComplianceDocuments } from '../hooks/useComplianceDocuments';
import type { ComplianceDocument, ComplianceFilters, ComplianceStatus, ComplianceDocumentType } from '../types/compliance';
import { ComplianceStatus as StatusEnum, ComplianceDocumentType as DocTypeEnum } from '../types/compliance';
import { PageContainer } from '@/shared/components/ui/PageContainer';
import { DataTable } from '@/shared/components/ui/DataTable';
import { StatusBadge } from '@/shared/components/ui/StatusBadge';
import { useAppShell } from '@/shared/layouts/appShellContext';
import type { ColumnDef, ActionDef } from '@/shared/types/table';

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

export function ComplianceList() {
  const navigate = useNavigate();
  const { setPageTitle } = useAppShell();
  const [statusFilter, setStatusFilter] = useState<string>('');
  const [typeFilter, setTypeFilter] = useState<string>('');
  const [page, setPage] = useState(1);

  const filters: ComplianceFilters = {
    page,
    per_page: 15,
    status: (statusFilter || undefined) as ComplianceStatus | undefined,
    type: (typeFilter || undefined) as ComplianceDocumentType | undefined,
  };

  const { data, isLoading, isError, refetch } = useComplianceDocuments(filters);

  useEffect(() => {
    setPageTitle('Compliance');
  }, [setPageTitle]);

  const documents = data?.data ?? [];
  const pagination = data?.meta?.pagination;

  const columns: ColumnDef<ComplianceDocument>[] = [
    {
      header: 'Type',
      accessorKey: 'type',
      cell: ({ getValue }) => {
        const val = getValue() as string;
        return typeLabels[val] ?? val;
      },
    },
    {
      header: 'Status',
      accessorKey: 'status',
      cell: ({ getValue }) => {
        const val = getValue() as ComplianceStatus;
        return <StatusBadge status={val} variant={statusVariant[val] ?? 'neutral'} />;
      },
    },
    { header: 'Filename', accessorKey: 'original_filename' },
    { header: 'Expires', accessorKey: 'expires_at', cell: ({ getValue }) => (getValue() as string) ?? '-' },
  ];

  const actions: ActionDef<ComplianceDocument>[] = [
    {
      label: 'View',
      onClick: (row) => navigate(`/app/compliance/${row.id}`),
      variant: 'primary',
    },
    {
      label: 'Review',
      onClick: (row) => navigate(`/app/compliance/${row.id}/review`),
    },
  ];

  return (
    <PageContainer
      title="Compliance Documents"
      description="Manage vehicle, driver, and contractor compliance documents"
      actions={
        <button className="btn btn-primary" onClick={() => navigate('/app/compliance/upload')}>
          Upload Document
        </button>
      }
    >
      <div style={{ display: 'flex', gap: 'var(--space-2)', marginBottom: 'var(--space-4)' }}>
        <select
          className="form-input"
          value={statusFilter}
          onChange={(e) => { setStatusFilter(e.target.value); setPage(1); }}
          style={{ maxWidth: 200 }}
          aria-label="Filter by status"
        >
          <option value="">All Statuses</option>
          <option value={StatusEnum.Pending}>Pending</option>
          <option value={StatusEnum.Approved}>Approved</option>
          <option value={StatusEnum.Rejected}>Rejected</option>
          <option value={StatusEnum.Expired}>Expired</option>
        </select>
        <select
          className="form-input"
          value={typeFilter}
          onChange={(e) => { setTypeFilter(e.target.value); setPage(1); }}
          style={{ maxWidth: 200 }}
          aria-label="Filter by type"
        >
          <option value="">All Types</option>
          <option value={DocTypeEnum.VehicleRegistration}>Vehicle Registration</option>
          <option value={DocTypeEnum.Insurance}>Insurance</option>
          <option value={DocTypeEnum.DriverLicense}>Driver License</option>
          <option value={DocTypeEnum.ContractDocument}>Contract</option>
          <option value={DocTypeEnum.Other}>Other</option>
        </select>
      </div>

      <DataTable<ComplianceDocument>
        data={documents}
        columns={columns}
        actions={actions}
        isLoading={isLoading}
        isError={isError}
        errorMessage="Failed to load compliance documents"
        onRetry={() => refetch()}
        emptyTitle="No documents found"
        emptyDescription="Upload a compliance document to get started."
        emptyActionLabel="Upload Document"
        emptyAction={() => navigate('/app/compliance/upload')}
        page={pagination?.current_page}
        pageCount={pagination?.last_page}
        total={pagination?.total}
        onPageChange={setPage}
      />
    </PageContainer>
  );
}
