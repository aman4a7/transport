import type { User } from '@/shared/types/auth';

export function hasPermission(user: User | null, permissionSlug: string): boolean {
  if (!user) return false;
  return user.roles.some((role) =>
    role.permissions.some((perm) => perm.slug === permissionSlug),
  );
}

export function hasRole(user: User | null, roleSlug: string): boolean {
  if (!user) return false;
  return user.roles.some((role) => role.slug === roleSlug);
}

export function hasAnyRole(user: User | null, roleSlugs: string[]): boolean {
  if (!user) return false;
  return roleSlugs.some((slug) => hasRole(user, slug));
}
