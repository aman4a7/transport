import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Loader2, Fuel, Gauge, AlertTriangle } from 'lucide-react';
import { useFuelStock, useRestockFuel, useAdjustFuel } from '../hooks/useFuel';
import { restockFuelSchema, adjustFuelSchema, type RestockFuelFormData, type AdjustFuelFormData } from '../schemas/fuelSchema';
import { PageContainer } from '@/shared/components/ui/PageContainer';
import { LoadingState } from '@/shared/components/ui/LoadingState';
import { EmptyState } from '@/shared/components/ui/EmptyState';
import { KpiCard } from '@/shared/components/ui/KpiCard';
import { useAppShell } from '@/shared/layouts/appShellContext';

export function FuelStock() {
  const navigate = useNavigate();
  const { setPageTitle } = useAppShell();
  const [restockType, setRestockType] = useState<string>('diesel');
  const [adjustType, setAdjustType] = useState<string>('diesel');
  const [serverError, setServerError] = useState<string | null>(null);
  const [activeTab, setActiveTab] = useState<'stock' | 'restock' | 'adjust'>('stock');

  const { data: stocks, isLoading } = useFuelStock();
  const restockMutation = useRestockFuel();
  const adjustMutation = useAdjustFuel();

  useEffect(() => {
    setPageTitle('Fuel Stock Management');
  }, [setPageTitle]);

  const {
    register: registerRestock,
    handleSubmit: handleSubmitRestock,
    formState: { errors: restockErrors },
    reset: resetRestock,
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  } = useForm<any>({
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    resolver: zodResolver(restockFuelSchema) as any,
  });

  const {
    register: registerAdjust,
    handleSubmit: handleSubmitAdjust,
    formState: { errors: adjustErrors },
    reset: resetAdjust,
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  } = useForm<any>({
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    resolver: zodResolver(adjustFuelSchema) as any,
  });

  function fieldError(errors: Record<string, unknown>, name: string): string | undefined {
    return (errors[name] as { message?: string } | undefined)?.message;
  }

  const inputStyle = (hasError: boolean): React.CSSProperties => ({
    width: '100%',
    padding: 'var(--space-2) var(--space-3)',
    fontSize: 'var(--text-sm)',
    color: 'var(--color-text)',
    background: 'var(--color-surface)',
    border: `1px solid ${hasError ? 'var(--color-danger)' : 'var(--color-border)'}`,
    borderRadius: 'var(--radius-md)',
    outline: 'none',
  });

  const selectStyle = (hasError: boolean): React.CSSProperties => ({
    ...inputStyle(hasError),
    appearance: 'none',
  });

  async function onRestock(raw: Record<string, unknown>) {
    setServerError(null);
    try {
      const data = raw as unknown as RestockFuelFormData;
      await restockMutation.mutateAsync({
        fuel_type: data.fuel_type,
        quantity: data.quantity,
        unit_cost: data.unit_cost || undefined,
        notes: data.notes || undefined,
      });
      resetRestock();
      setActiveTab('stock');
    } catch {
      setServerError('Failed to restock fuel');
    }
  }

  async function onAdjust(raw: Record<string, unknown>) {
    setServerError(null);
    try {
      const data = raw as unknown as AdjustFuelFormData;
      await adjustMutation.mutateAsync({
        fuel_type: data.fuel_type,
        quantity: data.quantity,
        notes: data.notes,
      });
      resetAdjust();
      setActiveTab('stock');
    } catch {
      setServerError('Failed to adjust stock');
    }
  }

  if (isLoading) {
    return <PageContainer title="Fuel Stock"><LoadingState variant="skeleton" type="card" rows={2} /></PageContainer>;
  }

  if (!stocks || stocks.length === 0) {
    return (
      <PageContainer title="Fuel Stock">
        <EmptyState title="No stock data" description="No fuel stock records found." actionLabel="Back to Transactions" onAction={() => navigate('/app/fuel')} />
      </PageContainer>
    );
  }

  return (
    <PageContainer
      title="Fuel Stock Management"
      actions={
        <div style={{ display: 'flex', gap: 'var(--space-2)' }}>
          <button className={`btn ${activeTab === 'stock' ? 'btn-primary' : 'btn-secondary'} btn-sm`} onClick={() => setActiveTab('stock')}>Stock Levels</button>
          <button className={`btn ${activeTab === 'restock' ? 'btn-primary' : 'btn-secondary'} btn-sm`} onClick={() => setActiveTab('restock')}>Restock</button>
          <button className={`btn ${activeTab === 'adjust' ? 'btn-primary' : 'btn-secondary'} btn-sm`} onClick={() => setActiveTab('adjust')}>Adjust</button>
        </div>
      }
    >
      {serverError && (
        <div role="alert" style={{ padding: 'var(--space-3)', marginBottom: 'var(--space-4)', background: 'var(--color-danger-light)', border: '1px solid var(--red-200)', borderRadius: 'var(--radius-md)', fontSize: 'var(--text-sm)', color: 'var(--color-danger)' }}>
          {serverError}
        </div>
      )}

      {activeTab === 'stock' && (
        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))', gap: 'var(--space-4)' }}>
          {stocks.map((stock) => {
            return (
              <div key={stock.id} className="card" style={{ padding: 'var(--space-6)' }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: 'var(--space-3)', marginBottom: 'var(--space-4)' }}>
                  <Fuel size={24} />
                  <h3 style={{ fontSize: 'var(--text-lg)', textTransform: 'capitalize' }}>{stock.fuel_type}</h3>
                </div>
                <KpiCard
                  label="Current Quantity"
                  value={`${stock.current_quantity.toFixed(1)} L`}
                  icon={Gauge}
                />
                <div style={{ marginTop: 'var(--space-3)', display: 'flex', gap: 'var(--space-2)', alignItems: 'center' }}>
                  <span style={{ fontSize: 'var(--text-sm)', color: 'var(--color-text-secondary)' }}>
                    Min: {stock.minimum_quantity.toFixed(1)} L
                  </span>
                  {stock.current_quantity < stock.minimum_quantity && (
                    <span style={{ display: 'flex', alignItems: 'center', gap: 4, fontSize: 'var(--text-xs)', color: 'var(--color-warning)' }}>
                      <AlertTriangle size={12} /> Below minimum
                    </span>
                  )}
                </div>
              </div>
            );
          })}
        </div>
      )}

      {activeTab === 'restock' && (
        <div className="card" style={{ padding: 'var(--space-6)', maxWidth: 500 }}>
          <h3 style={{ fontSize: 'var(--text-lg)', marginBottom: 'var(--space-4)' }}>Restock Fuel</h3>
          <form onSubmit={handleSubmitRestock(onRestock)} noValidate>
            <div style={{ marginBottom: 'var(--space-4)' }}>
              <label style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                Fuel Type <span style={{ color: 'var(--color-danger)' }}>*</span>
              </label>
              <select {...registerRestock('fuel_type')} value={restockType} onChange={(e) => setRestockType(e.target.value)} style={selectStyle(!!fieldError(restockErrors, 'fuel_type'))}>
                <option value="diesel">Diesel</option>
                <option value="petrol">Petrol</option>
              </select>
            </div>
            <div style={{ marginBottom: 'var(--space-4)' }}>
              <label style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                Quantity (L) <span style={{ color: 'var(--color-danger)' }}>*</span>
              </label>
              <input type="number" step="0.1" min="0" {...registerRestock('quantity')} style={inputStyle(!!fieldError(restockErrors, 'quantity'))} />
            </div>
            <div style={{ marginBottom: 'var(--space-4)' }}>
              <label style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                Unit Cost (optional)
              </label>
              <input type="number" step="0.01" min="0" {...registerRestock('unit_cost')} style={inputStyle(!!fieldError(restockErrors, 'unit_cost'))} />
            </div>
            <div style={{ marginBottom: 'var(--space-4)' }}>
              <label style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                Notes
              </label>
              <textarea rows={2} {...registerRestock('notes')} style={inputStyle(!!fieldError(restockErrors, 'notes'))} />
            </div>
            <button type="submit" className="btn btn-primary" disabled={restockMutation.isPending}>
              {restockMutation.isPending && <Loader2 size={16} className="spin" />}
              Restock
            </button>
          </form>
        </div>
      )}

      {activeTab === 'adjust' && (
        <div className="card" style={{ padding: 'var(--space-6)', maxWidth: 500 }}>
          <h3 style={{ fontSize: 'var(--text-lg)', marginBottom: 'var(--space-4)' }}>Adjust Stock</h3>
          <form onSubmit={handleSubmitAdjust(onAdjust)} noValidate>
            <div style={{ marginBottom: 'var(--space-4)' }}>
              <label style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                Fuel Type <span style={{ color: 'var(--color-danger)' }}>*</span>
              </label>
              <select {...registerAdjust('fuel_type')} value={adjustType} onChange={(e) => setAdjustType(e.target.value)} style={selectStyle(!!fieldError(adjustErrors, 'fuel_type'))}>
                <option value="diesel">Diesel</option>
                <option value="petrol">Petrol</option>
              </select>
            </div>
            <div style={{ marginBottom: 'var(--space-4)' }}>
              <label style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                Quantity (+/- L) <span style={{ color: 'var(--color-danger)' }}>*</span>
              </label>
              <input type="number" step="0.1" {...registerAdjust('quantity')} style={inputStyle(!!fieldError(adjustErrors, 'quantity'))} />
              <span style={{ fontSize: 'var(--text-xs)', color: 'var(--color-text-secondary)' }}>Use positive to add, negative to deduct</span>
            </div>
            <div style={{ marginBottom: 'var(--space-4)' }}>
              <label style={{ display: 'block', fontSize: 'var(--text-sm)', fontWeight: 500, marginBottom: 'var(--space-1)' }}>
                Reason <span style={{ color: 'var(--color-danger)' }}>*</span>
              </label>
              <textarea rows={3} {...registerAdjust('notes')} style={inputStyle(!!fieldError(adjustErrors, 'notes'))} placeholder="Explain why this adjustment is needed (min 10 characters)" />
            </div>
            <button type="submit" className="btn btn-warning" disabled={adjustMutation.isPending}>
              {adjustMutation.isPending && <Loader2 size={16} className="spin" />}
              Adjust Stock
            </button>
          </form>
        </div>
      )}
    </PageContainer>
  );
}
