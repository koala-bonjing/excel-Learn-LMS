import { useState, useEffect } from 'react';
import {
  RiSettings3Line,
  RiGlobalLine,
  RiSunLine,
  RiMoonLine,
  RiNotification3Line,
  RiMailLine,
  RiMessage3Line,
  RiInformationLine,
  RiShieldCheckLine,
  RiKeyLine,
  RiCheckLine,
  RiCloseLine,
  RiAlertLine,
  RiEyeLine,
  RiEyeOffLine,
  RiArrowRightLine,
  RiLockPasswordLine,
} from '@remixicon/react';

export interface AdminSettingsViewProps {
  onTabChange?: (tabId: string) => void;
}

interface ToastState {
  message: string;
  type: 'success' | 'error' | 'info';
}

export interface AdminSettingsState {
  language: 'en' | 'fil' | 'tlh';
  timezone: 'PHT' | 'GMT+8' | 'UTC';
  theme: 'dark' | 'light';
  emailAlerts: boolean;
  emailDigest: boolean;
  smsAlerts: boolean;
  smsReminder: boolean;
  pushBrowser: boolean;
  pushSystem: boolean;
}

const DEFAULT_SETTINGS: AdminSettingsState = {
  language: 'en',
  timezone: 'PHT',
  theme: 'dark',
  emailAlerts: true,
  emailDigest: false,
  smsAlerts: false,
  smsReminder: true, // "SMS for important system events"
  pushBrowser: true,
  pushSystem: true,
};

export function AdminSettingsView({ onTabChange }: AdminSettingsViewProps) {
  // Settings state with localStorage loading
  const [settings, setSettings] = useState<AdminSettingsState>(() => {
    try {
      const stored = localStorage.getItem('excelearn-admin-settings');
      if (stored) return { ...DEFAULT_SETTINGS, ...JSON.parse(stored) };
    } catch {}
    return DEFAULT_SETTINGS;
  });

  // Theme state synced with documentElement
  const [theme, setTheme] = useState<'dark' | 'light'>(() => {
    if (typeof document !== 'undefined') {
      return document.documentElement.classList.contains('dark') ? 'dark' : 'light';
    }
    return 'dark';
  });

  // Password fields
  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [showCurrentPw, setShowCurrentPw] = useState(false);
  const [showNewPw, setShowNewPw] = useState(false);
  const [showConfirmPw, setShowConfirmPw] = useState(false);
  const [passwordError, setPasswordError] = useState('');

  // Toast feedback state
  const [toast, setToast] = useState<ToastState | null>(null);

  const showToast = (message: string, type: 'success' | 'error' | 'info' = 'success') => {
    setToast({ message, type });
    setTimeout(() => {
      setToast((cur) => (cur?.message === message ? null : cur));
    }, 3200);
  };

  // Sync theme with external mutations (e.g. PrototypeSwitcher)
  useEffect(() => {
    if (typeof document === 'undefined') return;
    const root = document.documentElement;
    const observer = new MutationObserver(() => {
      const isDark = root.classList.contains('dark');
      setTheme(isDark ? 'dark' : 'light');
    });
    observer.observe(root, { attributes: true, attributeFilter: ['class'] });
    return () => observer.disconnect();
  }, []);

  // Persist helper
  const updateSetting = <K extends keyof AdminSettingsState>(
    key: K,
    value: AdminSettingsState[K],
    toastText?: string
  ) => {
    setSettings((prev) => {
      const next = { ...prev, [key]: value };
      try {
        localStorage.setItem('excelearn-admin-settings', JSON.stringify(next));
      } catch {}
      return next;
    });
    if (toastText) {
      showToast(toastText, 'success');
    }
  };

  // Handlers
  const handleLanguageChange = (lang: 'en' | 'fil' | 'tlh') => {
    updateSetting('language', lang);
    const label = lang === 'en' ? 'English' : lang === 'fil' ? 'Filipino' : 'Tagalog';
    showToast(`Language updated to ${label}.`, 'success');
  };

  const handleTimezoneChange = (tz: 'PHT' | 'GMT+8' | 'UTC') => {
    updateSetting('timezone', tz);
    showToast(`Timezone updated to ${tz}.`, 'success');
  };

  const handleThemeChange = (newTheme: 'dark' | 'light') => {
    setTheme(newTheme);
    updateSetting('theme', newTheme);
    const root = document.documentElement;
    if (newTheme === 'dark') {
      root.classList.add('dark');
    } else {
      root.classList.remove('dark');
    }
    try {
      localStorage.setItem('excelearn-theme', newTheme);
    } catch {}
    showToast(`Theme updated to ${newTheme === 'dark' ? 'Dark' : 'Light'} Mode.`, 'info');
  };

  const handleToggle = (key: keyof AdminSettingsState, label: string) => {
    const nextVal = !settings[key];
    updateSetting(key, nextVal as any);
    showToast(`${label} ${nextVal ? 'enabled' : 'disabled'}.`, 'success');
  };

  // Admin Password Checklist & Continuous Progress Bar Calculation
  const hasLength = newPassword.length >= 8;
  const hasUpper = /[A-Z]/.test(newPassword);
  const hasNum = /[0-9]/.test(newPassword);
  const pwStrengthCount = (hasLength ? 1 : 0) + (hasUpper ? 1 : 0) + (hasNum ? 1 : 0);

  const handlePasswordSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setPasswordError('');
    if (!currentPassword) {
      setPasswordError('Please enter your current administrator password.');
      showToast('Please enter your current administrator password.', 'error');
      return;
    }
    if (!hasLength || !hasUpper || !hasNum) {
      setPasswordError('Password must satisfy all 3 administrative security requirements.');
      showToast('Password must satisfy all 3 security requirements.', 'error');
      return;
    }
    if (newPassword !== confirmPassword) {
      setPasswordError('Administrator passwords do not match.');
      showToast('Administrator passwords do not match.', 'error');
      return;
    }
    setCurrentPassword('');
    setNewPassword('');
    setConfirmPassword('');
    showToast('Super Administrator credentials updated successfully!', 'success');
  };

  const handlePasswordReset = () => {
    setCurrentPassword('');
    setNewPassword('');
    setConfirmPassword('');
    setPasswordError('');
  };

  return (
    <div className="space-y-6 w-full max-w-5xl mx-auto pb-16 animate-in fade-in duration-200">
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
              Settings
            </h1>
            <p className="text-xs sm:text-sm text-text2 mt-0.5">
              Customize your account preferences and system defaults.
            </p>
          </div>
          <div className="p-3 rounded-2xl bg-accent-glow text-accent border border-accent/20 shrink-0">
            <RiSettings3Line className="size-6 text-accent" aria-hidden="true" />
          </div>
        </div>
      </div>

      {/* Section 1: General */}
      <div className="rounded-2xl border border-border bg-bg2 p-5 sm:p-6 shadow-xs space-y-5">
        <div className="flex items-center gap-2 pb-3 border-b border-border">
          <div className="w-1 h-4 rounded-full bg-accent" />
          <RiGlobalLine className="size-4 text-accent" aria-hidden="true" />
          <h2 className="text-sm sm:text-base font-bold text-text">General</h2>
        </div>

        <div className="space-y-4">
          {/* Row 1: Language */}
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 sm:gap-4 pb-4 border-b border-border/60">
            <div>
              <label className="text-xs sm:text-sm font-semibold text-text block">
                Language
              </label>
              <p className="text-xs text-text3">
                Choose your preferred interface language.
              </p>
            </div>
            <select
              value={settings.language}
              onChange={(e) => handleLanguageChange(e.target.value as 'en' | 'fil' | 'tlh')}
              className="w-full sm:w-56 px-3.5 py-2 rounded-xl bg-bg3 border border-border text-xs sm:text-sm text-text font-medium focus:border-accent focus:ring-1 focus:ring-accent outline-none cursor-pointer transition-all"
            >
              <option value="en">English</option>
              <option value="fil">Filipino</option>
              <option value="tlh">Tagalog</option>
            </select>
          </div>

          {/* Row 2: Timezone */}
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 sm:gap-4">
            <div>
              <label className="text-xs sm:text-sm font-semibold text-text block">
                Timezone
              </label>
              <p className="text-xs text-text3">
                Used for scheduling and due dates.
              </p>
            </div>
            <select
              value={settings.timezone}
              onChange={(e) => handleTimezoneChange(e.target.value as 'PHT' | 'GMT+8' | 'UTC')}
              className="w-full sm:w-56 px-3.5 py-2 rounded-xl bg-bg3 border border-border text-xs sm:text-sm text-text font-medium focus:border-accent focus:ring-1 focus:ring-accent outline-none cursor-pointer transition-all"
            >
              <option value="PHT">Asia/Manila (PHT, UTC+8)</option>
              <option value="GMT+8">GMT+8</option>
              <option value="UTC">UTC</option>
            </select>
          </div>
        </div>
      </div>

      {/* Section 2: Appearance */}
      <div className="rounded-2xl border border-border bg-bg2 p-5 sm:p-6 shadow-xs space-y-5">
        <div className="flex items-center gap-2 pb-3 border-b border-border">
          <div className="w-1 h-4 rounded-full bg-accent" />
          <RiSunLine className="size-4 text-accent" aria-hidden="true" />
          <h2 className="text-sm sm:text-base font-bold text-text">Appearance</h2>
        </div>

        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
          <div>
            <div className="text-xs sm:text-sm font-semibold text-text">Theme</div>
            <p className="text-xs text-text3">Switch between dark and light mode.</p>
          </div>

          <div
            role="radiogroup"
            aria-label="Theme mode"
            className="inline-flex items-center gap-1.5 p-1 rounded-xl bg-bg3 border border-border self-start sm:self-auto"
          >
            <button
              type="button"
              role="radio"
              aria-checked={theme === 'dark'}
              onClick={() => handleThemeChange('dark')}
              className={`flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-semibold cursor-pointer transition-all active:scale-95 ${
                theme === 'dark'
                  ? 'bg-accent/15 border border-accent text-accent shadow-[0_0_16px_var(--accent-glow)] ring-1 ring-accent/30'
                  : 'border border-transparent text-text2 hover:text-text hover:bg-bg2/80'
              }`}
            >
              <RiMoonLine className="size-4 shrink-0" aria-hidden="true" />
              <span>Dark</span>
            </button>

            <button
              type="button"
              role="radio"
              aria-checked={theme === 'light'}
              onClick={() => handleThemeChange('light')}
              className={`flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-semibold cursor-pointer transition-all active:scale-95 ${
                theme === 'light'
                  ? 'bg-accent/15 border border-accent text-accent shadow-[0_0_16px_var(--accent-glow)] ring-1 ring-accent/30'
                  : 'border border-transparent text-text2 hover:text-text hover:bg-bg2/80'
              }`}
            >
              <RiSunLine className="size-4 shrink-0" aria-hidden="true" />
              <span>Light</span>
            </button>
          </div>
        </div>
      </div>

      {/* Section 3: Notification Preferences */}
      <div className="rounded-2xl border border-border bg-bg2 p-5 sm:p-6 shadow-xs space-y-6">
        <div className="flex items-center gap-2 pb-3 border-b border-border">
          <div className="w-1 h-4 rounded-full bg-accent" />
          <RiNotification3Line className="size-4 text-accent" aria-hidden="true" />
          <h2 className="text-sm sm:text-base font-bold text-text">Notification Preferences</h2>
        </div>

        {/* Email Notifications */}
        <div className="space-y-3">
          <div className="flex items-center gap-2 text-xs font-bold text-text uppercase tracking-wider">
            <RiMailLine className="size-4 text-accent" aria-hidden="true" />
            <span>Email Notifications</span>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div className="flex items-center justify-between p-3.5 rounded-xl bg-bg3/60 border border-border/70">
              <div className="space-y-0.5 pr-2">
                <div className="text-xs font-semibold text-text">Email Alerts</div>
                <div className="text-[11px] text-text3">Receive updates via email</div>
              </div>
              <button
                type="button"
                role="switch"
                aria-checked={settings.emailAlerts}
                aria-label="Toggle email alerts"
                onClick={() => handleToggle('emailAlerts', 'Email alerts')}
                className={`relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus-visible:ring-2 focus-visible:ring-accent ${
                  settings.emailAlerts ? 'bg-accent' : 'bg-bg4'
                }`}
              >
                <span
                  className={`pointer-events-none inline-block size-5 transform rounded-full bg-white shadow-md ring-0 transition duration-200 ease-in-out ${
                    settings.emailAlerts ? 'translate-x-5' : 'translate-x-0'
                  }`}
                />
              </button>
            </div>

            <div className="flex items-center justify-between p-3.5 rounded-xl bg-bg3/60 border border-border/70">
              <div className="space-y-0.5 pr-2">
                <div className="text-xs font-semibold text-text">Weekly Digest</div>
                <div className="text-[11px] text-text3">Summary email every Monday</div>
              </div>
              <button
                type="button"
                role="switch"
                aria-checked={settings.emailDigest}
                aria-label="Toggle weekly digest"
                onClick={() => handleToggle('emailDigest', 'Weekly digest')}
                className={`relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus-visible:ring-2 focus-visible:ring-accent ${
                  settings.emailDigest ? 'bg-accent' : 'bg-bg4'
                }`}
              >
                <span
                  className={`pointer-events-none inline-block size-5 transform rounded-full bg-white shadow-md ring-0 transition duration-200 ease-in-out ${
                    settings.emailDigest ? 'translate-x-5' : 'translate-x-0'
                  }`}
                />
              </button>
            </div>
          </div>
        </div>

        {/* SMS Notifications */}
        <div className="space-y-3 pt-3 border-t border-border/60">
          <div className="flex items-center gap-2 text-xs font-bold text-text uppercase tracking-wider">
            <RiMessage3Line className="size-4 text-accent" aria-hidden="true" />
            <span>SMS Notifications</span>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div className="flex items-center justify-between p-3.5 rounded-xl bg-bg3/60 border border-border/70">
              <div className="space-y-0.5 pr-2">
                <div className="text-xs font-semibold text-text">SMS Alerts</div>
                <div className="text-[11px] text-text3">Receive text message notifications</div>
              </div>
              <button
                type="button"
                role="switch"
                aria-checked={settings.smsAlerts}
                aria-label="Toggle SMS alerts"
                onClick={() => handleToggle('smsAlerts', 'SMS alerts')}
                className={`relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus-visible:ring-2 focus-visible:ring-accent ${
                  settings.smsAlerts ? 'bg-accent' : 'bg-bg4'
                }`}
              >
                <span
                  className={`pointer-events-none inline-block size-5 transform rounded-full bg-white shadow-md ring-0 transition duration-200 ease-in-out ${
                    settings.smsAlerts ? 'translate-x-5' : 'translate-x-0'
                  }`}
                />
              </button>
            </div>

            <div className="flex items-center justify-between p-3.5 rounded-xl bg-bg3/60 border border-border/70">
              <div className="space-y-0.5 pr-2">
                <div className="text-xs font-semibold text-text">System Reminder</div>
                <div className="text-[11px] text-text3">SMS for important system events</div>
              </div>
              <button
                type="button"
                role="switch"
                aria-checked={settings.smsReminder}
                aria-label="Toggle system reminder"
                onClick={() => handleToggle('smsReminder', 'System reminder')}
                className={`relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus-visible:ring-2 focus-visible:ring-accent ${
                  settings.smsReminder ? 'bg-accent' : 'bg-bg4'
                }`}
              >
                <span
                  className={`pointer-events-none inline-block size-5 transform rounded-full bg-white shadow-md ring-0 transition duration-200 ease-in-out ${
                    settings.smsReminder ? 'translate-x-5' : 'translate-x-0'
                  }`}
                />
              </button>
            </div>
          </div>
        </div>

        {/* Push Notifications */}
        <div className="space-y-3 pt-3 border-t border-border/60">
          <div className="flex items-center gap-2 text-xs font-bold text-text uppercase tracking-wider">
            <RiNotification3Line className="size-4 text-accent" aria-hidden="true" />
            <span>Push Notifications</span>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div className="flex items-center justify-between p-3.5 rounded-xl bg-bg3/60 border border-border/70">
              <div className="space-y-0.5 pr-2">
                <div className="text-xs font-semibold text-text">Browser Push</div>
                <div className="text-[11px] text-text3">In-app and browser notifications</div>
              </div>
              <button
                type="button"
                role="switch"
                aria-checked={settings.pushBrowser}
                aria-label="Toggle browser push notifications"
                onClick={() => handleToggle('pushBrowser', 'Browser push')}
                className={`relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus-visible:ring-2 focus-visible:ring-accent ${
                  settings.pushBrowser ? 'bg-accent' : 'bg-bg4'
                }`}
              >
                <span
                  className={`pointer-events-none inline-block size-5 transform rounded-full bg-white shadow-md ring-0 transition duration-200 ease-in-out ${
                    settings.pushBrowser ? 'translate-x-5' : 'translate-x-0'
                  }`}
                />
              </button>
            </div>

            <div className="flex items-center justify-between p-3.5 rounded-xl bg-bg3/60 border border-border/70">
              <div className="space-y-0.5 pr-2">
                <div className="text-xs font-semibold text-text">System Alerts</div>
                <div className="text-[11px] text-text3">Maintenance and update alerts</div>
              </div>
              <button
                type="button"
                role="switch"
                aria-checked={settings.pushSystem}
                aria-label="Toggle system alerts"
                onClick={() => handleToggle('pushSystem', 'System alerts')}
                className={`relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus-visible:ring-2 focus-visible:ring-accent ${
                  settings.pushSystem ? 'bg-accent' : 'bg-bg4'
                }`}
              >
                <span
                  className={`pointer-events-none inline-block size-5 transform rounded-full bg-white shadow-md ring-0 transition duration-200 ease-in-out ${
                    settings.pushSystem ? 'translate-x-5' : 'translate-x-0'
                  }`}
                />
              </button>
            </div>
          </div>
        </div>

        {/* Section 4: Info Callout Box */}
        <div className="rounded-xl p-4 bg-bg3/70 border border-border/80 flex items-start gap-3 text-xs text-text2 leading-relaxed">
          <RiInformationLine className="size-5 text-accent shrink-0 mt-0.5" aria-hidden="true" />
          <div>
            <span>Notifications are sent to your registered email and phone number. Update them in your </span>
            <button
              type="button"
              onClick={() => onTabChange?.('profile')}
              className="text-accent underline font-semibold hover:text-accent2 cursor-pointer inline-flex items-center gap-0.5"
            >
              <span>Profile</span>
              <RiArrowRightLine className="size-3" aria-hidden="true" />
            </button>
            <span>.</span>
          </div>
        </div>
      </div>

      {/* Section 5: Super Administrator Credentials */}
      <div className="rounded-2xl border border-border bg-bg2 p-5 sm:p-6 shadow-xs space-y-5">
        <div className="flex items-center gap-2 pb-3 border-b border-border">
          <div className="w-1 h-4 rounded-full bg-accent" />
          <RiShieldCheckLine className="size-4 text-accent" aria-hidden="true" />
          <div>
            <h2 className="text-sm sm:text-base font-bold text-text">
              Super Administrator Credentials
            </h2>
            <p className="text-xs text-text3 mt-0.5">
              Configure system-wide administrative access and root security credentials.
            </p>
          </div>
        </div>

        {/* Institutional Account Identification Callout */}
        <div className="p-4 rounded-xl bg-bg3/80 border border-border text-xs flex items-center justify-between gap-4">
          <div className="space-y-0.5">
            <div className="text-[10px] uppercase font-bold tracking-wider text-text3">
              Institutional Root Account
            </div>
            <div className="font-semibold text-text text-sm">
              excelsiorpolyacad@gmail.com
            </div>
            <div className="text-text2 text-[11px]">
              Institutional Control · System ID #ADM-001 · Security Clearance Level 5
            </div>
          </div>
          <div className="p-2.5 rounded-xl bg-accent-glow text-accent border border-accent/20 shrink-0">
            <RiKeyLine className="size-5" aria-hidden="true" />
          </div>
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
              Current Password <span className="text-red">*</span>
            </label>
            <div className="relative">
              <input
                type={showCurrentPw ? 'text' : 'password'}
                placeholder="Enter your current administrator password"
                value={currentPassword}
                onChange={(e) => setCurrentPassword(e.target.value)}
                className="w-full pl-3.5 pr-10 py-2.5 rounded-xl bg-bg3 border border-border text-xs sm:text-sm text-text placeholder:text-text3 focus:border-accent focus:ring-1 focus:ring-accent outline-none transition-all"
                required
              />
              <button
                type="button"
                onClick={() => setShowCurrentPw(!showCurrentPw)}
                className="absolute right-3 top-1/2 -translate-y-1/2 text-text3 hover:text-text cursor-pointer transition-colors"
                aria-label={showCurrentPw ? 'Hide password' : 'Show password'}
              >
                {showCurrentPw ? <RiEyeOffLine className="size-4" /> : <RiEyeLine className="size-4" />}
              </button>
            </div>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            {/* New Password */}
            <div>
              <label className="block text-xs font-semibold text-text2 mb-1.5">
                New Password <span className="text-red">*</span>
              </label>
              <div className="relative">
                <input
                  type={showNewPw ? 'text' : 'password'}
                  placeholder="Enter new super-admin password"
                  value={newPassword}
                  onChange={(e) => setNewPassword(e.target.value)}
                  className="w-full pl-3.5 pr-10 py-2.5 rounded-xl bg-bg3 border border-border text-xs sm:text-sm text-text placeholder:text-text3 focus:border-accent focus:ring-1 focus:ring-accent outline-none transition-all"
                  required
                />
                <button
                  type="button"
                  onClick={() => setShowNewPw(!showNewPw)}
                  className="absolute right-3 top-1/2 -translate-y-1/2 text-text3 hover:text-text cursor-pointer transition-colors"
                  aria-label={showNewPw ? 'Hide password' : 'Show password'}
                >
                  {showNewPw ? <RiEyeOffLine className="size-4" /> : <RiEyeLine className="size-4" />}
                </button>
              </div>

              {/* Continuous Progress Bar */}
              <div className="mt-2.5 h-1.5 w-full bg-bg4 rounded-full overflow-hidden">
                <div
                  className={`h-full transition-all duration-300 ease-out ${
                    pwStrengthCount === 1
                      ? 'w-1/3 bg-red'
                      : pwStrengthCount === 2
                      ? 'w-2/3 bg-amber'
                      : pwStrengthCount === 3
                      ? 'w-full bg-green'
                      : 'w-0'
                  }`}
                />
              </div>

              {/* Real-Time 3-Item Rule Checklist */}
              <div className="mt-3 p-3.5 rounded-xl bg-bg3/50 border border-border/80 space-y-2 text-xs">
                <div className="text-[11px] font-semibold text-text2 uppercase tracking-wider mb-1">
                  Security Checklist Requirements
                </div>

                {/* Rule 1: Min 8 chars */}
                <div className={`flex items-center gap-2 ${hasLength ? 'text-green font-medium' : 'text-text3'}`}>
                  {hasLength ? (
                    <RiCheckLine className="size-4 text-green shrink-0" aria-hidden="true" />
                  ) : (
                    <RiCloseLine className="size-4 text-text3 shrink-0" aria-hidden="true" />
                  )}
                  <span>Minimum 8 characters</span>
                </div>

                {/* Rule 2: Upper */}
                <div className={`flex items-center gap-2 ${hasUpper ? 'text-green font-medium' : 'text-text3'}`}>
                  {hasUpper ? (
                    <RiCheckLine className="size-4 text-green shrink-0" aria-hidden="true" />
                  ) : (
                    <RiCloseLine className="size-4 text-text3 shrink-0" aria-hidden="true" />
                  )}
                  <span>At least one uppercase letter</span>
                </div>

                {/* Rule 3: Number */}
                <div className={`flex items-center gap-2 ${hasNum ? 'text-green font-medium' : 'text-text3'}`}>
                  {hasNum ? (
                    <RiCheckLine className="size-4 text-green shrink-0" aria-hidden="true" />
                  ) : (
                    <RiCloseLine className="size-4 text-text3 shrink-0" aria-hidden="true" />
                  )}
                  <span>At least one number</span>
                </div>
              </div>
            </div>

            {/* Confirm New Password */}
            <div>
              <label className="block text-xs font-semibold text-text2 mb-1.5">
                Confirm New Password <span className="text-red">*</span>
              </label>
              <div className="relative">
                <input
                  type={showConfirmPw ? 'text' : 'password'}
                  placeholder="Confirm new administrator password"
                  value={confirmPassword}
                  onChange={(e) => setConfirmPassword(e.target.value)}
                  className="w-full pl-3.5 pr-10 py-2.5 rounded-xl bg-bg3 border border-border text-xs sm:text-sm text-text placeholder:text-text3 focus:border-accent focus:ring-1 focus:ring-accent outline-none transition-all"
                  required
                />
                <button
                  type="button"
                  onClick={() => setShowConfirmPw(!showConfirmPw)}
                  className="absolute right-3 top-1/2 -translate-y-1/2 text-text3 hover:text-text cursor-pointer transition-colors"
                  aria-label={showConfirmPw ? 'Hide password' : 'Show password'}
                >
                  {showConfirmPw ? <RiEyeOffLine className="size-4" /> : <RiEyeLine className="size-4" />}
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
              Reset Form
            </button>
            <button
              type="submit"
              className="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-accent hover:bg-accent2 text-white text-xs sm:text-sm font-semibold shadow-xs cursor-pointer active:scale-95 transition-all"
            >
              <RiLockPasswordLine className="size-4" aria-hidden="true" />
              <span>Update Password</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
