import { useMutation, useQuery } from '@tanstack/react-query';
import axiosClient from '@/shared/api/axiosClient';
import { fetchCsrfCookie } from '@/shared/api/apiEnvelope';
import { useAuthStore } from '@/shared/stores/authStore';
import type { ApiResponse, User } from '@/shared/types';

export function useAuth() {
  const { user, isAuthenticated, setUser, clearUser } = useAuthStore();

  const loginMutation = useMutation({
    mutationFn: async (payload: { email: string; password: string }) => {
      await fetchCsrfCookie();
      const response = await axiosClient.post<ApiResponse<User>>('/auth/login', payload);
      return response.data.data;
    },
    onSuccess: (user) => setUser(user),
  });

  const logoutMutation = useMutation({
    mutationFn: async () => {
      await axiosClient.post('/auth/logout');
    },
    onSuccess: () => clearUser(),
  });

  const meQuery = useQuery({
    queryKey: ['auth', 'me'],
    queryFn: async () => {
      const response = await axiosClient.get<ApiResponse<User>>('/auth/me');
      return response.data.data;
    },
    retry: false,
    staleTime: 5 * 60 * 1000,
  });

  return {
    user,
    isAuthenticated,
    login: loginMutation.mutateAsync,
    logout: logoutMutation.mutateAsync,
    isLoginPending: loginMutation.isPending,
    isLogoutPending: logoutMutation.isPending,
    meQuery,
  };
}
