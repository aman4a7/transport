import { useQuery } from '@tanstack/react-query';
import { reportApi } from '../api';
import type { ReportFilters } from '../types';

export function useReportTypes() {
  return useQuery({
    queryKey: ['reports', 'types'],
    queryFn: () => reportApi.list().then((r) => r.data.data),
  });
}

export function useReport(type: string, filters?: ReportFilters) {
  return useQuery({
    queryKey: ['reports', type, filters],
    queryFn: () => reportApi.show(type, filters).then((r) => r.data.data),
    enabled: !!type,
  });
}
