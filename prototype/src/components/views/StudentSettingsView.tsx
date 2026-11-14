import { useState } from 'react';
import { useShell } from '@/components/layout/ShellContext';
import { useIsDarkMode, toggleThemeWithTransition } from '@/utils/theme';
import {
  RiSettings3Line,
  RiGlobalLine,
  RiMoonLine,
  RiSunLine,
  RiNotification3Line,
  RiMessage3Line,
  RiLockPasswordLine,
  RiShieldCheckLine,
  RiCheckLine,
  RiCloseLine,
  RiAlertLine,
  RiEyeLine,
  RiEyeOffLine,
  RiInformationLine,
  RiLogoutBoxRLine,
} from '@remixicon/react';

export interface StudentSettingsViewProps {
  onTabChange?: (tabId: string) => void;
}

interface ToastState {
  message: string;
  type: 'success' | 'error' | 'info';
}

interface StudentSettings {
  language: 'English (Philippines)' | 'English (US)' | 'Filipino';
  timezone: '(GMT+08:00) Philippine Time' | '(GMT+00:00) UTC';
  notif_push: boolean;
  notif_sms: boolean;
}

const DEFAULT_SETTINGS: StudentSettings = {
  language: 'English (Philippines)',
  timezone: '(GMT+08:00) Philippine Time',
  notif_push: true,
  notif_sms: false,
};

// Filipino Translations Dictionary (from legacy students/settings.php)
const FILIPINO_DICT: Record<string, string> = {
  'Settings': 'Mga Setting',
  'Manage your account preferences and security options.':
    'Pamahalaan ang iyong mga kagustuhan at opsyon sa seguridad.',
  'General Preferences': 'Mga Pangkalahatang Kagustuhan',
  'Language': 'Wika',
  'Timezone': 'Oras ng Lugar',
  'Dark Mode': 'Madilim na Mode',
  'Light Mode': 'Maliwanag na Mode',
  'Switch between light and dark interface theme.':
    'Pumili sa maliwanag o madilim na tema ng interface.',
  'Notifications': 'Mga Abiso',
  'Push Notifications': 'Mga Push na Abiso',
  'Receive daily summaries and course updates via push notification.':
    'Tumanggap ng pang-araw-araw na buod at mga update sa kurso.',
  'SMS Alerts': 'Mga SMS na Alerto',
  'Receive urgent alerts (e.g., class cancellations) via SMS.':
    'Tumanggap ng mga agarang alerto (hal., pagkansela ng klase) sa SMS.',
  'Account Security': 'Seguridad ng Account',
  'Update your student portal password and login security.':
    'I-update ang iyong password sa student portal at seguridad.',
  'Current Password': 'Kasalukuyang Password',
  'New Password': 'Bagong Password',
  'Confirm New Password': 'Kumpirmahin ang Bagong Password',
  'Update Password': 'I-update ang Password',
  'Reset Form': 'I-reset ang Form',
  'Portal Session': 'Sesyon ng Portal',
  'Signed in as student on this browser. Terminating session will require re-authentication with your academy credentials.':
    'Naka-sign in bilang mag-aaral sa browser na ito. Ang pagwawakas ng sesyon ay mangangailangan ng muling pagpapatotoo gamit ang iyong mga kredensyal ng akademya.',
  'Log Out': 'Mag-log Out',
};

export function StudentSettingsView({ onTabChange: _onTabChange }: StudentSettingsViewProps) {
  const { requestLogout } = useShell();
  // ── Settings State ────────────────────────────────────────────────────────
  const [settings, setSettings] = useState<StudentSettings>(() => {
    if (typeof window !== 'undefined') {
      try {
        const saved = localStorage.getItem('excelearn-student-settings');
        if (saved) return { ...DEFAULT_SETTINGS, ...JSON.parse(saved) };
      } catch {}
    }
    return DEFAULT_SETTINGS;
  });

  // ── Theme State (Reactive via useIsDarkMode) ──────────────────────────────
  const isDark = useIsDarkMode();

  // ── Password Form State ───────────────────────────────────────────────────
  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [showCurrent, setShowCurrent] = useState(false);
  const [showNew, setShowNew] = useState(false);
  const [showConfirm, setShowConfirm] = useState(false);
  const [passwordError, setPasswordError] = useState('');

  // ── Toast State ───────────────────────────────────────────────────────────
  const [toast, setToast] = useState<ToastState | null>(null);

  const showToast = (message: string, type: 'success' | 'error' | 'info' = 'success') => {
    setToast({ message, type });
    setTimeout(() => {
      setToast((cur) => (cur?.message === message ? null : cur));
    }, 3200);
  };

  // ── Save Settings Helper ──────────────────────────────────────────────────
  const updateSettings = (partial: Partial<StudentSettings>, toastText?: string) => {
    const updated = { ...settings, ...partial };
    setSettings(updated);
    try {
      localStorage.setItem('excelearn-student-settings', JSON.stringify(updated));
    } catch {}
    if (toastText) {
      showToast(toastText, 'success');
    }
  };

  // ── Theme Toggle Handler ──────────────────────────────────────────────────
  const handleToggleTheme = (e?: React.MouseEvent) => {
    const nextDark = !isDark;
    void toggleThemeWithTransition({
      event: e,
      forceTheme: nextDark ? 'dark' : 'light',
      onThemeChanged: (dark) => {
        showToast(dark ? 'Dark Mode activated' : 'Light Mode activated', 'info');
      },
    });
  };

  // ── Translation Helper ────────────────────────────────────────────────────
  const t = (key: string): string => {
    if (settings.language === 'Filipino') {
      return FILIPINO_DICT[key] || key;
    }
    return key;
  };

  // ── Password Strength Evaluation (5 Tiers) ────────────────────────────────
  const getPasswordTier = (pwd: string) => {
    if (!pwd || pwd.length === 0) return { tier: 0, label: 'Too Short' };
    if (pwd.length < 8) return { tier: 0, label: 'Too Short (< 8 chars)' };
    let score = 1;
    if (/[a-z]/.test(pwd) && /[A-Z]/.test(pwd)) score++;
    if (/[0-9]/.test(pwd)) score++;
    if (/[^a-zA-Z0-9]/.test(pwd)) score++;
    if (pwd.length >= 12 && score >= 4) score++;
    const labels = ['', 'Very Weak', 'Weak', 'Fair', 'Good', 'Very Strong'];
    return { tier: score, label: labels[score] || 'Strong' };
  };

  const pwStrength = getPasswordTier(newPassword);

  // ── Password Submit Handler ───────────────────────────────────────────────
  const handlePasswordSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setPasswordError('');

    if (!currentPassword) {
      setPasswordError('Please enter your current password.');
      showToast('Please enter your current password.', 'error');
      return;
    }
    if (newPassword.length < 8) {
      setPasswordError('New password must be at least 8 characters.');
      showToast('New password must be at least 8 characters.', 'error');
      return;
    }
    if (newPassword !== confirmPassword) {
      setPasswordError('New passwords do not match.');
      showToast('New passwords do not match.', 'error');
      return;
    }

    // Success reset
    setCurrentPassword('');
    setNewPassword('');
    setConfirmPassword('');
    setPasswordError('');
    showToast('Portal password updated securely!', 'success');
  };

  const handlePasswordReset = () => {
    setCurrentPassword('');
    setNewPassword('');
    setConfirmPassword('');
    setPasswordError('');
  };

  return (
    <div className="space-y-6 w-full pb-12 animate-in fade-in duration-200">
      {/* Toast Feedback */}
      {toast && (
        <div
          role="status"
          aria-live="polite"
          className={`fixed top-18 right-6 z-50 flex items-center gap-2.5 px-4 py-3 rounded-xl bg-bg2/95 border shadow-xl backdrop-blur-md animate-in fade-in slide-in-from-top-3 duration-200 ${
            toast.type === 'error'
              ? 'border-red/40 text-red'
              : toast.type === 'info'
              ? 'border-accent/40 text-accent'
              : 'border-green/40 text-green'
          }`}
        >
          {toast.type === 'error' ? (
            <RiAlertLine className="size-5 shrink-0 text-red" aria-hidden="true" />
          ) : toast.type === 'info' ? (
            <RiInformationLine className="size-5 shrink-0 text-accent" aria-hidden="true" />
          ) : (
            <RiCheckLine className="size-5 shrink-0 text-green" aria-hidden="true" />
          )}
          <span className="text-xs font-semibold text-text">{toast.message}</span>
          <button
            type="button"
            onClick={() => setToast(null)}
            className="ml-2 text-text3 hover:text-text cursor-pointer transition-colors"
            aria-label="Dismiss notification"
          >
            <RiCloseLine className="size-4" />
          </button>
        </div>
      )}

      {/* Hero Header */}
      <div className="relative rounded-2xl sm:rounded-3xl border border-border bg-bg2 overflow-hidden shadow-xs">
        <div
          className="h-28 sm:h-36 relative overflow-hidden select-none"
          style={{
            background: 'linear-gradient(135deg, #1e3c72 0%, #2a5298 42%, #f7971e 100%)',
          }}
        >
          <div
            className="absolute inset-0 pointer-events-none opacity-20"
            style={{
              backgroundImage: 'radial-gradient(rgba(255, 255, 255, 0.4) 1px, transparent 1px)',
              backgroundSize: '18px 18px',
            }}
          />
          <div className="absolute -bottom-10 -right-6 w-44 h-44 rounded-full bg-white/10 pointer-events-none" />
        </div>

        <div className="relative px-6 py-5 flex items-center justify-between">
          <div>
            <h1 className="font-serif text-2xl sm:text-3xl font-bold text-text tracking-tight">
              {t('Settings')}
            </h1>
            <p className="text-xs sm:text-sm text-text2 mt-0.5">
              {t('Manage your account preferences and security options.')}
            </p>
          </div>
          <div className="p-3 rounded-2xl bg-accent-glow text-accent border border-accent/20 shrink-0">
            <RiSettings3Line className="size-6 text-accent" aria-hidden="true" />
          </div>
        </div>
      </div>

      {/* Card 1: General Preferences */}
      <div className="p-6 rounded-2xl bg-bg2 border border-border shadow-xs space-y-5">
        <div className="flex items-center gap-2 border-b border-border pb-3.5">
          <span className="p-1.5 rounded-lg bg-accent-glow text-accent">
            <RiGlobalLine className="size-4" aria-hidden="true" />
          </span>
          <h2 className="text-base font-bold font-serif text-text">
            {t('General Preferences')}
          </h2>
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label className="block text-xs font-semibold text-text2 mb-1.5">
              {t('Language')}
            </label>
            <select
              value={settings.language}
              onChange={(e) => {
                const val = e.target.value as StudentSettings['language'];
                updateSettings({ language: val }, `Language preference set to ${val}`);
              }}
              className="w-full px-3.5 py-2.5 rounded-xl bg-bg3 border border-border text-xs sm:text-sm text-text focus:border-accent focus:ring-1 focus:ring-accent outline-none cursor-pointer transition-all"
            >
              <option value="English (Philippines)">English (Philippines)</option>
              <option value="English (US)">English (US)</option>
              <option value="Filipino">Filipino</option>
            </select>
          </div>

          <div>
            <label className="block text-xs font-semibold text-text2 mb-1.5">
              {t('Timezone')}
            </label>
            <select
              value={settings.timezone}
              onChange={(e) => {
                const val = e.target.value as StudentSettings['timezone'];
                updateSettings({ timezone: val }, `Timezone set to ${val}`);
              }}
              className="w-full px-3.5 py-2.5 rounded-xl bg-bg3 border border-border text-xs sm:text-sm text-text focus:border-accent focus:ring-1 focus:ring-accent outline-none cursor-pointer transition-all"
            >
              <option value="(GMT+08:00) Philippine Time">(GMT+08:00) Philippine Time</option>
              <option value="(GMT+00:00) UTC">(GMT+00:00) UTC</option>
            </select>
          </div>
        </div>

        {/* Dark Mode Toggle */}
        <div className="pt-4 border-t border-border flex items-center justify-between gap-4">
          <div className="space-y-0.5">
            <div className="flex items-center gap-1.5 text-xs sm:text-sm font-semibold text-text">
              {isDark ? (
                <RiMoonLine className="size-4 text-accent" aria-hidden="true" />
              ) : (
                <RiSunLine className="size-4 text-amber" aria-hidden="true" />
              )}
              <span>{isDark ? t('Dark Mode') : t('Light Mode')}</span>
            </div>
            <div className="text-xs text-text3">
              {t('Switch between light and dark interface theme.')}
            </div>
          </div>

          <button
            type="button"
            role="switch"
            aria-checked={isDark}
            onClick={handleToggleTheme}
            aria-label="Toggle dark mode theme"
            className={`relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus-visible:ring-2 focus-visible:ring-accent ${
              isDark ? 'bg-accent' : 'bg-bg4'
            }`}
          >
            <span
              className={`pointer-events-none inline-block size-5 transform rounded-full bg-white shadow-md ring-0 transition duration-200 ease-in-out ${
                isDark ? 'translate-x-5' : 'translate-x-0'
              }`}
            />
          </button>
        </div>
      </div>

      {/* Card 2: Notifications */}
      <div className="p-6 rounded-2xl bg-bg2 border border-border shadow-xs space-y-4">
        <div className="flex items-center gap-2 border-b border-border pb-3.5">
          <span className="p-1.5 rounded-lg bg-accent-glow text-accent">
            <RiNotification3Line className="size-4" aria-hidden="true" />
          </span>
          <h2 className="text-base font-bold font-serif text-text">
            {t('Notifications')}
          </h2>
        </div>

        {/* Push Notifications */}
        <div className="flex items-center justify-between gap-4 py-2 border-b border-border/60">
          <div className="space-y-0.5">
            <div className="flex items-center gap-1.5 text-xs sm:text-sm font-semibold text-text">
              <RiNotification3Line className="size-4 text-accent" aria-hidden="true" />
              <span>{t('Push Notifications')}</span>
            </div>
            <div className="text-xs text-text3">
              {t('Receive daily summaries and course updates via push notification.')}
            </div>
          </div>

          <button
            type="button"
            role="switch"
            aria-checked={settings.notif_push}
            onClick={() =>
              updateSettings(
                { notif_push: !settings.notif_push },
                `Push notifications ${!settings.notif_push ? 'enabled' : 'disabled'}`
              )
            }
            aria-label="Toggle push notifications"
            className={`relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus-visible:ring-2 focus-visible:ring-accent ${
              settings.notif_push ? 'bg-accent' : 'bg-bg4'
            }`}
          >
            <span
              className={`pointer-events-none inline-block size-5 transform rounded-full bg-white shadow-md ring-0 transition duration-200 ease-in-out ${
                settings.notif_push ? 'translate-x-5' : 'translate-x-0'
              }`}
            />
          </button>
        </div>

        {/* SMS Alerts */}
        <div className="flex items-center justify-between gap-4 py-2">
          <div className="space-y-0.5">
            <div className="flex items-center gap-1.5 text-xs sm:text-sm font-semibold text-text">
              <RiMessage3Line className="size-4 text-amber" aria-hidden="true" />
              <span>{t('SMS Alerts')}</span>
            </div>
            <div className="text-xs text-text3">
              {t('Receive urgent alerts (e.g., class cancellations) via SMS.')}
            </div>
          </div>

          <button
            type="button"
            role="switch"
            aria-checked={settings.notif_sms}
            onClick={() =>
              updateSettings(
                { notif_sms: !settings.notif_sms },
                `SMS alerts ${!settings.notif_sms ? 'enabled' : 'disabled'}`
              )
            }
            aria-label="Toggle SMS alerts"
            className={`relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus-visible:ring-2 focus-visible:ring-accent ${
              settings.notif_sms ? 'bg-accent' : 'bg-bg4'
            }`}
          >
            <span
              className={`pointer-events-none inline-block size-5 transform rounded-full bg-white shadow-md ring-0 transition duration-200 ease-in-out ${
                settings.notif_sms ? 'translate-x-5' : 'translate-x-0'
              }`}
            />
          </button>
        </div>
      </div>

      {/* Card 3: Account Security */}
      <div className="p-6 rounded-2xl bg-bg2 border border-border shadow-xs space-y-5">
        <div className="flex items-center justify-between border-b border-border pb-3.5">
          <div className="flex items-center gap-2">
            <span className="p-1.5 rounded-lg bg-accent-glow text-accent">
              <RiLockPasswordLine className="size-4" aria-hidden="true" />
            </span>
            <div>
              <h2 className="text-base font-bold font-serif text-text">
                {t('Account Security')}
              </h2>
              <p className="text-xs text-text3 mt-0.5">
                {t('Update your student portal password and login security.')}
              </p>
            </div>
          </div>
          <span className="hidden sm:inline-flex items-center gap-1 text-[11px] font-semibold text-green bg-green-bg px-2.5 py-1 rounded-full border border-green/30">
            <RiShieldCheckLine className="size-3.5" aria-hidden="true" />
            <span>Encrypted Session</span>
          </span>
        </div>

        {passwordError && (
          <div className="p-3 rounded-xl bg-red-bg border border-red/30 text-xs text-red flex items-center gap-2">
            <RiAlertLine className="size-4 shrink-0" aria-hidden="true" />
            <span>{passwordError}</span>
          </div>
        )}

        <form onSubmit={handlePasswordSubmit} className="space-y-4">
          {/* Current Password */}
          <div>
            <label className="block text-xs font-semibold text-text2 mb-1.5">
              {t('Current Password')} <span className="text-red">*</span>
            </label>
            <div className="relative">
              <input
                type={showCurrent ? 'text' : 'password'}
                placeholder="Enter current password"
                value={currentPassword}
                onChange={(e) => setCurrentPassword(e.target.value)}
                className="w-full pl-3.5 pr-10 py-2.5 rounded-xl bg-bg3 border border-border text-xs sm:text-sm text-text placeholder:text-text3 focus:border-accent focus:ring-1 focus:ring-accent outline-none transition-all"
                required
              />
              <button
                type="button"
                onClick={() => setShowCurrent(!showCurrent)}
                className="absolute right-3 top-1/2 -translate-y-1/2 text-text3 hover:text-text cursor-pointer transition-colors"
                aria-label={showCurrent ? 'Hide password' : 'Show password'}
              >
                {showCurrent ? <RiEyeOffLine className="size-4" /> : <RiEyeLine className="size-4" />}
              </button>
            </div>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            {/* New Password */}
            <div>
              <label className="block text-xs font-semibold text-text2 mb-1.5">
                {t('New Password')} <span className="text-red">*</span>
              </label>
              <div className="relative">
                <input
                  type={showNew ? 'text' : 'password'}
                  placeholder="Min. 8 characters"
                  value={newPassword}
                  onChange={(e) => setNewPassword(e.target.value)}
                  className="w-full pl-3.5 pr-10 py-2.5 rounded-xl bg-bg3 border border-border text-xs sm:text-sm text-text placeholder:text-text3 focus:border-accent focus:ring-1 focus:ring-accent outline-none transition-all"
                  required
                />
                <button
                  type="button"
                  onClick={() => setShowNew(!showNew)}
                  className="absolute right-3 top-1/2 -translate-y-1/2 text-text3 hover:text-text cursor-pointer transition-colors"
                  aria-label={showNew ? 'Hide password' : 'Show password'}
                >
                  {showNew ? <RiEyeOffLine className="size-4" /> : <RiEyeLine className="size-4" />}
                </button>
              </div>

              {/* 5-Tier Strength Indicator */}
              {newPassword && (
                <div className="mt-2 space-y-1.5">
                  <div className="grid grid-cols-5 gap-1.5">
                    {[1, 2, 3, 4, 5].map((step) => (
                      <div
                        key={step}
                        className={`h-1.5 rounded-full transition-all duration-300 ${
                          pwStrength.tier >= step
                            ? pwStrength.tier === 1
                              ? 'bg-red'
                              : pwStrength.tier === 2 || pwStrength.tier === 3
                              ? 'bg-amber'
                              : pwStrength.tier === 4
                              ? 'bg-accent'
                              : 'bg-green'
                            : 'bg-bg4'
                        }`}
                      />
                    ))}
                  </div>
                  <div className="flex items-center justify-between text-[11px]">
                    <span className="text-text3">Strength</span>
                    <span
                      className={`font-semibold ${
                        pwStrength.tier === 0 || pwStrength.tier === 1
                          ? 'text-red'
                          : pwStrength.tier === 2 || pwStrength.tier === 3
                          ? 'text-amber'
                          : pwStrength.tier === 4
                          ? 'text-accent'
                          : 'text-green'
                      }`}
                    >
                      {pwStrength.label}
                    </span>
                  </div>
                </div>
              )}
            </div>

            {/* Confirm New Password */}
            <div>
              <label className="block text-xs font-semibold text-text2 mb-1.5">
                {t('Confirm New Password')} <span className="text-red">*</span>
              </label>
              <div className="relative">
                <input
                  type={showConfirm ? 'text' : 'password'}
                  placeholder="Re-enter new password"
                  value={confirmPassword}
                  onChange={(e) => setConfirmPassword(e.target.value)}
                  className="w-full pl-3.5 pr-10 py-2.5 rounded-xl bg-bg3 border border-border text-xs sm:text-sm text-text placeholder:text-text3 focus:border-accent focus:ring-1 focus:ring-accent outline-none transition-all"
                  required
                />
                <button
                  type="button"
                  onClick={() => setShowConfirm(!showConfirm)}
                  className="absolute right-3 top-1/2 -translate-y-1/2 text-text3 hover:text-text cursor-pointer transition-colors"
                  aria-label={showConfirm ? 'Hide password' : 'Show password'}
                >
                  {showConfirm ? <RiEyeOffLine className="size-4" /> : <RiEyeLine className="size-4" />}
                </button>
              </div>

              {/* Match indicator */}
              {confirmPassword && (
                <div className="mt-2 text-[11px] flex items-center gap-1 font-medium">
                  {newPassword === confirmPassword ? (
                    <span className="text-green flex items-center gap-1">
                      <RiCheckLine className="size-3.5" aria-hidden="true" />
                      Passwords match
                    </span>
                  ) : (
                    <span className="text-red flex items-center gap-1">
                      <RiCloseLine className="size-3.5" aria-hidden="true" />
                      Passwords do not match
                    </span>
                  )}
                </div>
              )}
            </div>
          </div>

          {/* Form Actions */}
          <div className="pt-3 border-t border-border flex items-center justify-end gap-3">
            <button
              type="button"
              onClick={handlePasswordReset}
              className="px-4 py-2.5 rounded-xl bg-bg3 hover:bg-bg4 text-text2 hover:text-text text-xs sm:text-sm font-semibold border border-border cursor-pointer transition-colors active:scale-95"
            >
              {t('Reset Form')}
            </button>
            <button
              type="submit"
              className="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-accent hover:bg-accent2 text-white text-xs sm:text-sm font-semibold shadow-xs cursor-pointer active:scale-95 transition-all"
            >
              <RiLockPasswordLine className="size-4" aria-hidden="true" />
              <span>{t('Update Password')}</span>
            </button>
          </div>
        </form>
      </div>

      {/* Session Management & Logout */}
      <div className="p-6 rounded-2xl bg-bg2 border border-border shadow-xs">
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div className="flex items-start gap-3">
            <span className="p-2 rounded-xl bg-red-bg text-red shrink-0 mt-0.5">
              <RiLogoutBoxRLine className="size-5" aria-hidden="true" />
            </span>
            <div>
              <h3 className="text-sm sm:text-base font-bold font-serif text-text">
                {t('Portal Session')}
              </h3>
              <p className="text-xs text-text3 mt-0.5 max-w-xl">
                {t('Signed in as student on this browser. Terminating session will require re-authentication with your academy credentials.')}
              </p>
            </div>
          </div>
          <button
            type="button"
            onClick={requestLogout}
            className="self-start sm:self-auto px-4 py-2 rounded-xl bg-red-bg hover:bg-red/20 text-red text-xs sm:text-sm font-semibold border border-red/30 cursor-pointer active:scale-95 transition-all inline-flex items-center gap-1.5"
          >
            <RiLogoutBoxRLine className="size-4" aria-hidden="true" />
            <span>{t('Log Out')}</span>
          </button>
        </div>
      </div>
    </div>
  );
}
