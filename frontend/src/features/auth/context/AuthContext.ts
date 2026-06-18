import { createContext, useContext } from 'react';
import type { User } from '@/shared/types/auth';

export type AuthStatus = 'loading' | 'authenticated' | 'unauthenticated';

export interface AuthContextValue {
  user: User | null;
  status: AuthStatus;
  isAuthenticated: boolean;
  isLoading: boolean;
  login: (payload: { email: string; password: string }) => Promise<User>;
  logout: () => Promise<void>;
  loginError: string | null;
  isLoginPending: boolean;
  isLogoutPending: boolean;
}

export const AuthContext = createContext<AuthContextValue | null>(null);

export function useAuthContext(): AuthContextValue {
  const ctx = useContext(AuthContext);
  if (!ctx) {
    throw new Error('useAuthContext must be used within AuthProvider');
  }
  return ctx;
}
