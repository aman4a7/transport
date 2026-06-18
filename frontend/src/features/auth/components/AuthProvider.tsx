import { type ReactNode, useEffect, useState, useCallback } from 'react';
import { AuthContext, type AuthStatus } from '../context/AuthContext';
import { authApi } from '../api/authApi';
import { useAuthStore } from '@/shared/stores/authStore';
import type { User } from '@/shared/types/auth';

interface AuthProviderProps {
  children: ReactNode;
}

export function AuthProvider({ children }: AuthProviderProps) {
  const { user: storeUser, isAuthenticated: storeAuth, setUser, clearUser } = useAuthStore();
  const [status, setStatus] = useState<AuthStatus>(() =>
    storeAuth ? 'authenticated' : 'loading',
  );
  const [loginError, setLoginError] = useState<string | null>(null);
  const [isLoginPending, setIsLoginPending] = useState(false);
  const [isLogoutPending, setIsLogoutPending] = useState(false);

  useEffect(() => {
    let cancelled = false;

    async function init() {
      if (storeAuth) {
        setStatus('authenticated');
        return;
      }
      try {
        const user = await authApi.me();
        if (!cancelled) {
          setUser(user);
          setStatus('authenticated');
        }
      } catch {
        if (!cancelled) {
          clearUser();
          setStatus('unauthenticated');
        }
      }
    }

    init();
    return () => {
      cancelled = true;
    };
  }, [storeAuth, setUser, clearUser]);

  const login = useCallback(async (payload: { email: string; password: string }): Promise<User> => {
    setIsLoginPending(true);
    setLoginError(null);
    try {
      const user = await authApi.login(payload);
      setUser(user);
      setStatus('authenticated');
      return user;
    } catch (err: unknown) {
      const message =
        err && typeof err === 'object' && 'response' in err
          ? (err as { response: { data: { message?: string } } }).response.data?.message ??
            'Login failed. Please check your credentials.'
          : 'Login failed. Please try again.';
      setLoginError(message);
      throw err;
    } finally {
      setIsLoginPending(false);
    }
  }, [setUser]);

  const logout = useCallback(async (): Promise<void> => {
    setIsLogoutPending(true);
    try {
      await authApi.logout();
    } finally {
      clearUser();
      setStatus('unauthenticated');
      setLoginError(null);
      setIsLogoutPending(false);
    }
  }, [clearUser]);

  return (
    <AuthContext.Provider
      value={{
        user: storeUser,
        status,
        isAuthenticated: status === 'authenticated' && storeAuth,
        isLoading: status === 'loading',
        login,
        logout,
        loginError,
        isLoginPending,
        isLogoutPending,
      }}
    >
      {children}
    </AuthContext.Provider>
  );
}
