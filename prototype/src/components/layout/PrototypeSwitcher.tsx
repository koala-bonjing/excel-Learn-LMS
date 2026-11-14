import type { Role } from '@/types';
import {
  RiLoginCircleLine,
  RiGraduationCapLine,
  RiPresentationLine,
  RiShieldCheckLine,
} from '@remixicon/react';
import { ThemeToggle } from '@/components/base/theme-toggle/theme-toggle';

interface PrototypeSwitcherProps {
  currentRole: Role;
  onSelectRole: (role: Role) => void;
  inline?: boolean;
}

export function PrototypeSwitcher({ currentRole, onSelectRole, inline = false }: PrototypeSwitcherProps) {
  const roles: { role: Role; label: string; icon: typeof RiLoginCircleLine; userHint: string }[] = [
    { role: 'login', label: 'Login', icon: RiLoginCircleLine, userHint: 'Auth Portal' },
    { role: 'student', label: 'Student', icon: RiGraduationCapLine, userHint: 'Bryan · STEM 11-A' },
    { role: 'teacher', label: 'Teacher', icon: RiPresentationLine, userHint: 'Ms. Juna Velarde' },
    { role: 'admin', label: 'Admin', icon: RiShieldCheckLine, userHint: 'Institutional Control' },
  ];

  return (
    <div
      role="region"
      aria-label="Prototype Role Switcher"
      className={
        inline
          ? 'flex items-center gap-1 p-1 rounded-full bg-bg3/90 border border-border2/70'
          : 'fixed top-4 right-4 z-50 flex items-center gap-1.5 p-1 rounded-full bg-bg2/90 border border-border2/80 shadow-xl backdrop-blur-md'
      }
    >
      <div className="flex items-center gap-0.5 pl-0.5">
        {roles.map((item) => {
          const Icon = item.icon;
          const isActive = currentRole === item.role;
          return (
            <button
              key={item.role}
              type="button"
              onClick={() => onSelectRole(item.role)}
              title={`${item.label} (${item.userHint})`}
              className={`flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full transition-colors duration-150 cursor-pointer active:scale-96 ${
                isActive
                  ? 'bg-accent text-white shadow-xs'
                  : 'text-text2 hover:text-text hover:bg-bg2'
              }`}
            >
              <Icon className="size-3.5 shrink-0" aria-hidden="true" />
              <span className="hidden sm:inline">{item.label}</span>
            </button>
          );
        })}
      </div>

      <div className="w-px h-4 bg-border mx-0.5" />

      {/* Theme Toggle */}
      <ThemeToggle size="sm" variant="ghost" />
    </div>
  );
}
