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
  RiGroupLine,
  RiSearchLine,
  RiShieldCheckLine,
  RiUserStarLine,
  RiFileList3Line,
  RiArrowRightLine,
  RiEditLine,
  RiDeleteBinLine,
  RiBarChartLine,
  RiCheckboxCircleLine,
  RiFileTextLine,
  RiQuestionLine,
  RiCalendarEventLine,
} from '@remixicon/react';

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
  const [teacherAnnSearch, setTeacherAnnSearch] = useState('');
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
      {/* 1. TEACHER DASHBOARD (teachers/index.php lines 715-1015)     */}
      {/* ============================================================ */}
      {activeTab === 'dashboard' && (
        <div className="space-y-6">
          {/* Section Header (teachers/index.php lines 715-728) */}
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-1">
            <div>
              <div className="flex items-center gap-2">
                <h1 className="font-serif text-2xl sm:text-3xl font-bold tracking-tight text-text">
                  Good morning, Ms. Velarde!
                </h1>
                <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-accent/15 text-accent border border-accent/25">
                  Senior High Faculty
                </span>
              </div>
              <p className="text-xs sm:text-sm text-text2 mt-1">
                You have <span className="font-semibold text-amber">{pendingSubmissions.length} pending reviews</span> and 3 quizzes scheduled this week.
              </p>
            </div>
            <div className="flex items-center gap-2">
              <button
                type="button"
                onClick={() => setIsPostAnnounceOpen(true)}
                className="btn btn-primary btn-sm text-xs cursor-pointer shadow-sm hover:shadow-md inline-flex items-center gap-1.5"
              >
                <RiAddLine size={15} />
                <span>New Announcement</span>
              </button>
            </div>
          </div>

          {/* Stats Grid (teachers/index.php lines 735-744) */}
          <div className="stats-grid">
            <div className="stat-card blue group">
              <div className="stat-icon">
                <RiBookOpenLine size={22} />
              </div>
              <div className="stat-value">4</div>
              <div className="stat-label">Active Subjects</div>
              <div className="stat-change up">
                <span>↑ +1 this term</span>
              </div>
            </div>
            <div className="stat-card green group">
              <div className="stat-icon">
                <RiGroupLine size={22} />
              </div>
              <div className="stat-value">142</div>
              <div className="stat-label">Total Students</div>
              <div className="stat-change up">
                <span>↑ across 4 sections</span>
              </div>
            </div>
            <div className="stat-card amber group">
              <div className="stat-icon">
                <RiTimeLine size={22} />
              </div>
              <div className="stat-value">{pendingSubmissions.length || 12}</div>
              <div className="stat-label">Pending Reviews</div>
              <div className="stat-change down">
                <span>↓ 3 urgent</span>
              </div>
            </div>
            <div className="stat-card purple group">
              <div className="stat-icon">
                <RiVideoChatLine size={22} />
              </div>
              <div className="stat-value">1</div>
              <div className="stat-label">Live Sessions</div>
              <div className="stat-change up">
                <span>Oral Communication</span>
              </div>
            </div>
          </div>

          {/* Announcements Card (teachers/index.php lines 746-865) */}
          <div className="card">
            <div className="card-header">
              <div className="card-title">
                <div className="p-1.5 rounded-lg bg-accent/10 text-accent">
                  <RiNotification3Line size={18} />
                </div>
                <span>Announcements</span>
              </div>
              <button
                type="button"
                onClick={() => setIsPostAnnounceOpen(true)}
                className="btn btn-primary btn-sm text-xs cursor-pointer inline-flex items-center gap-1"
              >
                <RiAddLine size={14} />
                <span>Post Notice</span>
              </button>
            </div>

            {/* Search Bar Form */}
            <form
              onSubmit={(e) => e.preventDefault()}
              className="relative flex gap-2 items-center mb-4"
            >
              <div className="relative flex-1">
                <RiSearchLine size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-text3 pointer-events-none" />
                <input
                  type="text"
                  value={teacherAnnSearch}
                  onChange={(e) => setTeacherAnnSearch(e.target.value)}
                  placeholder="Search announcements by topic, section or date..."
                  className="w-full pl-9 pr-8 py-2 rounded-xl border border-border bg-bg3 text-xs text-text placeholder:text-text3 outline-none focus:border-accent focus:ring-2 focus:ring-accent/15 transition-all"
                />
                {teacherAnnSearch && (
                  <button
                    type="button"
                    onClick={() => setTeacherAnnSearch('')}
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
                  id: 'ann-1',
                  type: 'important',
                  posterRole: 'teacher',
                  posterName: 'Ms. Juna Velarde',
                  timeAgo: 'Just now',
                  title: 'Midterm Speech Video Submission Deadline Extended',
                  message:
                    'Students who experienced technical difficulties uploading to Google Drive/ExceLearn have until tomorrow 5:00 PM.',
                  isMine: true,
                },
                {
                  id: 'ann-2',
                  type: 'update',
                  posterRole: 'admin',
                  posterName: 'ExceLearn Admin',
                  timeAgo: '2 hrs ago',
                  title: 'Gradebook Quarterly Lock Schedule Announced',
                  message:
                    'Faculty members must finalize all formative task grades and periodic test percentages by Friday 11:59 PM.',
                  isMine: false,
                },
                {
                  id: 'ann-3',
                  type: 'info',
                  posterRole: 'teacher',
                  posterName: 'Ms. Juna Velarde',
                  timeAgo: '1 day ago',
                  title: 'STEM 11-A Virtual Review Session for General Mathematics',
                  message:
                    'A specialized review room will be hosted tomorrow at 2:00 PM covering Rational Inequalities and Graphing.',
                  isMine: true,
                },
              ]
                .filter(
                  (a) =>
                    !teacherAnnSearch ||
                    a.title.toLowerCase().includes(teacherAnnSearch.toLowerCase()) ||
                    a.message.toLowerCase().includes(teacherAnnSearch.toLowerCase())
                )
                .map((ann) => (
                  <div key={ann.id} className={`announce-item ${ann.type} flex items-start justify-between gap-3`}>
                    <div className="space-y-1.5 flex-1 min-w-0">
                      <div className="announce-header flex items-center gap-2">
                        <span className={`announce-tag ${ann.type}`}>{ann.type}</span>
                        <span
                          className="inline-flex items-center gap-1 text-[11px] font-semibold px-2 py-0.5 rounded-full"
                          style={{
                            background: ann.posterRole === 'admin' ? 'rgba(239, 68, 68, 0.12)' : 'rgba(139, 92, 246, 0.12)',
                            color: ann.posterRole === 'admin' ? 'var(--red)' : 'var(--purple)',
                          }}
                        >
                          {ann.posterRole === 'admin' ? <RiShieldCheckLine size={12} /> : <RiUserStarLine size={12} />}
                          {ann.posterRole === 'admin' ? 'Admin' : 'Teacher'}: {ann.posterName}
                        </span>
                        <span className="announce-date text-[11px] text-text3 ml-auto font-medium">{ann.timeAgo}</span>
                      </div>
                      <div className="announce-title text-sm font-bold text-text">{ann.title}</div>
                      <div className="announce-msg text-xs text-text2 leading-relaxed">{ann.message}</div>
                    </div>
                    {ann.isMine && (
                      <div className="flex gap-1.5 shrink-0 ml-2">
                        <button
                          type="button"
                          className="p-1.5 rounded-lg text-xs font-medium border border-border hover:bg-bg4 text-text2 hover:text-text cursor-pointer transition-colors"
                          title="Edit"
                        >
                          <RiEditLine size={14} />
                        </button>
                        <button
                          type="button"
                          className="p-1.5 rounded-lg text-xs font-medium bg-red-500/10 text-red border border-red-500/20 hover:bg-red-500/20 cursor-pointer transition-colors"
                          title="Delete"
                        >
                          <RiDeleteBinLine size={14} />
                        </button>
                      </div>
                    )}
                  </div>
                ))}
            </div>
          </div>

          {/* Dash Grid 1: My Subjects (1.4fr) & Quick Actions (1fr) (teachers/index.php lines 866-941) */}
          <div className="dash-grid dash-grid-2-1">
            {/* My Subjects Card */}
            <div className="card">
              <div className="card-header">
                <div className="card-title">
                  <div className="p-1.5 rounded-lg bg-accent/10 text-accent">
                    <RiBookOpenLine size={18} />
                  </div>
                  <span>My Subjects</span>
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
              <div className="space-y-2.5">
                {[
                  { title: 'Oral Communication in Context', code: 'ENG', students: 38, completed: 8, lessons: 10, progress: 80, color: 'var(--accent)', iconBg: 'bg-accent/10 text-accent' },
                  { title: 'General Mathematics', code: 'MATH', students: 42, completed: 9, lessons: 10, progress: 90, color: 'var(--green)', iconBg: 'bg-green/10 text-green' },
                  { title: 'Earth and Life Science', code: 'SCI', students: 35, completed: 6, lessons: 10, progress: 60, color: 'var(--accent)', iconBg: 'bg-purple/10 text-purple' },
                  { title: 'Empowerment Technologies (ICT)', code: 'ICT', students: 27, completed: 7, lessons: 10, progress: 70, color: 'var(--accent)', iconBg: 'bg-cyan/10 text-cyan' },
                ].map((c, idx) => (
                  <div
                    key={idx}
                    onClick={() => onTabChange('courses')}
                    className="course-row group"
                  >
                    <div className={`w-9 h-9 rounded-xl flex items-center justify-center font-bold text-xs shrink-0 ${c.iconBg}`}>
                      {c.code}
                    </div>
                    <div className="course-info">
                      <div className="course-name group-hover:text-accent transition-colors">{c.title}</div>
                      <div className="course-meta">
                        <span>{c.students} learners</span>
                        <span>·</span>
                        <span>{c.completed}/{c.lessons} lessons</span>
                      </div>
                    </div>
                    <div className="course-stats">
                      <div className="course-bar">
                        <div className="progress-bar">
                          <div
                            className={`progress-fill ${c.progress >= 90 ? 'green-fill' : ''}`}
                            style={{ width: `${c.progress}%` }}
                          />
                        </div>
                      </div>
                      <div className="course-pct" style={{ color: c.color }}>
                        {c.progress}%
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </div>

            {/* Quick Actions Card (teachers/index.php lines 903-940) */}
            <div className="card">
              <div className="card-header">
                <div className="card-title">
                  <div className="p-1.5 rounded-lg bg-amber/10 text-amber">
                    <RiFileList3Line size={18} />
                  </div>
                  <span>Quick Actions</span>
                </div>
              </div>
              <div className="quick-actions">
                <div
                  className="quick-action group"
                  onClick={() => setIsCreateQuizOpen(true)}
                >
                  <span className="qa-icon group-hover:scale-110 transition-transform">
                    <RiFileList3Line size={22} />
                  </span>
                  <span className="qa-label">Create Quiz</span>
                </div>
                <div
                  className="quick-action group"
                  onClick={() => onTabChange('courses')}
                >
                  <span className="qa-icon group-hover:scale-110 transition-transform">
                    <RiBookOpenLine size={22} />
                  </span>
                  <span className="qa-label">Add Lesson</span>
                </div>
                <div
                  className="quick-action group"
                  onClick={() => onTabChange('live')}
                >
                  <span className="qa-icon group-hover:scale-110 transition-transform">
                    <RiCalendarEventLine size={22} />
                  </span>
                  <span className="qa-label">Classes</span>
                </div>
                <div
                  className="quick-action group"
                  onClick={() => setIsPostAnnounceOpen(true)}
                >
                  <span className="qa-icon group-hover:scale-110 transition-transform">
                    <RiNotification3Line size={22} />
                  </span>
                  <span className="qa-label">Post Notice</span>
                </div>
                <div
                  className="quick-action group"
                  onClick={() => onTabChange('grades')}
                >
                  <span className="qa-icon group-hover:scale-110 transition-transform">
                    <RiBarChartLine size={22} />
                  </span>
                  <span className="qa-label">Gradebook</span>
                </div>
                <div
                  className="quick-action group"
                  onClick={() => onTabChange('tasks')}
                >
                  <span className="qa-icon group-hover:scale-110 transition-transform">
                    <RiCheckboxCircleLine size={22} />
                  </span>
                  <span className="qa-label">Review Tasks</span>
                </div>
              </div>
            </div>
          </div>

          {/* Dash Grid 2: Recent Lessons & Recent Quizzes (teachers/index.php lines 943-1014) */}
          <div className="dash-grid dash-grid-1-1">
            {/* Recent Lessons */}
            <div className="card">
              <div className="card-header">
                <div className="card-title">
                  <div className="p-1.5 rounded-lg bg-green/10 text-green">
                    <RiFileTextLine size={18} />
                  </div>
                  <span>Recent Lessons</span>
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
              <div className="space-y-1">
                {[
                  { title: 'Functions and Their Graphs', subj: 'General Mathematics', status: 'published', duration: '45 min', date: 'May 14' },
                  { title: 'Principles of Effective Speech Delivery', subj: 'Oral Communication', status: 'published', duration: '60 min', date: 'May 12' },
                  { title: 'The Universe and Solar System Exploration', subj: 'Earth and Life Science', status: 'draft', duration: '35 min', date: 'May 10' },
                  { title: 'ICT and Society: The Online Collaborative Toolset', subj: 'Empowerment Tech', status: 'published', duration: '50 min', date: 'May 08' },
                  { title: 'Rational Equations and Inequalities Practice', subj: 'General Mathematics', status: 'published', duration: '40 min', date: 'May 05' },
                ].map((l, idx) => (
                  <div key={idx} className="flex items-center gap-3 p-2 rounded-xl hover:bg-bg3/60 transition-colors">
                    <div
                      className={`w-8 h-8 rounded-lg flex items-center justify-center shrink-0 ${
                        l.status === 'published' ? 'bg-green-500/10 text-green' : 'bg-amber-500/10 text-amber'
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
                    <span className={`text-[10px] font-semibold px-2 py-0.5 rounded-full ${
                      l.status === 'published'
                        ? 'bg-green-500/10 text-green border border-green-500/20'
                        : 'bg-amber-500/10 text-amber border border-amber-500/20'
                    }`}>
                      {l.status === 'published' ? 'Published' : 'Draft'}
                    </span>
                  </div>
                ))}
              </div>
            </div>

            {/* Recent Quizzes */}
            <div className="card">
              <div className="card-header">
                <div className="card-title">
                  <div className="p-1.5 rounded-lg bg-purple/10 text-purple">
                    <RiQuestionLine size={18} />
                  </div>
                  <span>Recent Quizzes</span>
                </div>
                <button
                  type="button"
                  onClick={() => onTabChange('tasks')}
                  className="btn btn-ghost btn-sm text-xs cursor-pointer inline-flex items-center gap-1"
                >
                  <span>View all</span>
                  <RiArrowRightLine size={13} />
                </button>
              </div>
              <div className="space-y-1">
                {[
                  { title: 'Module 4: Rational Functions Mastery Test', subj: 'General Mathematics', qCount: 15, status: 'published', due: 'May 20' },
                  { title: 'Speech Delivery Assessment & Self-Reflection', subj: 'Oral Communication', qCount: 10, status: 'published', due: 'May 18' },
                  { title: 'Biogeochemical Cycles Diagnostic Quiz', subj: 'Earth & Life Science', qCount: 20, status: 'draft', due: 'May 25' },
                  { title: 'Web Design Principles & UI Patterns Quiz', subj: 'Empowerment Tech', qCount: 15, status: 'published', due: 'May 22' },
                  { title: 'Barayti at Rehistro ng Wika Pagsusulit', subj: 'Komunikasyon', qCount: 12, status: 'published', due: 'May 16' },
                ].map((q, idx) => (
                  <div key={idx} className="flex items-center gap-3 p-2 rounded-xl hover:bg-bg3/60 transition-colors">
                    <div
                      className={`w-8 h-8 rounded-lg flex items-center justify-center shrink-0 ${
                        q.status === 'published' ? 'bg-purple-500/10 text-purple' : 'bg-amber-500/10 text-amber'
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
                    <span className={`text-[10px] font-semibold px-2 py-0.5 rounded-full ${
                      q.status === 'published'
                        ? 'bg-purple-500/10 text-purple border border-purple-500/20'
                        : 'bg-amber-500/10 text-amber border border-amber-500/20'
                    }`}>
                      {q.status === 'published' ? 'Published' : 'Draft'}
                    </span>
                  </div>
                ))}
              </div>
            </div>
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
