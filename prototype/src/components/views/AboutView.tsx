import type { Role } from '@/types';
import {
  RiMapPin2Line,
  RiGraduationCapLine,
  RiComputerLine,
  RiEyeLine,
  RiTargetLine,
} from '@remixicon/react';

export interface AboutViewProps {
  role?: Role;
  onTabChange?: (tabId: string) => void;
}

export function AboutView({ role = 'student' }: AboutViewProps) {
  return (
    <div className="space-y-8 sm:space-y-10 w-full max-w-6xl mx-auto relative pb-16">
      {/* ============================================================ */}
      {/* 1. HERO SECTION                                              */}
      {/* ============================================================ */}
      <div className="about-hero fade-in border border-border rounded-2xl sm:rounded-3xl bg-bg2 overflow-hidden shadow-xs">
        <div className="about-banner relative h-56 sm:h-64 md:h-72 overflow-hidden">
          <div className="about-banner-bg absolute inset-0">
            <img
              src="/img/Excelsior_Seal_Logo_cover.jpg"
              alt="EPA Logo Cover"
              className="w-full h-full object-cover object-center"
              onError={(e) => {
                e.currentTarget.style.display = 'none';
              }}
            />
          </div>
          <div className="about-banner-pattern absolute inset-0 pointer-events-none" />
          <div className="about-banner-shape1 pointer-events-none" />
          <div className="about-banner-shape2 pointer-events-none" />
          <div className="about-banner-shape3 pointer-events-none" />
        </div>

        <div className="about-hero-content px-6 sm:px-10 py-6 sm:py-8 relative z-10">
          <div className="flex flex-col sm:flex-row items-center sm:items-start gap-4 sm:gap-6 text-center sm:text-left">
            <div className="about-logo size-20 sm:size-24 rounded-full border-4 border-bg2 bg-bg2 overflow-hidden shadow-md flex items-center justify-center shrink-0 ring-2 ring-border/60">
              <img
                src="/img/Excelsior_Seal_Logo.png"
                alt="Excelsior Polytechnic Academy"
                className="w-full h-full object-contain"
                onError={(e) => {
                  e.currentTarget.style.display = 'none';
                  const fallback = e.currentTarget.nextElementSibling as HTMLElement | null;
                  if (fallback) fallback.style.display = 'flex';
                }}
              />
              <div
                className="hidden w-full h-full items-center justify-center font-serif text-2xl font-bold text-white bg-gradient-to-br from-school-blue to-school-orange"
              >
                EPA
              </div>
            </div>

            <div className="min-w-0 flex-1 space-y-3.5">
              <div>
                <h1
                  className="about-hero-name font-serif text-2xl sm:text-3xl lg:text-4xl font-bold leading-tight inline-block"
                  style={{
                    background: 'linear-gradient(135deg, #1e3c72 0%, #2a5298 42%, #f7971e 100%)',
                    WebkitBackgroundClip: 'text',
                    backgroundClip: 'text',
                    WebkitTextFillColor: 'transparent',
                    color: 'transparent',
                  }}
                >
                  Excelsior Polytechnic Academy
                </h1>
                <p className="text-xs sm:text-sm font-serif italic text-text3 tracking-wide mt-1">
                  « in avanti, in alto, sempre »
                </p>
              </div>

              <p className="about-hero-tagline text-sm sm:text-base text-text2 max-w-4xl leading-relaxed">
                Excelsior Polytechnic Academy, Inc. is a private educational institution offering Senior High School,
                Preschool, Kindergarten, and Grade 1, with campuses in Cabitan, Mandaon, Masbate and in Crossing, Uson, Masbate. Visit us!
              </p>

              <div className="about-hero-badges flex flex-wrap gap-2.5 pt-1 justify-center sm:justify-start">
                <span className="badge badge-blue inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-accent-glow text-accent border border-accent/20">
                  <RiMapPin2Line className="size-3.5" aria-hidden="true" />
                  Cabitan, Mandaon
                </span>
                <span className="badge badge-green inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-green-bg text-green border border-green/20">
                  <RiGraduationCapLine className="size-3.5" aria-hidden="true" />
                  DepEd Recognized
                </span>
                <span className="badge badge-amber inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-amber-bg text-amber border border-amber/20">
                  <RiComputerLine className="size-3.5" aria-hidden="true" />
                  Blended Learning
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* ============================================================ */}
      {/* 2. VISION & MISSION                                          */}
      {/* ============================================================ */}
      <div className="vm-grid grid grid-cols-1 md:grid-cols-2 gap-6 sm:gap-8">
        {/* Vision Card */}
        <div className="vm-card p-7 sm:p-9 rounded-2xl sm:rounded-3xl bg-bg2 border border-border relative overflow-hidden transition-all duration-200 hover:-translate-y-0.5 hover:border-school-blue-light/50 shadow-xs">
          <div
            className="vm-card-glow absolute -top-10 -right-10 size-36 rounded-full opacity-15 pointer-events-none"
            style={{ background: 'var(--school-blue)' }}
          />
          <div className="vm-card-icon size-12 sm:size-14 rounded-xl sm:rounded-2xl bg-school-blue/15 text-school-blue-light flex items-center justify-center mb-5">
            <RiEyeLine className="size-6 sm:size-7" aria-hidden="true" />
          </div>
          <div className="vm-card-label text-xs font-bold uppercase tracking-wider text-school-blue-light mb-2.5">
            Our Vision
          </div>
          <div className="vm-card-text text-sm sm:text-base text-text2 leading-relaxed">
            Excelsior Polytechnic Academy is a leading educational institution that produces innovative, competent and resilient graduates for social development among Masbateños by <strong className="text-text font-bold">2030</strong>.
          </div>
        </div>

        {/* Mission Card */}
        <div className="vm-card p-7 sm:p-9 rounded-2xl sm:rounded-3xl bg-bg2 border border-border relative overflow-hidden transition-all duration-200 hover:-translate-y-0.5 hover:border-school-orange/50 shadow-xs">
          <div
            className="vm-card-glow absolute -top-10 -right-10 size-36 rounded-full opacity-15 pointer-events-none"
            style={{ background: 'var(--school-orange)' }}
          />
          <div className="vm-card-icon size-12 sm:size-14 rounded-xl sm:rounded-2xl bg-school-orange/15 text-school-orange flex items-center justify-center mb-5">
            <RiTargetLine className="size-6 sm:size-7" aria-hidden="true" />
          </div>
          <div className="vm-card-label text-xs font-bold uppercase tracking-wider text-school-orange mb-2.5">
            Our Mission
          </div>
          <div className="vm-card-text text-sm sm:text-base text-text2 leading-relaxed">
            Excelsior Polytechnic Academy commits in providing dynamic and relevant educational experiences in a <strong className="text-text font-bold">learner-friendly, gender-sensitive, safe and motivating environment</strong> for optimum development of life-long learners through a competency-based blended learning approach.
          </div>
        </div>
      </div>

      {/* ============================================================ */}
      {/* 3. CORE VALUES e-CORE                                        */}
      {/* ============================================================ */}
      <div className="core-values-section p-7 sm:p-10 rounded-2xl sm:rounded-3xl bg-bg2 border border-border shadow-xs">
        <div className="core-values-header flex items-center justify-between gap-4 mb-8 flex-wrap">
          <div>
            <h2 className="section-title font-serif text-xl sm:text-2xl lg:text-3xl font-bold text-text mb-1">
              Core Values
            </h2>
            <div className="core-values-sub text-xs sm:text-sm text-text2">
              The foundation of everything we do at Excelsior Polytechnic Academy
            </div>
          </div>
          <div className="core-values-badge font-serif text-3xl sm:text-4xl lg:text-5xl font-bold bg-gradient-to-r from-school-blue via-school-blue-light to-school-orange bg-clip-text text-transparent">
            e-CORE
          </div>
        </div>

        <div className="core-values-grid grid grid-cols-1 md:grid-cols-3 gap-6 sm:gap-8">
          {/* Excellence */}
          <div className="core-value-card p-7 sm:p-8 rounded-2xl bg-bg3 border border-border text-center relative overflow-hidden transition-all duration-200 hover:-translate-y-1 hover:border-border2 shadow-xs">
            <div
              className="core-value-card-glow absolute -top-8 left-1/2 -translate-x-1/2 size-28 rounded-full opacity-10 pointer-events-none"
              style={{ background: 'var(--school-blue)' }}
            />
            <div
              className="core-value-letter font-serif text-5xl sm:text-6xl font-bold mb-3 leading-none"
              style={{
                background: 'linear-gradient(135deg, var(--school-blue), var(--school-blue-light))',
                WebkitBackgroundClip: 'text',
                WebkitTextFillColor: 'transparent',
              }}
            >
              E
            </div>
            <div className="core-value-word font-bold text-lg sm:text-xl text-text mb-2.5 tracking-tight">
              Excellence
            </div>
            <div className="core-value-desc text-xs sm:text-sm text-text3 leading-relaxed">
              Striving for the highest standards in every endeavor &mdash; academically, personally, and as a community.
            </div>
          </div>

          {/* Competence */}
          <div className="core-value-card p-7 sm:p-8 rounded-2xl bg-bg3 border border-border text-center relative overflow-hidden transition-all duration-200 hover:-translate-y-1 hover:border-border2 shadow-xs">
            <div
              className="core-value-card-glow absolute -top-8 left-1/2 -translate-x-1/2 size-28 rounded-full opacity-10 pointer-events-none"
              style={{ background: 'var(--school-blue-light)' }}
            />
            <div
              className="core-value-letter font-serif text-5xl sm:text-6xl font-bold mb-3 leading-none"
              style={{
                background: 'linear-gradient(135deg, var(--school-blue-light), #4a7fd4)',
                WebkitBackgroundClip: 'text',
                WebkitTextFillColor: 'transparent',
              }}
            >
              C
            </div>
            <div className="core-value-word font-bold text-lg sm:text-xl text-text mb-2.5 tracking-tight">
              Competence
            </div>
            <div className="core-value-desc text-xs sm:text-sm text-text3 leading-relaxed">
              Equipping learners with the knowledge, skills, and confidence to thrive in a rapidly evolving world.
            </div>
          </div>

          {/* Resilience */}
          <div className="core-value-card p-7 sm:p-8 rounded-2xl bg-bg3 border border-border text-center relative overflow-hidden transition-all duration-200 hover:-translate-y-1 hover:border-border2 shadow-xs">
            <div
              className="core-value-card-glow absolute -top-8 left-1/2 -translate-x-1/2 size-28 rounded-full opacity-10 pointer-events-none"
              style={{ background: 'var(--school-orange)' }}
            />
            <div
              className="core-value-letter font-serif text-5xl sm:text-6xl font-bold mb-3 leading-none"
              style={{
                background: 'linear-gradient(135deg, var(--school-orange), var(--school-orange-light))',
                WebkitBackgroundClip: 'text',
                WebkitTextFillColor: 'transparent',
              }}
            >
              R
            </div>
            <div className="core-value-word font-bold text-lg sm:text-xl text-text mb-2.5 tracking-tight">
              Resilience
            </div>
            <div className="core-value-desc text-xs sm:text-sm text-text3 leading-relaxed">
              Building the strength and determination to overcome challenges and emerge stronger every time.
            </div>
          </div>
        </div>
      </div>

      {/* ============================================================ */}
      {/* 4. FOOTER CARD                                               */}
      {/* ============================================================ */}
      <div className="about-footer-card p-8 sm:p-12 rounded-2xl sm:rounded-3xl bg-bg2 border border-border text-center relative overflow-hidden shadow-xs">
        <div className="about-footer-card-bg absolute inset-0 pointer-events-none" />
        <div className="about-footer-card-content relative z-10 max-w-3xl mx-auto space-y-4">
          <div className="about-footer-logo font-serif text-2xl sm:text-3xl lg:text-4xl font-bold bg-gradient-to-r from-school-blue via-school-blue-light to-school-orange bg-clip-text text-transparent">
            Excelsior Polytechnic Academy
          </div>
          <div className="about-footer-tagline font-serif italic text-sm sm:text-base text-text3">
            &ldquo;Learn Better. Anytime, Anywhere.&rdquo;
          </div>
          <div className="about-footer-text text-xs sm:text-sm text-text2 leading-relaxed">
            {role === 'student'
              ? 'Excelsior Polytechnic Academy is dedicated to shaping the future of Masbateño youth through innovative education, blended learning technology, and unwavering commitment to the e-CORE values of Excellence, Competence, and Resilience.'
              : 'Dedicated to shaping the future of Masbateño youth through innovative education, blended learning technology, and unwavering commitment to the e-CORE values of Excellence, Competence, and Resilience.'}
          </div>
        </div>
      </div>

      {/* Copyright Line */}
      <div className="text-center text-xs text-text3 pt-2">
        ExceLearn LMS v2.2 · Excelsior Polytechnic Academy © JBH & Koala Bonjing: 2025–2026
      </div>
    </div>
  );
}
