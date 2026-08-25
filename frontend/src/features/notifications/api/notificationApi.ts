import axiosClient from '@/shared/api/axiosClient';
import type { ApiResponse } from '@/shared/types/api';
import type {
  AppNotification,
  NotificationFilters,
  NotificationPreference,
  NotificationPreferenceMap,
  NotificationsResponse,
} from '../types/notification';

export const notificationApi = {
  list: (params: NotificationFilters) =>
    axiosClient.get<NotificationsResponse>('/notifications', { params }),
  markRead: (id: number) =>
    axiosClient.patch<ApiResponse<AppNotification>>(`/notifications/${id}/read`),
  markAllRead: () =>
    axiosClient.patch<ApiResponse<{ marked: number }>>('/notifications/read-all'),
  getPreferences: () =>
    axiosClient.get<ApiResponse<NotificationPreference>>('/notifications/preferences'),
  updatePreferences: (preferences: NotificationPreferenceMap) =>
    axiosClient.patch<ApiResponse<NotificationPreference>>('/notifications/preferences', {
      preferences,
    }),
};