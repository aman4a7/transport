import { useAuthStore } from '@/shared/stores/authStore';
import { hasPermission, hasRole, hasAnyRole } from '@/shared/utils/permissions';

export function usePermission() {
  const user = useAuthStore((s) => s.user);

  return {
    can: (permissionSlug: string) => hasPermission(user, permissionSlug),
    hasRole: (roleSlug: string) => hasRole(user, roleSlug),
    hasAnyRole: (roleSlugs: string[]) => hasAnyRole(user, roleSlugs),
    user,
  };
}
