import { Navigate, Outlet } from 'react-router-dom';
import { useAuth } from '@/shared/hooks/useAuth';
import { LoadingState } from '@/shared/components/ui/LoadingState';

export function AuthGuard() {
  const { isAuthenticated, isLoading } = useAuth();

  if (isLoading) {
    return <LoadingState variant="spinner" message="Checking session..." />;
  }

  if (!isAuthenticated) {
    return <Navigate to="/login" replace />;
  }

  return <Outlet />;
}
