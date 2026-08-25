export interface ReportType {
  type: string;
  label: string;
  description: string;
}

export interface ReportData {
  type: string;
  label: string;
  aggregates: Record<string, unknown>;
}

export interface ReportFilters {
  date_from?: string;
  date_to?: string;
}

export interface ReportAggregates {
  total_vehicles?: number;
  total_transactions?: number;
  total_trips?: number;
  total_requests?: number;
  total_documents?: number;
  total_contracts?: number;
  total_assignments?: number;
  total_quantity_litres?: number;
  total_cost?: number;
  total_value?: number;
  [key: string]: unknown;
}
