import { useState, useEffect } from 'react';
import type { Role, UserProfile } from '@/types';
import { PrototypeSwitcher } from '@/components/layout/PrototypeSwitcher';
import {
  RiDashboardLine,
  RiDashboardFill,
  RiStackLine,
  RiStackFill,
  RiFilmLine,
  RiFilmFill,
  RiVideoLine,
  RiVideoFill,
  RiGroupLine,
  RiGroupFill,
  RiCheckboxCircleLine,
  RiCheckboxCircleFill,
  RiUserLine,
  RiUserFill,
  RiSettings3Line,
  RiSettings3Fill,
  RiInformationLine,
  RiInformationFill,
  RiLogoutBoxRLine,
  RiListCheck2,
  RiBarChartLine,
  RiBarChartFill,
  RiFileChartLine,
  RiFileChartFill,
  RiLineChartLine,
  RiLineChartFill,
  RiMenuLine,
  RiCloseLine,
  RiNotification3Line,
  RiMenuFoldLine,
  RiMenuUnfoldLine,
} from '@remixicon/react';

export interface NavItemConfig {
  id: string;
  label: string;
  section: string;
  lineIcon: React.ComponentType<{ className?: string; 'aria-hidden'?: boolean | 'true' | 'false' }>;
  fillIcon: React.ComponentType<{ className?: string; 'aria-hidden'?: boolean | 'true' | 'false' }>;
  badge?: string | number;
  badgeVariant?: 'accent' | 'amber' | 'green' | 'purple' | 'cyan' | 'red' | 'neutral';
  isLogout?: boolean;
}

interface ShellProps {
  currentRole: Role;
  user: UserProfile;
  currentTab: string;
  onTabChange: (tabId: string) => void;
  onSelectRole: (role: Role) => void;
  onLogout: () => void;
  children: React.ReactNode;
}

export function Shell({
  currentRole,
  user,
  currentTab,
  onTabChange,
  onSelectRole,
  onLogout,
  children,
}: ShellProps) {
  const [mobileDrawerOpen, setMobileDrawerOpen] = useState(false);
  const [isCollapsed, setIsCollapsed] = useState(() => {
    try {
      return localStorage.getItem('excelearn_sidebar_collapsed') === 'true';
    } catch {
      return false;
    }
  });

  const toggleSidebarCollapse = () => {
    setIsCollapsed((prev) => {
      const next = !prev;
      try {
        localStorage.setItem('excelearn_sidebar_collapsed', String(next));
      } catch {
        // ignore
      }
      return next;
    });
  };

  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === 'Escape' && mobileDrawerOpen) {
        setMobileDrawerOpen(false);
        return;
      }
      if (
        e.target instanceof HTMLInputElement ||
        e.target instanceof HTMLTextAreaElement ||
        (e.target instanceof HTMLElement && e.target.isContentEditable)
      ) {
        return;
      }
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'b') {
        e.preventDefault();
        toggleSidebarCollapse();
      }
    };
    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [mobileDrawerOpen]);

  // Exact navigation groups & sub-groups from the PHP codebase
  const getNavItems = (): NavItemConfig[] => {
    switch (currentRole) {
      case 'teacher':
        return [
          { id: 'dashboard', label: 'Dashboard', section: 'OVERVIEW', lineIcon: RiDashboardLine, fillIcon: RiDashboardFill },
          { id: 'courses', label: 'Subjects', section: 'TEACHING', lineIcon: RiStackLine, fillIcon: RiStackFill, badge: 0, badgeVariant: 'accent' },
          { id: 'tasks', label: 'Performance Tasks', section: 'TEACHING', lineIcon: RiFilmLine, fillIcon: RiFilmFill, badge: 0, badgeVariant: 'accent' },
          { id: 'live', label: 'Virtual Class', section: 'TEACHING', lineIcon: RiVideoLine, fillIcon: RiVideoFill },
          { id: 'students', label: 'Students', section: 'STUDENTS & GRADES', lineIcon: RiGroupLine, fillIcon: RiGroupFill },
          { id: 'grades', label: 'Gradebook', section: 'STUDENTS & GRADES', lineIcon: RiCheckboxCircleLine, fillIcon: RiCheckboxCircleFill },
          { id: 'profile', label: 'My Profile', section: 'ACCOUNT', lineIcon: RiUserLine, fillIcon: RiUserFill },
          { id: 'settings', label: 'Settings', section: 'ACCOUNT', lineIcon: RiSettings3Line, fillIcon: RiSettings3Fill },
          { id: 'about', label: 'About', section: 'ACCOUNT', lineIcon: RiInformationLine, fillIcon: RiInformationFill },
          { id: 'logout', label: 'Logout', section: 'ACCOUNT', lineIcon: RiLogoutBoxRLine, fillIcon: RiLogoutBoxRLine, isLogout: true },
        ];
      case 'student':
        return [
          { id: 'dashboard', label: 'Dashboard', section: 'OVERVIEW', lineIcon: RiDashboardLine, fillIcon: RiDashboardFill },
          { id: 'courses', label: 'My Subjects', section: 'LEARNING', lineIcon: RiStackLine, fillIcon: RiStackFill, badge: 6, badgeVariant: 'accent' },
          { id: 'tasks', label: 'Performance Tasks', section: 'LEARNING', lineIcon: RiFilmLine, fillIcon: RiFilmFill, badge: 3, badgeVariant: 'accent' },
          { id: 'live', label: 'Virtual Class', section: 'LEARNING', lineIcon: RiVideoLine, fillIcon: RiVideoFill },
          { id: 'grades', label: 'Grades', section: 'PROGRESS', lineIcon: RiCheckboxCircleLine, fillIcon: RiCheckboxCircleFill },
          { id: 'profile', label: 'My Profile', section: 'ACCOUNT', lineIcon: RiUserLine, fillIcon: RiUserFill },
          { id: 'settings', label: 'Settings', section: 'ACCOUNT', lineIcon: RiSettings3Line, fillIcon: RiSettings3Fill },
          { id: 'about', label: 'About', section: 'ACCOUNT', lineIcon: RiInformationLine, fillIcon: RiInformationFill },
          { id: 'logout', label: 'Logout', section: 'ACCOUNT', lineIcon: RiLogoutBoxRLine, fillIcon: RiLogoutBoxRLine, isLogout: true },
        ];
      case 'admin':
        return [
          { id: 'dashboard', label: 'Dashboard', section: 'OVERVIEW', lineIcon: RiDashboardLine, fillIcon: RiDashboardFill },
          { id: 'teachers', label: 'Teachers', section: 'USER MANAGEMENT', lineIcon: RiGroupLine, fillIcon: RiGroupFill, badge: 24, badgeVariant: 'accent' },
          { id: 'students', label: 'Students', section: 'USER MANAGEMENT', lineIcon: RiUserLine, fillIcon: RiUserFill, badge: 486, badgeVariant: 'accent' },
          { id: 'sections', label: 'Grade & Sections', section: 'ACADEMIC', lineIcon: RiListCheck2, fillIcon: RiListCheck2, badge: 12, badgeVariant: 'accent' },
          { id: 'strands', label: 'Strands', section: 'ACADEMIC', lineIcon: RiBarChartLine, fillIcon: RiBarChartFill, badge: 5, badgeVariant: 'accent' },
          { id: 'subjects', label: 'Subjects', section: 'ACADEMIC', lineIcon: RiStackLine, fillIcon: RiStackFill, badge: 18, badgeVariant: 'accent' },
          { id: 'reports', label: 'Reports', section: 'REPORTS AND ANALYTICS', lineIcon: RiFileChartLine, fillIcon: RiFileChartFill },
          { id: 'analytics', label: 'Analytics', section: 'REPORTS AND ANALYTICS', lineIcon: RiLineChartLine, fillIcon: RiLineChartFill },
          { id: 'profile', label: 'My Profile', section: 'ACCOUNT', lineIcon: RiUserLine, fillIcon: RiUserFill },
          { id: 'settings', label: 'Settings', section: 'ACCOUNT', lineIcon: RiSettings3Line, fillIcon: RiSettings3Fill },
          { id: 'about', label: 'About', section: 'ACCOUNT', lineIcon: RiInformationLine, fillIcon: RiInformationFill },
          { id: 'logout', label: 'Logout', section: 'ACCOUNT', lineIcon: RiLogoutBoxRLine, fillIcon: RiLogoutBoxRLine, isLogout: true },
        ];
      default:
        return [];
    }
  };

  const navItems = getNavItems();
  const sections = Array.from(new Set(navItems.map((item) => item.section)));

  const handleNavClick = (item: NavItemConfig) => {
    if (item.isLogout) {
      onLogout();
    } else {
      onTabChange(item.id);
    }
  };

  return (
    <div className="min-h-screen w-full bg-bg text-text flex flex-col lg:flex-row">
      {/* ============================================================ */}
      {/* Desktop Collapsible Sidebar (w-[260px] -> w-[72px])           */}
      {/* ============================================================ */}
      <aside
        className={`hidden lg:flex flex-col h-screen sticky top-0 shrink-0 bg-bg2 border-r border-border select-none z-30 transition-[width] duration-300 [transition-timing-function:cubic-bezier(0.22,1,0.36,1)] will-change-[width] overflow-x-hidden ${
          isCollapsed ? 'w-[72px] sidebar-collapsed' : 'w-[260px]'
        }`}
      >
        {/* Sidebar Logo Header - Fixed 74px height prevents CLS on collapse/expand */}
        <div className={`h-[74px] shrink-0 border-b border-border flex items-center ${isCollapsed ? 'justify-center px-2' : 'px-3.5'}`}>
          {!isCollapsed ? (
            <div className="flex items-center justify-between w-full min-w-0">
              <div className="flex items-center gap-2.5 min-w-0">
                <img
                  src="/img/Excelsior_Seal_Logo.png"
                  alt="ExceLearn Logo"
                  className="h-[38px] w-auto object-contain shrink-0"
                />
                <div className="min-w-0">
                  <div className="logo-name whitespace-nowrap">
                    Exce<span style={{ color: '#22c55e' }}>Learn</span>
                  </div>
                  <div className="logo-sub whitespace-nowrap">
                    Learn Better. Anytime, Anywhere.
                  </div>
                </div>
              </div>
              <button
                type="button"
                onClick={toggleSidebarCollapse}
                title="Collapse sidebar (Ctrl+B)"
                aria-label="Collapse sidebar"
                className="p-1.5 rounded-lg text-text3 hover:text-text hover:bg-bg3 cursor-pointer shrink-0 ml-1 press-scale"
              >
                <RiMenuFoldLine className="size-4" aria-hidden="true" />
              </button>
            </div>
          ) : (
            <button
              type="button"
              onClick={toggleSidebarCollapse}
              title="Expand sidebar (Ctrl+B)"
              aria-label="Expand sidebar"
              className="p-1.5 rounded-xl hover:bg-bg3 cursor-pointer flex items-center justify-center group press-scale"
            >
              <img
                src="/img/Excelsior_Seal_Logo.png"
                alt="ExceLearn Logo"
                className="size-9 object-contain group-hover:scale-105 transition-transform"
              />
              <span className="sr-only">Expand sidebar</span>
            </button>
          )}
        </div>

        {/* Navigation Sections */}
        <nav className={`sidebar-nav flex-1 overflow-y-auto ${isCollapsed ? 'px-2 py-3 space-y-3' : 'px-3 py-3 space-y-4'}`}>
          {sections.map((secName) => {
            const sectionItems = navItems.filter((item) => item.section === secName);
            return (
              <div key={secName} className="space-y-0.5">
                {!isCollapsed ? (
                  <div className="nav-section-label">
                    {secName}
                  </div>
                ) : (
                  <div className="my-2 mx-auto w-8 h-px bg-border/60" aria-hidden="true" />
                )}
                {sectionItems.map((item) => {
                  const isActive = currentTab === item.id;
                  const Icon = isActive ? item.fillIcon : item.lineIcon;

                  if (item.isLogout) {
                    return (
                      <button
                        key={item.id}
                        type="button"
                        onClick={onLogout}
                        title={isCollapsed ? item.label : undefined}
                        aria-label={item.label}
                        className={`nav-item text-red hover:bg-red/10 cursor-pointer mt-1 ${
                          isCollapsed ? '!justify-center !px-0 !border-l-0 h-10 w-10 mx-auto' : ''
                        }`}
                      >
                        <RiLogoutBoxRLine className="w-[18px] h-[18px] shrink-0 text-red opacity-90" aria-hidden="true" />
                        {!isCollapsed && <span className="font-medium text-[13.5px] text-red">{item.label}</span>}
                      </button>
                    );
                  }

                  return (
                    <button
                      key={item.id}
                      type="button"
                      onClick={() => handleNavClick(item)}
                      title={isCollapsed ? item.label : undefined}
                      aria-label={item.label}
                      className={`nav-item excelearn-nav-pill cursor-pointer ${
                        isCollapsed ? '!justify-center !px-0 !border-l-0 relative h-10 w-10 mx-auto' : ''
                      } ${isActive ? 'active' : ''}`}
                    >
                      <Icon className="w-[18px] h-[18px] shrink-0 opacity-85" aria-hidden="true" />
                      {!isCollapsed && <span className="truncate">{item.label}</span>}

                      {item.badge !== undefined && (
                        !isCollapsed ? (
                          <span className="nav-badge size-5 rounded-full bg-accent text-white font-bold text-[11px] flex items-center justify-center shrink-0 ml-auto">
                            {item.badge}
                          </span>
                        ) : (
                          <span className="absolute -top-1 -right-1 size-4 rounded-full bg-accent text-white font-bold text-[9px] flex items-center justify-center shadow-xs">
                            {typeof item.badge === 'number' && item.badge > 99 ? '99+' : item.badge}
                          </span>
                        )
                      )}
                    </button>
                  );
                })}
              </div>
            );
          })}
        </nav>

        {/* Sidebar Footer - Fixed 68px height */}
        <div className={`h-[68px] shrink-0 border-t border-border flex items-center ${isCollapsed ? 'justify-center p-2' : 'p-3.5'}`}>
          {!isCollapsed ? (
            <div className="user-card w-full">
              <div
                className="user-avatar text-white font-semibold text-xs"
                style={{ background: 'linear-gradient(135deg, var(--cyan), var(--accent))' }}
              >
                {user.initials}
              </div>
              <div className="user-info min-w-0">
                <p className="truncate">{user.name}</p>
                <span className="truncate">{user.subLabel}</span>
              </div>
            </div>
          ) : (
            <div
              className="user-avatar text-white font-semibold text-xs cursor-pointer hover:ring-2 hover:ring-accent transition-all shrink-0 press-scale"
              style={{ background: 'linear-gradient(135deg, var(--cyan), var(--accent))' }}
              title={`${user.name} (${user.subLabel})`}
            >
              {user.initials}
            </div>
          )}
        </div>
      </aside>

      {/* ============================================================ */}
      {/* Main Content Area                                            */}
      {/* ============================================================ */}
      <div className="flex-1 flex flex-col min-w-0 pb-20 lg:pb-8">
        {/* Topbar matching PHP .topbar */}
        <header className="sticky top-0 z-20 h-14 topbar-material px-4 sm:px-6 flex items-center justify-between">
          <div className="flex items-center gap-3">
            {/* Mobile Drawer Trigger */}
            <button
              type="button"
              onClick={() => setMobileDrawerOpen(true)}
              className="lg:hidden p-1.5 text-text2 hover:text-text hover:bg-bg3 rounded-lg cursor-pointer press-scale"
              aria-label="Open Navigation Drawer"
            >
              <RiMenuLine className="size-5" aria-hidden="true" />
            </button>

            {/* Desktop Sidebar Expand Trigger (shown when sidebar is collapsed) */}
            {isCollapsed && (
              <button
                type="button"
                onClick={toggleSidebarCollapse}
                className="hidden lg:flex p-1.5 text-text2 hover:text-text hover:bg-bg3 rounded-lg cursor-pointer press-scale"
                aria-label="Expand sidebar (Ctrl+B)"
                title="Expand sidebar (Ctrl+B)"
              >
                <RiMenuUnfoldLine className="size-5" aria-hidden="true" />
              </button>
            )}

            {/* School Long Logo matching PHP */}
            <img
              src="/img/Excelsior_Logo_long.png"
              alt="Excelsior"
              className="h-8 w-auto hidden sm:block object-contain"
            />
          </div>

          {/* Right Action Widgets */}
          <div className="flex items-center gap-2.5">
            {/* Notification Bell */}
            <button
              type="button"
              className="relative p-2 text-text2 hover:text-text hover:bg-bg3 rounded-lg cursor-pointer press-scale"
              aria-label="Notifications"
            >
              <RiNotification3Line className="size-4" aria-hidden="true" />
              <span className="absolute top-1.5 right-1.5 size-2 rounded-full bg-accent animate-pulse" />
            </button>

            {/* Role & Theme Switcher */}
            <PrototypeSwitcher
              currentRole={currentRole}
              onSelectRole={onSelectRole}
              inline
            />
          </div>
        </header>

        {/* Content Body */}
        <main className="flex-1 p-4 sm:p-6 lg:p-8 max-w-[1460px] mx-auto w-full transition-all duration-200">
          {children}
        </main>
      </div>

      {/* ============================================================ */}
      {/* Mobile Fixed Bottom Navigation Bar (h-16 / 64px)              */}
      {/* ============================================================ */}
      <nav
        aria-label="Mobile Bottom Navigation"
        className="lg:hidden fixed bottom-0 left-0 right-0 h-16 mobile-dock-material z-40 flex items-center justify-around px-2"
      >
        {navItems
          .filter((i) => !i.isLogout && i.id !== 'settings' && i.id !== 'about')
          .slice(0, 5)
          .map((item) => {
            const isActive = currentTab === item.id;
            const Icon = isActive ? item.fillIcon : item.lineIcon;

            return (
              <button
                key={item.id}
                type="button"
                onClick={() => onTabChange(item.id)}
                className={`relative flex flex-col items-center justify-center min-w-[54px] h-12 rounded-xl cursor-pointer press-scale ${
                  isActive ? 'text-accent font-semibold' : 'text-text3 hover:text-text'
                }`}
              >
                <Icon className="size-5 shrink-0" aria-hidden="true" />
                <span className="text-[10px] mt-0.5 truncate max-w-[60px]">{item.label}</span>
                {isActive && (
                  <span className="absolute bottom-1 size-1 rounded-full bg-accent" />
                )}
              </button>
            );
          })}
      </nav>

      {/* ============================================================ */}
      {/* Mobile Slide-Over Drawer (§3, §4, §7, §12)                   */}
      {/* ============================================================ */}
      <div
        className={`fixed inset-0 z-50 lg:hidden flex ${
          mobileDrawerOpen ? 'pointer-events-auto' : 'pointer-events-none'
        }`}
        aria-hidden={!mobileDrawerOpen}
        inert={!mobileDrawerOpen ? true : undefined}
      >
        {/* Backdrop */}
        <div
          className={`fixed inset-0 bg-black/60 backdrop-blur-xs transition-opacity duration-300 [transition-timing-function:cubic-bezier(0.22,1,0.36,1)] ${
            mobileDrawerOpen ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none'
          }`}
          onClick={() => setMobileDrawerOpen(false)}
        />

        {/* Drawer Pane */}
        <div
          className={`relative w-72 max-w-[80vw] h-full drawer-material border-r border-border p-5 flex flex-col justify-between shadow-2xl z-10 overflow-y-auto transform transition-transform duration-300 [transition-timing-function:cubic-bezier(0.22,1,0.36,1)] ${
            mobileDrawerOpen ? 'translate-x-0' : '-translate-x-full'
          }`}
        >
          <div>
            <div className="sidebar-logo !p-0 !border-b pb-4 mb-4 flex items-center justify-between">
              <div className="flex items-center gap-2.5">
                <img
                  src="/img/Excelsior_Seal_Logo.png"
                  alt="Logo"
                  className="size-8 rounded-full"
                />
                <div>
                  <div className="logo-name !text-base">
                    Exce<span style={{ color: '#22c55e' }}>Learn</span>
                  </div>
                  <div className="logo-sub !text-[9px]">
                    Learn Better. Anytime, Anywhere.
                  </div>
                </div>
              </div>
              <button
                type="button"
                onClick={() => setMobileDrawerOpen(false)}
                className="p-1 rounded-lg text-text3 hover:text-text hover:bg-bg3 cursor-pointer press-scale"
                aria-label="Close Drawer"
              >
                <RiCloseLine className="size-5" aria-hidden="true" />
              </button>
            </div>

            <div className="py-2 space-y-4">
              {sections.map((secName) => {
                const sectionItems = navItems.filter((item) => item.section === secName);
                return (
                  <div key={secName} className="space-y-0.5">
                    <div className="nav-section-label">
                      {secName}
                    </div>
                    {sectionItems.map((item) => {
                      const isActive = currentTab === item.id;
                      const Icon = isActive ? item.fillIcon : item.lineIcon;

                      if (item.isLogout) {
                        return (
                          <button
                            key={item.id}
                            type="button"
                            onClick={() => {
                              setMobileDrawerOpen(false);
                              onLogout();
                            }}
                            className="nav-item text-red hover:bg-red/10 cursor-pointer mt-1"
                          >
                            <RiLogoutBoxRLine className="w-[18px] h-[18px] text-red" aria-hidden="true" />
                            <span className="text-red font-medium">{item.label}</span>
                          </button>
                        );
                      }

                      return (
                        <button
                          key={item.id}
                          type="button"
                          onClick={() => {
                            onTabChange(item.id);
                            setMobileDrawerOpen(false);
                          }}
                          className={`nav-item excelearn-nav-pill cursor-pointer ${
                            isActive ? 'active' : ''
                          }`}
                        >
                          <Icon className="w-[18px] h-[18px] shrink-0" aria-hidden="true" />
                          <span className="truncate">{item.label}</span>
                          {item.badge !== undefined && (
                            <span className="nav-badge size-5 rounded-full bg-accent text-white font-bold text-[11px] flex items-center justify-center shrink-0 ml-auto">
                              {item.badge}
                            </span>
                          )}
                        </button>
                      );
                    })}
                  </div>
                );
              })}
            </div>
          </div>

          <div className="pt-4 border-t border-border">
            <div className="user-card">
              <div
                className="user-avatar text-white font-semibold text-xs"
                style={{ background: 'linear-gradient(135deg, var(--cyan), var(--accent))' }}
              >
                {user.initials}
              </div>
              <div className="user-info min-w-0">
                <p className="truncate">{user.name}</p>
                <span className="truncate">{user.subLabel}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
