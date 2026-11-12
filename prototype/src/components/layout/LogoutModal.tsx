import {
  ModalOverlay,
  Modal,
  Dialog,
  Heading,
} from 'react-aria-components';
import type { Role, UserProfile } from '@/types';
import {
  RiLogoutBoxRLine,
  RiCloseLine,
  RiShieldCheckLine,
} from '@remixicon/react';

export interface LogoutModalProps {
  isOpen: boolean;
  onClose: () => void;
  onConfirm: () => void;
  currentRole: Role;
  user: UserProfile;
}

interface RoleModalConfig {
  title: string;
  subtitle: string;
  roleBadge: string;
  badgeStyle: string;
  notice: string;
}

export function LogoutModal({
  isOpen,
  onClose,
  onConfirm,
  currentRole,
  user,
}: LogoutModalProps) {
  // Role-specific intentional messaging matching legacy PHP LMS workflows
  const getRoleConfig = (): RoleModalConfig => {
    switch (currentRole) {
      case 'student':
        return {
          title: 'Log Out of Student Portal?',
          subtitle:
            'Are you sure you want to end your student session? Your submitted performance tasks and quiz scores are safely saved.',
          roleBadge: 'Student Portal',
          badgeStyle: 'bg-accent/10 text-accent border-accent/20',
          notice:
            'You can sign back in anytime with your Student ID (LRN) and password.',
        };
      case 'teacher':
        return {
          title: 'Log Out of Faculty Portal?',
          subtitle:
            'Are you sure you want to end your teaching session? Please make sure any pending gradebook inputs, attendance checks, or classroom links are saved.',
          roleBadge: 'Faculty Portal',
          badgeStyle: 'bg-purple-bg text-purple border-purple/30',
          notice:
            'All uploaded learning modules and published announcements remain live for your learners.',
        };
      case 'admin':
        return {
          title: 'Log Out of Admin Console?',
          subtitle:
            'Are you sure you want to exit the Administrator Console? System audit logs will securely record your session sign-off.',
          roleBadge: 'Administrator',
          badgeStyle: 'bg-amber-bg text-amber border-amber/30',
          notice:
            'Institutional portal background services and backups will continue running normally.',
        };
      default:
        return {
          title: 'Sign Out of ExceLearn?',
          subtitle:
            'Are you sure you want to end your current session? You will be returned to the portal login screen.',
          roleBadge: 'Active Session',
          badgeStyle: 'bg-accent/10 text-accent border-accent/20',
          notice:
            'Your session credentials will be cleared from this browser window.',
        };
    }
  };

  const config = getRoleConfig();

  return (
    <ModalOverlay
      isOpen={isOpen}
      onOpenChange={(open) => {
        if (!open) onClose();
      }}
      isDismissable
      className={({ isEntering, isExiting }) =>
        `fixed inset-0 z-[200] flex min-h-full items-center justify-center p-4 sm:p-6 bg-black/60 backdrop-blur-xs transition-opacity duration-200 ease-out ${
          isEntering ? 'opacity-0' : isExiting ? 'opacity-0' : 'opacity-100'
        }`
      }
    >
      <Modal
        className={({ isEntering, isExiting }) =>
          `relative w-full max-w-[420px] rounded-2xl bg-bg2/95 backdrop-blur-xl border border-border shadow-2xl p-6 sm:p-7 text-center overflow-hidden transition-all duration-200 ease-out select-none will-change-transform ${
            isEntering
              ? 'scale-95 opacity-0'
              : isExiting
                ? 'scale-95 opacity-0'
                : 'scale-100 opacity-100'
          }`
        }
      >
        <Dialog
          role="alertdialog"
          aria-describedby="logout-dialog-desc"
          className="outline-none"
        >
          {({ close }) => (
            <>
              {/* Top-right close button */}
              <button
                type="button"
                onClick={close}
                aria-label="Close dialog"
                className="absolute top-4 right-4 p-1.5 rounded-lg text-text3 hover:text-text hover:bg-bg3 cursor-pointer transition-colors press-scale outline-none focus-visible:ring-2 focus-visible:ring-accent"
              >
                <RiCloseLine className="size-4" aria-hidden="true" />
              </button>

              {/* Red Icon Badge */}
              <div className="size-14 rounded-2xl bg-red-bg border border-red/25 text-red flex items-center justify-center mx-auto mb-3.5 shadow-xs">
                <RiLogoutBoxRLine className="size-7 text-red" aria-hidden="true" />
              </div>

              {/* Dialog Title & Description */}
              <Heading
                slot="title"
                className="text-lg font-bold font-serif text-text"
              >
                {config.title}
              </Heading>
              <p
                id="logout-dialog-desc"
                className="text-xs text-text2 mt-1.5 leading-relaxed"
              >
                {config.subtitle}
              </p>

              {/* Intentional User Identity Preview */}
              <div className="my-4 p-3 rounded-xl bg-bg3/80 border border-border/80 flex items-center gap-3 text-left">
                <div
                  className="size-10 rounded-full text-white font-semibold text-xs flex items-center justify-center shrink-0 shadow-xs"
                  style={{
                    background:
                      'linear-gradient(135deg, var(--cyan), var(--accent))',
                  }}
                >
                  {user.initials}
                </div>
                <div className="min-w-0 flex-1">
                  <div className="font-semibold text-xs sm:text-sm text-text truncate">
                    {user.name}
                  </div>
                  <div className="text-[11px] text-text3 truncate">
                    {user.subLabel}
                  </div>
                </div>
                <span
                  className={`shrink-0 text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-md border ${config.badgeStyle}`}
                >
                  {config.roleBadge}
                </span>
              </div>

              {/* Safety advisory note */}
              <div className="flex items-start gap-2 p-2.5 rounded-xl bg-bg3/60 border border-border/70 text-[11px] text-text3 text-left mb-5">
                <RiShieldCheckLine
                  className="size-3.5 text-accent shrink-0 mt-0.5"
                  aria-hidden="true"
                />
                <span className="leading-tight">{config.notice}</span>
              </div>

              {/* Action Buttons */}
              <div className="flex items-center gap-2.5">
                <button
                  autoFocus
                  type="button"
                  onClick={close}
                  className="flex-1 py-2.5 px-4 rounded-xl text-xs sm:text-sm font-semibold text-text2 hover:text-text bg-bg3 hover:bg-bg4 border border-border cursor-pointer transition-colors active:scale-95 outline-none focus-visible:ring-2 focus-visible:ring-accent"
                >
                  Stay Signed In
                </button>
                <button
                  type="button"
                  onClick={onConfirm}
                  className="flex-1 py-2.5 px-4 rounded-xl text-xs sm:text-sm font-semibold text-white bg-red hover:bg-red/90 border border-red/30 cursor-pointer shadow-xs transition-colors active:scale-95 flex items-center justify-center gap-1.5 outline-none focus-visible:ring-2 focus-visible:ring-red"
                >
                  <RiLogoutBoxRLine className="size-4 shrink-0" aria-hidden="true" />
                  <span>Log Out</span>
                </button>
              </div>
            </>
          )}
        </Dialog>
      </Modal>
    </ModalOverlay>
  );
}
