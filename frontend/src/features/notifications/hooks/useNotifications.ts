import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { notificationApi } from '../api/notificationApi';
import type { NotificationFilters, NotificationPreferenceMap } from '../types/notification';

export function useNotifications(filters: NotificationFilters) {
  return useQuery({
    queryKey: ['notifications', filters],
    queryFn: () => notificationApi.list(filters).then((r) => r.data),
    placeholderData: (prev) => prev,
  });
}

export function useNotificationPreferences() {
  return useQuery({
    queryKey: ['notifications', 'preferences'],
    queryFn: () => notificationApi.getPreferences().then((r) => r.data.data),
  });
}

export function useUpdateNotificationPreferences() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (preferences: NotificationPreferenceMap) =>
      notificationApi.updatePreferences(preferences).then((r) => r.data.data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['notifications', 'preferences'] });
    },
  });
}

export function useUnreadCount() {
  return useQuery<number>({
    queryKey: ['notifications', 'unreadCount'],
    queryFn: () =>
      notificationApi.list({ page: 1, per_page: 1 }).then((r) => r.data.meta.unread_count),
    refetchInterval: 60_000,
  });
}

export function useMarkNotificationRead() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (id: number) => notificationApi.markRead(id).then((r) => r.data.data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['notifications'] });
      qc.invalidateQueries({ queryKey: ['notifications', 'unreadCount'] });
    },
  });
}

export function useMarkAllRead() {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: () => notificationApi.markAllRead().then((r) => r.data.data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['notifications'] });
      qc.invalidateQueries({ queryKey: ['notifications', 'unreadCount'] });
    },
  });
}