import { useState, type ReactNode } from 'react';
import { Sidebar } from './Sidebar';
import { Topbar } from './Topbar';
import { AppShellContext } from './appShellContext';
import { useMediaQuery } from '@/shared/hooks/useMediaQuery';

interface AppShellProps {
  children: ReactNode;
}

export function AppShell({ children }: AppShellProps) {
  const [sidebarCollapsed, setSidebarCollapsed] = useState(false);
  const [mobileSidebarOpen, setMobileSidebarOpen] = useState(false);
  const [pageTitle, setPageTitle] = useState('Dashboard');
  const isMobile = useMediaQuery('(max-width: 1023px)');

  const toggleSidebar = () => {
    if (isMobile) {
      setMobileSidebarOpen((prev) => !prev);
    } else {
      setSidebarCollapsed((prev) => !prev);
    }
  };

  const contextValue = {
    sidebarCollapsed,
    toggleSidebar,
    mobileSidebarOpen,
    setMobileSidebarOpen,
    pageTitle,
    setPageTitle,
    isMobile,
  };

  return (
    <AppShellContext.Provider value={contextValue}>
      <div
        className={`app-shell${sidebarCollapsed && !isMobile ? ' sidebar-collapsed' : ''}`}
      >
        <Sidebar />
        <div className="app-main">
          <Topbar />
          <main className="app-content" id="main-content" role="main">
            {children}
          </main>
        </div>
      </div>
      {mobileSidebarOpen && isMobile && (
        <div
          className="sidebar-backdrop"
          onClick={() => setMobileSidebarOpen(false)}
          aria-hidden="true"
        />
      )}
    </AppShellContext.Provider>
  );
}
