import { useState } from 'react';
import type { StudentSubmission } from '@/types';
import { INITIAL_SUBMISSIONS, STUDENT_DIRECTORY } from '@/data/mockData';
import { AboutView } from '@/components/views/AboutView';
import { TeacherProfileView } from '@/components/views/TeacherProfileView';
import { TeacherSettingsView } from '@/components/views/TeacherSettingsView';
import { StudentClassesView } from '@/components/views/StudentClassesView';
import {
  RiVideoChatLine,
  RiCheckLine,
  RiAddLine,
  RiFilePdfLine,
  RiNotification3Line,
  RiUpload2Line,
  RiCloseLine,
  RiTimeLine,
  RiFileExcel2Line,
  RiBookOpenLine,
  RiUserStarLine,
  RiFileList3Line,
  RiArrowRightLine,
  RiEditLine,
  RiBarChartLine,
  RiCheckboxCircleLine,
  RiFileTextLine,
  RiQuestionLine,
  RiCalendarEventLine,
  RiStackLine,
  RiMegaphoneLine,
  RiArrowLeftSLine,
  RiArrowRightSLine,
  RiLiveLine,
  RiSparklingLine,
  RiAttachmentLine,
  RiErrorWarningLine,
} from '@remixicon/react';

interface TeacherClassHandled {
  id: string;
  code: string;
  title: string;
  section: string;
  schedule: string;
  room: string;
  students: number;
  completedModules: number;
  totalModules: number;
  progress: number;
  avgGrade: number;
  ungradedCount: number;
  isLiveNow: boolean;
  nextTopic: string;
  accentColor: 'blue' | 'emerald' | 'indigo' | 'purple' | 'cyan' | 'amber';
}

const TEACHER_CLASSES_HANDLED: TeacherClassHandled[] = [
  {
    id: 'tc-1',
    code: 'ORAL-COMM',
    title: 'Oral Communication in Context',
    section: 'STEM 11-A',
    schedule: 'MWF 08:00–09:00 AM',
    room: 'Room 302',
    students: 38,
    completedModules: 8,
    totalModules: 10,
    progress: 80,
    avgGrade: 91.2,
    ungradedCount: 3,
    isLiveNow: true,
    nextTopic: 'Module 9: Extemporaneous & Impromptu Speech Delivery',
    accentColor: 'blue',
  },
  {
    id: 'tc-2',
    code: 'GEN-MATH',
    title: 'General Mathematics',
    section: 'STEM 11-A',
    schedule: 'TTh 09:30–11:00 AM',
    room: 'Math Lab 2',
    students: 42,
    completedModules: 9,
    totalModules: 10,
    progress: 90,
    avgGrade: 89.8,
    ungradedCount: 2,
    isLiveNow: false,
    nextTopic: 'Module 10: Logic, Propositions & Truth Tables',
    accentColor: 'emerald',
  },
  {
    id: 'tc-3',
    code: 'ORAL-COMM',
    title: 'Oral Communication in Context',
    section: 'HUMSS 11-A',
    schedule: 'MWF 10:00–11:00 AM',
    room: 'Room 204',
    students: 36,
    completedModules: 8,
    totalModules: 10,
    progress: 80,
    avgGrade: 90.5,
    ungradedCount: 1,
    isLiveNow: false,
    nextTopic: 'Module 9: Speech Acts & Communicative Competence',
    accentColor: 'indigo',
  },
  {
    id: 'tc-4',
    code: 'EARTH-SCI',
    title: 'Earth and Life Science',
    section: 'ABM 11-B',
    schedule: 'MWF 01:00–02:00 PM',
    room: 'SciLab 1',
    students: 35,
    completedModules: 6,
    totalModules: 10,
    progress: 60,
    avgGrade: 88.4,
    ungradedCount: 0,
    isLiveNow: false,
    nextTopic: 'Module 7: Endogenic & Exogenic Geologic Processes',
    accentColor: 'purple',
  },
  {
    id: 'tc-5',
    code: 'EMP-TECH',
    title: 'Empowerment Technologies (ICT)',
    section: 'ICT 11-A',
    schedule: 'TTh 02:00–03:30 PM',
    room: 'CompLab 3',
    students: 27,
    completedModules: 7,
    totalModules: 10,
    progress: 70,
    avgGrade: 92.5,
    ungradedCount: 2,
    isLiveNow: false,
    nextTopic: 'Module 8: Interactive Multimedia & Web Portals',
    accentColor: 'cyan',
  },
  {
    id: 'tc-6',
    code: 'GEN-MATH',
    title: 'General Mathematics',
    section: 'ABM 11-B',
    schedule: 'TTh 04:00–05:00 PM',
    room: 'Room 305',
    students: 34,
    completedModules: 8,
    totalModules: 10,
    progress: 80,
    avgGrade: 88.9,
    ungradedCount: 0,
    isLiveNow: false,
    nextTopic: 'Module 9: Simple & Compound Interest Annuities',
    accentColor: 'amber',
  },
];

const TODAY_TEACHING_SCHEDULE = [
  {
    time: '08:00–09:00 AM',
    subject: 'Oral Communication in Context',
    code: 'ORAL-COMM',
    section: 'STEM 11-A',
    room: 'Room 302',
    attendance: '36/38 Present',
    status: 'live',
  },
  {
    time: '10:00–11:00 AM',
    subject: 'Oral Communication in Context',
    code: 'ORAL-COMM',
    section: 'HUMSS 11-A',
    room: 'Room 204',
    attendance: '35/36 Expected',
    status: 'next',
  },
  {
    time: '01:00–02:00 PM',
    subject: 'Earth and Life Science',
    code: 'EARTH-SCI',
    section: 'ABM 11-B',
    room: 'SciLab 1',
    attendance: '35 Learners',
    status: 'upcoming',
  },
  {
    time: '02:00–03:30 PM',
    subject: 'Empowerment Technologies (ICT)',
    code: 'EMP-TECH',
    section: 'ICT 11-A',
    room: 'CompLab 3',
    attendance: '27 Learners',
    status: 'upcoming',
  },
];

const RECENT_LESSONS = [
  { title: 'Functions and Their Graphs', subj: 'General Mathematics', status: 'published', duration: '45 min', date: 'May 14' },
  { title: 'Principles of Effective Speech Delivery', subj: 'Oral Communication', status: 'published', duration: '60 min', date: 'May 12' },
  { title: 'The Universe and Solar System Exploration', subj: 'Earth and Life Science', status: 'draft', duration: '35 min', date: 'May 10' },
  { title: 'ICT and Society: The Online Collaborative Toolset', subj: 'Empowerment Tech', status: 'published', duration: '50 min', date: 'May 08' },
  { title: 'Rational Equations and Inequalities Practice', subj: 'General Mathematics', status: 'published', duration: '40 min', date: 'May 05' },
];

const RECENT_QUIZZES = [
  { title: 'Module 4: Rational Functions Mastery Test', subj: 'General Mathematics', qCount: 15, status: 'published', due: 'May 20' },
  { title: 'Speech Delivery Assessment & Self-Reflection', subj: 'Oral Communication', qCount: 10, status: 'published', due: 'May 18' },
  { title: 'Biogeochemical Cycles Diagnostic Quiz', subj: 'Earth & Life Science', qCount: 20, status: 'draft', due: 'May 25' },
  { title: 'Web Design Principles & UI Patterns Quiz', subj: 'Empowerment Tech', qCount: 15, status: 'published', due: 'May 22' },
  { title: 'Barayti at Rehistro ng Wika Pagsusulit', subj: 'Komunikasyon', qCount: 12, status: 'published', due: 'May 16' },
];

const SECTION_MASTERY_LEADERBOARD = [
  { rank: 1, section: 'ICT 11-A', subject: 'Empowerment Technologies', average: 92.5, color: 'bg-cyan-500', students: 27 },
  { rank: 2, section: 'STEM 11-A', subject: 'Oral Communication', average: 91.2, color: 'bg-blue-500', students: 38 },
  { rank: 3, section: 'HUMSS 11-A', subject: 'Oral Communication', average: 90.5, color: 'bg-indigo-500', students: 36 },
  { rank: 4, section: 'STEM 11-A', subject: 'General Mathematics', average: 89.8, color: 'bg-emerald-500', students: 42 },
  { rank: 5, section: 'ABM 11-B', subject: 'General Mathematics', average: 88.9, color: 'bg-amber-500', students: 34 },
  { rank: 6, section: 'ABM 11-B', subject: 'Earth and Life Science', average: 88.4, color: 'bg-purple-500', students: 35 },
];

const AT_RISK_STUDENTS = [
  {
    id: 'ar-1',
    name: 'Gabriel Angelo Cruz',
    section: 'ICT 11-A',
    subject: 'Empowerment Technologies',
    issue: 'Missing 2 Performance Tasks',
    standing: '74.5%',
    riskLevel: 'high',
  },
  {
    id: 'ar-2',
    name: 'Carlo Santos',
    section: 'ABM 11-B',
    subject: 'Oral Communication',
    issue: 'Overdue Speech Critique Essay',
    standing: '78.2%',
    riskLevel: 'medium',
  },
  {
    id: 'ar-3',
    name: 'Daniel Padilla Jr.',
    section: 'STEM 11-A',
    subject: 'General Mathematics',
    issue: 'Incomplete Module 8 Problem Set',
    standing: '77.0%',
    riskLevel: 'medium',
  },
];

const getSubmissionSection = (sub: StudentSubmission): string => {
  const match = STUDENT_DIRECTORY.find((s) => s.name.toLowerCase() === sub.studentName.toLowerCase());
  if (match) {
    if (match.section.startsWith('STEM')) return 'STEM 11-A';
    if (match.section.startsWith('ABM')) return 'ABM 11-B';
    if (match.section.startsWith('ICT') || match.section.startsWith('TVL')) return 'ICT 11-A';
    return match.section;
  }
  if (sub.fileAttachment?.includes('STEM11A') || sub.studentName.includes('Donceras') || sub.studentName.includes('Lotino')) {
    return 'STEM 11-A';
  }
  if (sub.studentName.includes('Mendoza') || sub.studentName.includes('Santos')) {
    return 'ABM 11-B';
  }
  return 'STEM 11-A';
};

interface TeacherDashboardProps {
  activeTab: string;
  onTabChange: (tab: string) => void;
}

export function TeacherDashboard({ activeTab, onTabChange }: TeacherDashboardProps) {
  const [submissions, setSubmissions] = useState<StudentSubmission[]>(INITIAL_SUBMISSIONS);
  const [gradingScores, setGradingScores] = useState<Record<string, number>>({
    'subm-1': 95,
    'subm-2': 90,
    'subm-3': 46,
  });
  const [feedbackRemarks, setFeedbackRemarks] = useState<Record<string, string>>({
    'subm-1': 'Excellent vocal modulation and authoritative presence.',
  });

  // Teacher Dashboard Overhaul state
  const [dashboardSide, setDashboardSide] = useState<'a' | 'b'>('a');
  const [classFilter, setClassFilter] = useState<'ALL' | 'STEM 11-A' | 'ABM 11-B' | 'HUMSS 11-A' | 'ICT 11-A'>('ALL');
  const [curriculumTab, setCurriculumTab] = useState<'lessons' | 'quizzes'>('lessons');
  const [gradingSectionFilter, setGradingSectionFilter] = useState<'ALL' | 'STEM 11-A' | 'ABM 11-B' | 'ICT 11-A'>('ALL');
  const [kpiFilter, setKpiFilter] = useState<'all' | 'pending' | 'gpa' | 'turnin' | 'watchlist'>('all');
  const [isQuickActionsOpen, setIsQuickActionsOpen] = useState(false);
  const [noticeIdx, setNoticeIdx] = useState(0);

  // Modal states
  const [isCreateQuizOpen, setIsCreateQuizOpen] = useState(false);
  const [isCreateTaskOpen, setIsCreateTaskOpen] = useState(false);
  const [isAddCourseOpen, setIsAddCourseOpen] = useState(false);
  const [gradingModalSubmission, setGradingModalSubmission] = useState<StudentSubmission | null>(null);

  // Create Quiz form state (matching teacher_quizzes.php)
  const [quizLesson, setQuizLesson] = useState('Oral Communication — Module 5');
  const [quizDesc, setQuizDesc] = useState('');
  const [quizStatus, setQuizStatus] = useState('published');
  const [quizDuration, setQuizDuration] = useState(30);
  const [quizPassing, setQuizPassing] = useState(75);
  const [quizDue, setQuizDue] = useState('2026-11-20T23:59');
  const [quizQuestions, setQuizQuestions] = useState([
    {
      q: 'Which speech style is used between people with very close personal relationships?',
      optA: 'Intimate',
      optB: 'Formal',
      optC: 'Frozen',
      optD: 'Consultative',
      answer: 'A',
    },
  ]);

  // Create Task form state (matching teacher_performance_tasks.php)
  const [taskSubject, setTaskSubject] = useState('Oral Communication in Context');
  const [taskTitle, setTaskTitle] = useState('');
  const [taskInstructions, setTaskInstructions] = useState('');
  const [taskMaxScore, setTaskMaxScore] = useState(100);
  const [taskDue, setTaskDue] = useState('');

  // Announcements state
  const [announcements, setAnnouncements] = useState([
    {
      id: 'ann-1',
      title: 'Midterm Speech Video Submission Deadline Extended',
      category: 'Exam Alert',
      section: 'STEM 11-A & 11-B',
      date: 'Today, 9:00 AM',
      body: 'Students who experienced technical difficulties uploading to Google Drive/ExceLearn have until tomorrow 5:00 PM.',
    },
  ]);
  const [isPostAnnounceOpen, setIsPostAnnounceOpen] = useState(false);
  const [newTitle, setNewTitle] = useState('');
  const [newBody, setNewBody] = useState('');
  const [newCategory, setNewCategory] = useState('Rubric Update');
  const [newSection, setNewSection] = useState('STEM 11-A');

  const pendingSubmissions = submissions.filter((s) => s.status === 'pending');

  const handleGradeSubmission = (id: string) => {
    const score = gradingScores[id] ?? 90;
    setSubmissions((prev) =>
      prev.map((s) => (s.id === id ? { ...s, status: 'graded', score } : s))
    );
    setGradingModalSubmission(null);
  };

  const handlePostAnnouncement = (e: React.FormEvent) => {
    e.preventDefault();
    if (!newTitle.trim()) return;
    setAnnouncements((prev) => [
      {
        id: `ann-${Date.now()}`,
        title: newTitle,
        category: newCategory,
        section: newSection,
        date: 'Just now',
        body: newBody,
      },
      ...prev,
    ]);
    setNewTitle('');
    setNewBody('');
  };

  const handleCreateQuiz = (e: React.FormEvent) => {
    e.preventDefault();
    alert(`Quiz for "${quizLesson}" successfully created with ${quizQuestions.length} questions!`);
    setIsCreateQuizOpen(false);
  };

  const handleCreateTask = (e: React.FormEvent) => {
    e.preventDefault();
    alert(`Performance Task "${taskTitle}" successfully created for ${taskSubject}!`);
    setIsCreateTaskOpen(false);
    setTaskTitle('');
    setTaskInstructions('');
  };

  return (
    <div className="space-y-6">
      {/* ============================================================ */}
      {/* 1. TEACHER DASHBOARD OVERHAUL (Side A & Side B Flow)        */}
      {/* ============================================================ */}
      {activeTab === 'dashboard' && (
        <div className="space-y-6">
          {/* Faculty Profile Header Card + Side A / Side B Switcher */}
          <div className="rounded-2xl bg-bg2 border border-border p-4 sm:p-5 flex flex-col md:flex-row md:items-center justify-between gap-4 shadow-xs">
            {/* Left: Avatar + Name + Badges + Subtitle + Live status */}
            <div className="flex items-center gap-3.5 sm:gap-4 min-w-0">
              <div className="relative size-16 sm:size-18 rounded-full shrink-0 ring-4 ring-bg3 shadow-md overflow-hidden bg-gradient-to-br from-blue-600 via-indigo-600 to-accent flex items-center justify-center text-white select-none">
                <span className="text-xl sm:text-2xl font-bold tracking-tight">JV</span>
                <div className="absolute bottom-0 inset-x-0 bg-black/40 backdrop-blur-xs py-0.5 flex items-center justify-center">
                  <RiUserStarLine className="size-3 sm:size-3.5 text-amber-300" aria-hidden="true" />
                </div>
              </div>
              <div className="min-w-0">
                <div className="flex flex-wrap items-center gap-2 sm:gap-3">
                  <h1 className="font-serif text-2xl sm:text-3xl font-bold tracking-tight text-text">
                    Ms. Juna Velarde, LPT
                  </h1>
                  <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-accent/15 text-accent border border-accent/25">
                    4 Subjects · 6 Class Sections
                  </span>
                </div>
                <div className="text-xs sm:text-sm text-text2 mt-1 flex flex-wrap items-center gap-2">
                  <span>Senior High Faculty · Adviser: STEM 11-A · PRC #0948210</span>
                  <span className="size-1 rounded-full bg-border" aria-hidden="true" />
                  <span className="inline-flex items-center gap-1.5 text-[11px] font-semibold text-emerald-500 dark:text-emerald-400">
                    <span className="size-2 rounded-full bg-emerald-500 animate-pulse" aria-hidden="true" />
                    Live Class Ready: Room 302
                  </span>
                </div>
              </div>
            </div>

            {/* Right: Side A / Side B Switcher */}
            <div className="flex items-center gap-2 shrink-0">
              <div className="inline-flex items-center p-1 rounded-xl bg-bg3 border border-border shadow-xs w-full sm:w-auto">
                <button
                  type="button"
                  onClick={() => setDashboardSide('a')}
                  className={`flex-1 sm:flex-initial inline-flex items-center justify-center gap-2 px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all cursor-pointer ${
                    dashboardSide === 'a'
                      ? 'bg-bg2 text-accent shadow-xs border border-border/60'
                      : 'text-text2 hover:text-text'
                  }`}
                  aria-pressed={dashboardSide === 'a'}
                >
                  <RiStackLine className="size-3.5" aria-hidden="true" />
                  <span>Side A: Subjects & Classes</span>
                </button>
                <button
                  type="button"
                  onClick={() => setDashboardSide('b')}
                  className={`flex-1 sm:flex-initial inline-flex items-center justify-center gap-2 px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all cursor-pointer ${
                    dashboardSide === 'b'
                      ? 'bg-bg2 text-accent shadow-xs border border-border/60'
                      : 'text-text2 hover:text-text'
                  }`}
                  aria-pressed={dashboardSide === 'b'}
                >
                  <RiBarChartLine className="size-3.5" aria-hidden="true" />
                  <span>Side B: Grading & Analytics</span>
                  {pendingSubmissions.length > 0 && (
                    <span className="ml-0.5 px-1.5 py-0.2 rounded-full text-[10px] font-bold bg-amber-500 text-white leading-none">
                      {pendingSubmissions.length}
                    </span>
                  )}
                </button>
              </div>
            </div>
          </div>

          {/* Compact Faculty Notice & Schedule Bar */}
          <div className="rounded-xl bg-bg2 border border-border p-3 sm:px-4 sm:py-2.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs">
            <div className="flex items-center gap-2.5 sm:gap-3 min-w-0 flex-1">
              <span className="inline-flex items-center gap-1 text-[11px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-accent/10 text-accent shrink-0">
                <RiMegaphoneLine className="size-3" aria-hidden="true" />
                <span>Notice</span>
              </span>
              {announcements.length > 0 && (
                <div className="min-w-0 flex-1 flex items-center gap-2">
                  <span className="text-[10px] font-bold uppercase px-1.5 py-0.5 rounded shrink-0 bg-red-500/10 text-red border border-red-500/20">
                    {announcements[noticeIdx % announcements.length]?.category || 'Alert'}
                  </span>
                  <button
                    type="button"
                    onClick={() => onTabChange('announcements')}
                    className="text-xs font-semibold text-text hover:text-accent truncate transition-colors text-left cursor-pointer"
                  >
                    {announcements[noticeIdx % announcements.length]?.title}
                  </button>
                </div>
              )}
            </div>

            <div className="flex items-center justify-between sm:justify-end gap-2 shrink-0">
              {announcements.length > 1 && (
                <div className="flex items-center gap-1 mr-1">
                  <button
                    type="button"
                    onClick={() => setNoticeIdx((prev) => (prev > 0 ? prev - 1 : announcements.length - 1))}
                    aria-label="Previous announcement"
                    className="p-1 rounded-md text-text3 hover:text-text hover:bg-bg3 cursor-pointer"
                  >
                    <RiArrowLeftSLine className="size-4" />
                  </button>
                  <button
                    type="button"
                    onClick={() => setNoticeIdx((prev) => (prev + 1) % announcements.length)}
                    aria-label="Next announcement"
                    className="p-1 rounded-md text-text3 hover:text-text hover:bg-bg3 cursor-pointer"
                  >
                    <RiArrowRightSLine className="size-4" />
                  </button>
                </div>
              )}
              <button
                type="button"
                onClick={() => onTabChange('announcements')}
                className="text-xs font-semibold text-accent hover:underline cursor-pointer"
              >
                View All Notices →
              </button>
              <button
                type="button"
                onClick={() => setIsPostAnnounceOpen(true)}
                className="btn btn-primary btn-sm text-xs cursor-pointer inline-flex items-center gap-1 ml-1"
              >
                <RiAddLine size={13} />
                <span>Post Notice</span>
              </button>
            </div>
          </div>

          {/* ============================================================ */}
          {/* SIDE A: SUBJECTS & CLASSES HANDLED OVERVIEW                  */}
          {/* ============================================================ */}
          {dashboardSide === 'a' && (
            <div className="space-y-6">
              {/* Row 1: Today's Live / Upcoming Teaching Strip + Section Filter Pills */}
              <div className="flex flex-col md:flex-row md:items-center justify-between gap-3 bg-bg2 p-3 sm:px-4 rounded-xl border border-border shadow-xs">
                {/* Left: Filter Pills */}
                <div className="flex items-center gap-1.5 flex-wrap">
                  <span className="text-xs font-semibold text-text3 mr-1">Section Filter:</span>
                  {(['ALL', 'STEM 11-A', 'ABM 11-B', 'HUMSS 11-A', 'ICT 11-A'] as const).map((sec) => {
                    const count = sec === 'ALL'
                      ? TEACHER_CLASSES_HANDLED.length
                      : TEACHER_CLASSES_HANDLED.filter((c) => c.section === sec).length;
                    return (
                      <button
                        key={sec}
                        type="button"
                        onClick={() => setClassFilter(sec)}
                        className={`px-2.5 py-1 rounded-lg text-xs font-semibold transition-all cursor-pointer ${
                          classFilter === sec
                            ? 'bg-accent text-white shadow-xs'
                            : 'bg-bg3 text-text2 hover:text-text border border-border'
                        }`}
                      >
                        {sec === 'ALL' ? `All Classes (${count})` : `${sec} (${count})`}
                      </button>
                    );
                  })}
                </div>

                {/* Right: Live class status pill + Launch button */}
                <div className="flex items-center gap-2 shrink-0">
                  <div className="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-500 text-xs font-semibold">
                    <span className="size-2 rounded-full bg-emerald-500 animate-pulse" />
                    <span>LIVE NOW: Oral Comm · STEM 11-A · Rm 302</span>
                  </div>
                  <button
                    type="button"
                    onClick={() => onTabChange('live')}
                    className="btn btn-primary btn-sm text-xs cursor-pointer inline-flex items-center gap-1"
                  >
                    <RiLiveLine size={14} />
                    <span>Launch Class →</span>
                  </button>
                </div>
              </div>

              {/* Row 2: Rich 2-Column Grid of Subjects & Classes Handled Cards */}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                {TEACHER_CLASSES_HANDLED
                  .filter((c) => classFilter === 'ALL' || c.section === classFilter)
                  .map((c) => {
                    const colorStyles = {
                      blue: {
                        border: 'hover:border-blue-500/50',
                        badge: 'bg-blue-500/10 text-blue-500 dark:text-blue-400 border-blue-500/20',
                        bar: 'bg-blue-500',
                      },
                      emerald: {
                        border: 'hover:border-emerald-500/50',
                        badge: 'bg-emerald-500/10 text-emerald-500 dark:text-emerald-400 border-emerald-500/20',
                        bar: 'bg-emerald-500',
                      },
                      indigo: {
                        border: 'hover:border-indigo-500/50',
                        badge: 'bg-indigo-500/10 text-indigo-500 dark:text-indigo-400 border-indigo-500/20',
                        bar: 'bg-indigo-500',
                      },
                      purple: {
                        border: 'hover:border-purple-500/50',
                        badge: 'bg-purple-500/10 text-purple-500 dark:text-purple-400 border-purple-500/20',
                        bar: 'bg-purple-500',
                      },
                      cyan: {
                        border: 'hover:border-cyan-500/50',
                        badge: 'bg-cyan-500/10 text-cyan-500 dark:text-cyan-400 border-cyan-500/20',
                        bar: 'bg-cyan-500',
                      },
                      amber: {
                        border: 'hover:border-amber-500/50',
                        badge: 'bg-amber-500/10 text-amber-500 dark:text-amber-400 border-amber-500/20',
                        bar: 'bg-amber-500',
                      },
                    }[c.accentColor];

                    return (
                      <div
                        key={c.id}
                        className={`card p-4 sm:p-5 rounded-2xl bg-bg2 border border-border shadow-xs hover:shadow-md transition-all flex flex-col justify-between gap-4 ${colorStyles.border}`}
                      >
                        <div className="space-y-3">
                          {/* Top Badges */}
                          <div className="flex items-center justify-between gap-2 flex-wrap">
                            <div className="flex items-center gap-2">
                              <span className={`text-[11px] font-bold uppercase tracking-wider px-2.5 py-0.5 rounded-full border ${colorStyles.badge}`}>
                                {c.code}
                              </span>
                              <span className="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-bg3 text-text2 border border-border">
                                {c.section}
                              </span>
                              {c.isLiveNow && (
                                <span className="inline-flex items-center gap-1 text-[10px] font-bold uppercase px-2 py-0.5 rounded-full bg-emerald-500/15 text-emerald-500 border border-emerald-500/30">
                                  <span className="size-1.5 rounded-full bg-emerald-500 animate-pulse" />
                                  LIVE
                                </span>
                              )}
                            </div>

                            {/* Right Status Pill */}
                            {c.ungradedCount > 0 ? (
                              <button
                                type="button"
                                onClick={() => setDashboardSide('b')}
                                className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-500/15 text-amber-500 border border-amber-500/30 hover:bg-amber-500/25 transition-colors cursor-pointer"
                                title="Click to view grading queue"
                              >
                                <RiTimeLine size={12} />
                                <span>{c.ungradedCount} to grade</span>
                              </button>
                            ) : (
                              <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-500/10 text-emerald-500 border border-emerald-500/20">
                                <RiCheckLine size={12} />
                                <span>All graded</span>
                              </span>
                            )}
                          </div>

                          {/* Title & Meta */}
                          <div>
                            <h3 className="font-serif text-base sm:text-lg font-bold text-text line-clamp-1">
                              {c.title}
                            </h3>
                            <p className="text-xs text-text3 mt-1 flex flex-wrap items-center gap-1.5">
                              <span>{c.schedule}</span>
                              <span>·</span>
                              <span>{c.room}</span>
                              <span>·</span>
                              <span className="font-medium text-text2">{c.students} Learners</span>
                              <span>·</span>
                              <span className="font-semibold text-emerald-500 dark:text-emerald-400">Class Avg {c.avgGrade}%</span>
                            </p>
                          </div>

                          {/* Next Lesson Strip */}
                          <div className="bg-bg3/60 px-3 py-1.5 rounded-xl text-xs text-text2 flex items-center gap-2 border border-border/50">
                            <span className="font-bold text-accent text-[11px] shrink-0">Next:</span>
                            <span className="truncate">{c.nextTopic}</span>
                          </div>

                          {/* Syllabus Progress Bar */}
                          <div className="space-y-1.5 pt-1">
                            <div className="flex items-center justify-between text-xs font-medium">
                              <span className="text-text3">Syllabus Progress</span>
                              <span className="text-text font-semibold">
                                {c.completedModules}/{c.totalModules} Modules ({c.progress}%)
                              </span>
                            </div>
                            <div className="h-2 w-full rounded-full bg-bg3 overflow-hidden">
                              <div
                                className={`h-full rounded-full transition-all duration-500 ${colorStyles.bar}`}
                                style={{ width: `${c.progress}%` }}
                              />
                            </div>
                          </div>
                        </div>

                        {/* Card Footer Actions */}
                        <div className="flex items-center justify-between gap-2 pt-2 border-t border-border/60">
                          <div className="flex items-center gap-1">
                            <button
                              type="button"
                              onClick={() => onTabChange('courses')}
                              className="px-2 py-1 rounded-lg text-xs font-semibold text-text2 hover:text-text hover:bg-bg3 transition-colors cursor-pointer"
                              title="Modules & Syllabus"
                            >
                              Modules
                            </button>
                            <button
                              type="button"
                              onClick={() => onTabChange('grades')}
                              className="px-2 py-1 rounded-lg text-xs font-semibold text-text2 hover:text-text hover:bg-bg3 transition-colors cursor-pointer"
                              title="Section Gradebook"
                            >
                              Gradebook
                            </button>
                            <button
                              type="button"
                              onClick={() => onTabChange('students')}
                              className="px-2 py-1 rounded-lg text-xs font-semibold text-text2 hover:text-text hover:bg-bg3 transition-colors cursor-pointer"
                              title="Class Roster"
                            >
                              Roster
                            </button>
                          </div>
                          <button
                            type="button"
                            onClick={() => onTabChange(c.isLiveNow ? 'live' : 'courses')}
                            className={`btn btn-sm text-xs cursor-pointer inline-flex items-center gap-1 ${
                              c.isLiveNow ? 'btn-primary' : 'bg-bg3 hover:bg-bg4 text-text border border-border'
                            }`}
                          >
                            <span>{c.isLiveNow ? 'Join Live' : 'Open Class'}</span>
                            <RiArrowRightLine size={13} />
                          </button>
                        </div>
                      </div>
                    );
                  })}
              </div>

              {/* Row 3: Unified Tabbed Recent Curriculum & Today's Teaching Timeline */}
              <div className="grid grid-cols-1 lg:grid-cols-12 gap-4">
                {/* Left (7 cols): Recent Curriculum Items */}
                <div className="lg:col-span-7 card p-4 sm:p-5 rounded-2xl bg-bg2 border border-border shadow-xs space-y-4">
                  <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-1 border-b border-border/60">
                    <div className="flex items-center gap-2">
                      <div className="p-1 rounded-lg bg-bg3 border border-border inline-flex items-center">
                        <button
                          type="button"
                          onClick={() => setCurriculumTab('lessons')}
                          className={`px-3 py-1 rounded-md text-xs font-semibold transition-all cursor-pointer ${
                            curriculumTab === 'lessons'
                              ? 'bg-bg2 text-accent shadow-xs'
                              : 'text-text2 hover:text-text'
                          }`}
                        >
                          Recent Lessons ({RECENT_LESSONS.length})
                        </button>
                        <button
                          type="button"
                          onClick={() => setCurriculumTab('quizzes')}
                          className={`px-3 py-1 rounded-md text-xs font-semibold transition-all cursor-pointer ${
                            curriculumTab === 'quizzes'
                              ? 'bg-bg2 text-accent shadow-xs'
                              : 'text-text2 hover:text-text'
                          }`}
                        >
                          Recent Quizzes ({RECENT_QUIZZES.length})
                        </button>
                      </div>
                    </div>

                    <div className="flex items-center gap-2">
                      <button
                        type="button"
                        onClick={() => {
                          if (curriculumTab === 'lessons') setIsAddCourseOpen(true);
                          else setIsCreateQuizOpen(true);
                        }}
                        className="btn btn-primary btn-sm text-xs cursor-pointer inline-flex items-center gap-1"
                      >
                        <RiAddLine size={13} />
                        <span>{curriculumTab === 'lessons' ? 'Add Lesson' : 'Add Quiz'}</span>
                      </button>
                      <button
                        type="button"
                        onClick={() => onTabChange(curriculumTab === 'lessons' ? 'courses' : 'tasks')}
                        className="text-xs font-semibold text-accent hover:underline cursor-pointer inline-flex items-center gap-0.5"
                      >
                        <span>View all</span>
                        <RiArrowRightLine size={12} />
                      </button>
                    </div>
                  </div>

                  {curriculumTab === 'lessons' ? (
                    <div className="space-y-1.5">
                      {RECENT_LESSONS.map((l, idx) => (
                        <div
                          key={idx}
                          className="flex items-center gap-3 p-2.5 rounded-xl hover:bg-bg3/60 transition-colors"
                        >
                          <div
                            className={`size-8 rounded-lg flex items-center justify-center shrink-0 ${
                              l.status === 'published' ? 'bg-emerald-500/10 text-emerald-500' : 'bg-amber-500/10 text-amber-500'
                            }`}
                          >
                            <RiFileTextLine size={16} />
                          </div>
                          <div className="flex-1 min-w-0">
                            <div className="text-xs font-semibold text-text truncate">{l.title}</div>
                            <div className="text-[11px] text-text3 font-medium">
                              {l.subj} · {l.duration} · {l.date}
                            </div>
                          </div>
                          <span
                            className={`text-[10px] font-semibold px-2 py-0.5 rounded-full ${
                              l.status === 'published'
                                ? 'bg-emerald-500/10 text-emerald-500 border border-emerald-500/20'
                                : 'bg-amber-500/10 text-amber border border-amber-500/20'
                            }`}
                          >
                            {l.status === 'published' ? 'Published' : 'Draft'}
                          </span>
                        </div>
                      ))}
                    </div>
                  ) : (
                    <div className="space-y-1.5">
                      {RECENT_QUIZZES.map((q, idx) => (
                        <div
                          key={idx}
                          className="flex items-center gap-3 p-2.5 rounded-xl hover:bg-bg3/60 transition-colors"
                        >
                          <div
                            className={`size-8 rounded-lg flex items-center justify-center shrink-0 ${
                              q.status === 'published' ? 'bg-purple-500/10 text-purple-500' : 'bg-amber-500/10 text-amber-500'
                            }`}
                          >
                            <RiQuestionLine size={16} />
                          </div>
                          <div className="flex-1 min-w-0">
                            <div className="text-xs font-semibold text-text truncate">{q.title}</div>
                            <div className="text-[11px] text-text3 font-medium">
                              {q.subj} · {q.qCount} Questions · Due: {q.due}
                            </div>
                          </div>
                          <span
                            className={`text-[10px] font-semibold px-2 py-0.5 rounded-full ${
                              q.status === 'published'
                                ? 'bg-purple-500/10 text-purple border border-purple-500/20'
                                : 'bg-amber-500/10 text-amber border border-amber-500/20'
                            }`}
                          >
                            {q.status === 'published' ? 'Published' : 'Draft'}
                          </span>
                        </div>
                      ))}
                    </div>
                  )}
                </div>

                {/* Right (5 cols): Today's Class Schedule & Room Timeline */}
                <div className="lg:col-span-5 card p-4 sm:p-5 rounded-2xl bg-bg2 border border-border shadow-xs space-y-4">
                  <div className="flex items-center justify-between gap-2 pb-1 border-b border-border/60">
                    <div className="flex items-center gap-2">
                      <div className="p-1.5 rounded-lg bg-accent/10 text-accent">
                        <RiCalendarEventLine size={18} />
                      </div>
                      <h4 className="font-serif text-sm font-bold text-text">Today's Class Schedule</h4>
                    </div>
                    <button
                      type="button"
                      onClick={() => onTabChange('live')}
                      className="text-xs font-semibold text-accent hover:underline cursor-pointer inline-flex items-center gap-0.5"
                    >
                      <span>Virtual Room</span>
                      <RiArrowRightLine size={12} />
                    </button>
                  </div>

                  <div className="space-y-3">
                    {TODAY_TEACHING_SCHEDULE.map((item, idx) => (
                      <div
                        key={idx}
                        className={`p-3 rounded-xl border transition-all flex items-start justify-between gap-3 ${
                          item.status === 'live'
                            ? 'bg-emerald-500/5 border-emerald-500/30'
                            : 'bg-bg3/40 border-border/60 hover:bg-bg3/70'
                        }`}
                      >
                        <div className="space-y-1 min-w-0 flex-1">
                          <div className="flex items-center gap-2 flex-wrap">
                            <span className="text-[11px] font-mono font-bold text-accent">
                              {item.time}
                            </span>
                            <span className="text-[10px] font-bold uppercase px-2 py-0.2 rounded-full bg-bg3 text-text2 border border-border">
                              {item.section}
                            </span>
                            {item.status === 'live' && (
                              <span className="inline-flex items-center gap-1 text-[10px] font-bold uppercase px-1.5 py-0.2 rounded-full bg-emerald-500/15 text-emerald-500 border border-emerald-500/30">
                                <span className="size-1.5 rounded-full bg-emerald-500 animate-pulse" />
                                In Session
                              </span>
                            )}
                          </div>
                          <div className="text-xs font-bold text-text truncate">
                            {item.subject}
                          </div>
                          <div className="text-[11px] text-text3 flex items-center gap-2">
                            <span>{item.room}</span>
                            <span>·</span>
                            <span className="text-text2 font-medium">{item.attendance}</span>
                          </div>
                        </div>

                        {item.status === 'live' ? (
                          <button
                            type="button"
                            onClick={() => onTabChange('live')}
                            className="btn btn-primary btn-sm text-xs cursor-pointer inline-flex items-center gap-1 shrink-0 mt-1"
                          >
                            <RiVideoChatLine size={13} />
                            <span>Join</span>
                          </button>
                        ) : (
                          <span className="text-[11px] font-semibold text-text3 shrink-0 mt-1">
                            {item.status === 'next' ? 'Next Up' : 'Upcoming'}
                          </span>
                        )}
                      </div>
                    ))}
                  </div>
                </div>
              </div>
            </div>
          )}

          {/* ============================================================ */}
          {/* SIDE B: PRIORITY GRADING QUEUE & SECTION ANALYTICS           */}
          {/* ============================================================ */}
          {dashboardSide === 'b' && (
            <div className="space-y-6">
              {/* Row 1: 4 Actionable Interactive Faculty KPI Cards */}
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
                {/* KPI 1: Needs Grading */}
                <button
                  type="button"
                  onClick={() => {
                    setKpiFilter((prev) => (prev === 'pending' ? 'all' : 'pending'));
                    const el = document.getElementById('grading-queue-card');
                    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                  }}
                  className={`text-left w-full card p-4 rounded-2xl bg-bg2 border shadow-xs transition-all cursor-pointer group active:scale-[0.99] ${
                    kpiFilter === 'pending'
                      ? 'border-amber-500 ring-2 ring-amber-500/40 shadow-md bg-amber-500/5'
                      : 'border-border hover:border-amber-500/40 hover:shadow-md'
                  }`}
                  aria-label="Filter submissions needing grading"
                >
                  <div className="flex items-start justify-between gap-3">
                    <div className="space-y-1">
                      <div className="text-xs font-semibold text-text3 flex items-center gap-1.5">
                        <span>Needs Grading</span>
                        {kpiFilter === 'pending' && (
                          <span className="text-[10px] font-bold px-1.5 py-0.2 rounded-full bg-amber-500 text-slate-950 leading-none">
                            Filtering
                          </span>
                        )}
                      </div>
                      <div className="text-2xl font-bold font-serif text-text">
                        {pendingSubmissions.length} Submissions
                      </div>
                      <div className="text-[11px] font-medium text-amber-500 flex items-center gap-1">
                        <span>{kpiFilter === 'pending' ? 'Active filter: Showing 3 urgent' : 'Urgent queue · Click to filter'}</span>
                      </div>
                    </div>
                    <div
                      className={`size-10 rounded-xl flex items-center justify-center shrink-0 transition-all ${
                        kpiFilter === 'pending'
                          ? 'bg-amber-500 text-slate-950 font-bold shadow-xs'
                          : 'bg-amber-500/10 text-amber-500 group-hover:scale-105'
                      }`}
                    >
                      <RiTimeLine size={20} />
                    </div>
                  </div>
                  <div className="mt-2.5 pt-2 border-t border-border/60 flex items-center justify-between text-[11px] font-semibold text-amber-500">
                    <span>{kpiFilter === 'pending' ? 'Click to reset filter' : 'Filter pending queue'}</span>
                    <span className="inline-flex items-center gap-0.5 group-hover:translate-x-0.5 transition-transform">
                      <span>{kpiFilter === 'pending' ? 'Reset' : 'View'}</span>
                      <RiArrowRightLine size={12} />
                    </span>
                  </div>
                </button>

                {/* KPI 2: Sections GPA Average */}
                <button
                  type="button"
                  onClick={() => {
                    setKpiFilter((prev) => (prev === 'gpa' ? 'all' : 'gpa'));
                    const el = document.getElementById('section-mastery-card');
                    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                  }}
                  className={`text-left w-full card p-4 rounded-2xl bg-bg2 border shadow-xs transition-all cursor-pointer group active:scale-[0.99] ${
                    kpiFilter === 'gpa'
                      ? 'border-emerald-500 ring-2 ring-emerald-500/40 shadow-md bg-emerald-500/5'
                      : 'border-border hover:border-emerald-500/40 hover:shadow-md'
                  }`}
                  aria-label="View section GPA averages"
                >
                  <div className="flex items-start justify-between gap-3">
                    <div className="space-y-1">
                      <div className="text-xs font-semibold text-text3 flex items-center gap-1.5">
                        <span>Sections GPA Average</span>
                        {kpiFilter === 'gpa' && (
                          <span className="text-[10px] font-bold px-1.5 py-0.2 rounded-full bg-emerald-500 text-slate-950 leading-none">
                            Focused
                          </span>
                        )}
                      </div>
                      <div className="text-2xl font-bold font-serif text-text">90.4%</div>
                      <div className="text-[11px] font-medium text-emerald-500 flex items-center gap-1">
                        <span>+1.8% vs Midterms · Top: ICT 11-A</span>
                      </div>
                    </div>
                    <div
                      className={`size-10 rounded-xl flex items-center justify-center shrink-0 transition-all ${
                        kpiFilter === 'gpa'
                          ? 'bg-emerald-500 text-slate-950 font-bold shadow-xs'
                          : 'bg-emerald-500/10 text-emerald-500 group-hover:scale-105'
                      }`}
                    >
                      <RiBarChartLine size={20} />
                    </div>
                  </div>
                  <div className="mt-2.5 pt-2 border-t border-border/60 flex items-center justify-between text-[11px] font-semibold text-emerald-500">
                    <span>{kpiFilter === 'gpa' ? 'Highlighting section ranks' : 'View section rankings'}</span>
                    <span className="inline-flex items-center gap-0.5 group-hover:translate-x-0.5 transition-transform">
                      <span>{kpiFilter === 'gpa' ? 'Reset' : 'Jump'}</span>
                      <RiArrowRightLine size={12} />
                    </span>
                  </div>
                </button>

                {/* KPI 3: On-Time Turn-In Rate */}
                <button
                  type="button"
                  onClick={() => {
                    setKpiFilter((prev) => (prev === 'turnin' ? 'all' : 'turnin'));
                    const el = document.getElementById('grading-queue-card');
                    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                  }}
                  className={`text-left w-full card p-4 rounded-2xl bg-bg2 border shadow-xs transition-all cursor-pointer group active:scale-[0.99] ${
                    kpiFilter === 'turnin'
                      ? 'border-blue-500 ring-2 ring-blue-500/40 shadow-md bg-blue-500/5'
                      : 'border-border hover:border-blue-500/40 hover:shadow-md'
                  }`}
                  aria-label="Filter on-time turn-in submissions"
                >
                  <div className="flex items-start justify-between gap-3">
                    <div className="space-y-1">
                      <div className="text-xs font-semibold text-text3 flex items-center gap-1.5">
                        <span>On-Time Turn-In Rate</span>
                        {kpiFilter === 'turnin' && (
                          <span className="text-[10px] font-bold px-1.5 py-0.2 rounded-full bg-blue-500 text-white leading-none">
                            Filtering
                          </span>
                        )}
                      </div>
                      <div className="text-2xl font-bold font-serif text-text">94.2%</div>
                      <div className="text-[11px] font-medium text-blue-500 flex items-center gap-1">
                        <span>168 / 178 active learners</span>
                      </div>
                    </div>
                    <div
                      className={`size-10 rounded-xl flex items-center justify-center shrink-0 transition-all ${
                        kpiFilter === 'turnin'
                          ? 'bg-blue-500 text-white font-bold shadow-xs'
                          : 'bg-blue-500/10 text-blue-500 group-hover:scale-105'
                      }`}
                    >
                      <RiCheckboxCircleLine size={20} />
                    </div>
                  </div>
                  <div className="mt-2.5 pt-2 border-t border-border/60 flex items-center justify-between text-[11px] font-semibold text-blue-500">
                    <span>{kpiFilter === 'turnin' ? 'Showing on-time graded' : 'Filter on-time submissions'}</span>
                    <span className="inline-flex items-center gap-0.5 group-hover:translate-x-0.5 transition-transform">
                      <span>{kpiFilter === 'turnin' ? 'Reset' : 'View'}</span>
                      <RiArrowRightLine size={12} />
                    </span>
                  </div>
                </button>

                {/* KPI 4: Learner Watchlist */}
                <button
                  type="button"
                  onClick={() => {
                    setKpiFilter((prev) => (prev === 'watchlist' ? 'all' : 'watchlist'));
                    const el = document.getElementById('at-risk-watchlist-card');
                    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                  }}
                  className={`text-left w-full card p-4 rounded-2xl bg-bg2 border shadow-xs transition-all cursor-pointer group active:scale-[0.99] ${
                    kpiFilter === 'watchlist'
                      ? 'border-red-500 ring-2 ring-red-500/40 shadow-md bg-red-500/5'
                      : 'border-border hover:border-red-500/40 hover:shadow-md'
                  }`}
                  aria-label="View learner watchlist"
                >
                  <div className="flex items-start justify-between gap-3">
                    <div className="space-y-1">
                      <div className="text-xs font-semibold text-text3 flex items-center gap-1.5">
                        <span>Learner Watchlist</span>
                        {kpiFilter === 'watchlist' && (
                          <span className="text-[10px] font-bold px-1.5 py-0.2 rounded-full bg-red-500 text-white leading-none">
                            Focused
                          </span>
                        )}
                      </div>
                      <div className="text-2xl font-bold font-serif text-text">3 At-Risk</div>
                      <div className="text-[11px] font-medium text-red-500 flex items-center gap-1">
                        <span>Missing 2+ Performance Tasks</span>
                      </div>
                    </div>
                    <div
                      className={`size-10 rounded-xl flex items-center justify-center shrink-0 transition-all ${
                        kpiFilter === 'watchlist'
                          ? 'bg-red-500 text-white font-bold shadow-xs'
                          : 'bg-red-500/10 text-red-500 group-hover:scale-105'
                      }`}
                    >
                      <RiErrorWarningLine size={20} />
                    </div>
                  </div>
                  <div className="mt-2.5 pt-2 border-t border-border/60 flex items-center justify-between text-[11px] font-semibold text-red-500">
                    <span>{kpiFilter === 'watchlist' ? 'Highlighting 3 learners' : 'Jump to at-risk watchlist'}</span>
                    <span className="inline-flex items-center gap-0.5 group-hover:translate-x-0.5 transition-transform">
                      <span>{kpiFilter === 'watchlist' ? 'Reset' : 'Jump'}</span>
                      <RiArrowRightLine size={12} />
                    </span>
                  </div>
                </button>
              </div>

              {/* Row 2: 8:4 Bento (7 cols vs 5 cols) */}
              <div className="grid grid-cols-1 lg:grid-cols-12 gap-4">
                {/* Left (7 cols): Priority Grading & Submission Queue */}
                <div
                  id="grading-queue-card"
                  className="lg:col-span-7 card p-4 sm:p-5 rounded-2xl bg-bg2 border border-border shadow-xs space-y-4 scroll-mt-20"
                >
                  <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-1 border-b border-border/60">
                    <div>
                      <div className="flex items-center gap-2">
                        <div className="p-1.5 rounded-lg bg-amber-500/10 text-amber-500">
                          <RiFileList3Line size={18} />
                        </div>
                        <h4 className="font-serif text-base font-bold text-text">Priority Grading Queue</h4>
                      </div>
                      <p className="text-[11px] text-text3 mt-0.5">
                        Analytical rubric grading & qualitative feedback remarks
                      </p>
                    </div>

                    <div className="flex items-center gap-2">
                      <button
                        type="button"
                        onClick={() => onTabChange('tasks')}
                        className="text-xs font-semibold text-accent hover:underline cursor-pointer inline-flex items-center gap-0.5"
                      >
                        <span>Full Tasks Board</span>
                        <RiArrowRightLine size={12} />
                      </button>
                    </div>
                  </div>

                  {/* Active KPI Filter Banner */}
                  {kpiFilter !== 'all' && (
                    <div className="flex items-center justify-between px-3 py-1.5 rounded-xl bg-amber-500/10 border border-amber-500/25 text-xs text-text animate-in fade-in duration-150">
                      <div className="flex items-center gap-1.5">
                        <span className="size-2 rounded-full bg-amber-500 animate-pulse" />
                        <span className="font-semibold text-text">
                          {kpiFilter === 'pending' && `Active Filter: Needs Grading (${pendingSubmissions.length} submissions)`}
                          {kpiFilter === 'turnin' && 'Active Filter: Graded On-Time Submissions'}
                          {kpiFilter === 'gpa' && 'Jumped from: Sections GPA Average (90.4%)'}
                          {kpiFilter === 'watchlist' && 'Jumped from: Learner Watchlist (3 At-Risk)'}
                        </span>
                      </div>
                      <button
                        type="button"
                        onClick={() => setKpiFilter('all')}
                        className="text-xs font-bold text-amber-500 hover:underline cursor-pointer"
                      >
                        Reset Filter ✕
                      </button>
                    </div>
                  )}

                  {/* Section Filter Pills */}
                  <div className="flex items-center gap-1.5 flex-wrap">
                    <span className="text-xs font-semibold text-text3 mr-1">Filter Section:</span>
                    {(['ALL', 'STEM 11-A', 'ABM 11-B', 'ICT 11-A'] as const).map((sec) => (
                      <button
                        key={sec}
                        type="button"
                        onClick={() => setGradingSectionFilter(sec)}
                        className={`px-2.5 py-1 rounded-lg text-xs font-semibold transition-all cursor-pointer ${
                          gradingSectionFilter === sec
                            ? 'bg-amber-500 text-slate-950 shadow-xs'
                            : 'bg-bg3 text-text2 hover:text-text border border-border'
                        }`}
                      >
                        {sec}
                      </button>
                    ))}
                  </div>

                  {/* Submissions List */}
                  <div className="space-y-2.5">
                    {submissions
                      .filter((sub) => {
                        if (gradingSectionFilter !== 'ALL' && getSubmissionSection(sub) !== gradingSectionFilter) {
                          return false;
                        }
                        if (kpiFilter === 'pending') {
                          return sub.status === 'pending';
                        }
                        if (kpiFilter === 'turnin') {
                          return sub.status === 'graded';
                        }
                        return true;
                      })
                      .sort((a, b) => {
                        if (a.status === 'pending' && b.status !== 'pending') return -1;
                        if (a.status !== 'pending' && b.status === 'pending') return 1;
                        return 0;
                      })
                      .map((sub) => {
                        const sec = getSubmissionSection(sub);
                        return (
                          <div
                            key={sub.id}
                            className={`p-3 sm:p-3.5 rounded-xl border transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3 ${
                              sub.status === 'pending'
                                ? 'bg-amber-500/5 border-amber-500/30'
                                : 'bg-bg3/30 border-border/70 hover:bg-bg3/50'
                            }`}
                          >
                            <div className="space-y-1.5 min-w-0 flex-1">
                              <div className="flex items-center gap-2 flex-wrap">
                                <span className="font-bold text-xs text-text">{sub.studentName}</span>
                                <span className="text-[10px] font-bold px-2 py-0.2 rounded-full bg-bg3 text-text2 border border-border">
                                  {sec}
                                </span>
                                <span className="text-[10px] font-semibold px-2 py-0.2 rounded-full bg-accent/10 text-accent border border-accent/20">
                                  {sub.subject}
                                </span>
                                <span className="text-[11px] text-text3 ml-auto font-medium">
                                  {sub.submittedTime}
                                </span>
                              </div>
                              <div className="text-xs font-medium text-text2 truncate">
                                {sub.taskTitle}
                              </div>
                              <div className="flex items-center gap-2">
                                <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-bg3 text-text3 text-[11px] border border-border/50">
                                  <RiAttachmentLine size={12} />
                                  <span className="truncate max-w-[140px] sm:max-w-[220px]">{sub.fileAttachment}</span>
                                  <span>({sub.fileSize})</span>
                                </span>
                              </div>
                            </div>

                            {/* Action Button */}
                            <div className="shrink-0 flex items-center justify-end">
                              {sub.status === 'pending' ? (
                                <button
                                  type="button"
                                  onClick={() => setGradingModalSubmission(sub)}
                                  className="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs shadow-xs hover:shadow-md active:scale-95 transition-all cursor-pointer"
                                >
                                  <RiEditLine size={13} />
                                  <span>Grade Now</span>
                                </button>
                              ) : (
                                <div className="flex sm:flex-col items-center sm:items-end gap-1.5">
                                  <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 text-xs font-semibold">
                                    <RiCheckLine size={12} />
                                    <span>Graded · {sub.score}/{sub.maxScore}</span>
                                  </span>
                                  <button
                                    type="button"
                                    onClick={() => setGradingModalSubmission(sub)}
                                    className="text-[11px] font-semibold text-text2 hover:text-accent underline cursor-pointer"
                                  >
                                    Edit Score
                                  </button>
                                </div>
                              )}
                            </div>
                          </div>
                        );
                      })}
                  </div>
                </div>

                {/* Right (5 cols): Section Mastery Comparison & At-Risk Watchlist */}
                <div className="lg:col-span-5 space-y-4">
                  {/* Section Mastery Leaderboard */}
                  <div
                    id="section-mastery-card"
                    className={`card p-4 sm:p-5 rounded-2xl bg-bg2 border shadow-xs space-y-3.5 scroll-mt-20 transition-all ${
                      kpiFilter === 'gpa'
                        ? 'border-emerald-500 ring-2 ring-emerald-500/40 shadow-md bg-emerald-500/5'
                        : 'border-border'
                    }`}
                  >
                    <div className="flex items-center justify-between gap-2 pb-1 border-b border-border/60">
                      <div className="flex items-center gap-2">
                        <div className="p-1.5 rounded-lg bg-emerald-500/10 text-emerald-500">
                          <RiBarChartLine size={18} />
                        </div>
                        <h4 className="font-serif text-sm font-bold text-text">Section Mastery Comparison</h4>
                      </div>
                      <span className="text-[11px] font-semibold text-text3">Term Average</span>
                    </div>

                    <div className="space-y-2.5">
                      {SECTION_MASTERY_LEADERBOARD.map((item) => (
                        <div key={item.rank} className="space-y-1">
                          <div className="flex items-center justify-between text-xs">
                            <div className="flex items-center gap-1.5 truncate">
                              <span className="font-bold text-text3 text-[11px] w-4">#{item.rank}</span>
                              <span className="font-semibold text-text">{item.section}</span>
                              <span className="text-[11px] text-text3 truncate">({item.subject})</span>
                            </div>
                            <span className="font-bold text-text shrink-0">{item.average}%</span>
                          </div>
                          <div className="h-1.5 w-full rounded-full bg-bg3 overflow-hidden">
                            <div
                              className={`h-full rounded-full ${item.color}`}
                              style={{ width: `${item.average}%` }}
                            />
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>

                  {/* Early Intervention Watchlist */}
                  <div
                    id="at-risk-watchlist-card"
                    className={`card p-4 sm:p-5 rounded-2xl bg-bg2 border shadow-xs space-y-3.5 scroll-mt-20 transition-all ${
                      kpiFilter === 'watchlist'
                        ? 'border-red-500 ring-2 ring-red-500/40 shadow-md bg-red-500/5'
                        : 'border-border'
                    }`}
                  >
                    <div className="flex items-center justify-between gap-2 pb-1 border-b border-border/60">
                      <div className="flex items-center gap-2">
                        <div className="p-1.5 rounded-lg bg-red-500/10 text-red-500">
                          <RiErrorWarningLine size={18} />
                        </div>
                        <div>
                          <h4 className="font-serif text-sm font-bold text-text">Early Intervention Watchlist</h4>
                          <span className="text-[11px] text-text3">Learners requiring immediate follow-up</span>
                        </div>
                      </div>
                    </div>

                    <div className="space-y-2.5">
                      {AT_RISK_STUDENTS.map((st) => (
                        <div
                          key={st.id}
                          className="p-2.5 rounded-xl bg-bg3/40 border border-border/60 flex items-center justify-between gap-2 hover:bg-bg3/70 transition-colors"
                        >
                          <div className="space-y-0.5 min-w-0 flex-1">
                            <div className="flex items-center gap-1.5">
                              <span className="font-bold text-xs text-text truncate">{st.name}</span>
                              <span className="text-[10px] font-semibold px-1.5 py-0.2 rounded bg-bg3 text-text2 border border-border">
                                {st.section}
                              </span>
                            </div>
                            <div className="text-[11px] text-red-500 dark:text-red-400 font-medium truncate">
                              {st.issue}
                            </div>
                            <div className="text-[10px] text-text3">
                              Standing: <span className="font-bold text-text">{st.standing}</span> · {st.subject}
                            </div>
                          </div>
                          <button
                            type="button"
                            onClick={() => onTabChange('students')}
                            className="text-xs font-semibold text-accent hover:underline cursor-pointer shrink-0"
                          >
                            View Record →
                          </button>
                        </div>
                      ))}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          )}

          {/* ============================================================ */}
          {/* FLOATING QUICK ACTIONS SPEED-DIAL BUTTON (Nav Items Theme)   */}
          {/* ============================================================ */}
          <div
            className="fixed bottom-6 right-6 z-40 select-none"
            onMouseEnter={() => setIsQuickActionsOpen(true)}
            onMouseLeave={() => setIsQuickActionsOpen(false)}
          >
            {/* Pop-up Action Items (Positioned absolutely above the button) */}
            <div
              className={`absolute bottom-full right-0 pb-2.5 flex flex-col items-end gap-2 transition-all duration-200 ease-out ${
                isQuickActionsOpen
                  ? 'opacity-100 translate-y-0 pointer-events-auto visible'
                  : 'opacity-0 translate-y-2 pointer-events-none invisible'
              }`}
            >
              {/* 1. Grade Pending Queue */}
              <button
                type="button"
                onClick={() => {
                  setDashboardSide('b');
                  setKpiFilter('pending');
                  if (pendingSubmissions.length > 0) {
                    setGradingModalSubmission(pendingSubmissions[0]);
                  }
                  setIsQuickActionsOpen(false);
                }}
                className="group flex items-center gap-2.5 py-1.5 pl-3.5 pr-1.5 rounded-full bg-bg2/95 dark:bg-[#151d2a]/95 backdrop-blur-md border border-border hover:border-amber-500/50 hover:bg-bg3 shadow-lg hover:shadow-xl hover:-translate-x-1 transition-all cursor-pointer text-xs font-semibold text-text"
              >
                <span className="text-text2 group-hover:text-text transition-colors">
                  Grade Pending Queue ({pendingSubmissions.length})
                </span>
                <span className="size-7 rounded-full flex items-center justify-center text-slate-950 font-bold text-xs bg-[var(--sidebar-active-border)] shadow-xs">
                  {pendingSubmissions.length}
                </span>
              </button>

              {/* 2. Create Interactive Quiz */}
              <button
                type="button"
                onClick={() => {
                  setIsCreateQuizOpen(true);
                  setIsQuickActionsOpen(false);
                }}
                className="group flex items-center gap-2.5 py-1.5 pl-3.5 pr-1.5 rounded-full bg-bg2/95 dark:bg-[#151d2a]/95 backdrop-blur-md border border-border hover:border-amber-500/50 hover:bg-bg3 shadow-lg hover:shadow-xl hover:-translate-x-1 transition-all cursor-pointer text-xs font-semibold text-text"
              >
                <span className="text-text2 group-hover:text-text transition-colors">
                  Create Interactive Quiz
                </span>
                <span className="size-7 rounded-full flex items-center justify-center bg-bg3 text-text2 group-hover:bg-amber-500/15 group-hover:text-amber-500 border border-border group-hover:border-amber-500/40 shadow-xs transition-colors">
                  <RiQuestionLine className="size-3.5" />
                </span>
              </button>

              {/* 3. New Performance Task */}
              <button
                type="button"
                onClick={() => {
                  setIsCreateTaskOpen(true);
                  setIsQuickActionsOpen(false);
                }}
                className="group flex items-center gap-2.5 py-1.5 pl-3.5 pr-1.5 rounded-full bg-bg2/95 dark:bg-[#151d2a]/95 backdrop-blur-md border border-border hover:border-amber-500/50 hover:bg-bg3 shadow-lg hover:shadow-xl hover:-translate-x-1 transition-all cursor-pointer text-xs font-semibold text-text"
              >
                <span className="text-text2 group-hover:text-text transition-colors">
                  New Performance Task
                </span>
                <span className="size-7 rounded-full flex items-center justify-center bg-bg3 text-text2 group-hover:bg-amber-500/15 group-hover:text-amber-500 border border-border group-hover:border-amber-500/40 shadow-xs transition-colors">
                  <RiFileList3Line className="size-3.5" />
                </span>
              </button>

              {/* 4. Add Course / Module */}
              <button
                type="button"
                onClick={() => {
                  setIsAddCourseOpen(true);
                  setIsQuickActionsOpen(false);
                }}
                className="group flex items-center gap-2.5 py-1.5 pl-3.5 pr-1.5 rounded-full bg-bg2/95 dark:bg-[#151d2a]/95 backdrop-blur-md border border-border hover:border-amber-500/50 hover:bg-bg3 shadow-lg hover:shadow-xl hover:-translate-x-1 transition-all cursor-pointer text-xs font-semibold text-text"
              >
                <span className="text-text2 group-hover:text-text transition-colors">
                  Add Course / Module
                </span>
                <span className="size-7 rounded-full flex items-center justify-center bg-bg3 text-text2 group-hover:bg-amber-500/15 group-hover:text-amber-500 border border-border group-hover:border-amber-500/40 shadow-xs transition-colors">
                  <RiBookOpenLine className="size-3.5" />
                </span>
              </button>

              {/* 5. Post Announcement */}
              <button
                type="button"
                onClick={() => {
                  setIsPostAnnounceOpen(true);
                  setIsQuickActionsOpen(false);
                }}
                className="group flex items-center gap-2.5 py-1.5 pl-3.5 pr-1.5 rounded-full bg-bg2/95 dark:bg-[#151d2a]/95 backdrop-blur-md border border-border hover:border-amber-500/50 hover:bg-bg3 shadow-lg hover:shadow-xl hover:-translate-x-1 transition-all cursor-pointer text-xs font-semibold text-text"
              >
                <span className="text-text2 group-hover:text-text transition-colors">
                  Post Announcement
                </span>
                <span className="size-7 rounded-full flex items-center justify-center bg-bg3 text-text2 group-hover:bg-amber-500/15 group-hover:text-amber-500 border border-border group-hover:border-amber-500/40 shadow-xs transition-colors">
                  <RiNotification3Line className="size-3.5" />
                </span>
              </button>

              {/* 6. Launch Virtual Class */}
              <button
                type="button"
                onClick={() => {
                  onTabChange('live');
                  setIsQuickActionsOpen(false);
                }}
                className="group flex items-center gap-2.5 py-1.5 pl-3.5 pr-1.5 rounded-full bg-bg2/95 dark:bg-[#151d2a]/95 backdrop-blur-md border border-border hover:border-amber-500/50 hover:bg-bg3 shadow-lg hover:shadow-xl hover:-translate-x-1 transition-all cursor-pointer text-xs font-semibold text-text"
              >
                <span className="text-text2 group-hover:text-text transition-colors">
                  Launch Virtual Class
                </span>
                <span className="size-7 rounded-full flex items-center justify-center bg-bg3 text-text2 group-hover:bg-amber-500/15 group-hover:text-amber-500 border border-border group-hover:border-amber-500/40 shadow-xs transition-colors">
                  <RiLiveLine className="size-3.5" />
                </span>
              </button>
            </div>

            {/* Main Trigger Button (ExceLearn Nav Items Theme) */}
            <button
              type="button"
              onClick={() => setIsQuickActionsOpen((prev) => !prev)}
              aria-label="Toggle Quick Actions Speed-Dial"
              aria-expanded={isQuickActionsOpen}
              className="group inline-flex items-center gap-2.5 px-4 py-2.5 rounded-full excelearn-nav-pill active border-2 border-[var(--sidebar-active-border)] text-[var(--sidebar-active-text)] font-semibold text-xs sm:text-sm shadow-xl hover:shadow-2xl hover:brightness-105 active:scale-95 transition-all cursor-pointer"
            >
              <RiSparklingLine className="size-4 sm:size-4.5 animate-pulse text-[var(--sidebar-active-text)]" aria-hidden="true" />
              <span className="text-[var(--sidebar-active-text)]">Quick Actions</span>
              {pendingSubmissions.length > 0 && (
                <span className="px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-[var(--sidebar-active-border)] text-slate-950 leading-none shadow-xs">
                  {pendingSubmissions.length}
                </span>
              )}
              <span
                className={`size-6 rounded-full bg-white/20 flex items-center justify-center transition-transform duration-300 ${
                  isQuickActionsOpen ? 'rotate-[135deg]' : 'rotate-0'
                }`}
              >
                <RiAddLine className="size-4 text-[var(--sidebar-active-text)]" />
              </span>
            </button>
          </div>
        </div>
      )}

      {/* POST ANNOUNCEMENT MODAL (teachers/index.php lines 1020-1055) */}
      {isPostAnnounceOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md">
          <div className="w-full max-w-md bg-bg2 rounded-2xl border border-border shadow-2xl overflow-hidden p-6 space-y-4">
            <div className="flex items-center justify-between border-b border-border pb-3">
              <h4 className="text-base font-bold text-text flex items-center gap-1.5">
                <RiNotification3Line className="size-4 text-accent" />
                <span>Post Announcement</span>
              </h4>
              <button
                type="button"
                onClick={() => setIsPostAnnounceOpen(false)}
                className="p-1 rounded-lg text-text3 hover:text-text cursor-pointer"
              >
                <RiCloseLine className="size-5" />
              </button>
            </div>

            <form
              onSubmit={(e) => {
                e.preventDefault();
                setIsPostAnnounceOpen(false);
              }}
              className="space-y-3 text-xs"
            >
              <div>
                <label className="block font-semibold text-text2 mb-1">Title *</label>
                <input
                  type="text"
                  placeholder="Announcement title..."
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-text outline-none focus:border-accent"
                  required
                />
              </div>
              <div>
                <label className="block font-semibold text-text2 mb-1">Type</label>
                <select className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-text outline-none focus:border-accent">
                  <option value="system">System</option>
                  <option value="important">Important</option>
                  <option value="event">Event</option>
                </select>
              </div>
              <div>
                <label className="block font-semibold text-text2 mb-1">Message *</label>
                <textarea
                  rows={4}
                  placeholder="Write your announcement here..."
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-text outline-none focus:border-accent resize-vertical"
                  required
                />
              </div>
              <div className="flex justify-end gap-2 pt-2 border-t border-border">
                <button
                  type="button"
                  onClick={() => setIsPostAnnounceOpen(false)}
                  className="px-3 py-1.5 rounded-lg border border-border text-text2 hover:bg-bg3 cursor-pointer"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-4 py-1.5 rounded-lg bg-accent text-white font-semibold hover:bg-accent2 cursor-pointer shadow-xs"
                >
                  Post Announcement
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* ============================================================ */}
      {/* 2. COURSES & MODULES (teachers/teacher_courses.php)           */}
      {/* ============================================================ */}
      {activeTab === 'courses' && (
        <div className="p-5 rounded-xl bg-bg2 border border-border space-y-4">
          <div className="flex items-center justify-between">
            <div>
              <h4 className="text-lg font-bold text-text font-serif">Assigned Teaching Loads & Modules</h4>
              <p className="text-xs text-text2">Syllabus builder and downloadable PDF handouts.</p>
            </div>
            <button
              type="button"
              onClick={() => setIsAddCourseOpen(true)}
              className="px-3 py-1.5 rounded-lg bg-school-orange text-[#0e0f14] text-xs font-semibold hover:brightness-105 cursor-pointer shadow-xs flex items-center gap-1.5"
            >
              <RiAddLine className="size-4" />
              <span>Add Course / Lesson</span>
            </button>
          </div>

          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs">
              <thead className="bg-bg3 text-text3 uppercase tracking-wider border-b border-border">
                <tr>
                  <th className="py-2.5 px-3 font-semibold">Subject Title</th>
                  <th className="py-2.5 px-3 font-semibold">Section</th>
                  <th className="py-2.5 px-3 font-semibold">Active Module</th>
                  <th className="py-2.5 px-3 font-semibold text-center">Class Progress</th>
                  <th className="py-2.5 px-3 font-semibold text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                <tr>
                  <td className="py-3 px-3">
                    <span className="font-bold text-text">ORAL-COMM-11</span>
                    <div className="text-[11px] text-text3">Oral Communication in Context</div>
                  </td>
                  <td className="py-3 px-3 text-text2">STEM 11-A (38 students)</td>
                  <td className="py-3 px-3 text-text">Module 5: Types of Speech Context</td>
                  <td className="py-3 px-3 text-center font-bold text-green tabular-nums">78%</td>
                  <td className="py-3 px-3 text-right">
                    <button
                      type="button"
                      onClick={() => alert('Opening module editor...')}
                      className="text-accent hover:underline font-medium"
                    >
                      Edit Lessons
                    </button>
                  </td>
                </tr>
                <tr>
                  <td className="py-3 px-3">
                    <span className="font-bold text-text">ORAL-COMM-11</span>
                    <div className="text-[11px] text-text3">Oral Communication in Context</div>
                  </td>
                  <td className="py-3 px-3 text-text2">STEM 11-B (36 students)</td>
                  <td className="py-3 px-3 text-text">Module 5: Types of Speech Context</td>
                  <td className="py-3 px-3 text-center font-bold text-accent tabular-nums">72%</td>
                  <td className="py-3 px-3 text-right">
                    <button
                      type="button"
                      onClick={() => alert('Opening module editor...')}
                      className="text-accent hover:underline font-medium"
                    >
                      Edit Lessons
                    </button>
                  </td>
                </tr>
                <tr>
                  <td className="py-3 px-3">
                    <span className="font-bold text-text">READ-WRITE-11</span>
                    <div className="text-[11px] text-text3">Reading and Writing Skills</div>
                  </td>
                  <td className="py-3 px-3 text-text2">HUMSS 11-A (34 students)</td>
                  <td className="py-3 px-3 text-text">Module 4: Patterns of Development</td>
                  <td className="py-3 px-3 text-center font-bold text-purple tabular-nums">85%</td>
                  <td className="py-3 px-3 text-right">
                    <button
                      type="button"
                      onClick={() => alert('Opening module editor...')}
                      className="text-accent hover:underline font-medium"
                    >
                      Edit Lessons
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* ============================================================ */}
      {/* 3. QUIZZES BUILDER (teachers/teacher_quizzes.php)             */}
      {/* ============================================================ */}
      {activeTab === 'quizzes' && (
        <div className="p-5 rounded-xl bg-bg2 border border-border space-y-4">
          <div className="flex items-center justify-between">
            <div>
              <h4 className="text-lg font-bold text-text font-serif">Assessment Quizzes & Question Bank</h4>
              <p className="text-xs text-text2">Design timed tests with auto-grading rules.</p>
            </div>
            <button
              type="button"
              onClick={() => setIsCreateQuizOpen(true)}
              className="px-3 py-1.5 rounded-lg bg-school-orange text-[#0e0f14] text-xs font-semibold hover:brightness-105 cursor-pointer shadow-xs flex items-center gap-1.5"
            >
              <RiAddLine className="size-4" />
              <span>+ Create Quiz</span>
            </button>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div className="p-4 rounded-xl bg-bg3 border border-border space-y-2">
              <div className="flex items-center justify-between">
                <span className="text-[10px] font-bold px-2 py-0.5 rounded bg-green-bg text-green uppercase">Published</span>
                <span className="text-xs text-text3">30 Mins · 40 Pts</span>
              </div>
              <h5 className="text-sm font-bold text-text">Speech Contexts & Communication Barriers</h5>
              <p className="text-xs text-text3">Due: Tomorrow at 11:59 PM · 32/38 students completed</p>
              <div className="pt-2 flex justify-end">
                <button
                  type="button"
                  onClick={() => alert('Reviewing student quiz scores...')}
                  className="text-xs text-accent hover:underline font-semibold"
                >
                  View Student Scores &rarr;
                </button>
              </div>
            </div>

            <div className="p-4 rounded-xl bg-bg3 border border-border space-y-2">
              <div className="flex items-center justify-between">
                <span className="text-[10px] font-bold px-2 py-0.5 rounded bg-amber-bg text-amber uppercase">Draft</span>
                <span className="text-xs text-text3">45 Mins · 50 Pts</span>
              </div>
              <h5 className="text-sm font-bold text-text">Midterm Comprehensive Grammar & Vocabulary</h5>
              <p className="text-xs text-text3">Scheduled for Next Week</p>
              <div className="pt-2 flex justify-end">
                <button
                  type="button"
                  onClick={() => setIsCreateQuizOpen(true)}
                  className="text-xs text-accent hover:underline font-semibold"
                >
                  Edit Questions &rarr;
                </button>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* ============================================================ */}
      {/* 4. PERFORMANCE TASKS & GRADING (teacher_performance_tasks.php)*/}
      {/* ============================================================ */}
      {activeTab === 'tasks' && (
        <div className="space-y-4">
          <div className="flex items-center justify-between">
            <div>
              <h4 className="text-lg font-bold text-text font-serif">Performance Tasks & Rubric Evaluation</h4>
              <p className="text-xs text-text2">Evaluate student video recordings and assign rubric ratings.</p>
            </div>
            <button
              type="button"
              onClick={() => setIsCreateTaskOpen(true)}
              className="px-3 py-1.5 rounded-lg bg-school-orange text-[#0e0f14] text-xs font-semibold hover:brightness-105 cursor-pointer shadow-xs flex items-center gap-1.5"
            >
              <RiAddLine className="size-4" />
              <span>+ New Performance Task</span>
            </button>
          </div>

          <div className="grid grid-cols-1 gap-3">
            {submissions.map((subm) => {
              const isGraded = subm.status === 'graded';

              return (
                <div
                  key={subm.id}
                  className={`p-4 sm:p-5 rounded-xl border transition-all ${
                    isGraded
                      ? 'bg-bg2/60 border-border opacity-85'
                      : 'bg-bg2 border-border hover:border-school-orange/40 shadow-xs'
                  }`}
                >
                  <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div className="space-y-1 max-w-lg">
                      <div className="flex items-center gap-2">
                        <span className="text-sm font-bold text-text">{subm.studentName}</span>
                        <span className="text-xs px-2 py-0.5 rounded bg-bg3 text-text3 font-mono">
                          {subm.studentId}
                        </span>
                        <span
                          className={`text-[10px] font-bold px-2 py-0.5 rounded-full ${
                            isGraded ? 'bg-green-bg text-green' : 'bg-amber-bg text-amber'
                          }`}
                        >
                          {isGraded ? 'Graded' : 'Pending Evaluation'}
                        </span>
                      </div>

                      <div className="text-xs font-medium text-text2">{subm.taskTitle}</div>

                      <div className="flex items-center gap-3 text-xs text-text3 pt-1">
                        <span className="flex items-center gap-1">
                          <RiTimeLine className="size-3.5" aria-hidden="true" />
                          {subm.submittedTime}
                        </span>
                        <span className="flex items-center gap-1 text-accent font-medium">
                          <RiFilePdfLine className="size-3.5" aria-hidden="true" />
                          {subm.fileAttachment} ({subm.fileSize})
                        </span>
                      </div>
                    </div>

                    <div className="flex items-center gap-3 shrink-0">
                      {isGraded ? (
                        <div className="text-right">
                          <div className="text-xs text-text3">Final Score</div>
                          <div className="text-xl font-bold text-green tabular-nums">
                            {subm.score} / {subm.maxScore}
                          </div>
                        </div>
                      ) : (
                        <button
                          type="button"
                          onClick={() => setGradingModalSubmission(subm)}
                          className="flex items-center gap-1.5 px-4 py-2 rounded-lg bg-green text-[#0e0f14] font-semibold text-xs hover:brightness-105 active:scale-95 transition-all cursor-pointer shadow-xs"
                        >
                          <RiCheckLine className="size-4" aria-hidden="true" />
                          <span>Score & Feedback</span>
                        </button>
                      )}
                    </div>
                  </div>
                </div>
              );
            })}
          </div>
        </div>
      )}

      {/* ============================================================ */}
      {/* 5. GRADEBOOK (teachers/teacher_grades.php)                    */}
      {/* ============================================================ */}
      {activeTab === 'grades' && (
        <div className="p-5 rounded-xl bg-bg2 border border-border space-y-4">
          <div className="flex items-center justify-between">
            <div>
              <h4 className="text-lg font-bold text-text font-serif">Quarterly Gradebook Calculation</h4>
              <p className="text-xs text-text2">STEM 11-A · Oral Communication in Context (DepEd 25-50-25 Weights)</p>
            </div>
            <button
              type="button"
              onClick={() => alert('Exporting grade sheet via PhpSpreadsheet (.xlsx)...')}
              className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-green text-[#0e0f14] text-xs font-semibold hover:brightness-105 cursor-pointer shadow-xs"
            >
              <RiFileExcel2Line className="size-4" />
              <span>Export to Excel (.xlsx)</span>
            </button>
          </div>

          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs">
              <thead className="bg-bg3 text-text3 uppercase tracking-wider border-b border-border">
                <tr>
                  <th className="py-2.5 px-3 font-semibold">Student Name</th>
                  <th className="py-2.5 px-3 font-semibold">Student ID</th>
                  <th className="py-2.5 px-3 font-semibold text-center">Written (25%)</th>
                  <th className="py-2.5 px-3 font-semibold text-center">Tasks (50%)</th>
                  <th className="py-2.5 px-3 font-semibold text-center">Quarter Exam (25%)</th>
                  <th className="py-2.5 px-3 font-semibold text-center">Quarter Grade</th>
                  <th className="py-2.5 px-3 font-semibold text-right">Remarks</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {STUDENT_DIRECTORY.slice(0, 5).map((st) => (
                  <tr key={st.id} className="hover:bg-bg3/50 transition-colors">
                    <td className="py-3 px-3 font-semibold text-text">{st.name}</td>
                    <td className="py-3 px-3 font-mono text-text3">{st.studentId}</td>
                    <td className="py-3 px-3 text-center tabular-nums text-text">92.0</td>
                    <td className="py-3 px-3 text-center tabular-nums text-text">94.5</td>
                    <td className="py-3 px-3 text-center tabular-nums text-text">93.0</td>
                    <td className="py-3 px-3 text-center font-bold text-green tabular-nums">93.5</td>
                    <td className="py-3 px-3 text-right text-green font-semibold">Passed</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* ============================================================ */}
      {/* 6. STUDENTS ROSTER (teachers/teacher_students.php)           */}
      {/* ============================================================ */}
      {activeTab === 'students' && (
        <div className="p-5 rounded-xl bg-bg2 border border-border space-y-4">
          <div>
            <h4 className="text-lg font-bold text-text font-serif">Enrolled Students Roster</h4>
            <p className="text-xs text-text2">Official masterlist of learners in assigned teaching sections.</p>
          </div>

          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs">
              <thead className="bg-bg3 text-text3 uppercase tracking-wider border-b border-border">
                <tr>
                  <th className="py-2.5 px-3 font-semibold">Student Name</th>
                  <th className="py-2.5 px-3 font-semibold">LRN</th>
                  <th className="py-2.5 px-3 font-semibold">Section & Strand</th>
                  <th className="py-2.5 px-3 font-semibold text-center">Current Standing</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border">
                {STUDENT_DIRECTORY.map((st) => (
                  <tr key={st.id} className="hover:bg-bg3/50 transition-colors">
                    <td className="py-3 px-3 font-semibold text-text">{st.name}</td>
                    <td className="py-3 px-3 font-mono text-accent">{st.lrn}</td>
                    <td className="py-3 px-3 text-text2">{st.section}</td>
                    <td className="py-3 px-3 text-center">
                      <span className="font-bold text-green tabular-nums">{st.gwa} GWA</span>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* ============================================================ */}
      {/* 7. CLASSES & TEACHING TIMETABLE (teachers/teacher_classes.php) */}
      {/* ============================================================ */}
      {activeTab === 'live' && (
        <StudentClassesView
          userRole="teacher"
          onTabChange={onTabChange}
        />
      )}

      {/* ============================================================ */}
      {/* 8. ANNOUNCEMENTS COMPOSER (teachers/index.php feed)           */}
      {/* ============================================================ */}
      {activeTab === 'announcements' && (
        <div className="p-5 rounded-xl bg-bg2 border border-border space-y-4">
          <div className="flex items-center gap-2">
            <RiNotification3Line className="size-5 text-school-orange" aria-hidden="true" />
            <h4 className="text-base font-bold text-text font-serif">Class Announcements Composer</h4>
          </div>

          <form onSubmit={handlePostAnnouncement} className="space-y-3">
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label className="block text-xs font-semibold text-text2 mb-1">Category Tag</label>
                <select
                  value={newCategory}
                  onChange={(e) => setNewCategory(e.target.value)}
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text focus:outline-none focus:border-school-orange"
                >
                  <option value="Rubric Update">Rubric Update</option>
                  <option value="Exam Alert">Exam Alert</option>
                  <option value="General Reminder">General Reminder</option>
                  <option value="Class Schedule">Class Schedule</option>
                </select>
              </div>

              <div>
                <label className="block text-xs font-semibold text-text2 mb-1">Target Student Section</label>
                <select
                  value={newSection}
                  onChange={(e) => setNewSection(e.target.value)}
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text focus:outline-none focus:border-school-orange"
                >
                  <option value="STEM 11-A">STEM 11-A</option>
                  <option value="STEM 11-B">STEM 11-B</option>
                  <option value="HUMSS 11-A">HUMSS 11-A</option>
                  <option value="All Assigned Sections">All Assigned Sections</option>
                </select>
              </div>
            </div>

            <div>
              <label className="block text-xs font-semibold text-text2 mb-1">Announcement Headline</label>
              <input
                type="text"
                placeholder="e.g. Video Recording Submission Guidelines & Rubric"
                value={newTitle}
                onChange={(e) => setNewTitle(e.target.value)}
                className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text focus:outline-none focus:border-school-orange"
                required
              />
            </div>

            <div>
              <label className="block text-xs font-semibold text-text2 mb-1">Message Body</label>
              <textarea
                rows={3}
                placeholder="Enter detailed message for your students..."
                value={newBody}
                onChange={(e) => setNewBody(e.target.value)}
                className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text focus:outline-none focus:border-school-orange"
                required
              />
            </div>

            <div className="flex justify-end">
              <button
                type="submit"
                className="px-4 py-2 rounded-xl bg-school-orange text-[#0e0f14] text-xs font-semibold hover:brightness-105 active:scale-95 transition-all cursor-pointer shadow-xs"
              >
                Broadcast to Class
              </button>
            </div>
          </form>

          {/* Feed */}
          <div className="pt-3 border-t border-border space-y-2.5">
            <span className="text-xs font-semibold text-text3 uppercase tracking-wider block">Recent Broadcasts</span>
            {announcements.map((ann) => (
              <div key={ann.id} className="p-3.5 rounded-lg bg-bg3 border border-border space-y-1">
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-2">
                    <span className="text-[10px] font-bold px-2 py-0.5 rounded bg-school-orange/15 text-school-orange">
                      {ann.category}
                    </span>
                    <span className="text-xs font-bold text-text">{ann.title}</span>
                  </div>
                  <span className="text-[11px] text-text3">{ann.date}</span>
                </div>
                <p className="text-xs text-text2 leading-relaxed">{ann.body}</p>
                <div className="text-[10px] text-text3 pt-1">Audience: {ann.section}</div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* ============================================================ */}
      {/* 9. FACULTY PROFILE (teachers/teacher_profile.php)             */}
      {/* ============================================================ */}
      {activeTab === 'profile' && (
        <TeacherProfileView onTabChange={onTabChange} />
      )}

      {/* ============================================================ */}
      {/* 10. SETTINGS (teachers/settings.php)                          */}
      {/* ============================================================ */}
      {activeTab === 'settings' && (
        <TeacherSettingsView onTabChange={onTabChange} />
      )}

      {/* Screen: About (about.php) */}
      {activeTab === 'about' && (
        <AboutView role="teacher" onTabChange={onTabChange} />
      )}

      {/* ============================================================ */}
      {/* MODAL 1: CREATE QUIZ (teacher_quizzes.php lines 1220-1275)   */}
      {/* ============================================================ */}
      {isCreateQuizOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md">
          <div className="w-full max-w-xl max-h-[90vh] bg-bg2 rounded-2xl border border-border shadow-2xl overflow-y-auto p-6 space-y-4">
            <div className="flex items-center justify-between border-b border-border pb-3">
              <div>
                <span className="text-[10px] font-bold uppercase tracking-wider text-school-orange">Quiz Builder</span>
                <h4 className="text-base font-bold text-text">Create Assessment Quiz</h4>
              </div>
              <button
                type="button"
                onClick={() => setIsCreateQuizOpen(false)}
                className="p-1 rounded-lg text-text3 hover:text-text cursor-pointer"
              >
                <RiCloseLine className="size-5" />
              </button>
            </div>

            <form onSubmit={handleCreateQuiz} className="space-y-3 text-xs">
              <div>
                <label className="block font-semibold text-text2 mb-1">Lesson / Unit *</label>
                <select
                  value={quizLesson}
                  onChange={(e) => setQuizLesson(e.target.value)}
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-text"
                  required
                >
                  <option value="Oral Communication — Module 5">Oral Communication — Module 5: Speech Contexts</option>
                  <option value="Reading and Writing — Module 4">Reading and Writing — Module 4: Patterns of Development</option>
                </select>
              </div>

              <div>
                <label className="block font-semibold text-text2 mb-1">Quiz Description</label>
                <textarea
                  rows={2}
                  placeholder="Optional description / instructions for students..."
                  value={quizDesc}
                  onChange={(e) => setQuizDesc(e.target.value)}
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-text"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-semibold text-text2 mb-1">Status</label>
                  <select
                    value={quizStatus}
                    onChange={(e) => setQuizStatus(e.target.value)}
                    className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-text"
                  >
                    <option value="published">Active Now (Published)</option>
                    <option value="draft">Draft</option>
                  </select>
                </div>
                <div>
                  <label className="block font-semibold text-text2 mb-1">Duration (mins)</label>
                  <input
                    type="number"
                    value={quizDuration}
                    onChange={(e) => setQuizDuration(Number(e.target.value))}
                    className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-text"
                    min={1}
                  />
                </div>
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-semibold text-text2 mb-1">Passing Score (%)</label>
                  <input
                    type="number"
                    value={quizPassing}
                    onChange={(e) => setQuizPassing(Number(e.target.value))}
                    className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-text"
                    min={0}
                    max={100}
                  />
                </div>
                <div>
                  <label className="block font-semibold text-text2 mb-1">Due Date & Time</label>
                  <input
                    type="datetime-local"
                    value={quizDue}
                    onChange={(e) => setQuizDue(e.target.value)}
                    className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-text"
                  />
                </div>
              </div>

              <div className="pt-2 border-t border-border">
                <div className="flex items-center justify-between mb-2">
                  <span className="font-bold text-text">Questions ({quizQuestions.length})</span>
                  <button
                    type="button"
                    onClick={() =>
                      setQuizQuestions([
                        ...quizQuestions,
                        {
                          q: `Question ${quizQuestions.length + 1} text...`,
                          optA: 'Option A',
                          optB: 'Option B',
                          optC: 'Option C',
                          optD: 'Option D',
                          answer: 'A',
                        },
                      ])
                    }
                    className="text-accent hover:underline font-semibold"
                  >
                    + Add Question
                  </button>
                </div>

                <div className="space-y-3">
                  {quizQuestions.map((q, idx) => (
                    <div key={idx} className="p-3 rounded-lg bg-bg3 border border-border space-y-2">
                      <div className="flex justify-between items-center">
                        <span className="font-semibold text-text">Q{idx + 1}.</span>
                        {quizQuestions.length > 1 && (
                          <button
                            type="button"
                            onClick={() => setQuizQuestions(quizQuestions.filter((_, i) => i !== idx))}
                            className="text-red hover:underline"
                          >
                            Remove
                          </button>
                        )}
                      </div>
                      <input
                        type="text"
                        value={q.q}
                        onChange={(e) => {
                          const updated = [...quizQuestions];
                          updated[idx].q = e.target.value;
                          setQuizQuestions(updated);
                        }}
                        className="w-full px-2 py-1 rounded bg-bg2 border border-border text-text"
                      />
                    </div>
                  ))}
                </div>
              </div>

              <div className="flex justify-end gap-2 pt-3 border-t border-border">
                <button
                  type="button"
                  onClick={() => setIsCreateQuizOpen(false)}
                  className="px-4 py-2 rounded-lg bg-bg3 text-text cursor-pointer"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-5 py-2 rounded-xl bg-school-orange text-[#0e0f14] font-semibold cursor-pointer shadow-xs"
                >
                  Create Quiz
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* ============================================================ */}
      {/* MODAL 2: CREATE TASK (teacher_performance_tasks.php 892-942) */}
      {/* ============================================================ */}
      {isCreateTaskOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md">
          <div className="w-full max-w-lg bg-bg2 rounded-2xl border border-border shadow-2xl p-6 space-y-4">
            <div className="flex items-center justify-between border-b border-border pb-3">
              <h4 className="text-base font-bold text-text">New Performance Task</h4>
              <button
                type="button"
                onClick={() => setIsCreateTaskOpen(false)}
                className="p-1 rounded-lg text-text3 hover:text-text cursor-pointer"
              >
                <RiCloseLine className="size-5" />
              </button>
            </div>

            <form onSubmit={handleCreateTask} className="space-y-3 text-xs">
              <div>
                <label className="block font-semibold text-text2 mb-1">Subject *</label>
                <select
                  value={taskSubject}
                  onChange={(e) => setTaskSubject(e.target.value)}
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-text"
                  required
                >
                  <option value="Oral Communication in Context">Oral Communication in Context</option>
                  <option value="Reading and Writing Skills">Reading and Writing Skills</option>
                </select>
              </div>

              <div>
                <label className="block font-semibold text-text2 mb-1">Task Title *</label>
                <input
                  type="text"
                  placeholder="e.g. Extemporaneous Speech Video Demonstration"
                  value={taskTitle}
                  onChange={(e) => setTaskTitle(e.target.value)}
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-text"
                  required
                />
              </div>

              <div>
                <label className="block font-semibold text-text2 mb-1">Activity Instructions *</label>
                <textarea
                  rows={4}
                  placeholder="Describe exactly what students must perform (rubric, time limit, required video posture)..."
                  value={taskInstructions}
                  onChange={(e) => setTaskInstructions(e.target.value)}
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-text"
                  required
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block font-semibold text-text2 mb-1">Max Score *</label>
                  <input
                    type="number"
                    value={taskMaxScore}
                    onChange={(e) => setTaskMaxScore(Number(e.target.value))}
                    className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-text"
                    required
                  />
                </div>
                <div>
                  <label className="block font-semibold text-text2 mb-1">Due Date</label>
                  <input
                    type="datetime-local"
                    value={taskDue}
                    onChange={(e) => setTaskDue(e.target.value)}
                    className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-text"
                  />
                </div>
              </div>

              <div className="flex justify-end gap-2 pt-3 border-t border-border">
                <button
                  type="button"
                  onClick={() => setIsCreateTaskOpen(false)}
                  className="px-4 py-2 rounded-lg bg-bg3 text-text cursor-pointer"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-5 py-2 rounded-xl bg-school-orange text-[#0e0f14] font-semibold cursor-pointer shadow-xs"
                >
                  Create Task
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* ============================================================ */}
      {/* MODAL 3: RUBRIC GRADING MODAL                                */}
      {/* ============================================================ */}
      {gradingModalSubmission && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md">
          <div className="w-full max-w-lg bg-bg2 rounded-2xl border border-border shadow-2xl p-6 space-y-4">
            <div className="flex items-center justify-between border-b border-border pb-3">
              <div>
                <span className="text-[10px] font-bold uppercase tracking-wider text-green">Analytical Rubric Scoring</span>
                <h4 className="text-base font-bold text-text">{gradingModalSubmission.studentName}</h4>
              </div>
              <button
                type="button"
                onClick={() => setGradingModalSubmission(null)}
                className="p-1 rounded-lg text-text3 hover:text-text cursor-pointer"
              >
                <RiCloseLine className="size-5" />
              </button>
            </div>

            <div className="space-y-3 text-xs">
              <div className="p-3 rounded-lg bg-bg3 border border-border">
                <div className="text-text3 text-[11px]">Submitted Artifact:</div>
                <div className="font-semibold text-accent flex items-center gap-1.5 mt-0.5">
                  <RiFilePdfLine className="size-4" />
                  <span>{gradingModalSubmission.fileAttachment} ({gradingModalSubmission.fileSize})</span>
                </div>
              </div>

              <div>
                <label className="block font-semibold text-text2 mb-1">
                  Rubric Score (/ {gradingModalSubmission.maxScore}) *
                </label>
                <input
                  type="number"
                  min={0}
                  max={gradingModalSubmission.maxScore}
                  value={gradingScores[gradingModalSubmission.id] ?? 92}
                  onChange={(e) =>
                    setGradingScores({
                      ...gradingScores,
                      [gradingModalSubmission.id]: Number(e.target.value),
                    })
                  }
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-base font-bold text-text"
                />
              </div>

              <div>
                <label className="block font-semibold text-text2 mb-1">Teacher Feedback Remarks</label>
                <textarea
                  rows={3}
                  placeholder="Provide constructive feedback regarding vocal delivery, content structure, and eye contact..."
                  value={feedbackRemarks[gradingModalSubmission.id] ?? ''}
                  onChange={(e) =>
                    setFeedbackRemarks({
                      ...feedbackRemarks,
                      [gradingModalSubmission.id]: e.target.value,
                    })
                  }
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-text"
                />
              </div>

              <div className="flex justify-end gap-2 pt-3 border-t border-border">
                <button
                  type="button"
                  onClick={() => setGradingModalSubmission(null)}
                  className="px-4 py-2 rounded-lg bg-bg3 text-text cursor-pointer"
                >
                  Cancel
                </button>
                <button
                  type="button"
                  onClick={() => handleGradeSubmission(gradingModalSubmission.id)}
                  className="px-5 py-2 rounded-xl bg-green text-[#0e0f14] font-semibold cursor-pointer shadow-xs"
                >
                  Approve & Save Grade
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
      {/* ============================================================ */}
      {/* MODAL 5: ADD COURSE / LESSON                                 */}
      {/* ============================================================ */}
      {isAddCourseOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md">
          <div className="w-full max-w-md bg-bg2 rounded-2xl border border-border shadow-2xl p-6 space-y-4 text-xs">
            <div className="flex items-center justify-between border-b border-border pb-3">
              <h4 className="text-base font-bold text-text">Add Subject Course & Lesson</h4>
              <button
                type="button"
                onClick={() => setIsAddCourseOpen(false)}
                className="p-1 rounded-lg text-text3 hover:text-text cursor-pointer"
              >
                <RiCloseLine className="size-5" />
              </button>
            </div>
            <div className="space-y-3">
              <div>
                <label className="block font-semibold text-text2 mb-1">Course Code & Title</label>
                <input
                  type="text"
                  placeholder="e.g. LIT-11: 21st Century Literature"
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-text"
                />
              </div>
              <div>
                <label className="block font-semibold text-text2 mb-1">Section</label>
                <select className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-text">
                  <option>STEM 11-A</option>
                  <option>HUMSS 11-A</option>
                </select>
              </div>
              <div>
                <label className="block font-semibold text-text2 mb-1">Upload Module PDF</label>
                <div className="border-2 border-dashed border-border rounded-xl p-4 text-center cursor-pointer hover:border-accent">
                  <RiUpload2Line className="size-6 text-text3 mx-auto mb-1" />
                  <span className="text-text3 text-[11px]">Select syllabus PDF file</span>
                </div>
              </div>
            </div>
            <div className="flex justify-end gap-2 pt-2">
              <button
                type="button"
                onClick={() => setIsAddCourseOpen(false)}
                className="px-4 py-2 rounded-lg bg-bg3 text-text cursor-pointer"
              >
                Cancel
              </button>
              <button
                type="button"
                onClick={() => {
                  alert('Course & lesson successfully published to syllabus!');
                  setIsAddCourseOpen(false);
                }}
                className="px-4 py-2 rounded-xl bg-school-orange text-[#0e0f14] font-semibold cursor-pointer shadow-xs"
              >
                Save Course
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
