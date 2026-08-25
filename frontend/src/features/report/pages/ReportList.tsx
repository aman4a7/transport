import { useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useReportTypes } from '../hooks/useReports';
import type { ReportType } from '../types';
import { PageContainer } from '@/shared/components/ui/PageContainer';
import { KpiCard } from '@/shared/components/ui/KpiCard';
import { LoadingState } from '@/shared/components/ui/LoadingState';
import { useAppShell } from '@/shared/layouts/appShellContext';
import { Truck, Fuel, Route, Wrench, ShieldCheck, FileText, Users } from 'lucide-react';

const reportIcons: Record<string, typeof Truck> = {
  fleet_summary: Truck,
  fuel_consumption: Fuel,
  trip_analysis: Route,
  maintenance_summary: Wrench,
  compliance_status: ShieldCheck,
  contract_performance: FileText,
  passenger_utilization: Users,
};

export function ReportList() {
  const navigate = useNavigate();
  const { setPageTitle } = useAppShell();
  const { data: reports, isLoading, isError, refetch } = useReportTypes();

  useEffect(() => {
    setPageTitle('Reports');
  }, [setPageTitle]);

  if (isLoading) {
    return (
      <PageContainer title="Reports" description="Generate and view fleet reports">
        <LoadingState variant="skeleton" type="card" />
      </PageContainer>
    );
  }

  if (isError) {
    return (
      <PageContainer title="Reports" description="Generate and view fleet reports">
        <div className="empty-state">
          <p>Failed to load reports.</p>
          <button className="btn btn-primary" onClick={() => refetch()}>
            Retry
          </button>
        </div>
      </PageContainer>
    );
  }

  if (!reports || reports.length === 0) {
    return (
      <PageContainer title="Reports" description="Generate and view fleet reports">
        <div className="empty-state">
          <p>No reports available.</p>
        </div>
      </PageContainer>
    );
  }

  return (
    <PageContainer title="Reports" description="Generate and view fleet reports">
      <div
        style={{
          display: 'grid',
          gridTemplateColumns: 'repeat(auto-fill, minmax(280px, 1fr))',
          gap: 'var(--space-4)',
        }}
      >
        {reports.map((report: ReportType) => {
          const Icon = reportIcons[report.type] ?? Truck;
          return (
            <KpiCard
              key={report.type}
              label={report.label}
              value={report.description}
              icon={Icon}
              onClick={() => navigate(`/app/reports/${report.type}`)}
            />
          );
        })}
      </div>
    </PageContainer>
  );
}
