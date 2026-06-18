import { useCallback, useRef, useEffect } from 'react';
import { NavLink, useLocation } from 'react-router-dom';
import { PanelLeftClose, PanelLeft } from 'lucide-react';
import { useAppShell } from './appShellContext';
import { navigationConfig } from '@/shared/config/navigation';
import { useAuthStore } from '@/shared/stores/authStore';

function isItemVisible(
  roles: string[] | undefined,
  permissions: string[] | undefined,
  userRoles: string[],
  userPermissions: string[],
): boolean {
  if (roles && roles.length > 0) {
    const hasRole = roles.some((r) => userRoles.includes(r));
    if (!hasRole) return false;
  }
  if (permissions && permissions.length > 0) {
    const hasPerm = permissions.some((p) => userPermissions.includes(p));
    if (!hasPerm) return false;
  }
  return true;
}

function useActivePath(): string {
  const location = useLocation();
  return location.pathname;
}

export function Sidebar() {
  const {
    sidebarCollapsed,
    toggleSidebar,
    mobileSidebarOpen,
    setMobileSidebarOpen,
    isMobile,
  } = useAppShell();
  const activePath = useActivePath();
  const user = useAuthStore((s) => s.user);
  const sidebarRef = useRef<HTMLElement>(null);

  const userRoleSlugs = user?.roles.map((r) => r.slug) ?? [];
  const userPermissionSlugs: string[] = user?.roles.flatMap((r) =>
    r.permissions.map((p) => p.slug),
  ) ?? [];

  const isActive = useCallback(
    (path: string) => activePath === path || activePath.startsWith(path + '/'),
    [activePath],
  );

  useEffect(() => {
    function handleKeyDown(e: KeyboardEvent) {
      if (e.key === 'Escape' && mobileSidebarOpen && isMobile) {
        setMobileSidebarOpen(false);
      }
    }
    document.addEventListener('keydown', handleKeyDown);
    return () => document.removeEventListener('keydown', handleKeyDown);
  }, [mobileSidebarOpen, isMobile, setMobileSidebarOpen]);

  const sidebarClass = [
    'sidebar',
    sidebarCollapsed ? 'sidebar--collapsed' : '',
    mobileSidebarOpen ? 'sidebar--mobile-open' : '',
    isMobile ? 'sidebar--mobile' : '',
  ]
    .filter(Boolean)
    .join(' ');

  return (
    <aside
      ref={sidebarRef}
      className={sidebarClass}
      role="navigation"
      aria-label="Main navigation"
    >
      <div className="sidebar-header">
        {!sidebarCollapsed && (
          <span className="sidebar-brand">Transport Manager</span>
        )}
        {!isMobile && (
          <button
            type="button"
            className="sidebar-collapse-btn"
            onClick={toggleSidebar}
            aria-label={sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'}
          >
            {sidebarCollapsed ? <PanelLeft size={18} /> : <PanelLeftClose size={18} />}
          </button>
        )}
        {isMobile && (
          <button
            type="button"
            className="sidebar-collapse-btn"
            onClick={() => setMobileSidebarOpen(false)}
            aria-label="Close navigation menu"
          >
            <PanelLeftClose size={18} />
          </button>
        )}
      </div>

      <nav className="sidebar-nav" aria-label="Sidebar navigation">
        {navigationConfig.map((section, sectionIndex) => {
          const visibleItems = section.items.filter((item) =>
            isItemVisible(
              item.roles,
              item.permissions,
              userRoleSlugs,
              userPermissionSlugs,
            ),
          );
          if (visibleItems.length === 0) return null;

          return (
            <div key={sectionIndex} className="sidebar-section">
              {section.label && !sidebarCollapsed && (
                <span className="sidebar-section-label">{section.label}</span>
              )}
              <ul className="sidebar-item-list" role="list">
                {visibleItems.map((item) => (
                  <li key={item.path} className="sidebar-item">
                    <NavLink
                      to={item.path}
                      className={({ isActive: navIsActive }) =>
                        [
                          'sidebar-link',
                          (navIsActive || isActive(item.path))
                            ? 'sidebar-link--active'
                            : '',
                        ].join(' ')
                      }
                      aria-current={
                        isActive(item.path) ? 'page' : undefined
                      }
                      onClick={() => {
                        if (isMobile) setMobileSidebarOpen(false);
                      }}
                    >
                      <item.icon size={20} className="sidebar-link-icon" />
                      {!sidebarCollapsed && (
                        <span className="sidebar-link-label">
                          {item.label}
                        </span>
                      )}
                    </NavLink>
                  </li>
                ))}
              </ul>
            </div>
          );
        })}
      </nav>
    </aside>
  );
}
