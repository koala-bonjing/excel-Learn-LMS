import type { Role } from '@/types';
import {
  RiMapPin2Line,
  RiGraduationCapLine,
  RiComputerLine,
  RiEyeLine,
  RiTargetLine,
  RiShieldStarLine,
  RiCompass3Line,
  RiSeedlingLine,
  RiBuilding4Line,
  RiSparklingLine,
  RiDashboardLine,
  RiStackLine,
  RiFilmLine,
  RiCheckboxCircleLine,
  RiUserLine,
  RiGroupLine,
  RiListCheck2,
  RiArrowRightLine,
  RiAwardLine,
  RiTimeLine,
  RiHeartPulseLine,
} from '@remixicon/react';

export interface AboutViewProps {
  role?: Role;
  onTabChange?: (tabId: string) => void;
}

export function AboutView({ role = 'student', onTabChange }: AboutViewProps) {
  // Role-specific quick navigation shortcuts from legacy PHP portals
  const roleNavItems =
    role === 'student' || role === 'teacher' || role === 'admin'
      ? {
          student: [
            { id: 'dashboard', label: 'Dashboard', icon: RiDashboardLine },
            { id: 'courses', label: 'My Subjects', icon: RiStackLine },
            { id: 'tasks', label: 'Performance Tasks', icon: RiFilmLine },
            { id: 'grades', label: 'Grades', icon: RiCheckboxCircleLine },
            { id: 'profile', label: 'My Profile', icon: RiUserLine },
          ],
          teacher: [
            { id: 'dashboard', label: 'Dashboard', icon: RiDashboardLine },
            { id: 'courses', label: 'Subjects', icon: RiStackLine },
            { id: 'tasks', label: 'Performance Tasks', icon: RiFilmLine },
            { id: 'grades', label: 'Gradebook', icon: RiCheckboxCircleLine },
            { id: 'profile', label: 'My Profile', icon: RiUserLine },
          ],
          admin: [
            { id: 'dashboard', label: 'Dashboard', icon: RiDashboardLine },
            { id: 'teachers', label: 'Teachers', icon: RiGroupLine },
            { id: 'students', label: 'Students', icon: RiUserLine },
            { id: 'sections', label: 'Grade & Sections', icon: RiListCheck2 },
            { id: 'profile', label: 'My Profile', icon: RiUserLine },
          ],
        }[role]
      : [];

  return (
    <div className="space-y-6 sm:space-y-8 lg:space-y-10 w-full relative pb-16">
      {/* ============================================================ */}
      {/* 1. HERO SECTION & OFFICIAL CREST                             */}
      {/* ============================================================ */}
      <section
        aria-label="Academy Identity"
        className="about-hero about-stagger-1 border border-border rounded-2xl sm:rounded-3xl bg-bg2 overflow-hidden shadow-xs relative"
      >
        {/* Cover Banner */}
        <div className="about-banner relative h-48 sm:h-60 md:h-72 overflow-hidden">
          <div className="about-banner-bg absolute inset-0">
            <img
              src="/img/Excelsior_Seal_Logo_cover.jpg"
              alt="Excelsior Polytechnic Academy Campus Cover"
              className="w-full h-full object-cover object-center transition-transform duration-700 hover:scale-105"
              onError={(e) => {
                e.currentTarget.style.display = 'none';
              }}
            />
          </div>
          {/* Subtle pattern & depth vignette */}
          <div className="about-banner-pattern absolute inset-0 pointer-events-none" />
          <div className="about-banner-shape1 pointer-events-none" />
          <div className="about-banner-shape2 pointer-events-none" />
          <div className="about-banner-shape3 pointer-events-none" />
          <div className="absolute inset-0 bg-gradient-to-t from-bg2 via-bg2/30 to-transparent pointer-events-none" />

          {/* Top-Right Official Status Badge */}
          <div className="absolute top-3 right-3 sm:top-4 sm:right-4 z-10 pointer-events-none">
            <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] sm:text-xs font-semibold backdrop-blur-md bg-black/40 text-white/95 border border-white/20 shadow-xs">
              <span className="size-2 rounded-full bg-emerald-400 animate-pulse" aria-hidden="true" />
              DepEd ID: 406471 · Senior High School
            </span>
          </div>
        </div>

        {/* Hero Card Content */}
        <div className="about-hero-content px-4 sm:px-8 md:px-10 pb-7 sm:pb-9 pt-0 relative z-10">
          <div className="flex flex-col sm:flex-row items-center sm:items-start gap-4 sm:gap-6 text-center sm:text-left">
            {/* Straddling Institutional Seal */}
            <div className="about-logo-wrap shrink-0 -mt-10 sm:-mt-12 md:-mt-14 z-20">
              <div className="about-logo size-20 sm:size-24 md:size-28 rounded-full border-4 border-bg2 bg-bg2 overflow-hidden shadow-lg flex items-center justify-center ring-1 ring-border/80 p-1 transition-transform duration-300 hover:scale-105 hover:shadow-xl cursor-default">
                <img
                  src="/img/Excelsior_Seal_Logo.png"
                  alt="Excelsior Polytechnic Academy Official Seal"
                  className="w-full h-full object-contain"
                  onError={(e) => {
                    e.currentTarget.style.display = 'none';
                    const fallback = e.currentTarget.nextElementSibling as HTMLElement | null;
                    if (fallback) fallback.style.display = 'flex';
                  }}
                />
                <div
                  className="hidden w-full h-full items-center justify-center font-serif text-2xl font-bold text-white bg-gradient-to-br from-school-blue to-school-orange rounded-full"
                  aria-label="EPA Monogram"
                >
                  EPA
                </div>
              </div>
            </div>

            {/* School Title & Motto */}
            <div className="min-w-0 flex-1 space-y-3">
              <div className="space-y-1.5">
                <h1 className="about-hero-name font-serif text-2xl sm:text-3xl lg:text-4xl font-bold leading-tight tracking-tight">
                  Excelsior Polytechnic Academy
                </h1>
                <div>
                  <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs sm:text-sm font-serif italic text-accent bg-accent-glow/50 border border-accent/20 transition-all duration-200 hover:border-accent/40">
                    <RiSparklingLine className="size-3.5 text-school-orange shrink-0 animate-pulse" aria-hidden="true" />
                    « in avanti, in alto, sempre »
                    <span className="text-[11px] font-sans not-italic text-text3 hidden md:inline ml-1 font-normal">
                      (Forward, Upward, Always)
                    </span>
                  </span>
                </div>
              </div>

              {/* Tagline / Overview */}
              <p className="about-hero-tagline text-xs sm:text-sm md:text-base text-text2 max-w-4xl leading-relaxed text-pretty">
                Excelsior Polytechnic Academy, Inc. is a premier private educational institution in Masbate providing quality Senior High School and Basic Education programs. Built on a foundation of competence, character, and blended learning innovation.
              </p>

              {/* Trust Badges */}
              <div className="about-hero-badges flex flex-wrap gap-2 pt-1 justify-center sm:justify-start">
                <span className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-accent-glow text-accent border border-accent/20 transition-all duration-150 hover:scale-105">
                  <RiMapPin2Line className="size-3.5 shrink-0" aria-hidden="true" />
                  Cabitan &amp; Uson, Masbate
                </span>
                <span className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-green-bg text-green border border-green/20 transition-all duration-150 hover:scale-105">
                  <RiGraduationCapLine className="size-3.5 shrink-0" aria-hidden="true" />
                  DepEd Recognized SHS
                </span>
                <span className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-amber-bg text-amber border border-amber/20 transition-all duration-150 hover:scale-105">
                  <RiComputerLine className="size-3.5 shrink-0" aria-hidden="true" />
                  Blended Learning LMS
                </span>
                <span className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-bg3 text-text2 border border-border transition-all duration-150 hover:scale-105">
                  <RiAwardLine className="size-3.5 text-school-orange shrink-0" aria-hidden="true" />
                  Vision 2030 Benchmark
                </span>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* ============================================================ */}
      {/* 2. CAMPUSES & INSTITUTIONAL PROFILE                          */}
      {/* ============================================================ */}
      <section aria-labelledby="campuses-heading" className="about-stagger-2 space-y-4">
        <div className="flex items-center justify-between gap-4 flex-wrap">
          <div>
            <h2 id="campuses-heading" className="font-serif text-lg sm:text-xl md:text-2xl font-bold text-text">
              Campuses &amp; Academic Programs
            </h2>
            <p className="text-xs sm:text-sm text-text3">
              Serving Masbateño youth across dual campus locations and blended digital classrooms
            </p>
          </div>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
          {/* Main Campus: Cabitan */}
          <div className="about-interactive-card p-5 sm:p-6 rounded-2xl bg-bg2 border border-border hover:border-border2 shadow-xs flex flex-col justify-between space-y-4 group">
            <div className="space-y-3">
              <div className="flex items-start justify-between gap-3">
                <div className="size-10 sm:size-11 rounded-xl bg-school-blue/15 text-school-blue-light dark:text-blue-400 flex items-center justify-center shrink-0 transition-transform duration-200 group-hover:scale-110">
                  <RiBuilding4Line className="size-5 sm:size-6" aria-hidden="true" />
                </div>
                <span className="px-2.5 py-1 rounded-full text-[11px] font-semibold bg-accent-glow text-accent border border-accent/20">
                  Main Campus
                </span>
              </div>
              <div>
                <h3 className="font-bold text-base sm:text-lg text-text group-hover:text-accent transition-colors duration-150">
                  Cabitan Main Campus
                </h3>
                <p className="text-xs sm:text-sm text-text3 flex items-center gap-1.5 mt-0.5">
                  <RiMapPin2Line className="size-3.5 text-accent shrink-0" aria-hidden="true" />
                  Cabitan, Mandaon, Masbate, Philippines
                </p>
              </div>
              <p className="text-xs sm:text-sm text-text2 leading-relaxed">
                Primary institutional headquarters housing senior high school departments (STEM, ABM, HUMSS, TVL), basic education classrooms, science and computer laboratories, and central administrative offices.
              </p>
            </div>
            <div className="pt-2 border-t border-border/60 flex flex-wrap gap-1.5 text-[11px] text-text3 font-medium">
              <span className="px-2 py-0.5 rounded bg-bg3 border border-border/50">Senior High School</span>
              <span className="px-2 py-0.5 rounded bg-bg3 border border-border/50">Preschool</span>
              <span className="px-2 py-0.5 rounded bg-bg3 border border-border/50">Kindergarten</span>
              <span className="px-2 py-0.5 rounded bg-bg3 border border-border/50">Grade 1</span>
            </div>
          </div>

          {/* Extension Campus: Crossing */}
          <div className="about-interactive-card p-5 sm:p-6 rounded-2xl bg-bg2 border border-border hover:border-border2 shadow-xs flex flex-col justify-between space-y-4 group">
            <div className="space-y-3">
              <div className="flex items-start justify-between gap-3">
                <div className="size-10 sm:size-11 rounded-xl bg-school-orange/15 text-school-orange dark:text-amber-400 flex items-center justify-center shrink-0 transition-transform duration-200 group-hover:scale-110">
                  <RiBuilding4Line className="size-5 sm:size-6" aria-hidden="true" />
                </div>
                <span className="px-2.5 py-1 rounded-full text-[11px] font-semibold bg-amber-bg text-amber border border-amber/20">
                  Extension Campus
                </span>
              </div>
              <div>
                <h3 className="font-bold text-base sm:text-lg text-text group-hover:text-school-orange transition-colors duration-150">
                  Crossing Extension Campus
                </h3>
                <p className="text-xs sm:text-sm text-text3 flex items-center gap-1.5 mt-0.5">
                  <RiMapPin2Line className="size-3.5 text-school-orange dark:text-amber-400 shrink-0" aria-hidden="true" />
                  Crossing, Uson, Masbate, Philippines
                </p>
              </div>
              <p className="text-xs sm:text-sm text-text2 leading-relaxed">
                Strategic regional learning extension center extending educational access to Uson and nearby municipalities, offering modular consultations and digital learning access points.
              </p>
            </div>
            <div className="pt-2 border-t border-border/60 flex flex-wrap gap-1.5 text-[11px] text-text3 font-medium">
              <span className="px-2 py-0.5 rounded bg-bg3 border border-border/50">SHS Extension</span>
              <span className="px-2 py-0.5 rounded bg-bg3 border border-border/50">Learning Commons</span>
              <span className="px-2 py-0.5 rounded bg-bg3 border border-border/50">Modular Hub</span>
              <span className="px-2 py-0.5 rounded bg-bg3 border border-border/50">Blended Center</span>
            </div>
          </div>
        </div>
      </section>

      {/* ============================================================ */}
      {/* 3. VISION & MISSION                                          */}
      {/* ============================================================ */}
      <section aria-label="Vision and Mission" className="vm-grid about-stagger-3 grid grid-cols-1 md:grid-cols-2 gap-5 sm:gap-6 lg:gap-8">
        {/* Vision Card */}
        <div className="vm-card about-interactive-card p-6 sm:p-8 rounded-2xl sm:rounded-3xl bg-bg2 border border-border border-t-4 border-t-school-blue dark:border-t-blue-400 relative overflow-hidden shadow-xs group">
          <div
            className="vm-card-glow absolute -top-10 -right-10 size-36 rounded-full opacity-15 dark:opacity-20 pointer-events-none transition-transform duration-500 group-hover:scale-125"
            style={{ background: 'var(--school-blue)' }}
          />
          <div className="flex items-center justify-between mb-4">
            <div className="vm-card-icon size-12 sm:size-14 rounded-xl sm:rounded-2xl bg-school-blue/15 text-school-blue-light dark:text-blue-400 flex items-center justify-center shrink-0 transition-transform duration-200 group-hover:scale-110">
              <RiEyeLine className="size-6 sm:size-7" aria-hidden="true" />
            </div>
            <span className="px-2.5 py-1 rounded-full text-[11px] font-bold tracking-wider uppercase bg-accent-glow text-accent border border-accent/20">
              Target 2030
            </span>
          </div>
          <div className="vm-card-label text-xs font-bold uppercase tracking-wider text-school-blue-light dark:text-blue-400 mb-2">
            Our Vision
          </div>
          <div className="vm-card-text text-sm sm:text-base text-text2 leading-relaxed">
            Excelsior Polytechnic Academy is a leading educational institution that produces innovative, competent and resilient graduates for social development among Masbateños by <strong className="text-text font-bold">2030</strong>.
          </div>
          <div className="mt-4 pt-4 border-t border-border/60 flex flex-wrap gap-2 text-xs font-medium text-text3">
            <span className="inline-flex items-center gap-1">
              <span className="size-1.5 rounded-full bg-accent" /> Innovative Graduates
            </span>
            <span className="inline-flex items-center gap-1">
              <span className="size-1.5 rounded-full bg-green" /> Competence &amp; Character
            </span>
            <span className="inline-flex items-center gap-1">
              <span className="size-1.5 rounded-full bg-amber" /> Social Development
            </span>
          </div>
        </div>

        {/* Mission Card */}
        <div className="vm-card about-interactive-card p-6 sm:p-8 rounded-2xl sm:rounded-3xl bg-bg2 border border-border border-t-4 border-t-school-orange dark:border-t-amber-400 relative overflow-hidden shadow-xs group">
          <div
            className="vm-card-glow absolute -top-10 -right-10 size-36 rounded-full opacity-15 dark:opacity-20 pointer-events-none transition-transform duration-500 group-hover:scale-125"
            style={{ background: 'var(--school-orange)' }}
          />
          <div className="flex items-center justify-between mb-4">
            <div className="vm-card-icon size-12 sm:size-14 rounded-xl sm:rounded-2xl bg-school-orange/15 text-school-orange dark:text-amber-400 flex items-center justify-center shrink-0 transition-transform duration-200 group-hover:scale-110">
              <RiTargetLine className="size-6 sm:size-7" aria-hidden="true" />
            </div>
            <span className="px-2.5 py-1 rounded-full text-[11px] font-bold tracking-wider uppercase bg-amber-bg text-amber border border-amber/20">
              Lifelong Learners
            </span>
          </div>
          <div className="vm-card-label text-xs font-bold uppercase tracking-wider text-school-orange dark:text-amber-400 mb-2">
            Our Mission
          </div>
          <div className="vm-card-text text-sm sm:text-base text-text2 leading-relaxed">
            Excelsior Polytechnic Academy commits in providing dynamic and relevant educational experiences in a <strong className="text-text font-bold">learner-friendly, gender-sensitive, safe and motivating environment</strong> for optimum development of life-long learners through a competency-based blended learning approach.
          </div>
          <div className="mt-4 pt-4 border-t border-border/60 flex flex-wrap gap-2 text-xs font-medium text-text3">
            <span className="inline-flex items-center gap-1">
              <span className="size-1.5 rounded-full bg-school-orange dark:bg-amber-400" /> Safe &amp; Gender-Sensitive
            </span>
            <span className="inline-flex items-center gap-1">
              <span className="size-1.5 rounded-full bg-accent" /> Competency-Based
            </span>
            <span className="inline-flex items-center gap-1">
              <span className="size-1.5 rounded-full bg-green" /> Blended Learning
            </span>
          </div>
        </div>
      </section>

      {/* ============================================================ */}
      {/* 4. CORE VALUES e-CORE                                        */}
      {/* ============================================================ */}
      <section aria-labelledby="core-values-heading" className="core-values-section about-stagger-4 p-6 sm:p-8 md:p-10 rounded-2xl sm:rounded-3xl bg-bg2 border border-border shadow-xs">
        <div className="core-values-header flex items-center justify-between gap-4 mb-6 sm:mb-8 flex-wrap">
          <div>
            <h2 id="core-values-heading" className="section-title font-serif text-xl sm:text-2xl lg:text-3xl font-bold text-text mb-1">
              Core Values
            </h2>
            <div className="core-values-sub text-xs sm:text-sm text-text2">
              The foundational pillars guiding every student, faculty member, and administrator
            </div>
          </div>
          <div className="core-values-badge font-serif text-3xl sm:text-4xl lg:text-5xl font-bold">
            e-CORE
          </div>
        </div>

        <div className="core-values-grid grid grid-cols-1 md:grid-cols-3 gap-5 sm:gap-6">
          {/* Excellence */}
          <div className="core-value-card about-interactive-card p-6 sm:p-7 rounded-2xl bg-bg3 border border-border text-center relative overflow-hidden shadow-xs group">
            <div
              className="core-value-card-glow absolute -top-8 left-1/2 -translate-x-1/2 size-28 rounded-full opacity-10 pointer-events-none transition-transform duration-500 group-hover:scale-150"
              style={{ background: 'var(--school-blue)' }}
            />
            {/* Watermark Monogram */}
            <div
              className="absolute -top-3 -right-2 font-serif text-7xl font-black text-text/5 select-none pointer-events-none transition-all duration-300 group-hover:scale-110 group-hover:text-text/10"
              aria-hidden="true"
            >
              E
            </div>

            <div className="size-11 sm:size-12 mx-auto rounded-xl bg-school-blue/15 text-school-blue-light dark:text-blue-400 flex items-center justify-center mb-3 transition-transform duration-200 group-hover:scale-115">
              <RiShieldStarLine className="size-5 sm:size-6" aria-hidden="true" />
            </div>

            <div className="core-value-letter font-serif text-4xl sm:text-5xl font-bold mb-2 leading-none bg-gradient-to-br from-school-blue to-school-blue-light dark:from-blue-400 dark:to-blue-300 bg-clip-text text-transparent">
              E
            </div>
            <div className="core-value-word font-bold text-base sm:text-lg text-text mb-2 tracking-tight group-hover:text-accent transition-colors duration-150">
              Excellence
            </div>
            <div className="core-value-desc text-xs sm:text-sm text-text3 leading-relaxed">
              Striving for the highest standards in every endeavor &mdash; academically, personally, and as a community.
            </div>
            <div className="mt-4 pt-3 border-t border-border/50 text-[11px] font-semibold text-accent uppercase tracking-wider">
              Scholarly Merit &amp; Integrity
            </div>
          </div>

          {/* Competence */}
          <div className="core-value-card about-interactive-card p-6 sm:p-7 rounded-2xl bg-bg3 border border-border text-center relative overflow-hidden shadow-xs group">
            <div
              className="core-value-card-glow absolute -top-8 left-1/2 -translate-x-1/2 size-28 rounded-full opacity-10 pointer-events-none transition-transform duration-500 group-hover:scale-150"
              style={{ background: 'var(--school-blue-light)' }}
            />
            {/* Watermark Monogram */}
            <div
              className="absolute -top-3 -right-2 font-serif text-7xl font-black text-text/5 select-none pointer-events-none transition-all duration-300 group-hover:scale-110 group-hover:text-text/10"
              aria-hidden="true"
            >
              C
            </div>

            <div className="size-11 sm:size-12 mx-auto rounded-xl bg-accent-glow text-accent flex items-center justify-center mb-3 transition-transform duration-200 group-hover:scale-115">
              <RiCompass3Line className="size-5 sm:size-6" aria-hidden="true" />
            </div>

            <div className="core-value-letter font-serif text-4xl sm:text-5xl font-bold mb-2 leading-none bg-gradient-to-br from-school-blue-light to-blue-600 dark:from-cyan-400 dark:to-blue-400 bg-clip-text text-transparent">
              C
            </div>
            <div className="core-value-word font-bold text-base sm:text-lg text-text mb-2 tracking-tight group-hover:text-accent transition-colors duration-150">
              Competence
            </div>
            <div className="core-value-desc text-xs sm:text-sm text-text3 leading-relaxed">
              Equipping learners with practical knowledge, skills, and confidence to thrive in a rapidly evolving world.
            </div>
            <div className="mt-4 pt-3 border-t border-border/50 text-[11px] font-semibold text-green uppercase tracking-wider">
              Applied Skills &amp; Mastery
            </div>
          </div>

          {/* Resilience */}
          <div className="core-value-card about-interactive-card p-6 sm:p-7 rounded-2xl bg-bg3 border border-border text-center relative overflow-hidden shadow-xs group">
            <div
              className="core-value-card-glow absolute -top-8 left-1/2 -translate-x-1/2 size-28 rounded-full opacity-10 pointer-events-none transition-transform duration-500 group-hover:scale-150"
              style={{ background: 'var(--school-orange)' }}
            />
            {/* Watermark Monogram */}
            <div
              className="absolute -top-3 -right-2 font-serif text-7xl font-black text-text/5 select-none pointer-events-none transition-all duration-300 group-hover:scale-110 group-hover:text-text/10"
              aria-hidden="true"
            >
              R
            </div>

            <div className="size-11 sm:size-12 mx-auto rounded-xl bg-school-orange/15 text-school-orange dark:text-amber-400 flex items-center justify-center mb-3 transition-transform duration-200 group-hover:scale-115">
              <RiSeedlingLine className="size-5 sm:size-6" aria-hidden="true" />
            </div>

            <div className="core-value-letter font-serif text-4xl sm:text-5xl font-bold mb-2 leading-none bg-gradient-to-br from-school-orange to-school-orange-light dark:from-amber-400 dark:to-orange-400 bg-clip-text text-transparent">
              R
            </div>
            <div className="core-value-word font-bold text-base sm:text-lg text-text mb-2 tracking-tight group-hover:text-school-orange transition-colors duration-150">
              Resilience
            </div>
            <div className="core-value-desc text-xs sm:text-sm text-text3 leading-relaxed">
              Building the strength and determination to overcome challenges and emerge stronger every time.
            </div>
            <div className="mt-4 pt-3 border-t border-border/50 text-[11px] font-semibold text-amber uppercase tracking-wider">
              Grit &amp; Adaptability
            </div>
          </div>
        </div>
      </section>

      {/* ============================================================ */}
      {/* 5. EXCELEARN LMS PLATFORM ADVANTAGES                         */}
      {/* ============================================================ */}
      <section aria-labelledby="platform-heading" className="about-stagger-5 space-y-4">
        <div>
          <h2 id="platform-heading" className="font-serif text-lg sm:text-xl md:text-2xl font-bold text-text">
            ExceLearn Blended Learning Platform
          </h2>
          <p className="text-xs sm:text-sm text-text3">
            Digital education engineered for accessibility, high performance, and continuous learner engagement
          </p>
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <div className="about-interactive-card p-4 sm:p-5 rounded-2xl bg-bg2 border border-border shadow-xs space-y-2 group">
            <div className="size-9 rounded-lg bg-accent-glow text-accent flex items-center justify-center transition-transform duration-200 group-hover:scale-110">
              <RiTimeLine className="size-4.5" aria-hidden="true" />
            </div>
            <h3 className="font-bold text-sm text-text group-hover:text-accent transition-colors duration-150">Anytime, Anywhere</h3>
            <p className="text-xs text-text3 leading-relaxed">
              Access coursework, download digital modules, and track assignments seamlessly across desktop and mobile devices.
            </p>
          </div>

          <div className="about-interactive-card p-4 sm:p-5 rounded-2xl bg-bg2 border border-border shadow-xs space-y-2 group">
            <div className="size-9 rounded-lg bg-green-bg text-green flex items-center justify-center transition-transform duration-200 group-hover:scale-110">
              <RiCheckboxCircleLine className="size-4.5" aria-hidden="true" />
            </div>
            <h3 className="font-bold text-sm text-text group-hover:text-green transition-colors duration-150">DepEd Performance Rubrics</h3>
            <p className="text-xs text-text3 leading-relaxed">
              Transparent, competency-based criteria for written tasks and hands-on laboratory outputs with instant feedback.
            </p>
          </div>

          <div className="about-interactive-card p-4 sm:p-5 rounded-2xl bg-bg2 border border-border shadow-xs space-y-2 group">
            <div className="size-9 rounded-lg bg-school-blue/15 text-school-blue-light dark:text-blue-400 flex items-center justify-center transition-transform duration-200 group-hover:scale-110">
              <RiBuilding4Line className="size-4.5" aria-hidden="true" />
            </div>
            <h3 className="font-bold text-sm text-text group-hover:text-school-blue-light dark:group-hover:text-blue-400 transition-colors duration-150">Dual-Campus Synchronization</h3>
            <p className="text-xs text-text3 leading-relaxed">
              Unifies Cabitan and Uson campuses under one academic calendar, standardized grading, and unified announcements.
            </p>
          </div>

          <div className="about-interactive-card p-4 sm:p-5 rounded-2xl bg-bg2 border border-border shadow-xs space-y-2 group">
            <div className="size-9 rounded-lg bg-amber-bg text-amber flex items-center justify-center transition-transform duration-200 group-hover:scale-110">
              <RiHeartPulseLine className="size-4.5" aria-hidden="true" />
            </div>
            <h3 className="font-bold text-sm text-text group-hover:text-amber transition-colors duration-150">Safe &amp; Motivating Portal</h3>
            <p className="text-xs text-text3 leading-relaxed">
              A respectful, gender-sensitive digital space providing positive reinforcement and lifelong learning development.
            </p>
          </div>
        </div>
      </section>

      {/* ============================================================ */}
      {/* 6. INSTITUTIONAL FOOTER & QUICK NAVIGATION                   */}
      {/* ============================================================ */}
      <section
        aria-label="Institutional Summary and Quick Links"
        className="about-footer-card about-stagger-6 p-6 sm:p-10 md:p-12 rounded-2xl sm:rounded-3xl bg-bg2 border border-border text-center relative overflow-hidden shadow-xs"
      >
        <div className="about-footer-card-bg absolute inset-0 pointer-events-none" />
        <div className="about-footer-card-content relative z-10 max-w-3xl mx-auto space-y-4 sm:space-y-5">
          <div className="about-footer-logo font-serif text-2xl sm:text-3xl lg:text-4xl font-bold">
            Excelsior Polytechnic Academy
          </div>
          <div className="about-footer-tagline font-serif italic text-sm sm:text-base text-text3">
            &ldquo;Learn Better. Anytime, Anywhere.&rdquo;
          </div>
          <p className="about-footer-text text-xs sm:text-sm text-text2 leading-relaxed max-w-2xl mx-auto text-pretty">
            {role === 'student' &&
              'Excelsior Polytechnic Academy is dedicated to shaping the future of Masbateño youth through innovative education, blended learning technology, and unwavering commitment to the e-CORE values of Excellence, Competence, and Resilience.'}
            {role === 'teacher' &&
              'Empowering educators with digital pedagogical tools, streamlined gradebook management, and dynamic classroom experiences to guide every Masbateño student toward academic excellence.'}
            {role === 'admin' &&
              'Comprehensive institutional governance and academic management connecting Cabitan and Uson campuses through data-driven insight, student tracking, and DepEd standards compliance.'}
          </p>

          {/* Quick Portal Navigation Links */}
          {onTabChange && roleNavItems.length > 0 && (
            <div className="pt-2 sm:pt-4">
              <div className="text-[11px] font-mono uppercase tracking-wider text-text3 mb-3">
                Quick Navigation · {role.charAt(0).toUpperCase() + role.slice(1)} Portal
              </div>
              <div className="flex flex-wrap items-center justify-center gap-2">
                {roleNavItems.map((item) => {
                  const Icon = item.icon;
                  return (
                    <button
                      key={item.id}
                      type="button"
                      onClick={() => onTabChange(item.id)}
                      className="px-3 py-1.5 rounded-xl bg-bg3 hover:bg-bg4 border border-border text-xs font-semibold text-text flex items-center gap-1.5 transition-all cursor-pointer shadow-2xs hover:border-accent/40"
                    >
                      <Icon className="size-3.5 text-accent" />
                      <span>{item.label}</span>
                      <RiArrowRightLine className="size-3 text-text3" />
                    </button>
                  );
                })}
              </div>
            </div>
          )}
        </div>
      </section>

      {/* Copyright Line */}
      <footer className="text-center text-xs text-text3 pt-1 space-y-1">
        <div>
          ExceLearn LMS v2.2 · Excelsior Polytechnic Academy © JBH &amp; Koala Bonjing: 2025–2026
        </div>
        <div className="text-[11px] text-text3/70">
          Cabitan, Mandaon &amp; Crossing, Uson, Masbate, Philippines · DepEd Recognized
        </div>
      </footer>
    </div>
  );
}
