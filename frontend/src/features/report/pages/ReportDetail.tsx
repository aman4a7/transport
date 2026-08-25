import { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import { useReport } from '../hooks/useReports';
import { PageContainer } from '@/shared/components/ui/PageContainer';
import { KpiCard } from '@/shared/components/ui/KpiCard';
import { LoadingState } from '@/shared/components/ui/LoadingState';
import { useAppShell } from '@/shared/layouts/appShellContext';
import { Truck, Fuel, Route, Wrench, ShieldCheck, FileText, Users, Calendar } from 'lucide-react';

const reportLabels: Record<string, string> = {
  fleet_summary: 'Fleet Summary',
  fuel_consumption: 'Fuel Consumption',
  trip_analysis: 'Trip Analysis',
  maintenance_summary: 'Maintenance Summary',
  compliance_status: 'Compliance Status',
  contract_performance: 'Contract Performance',
  passenger_utilization: 'Passenger Utilization',
};

function getAggregateCards(type: string, aggregates: Record<string, unknown>) {
  const cards: { label: string; value: string | number; icon: typeof Truck }[] = [];

  switch (type) {
    case 'fleet_summary':
      cards.push({ label: 'Total Vehicles', value: (aggregates.total_vehicles as number) ?? 0, icon: Truck });
      if (aggregates.by_category && typeof aggregates.by_category === 'object') {
        const cat = aggregates.by_category as Record<string, number>;
        Object.entries(cat).forEach(([k, v]) => {
          cards.push({ label: `${k.replace('_', ' ')}`, value: v, icon: Truck });
        });
      }
      if (aggregates.by_status && typeof aggregates.by_status === 'object') {
        const st = aggregates.by_status as Record<string, number>;
        Object.entries(st).forEach(([k, v]) => {
          cards.push({ label: `${k.replace('_', ' ')}`, value: v, icon: Truck });
        });
      }
      break;
    case 'fuel_consumption':
      cards.push({ label: 'Total Litres', value: (aggregates.total_quantity_litres as number) ?? 0, icon: Fuel });
      cards.push({ label: 'Transactions', value: (aggregates.total_transactions as number) ?? 0, icon: Fuel });
      break;
    case 'trip_analysis':
      cards.push({ label: 'Total Trips', value: (aggregates.total_trips as number) ?? 0, icon: Route });
      cards.push({ label: 'Assignments', value: (aggregates.total_assignments as number) ?? 0, icon: Route });
      break;
    case 'maintenance_summary':
      cards.push({ label: 'Total Requests', value: (aggregates.total_requests as number) ?? 0, icon: Wrench });
      cards.push({ label: 'Total Cost', value: `ETB ${(aggregates.total_cost as number)?.toLocaleString() ?? 0}`, icon: Wrench });
      break;
    case 'compliance_status':
      cards.push({ label: 'Total Documents', value: (aggregates.total_documents as number) ?? 0, icon: ShieldCheck });
      cards.push({ label: 'Expiring Soon', value: (aggregates.expiring_soon as number) ?? 0, icon: ShieldCheck });
      cards.push({ label: 'Expired', value: (aggregates.expired as number) ?? 0, icon: ShieldCheck });
      break;
    case 'contract_performance':
      cards.push({ label: 'Total Contracts', value: (aggregates.total_contracts as number) ?? 0, icon: FileText });
      cards.push({ label: 'Total Value', value: `ETB ${(aggregates.total_value as number)?.toLocaleString() ?? 0}`, icon: FileText });
      break;
    case 'passenger_utilization':
      cards.push({ label: 'Total Assignments', value: (aggregates.total_assignments as number) ?? 0, icon: Users });
      cards.push({ label: 'Passenger Trips', value: (aggregates.passenger_trips as number) ?? 0, icon: Users });
      break;
  }

  return cards;
}

export function ReportDetail() {
  const { type } = useParams<{ type: string }>();
  const { setPageTitle } = useAppShell();
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');

  const filters = {
    ...(dateFrom ? { date_from: dateFrom } : {}),
    ...(dateTo ? { date_to: dateTo } : {}),
  };

  const { data, isLoading, isError, refetch } = useReport(type ?? '', filters);

  const label = type ? (reportLabels[type] ?? type) : 'Report';
  useEffect(() => {
    setPageTitle(label);
  }, [setPageTitle, label]);

  return (
    <PageContainer
      title={label}
      description="View report details and aggregates"
      actions={
        <div style={{ display: 'flex', gap: 'var(--space-2)', alignItems: 'center' }}>
          <Calendar size={16} />
          <input
            type="date"
            className="form-input"
            value={dateFrom}
            onChange={(e) => setDateFrom(e.target.value)}
            aria-label="Date from"
            style={{ maxWidth: 160 }}
          />
          <span>to</span>
          <input
            type="date"
            className="form-input"
            value={dateTo}
            onChange={(e) => setDateTo(e.target.value)}
            aria-label="Date to"
            style={{ maxWidth: 160 }}
          />
        </div>
      }
    >
      {isLoading && <LoadingState variant="skeleton" type="card" />}

      {isError && (
        <div className="empty-state">
          <p>Failed to load report data.</p>
          <button className="btn btn-primary" onClick={() => refetch()}>
            Retry
          </button>
        </div>
      )}

      {data?.aggregates && (
        <div
          style={{
            display: 'grid',
            gridTemplateColumns: 'repeat(auto-fill, minmax(240px, 1fr))',
            gap: 'var(--space-4)',
          }}
        >
          {getAggregateCards(type ?? '', data.aggregates).map((card, i) => (
            <KpiCard key={i} label={card.label} value={card.value} icon={card.icon} />
          ))}
        </div>
      )}

      {!isLoading && !isError && !data?.aggregates && (
        <div className="empty-state">
          <p>No data available for this report.</p>
        </div>
      )}
    </PageContainer>
  );
}
