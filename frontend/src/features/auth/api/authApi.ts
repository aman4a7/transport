import axiosClient from '@/shared/api/axiosClient';
import { fetchCsrfCookie } from '@/shared/api/apiEnvelope';
import type { ApiResponse, User } from '@/shared/types';

export interface LoginPayload {
  email: string;
  password: string;
}

export interface LoginError {
  message: string;
  errors?: Record<string, string[]>;
}

export const authApi = {
  login: async (payload: LoginPayload): Promise<User> => {
    await fetchCsrfCookie();
    const response = await axiosClient.post<ApiResponse<User>>('/auth/login', payload);
    return response.data.data;
  },

  logout: async (): Promise<void> => {
    await axiosClient.post('/auth/logout');
  },

  me: async (): Promise<User> => {
    const response = await axiosClient.get<ApiResponse<User>>('/auth/me');
    return response.data.data;
  },
};
