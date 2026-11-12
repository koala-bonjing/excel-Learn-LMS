import { useState } from 'react';
import {
  RiUserLine,
  RiGraduationCapLine,
  RiBookOpenLine,
  RiCheckboxCircleLine,
  RiAwardLine,
  RiCameraLine,
  RiEditLine,
  RiLockPasswordLine,
  RiCheckLine,
  RiCloseLine,
  RiAlertLine,
} from '@remixicon/react';

export interface StudentProfileViewProps {
  onTabChange?: (tabId: string) => void;
}

export function StudentProfileView({ onTabChange }: StudentProfileViewProps) {
  // Personal & Academic profile master state (matching tblstudents + tblsections)
  const [personalInfo, setPersonalInfo] = useState({
    firstName: 'Bryan',
    middleName: 'Gomez',
    lastName: 'Donceras',
    suffix: '',
    studentId: 'STUD-123456',
    phone: '0912-345-6789',
    birthdate: '2008-05-14',
    gender: 'Male',
    address: 'Cabitan, Mandaon, Masbate',
    guardianName: 'Roberto Donceras',
    guardianContact: '0917-889-1234',
  });

  const [academicInfo, setAcademicInfo] = useState({
    lrn: '109823456712',
    gradeLevel: 'Grade 11',
    gradeLevelId: '11',
    section: 'STEM 11-A',
    sectionId: '1',
    strand: 'Science, Technology, Engineering & Mathematics',
    adviser: 'Ms. Juna Velarde',
    enrollmentDate: 'August 18, 2025',
  });

  // Avatar & upload state
  const [avatarPreview, setAvatarPreview] = useState<string | null>(null);

  // Modals state
  const [isEditModalOpen, setIsEditModalOpen] = useState(false);
  const [isAcademicModalOpen, setIsAcademicModalOpen] = useState(false);
  const [isPasswordModalOpen, setIsPasswordModalOpen] = useState(false);
  const [isDeactivateModalOpen, setIsDeactivateModalOpen] = useState(false);

  // Modal draft forms
  const [editDraft, setEditDraft] = useState({ ...personalInfo });
  const [academicDraft, setAcademicDraft] = useState({ ...academicInfo });

  // Password draft
  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [passwordError, setPasswordError] = useState('');

  // Toast banner
  const [toastMessage, setToastMessage] = useState<string | null>(null);

  const showToast = (msg: string) => {
    setToastMessage(msg);
    setTimeout(() => {
      setToastMessage((cur) => (cur === msg ? null : cur));
    }, 3200);
  };

  // Full name computed
  const fullName = [
    personalInfo.firstName,
    personalInfo.middleName,
    personalInfo.lastName,
    personalInfo.suffix,
  ]
    .filter(Boolean)
    .join(' ');

  const initials =
    (personalInfo.firstName[0] || 'B') + (personalInfo.lastName[0] || 'D');

  // Dynamic Profile Completion calculation (matching PHP completion_fields logic)
  const completionFields = [
    personalInfo.firstName,
    personalInfo.middleName,
    personalInfo.lastName,
    personalInfo.studentId,
    personalInfo.phone,
    personalInfo.birthdate,
    personalInfo.gender,
    personalInfo.address,
    personalInfo.guardianName,
    personalInfo.guardianContact,
    academicInfo.lrn,
    academicInfo.gradeLevel,
    academicInfo.section,
    academicInfo.adviser,
    academicInfo.enrollmentDate,
  ];
  const filledCount = completionFields.filter((f) => f && f.trim().length > 0).length;
  const completionRate = Math.round((filledCount / completionFields.length) * 100);

  // Handlers
  const handleOpenEdit = () => {
    setEditDraft({ ...personalInfo });
    setIsEditModalOpen(true);
  };

  const handleSaveEdit = (e: React.FormEvent) => {
    e.preventDefault();
    setPersonalInfo({ ...editDraft });
    setIsEditModalOpen(false);
    showToast('Personal information updated successfully!');
  };

  const handleOpenAcademic = () => {
    setAcademicDraft({ ...academicInfo });
    setIsAcademicModalOpen(true);
  };

  const handleSaveAcademic = (e: React.FormEvent) => {
    e.preventDefault();
    setAcademicInfo({ ...academicDraft });
    setIsAcademicModalOpen(false);
    showToast('Academic records updated successfully!');
  };

  const handleAvatarFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      if (file.size > 2 * 1024 * 1024) {
        showToast('Image size exceeds 2MB limit.');
        return;
      }
      const reader = new FileReader();
      reader.onload = (ev) => {
        setAvatarPreview(ev.target?.result as string);
        showToast('Profile photo updated!');
      };
      reader.readAsDataURL(file);
    }
  };

  const handlePasswordSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setPasswordError('');
    if (!currentPassword) {
      setPasswordError('Please enter your current portal password.');
      return;
    }
    if (newPassword.length < 8) {
      setPasswordError('New password must be at least 8 characters.');
      return;
    }
    if (newPassword !== confirmPassword) {
      setPasswordError('New passwords do not match.');
      return;
    }
    setIsPasswordModalOpen(false);
    setCurrentPassword('');
    setNewPassword('');
    setConfirmPassword('');
    showToast('Portal password updated securely!');
  };

  // Password strength score
  const getPasswordStrength = (pwd: string) => {
    let score = 0;
    if (pwd.length >= 8) score++;
    if (/[A-Z]/.test(pwd)) score++;
    if (/[0-9]/.test(pwd)) score++;
    if (/[^A-Za-z0-9]/.test(pwd)) score++;
    return score;
  };
  const pwStrengthScore = getPasswordStrength(newPassword);

  const formatBirthdateDisplay = (dateStr: string) => {
    if (!dateStr) return '—';
    try {
      const parts = dateStr.split('-');
      if (parts.length === 3) {
        const d = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
        return d.toLocaleDateString('en-US', {
          month: 'long',
          day: 'numeric',
          year: 'numeric',
        });
      }
      return dateStr;
    } catch {
      return dateStr;
    }
  };

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
      {/* 1. PROFILE HERO (matching PHP .profile-hero)                 */}
      {/* ============================================================ */}
      <div className="relative rounded-2xl sm:rounded-3xl border border-border bg-bg2 overflow-hidden shadow-xs">
        {/* Banner with school gradient & pattern */}
        <div className="relative h-36 sm:h-44 overflow-hidden select-none">
          <div
            className="absolute inset-0 opacity-95 transition-opacity"
            style={{
              background:
                'linear-gradient(135deg, #1e3c72 0%, #2a5298 42%, #f7971e 100%)',
            }}
          />
          {/* Subtle dot pattern */}
          <div
            className="absolute inset-0 pointer-events-none opacity-20"
            style={{
              backgroundImage: 'radial-gradient(rgba(255, 255, 255, 0.4) 1px, transparent 1px)',
              backgroundSize: '18px 18px',
            }}
          />
          {/* Decorative shapes */}
          <div className="absolute -bottom-10 -right-6 w-44 h-44 rounded-full bg-white/10 pointer-events-none" />
          <div className="absolute -top-12 left-1/4 w-32 h-32 rounded-full bg-white/5 pointer-events-none" />
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
                  id="studentPicInput"
                  accept="image/*"
                  className="hidden"
                  onChange={handleAvatarFileChange}
                />
                <label
                  htmlFor="studentPicInput"
                  title="Click to change photo"
                  className="relative flex items-center justify-center size-24 sm:size-28 rounded-full border-4 border-bg2 bg-bg2 shadow-lg overflow-hidden cursor-pointer ring-2 ring-border/50 group-hover:ring-accent transition-all duration-200"
                >
                  {avatarPreview ? (
                    <img
                      src={avatarPreview}
                      alt={fullName}
                      className="w-full h-full object-cover"
                    />
                  ) : (
                    <div
                      className="w-full h-full flex items-center justify-center font-serif text-2xl sm:text-3xl font-bold text-white"
                      style={{
                        background:
                          'linear-gradient(135deg, var(--cyan), var(--accent))',
                      }}
                    >
                      {initials}
                    </div>
                  )}
                  {/* Overlay hover prompt */}
                  <div className="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex flex-col items-center justify-center text-white text-[10px] font-semibold transition-opacity duration-200">
                    <RiCameraLine className="size-5 mb-0.5" />
                    <span>Upload</span>
                  </div>
                </label>
                {/* Camera badge */}
                <label
                  htmlFor="studentPicInput"
                  className="absolute bottom-1 right-1 p-1.5 rounded-full bg-accent text-white shadow-md border-2 border-bg2 cursor-pointer hover:scale-110 active:scale-95 transition-transform"
                  title="Upload photo"
                >
                  <RiCameraLine className="size-3.5" />
                </label>
              </div>

              {/* Info Details */}
              <div className="min-w-0 space-y-1 sm:space-y-1.5 pt-1 sm:pt-3 flex-1">
                <div className="flex flex-col sm:flex-row sm:items-center gap-2">
                  <h1 className="font-serif text-2xl sm:text-3xl font-bold text-text tracking-tight truncate">
                    {fullName}
                  </h1>
                  <span className="inline-flex items-center justify-center self-center sm:self-auto px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-green-bg text-green border border-green/30 shrink-0">
                    Active Enrolled
                  </span>
                </div>
                <div className="flex flex-wrap items-center justify-center sm:justify-start gap-2 text-xs text-text2">
                  <span className="inline-flex items-center gap-1 font-semibold text-accent">
                    <RiGraduationCapLine className="size-3.5" />
                    {academicInfo.gradeLevel}
                  </span>
                  <span className="text-text3">·</span>
                  <span className="font-medium text-text">Section {academicInfo.section}</span>
                  <span className="text-text3">·</span>
                  <span className="text-text3 font-mono text-[11px]">LRN: {academicInfo.lrn}</span>
                </div>
              </div>
            </div>

            {/* Hero Actions */}
            <div className="flex items-center gap-2.5 pt-1 sm:pt-3 shrink-0 w-full sm:w-auto justify-center">
              <button
                type="button"
                onClick={handleOpenEdit}
                className="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl bg-accent text-white text-xs font-semibold hover:bg-accent2 shadow-xs cursor-pointer active:scale-95 transition-all"
              >
                <RiEditLine className="size-3.5" />
                <span>Edit Profile</span>
              </button>
              <button
                type="button"
                onClick={() => setIsPasswordModalOpen(true)}
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
      {/* 2. STATS ROW (matching PHP .profile-stats-row)                 */}
      {/* ============================================================ */}
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        {/* Card 1: Enrolled Subjects */}
        <div
          onClick={() => onTabChange?.('courses')}
          role="button"
          tabIndex={0}
          onKeyDown={(e) => e.key === 'Enter' && onTabChange?.('courses')}
          className="p-4 sm:p-5 rounded-2xl bg-bg2 border border-border shadow-xs hover:border-accent/60 hover:shadow-md cursor-pointer transition-all group active:scale-[0.98]"
        >
          <div className="flex items-center justify-between mb-2">
            <span className="text-xs font-semibold text-text3 uppercase tracking-wider">
              Enrolled Subjects
            </span>
            <span className="p-2 rounded-xl bg-accent-glow text-accent group-hover:scale-110 transition-transform">
              <RiBookOpenLine className="size-4" />
            </span>
          </div>
          <div className="text-2xl sm:text-3xl font-serif font-bold text-accent">6</div>
          <div className="mt-3">
            <div className="h-1.5 w-full rounded-full bg-bg3 overflow-hidden">
              <div
                className="h-full rounded-full bg-accent transition-all duration-700"
                style={{ width: '100%' }}
              />
            </div>
            <div className="flex justify-between text-[11px] text-text3 mt-1.5">
              <span>DepEd SHS Curriculum</span>
              <span className="text-accent font-medium">View subjects →</span>
            </div>
          </div>
        </div>

        {/* Card 2: Average Grade */}
        <div
          onClick={() => onTabChange?.('grades')}
          role="button"
          tabIndex={0}
          onKeyDown={(e) => e.key === 'Enter' && onTabChange?.('grades')}
          className="p-4 sm:p-5 rounded-2xl bg-bg2 border border-border shadow-xs hover:border-green/60 hover:shadow-md cursor-pointer transition-all group active:scale-[0.98]"
        >
          <div className="flex items-center justify-between mb-2">
            <span className="text-xs font-semibold text-text3 uppercase tracking-wider">
              Average Grade
            </span>
            <span className="p-2 rounded-xl bg-green-bg text-green group-hover:scale-110 transition-transform">
              <RiCheckboxCircleLine className="size-4" />
            </span>
          </div>
          <div className="text-2xl sm:text-3xl font-serif font-bold text-green">92%</div>
          <div className="mt-3">
            <div className="h-1.5 w-full rounded-full bg-bg3 overflow-hidden">
              <div
                className="h-full rounded-full bg-linear-to-r from-green to-emerald-400 transition-all duration-700"
                style={{ width: '92%' }}
              />
            </div>
            <div className="flex justify-between text-[11px] text-text3 mt-1.5">
              <span>With Honors (GWA)</span>
              <span className="text-green font-medium">View grades →</span>
            </div>
          </div>
        </div>

        {/* Card 3: Total Quizzes Taken */}
        <div
          onClick={() => onTabChange?.('tasks')}
          role="button"
          tabIndex={0}
          onKeyDown={(e) => e.key === 'Enter' && onTabChange?.('tasks')}
          className="p-4 sm:p-5 rounded-2xl bg-bg2 border border-border shadow-xs hover:border-amber/60 hover:shadow-md cursor-pointer transition-all group active:scale-[0.98]"
        >
          <div className="flex items-center justify-between mb-2">
            <span className="text-xs font-semibold text-text3 uppercase tracking-wider">
              Quizzes & Tasks
            </span>
            <span className="p-2 rounded-xl bg-amber-bg text-amber group-hover:scale-110 transition-transform">
              <RiAwardLine className="size-4" />
            </span>
          </div>
          <div className="text-2xl sm:text-3xl font-serif font-bold text-amber">14</div>
          <div className="mt-3">
            <div className="h-1.5 w-full rounded-full bg-bg3 overflow-hidden">
              <div
                className="h-full rounded-full bg-amber transition-all duration-700"
                style={{ width: '85%' }}
              />
            </div>
            <div className="flex justify-between text-[11px] text-text3 mt-1.5">
              <span>Completed & Graded</span>
              <span className="text-amber font-medium">View tasks →</span>
            </div>
          </div>
        </div>

        {/* Card 4: Profile Completion */}
        <div className="p-4 sm:p-5 rounded-2xl bg-bg2 border border-border shadow-xs hover:border-purple/60 transition-all group">
          <div className="flex items-center justify-between mb-2">
            <span className="text-xs font-semibold text-text3 uppercase tracking-wider">
              Profile Completion
            </span>
            <span className="p-2 rounded-xl bg-purple-bg text-purple group-hover:scale-110 transition-transform">
              <RiUserLine className="size-4" />
            </span>
          </div>
          <div className="text-2xl sm:text-3xl font-serif font-bold text-purple">{completionRate}%</div>
          <div className="mt-3">
            <div className="h-1.5 w-full rounded-full bg-bg3 overflow-hidden">
              <div
                className="h-full rounded-full bg-linear-to-r from-purple to-indigo-500 transition-all duration-700"
                style={{ width: `${completionRate}%` }}
              />
            </div>
            <div className="flex justify-between text-[11px] text-text3 mt-1.5">
              <span>LIS Master Record</span>
              <span>{filledCount} of {completionFields.length} fields</span>
            </div>
          </div>
        </div>
      </div>

      {/* ============================================================ */}
      {/* 3. TWO COLUMN GRID (Personal & Academic Information)          */}
      {/* ============================================================ */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Card 1: Personal Information */}
        <div className="p-6 rounded-2xl bg-bg2 border border-border shadow-xs space-y-5">
          <div className="flex items-center justify-between border-b border-border pb-3.5">
            <div className="flex items-center gap-2">
              <span className="p-1.5 rounded-lg bg-accent-glow text-accent">
                <RiUserLine className="size-4" />
              </span>
              <h2 className="text-base font-bold font-serif text-text">Personal Information</h2>
            </div>
            <button
              type="button"
              onClick={handleOpenEdit}
              className="px-3 py-1 rounded-lg text-xs font-semibold text-text2 hover:text-text bg-bg3 hover:bg-bg4 border border-border cursor-pointer transition-colors inline-flex items-center gap-1.5"
            >
              <RiEditLine className="size-3.5" />
              <span>Edit</span>
            </button>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
            <div>
              <div className="text-[11px] font-semibold text-text3 uppercase tracking-wider">First Name</div>
              <div className="mt-1 font-semibold text-text text-sm">{personalInfo.firstName || '—'}</div>
            </div>
            <div>
              <div className="text-[11px] font-semibold text-text3 uppercase tracking-wider">Middle Name</div>
              <div className="mt-1 font-semibold text-text text-sm">{personalInfo.middleName || '—'}</div>
            </div>
            <div>
              <div className="text-[11px] font-semibold text-text3 uppercase tracking-wider">Last Name</div>
              <div className="mt-1 font-semibold text-text text-sm">{personalInfo.lastName || '—'}</div>
            </div>
            <div>
              <div className="text-[11px] font-semibold text-text3 uppercase tracking-wider">Suffix</div>
              <div className="mt-1 font-semibold text-text text-sm">{personalInfo.suffix || '—'}</div>
            </div>
            <div>
              <div className="text-[11px] font-semibold text-text3 uppercase tracking-wider">Student ID</div>
              <div className="mt-1 font-mono font-bold text-accent text-sm">{personalInfo.studentId || '—'}</div>
            </div>
            <div>
              <div className="text-[11px] font-semibold text-text3 uppercase tracking-wider">Phone Number</div>
              <div className="mt-1 font-semibold text-text text-sm">{personalInfo.phone || '—'}</div>
            </div>
            <div>
              <div className="text-[11px] font-semibold text-text3 uppercase tracking-wider">Birthdate</div>
              <div className="mt-1 font-semibold text-text text-sm">
                {formatBirthdateDisplay(personalInfo.birthdate)}
              </div>
            </div>
            <div>
              <div className="text-[11px] font-semibold text-text3 uppercase tracking-wider">Gender</div>
              <div className="mt-1 font-semibold text-text text-sm">{personalInfo.gender || '—'}</div>
            </div>
            <div>
              <div className="text-[11px] font-semibold text-text3 uppercase tracking-wider">Parent / Guardian</div>
              <div className="mt-1 font-semibold text-text text-sm">{personalInfo.guardianName || '—'}</div>
            </div>
            <div>
              <div className="text-[11px] font-semibold text-text3 uppercase tracking-wider">Guardian Phone</div>
              <div className="mt-1 font-semibold text-text text-sm">{personalInfo.guardianContact || '—'}</div>
            </div>
            <div className="sm:col-span-2 pt-1 border-t border-border/60">
              <div className="text-[11px] font-semibold text-text3 uppercase tracking-wider">Residential Address</div>
              <div className="mt-1 font-medium text-text text-sm leading-relaxed">
                {personalInfo.address || '—'}
              </div>
            </div>
          </div>
        </div>

        {/* Card 2: Academic Information */}
        <div className="p-6 rounded-2xl bg-bg2 border border-border shadow-xs space-y-5">
          <div className="flex items-center justify-between border-b border-border pb-3.5">
            <div className="flex items-center gap-2">
              <span className="p-1.5 rounded-lg bg-accent-glow text-accent">
                <RiGraduationCapLine className="size-4" />
              </span>
              <h2 className="text-base font-bold font-serif text-text">Academic Information</h2>
            </div>
            <button
              type="button"
              onClick={handleOpenAcademic}
              className="px-3 py-1 rounded-lg text-xs font-semibold text-text2 hover:text-text bg-bg3 hover:bg-bg4 border border-border cursor-pointer transition-colors inline-flex items-center gap-1.5"
            >
              <RiEditLine className="size-3.5" />
              <span>Edit</span>
            </button>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
            <div>
              <div className="text-[11px] font-semibold text-text3 uppercase tracking-wider">
                Learner Reference No. (LRN)
              </div>
              <div className="mt-1 font-mono font-bold text-accent text-sm">{academicInfo.lrn || '—'}</div>
            </div>
            <div>
              <div className="text-[11px] font-semibold text-text3 uppercase tracking-wider">Grade Level</div>
              <div className="mt-1 font-semibold text-text text-sm">{academicInfo.gradeLevel || '—'}</div>
            </div>
            <div>
              <div className="text-[11px] font-semibold text-text3 uppercase tracking-wider">Section</div>
              <div className="mt-1 font-semibold text-text text-sm">{academicInfo.section || '—'}</div>
            </div>
            <div>
              <div className="text-[11px] font-semibold text-text3 uppercase tracking-wider">Adviser</div>
              <div className="mt-1 font-semibold text-text text-sm">{academicInfo.adviser || '—'}</div>
            </div>
            <div className="sm:col-span-2 pt-1 border-t border-border/60">
              <div className="text-[11px] font-semibold text-text3 uppercase tracking-wider">Senior High Track / Strand</div>
              <div className="mt-1 font-semibold text-text text-sm">
                {academicInfo.strand}
              </div>
            </div>
            <div className="sm:col-span-2 pt-1 border-t border-border/60">
              <div className="text-[11px] font-semibold text-text3 uppercase tracking-wider">Enrollment Date</div>
              <div className="mt-1 font-semibold text-text text-sm">{academicInfo.enrollmentDate || '—'}</div>
            </div>
          </div>
        </div>
      </div>

      {/* ============================================================ */}
      {/* 4. DANGER ZONE (matching PHP line 708)                        */}
      {/* ============================================================ */}
      <div className="p-6 rounded-2xl bg-bg2 border border-red/25 shadow-xs">
        <div className="flex items-center gap-2 border-b border-border pb-3 mb-4">
          <span className="p-1.5 rounded-lg bg-red-bg text-red">
            <RiAlertLine className="size-4 text-red" />
          </span>
          <h3 className="text-sm font-bold text-red uppercase tracking-wider">Danger Zone</h3>
        </div>
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <div className="font-semibold text-sm text-text">Deactivate Account</div>
            <div className="text-xs text-text3 mt-0.5 max-w-xl">
              Temporarily deactivate your student portal access. You will need to contact an Excelsior Polytechnic Academy administrator to reactivate your credentials.
            </div>
          </div>
          <button
            type="button"
            onClick={() => setIsDeactivateModalOpen(true)}
            className="self-start sm:self-auto px-4 py-2 rounded-xl bg-red-bg hover:bg-red/20 text-red text-xs font-semibold border border-red/30 cursor-pointer active:scale-95 transition-all"
          >
            Deactivate Account
          </button>
        </div>
      </div>

      {/* Footer credits */}
      <div className="text-center text-xs text-text3 pt-4">
        ExceLearn LMS v2.1 · Excelsior Polytechnic Academy © JBH: 2025–2026
      </div>

      {/* ============================================================ */}
      {/* MODAL 1: EDIT PERSONAL PROFILE (matching PHP editModal)       */}
      {/* ============================================================ */}
      {isEditModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs animate-in fade-in duration-200">
          <div className="relative w-full max-w-lg rounded-2xl bg-bg2 border border-border shadow-2xl overflow-hidden animate-in zoom-in-95 duration-200">
            {/* Modal Header Banner */}
            <div
              className="h-20 relative p-4 flex items-center justify-between"
              style={{
                background: 'linear-gradient(135deg, #1e3c72 0%, #2a5298 42%, #f7971e 100%)',
              }}
            >
              <div className="text-white">
                <h3 className="font-serif text-lg font-bold">Edit Profile</h3>
                <p className="text-xs text-white/80">Update your student personal information</p>
              </div>
              <button
                type="button"
                onClick={() => setIsEditModalOpen(false)}
                className="size-8 rounded-full bg-black/30 hover:bg-black/50 text-white flex items-center justify-center cursor-pointer transition-colors"
              >
                <RiCloseLine className="size-4" />
              </button>
            </div>

            {/* Modal Body Form */}
            <form onSubmit={handleSaveEdit} className="p-5 sm:p-6 space-y-4 max-h-[75vh] overflow-y-auto">
              {/* Photo Upload area inside modal */}
              <div className="flex items-center gap-4 p-3 rounded-xl bg-bg3 border border-border">
                <div className="size-14 rounded-full overflow-hidden bg-bg2 flex items-center justify-center font-bold text-accent border border-border shrink-0">
                  {avatarPreview ? (
                    <img src={avatarPreview} alt="Preview" className="w-full h-full object-cover" />
                  ) : (
                    <span>{initials}</span>
                  )}
                </div>
                <div className="flex-1 min-w-0">
                  <label
                    htmlFor="studentModalPicInput"
                    className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-accent text-white hover:bg-accent2 cursor-pointer transition-colors shadow-xs"
                  >
                    <RiCameraLine className="size-3.5" />
                    <span>Upload New Photo</span>
                  </label>
                  <input
                    type="file"
                    id="studentModalPicInput"
                    accept="image/*"
                    className="hidden"
                    onChange={handleAvatarFileChange}
                  />
                  <p className="text-[10px] text-text3 mt-1">Allowed: JPG, PNG, WEBP. Max 2MB.</p>
                </div>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-semibold text-text2 mb-1">First Name *</label>
                  <input
                    type="text"
                    required
                    value={editDraft.firstName}
                    onChange={(e) => setEditDraft({ ...editDraft, firstName: e.target.value })}
                    className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text focus:border-accent outline-none"
                  />
                </div>
                <div>
                  <label className="block text-xs font-semibold text-text2 mb-1">Middle Name</label>
                  <input
                    type="text"
                    value={editDraft.middleName}
                    onChange={(e) => setEditDraft({ ...editDraft, middleName: e.target.value })}
                    className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text focus:border-accent outline-none"
                  />
                </div>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-semibold text-text2 mb-1">Last Name *</label>
                  <input
                    type="text"
                    required
                    value={editDraft.lastName}
                    onChange={(e) => setEditDraft({ ...editDraft, lastName: e.target.value })}
                    className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text focus:border-accent outline-none"
                  />
                </div>
                <div>
                  <label className="block text-xs font-semibold text-text2 mb-1">Suffix</label>
                  <input
                    type="text"
                    placeholder="Jr., Sr., III"
                    value={editDraft.suffix}
                    onChange={(e) => setEditDraft({ ...editDraft, suffix: e.target.value })}
                    className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text focus:border-accent outline-none"
                  />
                </div>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-semibold text-text2 mb-1">Student ID</label>
                  <input
                    type="text"
                    value={editDraft.studentId}
                    onChange={(e) => setEditDraft({ ...editDraft, studentId: e.target.value })}
                    className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text font-mono focus:border-accent outline-none"
                  />
                </div>
                <div>
                  <label className="block text-xs font-semibold text-text2 mb-1">Phone Number</label>
                  <input
                    type="tel"
                    value={editDraft.phone}
                    onChange={(e) => setEditDraft({ ...editDraft, phone: e.target.value })}
                    className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text focus:border-accent outline-none"
                  />
                </div>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-semibold text-text2 mb-1">Birthdate</label>
                  <input
                    type="date"
                    value={editDraft.birthdate}
                    onChange={(e) => setEditDraft({ ...editDraft, birthdate: e.target.value })}
                    className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text focus:border-accent outline-none"
                  />
                </div>
                <div>
                  <label className="block text-xs font-semibold text-text2 mb-1">Gender</label>
                  <select
                    value={editDraft.gender}
                    onChange={(e) => setEditDraft({ ...editDraft, gender: e.target.value })}
                    className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text focus:border-accent outline-none"
                  >
                    <option value="Female">Female</option>
                    <option value="Male">Male</option>
                    <option value="Other">Other</option>
                    <option value="Prefer not to say">Prefer not to say</option>
                  </select>
                </div>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-semibold text-text2 mb-1">Parent / Guardian Name</label>
                  <input
                    type="text"
                    placeholder="e.g. Roberto Donceras"
                    value={editDraft.guardianName}
                    onChange={(e) => setEditDraft({ ...editDraft, guardianName: e.target.value })}
                    className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text focus:border-accent outline-none"
                  />
                </div>
                <div>
                  <label className="block text-xs font-semibold text-text2 mb-1">Guardian Phone Number</label>
                  <input
                    type="tel"
                    placeholder="e.g. 0917-889-1234"
                    value={editDraft.guardianContact}
                    onChange={(e) => setEditDraft({ ...editDraft, guardianContact: e.target.value })}
                    className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text focus:border-accent outline-none"
                  />
                </div>
              </div>

              <div>
                <label className="block text-xs font-semibold text-text2 mb-1">Address</label>
                <textarea
                  rows={2}
                  value={editDraft.address}
                  onChange={(e) => setEditDraft({ ...editDraft, address: e.target.value })}
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text focus:border-accent outline-none resize-none"
                />
              </div>

              {/* Actions */}
              <div className="pt-3 border-t border-border flex items-center justify-end gap-2.5">
                <button
                  type="button"
                  onClick={() => setIsEditModalOpen(false)}
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
        </div>
      )}

      {/* ============================================================ */}
      {/* MODAL 2: EDIT ACADEMIC INFO (matching PHP academicModal)     */}
      {/* ============================================================ */}
      {isAcademicModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs animate-in fade-in duration-200">
          <div className="relative w-full max-w-lg rounded-2xl bg-bg2 border border-border shadow-2xl overflow-hidden animate-in zoom-in-95 duration-200">
            {/* Header banner */}
            <div
              className="h-20 relative p-4 flex items-center justify-between"
              style={{
                background: 'linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%)',
              }}
            >
              <div className="text-white">
                <h3 className="font-serif text-lg font-bold">Edit Academic Information</h3>
                <p className="text-xs text-white/80">DepEd Senior High School academic parameters</p>
              </div>
              <button
                type="button"
                onClick={() => setIsAcademicModalOpen(false)}
                className="size-8 rounded-full bg-black/30 hover:bg-black/50 text-white flex items-center justify-center cursor-pointer transition-colors"
              >
                <RiCloseLine className="size-4" />
              </button>
            </div>

            {/* Body */}
            <form onSubmit={handleSaveAcademic} className="p-5 sm:p-6 space-y-4">
              <div>
                <label className="block text-xs font-semibold text-text2 mb-1">
                  Learner Reference No. (LRN)
                </label>
                <input
                  type="text"
                  value={academicDraft.lrn}
                  onChange={(e) => setAcademicDraft({ ...academicDraft, lrn: e.target.value })}
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text font-mono focus:border-accent outline-none"
                />
              </div>

              <div>
                <label className="block text-xs font-semibold text-text2 mb-1">Adviser</label>
                <input
                  type="text"
                  value={academicDraft.adviser}
                  onChange={(e) => setAcademicDraft({ ...academicDraft, adviser: e.target.value })}
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text focus:border-accent outline-none"
                />
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-semibold text-text2 mb-1">Grade Level</label>
                  <select
                    value={academicDraft.gradeLevel}
                    onChange={(e) => setAcademicDraft({ ...academicDraft, gradeLevel: e.target.value })}
                    className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text focus:border-accent outline-none"
                  >
                    <option value="Grade 11">Grade 11</option>
                    <option value="Grade 12">Grade 12</option>
                  </select>
                </div>
                <div>
                  <label className="block text-xs font-semibold text-text2 mb-1">Section</label>
                  <select
                    value={academicDraft.section}
                    onChange={(e) => setAcademicDraft({ ...academicDraft, section: e.target.value })}
                    className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text focus:border-accent outline-none"
                  >
                    <option value="STEM 11-A">STEM 11-A</option>
                    <option value="STEM 11-B">STEM 11-B</option>
                    <option value="HUMSS 11-A">HUMSS 11-A</option>
                    <option value="ABM 11-A">ABM 11-A</option>
                    <option value="TVL-ICT 11-A">TVL-ICT 11-A</option>
                    <option value="STEM 12-A">STEM 12-A</option>
                  </select>
                </div>
              </div>

              <div className="pt-3 border-t border-border flex items-center justify-end gap-2.5">
                <button
                  type="button"
                  onClick={() => setIsAcademicModalOpen(false)}
                  className="px-4 py-2 rounded-xl text-xs font-semibold text-text2 hover:text-text bg-bg3 hover:bg-bg4 border border-border cursor-pointer transition-colors"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-5 py-2 rounded-xl text-xs font-semibold text-white bg-accent hover:bg-accent2 shadow-xs cursor-pointer active:scale-95 transition-all"
                >
                  Save Academic
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* ============================================================ */}
      {/* MODAL 3: CHANGE PASSWORD (matching PHP passwordModal)         */}
      {/* ============================================================ */}
      {isPasswordModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs animate-in fade-in duration-200">
          <div className="relative w-full max-w-md rounded-2xl bg-bg2 border border-border shadow-2xl overflow-hidden animate-in zoom-in-95 duration-200">
            {/* Header banner */}
            <div
              className="h-20 relative p-4 flex items-center justify-between"
              style={{
                background: 'linear-gradient(135deg, #2a5298 0%, #1e3c72 100%)',
              }}
            >
              <div className="text-white">
                <h3 className="font-serif text-lg font-bold">Change Password</h3>
                <p className="text-xs text-white/80">Keep your LMS account secure</p>
              </div>
              <button
                type="button"
                onClick={() => setIsPasswordModalOpen(false)}
                className="size-8 rounded-full bg-black/30 hover:bg-black/50 text-white flex items-center justify-center cursor-pointer transition-colors"
              >
                <RiCloseLine className="size-4" />
              </button>
            </div>

            {/* Body */}
            <form onSubmit={handlePasswordSubmit} className="p-5 sm:p-6 space-y-3.5">
              {passwordError && (
                <div className="p-3 rounded-lg bg-red-bg border border-red/30 text-xs text-red flex items-center gap-2">
                  <RiAlertLine className="size-4 shrink-0" />
                  <span>{passwordError}</span>
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
                  placeholder="Minimum 8 characters"
                  value={newPassword}
                  onChange={(e) => setNewPassword(e.target.value)}
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text focus:border-accent outline-none"
                  required
                />
                {/* 4-bar strength indicator */}
                {newPassword && (
                  <div className="mt-2 space-y-1">
                    <div className="flex gap-1">
                      {[1, 2, 3, 4].map((step) => (
                        <div
                          key={step}
                          className={`h-1 flex-1 rounded-full transition-colors ${
                            pwStrengthScore >= step
                              ? pwStrengthScore === 1
                                ? 'bg-red'
                                : pwStrengthScore === 2
                                ? 'bg-amber'
                                : pwStrengthScore === 3
                                ? 'bg-accent'
                                : 'bg-green'
                              : 'bg-bg4'
                          }`}
                        />
                      ))}
                    </div>
                    <div className="text-[10px] text-text3 text-right">
                      {pwStrengthScore === 1 && 'Weak'}
                      {pwStrengthScore === 2 && 'Fair'}
                      {pwStrengthScore === 3 && 'Good'}
                      {pwStrengthScore >= 4 && 'Strong password'}
                    </div>
                  </div>
                )}
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
                  onClick={() => setIsPasswordModalOpen(false)}
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
      {/* MODAL 4: DEACTIVATE CONFIRMATION                             */}
      {/* ============================================================ */}
      {isDeactivateModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs animate-in fade-in duration-200">
          <div className="relative w-full max-w-sm rounded-2xl bg-bg2 border border-border p-6 shadow-2xl text-center space-y-4">
            <div className="size-12 rounded-full bg-red-bg text-red flex items-center justify-center mx-auto">
              <RiAlertLine className="size-6 text-red" />
            </div>
            <div>
              <h4 className="text-base font-bold text-text">Deactivate Student Account?</h4>
              <p className="text-xs text-text3 mt-1.5 leading-relaxed">
                Are you sure you want to deactivate your account? This action cannot be easily undone and requires administrator approval to restore.
              </p>
            </div>
            <div className="flex gap-2.5 pt-2">
              <button
                type="button"
                onClick={() => setIsDeactivateModalOpen(false)}
                className="flex-1 py-2 rounded-xl text-xs font-semibold text-text2 bg-bg3 hover:bg-bg4 border border-border cursor-pointer transition-colors"
              >
                Cancel
              </button>
              <button
                type="button"
                onClick={() => {
                  setIsDeactivateModalOpen(false);
                  showToast('Deactivation request logged. Contact administrator.');
                }}
                className="flex-1 py-2 rounded-xl text-xs font-semibold text-white bg-red hover:bg-red/90 cursor-pointer transition-colors"
              >
                Confirm
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
