export type NotificationType =
  | 'compliance_expiring'
  | 'contract_expiring'
  | 'maintenance_due'
  | 'fuel_low_stock';

export type NotificationPreferenceMap = Record<NotificationType, boolean>;

export interface NotificationPreference {
  id: number;
  user_id: number;
  preferences: NotificationPreferenceMap;
  created_at: string;
  updated_at: string;
  [key: string]: unknown;
}

export interface AppNotification {
  id: number;
  user_id: number;
  type: NotificationType;
  title: string;
  body: string | null;
  data: Record<string, unknown> | null;
  read_at: string | null;
  created_at: string;
  [key: string]: unknown;
}

export interface NotificationFilters {
  page?: number;
  per_page?: number;
  status?: 'unread' | 'read' | '';
  type?: NotificationType | '';
}

export interface NotificationsResponse {
  success: boolean;
  data: AppNotification[];
  meta: {
    pagination: {
      current_page: number;
      total: number;
      per_page: number;
      last_page: number;
    };
    unread_count: number;
  };
}