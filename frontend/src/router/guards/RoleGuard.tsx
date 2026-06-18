import { Navigate, Outlet } from 'react-router-dom';
import { useAuthStore } from '@/shared/stores/authStore';
import { hasAnyRole } from '@/shared/utils/permissions';

interface RoleGuardProps {
  allowedRoles: string[];
  redirectTo?: string;
}

export function RoleGuard({ allowedRoles, redirectTo = '/app/dashboard' }: RoleGuardProps) {
  const user = useAuthStore((s) => s.user);

  if (!user || !hasAnyRole(user, allowedRoles)) {
    return <Navigate to={redirectTo} replace />;
  }

  return <Outlet />;
}
