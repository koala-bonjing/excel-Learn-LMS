import React, { useState, useMemo } from 'react';
import {
  RiBarChartLine,
  RiTrophyLine,
  RiStarLine,
  RiCheckLine,
  RiCloseLine,
  RiSearchLine,
  RiPrinterLine,
  RiDownload2Line,
  RiGraduationCapLine,
  RiUserLine,
  RiArrowRightLine,
  RiArrowLeftSLine,
  RiArrowRightSLine,
  RiInformationLine,
  RiFileList3Line,
} from '@remixicon/react';
import {
  STUDENT_REPORT_PROFILE,
  FIRST_SEMESTER_GRADES,
  SECOND_SEMESTER_GRADES,
  OBSERVED_CORE_VALUES,
  MONTHLY_ATTENDANCE_RECORD,
} from '../../data/gradesData';
import type { SubjectGradeRecord } from '../../data/gradesData';

interface StudentGradesViewProps {
  onTabChange?: (tab: string) => void;
}

// Letter Grade helper (matching student_grades.php)
function getLetterGrade(pct: number): string {
  if (pct >= 97) return 'A+';
  if (pct >= 93) return 'A';
  if (pct >= 90) return 'A-';
  if (pct >= 87) return 'B+';
  if (pct >= 83) return 'B';
  if (pct >= 80) return 'B-';
  if (pct >= 77) return 'C+';
  if (pct >= 73) return 'C';
  if (pct >= 70) return 'C-';
  if (pct >= 67) return 'D+';
  if (pct >= 63) return 'D';
  if (pct >= 60) return 'D-';
  return 'F';
}

function getGradeColor(pct: number): string {
  if (pct >= 90) return 'text-green';
  if (pct >= 80) return 'text-accent';
  if (pct >= 75) return 'text-amber';
  return 'text-red';
}

function getGradeBg(pct: number): string {
  if (pct >= 90) return 'bg-green-bg text-green border-green/20';
  if (pct >= 80) return 'bg-accent/10 text-accent border-accent/20';
  if (pct >= 75) return 'bg-amber-bg text-amber border-amber/20';
  return 'bg-red-bg text-red border-red/20';
}

function getProgressBarClass(pct: number): string {
  if (pct >= 90) return 'bg-green';
  if (pct >= 80) return 'bg-accent';
  if (pct >= 75) return 'bg-amber';
  return 'bg-red';
}

export const StudentGradesView: React.FC<StudentGradesViewProps> = () => {
  // View mode: 'breakdown' (PHP interactive analysis) vs 'reportCard' (Physical DepEd SF9 / Form 138)
  const [viewMode, setViewMode] = useState<'breakdown' | 'reportCard'>('breakdown');

  // Filter and sort state
  const [selectedSemester, setSelectedSemester] = useState<'all' | '1' | '2'>('all');
  const [selectedCategory, setSelectedCategory] = useState<'all' | 'core' | 'applied' | 'specialized'>('all');
  const [searchQuery, setSearchQuery] = useState('');
  const [sortBy, setSortBy] = useState<'default' | 'score-desc' | 'score-asc' | 'name-asc'>('default');

  // Pagination state
  const [currentPage, setCurrentPage] = useState(1);
  const [pageSize, setPageSize] = useState(7);

  // Modal detail state (matching student_grades.php openModal)
  const [activeModalSubject, setActiveModalSubject] = useState<SubjectGradeRecord | null>(null);
  const [showToast, setShowToast] = useState<string | null>(null);

  // Combine grades based on semester selection
  const allGradesPool = useMemo(() => {
    if (selectedSemester === '1') return FIRST_SEMESTER_GRADES;
    if (selectedSemester === '2') return SECOND_SEMESTER_GRADES;
    return [...FIRST_SEMESTER_GRADES, ...SECOND_SEMESTER_GRADES];
  }, [selectedSemester]);

  // Overall KPI statistics
  const stats = useMemo(() => {
    const list = allGradesPool;
    const totalCount = list.length;
    if (totalCount === 0) {
      return { avg: 0, highest: 0, above90: 0, passingCount: 0, passRate: 0, lowest: 0, lowestLabel: '' };
    }
    const sum = list.reduce((acc, g) => acc + g.semFinal, 0);
    const avg = Number((sum / totalCount).toFixed(1));
    const highest = Math.max(...list.map((g) => g.semFinal));
    const above90 = list.filter((g) => g.semFinal >= 90).length;
    const passingCount = list.filter((g) => g.semFinal >= 75).length;
    const passRate = Math.round((passingCount / totalCount) * 100);

    const lowestRec = list.reduce((lowest, curr) => (curr.semFinal < lowest.semFinal ? curr : lowest), list[0]);

    return {
      avg,
      highest,
      above90,
      passingCount,
      passRate,
      lowest: lowestRec.semFinal,
      lowestLabel: lowestRec.title,
    };
  }, [allGradesPool]);

  // Filtered & sorted grades for the interactive breakdown table
  const filteredGrades = useMemo(() => {
    return allGradesPool
      .filter((g) => {
        // Search query
        const query = searchQuery.toLowerCase().trim();
        const matchesSearch =
          !query ||
          g.title.toLowerCase().includes(query) ||
          g.code.toLowerCase().includes(query) ||
          g.instructor.toLowerCase().includes(query);

        // Category filter
        const matchesCat = selectedCategory === 'all' || g.category === selectedCategory;

        return matchesSearch && matchesCat;
      })
      .sort((a, b) => {
        if (sortBy === 'score-desc') return b.semFinal - a.semFinal;
        if (sortBy === 'score-asc') return a.semFinal - b.semFinal;
        if (sortBy === 'name-asc') return a.title.localeCompare(b.title);
        // Default: Keep canonical curriculum order
        return 0;
      });
  }, [allGradesPool, searchQuery, selectedCategory, sortBy]);

  // Pagination calculation
  const totalItems = filteredGrades.length;
  const totalPages = Math.max(1, Math.ceil(totalItems / pageSize));
  const safePage = Math.min(currentPage, totalPages);
  const startIndex = totalItems === 0 ? 0 : (safePage - 1) * pageSize;
  const endIndex = Math.min(startIndex + pageSize, totalItems);

  const paginatedGrades = useMemo(() => {
    if (totalItems === 0) return [];
    return filteredGrades.slice(startIndex, endIndex);
  }, [filteredGrades, startIndex, endIndex, totalItems]);

  const handlePrint = () => {
    window.print();
  };

  const handleDownloadPdf = () => {
    setShowToast('Generating official DepEd Form 138-SHS PDF transcript with digital dry seal...');
    setTimeout(() => {
      setShowToast(null);
    }, 3500);
  };

  return (
    <div className="space-y-6 animate-in fade-in duration-200">
      {/* ============================================================ */}
      {/* 1. TOP HEADER & VIEW MODE SELECTOR                           */}
      {/* ============================================================ */}
      <div className="flex flex-col lg:flex-row lg:items-center justify-between gap-4 border-b border-border/70 pb-4">
        <div>
          <div className="flex items-center gap-2">
            <span className="px-2.5 py-0.5 rounded-full text-[11px] font-mono font-bold bg-accent/10 text-accent border border-accent/20">
              DepEd Form 138 / SF9-SHS
            </span>
            <span className="text-text3 text-xs hidden sm:inline">·</span>
            <span className="text-xs text-text3 font-medium hidden sm:inline">
              Senior High School Department · K to 12 Basic Education
            </span>
          </div>
          <h2 className="text-xl sm:text-2xl font-bold font-serif text-text tracking-tight mt-1">
            Academic Performance & Report Card
          </h2>
          <p className="text-xs text-text2 mt-0.5">
            Bryan Donceras · Grade 11 STEM Section A · General Weighted Average: <strong>92.9 (With Honors)</strong>
          </p>
        </div>

        {/* View Switcher & Print / Download Controls */}
        <div className="flex items-center gap-2.5 flex-wrap">
          <div className="flex items-center bg-bg3 p-1 rounded-xl border border-border text-xs">
            <button
              type="button"
              onClick={() => setViewMode('breakdown')}
              className={`px-3 py-1.5 rounded-lg flex items-center gap-1.5 font-medium transition-all cursor-pointer ${
                viewMode === 'breakdown'
                  ? 'bg-bg2 text-text shadow-2xs font-semibold'
                  : 'text-text3 hover:text-text'
              }`}
            >
              <RiBarChartLine className="size-3.5" />
              <span>Digital Breakdown</span>
            </button>
            <button
              type="button"
              onClick={() => setViewMode('reportCard')}
              className={`px-3 py-1.5 rounded-lg flex items-center gap-1.5 font-medium transition-all cursor-pointer ${
                viewMode === 'reportCard'
                  ? 'bg-bg2 text-text shadow-2xs font-semibold'
                  : 'text-text3 hover:text-text'
              }`}
            >
              <RiGraduationCapLine className="size-3.5" />
              <span>Physical Report Card (SF9)</span>
            </button>
          </div>

          <div className="h-5 w-px bg-border hidden sm:block" />

          <button
            type="button"
            onClick={handlePrint}
            className="px-3 py-2 rounded-xl border border-border bg-bg2 hover:bg-bg3 text-text text-xs font-semibold cursor-pointer shadow-2xs flex items-center gap-1.5 transition-colors"
            title="Print Official Report Card"
          >
            <RiPrinterLine className="size-3.5 text-text2" />
            <span className="hidden sm:inline">Print</span>
          </button>

          <button
            type="button"
            onClick={handleDownloadPdf}
            className="px-3 py-2 rounded-xl bg-accent hover:bg-accent2 text-white text-xs font-semibold cursor-pointer shadow-2xs flex items-center gap-1.5 transition-all"
            title="Download Form 138-SHS PDF"
          >
            <RiDownload2Line className="size-3.5" />
            <span>Download Form 138</span>
          </button>
        </div>
      </div>

      {/* ============================================================ */}
      {/* 2. MODE A: DIGITAL BREAKDOWN & ANALYTICS VIEW                */}
      {/* ============================================================ */}
      {viewMode === 'breakdown' && (
        <div className="space-y-6">
          {/* Quick Banner to switch to Physical Report Card */}
          <div className="p-4 rounded-2xl bg-gradient-to-r from-accent/10 via-bg2 to-bg2 border border-accent/25 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-2xs">
            <div className="flex items-center gap-3">
              <div className="size-10 rounded-xl bg-accent text-white flex items-center justify-center shrink-0 shadow-xs">
                <RiFileList3Line className="size-5" />
              </div>
              <div>
                <h4 className="text-sm font-bold text-text">
                  Official DepEd School Form 9 (SF9-SHS) Available
                </h4>
                <p className="text-xs text-text2 mt-0.5">
                  View the authentic printable physical report card with core values, attendance ledger, and principal certification.
                </p>
              </div>
            </div>

            <button
              type="button"
              onClick={() => setViewMode('reportCard')}
              className="px-4 py-2 rounded-xl bg-bg border border-border hover:border-accent text-text text-xs font-semibold cursor-pointer shadow-2xs flex items-center gap-1.5 transition-all shrink-0 hover:translate-x-0.5"
            >
              <span>View Physical Card</span>
              <RiArrowRightLine className="size-3.5 text-accent" />
            </button>
          </div>

          {/* SUMMARY KPI CARDS (Matching student_grades.php line 491-518) */}
          <div className="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-3.5">
            {/* Card 1: Average Grade */}
            <div className="p-4 rounded-xl bg-bg2 border border-border shadow-2xs flex items-center gap-3.5 hover:border-border2 transition-all">
              <div className="size-10 rounded-xl bg-accent/15 text-accent flex items-center justify-center shrink-0">
                <RiBarChartLine className="size-5" />
              </div>
              <div className="min-w-0">
                <div className="text-xl font-bold font-mono text-text">{stats.avg}%</div>
                <div className="text-[11px] text-text3 font-medium truncate">Average Grade (GWA)</div>
              </div>
            </div>

            {/* Card 2: Highest Score */}
            <div className="p-4 rounded-xl bg-bg2 border border-border shadow-2xs flex items-center gap-3.5 hover:border-border2 transition-all">
              <div className="size-10 rounded-xl bg-green-bg text-green flex items-center justify-center shrink-0">
                <RiTrophyLine className="size-5" />
              </div>
              <div className="min-w-0">
                <div className="text-xl font-bold font-mono text-text">{stats.highest}%</div>
                <div className="text-[11px] text-text3 font-medium truncate">Highest Subject Grade</div>
              </div>
            </div>

            {/* Card 3: 90% and Above */}
            <div className="p-4 rounded-xl bg-bg2 border border-border shadow-2xs flex items-center gap-3.5 hover:border-border2 transition-all">
              <div className="size-10 rounded-xl bg-purple/15 text-purple flex items-center justify-center shrink-0">
                <RiStarLine className="size-5" />
              </div>
              <div className="min-w-0">
                <div className="text-xl font-bold font-mono text-text">
                  {stats.above90} <span className="text-xs font-normal text-text3">of {allGradesPool.length}</span>
                </div>
                <div className="text-[11px] text-text3 font-medium truncate">
                  90% & Above ({Math.round((stats.above90 / allGradesPool.length) * 100)}%)
                </div>
              </div>
            </div>

            {/* Card 4: Pass Rate */}
            <div className="p-4 rounded-xl bg-bg2 border border-border shadow-2xs flex items-center gap-3.5 hover:border-border2 transition-all">
              <div className="size-10 rounded-xl bg-cyan/15 text-cyan flex items-center justify-center shrink-0">
                <RiCheckLine className="size-5" />
              </div>
              <div className="min-w-0">
                <div className="text-xl font-bold font-mono text-green">{stats.passRate}%</div>
                <div className="text-[11px] text-text3 font-medium truncate">
                  Pass Rate ({stats.passingCount}/{allGradesPool.length})
                </div>
              </div>
            </div>

            {/* Card 5: Lowest Grade */}
            <div className="p-4 rounded-xl bg-bg2 border border-border shadow-2xs flex items-center gap-3.5 hover:border-border2 transition-all col-span-2 md:col-span-1">
              <div className="size-10 rounded-xl bg-amber-bg text-amber flex items-center justify-center shrink-0">
                <RiInformationLine className="size-5" />
              </div>
              <div className="min-w-0">
                <div className="text-xl font-bold font-mono text-text">{stats.lowest}%</div>
                <div className="text-[11px] text-text3 font-medium truncate" title={stats.lowestLabel}>
                  {stats.lowestLabel || 'Lowest Grade'}
                </div>
              </div>
            </div>
          </div>

          {/* FILTER TOOLBAR (Matching student_grades.php line 521-565) */}
          <div className="bg-bg2 border border-border rounded-xl p-3 shadow-2xs flex flex-wrap items-center justify-between gap-3">
            <div className="flex items-center gap-2 flex-wrap flex-1 min-w-[280px]">
              {/* Search Box */}
              <div className="relative min-w-[200px] flex-1 sm:flex-initial">
                <RiSearchLine className="absolute left-2.5 top-1/2 -translate-y-1/2 size-3.5 text-text3 pointer-events-none" />
                <input
                  type="text"
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  placeholder="Search subjects, codes, instructors..."
                  className="w-full pl-8 pr-7 py-1.5 rounded-lg bg-bg border border-border text-xs text-text placeholder:text-text3 focus:outline-none focus:border-accent transition-colors"
                />
                {searchQuery && (
                  <button
                    type="button"
                    onClick={() => setSearchQuery('')}
                    className="absolute right-2 top-1/2 -translate-y-1/2 text-text3 hover:text-text p-0.5 cursor-pointer"
                  >
                    <RiCloseLine className="size-3.5" />
                  </button>
                )}
              </div>

              {/* Semester Filter */}
              <select
                value={selectedSemester}
                onChange={(e) => setSelectedSemester(e.target.value as 'all' | '1' | '2')}
                className="px-3 py-1.5 rounded-lg bg-bg border border-border text-xs text-text font-medium cursor-pointer focus:outline-none focus:border-accent"
              >
                <option value="all">All Semesters (SY 2025–26)</option>
                <option value="1">1st Semester (Completed)</option>
                <option value="2">2nd Semester (Current Ongoing)</option>
              </select>

              {/* Subject Category Filter */}
              <select
                value={selectedCategory}
                onChange={(e) => setSelectedCategory(e.target.value as 'all' | 'core' | 'applied' | 'specialized')}
                className="px-3 py-1.5 rounded-lg bg-bg border border-border text-xs text-text font-medium cursor-pointer focus:outline-none focus:border-accent"
              >
                <option value="all">All Categories</option>
                <option value="core">Core Subjects</option>
                <option value="applied">Applied Track</option>
                <option value="specialized">Specialized STEM</option>
              </select>

              {/* Sort Order */}
              <select
                value={sortBy}
                onChange={(e) => setSortBy(e.target.value as 'default' | 'score-desc' | 'score-asc' | 'name-asc')}
                className="px-3 py-1.5 rounded-lg bg-bg border border-border text-xs text-text font-medium cursor-pointer focus:outline-none focus:border-accent"
              >
                <option value="default">Sort: Default (Curriculum)</option>
                <option value="score-desc">Score: High → Low</option>
                <option value="score-asc">Score: Low → High</option>
                <option value="name-asc">Subject: A → Z</option>
              </select>
            </div>
          </div>

          {/* INTERACTIVE GRADES TABLE WITH QUARTERS & PARTS */}
          <div className="bg-bg2 border border-border rounded-2xl overflow-hidden shadow-2xs">
            <div className="p-4 border-b border-border flex items-center justify-between">
              <div>
                <h3 className="font-bold text-sm text-text">Subject Performance Ledger</h3>
                <p className="text-[11px] text-text3 mt-0.5">
                  Click any subject row to inspect detailed assessment quizzes, rubric weights, and passing cutoffs.
                </p>
              </div>
              <span className="text-xs text-text3 font-mono">
                {totalItems === 0
                  ? '0 subjects'
                  : `Showing ${startIndex + 1}–${endIndex} of ${totalItems} subjects`}
              </span>
            </div>

            <div className="overflow-x-auto">
              <table className="w-full text-left text-xs">
                <thead className="bg-bg3/80 text-text3 uppercase tracking-wider text-[11px] font-semibold border-b border-border">
                  <tr>
                    <th className="py-3 px-4">Subject & Code</th>
                    <th className="py-3 px-3">Instructor</th>
                    <th className="py-3 px-3 text-center">Semester</th>
                    <th className="py-3 px-3 text-center">Q1 / Q3</th>
                    <th className="py-3 px-3 text-center">Q2 / Q4</th>
                    <th className="py-3 px-3 text-center">Sem Final</th>
                    <th className="py-3 px-4 text-center">Grade Bar</th>
                    <th className="py-3 px-3 text-center">Rating</th>
                    <th className="py-3 px-4 text-right">Action Taken</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-border">
                  {paginatedGrades.length === 0 ? (
                    <tr>
                      <td colSpan={9} className="py-12 text-center text-text3 text-xs">
                        No subject grades match your selected criteria.
                      </td>
                    </tr>
                  ) : (
                    paginatedGrades.map((g) => {
                      const letter = getLetterGrade(g.semFinal);
                      const colorClass = getGradeColor(g.semFinal);
                      const barClass = getProgressBarClass(g.semFinal);
                      const badgeClass = getGradeBg(g.semFinal);

                      const qFirst = g.semester === 1 ? g.q1 : g.q3;
                      const qSecond = g.semester === 1 ? g.q2 : g.q4;

                      return (
                        <tr
                          key={g.id}
                          onClick={() => setActiveModalSubject(g)}
                          className="hover:bg-bg3/50 cursor-pointer transition-colors group"
                        >
                          <td className="py-3 px-4">
                            <div className="flex items-center gap-2">
                              <span className="font-mono text-[10px] font-bold px-1.5 py-0.5 rounded bg-bg3 text-text3 group-hover:text-accent border border-border">
                                {g.code}
                              </span>
                              <div>
                                <div className="font-bold text-text group-hover:text-accent transition-colors flex items-center gap-1.5">
                                  <span>{g.title}</span>
                                  {g.semFinal >= 95 && (
                                    <span className="text-[10px] px-1.5 py-0.2 rounded bg-amber-bg text-amber font-semibold">
                                      ★ High Mark
                                    </span>
                                  )}
                                </div>
                                <div className="text-[11px] text-text3 flex items-center gap-2 mt-0.5">
                                  <span>{g.categoryLabel}</span>
                                  <span>·</span>
                                  <span>{g.quizzesTaken} Quizzes Averaged</span>
                                </div>
                              </div>
                            </div>
                          </td>

                          <td className="py-3 px-3 text-text2 text-[11px]">
                            {g.instructor}
                          </td>

                          <td className="py-3 px-3 text-center">
                            <span className="text-[11px] font-mono text-text3 font-semibold">
                              Sem {g.semester}
                            </span>
                          </td>

                          {/* Quarter 1 or 3 */}
                          <td className="py-3 px-3 text-center font-mono font-bold text-text">
                            {qFirst ?? '—'}
                          </td>

                          {/* Quarter 2 or 4 */}
                          <td className="py-3 px-3 text-center font-mono font-bold text-text">
                            {qSecond ?? '—'}
                          </td>

                          {/* Final Semester Grade */}
                          <td className="py-3 px-3 text-center font-mono font-extrabold text-sm" style={{ color: 'var(--text)' }}>
                            <span className={colorClass}>{g.semFinal}</span>
                          </td>

                          {/* Grade Bar Progress */}
                          <td className="py-3 px-4 text-center min-w-[120px]">
                            <div className="space-y-1">
                              <div className="h-1.5 w-full bg-bg3 rounded-full overflow-hidden">
                                <div
                                  className={`h-full rounded-full transition-all duration-300 ${barClass}`}
                                  style={{ width: `${g.semFinal}%` }}
                                />
                              </div>
                              <div className="text-[10px] text-text3 font-mono">
                                {g.totalScore} / {g.totalMax} pts
                              </div>
                            </div>
                          </td>

                          {/* Letter Rating */}
                          <td className="py-3 px-3 text-center">
                            <span className={`px-2 py-0.5 rounded-md text-[11px] font-bold border ${badgeClass}`}>
                              {letter}
                            </span>
                          </td>

                          {/* Action Taken */}
                          <td className="py-3 px-4 text-right">
                            <span className="inline-flex items-center gap-1 text-[11px] font-semibold text-green">
                              <RiCheckLine className="size-3.5" />
                              <span>{g.actionTaken}</span>
                            </span>
                          </td>
                        </tr>
                      );
                    })
                  )}
                </tbody>
              </table>
            </div>

            {/* Pagination Controls Footer */}
            {totalItems > 0 && (
              <div className="p-3 sm:p-4 bg-bg3/30 border-t border-border flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                <div className="flex items-center gap-2 text-text3">
                  <span>
                    Showing <strong className="text-text font-mono">{startIndex + 1}</strong> to{' '}
                    <strong className="text-text font-mono">{endIndex}</strong> of{' '}
                    <strong className="text-text font-mono">{totalItems}</strong> subjects
                  </span>
                  <span className="hidden sm:inline">·</span>
                  <div className="flex items-center gap-1.5">
                    <span className="text-text3 text-[11px]">Rows:</span>
                    <select
                      value={pageSize}
                      onChange={(e) => {
                        setPageSize(Number(e.target.value));
                        setCurrentPage(1);
                      }}
                      className="px-2 py-0.5 rounded-md bg-bg border border-border text-xs text-text font-medium cursor-pointer focus:outline-none focus:border-accent"
                    >
                      <option value={5}>5</option>
                      <option value={7}>7</option>
                      <option value={10}>10</option>
                      <option value={20}>All</option>
                    </select>
                  </div>
                </div>

                {/* Page Buttons */}
                {totalPages > 1 && (
                  <div className="flex items-center gap-1">
                    <button
                      type="button"
                      disabled={safePage <= 1}
                      onClick={() => setCurrentPage((p) => Math.max(1, p - 1))}
                      className="p-1 rounded-lg border border-border bg-bg hover:bg-bg3 text-text disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer transition-colors"
                      aria-label="Previous page"
                    >
                      <RiArrowLeftSLine className="size-4" />
                    </button>

                    {Array.from({ length: totalPages }, (_, i) => i + 1).map((pageNum) => (
                      <button
                        key={pageNum}
                        type="button"
                        onClick={() => setCurrentPage(pageNum)}
                        className={`size-7 rounded-lg text-xs font-mono font-bold cursor-pointer transition-all ${
                          pageNum === safePage
                            ? 'bg-accent text-white shadow-2xs'
                            : 'bg-bg hover:bg-bg3 border border-border text-text2 hover:text-text'
                        }`}
                      >
                        {pageNum}
                      </button>
                    ))}

                    <button
                      type="button"
                      disabled={safePage >= totalPages}
                      onClick={() => setCurrentPage((p) => Math.min(totalPages, p + 1))}
                      className="p-1 rounded-lg border border-border bg-bg hover:bg-bg3 text-text disabled:opacity-30 disabled:cursor-not-allowed cursor-pointer transition-colors"
                      aria-label="Next page"
                    >
                      <RiArrowRightSLine className="size-4" />
                    </button>
                  </div>
                )}
              </div>
            )}
          </div>
        </div>
      )}

      {/* ============================================================ */}
      {/* 3. MODE B: OFFICIAL PHYSICAL REPORT CARD (SF9-SHS / FORM 138) */}
      {/* ============================================================ */}
      {viewMode === 'reportCard' && (
        <div className="space-y-6">
          {/* Card Frame simulating the physical sheet */}
          <div
            id="official-report-card-sheet"
            className="rounded-2xl border-2 border-border bg-[#fcfcfb] dark:bg-[#12151e] text-slate-900 dark:text-slate-100 p-6 sm:p-10 shadow-xl space-y-8 print:border-none print:shadow-none print:p-0"
          >
            {/* Header: Republic & Institutional Seal */}
            <div className="border-b-2 border-slate-300 dark:border-slate-700 pb-6">
              <div className="flex flex-col sm:flex-row items-center justify-between text-center sm:text-left gap-4">
                <div className="flex items-center gap-4">
                  <div className="size-16 rounded-full bg-white dark:bg-bg3 border-2 border-slate-200 dark:border-slate-700 p-1 flex items-center justify-center shadow-xs shrink-0 overflow-hidden">
                    <img
                      src="/img/Excelsior_Seal_Logo.png"
                      alt="Excelsior Polytechnic Academy Seal"
                      className="w-full h-full object-contain"
                    />
                  </div>
                  <div>
                    <div className="text-[10px] font-extrabold uppercase tracking-widest text-slate-500 dark:text-slate-400">
                      Republic of the Philippines · Department of Education
                    </div>
                    <h1 className="text-lg sm:text-2xl font-bold font-serif uppercase tracking-tight text-slate-900 dark:text-white mt-0.5">
                      {STUDENT_REPORT_PROFILE.schoolName}
                    </h1>
                    <p className="text-xs text-slate-600 dark:text-slate-300">
                      Senior High School Department · Government Recognition (SHS) No. 042, s. 2017
                    </p>
                    <p className="text-[11px] text-slate-500 dark:text-slate-400 font-mono">
                      {STUDENT_REPORT_PROFILE.division} · {STUDENT_REPORT_PROFILE.region} · School ID: {STUDENT_REPORT_PROFILE.schoolId}
                    </p>
                  </div>
                </div>

                <div className="text-center sm:text-right shrink-0">
                  <div className="inline-block px-3 py-1 rounded border-2 border-slate-800 dark:border-slate-200 font-mono font-bold text-xs uppercase tracking-wider bg-slate-100 dark:bg-slate-800">
                    DepEd Form 138-SHS (SF9)
                  </div>
                  <div className="text-xs font-serif italic text-slate-600 dark:text-slate-300 mt-1">
                    Learner Progress Report Card
                  </div>
                  <div className="text-[11px] font-mono text-slate-500 dark:text-slate-400">
                    School Year: {STUDENT_REPORT_PROFILE.schoolYear}
                  </div>
                </div>
              </div>
            </div>

            {/* Learner Reference & Biographical Information Box */}
            <div className="rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50/70 dark:bg-slate-900/60 p-4">
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
                <div>
                  <span className="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">
                    Learner's Full Name
                  </span>
                  <span className="font-bold text-sm text-slate-900 dark:text-white font-mono">
                    {STUDENT_REPORT_PROFILE.studentName}
                  </span>
                </div>

                <div>
                  <span className="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">
                    Learner Reference No. (LRN)
                  </span>
                  <span className="font-bold font-mono text-slate-900 dark:text-white">
                    {STUDENT_REPORT_PROFILE.lrn}
                  </span>
                </div>

                <div>
                  <span className="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">
                    Grade & Section
                  </span>
                  <span className="font-semibold text-slate-900 dark:text-white">
                    {STUDENT_REPORT_PROFILE.gradeLevel} — {STUDENT_REPORT_PROFILE.section}
                  </span>
                </div>

                <div>
                  <span className="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">
                    Track & Strand
                  </span>
                  <span className="font-semibold text-slate-900 dark:text-white truncate block">
                    {STUDENT_REPORT_PROFILE.strand}
                  </span>
                </div>

                <div>
                  <span className="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">
                    Class Adviser
                  </span>
                  <span className="font-semibold text-slate-900 dark:text-white">
                    {STUDENT_REPORT_PROFILE.classAdviser}
                  </span>
                </div>

                <div>
                  <span className="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">
                    Age / Sex
                  </span>
                  <span className="font-semibold text-slate-900 dark:text-white">
                    {STUDENT_REPORT_PROFILE.age} Years Old / {STUDENT_REPORT_PROFILE.sex}
                  </span>
                </div>

                <div>
                  <span className="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">
                    Curriculum
                  </span>
                  <span className="font-semibold text-slate-900 dark:text-white">
                    K to 12 Senior High School
                  </span>
                </div>

                <div>
                  <span className="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider block">
                    Academic Standing
                  </span>
                  <span className="inline-flex items-center gap-1 font-bold text-green">
                    <RiCheckLine className="size-3.5" />
                    <span>With Honors (GWA: 92.9)</span>
                  </span>
                </div>
              </div>
            </div>

            {/* REPORT ON LEARNING PROGRESS AND ACHIEVEMENT */}
            <div className="space-y-6">
              <div className="text-center">
                <h3 className="font-serif font-bold text-base uppercase tracking-wider text-slate-900 dark:text-white">
                  Report on Learning Progress and Achievement
                </h3>
                <p className="text-xs text-slate-500 dark:text-slate-400">
                  Transmuted grades pursuant to DepEd Order No. 8, s. 2015 Policy Guidelines on Classroom Assessment
                </p>
              </div>

              {/* SEMESTER 1 TABLE */}
              <div className="rounded-xl border border-slate-300 dark:border-slate-700 overflow-hidden">
                <div className="bg-slate-200/80 dark:bg-slate-800 px-4 py-2 flex items-center justify-between border-b border-slate-300 dark:border-slate-700 font-bold text-xs uppercase tracking-wider">
                  <span>First Semester</span>
                  <span className="font-mono text-green font-bold">
                    General Average: {STUDENT_REPORT_PROFILE.firstSemGWA} (Passed)
                  </span>
                </div>

                <table className="w-full text-left text-xs">
                  <thead className="bg-slate-100 dark:bg-slate-800/60 text-slate-600 dark:text-slate-300 uppercase tracking-wider text-[10px] font-bold border-b border-slate-300 dark:border-slate-700">
                    <tr>
                      <th className="py-2.5 px-4">Subject Title</th>
                      <th className="py-2.5 px-3 text-center w-24">Quarter 1</th>
                      <th className="py-2.5 px-3 text-center w-24">Quarter 2</th>
                      <th className="py-2.5 px-3 text-center w-28">Semester Final</th>
                      <th className="py-2.5 px-4 text-right w-28">Action Taken</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-200 dark:divide-slate-800">
                    {/* Core Subjects Subhead */}
                    <tr className="bg-slate-50/50 dark:bg-slate-900/40">
                      <td colSpan={5} className="py-1 px-4 text-[10px] font-extrabold uppercase tracking-wider text-slate-500">
                        Core Subjects
                      </td>
                    </tr>
                    {FIRST_SEMESTER_GRADES.filter((g) => g.category === 'core').map((g) => (
                      <tr key={g.id} className="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                        <td className="py-2 px-4 font-medium text-slate-900 dark:text-slate-100">{g.title}</td>
                        <td className="py-2 px-3 text-center font-mono font-semibold">{g.q1}</td>
                        <td className="py-2 px-3 text-center font-mono font-semibold">{g.q2}</td>
                        <td className="py-2 px-3 text-center font-mono font-bold text-slate-900 dark:text-white">{g.semFinal}</td>
                        <td className="py-2 px-4 text-right font-semibold text-green">{g.actionTaken}</td>
                      </tr>
                    ))}

                    {/* Applied Subjects Subhead */}
                    <tr className="bg-slate-50/50 dark:bg-slate-900/40">
                      <td colSpan={5} className="py-1 px-4 text-[10px] font-extrabold uppercase tracking-wider text-slate-500">
                        Applied Track Subjects
                      </td>
                    </tr>
                    {FIRST_SEMESTER_GRADES.filter((g) => g.category === 'applied').map((g) => (
                      <tr key={g.id} className="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                        <td className="py-2 px-4 font-medium text-slate-900 dark:text-slate-100">{g.title}</td>
                        <td className="py-2 px-3 text-center font-mono font-semibold">{g.q1}</td>
                        <td className="py-2 px-3 text-center font-mono font-semibold">{g.q2}</td>
                        <td className="py-2 px-3 text-center font-mono font-bold text-slate-900 dark:text-white">{g.semFinal}</td>
                        <td className="py-2 px-4 text-right font-semibold text-green">{g.actionTaken}</td>
                      </tr>
                    ))}

                    {/* Specialized Subjects Subhead */}
                    <tr className="bg-slate-50/50 dark:bg-slate-900/40">
                      <td colSpan={5} className="py-1 px-4 text-[10px] font-extrabold uppercase tracking-wider text-slate-500">
                        Specialized STEM Subjects
                      </td>
                    </tr>
                    {FIRST_SEMESTER_GRADES.filter((g) => g.category === 'specialized').map((g) => (
                      <tr key={g.id} className="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                        <td className="py-2 px-4 font-medium text-slate-900 dark:text-slate-100">{g.title}</td>
                        <td className="py-2 px-3 text-center font-mono font-semibold">{g.q1}</td>
                        <td className="py-2 px-3 text-center font-mono font-semibold">{g.q2}</td>
                        <td className="py-2 px-3 text-center font-mono font-bold text-slate-900 dark:text-white">{g.semFinal}</td>
                        <td className="py-2 px-4 text-right font-semibold text-green">{g.actionTaken}</td>
                      </tr>
                    ))}
                  </tbody>
                  <tfoot className="bg-slate-100 dark:bg-slate-800/80 font-bold border-t-2 border-slate-300 dark:border-slate-700">
                    <tr>
                      <td className="py-2.5 px-4 uppercase tracking-wider text-[11px]">
                        General Average for First Semester
                      </td>
                      <td colSpan={2} />
                      <td className="py-2.5 px-3 text-center font-mono text-sm text-green">
                        {STUDENT_REPORT_PROFILE.firstSemGWA}
                      </td>
                      <td className="py-2.5 px-4 text-right text-green">Passed</td>
                    </tr>
                  </tfoot>
                </table>
              </div>

              {/* SEMESTER 2 TABLE */}
              <div className="rounded-xl border border-slate-300 dark:border-slate-700 overflow-hidden">
                <div className="bg-slate-200/80 dark:bg-slate-800 px-4 py-2 flex items-center justify-between border-b border-slate-300 dark:border-slate-700 font-bold text-xs uppercase tracking-wider">
                  <span>Second Semester</span>
                  <span className="font-mono text-green font-bold">
                    General Average: {STUDENT_REPORT_PROFILE.secondSemGWA} (Passed)
                  </span>
                </div>

                <table className="w-full text-left text-xs">
                  <thead className="bg-slate-100 dark:bg-slate-800/60 text-slate-600 dark:text-slate-300 uppercase tracking-wider text-[10px] font-bold border-b border-slate-300 dark:border-slate-700">
                    <tr>
                      <th className="py-2.5 px-4">Subject Title</th>
                      <th className="py-2.5 px-3 text-center w-24">Quarter 3</th>
                      <th className="py-2.5 px-3 text-center w-24">Quarter 4</th>
                      <th className="py-2.5 px-3 text-center w-28">Semester Final</th>
                      <th className="py-2.5 px-4 text-right w-28">Action Taken</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-200 dark:divide-slate-800">
                    {/* Core Subjects Subhead */}
                    <tr className="bg-slate-50/50 dark:bg-slate-900/40">
                      <td colSpan={5} className="py-1 px-4 text-[10px] font-extrabold uppercase tracking-wider text-slate-500">
                        Core Subjects
                      </td>
                    </tr>
                    {SECOND_SEMESTER_GRADES.filter((g) => g.category === 'core').map((g) => (
                      <tr key={g.id} className="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                        <td className="py-2 px-4 font-medium text-slate-900 dark:text-slate-100">{g.title}</td>
                        <td className="py-2 px-3 text-center font-mono font-semibold">{g.q3}</td>
                        <td className="py-2 px-3 text-center font-mono font-semibold">{g.q4}</td>
                        <td className="py-2 px-3 text-center font-mono font-bold text-slate-900 dark:text-white">{g.semFinal}</td>
                        <td className="py-2 px-4 text-right font-semibold text-green">{g.actionTaken}</td>
                      </tr>
                    ))}

                    {/* Applied Subjects Subhead */}
                    <tr className="bg-slate-50/50 dark:bg-slate-900/40">
                      <td colSpan={5} className="py-1 px-4 text-[10px] font-extrabold uppercase tracking-wider text-slate-500">
                        Applied Track Subjects
                      </td>
                    </tr>
                    {SECOND_SEMESTER_GRADES.filter((g) => g.category === 'applied').map((g) => (
                      <tr key={g.id} className="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                        <td className="py-2 px-4 font-medium text-slate-900 dark:text-slate-100">{g.title}</td>
                        <td className="py-2 px-3 text-center font-mono font-semibold">{g.q3}</td>
                        <td className="py-2 px-3 text-center font-mono font-semibold">{g.q4}</td>
                        <td className="py-2 px-3 text-center font-mono font-bold text-slate-900 dark:text-white">{g.semFinal}</td>
                        <td className="py-2 px-4 text-right font-semibold text-green">{g.actionTaken}</td>
                      </tr>
                    ))}

                    {/* Specialized Subjects Subhead */}
                    <tr className="bg-slate-50/50 dark:bg-slate-900/40">
                      <td colSpan={5} className="py-1 px-4 text-[10px] font-extrabold uppercase tracking-wider text-slate-500">
                        Specialized STEM Subjects
                      </td>
                    </tr>
                    {SECOND_SEMESTER_GRADES.filter((g) => g.category === 'specialized').map((g) => (
                      <tr key={g.id} className="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                        <td className="py-2 px-4 font-medium text-slate-900 dark:text-slate-100">{g.title}</td>
                        <td className="py-2 px-3 text-center font-mono font-semibold">{g.q3}</td>
                        <td className="py-2 px-3 text-center font-mono font-semibold">{g.q4}</td>
                        <td className="py-2 px-3 text-center font-mono font-bold text-slate-900 dark:text-white">{g.semFinal}</td>
                        <td className="py-2 px-4 text-right font-semibold text-green">{g.actionTaken}</td>
                      </tr>
                    ))}
                  </tbody>
                  <tfoot className="bg-slate-100 dark:bg-slate-800/80 font-bold border-t-2 border-slate-300 dark:border-slate-700">
                    <tr>
                      <td className="py-2.5 px-4 uppercase tracking-wider text-[11px]">
                        General Average for Second Semester
                      </td>
                      <td colSpan={2} />
                      <td className="py-2.5 px-3 text-center font-mono text-sm text-green">
                        {STUDENT_REPORT_PROFILE.secondSemGWA}
                      </td>
                      <td className="py-2.5 px-4 text-right text-green">Passed</td>
                    </tr>
                  </tfoot>
                </table>
              </div>

              {/* FINAL SUMMARY CALLOUT */}
              <div className="p-4 rounded-xl border-2 border-green/40 bg-green-bg/20 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                <div>
                  <span className="text-[10px] font-extrabold uppercase tracking-widest text-green">
                    General Weighted Average (GWA) for the School Year
                  </span>
                  <div className="text-xl font-bold font-mono text-slate-900 dark:text-white mt-0.5">
                    {STUDENT_REPORT_PROFILE.generalAverage}% —{' '}
                    <span className="text-green">Promoted to Grade 12 (With Honors)</span>
                  </div>
                </div>

                <div className="flex items-center gap-2">
                  <span className="px-3 py-1 rounded-full bg-green text-white font-mono font-bold text-xs shadow-xs">
                    PASSED / PROMOTED
                  </span>
                </div>
              </div>
            </div>

            {/* GRADING SCALE DESCRIPTORS (DepEd Order No. 8, s. 2015) */}
            <div className="p-4 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 space-y-2">
              <div className="font-bold text-xs uppercase tracking-wider text-slate-700 dark:text-slate-300">
                Descriptors & Grading Scale (DepEd Order No. 8, s. 2015)
              </div>
              <div className="grid grid-cols-2 sm:grid-cols-5 gap-2 text-[11px]">
                <div className="p-2 rounded bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                  <div className="font-bold text-green">90 – 100</div>
                  <div className="text-slate-600 dark:text-slate-300">Outstanding (O)</div>
                  <div className="text-[10px] text-green font-semibold">Passed</div>
                </div>
                <div className="p-2 rounded bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                  <div className="font-bold text-accent">85 – 89</div>
                  <div className="text-slate-600 dark:text-slate-300">Very Satisfactory (VS)</div>
                  <div className="text-[10px] text-green font-semibold">Passed</div>
                </div>
                <div className="p-2 rounded bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                  <div className="font-bold text-slate-800 dark:text-slate-200">80 – 84</div>
                  <div className="text-slate-600 dark:text-slate-300">Satisfactory (S)</div>
                  <div className="text-[10px] text-green font-semibold">Passed</div>
                </div>
                <div className="p-2 rounded bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                  <div className="font-bold text-amber">75 – 79</div>
                  <div className="text-slate-600 dark:text-slate-300">Fairly Satisfactory (FS)</div>
                  <div className="text-[10px] text-green font-semibold">Passed</div>
                </div>
                <div className="p-2 rounded bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                  <div className="font-bold text-red">Below 75</div>
                  <div className="text-slate-600 dark:text-slate-300">Did Not Meet Exp. (DNME)</div>
                  <div className="text-[10px] text-red font-semibold">Failed</div>
                </div>
              </div>
            </div>

            {/* REPORT ON LEARNER'S OBSERVED VALUES (Core Values SF9 Section) */}
            <div className="space-y-3">
              <div className="flex items-center justify-between">
                <div>
                  <h4 className="font-serif font-bold text-sm uppercase tracking-wider text-slate-900 dark:text-white">
                    Report on Learner's Observed Values (SF9)
                  </h4>
                  <p className="text-[11px] text-slate-500 dark:text-slate-400">
                    Marking Codes: <strong>AO</strong> – Always Observed | <strong>SO</strong> – Sometimes Observed | <strong>RO</strong> – Rarely Observed | <strong>NO</strong> – Not Observed
                  </p>
                </div>
              </div>

              <div className="rounded-xl border border-slate-300 dark:border-slate-700 overflow-hidden">
                <table className="w-full text-left text-xs">
                  <thead className="bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 uppercase tracking-wider text-[10px] font-bold border-b border-slate-300 dark:border-slate-700">
                    <tr>
                      <th className="py-2.5 px-4 w-40">Core Values</th>
                      <th className="py-2.5 px-4">Behavior Statements</th>
                      <th className="py-2.5 px-3 text-center w-14">Q1</th>
                      <th className="py-2.5 px-3 text-center w-14">Q2</th>
                      <th className="py-2.5 px-3 text-center w-14">Q3</th>
                      <th className="py-2.5 px-3 text-center w-14">Q4</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-200 dark:divide-slate-800">
                    {OBSERVED_CORE_VALUES.map((cv) => (
                      <tr key={cv.coreValue} className="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                        <td className="py-3 px-4 font-bold text-slate-900 dark:text-white align-top">
                          {cv.coreValue}
                        </td>
                        <td className="py-3 px-4 text-[11px] text-slate-700 dark:text-slate-300 space-y-1">
                          {cv.behaviorStatements.map((stmt, sIdx) => (
                            <div key={sIdx}>• {stmt}</div>
                          ))}
                        </td>
                        <td className="py-3 px-3 text-center font-mono font-bold text-green align-middle">{cv.q1}</td>
                        <td className="py-3 px-3 text-center font-mono font-bold text-green align-middle">{cv.q2}</td>
                        <td className="py-3 px-3 text-center font-mono font-bold text-green align-middle">{cv.q3}</td>
                        <td className="py-3 px-3 text-center font-mono font-bold text-green align-middle">{cv.q4}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>

            {/* ATTENDANCE RECORD (School Form 9 Standard Ledger) */}
            <div className="space-y-3">
              <div className="flex items-center justify-between">
                <h4 className="font-serif font-bold text-sm uppercase tracking-wider text-slate-900 dark:text-white">
                  Report on Attendance Record
                </h4>
                <span className="text-xs text-text3 font-mono">Total School Days: 204 · Days Present: 202</span>
              </div>

              <div className="rounded-xl border border-slate-300 dark:border-slate-700 overflow-x-auto">
                <table className="w-full text-center text-xs">
                  <thead className="bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 uppercase tracking-wider text-[10px] font-bold border-b border-slate-300 dark:border-slate-700">
                    <tr>
                      <th className="py-2 px-3 text-left">Category</th>
                      {MONTHLY_ATTENDANCE_RECORD.map((m) => (
                        <th key={m.month} className="py-2 px-2 font-mono">
                          {m.month}
                        </th>
                      ))}
                      <th className="py-2 px-3 font-bold text-slate-900 dark:text-white">Total</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-200 dark:divide-slate-800 font-mono text-[11px]">
                    <tr>
                      <td className="py-2 px-3 text-left font-sans font-semibold text-slate-700 dark:text-slate-300">
                        No. of school days
                      </td>
                      {MONTHLY_ATTENDANCE_RECORD.map((m) => (
                        <td key={m.month} className="py-2 px-2">
                          {m.daysOfSchool}
                        </td>
                      ))}
                      <td className="py-2 px-3 font-bold text-slate-900 dark:text-white">
                        {MONTHLY_ATTENDANCE_RECORD.reduce((acc, curr) => acc + curr.daysOfSchool, 0)}
                      </td>
                    </tr>
                    <tr>
                      <td className="py-2 px-3 text-left font-sans font-semibold text-slate-700 dark:text-slate-300">
                        No. of days present
                      </td>
                      {MONTHLY_ATTENDANCE_RECORD.map((m) => (
                        <td key={m.month} className="py-2 px-2 text-green font-bold">
                          {m.daysPresent}
                        </td>
                      ))}
                      <td className="py-2 px-3 font-bold text-green">
                        {MONTHLY_ATTENDANCE_RECORD.reduce((acc, curr) => acc + curr.daysPresent, 0)}
                      </td>
                    </tr>
                    <tr>
                      <td className="py-2 px-3 text-left font-sans font-semibold text-slate-700 dark:text-slate-300">
                        No. of days absent
                      </td>
                      {MONTHLY_ATTENDANCE_RECORD.map((m) => (
                        <td key={m.month} className={`py-2 px-2 ${m.daysAbsent > 0 ? 'text-red font-bold' : 'text-slate-400'}`}>
                          {m.daysAbsent}
                        </td>
                      ))}
                      <td className="py-2 px-3 font-bold text-slate-900 dark:text-white">
                        {MONTHLY_ATTENDANCE_RECORD.reduce((acc, curr) => acc + curr.daysAbsent, 0)}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            {/* CERTIFICATION OF TRANSFER & SIGNATURES */}
            <div className="pt-6 border-t-2 border-slate-300 dark:border-slate-700 space-y-8">
              <div className="rounded-xl border border-slate-300 dark:border-slate-700 p-4 bg-slate-50/50 dark:bg-slate-900/30 text-xs space-y-2">
                <div className="font-bold text-xs uppercase tracking-wider text-slate-800 dark:text-slate-200">
                  Certificate of Transfer & Eligibility
                </div>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 text-slate-600 dark:text-slate-300">
                  <div>
                    Eligible for admission to:{' '}
                    <strong className="text-slate-900 dark:text-white">
                      {STUDENT_REPORT_PROFILE.eligibleForAdmissionTo}
                    </strong>
                  </div>
                  <div>
                    Lacks subjects / deficiencies:{' '}
                    <strong className="text-slate-900 dark:text-white">None (Clean Record)</strong>
                  </div>
                </div>
              </div>

              {/* Signature Lines */}
              <div className="grid grid-cols-1 sm:grid-cols-3 gap-8 pt-4 text-center">
                <div className="space-y-1">
                  <div className="h-10 flex items-end justify-center font-serif font-bold text-slate-800 dark:text-slate-200">
                    Maria Santos, PhD
                  </div>
                  <div className="border-t border-slate-400 dark:border-slate-600 pt-1.5 text-xs font-bold text-slate-900 dark:text-white">
                    {STUDENT_REPORT_PROFILE.classAdviser}
                  </div>
                  <div className="text-[10px] text-slate-500 uppercase tracking-wider">Class Adviser</div>
                </div>

                <div className="space-y-1 flex flex-col items-center justify-center">
                  <div className="size-16 rounded-full border-2 border-dashed border-slate-400 dark:border-slate-600 flex items-center justify-center text-[9px] font-mono text-slate-500 uppercase text-center p-1">
                    Registrar Dry Seal
                  </div>
                  <div className="text-[10px] text-slate-500 mt-1 font-mono">Date Issued: May 20, 2026</div>
                </div>

                <div className="space-y-1">
                  <div className="h-10 flex items-end justify-center font-serif font-bold text-slate-800 dark:text-slate-200">
                    Eduardo V. Morales, EdD
                  </div>
                  <div className="border-t border-slate-400 dark:border-slate-600 pt-1.5 text-xs font-bold text-slate-900 dark:text-white">
                    {STUDENT_REPORT_PROFILE.principal}
                  </div>
                  <div className="text-[10px] text-slate-500 uppercase tracking-wider">Senior High School Principal</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* ============================================================ */}
      {/* 4. MODAL: SUBJECT ASSESSMENT BREAKDOWN (PHP openModal)        */}
      {/* ============================================================ */}
      {activeModalSubject && (
        <div
          role="dialog"
          aria-modal="true"
          onClick={() => setActiveModalSubject(null)}
          className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-xs cursor-pointer animate-in fade-in duration-150"
        >
          <div
            onClick={(e) => e.stopPropagation()}
            className="w-full max-w-2xl bg-bg2 rounded-2xl border border-border shadow-2xl overflow-hidden flex flex-col max-h-[85vh] cursor-default animate-in zoom-in-95 duration-150"
          >
            {/* Modal Header */}
            <div className="p-4 sm:p-5 bg-gradient-to-r from-accent/15 via-bg3 to-bg3 border-b border-border flex items-center justify-between">
              <div className="flex items-center gap-3">
                <div className="size-10 rounded-xl bg-accent text-white flex items-center justify-center font-bold font-mono text-sm shadow-xs shrink-0">
                  {activeModalSubject.code.slice(0, 3)}
                </div>
                <div>
                  <div className="flex items-center gap-2">
                    <span className="text-[10px] font-extrabold uppercase tracking-wider text-accent">
                      {activeModalSubject.categoryLabel}
                    </span>
                    <span className="text-text3 text-xs">·</span>
                    <span className="text-xs text-text3 font-medium">Semester {activeModalSubject.semester}</span>
                  </div>
                  <h3 className="font-bold text-base text-text">{activeModalSubject.title}</h3>
                </div>
              </div>

              <button
                type="button"
                onClick={() => setActiveModalSubject(null)}
                className="p-1.5 rounded-lg text-text3 hover:text-text hover:bg-bg2 cursor-pointer transition-colors"
                aria-label="Close modal"
              >
                <RiCloseLine className="size-5" />
              </button>
            </div>

            {/* Modal Body */}
            <div className="p-4 sm:p-6 overflow-y-auto space-y-5 flex-1">
              {/* Score Hero (Matching student_grades.php line 826-835) */}
              <div className="p-5 rounded-2xl bg-bg3/60 border border-border text-center space-y-2">
                <div className="text-4xl font-extrabold font-mono" style={{ color: 'var(--text)' }}>
                  <span className={getGradeColor(activeModalSubject.semFinal)}>
                    {activeModalSubject.semFinal}%
                  </span>
                </div>
                <div className="text-xs font-bold text-text flex items-center justify-center gap-2">
                  <span>Letter Grade:</span>
                  <span className={`px-2 py-0.5 rounded-md font-mono text-xs border ${getGradeBg(activeModalSubject.semFinal)}`}>
                    {getLetterGrade(activeModalSubject.semFinal)}
                  </span>
                </div>
                <div className="text-xs text-text3">
                  {activeModalSubject.totalScore} / {activeModalSubject.totalMax} total points ·{' '}
                  {activeModalSubject.passed ? (
                    <span className="text-green font-semibold">✓ Passing Grade (Cutoff: {activeModalSubject.passingScore}%)</span>
                  ) : (
                    <span className="text-red font-semibold">✗ Below Passing (Cutoff: {activeModalSubject.passingScore}%)</span>
                  )}
                </div>

                <div className="pt-2 max-w-md mx-auto">
                  <div className="h-2 w-full bg-bg rounded-full overflow-hidden">
                    <div
                      className={`h-full rounded-full transition-all duration-300 ${getProgressBarClass(activeModalSubject.semFinal)}`}
                      style={{ width: `${activeModalSubject.semFinal}%` }}
                    />
                  </div>
                </div>
              </div>

              {/* DepEd Assessment Component Weights (Quarter Parts) */}
              <div className="space-y-2">
                <div className="text-xs font-bold text-text3 uppercase tracking-wider">
                  DepEd SHS Grading Components (Weights)
                </div>
                <div className="grid grid-cols-3 gap-2.5 text-xs">
                  <div className="p-3 rounded-xl bg-bg border border-border">
                    <div className="text-[10px] text-text3 uppercase font-semibold">Written Works</div>
                    <div className="font-bold text-text font-mono mt-0.5">25% Weight</div>
                    <div className="text-[11px] text-text2 mt-1">Quizzes & Summaries</div>
                  </div>
                  <div className="p-3 rounded-xl bg-bg border border-border">
                    <div className="text-[10px] text-text3 uppercase font-semibold">Performance Tasks</div>
                    <div className="font-bold text-text font-mono mt-0.5">50% Weight</div>
                    <div className="text-[11px] text-text2 mt-1">Demos & Lab Outputs</div>
                  </div>
                  <div className="p-3 rounded-xl bg-bg border border-border">
                    <div className="text-[10px] text-text3 uppercase font-semibold">Quarterly Exam</div>
                    <div className="font-bold text-text font-mono mt-0.5">25% Weight</div>
                    <div className="text-[11px] text-text2 mt-1">Periodical Examination</div>
                  </div>
                </div>
              </div>

              {/* Teacher Remarks */}
              {activeModalSubject.remarks && (
                <div className="p-3.5 rounded-xl bg-accent/5 border border-accent/20 text-xs">
                  <div className="font-semibold text-accent flex items-center gap-1.5">
                    <RiUserLine className="size-3.5" />
                    <span>Instructor Evaluation ({activeModalSubject.instructor})</span>
                  </div>
                  <p className="text-text2 italic mt-1 leading-relaxed">
                    "{activeModalSubject.remarks}"
                  </p>
                </div>
              )}

              {/* Quiz Breakdown List (Matching student_grades.php line 802-817) */}
              <div className="space-y-2.5">
                <div className="text-xs font-bold text-text3 uppercase tracking-wider">
                  Quiz & Assessment History ({activeModalSubject.quizzes.length} Items)
                </div>

                <div className="space-y-2">
                  {activeModalSubject.quizzes.map((q, idx) => (
                    <div
                      key={q.id}
                      className="p-3 rounded-xl bg-bg border border-border hover:border-border2 transition-colors space-y-1.5"
                    >
                      <div className="flex items-center justify-between text-xs">
                        <span className="font-bold text-text">
                          {idx + 1}. {q.quizTitle}
                        </span>
                        <span className={`font-mono font-bold ${getGradeColor(q.percentage)}`}>
                          {q.percentage}%
                        </span>
                      </div>

                      <div className="h-1.5 w-full bg-bg3 rounded-full overflow-hidden">
                        <div
                          className={`h-full rounded-full transition-all duration-300 ${getProgressBarClass(q.percentage)}`}
                          style={{ width: `${q.percentage}%` }}
                        />
                      </div>

                      <div className="flex items-center justify-between text-[11px] text-text3">
                        <span>
                          {q.score}/{q.maxScore} pts · {q.lessonTitle}
                        </span>
                        <span>Taken: {q.dateTaken}</span>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            </div>

            {/* Modal Footer */}
            <div className="p-3 sm:p-4 bg-bg3/50 border-t border-border flex items-center justify-between">
              <span className="text-[11px] text-text3">
                Last recorded activity: {activeModalSubject.lastActivity}
              </span>
              <button
                type="button"
                onClick={() => setActiveModalSubject(null)}
                className="btn btn-secondary btn-sm text-xs cursor-pointer px-4"
              >
                Close
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Toast Notification Simulation */}
      {showToast && (
        <div className="fixed bottom-6 right-6 z-50 p-4 rounded-xl bg-bg2 border border-accent/40 shadow-2xl text-xs text-text flex items-center gap-3 animate-in slide-in-from-bottom duration-200">
          <div className="size-2 rounded-full bg-accent animate-ping" />
          <span>{showToast}</span>
        </div>
      )}
    </div>
  );
};

export default StudentGradesView;
