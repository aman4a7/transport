import { useState, useRef, useEffect } from 'react';
import { Menu, Bell, ChevronDown, LogOut, UserCircle } from 'lucide-react';
import { useNavigate } from 'react-router-dom';
import { useAppShell } from './appShellContext';
import { useAuth } from '@/shared/hooks/useAuth';

export function Topbar() {
  const { pageTitle, toggleSidebar, mobileSidebarOpen, setMobileSidebarOpen, isMobile } =
    useAppShell();
  const { user, logout, isLogoutPending } = useAuth();
  const navigate = useNavigate();
  const [dropdownOpen, setDropdownOpen] = useState(false);
  const dropdownRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    function handleClickOutside(e: MouseEvent) {
      if (
        dropdownRef.current &&
        !dropdownRef.current.contains(e.target as Node)
      ) {
        setDropdownOpen(false);
      }
    }
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  function handleMobileToggle() {
    if (isMobile) {
      setMobileSidebarOpen(!mobileSidebarOpen);
    } else {
      toggleSidebar();
    }
  }

  async function handleLogout() {
    setDropdownOpen(false);
    await logout();
    navigate('/login', { replace: true });
  }

  const userInitials = user
    ? (user.name?.[0] ?? user.email[0]).toUpperCase()
    : '?';

  return (
    <header className="topbar" role="banner">
      <div className="topbar-left">
        <button
          type="button"
          className="topbar-mobile-toggle"
          onClick={handleMobileToggle}
          aria-label="Toggle navigation menu"
        >
          <Menu size={20} />
        </button>
        <h1 className="topbar-title">{pageTitle}</h1>
      </div>

      <div className="topbar-right">
        <button
          type="button"
          className="topbar-icon-btn"
          aria-label="Notifications"
        >
          <Bell size={20} />
        </button>

        <div className="topbar-user" ref={dropdownRef}>
          <button
            type="button"
            className="topbar-user-btn"
            onClick={() => setDropdownOpen((prev) => !prev)}
            aria-expanded={dropdownOpen}
            aria-haspopup="true"
            aria-label="User menu"
          >
            <div className="topbar-avatar" aria-hidden="true">
              {userInitials}
            </div>
            <span className="topbar-user-name">
              {user?.name ?? user?.email ?? 'User'}
            </span>
            <ChevronDown
              size={14}
              className={`topbar-chevron${dropdownOpen ? ' topbar-chevron--open' : ''}`}
            />
          </button>

          {dropdownOpen && (
            <div className="topbar-dropdown" role="menu">
              <div className="topbar-dropdown-header">
                <UserCircle size={32} />
                <div>
                  <div className="topbar-dropdown-name">
                    {user?.name ?? user?.email ?? 'User'}
                  </div>
                  <div className="topbar-dropdown-email">{user?.email}</div>
                </div>
              </div>
              <div className="topbar-dropdown-divider" />
              <button
                type="button"
                className="topbar-dropdown-item"
                onClick={() => {
                  setDropdownOpen(false);
                  navigate('/app/settings');
                }}
                role="menuitem"
              >
                Settings
              </button>
              <button
                type="button"
                className="topbar-dropdown-item topbar-dropdown-item--danger"
                onClick={handleLogout}
                disabled={isLogoutPending}
                role="menuitem"
              >
                <LogOut size={16} />
                Sign out
              </button>
            </div>
          )}
        </div>
      </div>
    </header>
  );
}
