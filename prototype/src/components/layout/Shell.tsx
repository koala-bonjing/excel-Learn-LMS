import { useState } from 'react';
import type { Role, UserProfile } from '@/types';
import { Avatar } from '@/components/base/avatar/avatar';
import { PrototypeSwitcher } from '@/components/layout/PrototypeSwitcher';
import {
  RiDashboardLine,
  RiDashboardFill,
  RiBookOpenLine,
  RiBookOpenFill,
  RiQuestionnaireLine,
  RiQuestionnaireFill,
  RiFileList3Line,
  RiFileList3Fill,
  RiVideoChatLine,
  RiVideoChatFill,
  RiAwardLine,
  RiAwardFill,
  RiShieldCheckLine,
  RiShieldCheckFill,
  RiGroupLine,
  RiGroupFill,
  RiBarChartLine,
  RiBarChartFill,
  RiNotification3Line,
  RiNotification3Fill,
  RiLogoutBoxRLine,
  RiMenuLine,
  RiCloseLine,
  RiGraduationCapLine,
  RiSearchLine,
  RiUser3Line,
  RiUser3Fill,
  RiSettings3Line,
  RiSettings3Fill,
  RiTableLine,
  RiTableFill,
} from '@remixicon/react';

export interface NavItemConfig {
  id: string;
  label: string;
  section: string;
  lineIcon: typeof RiDashboardLine;
  fillIcon: typeof RiDashboardFill;
  badge?: string | number;
  badgeVariant?: 'accent' | 'amber' | 'green' | 'neutral';
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

  // Define nav items based on the actual PHP LMS subsystems
  const getNavItems = (): NavItemConfig[] => {
    switch (currentRole) {
      case 'student':
        return [
          { id: 'dashboard', label: 'Dashboard', section: 'Learning', lineIcon: RiDashboardLine, fillIcon: RiDashboardFill },
          { id: 'courses', label: 'My Courses', section: 'Learning', lineIcon: RiBookOpenLine, fillIcon: RiBookOpenFill, badge: 6, badgeVariant: 'accent' },
          { id: 'quizzes', label: 'Quizzes', section: 'Learning', lineIcon: RiQuestionnaireLine, fillIcon: RiQuestionnaireFill, badge: 2, badgeVariant: 'amber' },
          { id: 'tasks', label: 'Performance Tasks', section: 'Learning', lineIcon: RiFileList3Line, fillIcon: RiFileList3Fill, badge: 3, badgeVariant: 'amber' },
          { id: 'live', label: 'Virtual Class', section: 'Learning', lineIcon: RiVideoChatLine, fillIcon: RiVideoChatFill, badge: 'Live', badgeVariant: 'green' },
          { id: 'grades', label: 'Grades & Report Card', section: 'Learning', lineIcon: RiAwardLine, fillIcon: RiAwardFill },
          { id: 'profile', label: 'My Profile', section: 'Account', lineIcon: RiUser3Line, fillIcon: RiUser3Fill },
          { id: 'settings', label: 'Settings', section: 'Account', lineIcon: RiSettings3Line, fillIcon: RiSettings3Fill },
        ];
      case 'teacher':
        return [
          { id: 'dashboard', label: 'Dashboard', section: 'Teaching', lineIcon: RiDashboardLine, fillIcon: RiDashboardFill },
          { id: 'courses', label: 'Courses & Modules', section: 'Teaching', lineIcon: RiBookOpenLine, fillIcon: RiBookOpenFill, badge: 4, badgeVariant: 'accent' },
          { id: 'quizzes', label: 'Quizzes & Builder', section: 'Teaching', lineIcon: RiQuestionnaireLine, fillIcon: RiQuestionnaireFill, badge: 3, badgeVariant: 'neutral' },
          { id: 'tasks', label: 'Tasks & Grading', section: 'Teaching', lineIcon: RiFileList3Line, fillIcon: RiFileList3Fill, badge: 3, badgeVariant: 'amber' },
          { id: 'grades', label: 'Gradebook', section: 'Teaching', lineIcon: RiTableLine, fillIcon: RiTableFill },
          { id: 'students', label: 'Student Roster', section: 'Teaching', lineIcon: RiGroupLine, fillIcon: RiGroupFill, badge: 142, badgeVariant: 'neutral' },
          { id: 'live', label: 'Virtual Classroom', section: 'Teaching', lineIcon: RiVideoChatLine, fillIcon: RiVideoChatFill, badge: '1 Live', badgeVariant: 'green' },
          { id: 'announcements', label: 'Announcements', section: 'Teaching', lineIcon: RiNotification3Line, fillIcon: RiNotification3Fill },
          { id: 'profile', label: 'Faculty Profile', section: 'Account', lineIcon: RiUser3Line, fillIcon: RiUser3Fill },
          { id: 'settings', label: 'Settings', section: 'Account', lineIcon: RiSettings3Line, fillIcon: RiSettings3Fill },
        ];
      case 'admin':
        return [
          { id: 'dashboard', label: 'Executive Dashboard', section: 'Institutional Control', lineIcon: RiDashboardLine, fillIcon: RiDashboardFill },
          { id: 'teachers', label: 'Faculty Management', section: 'Institutional Control', lineIcon: RiShieldCheckLine, fillIcon: RiShieldCheckFill, badge: 3, badgeVariant: 'amber' },
          { id: 'students', label: 'Students Directory', section: 'Institutional Control', lineIcon: RiGroupLine, fillIcon: RiGroupFill, badge: 486, badgeVariant: 'neutral' },
          { id: 'subjects', label: 'Subjects Catalog', section: 'Institutional Control', lineIcon: RiBookOpenLine, fillIcon: RiBookOpenFill, badge: 18, badgeVariant: 'neutral' },
          { id: 'strands', label: 'Strands & Sections', section: 'Institutional Control', lineIcon: RiBarChartLine, fillIcon: RiBarChartFill, badge: 5, badgeVariant: 'accent' },
          { id: 'profile', label: 'Admin Profile', section: 'Account', lineIcon: RiUser3Line, fillIcon: RiUser3Fill },
          { id: 'settings', label: 'Settings', section: 'Account', lineIcon: RiSettings3Line, fillIcon: RiSettings3Fill },
        ];
      default:
        return [];
    }
  };

  const navItems = getNavItems();

  const getBadgeClass = (variant?: 'accent' | 'amber' | 'green' | 'neutral') => {
    switch (variant) {
      case 'accent':
        return 'bg-accent/15 text-accent';
      case 'amber':
        return 'bg-amber/15 text-amber';
      case 'green':
        return 'bg-green/15 text-green';
      default:
        return 'bg-bg4 text-text2';
    }
  };

  // Group items by section
  const sections = Array.from(new Set(navItems.map((item) => item.section)));

  return (
    <div className="min-h-screen w-full bg-bg text-text flex flex-col lg:flex-row">
      {/* ============================================================ */}
      {/* Desktop Fixed Sidebar (w-[264px])                            */}
      {/* ============================================================ */}
      <aside className="hidden lg:flex flex-col w-[264px] h-screen sticky top-0 shrink-0 bg-bg2 border-r border-border select-none z-30">
        {/* Brand Header */}
        <div className="p-4 flex items-center gap-3 border-b border-border">
          <img
            src="/img/Excelsior_Seal_Logo.png"
            alt="Excelsior Polytechnic Academy"
            className="size-10 rounded-full shadow-xs object-contain"
          />
          <div>
            <div className="flex items-center gap-1.5">
              <span className="text-lg font-bold tracking-tight text-text">
                Exce<span className="text-green">Learn</span>
              </span>
              <span className="px-1.5 py-0.5 text-[9px] font-bold rounded bg-school-orange/15 text-school-orange uppercase tracking-wider">
                SHS
              </span>
            </div>
            <p className="text-[11px] text-text3 truncate max-w-[170px]">
              Excelsior Polytechnic Academy
            </p>
          </div>
        </div>

        {/* Navigation Sections */}
        <div className="flex-1 overflow-y-auto px-3 py-3 space-y-4">
          {sections.map((secName) => {
            const sectionItems = navItems.filter((item) => item.section === secName);
            return (
              <div key={secName} className="space-y-1">
                <div className="px-3 pb-1 text-[10px] font-bold tracking-wider uppercase text-text3">
                  {secName}
                </div>
                {sectionItems.map((item) => {
                  const isActive = currentTab === item.id;
                  const Icon = isActive ? item.fillIcon : item.lineIcon;

                  return (
                    <button
                      key={item.id}
                      type="button"
                      onClick={() => onTabChange(item.id)}
                      className={`w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs sm:text-sm font-medium excelearn-nav-pill cursor-pointer active:scale-[0.98] ${
                        isActive
                          ? 'active shadow-sm font-semibold'
                          : 'text-text2 hover:text-text hover:bg-bg3'
                      }`}
                    >
                      <div className="flex items-center gap-2.5">
                        <Icon className="size-[18px] shrink-0" aria-hidden="true" />
                        <span className="truncate">{item.label}</span>
                      </div>

                      {item.badge !== undefined && (
                        <span
                          className={`nav-badge px-1.5 py-0.5 text-[11px] font-semibold rounded-full tabular-nums ${getBadgeClass(
                            item.badgeVariant
                          )}`}
                        >
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

        {/* DepEd K to 12 MATATAG Mini Card */}
        <div className="mx-3 my-2 p-2.5 rounded-xl bg-bg3 border border-border">
          <div className="flex items-center gap-2 mb-0.5">
            <RiGraduationCapLine className="size-4 text-accent shrink-0" aria-hidden="true" />
            <span className="text-xs font-semibold text-text">DepEd MATATAG</span>
          </div>
          <p className="text-[10px] text-text3 leading-snug">
            Senior High School Standard Curriculum
          </p>
        </div>

        {/* Bottom User Card */}
        <div className="p-3 border-t border-border bg-bg2/90 flex items-center justify-between">
          <div className="flex items-center gap-2.5 min-w-0">
            <Avatar size="md" initials={user.initials} color="blue" />
            <div className="min-w-0">
              <div className="text-xs font-semibold text-text truncate">{user.name}</div>
              <div className="text-[10px] text-text3 truncate">{user.subLabel}</div>
            </div>
          </div>

          <button
            type="button"
            onClick={onLogout}
            title="Log Out / Switch Role"
            className="p-1.5 text-text3 hover:text-red hover:bg-red/10 rounded-lg transition-colors cursor-pointer"
            aria-label="Log Out"
          >
            <RiLogoutBoxRLine className="size-4" aria-hidden="true" />
          </button>
        </div>
      </aside>

      {/* ============================================================ */}
      {/* Main Content Area                                            */}
      {/* ============================================================ */}
      <div className="flex-1 flex flex-col min-w-0 pb-20 lg:pb-8">
        {/* Sticky Topbar */}
        <header className="sticky top-0 z-20 h-14 bg-bg2/85 border-b border-border backdrop-blur-md px-4 sm:px-6 flex items-center justify-between">
          <div className="flex items-center gap-3">
            {/* Mobile Drawer Trigger */}
            <button
              type="button"
              onClick={() => setMobileDrawerOpen(true)}
              className="lg:hidden p-1.5 text-text2 hover:text-text hover:bg-bg3 rounded-lg cursor-pointer"
              aria-label="Open Navigation Drawer"
            >
              <RiMenuLine className="size-5" aria-hidden="true" />
            </button>

            {/* Breadcrumb / Title */}
            <div className="flex items-center gap-2">
              <span className="text-xs font-semibold text-text3 uppercase tracking-wider hidden sm:inline">
                {currentRole}
              </span>
              <span className="text-text3 hidden sm:inline">/</span>
              <span className="text-sm font-semibold text-text capitalize">
                {navItems.find((n) => n.id === currentTab)?.label || currentTab}
              </span>
            </div>
          </div>

          {/* Right Action Widgets */}
          <div className="flex items-center gap-2.5">
            {/* Search Pill */}
            <div className="hidden xl:flex items-center gap-2 px-2.5 py-1.5 rounded-lg bg-bg3 border border-border text-xs text-text3">
              <RiSearchLine className="size-3.5" aria-hidden="true" />
              <span>Search modules, tasks, or records...</span>
            </div>

            {/* Notification Bell */}
            <button
              type="button"
              className="relative p-2 text-text2 hover:text-text hover:bg-bg3 rounded-lg transition-colors cursor-pointer"
              aria-label="Notifications"
            >
              <RiNotification3Line className="size-4" aria-hidden="true" />
              <span className="absolute top-1.5 right-1.5 size-2 rounded-full bg-accent animate-pulse" />
            </button>

            {/* Docked Role & Theme Switcher */}
            <PrototypeSwitcher
              currentRole={currentRole}
              onSelectRole={onSelectRole}
              inline
            />
          </div>
        </header>

        {/* Content Body */}
        <main className="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto w-full">
          {children}
        </main>
      </div>

      {/* ============================================================ */}
      {/* Mobile Fixed Bottom Navigation Bar (h-16 / 64px)              */}
      {/* ============================================================ */}
      <nav
        aria-label="Mobile Bottom Navigation"
        className="lg:hidden fixed bottom-0 left-0 right-0 h-16 bg-bg2/95 border-t border-border backdrop-blur-lg z-40 flex items-center justify-around px-2"
      >
        {navItems.slice(0, 5).map((item) => {
          const isActive = currentTab === item.id;
          const Icon = isActive ? item.fillIcon : item.lineIcon;

          return (
            <button
              key={item.id}
              type="button"
              onClick={() => onTabChange(item.id)}
              className={`relative flex flex-col items-center justify-center min-w-[54px] h-12 rounded-xl transition-all duration-150 cursor-pointer active:scale-95 ${
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
      {/* Mobile Slide-Over Drawer                                     */}
      {/* ============================================================ */}
      {mobileDrawerOpen && (
        <div className="fixed inset-0 z-50 lg:hidden flex">
          {/* Backdrop */}
          <div
            className="fixed inset-0 bg-black/60 backdrop-blur-xs transition-opacity"
            onClick={() => setMobileDrawerOpen(false)}
          />

          {/* Drawer Pane */}
          <div className="relative w-72 max-w-[80vw] h-full bg-bg2 border-r border-border p-5 flex flex-col justify-between shadow-2xl z-10 overflow-y-auto">
            <div>
              <div className="flex items-center justify-between pb-4 border-b border-border">
                <div className="flex items-center gap-2">
                  <img
                    src="/img/Excelsior_Seal_Logo.png"
                    alt="Logo"
                    className="size-8 rounded-full"
                  />
                  <span className="font-bold text-text">Exce<span className="text-green">Learn</span></span>
                </div>
                <button
                  type="button"
                  onClick={() => setMobileDrawerOpen(false)}
                  className="p-1 rounded-lg text-text3 hover:text-text hover:bg-bg3"
                  aria-label="Close Drawer"
                >
                  <RiCloseLine className="size-5" aria-hidden="true" />
                </button>
              </div>

              <div className="py-4 space-y-4">
                {sections.map((secName) => {
                  const sectionItems = navItems.filter((item) => item.section === secName);
                  return (
                    <div key={secName} className="space-y-1">
                      <div className="text-[10px] font-bold uppercase tracking-wider text-text3 px-2">
                        {secName}
                      </div>
                      {sectionItems.map((item) => {
                        const isActive = currentTab === item.id;
                        const Icon = isActive ? item.fillIcon : item.lineIcon;
                        return (
                          <button
                            key={item.id}
                            type="button"
                            onClick={() => {
                              onTabChange(item.id);
                              setMobileDrawerOpen(false);
                            }}
                            className={`w-full flex items-center justify-between px-3 py-2 rounded-xl text-sm font-medium ${
                              isActive
                                ? 'bg-accent/15 text-accent font-semibold'
                                : 'text-text2 hover:text-text hover:bg-bg3'
                            }`}
                          >
                            <div className="flex items-center gap-3">
                              <Icon className="size-5" aria-hidden="true" />
                              <span>{item.label}</span>
                            </div>
                            {item.badge !== undefined && (
                              <span className="px-2 py-0.5 text-xs font-semibold rounded-full bg-bg3 text-text">
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
              <div className="flex items-center gap-2 mb-3">
                <Avatar size="sm" initials={user.initials} color="blue" />
                <div className="truncate">
                  <div className="text-xs font-semibold text-text">{user.name}</div>
                  <div className="text-[10px] text-text3">{user.subLabel}</div>
                </div>
              </div>
              <button
                type="button"
                onClick={() => {
                  setMobileDrawerOpen(false);
                  onLogout();
                }}
                className="w-full flex items-center justify-center gap-2 py-2 text-xs font-medium rounded-lg text-red bg-red/10 hover:bg-red/20 transition-colors"
              >
                <RiLogoutBoxRLine className="size-4" aria-hidden="true" />
                <span>Log Out</span>
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
