import { Outlet } from 'react-router-dom';
import { AppShell } from '@/shared/layouts/AppShell';

export function AppLayout() {
  return (
    <AppShell>
      <Outlet />
    </AppShell>
  );
}
