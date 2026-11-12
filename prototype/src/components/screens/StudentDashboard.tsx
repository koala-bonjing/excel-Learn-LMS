import { useState, useEffect } from 'react';
import type { StudentTask } from '@/types';
import { INITIAL_STUDENT_TASKS, SUBJECTS_LIST } from '@/data/mockData';
import { AboutView } from '@/components/views/AboutView';
import { StudentActivityHeatmap } from '@/components/screens/StudentActivityHeatmap';
import {
  RiVideoChatLine,
  RiFilePdfLine,
  RiCheckLine,
  RiMicLine,
  RiMicOffLine,
  RiCameraLine,
  RiCloseLine,
  RiDownload2Line,
  RiUpload2Line,
  RiBookOpenLine,
  RiCheckboxCircleLine,
  RiAwardLine,
  RiFileList3Line,
  RiNotification3Line,
  RiSearchLine,
  RiShieldCheckLine,
  RiUserStarLine,
  RiBarChartLine,
  RiTimeLine,
  RiTrophyLine,
  RiTrophyFill,
  RiMedalFill,
  RiMedalLine,
  RiHistoryLine,
  RiArrowRightLine,
} from '@remixicon/react';

interface StudentDashboardProps {
  activeTab: string;
  onTabChange: (tab: string) => void;
}

export function StudentDashboard({ activeTab, onTabChange }: StudentDashboardProps) {
  const [tasks, setTasks] = useState<StudentTask[]>(INITIAL_STUDENT_TASKS);

  // PDF Viewer Modal (pdf_viewer.php)
  const [activePdfModule, setActivePdfModule] = useState<{ title: string; subject: string } | null>(null);

  // Quiz Runner Modal (student_quizzes.php)
  const [activeQuiz, setActiveQuiz] = useState<StudentTask | null>(null);
  const [quizTimer, setQuizTimer] = useState(1799); // 30 mins
  const [currentQuestionIndex, setCurrentQuestionIndex] = useState(0);
  const [selectedAnswers, setSelectedAnswers] = useState<Record<number, string>>({});
  const [quizSubmitted, setQuizSubmitted] = useState(false);

  // Submit Performance Task Modal (student_performance_tasks.php)
  const [submitTaskModal, setSubmitTaskModal] = useState<StudentTask | null>(null);
  const [submissionFileName, setSubmissionFileName] = useState('');
  const [submissionRemarks, setSubmissionRemarks] = useState('');
  const [submitSuccess, setSubmitSuccess] = useState(false);

  // Profile Form State (student_profile.php)
  const [profileSaved, setProfileSaved] = useState(false);
  const [profileData, setProfileData] = useState({
    firstName: 'Bryan',
    middleName: 'Gomez',
    lastName: 'Donceras',
    suffix: '',
    studentId: 'STUD-123456',
    lrn: '109823456712',
    gradeLevel: 'Grade 11',
    strand: 'STEM',
    section: 'STEM 11-A',
    birthdate: '2008-05-14',
    age: 18,
    gender: 'Male',
    contact: '09123456789',
    address: 'Poblacion, Mandaon, Masbate',
    guardianName: 'Roberto Donceras',
    guardianContact: '0917-889-1234',
  });

  // Settings State (settings.php)
  const [currentPass, setCurrentPass] = useState('');
  const [newPass, setNewPass] = useState('');
  const [confirmPass, setConfirmPass] = useState('');
  const [passUpdated, setPassUpdated] = useState(false);
  const [notifyPush, setNotifyPush] = useState(true);
  const [notifyEmail, setNotifyEmail] = useState(true);

  // Announcements Search state (students/index.php)
  const [studentAnnSearch, setStudentAnnSearch] = useState('');

  // Virtual class studio controls
  const [isMuted, setIsMuted] = useState(false);

  // Timer effect for quiz runner
  useEffect(() => {
    let interval: ReturnType<typeof setInterval> | null = null;
    if (activeQuiz && !quizSubmitted && quizTimer > 0) {
      interval = setInterval(() => {
        setQuizTimer((prev) => (prev > 0 ? prev - 1 : 0));
      }, 1000);
    }
    return () => {
      if (interval) clearInterval(interval);
    };
  }, [activeQuiz, quizSubmitted, quizTimer]);

  const formatTimer = (seconds: number) => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
  };

  const sampleQuestions = [
    {
      q: 'Which of the following defines the vertical asymptote of a rational function f(x) = P(x) / Q(x)?',
      options: [
        'A. The values of x where Q(x) = 0 and P(x) ≠ 0',
        'B. The ratio of the leading coefficients when degrees are equal',
        'C. The y-intercept where x = 0',
        'D. The horizontal line y = c as x approaches infinity',
      ],
      correct: 'A. The values of x where Q(x) = 0 and P(x) ≠ 0',
    },
    {
      q: 'What is the horizontal asymptote of f(x) = (3x² - 5) / (x² + 2)?',
      options: ['A. y = 0', 'B. y = 3', 'C. y = -5/2', 'D. No horizontal asymptote'],
      correct: 'B. y = 3',
    },
    {
      q: 'Find the domain of f(x) = (x + 4) / (x - 2).',
      options: [
        'A. All real numbers except x = -4',
        'B. All real numbers except x = 2',
        'C. All real numbers except x = 0',
        'D. All real numbers',
      ],
      correct: 'B. All real numbers except x = 2',
    },
  ];

  const handleQuizFinish = () => {
    setQuizSubmitted(true);
    if (activeQuiz) {
      setTasks((prev) =>
        prev.map((t) =>
          t.id === activeQuiz.id
            ? { ...t, status: 'submitted', grade: 38, feedback: 'Auto-graded: 38/40' }
            : t
        )
      );
    }
    setTimeout(() => {
      setActiveQuiz(null);
      setQuizSubmitted(false);
      setQuizTimer(1799);
      setSelectedAnswers({});
    }, 2200);
  };

  const handleDeliverableSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!submitTaskModal) return;
    setSubmitSuccess(true);
    setTasks((prev) =>
      prev.map((t) =>
        t.id === submitTaskModal.id ? { ...t, status: 'submitted' } : t
      )
    );
    setTimeout(() => {
      setSubmitTaskModal(null);
      setSubmitSuccess(false);
      setSubmissionFileName('');
      setSubmissionRemarks('');
    }, 1500);
  };

  const handleProfileSave = (e: React.FormEvent) => {
    e.preventDefault();
    setProfileSaved(true);
    setTimeout(() => setProfileSaved(false), 2500);
  };

  const handlePasswordSave = (e: React.FormEvent) => {
    e.preventDefault();
    if (newPass !== confirmPass) return;
    setPassUpdated(true);
    setCurrentPass('');
    setNewPass('');
    setConfirmPass('');
    setTimeout(() => setPassUpdated(false), 2500);
  };

  return (
    <div className="space-y-6">
      {/* ============================================================ */}
      {/* 1. DASHBOARD PAGE (students/index.php lines 531-880)          */}
      {/* ============================================================ */}
      {activeTab === 'dashboard' && (
        <div className="space-y-6">
          {/* Section Header (students/index.php lines 532-537) */}
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-1">
            <div>
              <div className="flex items-center gap-2">
                <h1 className="font-serif text-2xl sm:text-3xl font-bold tracking-tight text-text">
                  Welcome back, Bryan!
                </h1>
                <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/25">
                  🔥 7-day streak
                </span>
              </div>
              <p className="text-xs sm:text-sm text-text2 mt-1">
                Keep up the momentum — Grade 11 STEM Section A · S.Y. 2026–2027
              </p>
            </div>
            <div className="flex items-center gap-2">
              <button
                type="button"
                onClick={() => onTabChange('tasks')}
                className="btn btn-primary btn-sm text-xs cursor-pointer shadow-sm hover:shadow-md"
              >
                <RiFileList3Line size={14} />
                Open Task Board
              </button>
            </div>
          </div>

          {/* Stats Grid (students/index.php lines 538-565) */}
          <div className="stats-grid">
            <div className="stat-card blue group">
              <div className="stat-icon">
                <RiBookOpenLine size={22} />
              </div>
              <div className="stat-value">6</div>
              <div className="stat-label">Enrolled Courses</div>
              <div className="stat-change up">
                <span>↑ 1 new this week</span>
              </div>
            </div>
            <div className="stat-card green group">
              <div className="stat-icon">
                <RiCheckboxCircleLine size={22} />
              </div>
              <div className="stat-value">24</div>
              <div className="stat-label">Lessons Completed</div>
              <div className="stat-change up">
                <span>↑ 5 this week</span>
              </div>
            </div>
            <div className="stat-card amber group">
              <div className="stat-icon">
                <RiAwardLine size={22} />
              </div>
              <div className="stat-value">91.4%</div>
              <div className="stat-label">Average Grade</div>
              <div className="stat-change up">
                <span>↑ across all subjects</span>
              </div>
            </div>
            <div className="stat-card purple group">
              <div className="stat-icon">
                <RiFileList3Line size={22} />
              </div>
              <div className="stat-value">12</div>
              <div className="stat-label">Quizzes Taken</div>
              <div className="stat-change up">
                <span>↑ 12 completed</span>
              </div>
            </div>
          </div>

          {/* Student Learning Activity & Contribution Heatmap (QoL feature, GitHub-style) */}
          <StudentActivityHeatmap />

          {/* Announcements Card with Search Form (students/index.php lines 566-677) */}
          <div className="card">
            <div className="card-header">
              <div className="card-title">
                <div className="p-1.5 rounded-lg bg-accent/10 text-accent">
                  <RiNotification3Line size={18} />
                </div>
                <span>Announcements</span>
              </div>
              <span className="text-xs text-text3 font-medium">3 active notices</span>
            </div>

            {/* Search Form */}
            <form
              onSubmit={(e) => e.preventDefault()}
              className="relative flex gap-2 items-center mb-4"
            >
              <div className="relative flex-1">
                <RiSearchLine size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-text3 pointer-events-none" />
                <input
                  type="text"
                  value={studentAnnSearch}
                  onChange={(e) => setStudentAnnSearch(e.target.value)}
                  placeholder="Search announcements by keyword, topic or faculty..."
                  className="w-full pl-9 pr-8 py-2 rounded-xl border border-border bg-bg3 text-xs text-text placeholder:text-text3 outline-none focus:border-accent focus:ring-2 focus:ring-accent/15 transition-all"
                />
                {studentAnnSearch && (
                  <button
                    type="button"
                    onClick={() => setStudentAnnSearch('')}
                    className="absolute right-2.5 top-1/2 -translate-y-1/2 text-text3 hover:text-text p-0.5 cursor-pointer"
                  >
                    <RiCloseLine size={14} />
                  </button>
                )}
              </div>
            </form>

            <div className="space-y-3">
              {[
                {
                  id: 1,
                  type: 'important',
                  posterRole: 'admin',
                  posterName: 'ExceLearn Admin',
                  timeLabel: '2 hrs ago',
                  title: 'Academic Year 2026–2027 Midterm Examination Schedule',
                  message:
                    'Senior High School midterm examinations for Grade 11 and Grade 12 will commence on October 20–24, 2026. Please ensure all performance tasks are submitted beforehand.',
                },
                {
                  id: 2,
                  type: 'info',
                  posterRole: 'teacher',
                  posterName: 'Ms. Juna Velarde',
                  timeLabel: '1 day ago',
                  title: 'Virtual Class Attendance & DepEd Submission Guidelines',
                  message:
                    'All learners are required to log in 5 minutes before scheduled virtual classes. Session recordings will be published under the Subject Modules tab.',
                },
                {
                  id: 3,
                  type: 'update',
                  posterRole: 'teacher',
                  posterName: 'Ms. Juna Velarde',
                  timeLabel: '3 days ago',
                  title: 'Science & Math Blended Workshop at Cabitan Campus',
                  message:
                    'Hands-on laboratory sessions for STEM learners will take place this Friday at the Cabitan Campus Science Lab. Bring your lab notebooks and safety gear.',
                },
              ]
                .filter(
                  (a) =>
                    !studentAnnSearch ||
                    a.title.toLowerCase().includes(studentAnnSearch.toLowerCase()) ||
                    a.message.toLowerCase().includes(studentAnnSearch.toLowerCase())
                )
                .map((a) => (
                  <div key={a.id} className={`announce-item ${a.type} flex flex-col gap-1.5`}>
                    <div className="announce-header flex items-center gap-2">
                      <span className={`announce-tag ${a.type}`}>{a.type}</span>
                      <span
                        className="inline-flex items-center gap-1 text-[11px] font-semibold px-2 py-0.5 rounded-full"
                        style={{
                          background: a.posterRole === 'admin' ? 'rgba(239, 68, 68, 0.12)' : 'rgba(139, 92, 246, 0.12)',
                          color: a.posterRole === 'admin' ? 'var(--red)' : 'var(--purple)',
                        }}
                      >
                        {a.posterRole === 'admin' ? <RiShieldCheckLine size={12} /> : <RiUserStarLine size={12} />}
                        {a.posterRole === 'admin' ? 'Admin' : 'Teacher'}: {a.posterName}
                      </span>
                      <span className="announce-time text-[11px] text-text3 ml-auto font-medium">{a.timeLabel}</span>
                    </div>
                    <div className="text-sm font-bold text-text pt-0.5">{a.title}</div>
                    <div className="text-xs text-text2 leading-relaxed">{a.message}</div>
                  </div>
                ))}
            </div>
          </div>

          {/* Dash Grid 1: My Subject Progress & Upcoming Deadlines (students/index.php lines 678-743) */}
          <div className="dash-grid dash-grid-2-1">
            {/* My Subject Progress */}
            <div className="card">
              <div className="card-header">
                <div className="card-title">
                  <div className="p-1.5 rounded-lg bg-green/10 text-green">
                    <RiBarChartLine size={18} />
                  </div>
                  <span>My Subject Progress</span>
                </div>
                <button
                  type="button"
                  onClick={() => onTabChange('courses')}
                  className="btn btn-ghost btn-sm text-xs cursor-pointer inline-flex items-center gap-1"
                >
                  <span>View all</span>
                  <RiArrowRightLine size={13} />
                </button>
              </div>
              <div className="space-y-4">
                {[
                  { subject: 'General Mathematics', code: 'MATH', done: 8, total: 10, pct: 80, fill: '', color: 'var(--accent)', iconBg: 'bg-accent/10 text-accent' },
                  { subject: 'Oral Communication in Context', code: 'ENG', done: 9, total: 10, pct: 90, fill: 'green-fill', color: 'var(--green)', iconBg: 'bg-green/10 text-green' },
                  { subject: 'Earth and Life Science', code: 'SCI', done: 6, total: 10, pct: 60, fill: '', color: 'var(--accent)', iconBg: 'bg-purple/10 text-purple' },
                  { subject: 'Empowerment Technologies (ICT)', code: 'ICT', done: 9, total: 10, pct: 90, fill: 'green-fill', color: 'var(--green)', iconBg: 'bg-cyan/10 text-cyan' },
                  { subject: 'Komunikasyon at Pananaliksik', code: 'FIL', done: 7, total: 10, pct: 70, fill: '', color: 'var(--accent)', iconBg: 'bg-amber/10 text-amber' },
                  { subject: 'Physical Education and Health 1', code: 'PEH', done: 10, total: 10, pct: 100, fill: 'green-fill', color: 'var(--green)', iconBg: 'bg-green/10 text-green' },
                ].map((s, idx) => (
                  <div
                    key={idx}
                    onClick={() => onTabChange('courses')}
                    className="p-2 -mx-2 rounded-xl hover:bg-bg3/60 cursor-pointer group transition-all"
                  >
                    <div className="flex items-center gap-3 mb-1.5">
                      <div className={`w-8 h-8 rounded-lg flex items-center justify-center font-bold text-xs shrink-0 ${s.iconBg}`}>
                        {s.code}
                      </div>
                      <div className="flex-1 min-w-0">
                        <div className="text-xs font-semibold text-text truncate group-hover:text-accent transition-colors">
                          {s.subject}
                        </div>
                        <div className="text-[11px] text-text3 font-medium">{s.done} of {s.total} lessons completed</div>
                      </div>
                      <span className="text-xs font-bold tabular-nums px-2 py-0.5 rounded-md bg-bg3 border border-border/40" style={{ color: s.color }}>
                        {s.pct}%
                      </span>
                    </div>
                    <div className="progress-bar">
                      <div className={`progress-fill ${s.fill}`} style={{ width: `${s.pct}%` }} />
                    </div>
                  </div>
                ))}
              </div>
            </div>

            {/* Upcoming Deadlines */}
            <div className="card">
              <div className="card-header">
                <div className="card-title">
                  <div className="p-1.5 rounded-lg bg-red/10 text-red">
                    <RiTimeLine size={18} />
                  </div>
                  <span>Upcoming Deadlines</span>
                </div>
              </div>
              <div className="divide-y divide-border">
                {[
                  { title: 'General Mathematics — Rational Functions Quiz #3', meta: 'Quarter 2 Assessment', due: 'Today', urgency: 'urgent' },
                  { title: 'Oral Communication — Extemporaneous Speech Recording', meta: 'Performance Task Deliverable', due: 'Tomorrow', urgency: 'soon' },
                  { title: 'Earth and Life Science — Biogeochemical Cycles Diagram', meta: 'Module 3 Worksheet Submission', due: 'May 20, 11:59 PM', urgency: 'later' },
                  { title: 'Empowerment Technologies — Web Portfolio Wireframe', meta: 'ICT Project Deliverable', due: 'May 24, 5:00 PM', urgency: 'later' },
                ].map((d, idx) => (
                  <div key={idx} className="deadline-item py-3">
                    <div className="relative flex items-center justify-center shrink-0">
                      {d.urgency === 'urgent' && (
                        <span className="animate-ping absolute inline-flex h-3 w-3 rounded-full bg-red opacity-60" />
                      )}
                      <div className={`deadline-dot ${d.urgency}`} />
                    </div>
                    <div className="deadline-info">
                      <div className="deadline-title font-semibold">{d.title}</div>
                      <div className="deadline-meta">{d.meta}</div>
                    </div>
                    <div className={`deadline-due text-[11px] px-2.5 py-1 rounded-full font-semibold border ${
                      d.urgency === 'urgent'
                        ? 'bg-red-500/10 text-red border-red-500/25'
                        : d.urgency === 'soon'
                        ? 'bg-amber-500/10 text-amber border-amber-500/25'
                        : 'bg-green-500/10 text-green border-green-500/25'
                    }`}>
                      {d.due}
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </div>

          {/* Dash Grid 2: Recent Quiz Scores & Class Leaderboard (students/index.php lines 745-820) */}
          <div className="dash-grid dash-grid-1-1">
            {/* Recent Quiz Scores */}
            <div className="card">
              <div className="card-header">
                <div className="card-title">
                  <div className="p-1.5 rounded-lg bg-purple/10 text-purple">
                    <RiFileList3Line size={18} />
                  </div>
                  <span>Recent Quiz Scores</span>
                </div>
                <button
                  type="button"
                  onClick={() => onTabChange('grades')}
                  className="btn btn-ghost btn-sm text-xs cursor-pointer inline-flex items-center gap-1"
                >
                  <span>All Scores</span>
                  <RiArrowRightLine size={13} />
                </button>
              </div>
              <div>
                {[
                  { course: 'General Mathematics: Rational Functions', take: 1, score: 95, pct: 95, fill: 'green-fill', color: 'var(--green)', label: 'Excellent' },
                  { course: 'Oral Communication: Speech Models', take: 2, score: 92, pct: 92, fill: 'green-fill', color: 'var(--green)', label: 'Excellent' },
                  { course: 'Earth Science: Rock Formations', take: 1, score: 85, pct: 85, fill: '', color: 'var(--accent)', label: 'Good' },
                  { course: 'Empowerment Tech: Web Principles', take: 1, score: 98, pct: 98, fill: 'green-fill', color: 'var(--green)', label: 'Excellent' },
                  { course: 'Komunikasyon: Barayti ng Wika', take: 1, score: 76, pct: 76, fill: 'amber-fill', color: 'var(--amber)', label: 'Fair' },
                ].map((g, idx) => (
                  <div key={idx} className="grade-row py-2.5">
                    <div className="grade-course">
                      <span className="font-medium text-text">{g.course}</span>{' '}
                      <span className="text-[10px] text-text3 font-bold px-1.5 py-0.5 rounded bg-bg3 border border-border/50">Take #{g.take}</span>
                    </div>
                    <div className="grade-score font-bold tabular-nums text-sm" style={{ color: g.color }}>
                      {g.score}%
                    </div>
                    <div className="grade-bar">
                      <div className="progress-bar">
                        <div className={`progress-fill ${g.fill}`} style={{ width: `${g.pct}%` }} />
                      </div>
                    </div>
                    <div className="grade-label">
                      <span className={`text-[10px] font-semibold px-2 py-0.5 rounded-full ${
                        g.label === 'Excellent'
                          ? 'bg-green-500/10 text-green border border-green-500/20'
                          : g.label === 'Good'
                          ? 'bg-accent/10 text-accent border border-accent/20'
                          : 'bg-amber-500/10 text-amber border border-amber-500/20'
                      }`}>
                        {g.label}
                      </span>
                    </div>
                  </div>
                ))}
              </div>
            </div>

            {/* Class Leaderboard */}
            <div className="card">
              <div className="card-header">
                <div className="card-title">
                  <div className="p-1.5 rounded-lg bg-amber/10 text-amber">
                    <RiTrophyLine size={18} />
                  </div>
                  <span>Class Leaderboard</span>
                </div>
                <span className="badge badge-blue">Grade 11 - STEM</span>
              </div>
              <div className="space-y-1.5">
                {[
                  { rank: 1, rankType: 'gold', initials: 'KD', name: 'Katrina Diaz', score: '98.5%', isMe: false },
                  { rank: 2, rankType: 'silver', initials: 'BD', name: 'Bryan Donceras', score: '96.2%', isMe: true },
                  { rank: 3, rankType: 'bronze', initials: 'JL', name: 'Janloyd Lotino', score: '94.8%', isMe: false },
                  { rank: 4, rankType: 'normal', initials: 'MS', name: 'Mary Santos', score: '93.1%', isMe: false },
                  { rank: 5, rankType: 'normal', initials: 'RA', name: 'Rafael Aquino', score: '91.5%', isMe: false },
                ].map((s) => (
                  <div
                    key={s.rank}
                    className={`flex items-center gap-3 p-2.5 rounded-xl transition-all ${
                      s.isMe
                        ? 'bg-accent/10 border-2 border-accent/40 shadow-xs ring-2 ring-accent/15'
                        : 'hover:bg-bg3/60'
                    }`}
                  >
                    <div className="w-6 h-6 flex items-center justify-center shrink-0">
                      {s.rank === 1 && <RiTrophyFill size={18} className="text-amber-500" />}
                      {s.rank === 2 && <RiMedalFill size={18} className="text-slate-400 dark:text-slate-300" />}
                      {s.rank === 3 && <RiMedalLine size={18} className="text-amber-700 dark:text-amber-600" />}
                      {s.rank > 3 && (
                        <span className="text-xs font-bold text-text3 tabular-nums">#{s.rank}</span>
                      )}
                    </div>
                    <div
                      className="w-7 h-7 rounded-full flex items-center justify-center font-bold text-[11px] text-white shrink-0 shadow-xs"
                      style={{
                        background:
                          s.rank === 1
                            ? 'linear-gradient(135deg, #f59e0b, #d97706)'
                            : s.rank === 2
                            ? 'linear-gradient(135deg, #06b6d4, #3b82f6)'
                            : s.rank === 3
                            ? 'linear-gradient(135deg, #8b5cf6, #6d28d9)'
                            : 'linear-gradient(135deg, #10b981, #059669)',
                      }}
                    >
                      {s.initials}
                    </div>
                    <div className="flex-1 min-w-0">
                      <div className="text-xs font-semibold text-text truncate flex items-center gap-1.5">
                        <span>{s.name}</span>
                        {s.isMe && (
                          <span className="text-[10px] font-bold px-1.5 py-0.5 rounded bg-accent text-white">
                            You
                          </span>
                        )}
                      </div>
                    </div>
                    <div className="text-xs font-bold text-accent tabular-nums">
                      {s.score}
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </div>

          {/* Dash Grid 3: Recent Activity (students/index.php lines 821-880) */}
          <div className="dash-grid dash-grid-1-1">
            <div className="card">
              <div className="card-header">
                <div className="card-title">
                  <div className="p-1.5 rounded-lg bg-cyan/10 text-cyan">
                    <RiHistoryLine size={18} />
                  </div>
                  <span>Recent Activity</span>
                </div>
              </div>
              <div className="relative pl-6 space-y-4 before:absolute before:left-2.5 before:top-2 before:bottom-2 before:w-0.5 before:bg-border">
                {[
                  { passed: true, score: '95%', quiz: 'General Mathematics Quiz #1', time: '2 hrs ago' },
                  { passed: true, score: '92%', quiz: 'Oral Communication Speech Quiz #2', time: 'Yesterday' },
                  { passed: true, score: '98%', quiz: 'Empowerment Technologies Unit 1 Exam', time: '2 days ago' },
                  { passed: false, score: '68%', quiz: 'Komunikasyon at Pananaliksik Diagnostic', time: '4 days ago' },
                ].map((act, idx) => (
                  <div key={idx} className="relative flex items-start gap-3 group">
                    <div
                      className={`absolute -left-6 top-0.5 w-5 h-5 rounded-full flex items-center justify-center ring-4 ring-bg2 shrink-0 ${
                        act.passed
                          ? 'bg-green text-white'
                          : 'bg-amber text-white'
                      }`}
                    >
                      {act.passed ? <RiCheckLine size={12} /> : <RiCloseLine size={12} />}
                    </div>
                    <div className="flex-1 min-w-0">
                      <div className="text-xs text-text2 leading-snug">
                        <span className="font-bold text-text">Scored {act.score}</span>{' '}
                        <span className={act.passed ? 'text-green font-semibold' : 'text-amber font-semibold'}>
                          {act.passed ? '✓ Passed' : '✗ Below passing'}
                        </span>{' '}
                        on <span className="font-medium text-text">{act.quiz}</span>
                      </div>
                      <div className="text-[11px] text-text3 font-medium mt-0.5">{act.time}</div>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>
      )}

      {/* ============================================================ */}
      {/* 2. MY COURSES & MODULES (students/student_courses.php)        */}
      {/* ============================================================ */}
      {activeTab === 'courses' && (
        <div className="space-y-4">
          <div>
            <h3 className="text-xl font-bold font-serif text-text">My Courses & Syllabus Modules</h3>
            <p className="text-xs text-text2">
              Grade 11 STEM Section A · Semester 1 Academic Year 2026–2027
            </p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {SUBJECTS_LIST.map((subj) => (
              <div
                key={subj.id}
                className="rounded-xl bg-bg2 border border-border p-4 space-y-3 hover:border-accent/40 transition-all flex flex-col justify-between"
              >
                <div>
                  <div className="flex items-center justify-between mb-2">
                    <span className="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded bg-accent/10 text-accent">
                      {subj.code}
                    </span>
                    {subj.hasLive && (
                      <span className="flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded bg-cyan-bg text-cyan">
                        <span className="size-1.5 rounded-full bg-cyan animate-pulse" />
                        Live Now
                      </span>
                    )}
                  </div>

                  <h4 className="text-base font-bold text-text">{subj.title}</h4>
                  <p className="text-xs text-text3 mt-0.5">{subj.teacher}</p>
                  <p className="text-[11px] text-text2 mt-1">{subj.schedule}</p>
                </div>

                <div className="pt-3 border-t border-border space-y-2">
                  <div className="text-xs font-semibold text-text truncate">
                    {subj.currentModule}
                  </div>

                  <div>
                    <div className="flex items-center justify-between text-[11px] text-text3 mb-1">
                      <span>Completion</span>
                      <span className="tabular-nums font-semibold text-text">{subj.progressPercent}%</span>
                    </div>
                    <div className="h-1.5 w-full bg-bg3 rounded-full overflow-hidden">
                      <div
                        className="h-full bg-accent rounded-full transition-all"
                        style={{ width: `${subj.progressPercent}%` }}
                      />
                    </div>
                  </div>

                  <div className="flex items-center gap-2 pt-1">
                    <button
                      type="button"
                      onClick={() => setActivePdfModule({ title: subj.currentModule, subject: subj.title })}
                      className="flex-1 flex items-center justify-center gap-1 py-1.5 px-2 rounded-lg bg-bg3 hover:bg-bg4 text-text text-xs font-medium transition-colors cursor-pointer"
                    >
                      <RiFilePdfLine className="size-3.5 text-red" aria-hidden="true" />
                      <span>Read PDF</span>
                    </button>
                    {subj.hasLive ? (
                      <button
                        type="button"
                        onClick={() => onTabChange('live')}
                        className="flex-1 flex items-center justify-center gap-1 py-1.5 px-2 rounded-lg bg-cyan text-[#0e0f14] text-xs font-semibold hover:brightness-105 transition-all cursor-pointer"
                      >
                        <RiVideoChatLine className="size-3.5" aria-hidden="true" />
                        <span>Join Live</span>
                      </button>
                    ) : (
                      <button
                        type="button"
                        onClick={() => onTabChange('tasks')}
                        className="flex-1 flex items-center justify-center gap-1 py-1.5 px-2 rounded-lg bg-accent/10 hover:bg-accent/20 text-accent text-xs font-medium transition-colors cursor-pointer"
                      >
                        <span>Tasks</span>
                      </button>
                    )}
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* ============================================================ */}
      {/* 3. QUIZZES RUNNER (students/student_quizzes.php)              */}
      {/* ============================================================ */}
      {activeTab === 'quizzes' && (
        <div className="space-y-4">
          <div className="flex items-center justify-between">
            <div>
              <h3 className="text-xl font-bold font-serif text-text">Timed Academic Quizzes</h3>
              <p className="text-xs text-text2">
                Automated assessments aligned with DepEd learning competencies.
              </p>
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            {tasks.filter((t) => t.category === 'Quiz').map((quiz) => (
              <div
                key={quiz.id}
                className="p-5 rounded-xl bg-bg2 border border-border shadow-xs space-y-3 hover:border-accent/40 transition-all flex flex-col justify-between"
              >
                <div>
                  <div className="flex items-center justify-between mb-2">
                    <span className="text-[10px] font-bold px-2 py-0.5 rounded bg-amber-bg text-amber uppercase tracking-wider">
                      {quiz.subject}
                    </span>
                    <span className="text-xs font-bold text-accent tabular-nums">
                      {quiz.points} Points
                    </span>
                  </div>
                  <h4 className="text-base font-bold text-text">{quiz.title}</h4>
                  <p className="text-xs text-text3 mt-1">Due: {quiz.dueDate} at {quiz.dueTime}</p>
                </div>

                <div className="pt-3 border-t border-border flex items-center justify-between">
                  <div className="text-xs">
                    {quiz.status === 'submitted' ? (
                      <span className="text-green font-semibold flex items-center gap-1">
                        <RiCheckLine className="size-4" aria-hidden="true" />
                        Completed ({quiz.grade}/{quiz.points})
                      </span>
                    ) : (
                      <span className="text-amber font-medium">Pending Attempt</span>
                    )}
                  </div>

                  {quiz.status !== 'submitted' ? (
                    <button
                      type="button"
                      onClick={() => setActiveQuiz(quiz)}
                      className="px-4 py-1.5 rounded-lg bg-accent text-white text-xs font-semibold hover:bg-accent2 cursor-pointer shadow-xs"
                    >
                      Take Quiz Now
                    </button>
                  ) : (
                    <span className="text-xs text-text3">Recorded</span>
                  )}
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* ============================================================ */}
      {/* 4. PERFORMANCE TASKS (students/student_performance_tasks.php) */}
      {/* ============================================================ */}
      {activeTab === 'tasks' && (
        <div className="space-y-4">
          <div className="flex items-center justify-between">
            <div>
              <h3 className="text-xl font-bold font-serif text-text">Performance Tasks & Submissions</h3>
              <p className="text-xs text-text2">
                Upload demonstration videos, laboratory papers, and research outputs for rubric evaluation.
              </p>
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            {tasks.filter((t) => t.category === 'Performance Task' || t.category === 'Written Work').map((task) => (
              <div
                key={task.id}
                className="p-5 rounded-xl bg-bg2 border border-border shadow-xs space-y-3 hover:border-accent/40 transition-all flex flex-col justify-between"
              >
                <div>
                  <div className="flex items-center justify-between mb-2">
                    <span className="text-[10px] font-bold px-2 py-0.5 rounded bg-accent/15 text-accent uppercase tracking-wider">
                      {task.subject}
                    </span>
                    <span className="text-xs font-bold text-accent tabular-nums">
                      {task.points} pts
                    </span>
                  </div>
                  <h4 className="text-base font-bold text-text">{task.title}</h4>
                  <p className="text-xs text-text3 mt-1">Due: {task.dueDate} at {task.dueTime}</p>
                </div>

                <div className="pt-3 border-t border-border flex items-center justify-between">
                  <div className="text-xs">
                    {task.status === 'submitted' ? (
                      <span className="text-green font-semibold flex items-center gap-1">
                        <RiCheckLine className="size-4" aria-hidden="true" />
                        Submitted ({task.grade ?? 'Pending Evaluation'})
                      </span>
                    ) : (
                      <span className="text-amber font-medium">To Submit</span>
                    )}
                  </div>

                  {task.status !== 'submitted' ? (
                    <button
                      type="button"
                      onClick={() => setSubmitTaskModal(task)}
                      className="flex items-center gap-1 px-4 py-1.5 rounded-lg bg-green text-[#0e0f14] text-xs font-semibold hover:brightness-105 cursor-pointer shadow-xs"
                    >
                      <RiUpload2Line className="size-3.5" aria-hidden="true" />
                      <span>Submit Output</span>
                    </button>
                  ) : (
                    <span className="text-xs text-text3">Graded / Stored</span>
                  )}
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* ============================================================ */}
      {/* 5. VIRTUAL CLASSROOM (students/student_virtual_class.php)      */}
      {/* ============================================================ */}
      {activeTab === 'live' && (
        <div className="space-y-4">
          <div className="flex items-center justify-between">
            <div>
              <h3 className="text-xl font-bold font-serif text-text">WebRTC Virtual Classroom</h3>
              <p className="text-xs text-text2">
                Oral Communication in Context with Ms. Juna Velarde
              </p>
            </div>
            <span className="px-3 py-1 rounded-full bg-cyan-bg text-cyan text-xs font-semibold flex items-center gap-1.5">
              <span className="size-2 rounded-full bg-cyan animate-ping" />
              Connected · 28 Students Active
            </span>
          </div>

          <div className="rounded-2xl bg-[#090a0f] border border-border overflow-hidden shadow-2xl">
            <div className="relative aspect-video flex items-center justify-center p-6 text-white">
              <div className="text-center space-y-3">
                <div className="size-20 mx-auto rounded-full bg-cyan/20 border-2 border-cyan flex items-center justify-center text-cyan">
                  <RiVideoChatLine className="size-10" aria-hidden="true" />
                </div>
                <div>
                  <h4 className="text-lg font-bold">Ms. Juna Velarde (Broadcasting)</h4>
                  <p className="text-xs text-white/70">Topic: Speech Acts & Non-Verbal Dynamics</p>
                </div>
                <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 text-xs text-green">
                  <span className="size-2 rounded-full bg-green animate-pulse" />
                  HD 1080p · Low-Latency WebRTC Signaling
                </div>
              </div>
            </div>

            <div className="p-4 bg-bg2 border-t border-border flex items-center justify-between">
              <div className="flex items-center gap-2">
                <button
                  type="button"
                  onClick={() => setIsMuted(!isMuted)}
                  className={`p-2 rounded-lg cursor-pointer ${
                    isMuted ? 'bg-red text-white' : 'bg-bg3 text-text border border-border'
                  }`}
                  aria-label={isMuted ? 'Unmute microphone' : 'Mute microphone'}
                >
                  {isMuted ? <RiMicOffLine className="size-4" /> : <RiMicLine className="size-4" />}
                </button>
                <button
                  type="button"
                  className="p-2 rounded-lg bg-bg3 text-text border border-border cursor-pointer"
                  aria-label="Toggle camera"
                >
                  <RiCameraLine className="size-4" />
                </button>
              </div>

              <button
                type="button"
                onClick={() => onTabChange('dashboard')}
                className="px-4 py-2 rounded-xl bg-red text-white text-xs font-semibold hover:bg-red/90 cursor-pointer"
              >
                Leave Classroom
              </button>
            </div>
          </div>
        </div>
      )}

      {/* ============================================================ */}
      {/* 6. GRADES & CERTIFICATES (students/student_grades.php)        */}
      {/* ============================================================ */}
      {activeTab === 'grades' && (
        <div className="space-y-6">
          <div className="p-5 rounded-xl bg-bg2 border border-border space-y-3">
            <div className="flex items-center justify-between">
              <div>
                <h3 className="text-lg font-bold font-serif text-text">DepEd Form 138-SHS (Report Card)</h3>
                <p className="text-xs text-text2">Bryan Donceras · STEM 11-A · General Weighted Average: 92.8</p>
              </div>
              <button
                type="button"
                onClick={() => alert('Downloading official DepEd Form 138 PDF via Dompdf...')}
                className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-accent text-white text-xs font-semibold hover:bg-accent2 cursor-pointer shadow-xs"
              >
                <RiDownload2Line className="size-4" aria-hidden="true" />
                <span>Download Form 138 PDF</span>
              </button>
            </div>

            <div className="overflow-x-auto">
              <table className="w-full text-left text-xs">
                <thead className="bg-bg3 text-text3 uppercase tracking-wider">
                  <tr>
                    <th className="py-2.5 px-3 font-semibold">Subject Title</th>
                    <th className="py-2.5 px-3 font-semibold text-center">Prelims (Q1)</th>
                    <th className="py-2.5 px-3 font-semibold text-center">Midterms (Q2)</th>
                    <th className="py-2.5 px-3 font-semibold text-center">Final Rating</th>
                    <th className="py-2.5 px-3 font-semibold text-right">Remarks</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-border">
                  <tr>
                    <td className="py-2.5 px-3 font-medium text-text">General Mathematics</td>
                    <td className="py-2.5 px-3 text-center tabular-nums text-text">92</td>
                    <td className="py-2.5 px-3 text-center tabular-nums text-text">94</td>
                    <td className="py-2.5 px-3 text-center tabular-nums font-bold text-green">93</td>
                    <td className="py-2.5 px-3 text-right text-green font-semibold">Passed</td>
                  </tr>
                  <tr>
                    <td className="py-2.5 px-3 font-medium text-text">Oral Communication in Context</td>
                    <td className="py-2.5 px-3 text-center tabular-nums text-text">91</td>
                    <td className="py-2.5 px-3 text-center tabular-nums text-text">95</td>
                    <td className="py-2.5 px-3 text-center tabular-nums font-bold text-green">93</td>
                    <td className="py-2.5 px-3 text-right text-green font-semibold">Passed</td>
                  </tr>
                  <tr>
                    <td className="py-2.5 px-3 font-medium text-text">Earth and Life Science</td>
                    <td className="py-2.5 px-3 text-center tabular-nums text-text">94</td>
                    <td className="py-2.5 px-3 text-center tabular-nums text-text">96</td>
                    <td className="py-2.5 px-3 text-center tabular-nums font-bold text-green">95</td>
                    <td className="py-2.5 px-3 text-right text-green font-semibold">Passed</td>
                  </tr>
                  <tr>
                    <td className="py-2.5 px-3 font-medium text-text">Empowerment Technologies (ICT)</td>
                    <td className="py-2.5 px-3 text-center tabular-nums text-text">95</td>
                    <td className="py-2.5 px-3 text-center tabular-nums text-text">96</td>
                    <td className="py-2.5 px-3 text-center tabular-nums font-bold text-green">95.5</td>
                    <td className="py-2.5 px-3 text-right text-green font-semibold">Passed</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      )}

      {/* ============================================================ */}
      {/* 7. STUDENT PROFILE (students/student_profile.php)             */}
      {/* ============================================================ */}
      {activeTab === 'profile' && (
        <div className="p-6 rounded-xl bg-bg2 border border-border shadow-xs space-y-4">
          <div className="flex items-center justify-between border-b border-border pb-3">
            <div>
              <h3 className="text-xl font-bold font-serif text-text">Student Academic Profile</h3>
              <p className="text-xs text-text2">DepEd Learner Information System (LIS) Master Data</p>
            </div>
            <span className="px-2.5 py-1 text-xs font-bold rounded-lg bg-accent/15 text-accent">
              Enrolled · Active
            </span>
          </div>

          {profileSaved && (
            <div className="p-3 rounded-lg bg-green-bg border border-green/30 text-xs text-green flex items-center gap-2">
              <RiCheckLine className="size-4" aria-hidden="true" />
              <span>Student profile records updated successfully!</span>
            </div>
          )}

          <form onSubmit={handleProfileSave} className="space-y-4">
            <div className="grid grid-cols-1 sm:grid-cols-4 gap-3">
              <div>
                <label className="block text-xs font-semibold text-text2 mb-1">First Name *</label>
                <input
                  type="text"
                  value={profileData.firstName}
                  onChange={(e) => setProfileData({ ...profileData, firstName: e.target.value })}
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text"
                  required
                />
              </div>
              <div>
                <label className="block text-xs font-semibold text-text2 mb-1">Middle Name</label>
                <input
                  type="text"
                  value={profileData.middleName}
                  onChange={(e) => setProfileData({ ...profileData, middleName: e.target.value })}
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text"
                />
              </div>
              <div>
                <label className="block text-xs font-semibold text-text2 mb-1">Last Name *</label>
                <input
                  type="text"
                  value={profileData.lastName}
                  onChange={(e) => setProfileData({ ...profileData, lastName: e.target.value })}
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text"
                  required
                />
              </div>
              <div>
                <label className="block text-xs font-semibold text-text2 mb-1">Suffix</label>
                <input
                  type="text"
                  placeholder="Jr., Sr., III"
                  value={profileData.suffix}
                  onChange={(e) => setProfileData({ ...profileData, suffix: e.target.value })}
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text"
                />
              </div>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <div>
                <label className="block text-xs font-semibold text-text2 mb-1">Student ID</label>
                <input
                  type="text"
                  value={profileData.studentId}
                  disabled
                  className="w-full px-3 py-2 rounded-lg bg-bg4 border border-border text-xs text-text3 font-mono cursor-not-allowed"
                />
              </div>
              <div>
                <label className="block text-xs font-semibold text-text2 mb-1">DepEd LRN (12-Digit)</label>
                <input
                  type="text"
                  value={profileData.lrn}
                  disabled
                  className="w-full px-3 py-2 rounded-lg bg-bg4 border border-border text-xs text-accent font-mono cursor-not-allowed"
                />
              </div>
              <div>
                <label className="block text-xs font-semibold text-text2 mb-1">Grade & Section</label>
                <input
                  type="text"
                  value={`${profileData.gradeLevel} · ${profileData.section}`}
                  disabled
                  className="w-full px-3 py-2 rounded-lg bg-bg4 border border-border text-xs text-text3 cursor-not-allowed"
                />
              </div>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <div>
                <label className="block text-xs font-semibold text-text2 mb-1">Contact Number</label>
                <input
                  type="text"
                  value={profileData.contact}
                  onChange={(e) => setProfileData({ ...profileData, contact: e.target.value })}
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text"
                />
              </div>
              <div>
                <label className="block text-xs font-semibold text-text2 mb-1">Parent / Guardian Name</label>
                <input
                  type="text"
                  value={profileData.guardianName}
                  onChange={(e) => setProfileData({ ...profileData, guardianName: e.target.value })}
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text"
                />
              </div>
              <div>
                <label className="block text-xs font-semibold text-text2 mb-1">Guardian Contact</label>
                <input
                  type="text"
                  value={profileData.guardianContact}
                  onChange={(e) => setProfileData({ ...profileData, guardianContact: e.target.value })}
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text"
                />
              </div>
            </div>

            <div>
              <label className="block text-xs font-semibold text-text2 mb-1">Residential Address</label>
              <input
                type="text"
                value={profileData.address}
                onChange={(e) => setProfileData({ ...profileData, address: e.target.value })}
                className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text"
              />
            </div>

            <div className="flex justify-end">
              <button
                type="submit"
                className="px-5 py-2.5 rounded-xl bg-accent text-white text-xs font-semibold hover:bg-accent2 cursor-pointer shadow-xs transition-all"
              >
                Save Profile Changes
              </button>
            </div>
          </form>
        </div>
      )}

      {/* ============================================================ */}
      {/* 8. SETTINGS (students/settings.php)                          */}
      {/* ============================================================ */}
      {activeTab === 'settings' && (
        <div className="max-w-2xl space-y-6">
          <div className="p-6 rounded-xl bg-bg2 border border-border shadow-xs space-y-4">
            <h3 className="text-xl font-bold font-serif text-text">Account Security</h3>
            <p className="text-xs text-text2">Update your portal password and login security.</p>

            {passUpdated && (
              <div className="p-3 rounded-lg bg-green-bg border border-green/30 text-xs text-green flex items-center gap-2">
                <RiCheckLine className="size-4" aria-hidden="true" />
                <span>Password successfully updated!</span>
              </div>
            )}

            <form onSubmit={handlePasswordSave} className="space-y-3">
              <div>
                <label className="block text-xs font-semibold text-text2 mb-1">Current Password *</label>
                <input
                  type="password"
                  placeholder="Enter current password"
                  value={currentPass}
                  onChange={(e) => setCurrentPass(e.target.value)}
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text"
                  required
                />
              </div>
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-semibold text-text2 mb-1">New Password *</label>
                  <input
                    type="password"
                    placeholder="Min 8 characters"
                    value={newPass}
                    onChange={(e) => setNewPass(e.target.value)}
                    className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text"
                    required
                  />
                </div>
                <div>
                  <label className="block text-xs font-semibold text-text2 mb-1">Confirm New Password *</label>
                  <input
                    type="password"
                    placeholder="Re-enter new password"
                    value={confirmPass}
                    onChange={(e) => setConfirmPass(e.target.value)}
                    className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text"
                    required
                  />
                </div>
              </div>
              <div className="flex justify-end pt-2">
                <button
                  type="submit"
                  className="px-4 py-2 rounded-xl bg-accent text-white text-xs font-semibold hover:bg-accent2 cursor-pointer shadow-xs"
                >
                  Update Password
                </button>
              </div>
            </form>
          </div>

          <div className="p-6 rounded-xl bg-bg2 border border-border shadow-xs space-y-3">
            <h4 className="text-sm font-bold text-text">Notification Preferences (OneSignal)</h4>
            <div className="space-y-3 pt-1">
              <label className="flex items-center justify-between cursor-pointer">
                <div>
                  <div className="text-xs font-medium text-text">Browser Web Push Alerts</div>
                  <div className="text-[11px] text-text3">Notify when a new quiz or module is posted</div>
                </div>
                <input
                  type="checkbox"
                  checked={notifyPush}
                  onChange={(e) => setNotifyPush(e.target.checked)}
                  className="size-4 rounded text-accent"
                />
              </label>
              <label className="flex items-center justify-between cursor-pointer">
                <div>
                  <div className="text-xs font-medium text-text">Email Digest Notifications</div>
                  <div className="text-[11px] text-text3">Receive weekly summary of upcoming deadlines</div>
                </div>
                <input
                  type="checkbox"
                  checked={notifyEmail}
                  onChange={(e) => setNotifyEmail(e.target.checked)}
                  className="size-4 rounded text-accent"
                />
              </label>
            </div>
          </div>
        </div>
      )}

      {/* Screen: About (about.php) */}
      {activeTab === 'about' && (
        <AboutView role="student" onTabChange={onTabChange} />
      )}

      {/* ============================================================ */}
      {/* MODAL 1: PDF MODULE VIEWER (pdf_viewer.php)                  */}
      {/* ============================================================ */}
      {activePdfModule && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
          <div className="w-full max-w-4xl h-[85vh] bg-bg2 rounded-2xl border border-border shadow-2xl overflow-hidden flex flex-col">
            <div className="p-4 bg-bg3 border-b border-border flex items-center justify-between">
              <div className="flex items-center gap-2">
                <RiFilePdfLine className="size-5 text-red" aria-hidden="true" />
                <div>
                  <h4 className="text-xs font-bold text-text">{activePdfModule.title}</h4>
                  <p className="text-[10px] text-text3">{activePdfModule.subject} · Secure PDF Reader</p>
                </div>
              </div>
              <div className="flex items-center gap-2">
                <button
                  type="button"
                  onClick={() => alert('Downloading official module handout...')}
                  className="px-3 py-1.5 rounded-lg bg-bg2 text-text border border-border text-xs font-medium hover:bg-bg4 cursor-pointer flex items-center gap-1"
                >
                  <RiDownload2Line className="size-3.5" aria-hidden="true" />
                  <span>Download</span>
                </button>
                <button
                  type="button"
                  onClick={() => setActivePdfModule(null)}
                  className="p-1 rounded-lg text-text3 hover:text-text cursor-pointer"
                  aria-label="Close PDF Viewer"
                >
                  <RiCloseLine className="size-5" aria-hidden="true" />
                </button>
              </div>
            </div>

            {/* Document Canvas Simulation */}
            <div className="flex-1 bg-bg4/50 p-6 overflow-y-auto flex items-center justify-center">
              <div className="w-full max-w-2xl bg-bg2 border border-border rounded-xl p-8 shadow-md space-y-6">
                <div className="border-b border-border pb-4 flex items-center justify-between">
                  <div>
                    <span className="text-[10px] font-bold uppercase tracking-wider text-accent">DepEd Senior High School</span>
                    <h2 className="text-xl font-bold font-serif text-text mt-1">{activePdfModule.title}</h2>
                  </div>
                  <span className="text-xs text-text3">Page 1 of 12</span>
                </div>

                <div className="space-y-4 text-xs text-text2 leading-relaxed">
                  <p>
                    <strong>Learning Competency:</strong> The learner determines the intercepts, zeroes, and asymptotes of rational functions (M11GM-Ib-1).
                  </p>
                  <p>
                    A <em>rational function</em> is any function which can be written as the ratio of two polynomial functions, where the denominator polynomial is not zero.
                  </p>
                  <div className="p-4 rounded-lg bg-bg3 border border-border font-mono text-[11px] text-center">
                    f(x) = P(x) / Q(x), where Q(x) ≠ 0
                  </div>
                  <p>
                    In this unit, students explore both geometric representations on the Cartesian plane and algebraic solutions for vertical and horizontal asymptotic behavior.
                  </p>
                </div>

                <div className="pt-4 border-t border-border flex items-center justify-between text-xs text-text3">
                  <span>Excelsior Polytechnic Academy · DepEd Curriculum</span>
                  <span>Verified Module Handout</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* ============================================================ */}
      {/* MODAL 2: INTERACTIVE QUIZ RUNNER (student_quizzes.php)       */}
      {/* ============================================================ */}
      {activeQuiz && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md">
          <div className="w-full max-w-2xl bg-bg2 rounded-2xl border border-border shadow-2xl overflow-hidden flex flex-col">
            <div className="p-4 bg-bg3 border-b border-border flex items-center justify-between">
              <div>
                <span className="text-[10px] font-bold uppercase tracking-wider text-amber">Active Examination</span>
                <h4 className="text-sm font-bold text-text">{activeQuiz.title}</h4>
              </div>
              <div className="flex items-center gap-2">
                <div className="px-3 py-1 rounded-lg bg-red/10 border border-red/20 text-red font-mono font-bold text-xs">
                  ⏱ {formatTimer(quizTimer)}
                </div>
                <button
                  type="button"
                  onClick={() => {
                    if (confirm('Leave quiz attempt? Unsaved answers will be discarded.')) {
                      setActiveQuiz(null);
                    }
                  }}
                  className="p-1 rounded-lg text-text3 hover:text-text cursor-pointer"
                >
                  <RiCloseLine className="size-5" aria-hidden="true" />
                </button>
              </div>
            </div>

            <div className="p-6 space-y-6">
              {quizSubmitted ? (
                <div className="py-8 text-center space-y-3">
                  <div className="size-16 mx-auto rounded-full bg-green-bg text-green flex items-center justify-center">
                    <RiCheckLine className="size-8" aria-hidden="true" />
                  </div>
                  <h3 className="text-xl font-bold font-serif text-text">Quiz Submitted!</h3>
                  <p className="text-xs text-text2">Your score has been computed and recorded in the gradebook.</p>
                </div>
              ) : (
                <>
                  <div className="flex items-center justify-between text-xs text-text3">
                    <span>Question {currentQuestionIndex + 1} of {sampleQuestions.length}</span>
                    <span>Single Choice</span>
                  </div>

                  <div className="text-sm font-semibold text-text leading-relaxed">
                    {sampleQuestions[currentQuestionIndex].q}
                  </div>

                  <div className="space-y-2.5">
                    {sampleQuestions[currentQuestionIndex].options.map((opt, i) => {
                      const isSelected = selectedAnswers[currentQuestionIndex] === opt;
                      return (
                        <div
                          key={i}
                          onClick={() =>
                            setSelectedAnswers({
                              ...selectedAnswers,
                              [currentQuestionIndex]: opt,
                            })
                          }
                          className={`p-3.5 rounded-xl border text-xs cursor-pointer transition-all ${
                            isSelected
                              ? 'bg-accent/15 border-accent text-text font-semibold'
                              : 'bg-bg3 border-border hover:border-accent/40 text-text2'
                          }`}
                        >
                          {opt}
                        </div>
                      );
                    })}
                  </div>

                  <div className="pt-4 border-t border-border flex items-center justify-between">
                    <button
                      type="button"
                      disabled={currentQuestionIndex === 0}
                      onClick={() => setCurrentQuestionIndex((prev) => prev - 1)}
                      className="px-4 py-2 rounded-lg bg-bg3 text-text text-xs font-medium disabled:opacity-40 cursor-pointer"
                    >
                      &larr; Previous
                    </button>

                    {currentQuestionIndex < sampleQuestions.length - 1 ? (
                      <button
                        type="button"
                        onClick={() => setCurrentQuestionIndex((prev) => prev + 1)}
                        className="px-4 py-2 rounded-lg bg-accent text-white text-xs font-semibold hover:bg-accent2 cursor-pointer"
                      >
                        Next Question &rarr;
                      </button>
                    ) : (
                      <button
                        type="button"
                        onClick={handleQuizFinish}
                        className="px-5 py-2 rounded-xl bg-green text-[#0e0f14] text-xs font-semibold hover:brightness-105 cursor-pointer shadow-xs"
                      >
                        Submit Examination
                      </button>
                    )}
                  </div>
                </>
              )}
            </div>
          </div>
        </div>
      )}

      {/* ============================================================ */}
      {/* MODAL 3: SUBMIT DELIVERABLE (student_performance_tasks.php)  */}
      {/* ============================================================ */}
      {submitTaskModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-xs">
          <div className="w-full max-w-lg bg-bg2 rounded-2xl border border-border shadow-2xl p-6 space-y-4">
            <div className="flex items-center justify-between border-b border-border pb-3">
              <div>
                <span className="text-[10px] font-bold px-2 py-0.5 rounded bg-green-bg text-green uppercase tracking-wider">
                  Deliverable Submission
                </span>
                <h4 className="text-base font-bold text-text mt-1">{submitTaskModal.title}</h4>
              </div>
              <button
                type="button"
                onClick={() => setSubmitTaskModal(null)}
                className="p-1 rounded-lg text-text3 hover:text-text cursor-pointer"
              >
                <RiCloseLine className="size-5" aria-hidden="true" />
              </button>
            </div>

            {submitSuccess ? (
              <div className="py-6 text-center space-y-2">
                <div className="size-14 mx-auto rounded-full bg-green-bg text-green flex items-center justify-center">
                  <RiCheckLine className="size-7" aria-hidden="true" />
                </div>
                <h4 className="text-base font-bold font-serif text-text">Output Submitted!</h4>
                <p className="text-xs text-text2">Your work is now awaiting teacher evaluation and rubric scoring.</p>
              </div>
            ) : (
              <form onSubmit={handleDeliverableSubmit} className="space-y-4">
                <div>
                  <label className="block text-xs font-semibold text-text2 mb-1.5">
                    Upload Artifact (MP4 Video / PDF / Docx) *
                  </label>
                  <div
                    onClick={() => setSubmissionFileName('Bryan_Donceras_Speech_STEM11A.mp4')}
                    className="border-2 border-dashed border-border rounded-xl p-5 text-center cursor-pointer hover:border-accent transition-colors"
                  >
                    <RiUpload2Line className="size-8 text-text3 mx-auto mb-2" />
                    <span className="text-xs text-text font-medium block">
                      {submissionFileName || 'Click to select project file from device'}
                    </span>
                    <span className="text-[10px] text-text3 mt-0.5 block">
                      Supported formats: MP4, MOV, PDF, DOCX (Max 100MB)
                    </span>
                  </div>
                </div>

                <div>
                  <label className="block text-xs font-semibold text-text2 mb-1">
                    Student Submission Remarks
                  </label>
                  <textarea
                    rows={3}
                    placeholder="Enter any notes or reflections for your teacher..."
                    value={submissionRemarks}
                    onChange={(e) => setSubmissionRemarks(e.target.value)}
                    className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text focus:outline-none focus:border-accent"
                  />
                </div>

                <div className="flex justify-end gap-2 pt-2">
                  <button
                    type="button"
                    onClick={() => setSubmitTaskModal(null)}
                    className="px-4 py-2 rounded-lg bg-bg3 text-text text-xs font-medium cursor-pointer"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    className="px-5 py-2 rounded-xl bg-green text-[#0e0f14] text-xs font-semibold hover:brightness-105 cursor-pointer shadow-xs"
                  >
                    Submit Deliverable
                  </button>
                </div>
              </form>
            )}
          </div>
        </div>
      )}
    </div>
  );
}
