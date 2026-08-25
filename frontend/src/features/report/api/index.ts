import axiosClient from '@/shared/api/axiosClient';
import type { ApiResponse } from '@/shared/types/api';
import type { ReportType, ReportData, ReportFilters } from '../types';

export const reportApi = {
  list: () =>
    axiosClient.get<ApiResponse<ReportType[]>>('/reports'),
  show: (type: string, params?: ReportFilters) =>
    axiosClient.get<ApiResponse<ReportData>>(`/reports/${type}`, { params }),
};
