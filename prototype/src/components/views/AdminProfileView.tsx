import { useState } from 'react';
import { useShell } from '@/components/layout/ShellContext';
import {
  RiUserLine,
  RiMailLine,
  RiPhoneLine,
  RiMapPinLine,
  RiShieldCheckLine,
  RiCheckLine,
  RiCloseLine,
  RiEditLine,
  RiComputerLine,
  RiSmartphoneLine,
  RiTimeLine,
  RiKeyLine,
  RiLockPasswordLine,
  RiAlertLine,
  RiCameraLine,
  RiHistoryLine,
  RiGroupLine,
  RiListCheck2,
  RiBarChartLine,
  RiQrCodeLine,
  RiLockLine,
  RiLogoutBoxRLine,
} from '@remixicon/react';

export interface AdminProfileViewProps {
  onTabChange?: (tabId: string) => void;
}

type AdminSubTab = 'overview' | 'edit' | 'security' | 'activity';

interface UserSessionItem {
  id: string;
  device: string;
  browser: string;
  ip: string;
  location: string;
  time: string;
  isCurrent: boolean;
  type: 'desktop' | 'mobile';
}

interface ActivityEvent {
  id: string;
  text: string;
  time: string;
  dotColor: string;
}

export function AdminProfileView({ onTabChange }: AdminProfileViewProps) {
  const { requestLogout } = useShell();
  const [activeSubTab, setActiveSubTab] = useState<AdminSubTab>('overview');

  // Master Admin Profile state (matching tblusers)
  const [profile, setProfile] = useState({
    firstName: 'ExceLearn',
    middleName: 'Institutional',
    lastName: 'Admin',
    email: 'excelsiorpolyacad@gmail.com',
    phone: '+63 917 123 4567',
    gender: 'Male',
    age: 42,
    address: 'Cabitan, Mandaon, Masbate',
    role: 'Admin',
    status: 'Active',
    isVerified: true,
    createdAt: 'Aug 15, 2024',
    updatedAt: 'Just now',
    twoFactorEnabled: true,
    twoFactorSecret: 'JBSW Y3DP EHPK 5NT7',
  });

  const [avatarPreview, setAvatarPreview] = useState<string | null>(null);

  // Form draft for Edit Profile Tab
  const [formDraft, setFormDraft] = useState({ ...profile });

  // Modals state
  const [isPwModalOpen, setIsPwModalOpen] = useState(false);
  const [is2faModalOpen, setIs2faModalOpen] = useState(false);
  const [twoFactorCode, setTwoFactorCode] = useState('');
  const [twoFactorError, setTwoFactorError] = useState('');

  // Password state
  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [pwError, setPwError] = useState('');

  // Toast
  const [toastMessage, setToastMessage] = useState<string | null>(null);

  const showToast = (msg: string) => {
    setToastMessage(msg);
    setTimeout(() => {
      setToastMessage((cur) => (cur === msg ? null : cur));
    }, 3200);
  };

  const displayName = `${profile.firstName} ${profile.lastName}`;
  const initials = (profile.firstName[0] || 'E') + (profile.lastName[0] || 'A');

  // Recent Sessions matching PHP tbluser_sessions
  const [sessions, setSessions] = useState<UserSessionItem[]>([
    {
      id: 'sess-1',
      device: 'Windows PC',
      browser: 'Microsoft Edge',
      ip: '112.198.84.12',
      location: 'Masbate, Philippines',
      time: 'Just now',
      isCurrent: true,
      type: 'desktop',
    },
    {
      id: 'sess-2',
      device: 'Windows PC',
      browser: 'Google Chrome',
      ip: '112.198.84.12',
      location: 'Masbate, Philippines',
      time: 'Oct 19, 2026 — 04:30 PM',
      isCurrent: false,
      type: 'desktop',
    },
    {
      id: 'sess-3',
      device: 'iPhone Mobile',
      browser: 'Safari',
      ip: '120.29.77.41',
      location: 'Masbate, Philippines',
      time: 'Oct 18, 2026 — 09:15 AM',
      isCurrent: false,
      type: 'mobile',
    },
  ]);

  // Activity events matching PHP tbluser_sessions & audit logs
  const [activities] = useState<ActivityEvent[]>([
    {
      id: 'act-1',
      text: 'Logged in from <strong>Microsoft Edge</strong> using Windows PC',
      time: 'Just now',
      dotColor: 'var(--green)',
    },
    {
      id: 'act-2',
      text: 'Verified Faculty PRC credentials for <strong>Ms. Juna Velarde</strong>',
      time: 'Oct 20, 2026 — 10:14 AM',
      dotColor: 'var(--accent)',
    },
    {
      id: 'act-3',
      text: 'Updated Senior High School curriculum section schedules (STEM 11-A)',
      time: 'Oct 19, 2026 — 02:45 PM',
      dotColor: 'var(--purple)',
    },
    {
      id: 'act-4',
      text: 'Approved 24 DepEd Form 137 Learner Reference Number imports',
      time: 'Oct 18, 2026 — 11:20 AM',
      dotColor: 'var(--accent)',
    },
    {
      id: 'act-5',
      text: 'Institutional Security Protocol: Two-Factor Authentication validated',
      time: 'Oct 17, 2026 — 08:30 AM',
      dotColor: 'var(--green)',
    },
  ]);

  // Handlers
  const handleSaveProfileForm = (e: React.FormEvent) => {
    e.preventDefault();
    setProfile({
      ...formDraft,
      updatedAt: 'Just now',
    });
    showToast('Admin profile updated successfully!');
  };

  const handleAvatarFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      if (file.size > 2 * 1024 * 1024) {
        showToast('File size must be under 2MB.');
        return;
      }
      const reader = new FileReader();
      reader.onload = (ev) => {
        setAvatarPreview(ev.target?.result as string);
        showToast('Admin avatar updated!');
      };
      reader.readAsDataURL(file);
    }
  };

  const handleRevokeOtherSessions = () => {
    setSessions((prev) => prev.filter((s) => s.isCurrent));
    showToast('Other administrator sessions revoked.');
  };

  const handleToggle2FA = () => {
    if (profile.twoFactorEnabled) {
      setProfile((prev) => ({ ...prev, twoFactorEnabled: false }));
      showToast('Two-Factor Authentication disabled.');
    } else {
      setIs2faModalOpen(true);
    }
  };

  const handleConfirm2FA = (e?: React.FormEvent) => {
    if (e) e.preventDefault();
    if (twoFactorCode.length < 6) {
      setTwoFactorError('Please enter the complete 6-digit code from your authenticator app.');
      return;
    }
    setProfile((prev) => ({ ...prev, twoFactorEnabled: true }));
    setIs2faModalOpen(false);
    setTwoFactorCode('');
    setTwoFactorError('');
    showToast('Two-Factor Authentication enabled successfully!');
  };

  const handlePasswordSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setPwError('');
    if (!currentPassword) {
      setPwError('Please enter your current administrator password.');
      return;
    }
    if (newPassword.length < 8) {
      setPwError('New password must be at least 8 characters.');
      return;
    }
    if (!/[A-Z]/.test(newPassword)) {
      setPwError('New password must include at least one uppercase letter.');
      return;
    }
    if (!/[0-9]/.test(newPassword)) {
      setPwError('New password must include at least one number.');
      return;
    }
    if (newPassword !== confirmPassword) {
      setPwError('New passwords do not match.');
      return;
    }
    setIsPwModalOpen(false);
    setCurrentPassword('');
    setNewPassword('');
    setConfirmPassword('');
    setProfile((prev) => ({ ...prev, updatedAt: 'Just now' }));
    showToast('Administrator password updated successfully.');
  };

  // Password Checklist
  const hasLength = newPassword.length >= 8;
  const hasUpper = /[A-Z]/.test(newPassword);
  const hasNum = /[0-9]/.test(newPassword);
  const pwStrengthCount = (hasLength ? 1 : 0) + (hasUpper ? 1 : 0) + (hasNum ? 1 : 0);

  return (
    <div className="space-y-6 w-full max-w-6xl mx-auto pb-12">
      {/* Toast Notification Banner */}
      {toastMessage && (
        <div className="fixed top-18 right-6 z-50 flex items-center gap-2.5 px-4 py-3 rounded-xl bg-bg2 border border-green/40 text-green shadow-xl backdrop-blur-md animate-in fade-in slide-in-from-top-3 duration-200">
          <RiCheckLine className="size-5 shrink-0 text-green" aria-hidden="true" />
          <span className="text-xs font-semibold text-text">{toastMessage}</span>
          <button
            type="button"
            onClick={() => setToastMessage(null)}
            className="ml-2 text-text3 hover:text-text cursor-pointer"
          >
            <RiCloseLine className="size-4" />
          </button>
        </div>
      )}

      {/* ============================================================ */}
      {/* 1. ADMIN PROFILE HERO (matching admin/admin_profile.php)      */}
      {/* ============================================================ */}
      <div className="relative rounded-2xl sm:rounded-3xl border border-border bg-bg2 overflow-hidden shadow-xs">
        {/* Banner with Brand Palette Gradient */}
        <div
          className="h-36 sm:h-44 relative overflow-hidden select-none"
          style={{
            background: 'linear-gradient(135deg, #1e3c72 0%, #2a5298 52%, #f7971e 100%)',
          }}
        >
          <div
            className="absolute inset-0 pointer-events-none opacity-20"
            style={{
              backgroundImage: 'radial-gradient(rgba(255, 255, 255, 0.4) 1px, transparent 1px)',
              backgroundSize: '18px 18px',
            }}
          />
        </div>

        {/* Hero Content */}
        <div className="relative px-5 sm:px-8 pb-6 pt-0">
          <div className="flex flex-col sm:flex-row items-center sm:items-start justify-between gap-4 sm:gap-6 text-center sm:text-left">
            {/* Left Column: Avatar + Details */}
            <div className="flex flex-col sm:flex-row items-center sm:items-start gap-4 sm:gap-5 min-w-0 flex-1">
              {/* Avatar with Camera upload trigger (brought down to ~quarter overlap) */}
              <div className="relative group shrink-0 -mt-6 sm:-mt-7 z-10">
                <input
                  type="file"
                  id="adminPicInput"
                  accept="image/*"
                  className="hidden"
                  onChange={handleAvatarFileChange}
                />
                <label
                  htmlFor="adminPicInput"
                  title="Click to change portrait"
                  className="relative flex items-center justify-center size-24 sm:size-28 rounded-full border-4 border-bg2 bg-bg2 shadow-lg overflow-hidden cursor-pointer ring-2 ring-border/50 group-hover:ring-accent transition-all duration-200"
                >
                  {avatarPreview ? (
                    <img
                      src={avatarPreview}
                      alt={displayName}
                      className="w-full h-full object-cover"
                    />
                  ) : (
                    <div
                      className="w-full h-full flex items-center justify-center font-serif text-2xl sm:text-3xl font-bold text-white"
                      style={{
                        background: 'linear-gradient(135deg, #1e3c72 0%, #f7971e 100%)',
                      }}
                    >
                      {initials}
                    </div>
                  )}
                  <div className="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex flex-col items-center justify-center text-white text-[10px] font-semibold transition-opacity duration-200">
                    <RiCameraLine className="size-5 mb-0.5" />
                    <span>Upload</span>
                  </div>
                </label>
                <label
                  htmlFor="adminPicInput"
                  className="absolute bottom-1 right-1 p-1.5 rounded-full bg-accent text-white shadow-md border-2 border-bg2 cursor-pointer hover:scale-110 active:scale-95 transition-transform"
                  title="Upload photo"
                >
                  <RiCameraLine className="size-3.5" />
                </label>
              </div>

              {/* Info Details: sits cleanly in the card body below the banner */}
              <div className="min-w-0 space-y-1 sm:space-y-1.5 pt-1 sm:pt-3 flex-1">
                <div className="flex flex-col sm:flex-row sm:items-center gap-2">
                  <h1 className="font-serif text-2xl sm:text-3xl font-bold text-text tracking-tight truncate">
                    {displayName}
                  </h1>
                  <div className="flex items-center justify-center sm:justify-start gap-1.5 shrink-0">
                    <span className="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-purple-bg text-purple border border-purple/30">
                      Super Admin
                    </span>
                    <span className="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-green-bg text-green border border-green/30">
                      Active
                    </span>
                  </div>
                </div>

                <div className="flex flex-wrap items-center justify-center sm:justify-start gap-3 text-xs text-text2 pt-0.5">
                  <span className="inline-flex items-center gap-1">
                    <RiMailLine className="size-3.5 text-text3" />
                    <span>{profile.email}</span>
                  </span>
                  <span className="text-text3">·</span>
                  <span className="inline-flex items-center gap-1">
                    <RiPhoneLine className="size-3.5 text-text3" />
                    <span>{profile.phone}</span>
                  </span>
                  <span className="text-text3">·</span>
                  <span className="inline-flex items-center gap-1">
                    <RiMapPinLine className="size-3.5 text-text3" />
                    <span>{profile.address}</span>
                  </span>
                </div>
              </div>
            </div>

            {/* Actions */}
            <div className="flex items-center gap-2.5 pt-1 sm:pt-3 shrink-0 w-full sm:w-auto justify-center">
              <button
                type="button"
                onClick={() => {
                  setFormDraft({ ...profile });
                  setActiveSubTab('edit');
                }}
                className="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl bg-accent text-white text-xs font-semibold hover:bg-accent2 shadow-xs cursor-pointer active:scale-95 transition-all"
              >
                <RiEditLine className="size-3.5" />
                <span>Edit Profile</span>
              </button>
              <button
                type="button"
                onClick={() => setIsPwModalOpen(true)}
                className="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl bg-bg3 hover:bg-bg4 text-text text-xs font-semibold border border-border cursor-pointer active:scale-95 transition-all"
              >
                <RiLockPasswordLine className="size-3.5 text-text2" />
                <span>Change Password</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      {/* ============================================================ */}
      {/* 2. INSTITUTIONAL KPI STATS (matching PHP .profile-stats)     */}
      {/* ============================================================ */}
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        {/* Card 1: Teachers */}
        <div
          onClick={() => onTabChange?.('teachers')}
          role="button"
          tabIndex={0}
          onKeyDown={(e) => e.key === 'Enter' && onTabChange?.('teachers')}
          className="p-4 sm:p-5 rounded-2xl bg-bg2 border border-border text-center shadow-xs hover:border-accent/60 hover:shadow-md cursor-pointer transition-all active:scale-[0.98] group"
        >
          <div className="flex items-center justify-between mb-2">
            <span className="text-xs font-semibold text-text3 uppercase tracking-wider">
              Faculty Members
            </span>
            <span className="p-2 rounded-xl bg-accent-glow text-accent group-hover:scale-110 transition-transform">
              <RiGroupLine className="size-4" />
            </span>
          </div>
          <div className="text-2xl sm:text-3xl font-serif font-bold text-accent">24</div>
          <div className="mt-3">
            <div className="h-1.5 w-full rounded-full bg-bg3 overflow-hidden">
              <div className="h-full rounded-full bg-accent transition-all duration-700" style={{ width: '85%' }} />
            </div>
            <div className="flex justify-between text-[11px] text-text3 mt-1.5">
              <span>Licensed PRC Faculty</span>
              <span className="text-accent font-medium">Manage →</span>
            </div>
          </div>
        </div>

        {/* Card 2: Students */}
        <div
          onClick={() => onTabChange?.('students')}
          role="button"
          tabIndex={0}
          onKeyDown={(e) => e.key === 'Enter' && onTabChange?.('students')}
          className="p-4 sm:p-5 rounded-2xl bg-bg2 border border-border text-center shadow-xs hover:border-green/60 hover:shadow-md cursor-pointer transition-all active:scale-[0.98] group"
        >
          <div className="flex items-center justify-between mb-2">
            <span className="text-xs font-semibold text-text3 uppercase tracking-wider">
              Total Students
            </span>
            <span className="p-2 rounded-xl bg-green-bg text-green group-hover:scale-110 transition-transform">
              <RiUserLine className="size-4" />
            </span>
          </div>
          <div className="text-2xl sm:text-3xl font-serif font-bold text-green">486</div>
          <div className="mt-3">
            <div className="h-1.5 w-full rounded-full bg-bg3 overflow-hidden">
              <div className="h-full rounded-full bg-linear-to-r from-green to-emerald-400 transition-all duration-700" style={{ width: '94%' }} />
            </div>
            <div className="flex justify-between text-[11px] text-text3 mt-1.5">
              <span>DepEd LRN Enrolled</span>
              <span className="text-green font-medium">Directory →</span>
            </div>
          </div>
        </div>

        {/* Card 3: Grade & Sections */}
        <div
          onClick={() => onTabChange?.('sections')}
          role="button"
          tabIndex={0}
          onKeyDown={(e) => e.key === 'Enter' && onTabChange?.('sections')}
          className="p-4 sm:p-5 rounded-2xl bg-bg2 border border-border text-center shadow-xs hover:border-amber/60 hover:shadow-md cursor-pointer transition-all active:scale-[0.98] group"
        >
          <div className="flex items-center justify-between mb-2">
            <span className="text-xs font-semibold text-text3 uppercase tracking-wider">
              Grade & Sections
            </span>
            <span className="p-2 rounded-xl bg-amber-bg text-amber group-hover:scale-110 transition-transform">
              <RiListCheck2 className="size-4" />
            </span>
          </div>
          <div className="text-2xl sm:text-3xl font-serif font-bold text-amber">12</div>
          <div className="mt-3">
            <div className="h-1.5 w-full rounded-full bg-bg3 overflow-hidden">
              <div className="h-full rounded-full bg-amber transition-all duration-700" style={{ width: '75%' }} />
            </div>
            <div className="flex justify-between text-[11px] text-text3 mt-1.5">
              <span>Grades 11 & 12 Cohorts</span>
              <span className="text-amber font-medium">Sections →</span>
            </div>
          </div>
        </div>

        {/* Card 4: Strands */}
        <div
          onClick={() => onTabChange?.('strands')}
          role="button"
          tabIndex={0}
          onKeyDown={(e) => e.key === 'Enter' && onTabChange?.('strands')}
          className="p-4 sm:p-5 rounded-2xl bg-bg2 border border-border text-center shadow-xs hover:border-purple/60 hover:shadow-md cursor-pointer transition-all active:scale-[0.98] group"
        >
          <div className="flex items-center justify-between mb-2">
            <span className="text-xs font-semibold text-text3 uppercase tracking-wider">
              Curricular Strands
            </span>
            <span className="p-2 rounded-xl bg-purple-bg text-purple group-hover:scale-110 transition-transform">
              <RiBarChartLine className="size-4" />
            </span>
          </div>
          <div className="text-2xl sm:text-3xl font-serif font-bold text-purple">5</div>
          <div className="mt-3">
            <div className="h-1.5 w-full rounded-full bg-bg3 overflow-hidden">
              <div className="h-full rounded-full bg-linear-to-r from-purple to-indigo-500 transition-all duration-700" style={{ width: '100%' }} />
            </div>
            <div className="flex justify-between text-[11px] text-text3 mt-1.5">
              <span>Academic & TVL Tracks</span>
              <span className="text-purple font-medium">Strands →</span>
            </div>
          </div>
        </div>
      </div>

      {/* ============================================================ */}
      {/* 3. PROFILE NAV TABS (matching PHP .profile-nav)              */}
      {/* ============================================================ */}
      <div className="flex items-center gap-1.5 p-1 rounded-xl bg-bg2 border border-border overflow-x-auto select-none">
        <button
          type="button"
          onClick={() => setActiveSubTab('overview')}
          className={`flex items-center gap-1.5 px-4 py-2 rounded-lg text-xs font-semibold cursor-pointer transition-all whitespace-nowrap ${
            activeSubTab === 'overview'
              ? 'bg-accent text-white shadow-xs'
              : 'text-text2 hover:text-text hover:bg-bg3'
          }`}
        >
          <RiUserLine className="size-3.5" />
          <span>Overview</span>
        </button>

        <button
          type="button"
          onClick={() => {
            setFormDraft({ ...profile });
            setActiveSubTab('edit');
          }}
          className={`flex items-center gap-1.5 px-4 py-2 rounded-lg text-xs font-semibold cursor-pointer transition-all whitespace-nowrap ${
            activeSubTab === 'edit'
              ? 'bg-accent text-white shadow-xs'
              : 'text-text2 hover:text-text hover:bg-bg3'
          }`}
        >
          <RiEditLine className="size-3.5" />
          <span>Edit Profile</span>
        </button>

        <button
          type="button"
          onClick={() => setActiveSubTab('security')}
          className={`flex items-center gap-1.5 px-4 py-2 rounded-lg text-xs font-semibold cursor-pointer transition-all whitespace-nowrap ${
            activeSubTab === 'security'
              ? 'bg-accent text-white shadow-xs'
              : 'text-text2 hover:text-text hover:bg-bg3'
          }`}
        >
          <RiShieldCheckLine className="size-3.5" />
          <span>Security</span>
        </button>

        <button
          type="button"
          onClick={() => setActiveSubTab('activity')}
          className={`flex items-center gap-1.5 px-4 py-2 rounded-lg text-xs font-semibold cursor-pointer transition-all whitespace-nowrap ${
            activeSubTab === 'activity'
              ? 'bg-accent text-white shadow-xs'
              : 'text-text2 hover:text-text hover:bg-bg3'
          }`}
        >
          <RiHistoryLine className="size-3.5" />
          <span>Activity</span>
        </button>
      </div>

      {/* ============================================================ */}
      {/* 3. OVERVIEW SUB-TAB (matching PHP #tab-overview)              */}
      {/* ============================================================ */}
      {activeSubTab === 'overview' && (
        <div className="space-y-6 animate-in fade-in duration-200">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            {/* Card 1: Personal Information */}
            <div className="p-6 rounded-2xl bg-bg2 border border-border shadow-xs space-y-4">
              <div className="flex items-center justify-between border-b border-border pb-3.5">
                <div className="flex items-center gap-2">
                  <span className="p-1.5 rounded-lg bg-accent-glow text-accent">
                    <RiUserLine className="size-4" />
                  </span>
                  <h3 className="text-base font-bold font-serif text-text">Personal Information</h3>
                </div>
                <button
                  type="button"
                  onClick={() => {
                    setFormDraft({ ...profile });
                    setActiveSubTab('edit');
                  }}
                  className="px-3 py-1 rounded-lg text-xs font-semibold text-text2 hover:text-text bg-bg3 hover:bg-bg4 border border-border cursor-pointer transition-colors inline-flex items-center gap-1.5"
                >
                  <RiEditLine className="size-3.5" />
                  <span>Edit</span>
                </button>
              </div>

              <div className="space-y-3 text-xs">
                <div className="flex items-center justify-between">
                  <span className="text-text3 font-medium">Full Name</span>
                  <span className="font-semibold text-text">{profile.firstName} {profile.middleName} {profile.lastName}</span>
                </div>
                <div className="flex items-center justify-between">
                  <span className="text-text3 font-medium">Email Address</span>
                  <span className="font-semibold text-text">{profile.email}</span>
                </div>
                <div className="flex items-center justify-between">
                  <span className="text-text3 font-medium">Gender</span>
                  <span className="font-semibold text-text">{profile.gender}</span>
                </div>
                <div className="flex items-center justify-between">
                  <span className="text-text3 font-medium">Age</span>
                  <span className="font-semibold text-text">{profile.age}</span>
                </div>
                <div className="flex items-center justify-between pt-1">
                  <span className="text-text3 font-medium">Official Campus Address</span>
                  <span className="font-medium text-text text-right max-w-[65%]">{profile.address}</span>
                </div>
              </div>
            </div>

            {/* Card 2: Account Information */}
            <div className="p-6 rounded-2xl bg-bg2 border border-border shadow-xs space-y-4">
              <div className="flex items-center gap-2 border-b border-border pb-3.5">
                <span className="p-1.5 rounded-lg bg-accent-glow text-accent">
                  <RiShieldCheckLine className="size-4" />
                </span>
                <h3 className="text-base font-bold font-serif text-text">Account Information</h3>
              </div>

              <div className="space-y-3 text-xs">
                <div className="flex items-center justify-between">
                  <span className="text-text3 font-medium">Institutional Role</span>
                  <span className="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-bg text-purple">
                    {profile.role}
                  </span>
                </div>
                <div className="flex items-center justify-between">
                  <span className="text-text3 font-medium">Account Status</span>
                  <span className="px-2 py-0.5 rounded-full text-[10px] font-bold bg-green-bg text-green">
                    {profile.status}
                  </span>
                </div>
                <div className="flex items-center justify-between">
                  <span className="text-text3 font-medium">System Verified</span>
                  <span className="px-2 py-0.5 rounded-full text-[10px] font-bold bg-green-bg text-green">
                    Yes · DepEd Authenticated
                  </span>
                </div>
                <div className="flex items-center justify-between">
                  <span className="text-text3 font-medium">Account Created</span>
                  <span className="font-semibold text-text">{profile.createdAt}</span>
                </div>
                <div className="flex items-center justify-between">
                  <span className="text-text3 font-medium">Last Profile Update</span>
                  <span className="font-semibold text-text">{profile.updatedAt}</span>
                </div>
              </div>
            </div>
          </div>

          {/* Recent Sessions Card (matching PHP tbluser_sessions) */}
          <div className="p-6 rounded-2xl bg-bg2 border border-border shadow-xs space-y-4">
            <div className="border-b border-border pb-3.5 flex items-center justify-between">
              <div className="flex items-center gap-2">
                <span className="p-1.5 rounded-lg bg-accent-glow text-accent">
                  <RiComputerLine className="size-4" />
                </span>
                <div>
                  <h3 className="text-base font-bold font-serif text-text">Recent Sessions</h3>
                  <p className="text-[11px] text-text3">Active web login devices recorded in tbluser_sessions</p>
                </div>
              </div>
              <div className="flex items-center gap-2">
                <button
                  type="button"
                  onClick={handleRevokeOtherSessions}
                  className="px-3 py-1 rounded-lg text-xs font-semibold text-text2 hover:text-text bg-bg3 hover:bg-bg4 border border-border cursor-pointer transition-colors"
                >
                  Revoke Others
                </button>
                <button
                  type="button"
                  onClick={requestLogout}
                  className="px-3 py-1 rounded-lg text-xs font-semibold text-red hover:bg-red-bg border border-red/30 cursor-pointer transition-colors flex items-center gap-1 active:scale-95"
                >
                  <RiLogoutBoxRLine className="size-3.5" aria-hidden="true" />
                  <span>Log Out</span>
                </button>
              </div>
            </div>

            <div className="space-y-3">
              {sessions.map((sess) => (
                <div
                  key={sess.id}
                  className="p-3.5 rounded-xl bg-bg3 border border-border flex items-center gap-3.5 text-xs hover:border-accent/40 transition-colors"
                >
                  <div className="size-10 rounded-xl bg-accent-glow text-accent flex items-center justify-center shrink-0">
                    {sess.type === 'desktop' ? (
                      <RiComputerLine className="size-5" />
                    ) : (
                      <RiSmartphoneLine className="size-5" />
                    )}
                  </div>
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2">
                      <span className="font-semibold text-text">{sess.browser} on {sess.device}</span>
                      {sess.isCurrent && (
                        <span className="px-2 py-0.5 rounded-full text-[9px] font-bold bg-green-bg text-green border border-green/30">
                          This Device
                        </span>
                      )}
                    </div>
                    <div className="text-[11px] text-text3 mt-0.5">
                      {sess.ip} · {sess.location}
                    </div>
                  </div>
                  <div className="text-[11px] text-text3 shrink-0">{sess.time}</div>
                </div>
              ))}
            </div>
          </div>
        </div>
      )}

      {/* ============================================================ */}
      {/* 4. EDIT PROFILE SUB-TAB (matching PHP #tab-edit)             */}
      {/* ============================================================ */}
      {activeSubTab === 'edit' && (
        <div className="p-6 rounded-2xl bg-bg2 border border-border shadow-xs animate-in fade-in duration-200">
          <div className="border-b border-border pb-3.5 mb-5 flex items-center gap-2.5">
            <span className="p-1.5 rounded-lg bg-accent-glow text-accent">
              <RiEditLine className="size-4" />
            </span>
            <div>
              <h3 className="text-base font-bold font-serif text-text">Edit Administrator Information</h3>
              <p className="text-xs text-text3">Update root system profile fields saved in tblusers.</p>
            </div>
          </div>

          <form onSubmit={handleSaveProfileForm} className="space-y-4 max-w-3xl">
            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <div>
                <label className="block text-xs font-semibold text-text2 mb-1">First Name *</label>
                <input
                  type="text"
                  required
                  value={formDraft.firstName}
                  onChange={(e) => setFormDraft({ ...formDraft, firstName: e.target.value })}
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text focus:border-accent outline-none"
                />
              </div>
              <div>
                <label className="block text-xs font-semibold text-text2 mb-1">Middle Name</label>
                <input
                  type="text"
                  value={formDraft.middleName}
                  onChange={(e) => setFormDraft({ ...formDraft, middleName: e.target.value })}
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text focus:border-accent outline-none"
                />
              </div>
              <div>
                <label className="block text-xs font-semibold text-text2 mb-1">Last Name *</label>
                <input
                  type="text"
                  required
                  value={formDraft.lastName}
                  onChange={(e) => setFormDraft({ ...formDraft, lastName: e.target.value })}
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text focus:border-accent outline-none"
                />
              </div>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label className="block text-xs font-semibold text-text2 mb-1">Email Address *</label>
                <input
                  type="email"
                  required
                  value={formDraft.email}
                  onChange={(e) => setFormDraft({ ...formDraft, email: e.target.value })}
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text focus:border-accent outline-none"
                />
              </div>
              <div>
                <label className="block text-xs font-semibold text-text2 mb-1">Phone Number</label>
                <input
                  type="tel"
                  value={formDraft.phone}
                  onChange={(e) => setFormDraft({ ...formDraft, phone: e.target.value })}
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text focus:border-accent outline-none"
                />
              </div>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label className="block text-xs font-semibold text-text2 mb-1">Gender</label>
                <select
                  value={formDraft.gender}
                  onChange={(e) => setFormDraft({ ...formDraft, gender: e.target.value })}
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text focus:border-accent outline-none"
                >
                  <option value="Male">Male</option>
                  <option value="Female">Female</option>
                  <option value="Prefer not to say">Prefer not to say</option>
                </select>
              </div>
              <div>
                <label className="block text-xs font-semibold text-text2 mb-1">Age</label>
                <input
                  type="number"
                  value={formDraft.age}
                  onChange={(e) => setFormDraft({ ...formDraft, age: Number(e.target.value) })}
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text focus:border-accent outline-none"
                />
              </div>
            </div>

            <div>
              <label className="block text-xs font-semibold text-text2 mb-1">Campus Address</label>
              <textarea
                rows={2}
                value={formDraft.address}
                onChange={(e) => setFormDraft({ ...formDraft, address: e.target.value })}
                className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text focus:border-accent outline-none resize-none"
              />
            </div>

            <div>
              <label className="block text-xs font-semibold text-text2 mb-1">Profile Picture</label>
              <input
                type="file"
                accept="image/*"
                onChange={handleAvatarFileChange}
                className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text3 file:mr-3 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-accent file:text-white hover:file:bg-accent2 cursor-pointer"
              />
              <span className="text-[11px] text-text3 mt-1 block">Allowed: JPG, PNG, WEBP. Max 2MB.</span>
            </div>

            <div className="pt-4 border-t border-border flex items-center justify-end gap-2.5">
              <button
                type="button"
                onClick={() => setActiveSubTab('overview')}
                className="px-4 py-2 rounded-xl text-xs font-semibold text-text2 hover:text-text bg-bg3 hover:bg-bg4 border border-border cursor-pointer transition-colors"
              >
                Cancel
              </button>
              <button
                type="submit"
                className="px-5 py-2 rounded-xl text-xs font-semibold text-white bg-accent hover:bg-accent2 shadow-xs cursor-pointer active:scale-95 transition-all"
              >
                Save Changes
              </button>
            </div>
          </form>
        </div>
      )}

      {/* ============================================================ */}
      {/* 5. SECURITY SUB-TAB (matching PHP #tab-security)             */}
      {/* ============================================================ */}
      {activeSubTab === 'security' && (
        <div className="p-6 rounded-2xl bg-bg2 border border-border shadow-xs space-y-6 animate-in fade-in duration-200">
          <div className="border-b border-border pb-3.5 flex items-center gap-2.5">
            <span className="p-1.5 rounded-lg bg-accent-glow text-accent">
              <RiShieldCheckLine className="size-4" />
            </span>
            <div>
              <h3 className="text-base font-bold font-serif text-text">Password & Authentication Security</h3>
              <p className="text-xs text-text3">Ensure institutional control accounts are protected with modern cryptographic controls.</p>
            </div>
          </div>

          {/* Password Card Row */}
          <div className="p-4 rounded-xl bg-bg3 border border-border flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
            <div>
              <div className="font-semibold text-text text-sm">Administrator Password</div>
              <div className="text-text3 mt-0.5">
                Last updated: {profile.updatedAt}. We enforce 90-day password rotation for super administrators.
              </div>
            </div>
            <button
              type="button"
              onClick={() => setIsPwModalOpen(true)}
              className="self-start sm:self-auto px-4 py-1.5 rounded-lg bg-bg2 hover:bg-bg4 border border-border text-text font-semibold flex items-center gap-1.5 cursor-pointer transition-colors"
            >
              <RiKeyLine className="size-3.5 text-accent" />
              <span>Change Password</span>
            </button>
          </div>

          {/* Two-Factor Authentication Card Row */}
          <div className="p-4 rounded-xl bg-bg3 border border-border flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
            <div className="flex items-start sm:items-center gap-3">
              <span className="p-2.5 rounded-xl bg-accent-glow text-accent shrink-0 mt-0.5 sm:mt-0">
                <RiLockLine className="size-5" />
              </span>
              <div>
                <div className="flex items-center gap-2">
                  <span className="font-semibold text-text text-sm">Two-Factor Authentication (2FA)</span>
                  <span
                    className={`px-2 py-0.5 rounded-full text-[10px] font-bold ${
                      profile.twoFactorEnabled
                        ? 'bg-green-bg text-green'
                        : 'bg-red-bg text-red'
                    }`}
                  >
                    {profile.twoFactorEnabled ? 'Enabled' : 'Disabled'}
                  </span>
                </div>
                <div className="text-text3 mt-0.5 leading-relaxed">
                  {profile.twoFactorEnabled
                    ? 'Your administrator session is protected with TOTP hardware or authenticator app 2FA.'
                    : 'Enable 2FA to protect institutional student and gradebook records.'}
                </div>
              </div>
            </div>
            <button
              type="button"
              onClick={handleToggle2FA}
              className={`self-start sm:self-auto px-4 py-1.5 rounded-lg font-semibold cursor-pointer transition-colors ${
                profile.twoFactorEnabled
                  ? 'bg-bg2 hover:bg-bg4 border border-border text-text3 hover:text-text'
                  : 'bg-accent hover:bg-accent2 text-white shadow-xs'
              }`}
            >
              {profile.twoFactorEnabled ? 'Disable 2FA' : 'Enable 2FA'}
            </button>
          </div>

          {/* Active Session & Logout Card Row */}
          <div className="p-4 rounded-xl bg-bg3 border border-border flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
            <div>
              <div className="font-semibold text-text text-sm">Active Administrator Session</div>
              <div className="text-text3 mt-0.5">
                Current browser session · Session ID: #ADM-SESSION-SEC9
              </div>
            </div>
            <button
              type="button"
              onClick={requestLogout}
              className="self-start sm:self-auto px-4 py-1.5 rounded-lg bg-red-bg hover:bg-red/20 border border-red/30 text-red font-semibold flex items-center gap-1.5 cursor-pointer transition-colors active:scale-95"
            >
              <RiLogoutBoxRLine className="size-3.5" aria-hidden="true" />
              <span>Log Out</span>
            </button>
          </div>
        </div>
      )}

      {/* ============================================================ */}
      {/* 6. ACTIVITY SUB-TAB (matching PHP #tab-activity)             */}
      {/* ============================================================ */}
      {activeSubTab === 'activity' && (
        <div className="p-6 rounded-2xl bg-bg2 border border-border shadow-xs space-y-5 animate-in fade-in duration-200">
          <div className="border-b border-border pb-3.5 flex items-center gap-2.5">
            <span className="p-1.5 rounded-lg bg-accent-glow text-accent">
              <RiHistoryLine className="size-4" />
            </span>
            <div>
              <h3 className="text-base font-bold font-serif text-text">Recent Administrative Activity</h3>
              <p className="text-xs text-text3">System logs, authentication records, and administrative transactions.</p>
            </div>
          </div>

          <div className="relative pl-6 space-y-6 before:absolute before:left-2.5 before:top-2 before:bottom-2 before:w-0.5 before:bg-border">
            {activities.map((act) => (
              <div key={act.id} className="relative group text-xs">
                {/* Dot */}
                <div
                  className="absolute -left-6 top-1 size-3 rounded-full border-2 border-bg2"
                  style={{ backgroundColor: act.dotColor }}
                />
                <div
                  className="text-text leading-relaxed"
                  dangerouslySetInnerHTML={{ __html: act.text }}
                />
                <div className="text-[11px] text-text3 mt-1 flex items-center gap-1">
                  <RiTimeLine className="size-3" />
                  <span>{act.time}</span>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* ============================================================ */}
      {/* CHANGE PASSWORD MODAL (matching PHP #pw-modal)               */}
      {/* ============================================================ */}
      {isPwModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs animate-in fade-in duration-200">
          <div className="relative w-full max-w-md rounded-2xl bg-bg2 border border-border shadow-2xl overflow-hidden animate-in zoom-in-95 duration-200">
            <div
              className="h-20 relative p-4 flex items-center justify-between"
              style={{
                background: 'linear-gradient(135deg, #1e3c72 0%, #2a5298 100%)',
              }}
            >
              <div className="text-white">
                <h3 className="font-serif text-lg font-bold">Change Password</h3>
                <p className="text-xs text-white/80">Super Administrator credential update</p>
              </div>
              <button
                type="button"
                onClick={() => setIsPwModalOpen(false)}
                className="size-8 rounded-full bg-black/30 hover:bg-black/50 text-white flex items-center justify-center cursor-pointer transition-colors"
              >
                <RiCloseLine className="size-4" />
              </button>
            </div>

            <form onSubmit={handlePasswordSubmit} className="p-5 sm:p-6 space-y-4">
              {pwError && (
                <div className="p-3 rounded-lg bg-red-bg border border-red/30 text-xs text-red flex items-center gap-2">
                  <RiAlertLine className="size-4 shrink-0" />
                  <span>{pwError}</span>
                </div>
              )}

              <div>
                <label className="block text-xs font-semibold text-text2 mb-1">Current Password *</label>
                <input
                  type="password"
                  placeholder="Enter current password"
                  value={currentPassword}
                  onChange={(e) => setCurrentPassword(e.target.value)}
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text focus:border-accent outline-none"
                  required
                />
              </div>

              <div>
                <label className="block text-xs font-semibold text-text2 mb-1">New Password *</label>
                <input
                  type="password"
                  placeholder="Enter new password"
                  value={newPassword}
                  onChange={(e) => setNewPassword(e.target.value)}
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text focus:border-accent outline-none"
                  required
                />

                {/* Strength bar */}
                <div className="mt-2 h-1 w-full bg-bg4 rounded-full overflow-hidden">
                  <div
                    className={`h-full transition-all duration-300 ${
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

                {/* Requirements list matching PHP pw-requirements */}
                <div className="mt-2 space-y-1 text-[11px]">
                  <div className={`flex items-center gap-1.5 ${hasLength ? 'text-green font-medium' : 'text-text3'}`}>
                    {hasLength ? (
                      <RiCheckLine className="size-3.5 text-green shrink-0" />
                    ) : (
                      <RiCloseLine className="size-3.5 text-text3 shrink-0" />
                    )}
                    <span>Minimum 8 characters</span>
                  </div>
                  <div className={`flex items-center gap-1.5 ${hasUpper ? 'text-green font-medium' : 'text-text3'}`}>
                    {hasUpper ? (
                      <RiCheckLine className="size-3.5 text-green shrink-0" />
                    ) : (
                      <RiCloseLine className="size-3.5 text-text3 shrink-0" />
                    )}
                    <span>At least one uppercase letter</span>
                  </div>
                  <div className={`flex items-center gap-1.5 ${hasNum ? 'text-green font-medium' : 'text-text3'}`}>
                    {hasNum ? (
                      <RiCheckLine className="size-3.5 text-green shrink-0" />
                    ) : (
                      <RiCloseLine className="size-3.5 text-text3 shrink-0" />
                    )}
                    <span>At least one number</span>
                  </div>
                </div>
              </div>

              <div>
                <label className="block text-xs font-semibold text-text2 mb-1">Confirm New Password *</label>
                <input
                  type="password"
                  placeholder="Re-enter new password"
                  value={confirmPassword}
                  onChange={(e) => setConfirmPassword(e.target.value)}
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text focus:border-accent outline-none"
                  required
                />
              </div>

              <div className="pt-3 border-t border-border flex items-center justify-end gap-2.5">
                <button
                  type="button"
                  onClick={() => setIsPwModalOpen(false)}
                  className="px-4 py-2 rounded-xl text-xs font-semibold text-text2 hover:text-text bg-bg3 hover:bg-bg4 border border-border cursor-pointer transition-colors"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-5 py-2 rounded-xl text-xs font-semibold text-white bg-accent hover:bg-accent2 shadow-xs cursor-pointer active:scale-95 transition-all"
                >
                  Update Password
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* ============================================================ */}
      {/* 2FA MODAL (matching PHP #twofa-modal)                         */}
      {/* ============================================================ */}
      {is2faModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs animate-in fade-in duration-200">
          <div className="relative w-full max-w-sm rounded-2xl bg-bg2 border border-border p-6 shadow-2xl text-center space-y-4 animate-in zoom-in-95 duration-200">
            <div className="size-12 rounded-full bg-accent-glow text-accent flex items-center justify-center mx-auto">
              <RiQrCodeLine className="size-6 text-accent" />
            </div>
            <div>
              <h4 className="text-base font-bold font-serif text-text">Scan with Authenticator App</h4>
              <p className="text-xs text-text3 mt-1 leading-relaxed">
                Use Google Authenticator, Microsoft Authenticator, or any TOTP application.
              </p>
            </div>

            {/* Mock QR Code Pattern matching PHP */}
            <div className="inline-block p-3.5 bg-white rounded-xl shadow-xs border border-border">
              <div
                className="size-36 rounded-md opacity-85"
                style={{
                  background:
                    'repeating-conic-gradient(#000 0% 25%, #fff 0% 50%) 50%/16px 16px',
                }}
              />
            </div>

            <div className="space-y-1">
              <span className="text-[11px] text-text3 block">Or enter this secret key manually:</span>
              <div className="font-mono text-xs font-bold tracking-widest px-3 py-2 rounded-lg bg-bg3 border border-border text-accent select-all">
                {profile.twoFactorSecret}
              </div>
            </div>

            {twoFactorError && (
              <div className="p-2.5 rounded-lg bg-red-bg border border-red/30 text-xs text-red text-left flex items-center gap-2">
                <RiAlertLine className="size-4 shrink-0" />
                <span>{twoFactorError}</span>
              </div>
            )}

            <form onSubmit={handleConfirm2FA} className="space-y-4">
              <div>
                <label className="block text-xs font-semibold text-text2 mb-1.5 text-left">
                  Verification Code (6-digit)
                </label>
                <input
                  type="text"
                  maxLength={6}
                  placeholder="000 000"
                  value={twoFactorCode}
                  onChange={(e) => {
                    setTwoFactorError('');
                    setTwoFactorCode(e.target.value.replace(/\D/g, ''));
                  }}
                  className="w-full text-center tracking-widest font-mono text-lg py-2 px-3 rounded-lg bg-bg3 border border-border text-text font-bold focus:border-accent outline-none"
                  autoFocus
                />
              </div>

              <div className="flex gap-2.5 pt-1">
                <button
                  type="button"
                  onClick={() => {
                    setIs2faModalOpen(false);
                    setTwoFactorCode('');
                    setTwoFactorError('');
                  }}
                  className="flex-1 py-2 rounded-xl text-xs font-semibold text-text2 bg-bg3 hover:bg-bg4 border border-border cursor-pointer transition-colors"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="flex-1 py-2 rounded-xl text-xs font-semibold text-white bg-accent hover:bg-accent2 shadow-xs cursor-pointer transition-colors"
                >
                  Verify & Enable
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
