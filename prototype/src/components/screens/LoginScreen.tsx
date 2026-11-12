import { useState } from 'react';
import {
  RiBookOpenLine,
  RiFileList3Line,
  RiVideoChatLine,
  RiAwardLine,
  RiGraduationCapFill,
  RiPresentationFill,
  RiUser3Line,
  RiLockLine,
  RiEyeLine,
  RiEyeOffLine,
  RiArrowRightLine,
  RiShieldCheckLine,
  RiMailLine,
  RiTimeLine,
  RiArrowLeftLine,
  RiCheckLine,
} from '@remixicon/react';

interface LoginScreenProps {
  onLogin: (role: 'student' | 'teacher' | 'admin') => void;
}

type AuthMode = 'login' | 'register' | 'pending' | 'forgot';

export function LoginScreen({ onLogin }: LoginScreenProps) {
  const [authMode, setAuthMode] = useState<AuthMode>('login');
  const [selectedRole, setSelectedRole] = useState<'student' | 'teacher'>('student');
  const [identifier, setIdentifier] = useState('STUD-123456');
  const [password, setPassword] = useState('Admin@123');
  const [showPassword, setShowPassword] = useState(false);
  const [rememberMe, setRememberMe] = useState(true);

  // Register form state (register.php)
  const [regFirst, setRegFirst] = useState('');
  const [regMiddle, setRegMiddle] = useState('');
  const [regLast, setRegLast] = useState('');
  const [regEmail, setRegEmail] = useState('');
  const [regPassword, setRegPassword] = useState('');
  const [regConfirmPassword, setRegConfirmPassword] = useState('');
  const [regError, setRegError] = useState('');

  // Forgot password form state (forgotpassword.php -> verify -> newpassword)
  const [fpStep, setFpStep] = useState<1 | 2 | 3>(1);
  const [fpEmail, setFpEmail] = useState('');
  const [fpOtp, setFpOtp] = useState('');
  const [fpNewPass, setFpNewPass] = useState('');
  const [fpConfirmPass, setFpConfirmPass] = useState('');
  const [fpSuccess, setFpSuccess] = useState('');

  const handleRoleToggle = (role: 'student' | 'teacher') => {
    setSelectedRole(role);
    if (role === 'student') {
      setIdentifier('STUD-123456');
    } else {
      setIdentifier('junacvelarde@gmail.com');
    }
  };

  const handleLoginSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (identifier.toLowerCase().includes('admin') || identifier === 'excelsiorpolyacad@gmail.com') {
      onLogin('admin');
    } else {
      onLogin(selectedRole);
    }
  };

  const handleRegisterSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!regFirst || !regLast || !regEmail || !regPassword || !regConfirmPassword) {
      setRegError('Please fill in all required fields.');
      return;
    }
    if (regPassword !== regConfirmPassword) {
      setRegError('Passwords do not match.');
      return;
    }
    if (regPassword.length < 8) {
      setRegError('Password must be at least 8 characters long.');
      return;
    }
    setRegError('');
    setAuthMode('pending');
  };

  const handleFpStep1 = (e: React.FormEvent) => {
    e.preventDefault();
    if (!fpEmail) return;
    setFpStep(2);
  };

  const handleFpStep2 = (e: React.FormEvent) => {
    e.preventDefault();
    if (fpOtp.length < 4) return;
    setFpStep(3);
  };

  const handleFpStep3 = (e: React.FormEvent) => {
    e.preventDefault();
    if (fpNewPass !== fpConfirmPass) return;
    setFpSuccess('Password successfully reset! You may now sign in.');
    setTimeout(() => {
      setAuthMode('login');
      setFpStep(1);
      setFpSuccess('');
    }, 1800);
  };

  return (
    <div className="min-h-screen w-full flex flex-col lg:flex-row bg-bg">
      {/* Left Panel: Hero with Animated Living School Gradient */}
      <div className="relative lg:w-1/2 min-h-[420px] lg:min-h-screen excelearn-living-grad p-8 lg:p-14 flex flex-col justify-between overflow-hidden text-white select-none">
        {/* Soft Ambient Radial Lights */}
        <div className="absolute -top-24 -left-24 size-96 rounded-full bg-white/10 blur-3xl pointer-events-none" />
        <div className="absolute -bottom-24 -right-24 size-96 rounded-full bg-school-orange/20 blur-3xl pointer-events-none" />

        {/* Top Header */}
        <div className="relative z-10 flex items-center gap-3">
          <img
            src="/img/Excelsior_Seal_Logo.png"
            alt="Excelsior Polytechnic Academy Seal"
            className="size-14 drop-shadow-md rounded-full bg-white/10 p-1"
          />
          <div>
            <h2 className="text-xl font-bold tracking-tight text-white font-serif">
              Excelsior Polytechnic Academy
            </h2>
            <p className="text-xs text-white/75">
              Cabitan, Mandaon, Masbate · Senior High School
            </p>
          </div>
        </div>

        {/* Center Mission & Title */}
        <div className="relative z-10 my-8 lg:my-0 max-w-xl">
          <h1 className="text-3xl lg:text-4xl xl:text-5xl font-serif font-normal leading-tight tracking-tight text-white mb-4">
            Excellence in Technology, Character, and Innovation
          </h1>
          <p className="text-sm lg:text-base text-white/80 leading-relaxed font-sans">
            Empowering students across STEM, ABM, HUMSS, TVL-ICT, and GAS strands with
            world-class digital classrooms, continuous competency assessment, and seamless faculty collaboration.
          </p>

          {/* 4 Feature Rows in Frosted Glass Wells */}
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-8">
            <div className="flex items-start gap-3 p-3.5 rounded-xl bg-white/10 backdrop-blur-md border border-white/15">
              <div className="p-2 rounded-lg bg-white/15 text-white shrink-0">
                <RiBookOpenLine className="size-5" aria-hidden="true" />
              </div>
              <div>
                <h4 className="text-sm font-semibold text-white">SHS Modular Lessons</h4>
                <p className="text-xs text-white/75 mt-0.5">Aligned with DepEd K to 12 MATATAG competency standards.</p>
              </div>
            </div>

            <div className="flex items-start gap-3 p-3.5 rounded-xl bg-white/10 backdrop-blur-md border border-white/15">
              <div className="p-2 rounded-lg bg-white/15 text-white shrink-0">
                <RiFileList3Line className="size-5" aria-hidden="true" />
              </div>
              <div>
                <h4 className="text-sm font-semibold text-white">Task & Quiz Tracking</h4>
                <p className="text-xs text-white/75 mt-0.5">Real-time rubrics, written works, and performance tasks.</p>
              </div>
            </div>

            <div className="flex items-start gap-3 p-3.5 rounded-xl bg-white/10 backdrop-blur-md border border-white/15">
              <div className="p-2 rounded-lg bg-white/15 text-white shrink-0">
                <RiVideoChatLine className="size-5" aria-hidden="true" />
              </div>
              <div>
                <h4 className="text-sm font-semibold text-white">Live Virtual Classrooms</h4>
                <p className="text-xs text-white/75 mt-0.5">One-click interactive sessions directly with your teachers.</p>
              </div>
            </div>

            <div className="flex items-start gap-3 p-3.5 rounded-xl bg-white/10 backdrop-blur-md border border-white/15">
              <div className="p-2 rounded-lg bg-white/15 text-white shrink-0">
                <RiAwardLine className="size-5" aria-hidden="true" />
              </div>
              <div>
                <h4 className="text-sm font-semibold text-white">Quarterly DepEd Grades</h4>
                <p className="text-xs text-white/75 mt-0.5">Automated 25-50-25 weight computation & digital reports.</p>
              </div>
            </div>
          </div>
        </div>

        {/* Footer */}
        <div className="relative z-10 text-xs text-white/60 flex items-center justify-between pt-4 border-t border-white/15">
          <span>DepEd School ID: 405621 · EPA Portal</span>
          <span>v2.6 Modern LMS Prototype</span>
        </div>
      </div>

      {/* Right Panel: Interactive Auth Forms (Login / Register / Forgot Password) */}
      <div className="lg:w-1/2 flex items-center justify-center p-6 sm:p-10 lg:p-16 bg-bg overflow-y-auto">
        <div className="w-full max-w-md space-y-6">
          {/* ============================================================ */}
          {/* VIEW 1: UNIVERSAL LOGIN (index.php)                          */}
          {/* ============================================================ */}
          {authMode === 'login' && (
            <>
              <div>
                <div className="flex items-center gap-2 mb-2">
                  <span className="text-2xl font-bold tracking-tight text-text">
                    Exce<span className="text-green">Learn</span>
                  </span>
                  <span className="px-2 py-0.5 text-[11px] font-semibold rounded-full bg-accent/15 text-accent">
                    DepEd LMS
                  </span>
                </div>
                <h2 className="text-3xl font-serif font-bold text-text tracking-tight">
                  Welcome back
                </h2>
                <p className="text-sm text-text2 mt-1">
                  Sign in to your learning workspace using your institutional account.
                </p>
              </div>

              {/* Role Segmented Control (matches index.php Student vs Teacher tab) */}
              <div className="p-1 rounded-xl bg-bg3 border border-border flex gap-1">
                <button
                  type="button"
                  onClick={() => handleRoleToggle('student')}
                  className={`flex-1 flex items-center justify-center gap-2 py-2 px-3 text-sm font-medium rounded-lg transition-all duration-150 cursor-pointer ${
                    selectedRole === 'student'
                      ? 'bg-bg2 text-text shadow-xs border border-border2 font-semibold'
                      : 'text-text2 hover:text-text'
                  }`}
                >
                  <RiGraduationCapFill className={`size-4 ${selectedRole === 'student' ? 'text-accent' : 'text-text3'}`} aria-hidden="true" />
                  <span>Student</span>
                </button>
                <button
                  type="button"
                  onClick={() => handleRoleToggle('teacher')}
                  className={`flex-1 flex items-center justify-center gap-2 py-2 px-3 text-sm font-medium rounded-lg transition-all duration-150 cursor-pointer ${
                    selectedRole === 'teacher'
                      ? 'bg-bg2 text-text shadow-xs border border-border2 font-semibold'
                      : 'text-text2 hover:text-text'
                  }`}
                >
                  <RiPresentationFill className={`size-4 ${selectedRole === 'teacher' ? 'text-school-orange' : 'text-text3'}`} aria-hidden="true" />
                  <span>Teacher / Admin</span>
                </button>
              </div>

              {/* Form */}
              <form onSubmit={handleLoginSubmit} className="space-y-4">
                <div>
                  <label htmlFor="identifier-input" className="block text-xs font-semibold text-text2 uppercase tracking-wider mb-1.5">
                    {selectedRole === 'student' ? 'Student ID or LRN' : 'School Gmail / Admin Email'}
                  </label>
                  <div className="relative">
                    <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-text3">
                      <RiUser3Line className="size-4" aria-hidden="true" />
                    </div>
                    <input
                      id="identifier-input"
                      type="text"
                      value={identifier}
                      onChange={(e) => setIdentifier(e.target.value)}
                      placeholder={selectedRole === 'student' ? 'e.g. STUD-123456 or STUD-2026-12345' : 'e.g. junacvelarde@gmail.com'}
                      className="w-full pl-9 pr-3 py-2.5 rounded-lg bg-bg2 border border-border text-sm text-text placeholder:text-text3 focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent transition-all"
                      required
                    />
                  </div>
                </div>

                <div>
                  <div className="flex items-center justify-between mb-1.5">
                    <label htmlFor="password-input" className="block text-xs font-semibold text-text2 uppercase tracking-wider">
                      Password
                    </label>
                    <button
                      type="button"
                      className="text-xs text-accent hover:underline cursor-pointer"
                      onClick={() => setAuthMode('forgot')}
                    >
                      Forgot password?
                    </button>
                  </div>
                  <div className="relative">
                    <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-text3">
                      <RiLockLine className="size-4" aria-hidden="true" />
                    </div>
                    <input
                      id="password-input"
                      type={showPassword ? 'text' : 'password'}
                      value={password}
                      onChange={(e) => setPassword(e.target.value)}
                      placeholder="Enter account password (default: Admin@123)"
                      className="w-full pl-9 pr-10 py-2.5 rounded-lg bg-bg2 border border-border text-sm text-text placeholder:text-text3 focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent transition-all"
                      required
                    />
                    <button
                      type="button"
                      onClick={() => setShowPassword(!showPassword)}
                      className="absolute inset-y-0 right-0 pr-3 flex items-center text-text3 hover:text-text cursor-pointer"
                      aria-label={showPassword ? 'Hide password' : 'Show password'}
                    >
                      {showPassword ? (
                        <RiEyeOffLine className="size-4" aria-hidden="true" />
                      ) : (
                        <RiEyeLine className="size-4" aria-hidden="true" />
                      )}
                    </button>
                  </div>
                </div>

                <div className="flex items-center justify-between pt-1">
                  <label className="flex items-center gap-2 cursor-pointer select-none">
                    <input
                      type="checkbox"
                      checked={rememberMe}
                      onChange={(e) => setRememberMe(e.target.checked)}
                      className="rounded border-border text-accent focus:ring-accent/20 cursor-pointer size-4"
                    />
                    <span className="text-xs text-text2">Remember me</span>
                  </label>
                  <span className="text-xs text-text3">SSL Secured</span>
                </div>

                {/* Tactile High-Contrast Sign In Button */}
                <button
                  type="submit"
                  className="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl font-semibold text-[#0e0f14] shadow-md transition-all duration-150 cursor-pointer active:scale-[0.96] hover:brightness-105"
                  style={{
                    background: 'linear-gradient(135deg, #f7971e 0%, #fbb03b 100%)',
                  }}
                >
                  <span>Sign In as {selectedRole === 'student' ? 'Student' : 'Faculty'}</span>
                  <RiArrowRightLine className="size-4 shrink-0" aria-hidden="true" />
                </button>
              </form>

              {/* Faculty Registration Link (register.php) */}
              <div className="text-center pt-2">
                <p className="text-xs text-text2">
                  New Faculty Member?{' '}
                  <button
                    type="button"
                    onClick={() => setAuthMode('register')}
                    className="font-semibold text-accent hover:underline cursor-pointer"
                  >
                    Register as Teacher &rarr;
                  </button>
                </p>
              </div>

              {/* Quick Demo Credentials */}
              <div className="pt-4 border-t border-border">
                <span className="text-xs text-text3 block mb-2 font-medium">Quick Demo Profiles:</span>
                <div className="grid grid-cols-3 gap-2">
                  <button
                    type="button"
                    onClick={() => onLogin('student')}
                    className="p-2 text-left rounded-lg bg-bg2 border border-border hover:border-accent/50 transition-all cursor-pointer group"
                  >
                    <div className="text-[11px] font-semibold text-text group-hover:text-accent">Bryan Donceras</div>
                    <div className="text-[10px] text-text3">STUD-123456</div>
                  </button>
                  <button
                    type="button"
                    onClick={() => onLogin('teacher')}
                    className="p-2 text-left rounded-lg bg-bg2 border border-border hover:border-school-orange/50 transition-all cursor-pointer group"
                  >
                    <div className="text-[11px] font-semibold text-text group-hover:text-school-orange">Ms. Juna Velarde</div>
                    <div className="text-[10px] text-text3">Teacher</div>
                  </button>
                  <button
                    type="button"
                    onClick={() => onLogin('admin')}
                    className="p-2 text-left rounded-lg bg-bg2 border border-border hover:border-purple/50 transition-all cursor-pointer group"
                  >
                    <div className="text-[11px] font-semibold text-text group-hover:text-purple flex items-center gap-1">
                      <RiShieldCheckLine className="size-3 text-purple" aria-hidden="true" />
                      <span>Admin</span>
                    </div>
                    <div className="text-[10px] text-text3">Super Admin</div>
                  </button>
                </div>
              </div>
            </>
          )}

          {/* ============================================================ */}
          {/* VIEW 2: TEACHER REGISTRATION (register.php)                  */}
          {/* ============================================================ */}
          {authMode === 'register' && (
            <div className="space-y-4">
              <div>
                <button
                  type="button"
                  onClick={() => setAuthMode('login')}
                  className="flex items-center gap-1 text-xs text-text3 hover:text-text mb-3 cursor-pointer"
                >
                  <RiArrowLeftLine className="size-4" aria-hidden="true" />
                  <span>Back to Sign In</span>
                </button>
                <h2 className="text-2xl font-serif font-bold text-text tracking-tight">
                  Teacher Registration
                </h2>
                <p className="text-xs text-text2 mt-1">
                  Create your faculty account. Submissions are reviewed by the administrator before activation.
                </p>
              </div>

              {regError && (
                <div className="p-3 rounded-lg bg-red/10 border border-red/20 text-xs text-red">
                  {regError}
                </div>
              )}

              <form onSubmit={handleRegisterSubmit} className="space-y-3">
                <div className="grid grid-cols-2 gap-2.5">
                  <div>
                    <label className="block text-xs font-semibold text-text2 mb-1">First Name *</label>
                    <input
                      type="text"
                      placeholder="e.g. Juna"
                      value={regFirst}
                      onChange={(e) => setRegFirst(e.target.value)}
                      className="w-full px-3 py-2 rounded-lg bg-bg2 border border-border text-xs text-text focus:outline-none focus:border-accent"
                      required
                    />
                  </div>
                  <div>
                    <label className="block text-xs font-semibold text-text2 mb-1">Middle Name</label>
                    <input
                      type="text"
                      placeholder="e.g. Castro"
                      value={regMiddle}
                      onChange={(e) => setRegMiddle(e.target.value)}
                      className="w-full px-3 py-2 rounded-lg bg-bg2 border border-border text-xs text-text focus:outline-none focus:border-accent"
                    />
                  </div>
                </div>

                <div>
                  <label className="block text-xs font-semibold text-text2 mb-1">Last Name *</label>
                  <input
                    type="text"
                    placeholder="e.g. Velarde"
                    value={regLast}
                    onChange={(e) => setRegLast(e.target.value)}
                    className="w-full px-3 py-2 rounded-lg bg-bg2 border border-border text-xs text-text focus:outline-none focus:border-accent"
                    required
                  />
                </div>

                <div>
                  <label className="block text-xs font-semibold text-text2 mb-1">DepEd / School Gmail Address *</label>
                  <input
                    type="email"
                    placeholder="e.g. teacher@deped.gov.ph"
                    value={regEmail}
                    onChange={(e) => setRegEmail(e.target.value)}
                    className="w-full px-3 py-2 rounded-lg bg-bg2 border border-border text-xs text-text focus:outline-none focus:border-accent"
                    required
                  />
                </div>

                <div className="grid grid-cols-2 gap-2.5">
                  <div>
                    <label className="block text-xs font-semibold text-text2 mb-1">Password *</label>
                    <input
                      type="password"
                      placeholder="Min 8 characters"
                      value={regPassword}
                      onChange={(e) => setRegPassword(e.target.value)}
                      className="w-full px-3 py-2 rounded-lg bg-bg2 border border-border text-xs text-text focus:outline-none focus:border-accent"
                      required
                    />
                  </div>
                  <div>
                    <label className="block text-xs font-semibold text-text2 mb-1">Confirm Password *</label>
                    <input
                      type="password"
                      placeholder="Re-enter password"
                      value={regConfirmPassword}
                      onChange={(e) => setRegConfirmPassword(e.target.value)}
                      className="w-full px-3 py-2 rounded-lg bg-bg2 border border-border text-xs text-text focus:outline-none focus:border-accent"
                      required
                    />
                  </div>
                </div>

                <div className="pt-2">
                  <button
                    type="submit"
                    className="w-full py-2.5 px-4 rounded-xl font-semibold text-[#0e0f14] text-xs shadow-md cursor-pointer transition-all active:scale-[0.98] hover:brightness-105"
                    style={{ background: 'linear-gradient(135deg, #f7971e 0%, #fbb03b 100%)' }}
                  >
                    Submit Teacher Registration
                  </button>
                </div>
              </form>
            </div>
          )}

          {/* ============================================================ */}
          {/* VIEW 3: REGISTRATION PENDING (registration_pending.php)      */}
          {/* ============================================================ */}
          {authMode === 'pending' && (
            <div className="text-center space-y-4 p-4 rounded-2xl bg-bg2 border border-border shadow-md">
              <div className="size-14 mx-auto rounded-full bg-amber-bg text-amber flex items-center justify-center border border-amber/30">
                <RiTimeLine className="size-7" aria-hidden="true" />
              </div>
              <div>
                <h3 className="text-xl font-bold font-serif text-text">
                  Registration Submitted!
                </h3>
                <p className="text-xs text-text2 mt-1 leading-relaxed">
                  Your faculty account has been registered in <strong>pending approval</strong> status.
                  The administrator will verify your credentials and approve your teaching load.
                </p>
              </div>
              <div className="p-3 rounded-lg bg-bg3 border border-border text-[11px] text-text3 text-left">
                <strong>Next Step:</strong> You will be notified via email once your account has been verified and active.
              </div>
              <button
                type="button"
                onClick={() => setAuthMode('login')}
                className="w-full py-2.5 px-4 rounded-xl bg-accent text-white text-xs font-semibold hover:bg-accent2 cursor-pointer transition-all"
              >
                Return to Sign In
              </button>
            </div>
          )}

          {/* ============================================================ */}
          {/* VIEW 4: PASSWORD RECOVERY (forgotpassword.php 3-step flow)    */}
          {/* ============================================================ */}
          {authMode === 'forgot' && (
            <div className="space-y-4">
              <div>
                <button
                  type="button"
                  onClick={() => {
                    setAuthMode('login');
                    setFpStep(1);
                  }}
                  className="flex items-center gap-1 text-xs text-text3 hover:text-text mb-3 cursor-pointer"
                >
                  <RiArrowLeftLine className="size-4" aria-hidden="true" />
                  <span>Back to Sign In</span>
                </button>
                <h2 className="text-2xl font-serif font-bold text-text tracking-tight">
                  Password Recovery
                </h2>
                <p className="text-xs text-text2 mt-1">
                  Step {fpStep} of 3 — Secure credential reset via 6-digit email OTP.
                </p>
              </div>

              {fpSuccess && (
                <div className="p-3 rounded-lg bg-green-bg border border-green/30 text-xs text-green flex items-center gap-2">
                  <RiCheckLine className="size-4" aria-hidden="true" />
                  <span>{fpSuccess}</span>
                </div>
              )}

              {/* Step 1: Email Lookup */}
              {fpStep === 1 && (
                <form onSubmit={handleFpStep1} className="space-y-3">
                  <div>
                    <label className="block text-xs font-semibold text-text2 mb-1">
                      Enter Registered Email Address
                    </label>
                    <div className="relative">
                      <RiMailLine className="absolute left-3 top-2.5 size-4 text-text3" aria-hidden="true" />
                      <input
                        type="email"
                        placeholder="e.g. junacvelarde@gmail.com"
                        value={fpEmail}
                        onChange={(e) => setFpEmail(e.target.value)}
                        className="w-full pl-9 pr-3 py-2 rounded-lg bg-bg2 border border-border text-xs text-text focus:outline-none focus:border-accent"
                        required
                      />
                    </div>
                  </div>
                  <button
                    type="submit"
                    className="w-full py-2.5 px-4 rounded-xl bg-accent text-white text-xs font-semibold hover:bg-accent2 cursor-pointer transition-all"
                  >
                    Send 6-Digit Verification Code &rarr;
                  </button>
                </form>
              )}

              {/* Step 2: OTP Verification */}
              {fpStep === 2 && (
                <form onSubmit={handleFpStep2} className="space-y-3">
                  <div className="p-3 rounded-lg bg-bg3 text-xs text-text2">
                    A 6-digit OTP verification code has been dispatched to <strong>{fpEmail}</strong>.
                  </div>
                  <div>
                    <label className="block text-xs font-semibold text-text2 mb-1">
                      6-Digit Security OTP
                    </label>
                    <input
                      type="text"
                      maxLength={6}
                      placeholder="e.g. 583921"
                      value={fpOtp}
                      onChange={(e) => setFpOtp(e.target.value)}
                      className="w-full px-3 py-2 text-center tracking-widest font-mono text-base font-bold rounded-lg bg-bg2 border border-border text-text focus:outline-none focus:border-accent"
                      required
                    />
                  </div>
                  <button
                    type="submit"
                    className="w-full py-2.5 px-4 rounded-xl bg-accent text-white text-xs font-semibold hover:bg-accent2 cursor-pointer transition-all"
                  >
                    Verify OTP Code &rarr;
                  </button>
                </form>
              )}

              {/* Step 3: New Password */}
              {fpStep === 3 && (
                <form onSubmit={handleFpStep3} className="space-y-3">
                  <div>
                    <label className="block text-xs font-semibold text-text2 mb-1">
                      New Password *
                    </label>
                    <input
                      type="password"
                      placeholder="Min 8 characters"
                      value={fpNewPass}
                      onChange={(e) => setFpNewPass(e.target.value)}
                      className="w-full px-3 py-2 rounded-lg bg-bg2 border border-border text-xs text-text focus:outline-none focus:border-accent"
                      required
                    />
                  </div>
                  <div>
                    <label className="block text-xs font-semibold text-text2 mb-1">
                      Confirm New Password *
                    </label>
                    <input
                      type="password"
                      placeholder="Confirm new password"
                      value={fpConfirmPass}
                      onChange={(e) => setFpConfirmPass(e.target.value)}
                      className="w-full px-3 py-2 rounded-lg bg-bg2 border border-border text-xs text-text focus:outline-none focus:border-accent"
                      required
                    />
                  </div>
                  <button
                    type="submit"
                    className="w-full py-2.5 px-4 rounded-xl bg-green text-[#0e0f14] text-xs font-semibold hover:brightness-105 cursor-pointer transition-all shadow-xs"
                  >
                    Update Password & Complete
                  </button>
                </form>
              )}
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
