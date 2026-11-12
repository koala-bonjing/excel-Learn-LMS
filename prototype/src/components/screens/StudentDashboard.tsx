import { useState, useEffect } from 'react';
import type { StudentTask } from '@/types';
import { INITIAL_STUDENT_TASKS, SUBJECTS_LIST } from '@/data/mockData';
import {
  RiBookOpenLine,
  RiTimeLine,
  RiCheckboxCircleLine,
  RiAwardLine,
  RiVideoChatLine,
  RiArrowRightLine,
  RiFilePdfLine,
  RiCheckLine,
  RiMicLine,
  RiMicOffLine,
  RiCameraLine,
  RiCloseLine,
  RiDownload2Line,
  RiUpload2Line,
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
      {/* 1. DASHBOARD PAGE (students/index.php)                        */}
      {/* ============================================================ */}
      {activeTab === 'dashboard' && (
        <>
          {/* Hero "Continue Learning" Card */}
          <div className="relative rounded-2xl excelearn-living-grad p-6 sm:p-8 text-white shadow-lg overflow-hidden flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div className="relative z-10 max-w-xl">
              <div className="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-white/15 backdrop-blur-md border border-white/20 text-xs font-semibold text-amber-200 mb-3">
                <span className="size-2 rounded-full bg-green animate-ping" />
                Current Active Lesson · STEM 11
              </div>
              <h3 className="text-2xl sm:text-3xl font-serif font-bold text-white tracking-tight">
                General Mathematics
              </h3>
              <p className="text-sm text-white/85 mt-1 font-sans">
                Module 4: Rational Functions, Vertical & Horizontal Asymptotes, and Inverses
              </p>

              <div className="mt-4 flex items-center gap-4">
                <div className="flex-1 max-w-xs">
                  <div className="flex items-center justify-between text-xs text-white/90 mb-1">
                    <span>Module Progress</span>
                    <span className="font-bold tabular-nums">75% Complete</span>
                  </div>
                  <div className="h-2 w-full bg-white/20 rounded-full overflow-hidden">
                    <div className="h-full bg-school-orange rounded-full transition-all duration-500" style={{ width: '75%' }} />
                  </div>
                </div>
              </div>
            </div>

            <div className="relative z-10 shrink-0">
              <button
                type="button"
                onClick={() => onTabChange('courses')}
                className="flex items-center gap-2 px-5 py-3 rounded-xl font-semibold text-[#0e0f14] shadow-md transition-all duration-150 cursor-pointer active:scale-[0.96] hover:brightness-105"
                style={{ background: 'linear-gradient(135deg, #f7971e 0%, #fbb03b 100%)' }}
              >
                <span>Resume Lesson</span>
                <RiArrowRightLine className="size-4" aria-hidden="true" />
              </button>
            </div>
          </div>

          {/* 4 KPI Stat Cards */}
          <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <div className="p-4 sm:p-5 rounded-xl bg-bg2 border border-border shadow-xs hover:border-accent/40 transition-colors">
              <div className="flex items-center justify-between text-text3 mb-2">
                <span className="text-xs font-semibold uppercase tracking-wider">Enrolled Subjects</span>
                <div className="p-2 rounded-lg bg-accent/10 text-accent">
                  <RiBookOpenLine className="size-4" aria-hidden="true" />
                </div>
              </div>
              <div className="text-2xl sm:text-3xl font-bold text-text tabular-nums">6</div>
              <span className="text-xs text-accent mt-1 inline-block font-medium">All STEM Core & Applied</span>
            </div>

            <div className="p-4 sm:p-5 rounded-xl bg-bg2 border border-border shadow-xs hover:border-amber/40 transition-colors">
              <div className="flex items-center justify-between text-text3 mb-2">
                <span className="text-xs font-semibold uppercase tracking-wider">Due This Week</span>
                <div className="p-2 rounded-lg bg-amber/10 text-amber">
                  <RiTimeLine className="size-4" aria-hidden="true" />
                </div>
              </div>
              <div className="text-2xl sm:text-3xl font-bold text-text tabular-nums">3</div>
              <span className="text-xs text-amber mt-1 inline-block font-medium">1 Quiz · 2 Tasks</span>
            </div>

            <div className="p-4 sm:p-5 rounded-xl bg-bg2 border border-border shadow-xs hover:border-green/40 transition-colors">
              <div className="flex items-center justify-between text-text3 mb-2">
                <span className="text-xs font-semibold uppercase tracking-wider">Quiz Average</span>
                <div className="p-2 rounded-lg bg-green/10 text-green">
                  <RiCheckboxCircleLine className="size-4" aria-hidden="true" />
                </div>
              </div>
              <div className="text-2xl sm:text-3xl font-bold text-text tabular-nums">91.4%</div>
              <span className="text-xs text-green mt-1 inline-block font-medium">+2.8% from Prelims</span>
            </div>

            <div className="p-4 sm:p-5 rounded-xl bg-bg2 border border-border shadow-xs hover:border-purple/40 transition-colors">
              <div className="flex items-center justify-between text-text3 mb-2">
                <span className="text-xs font-semibold uppercase tracking-wider">Certificates</span>
                <div className="p-2 rounded-lg bg-purple/10 text-purple">
                  <RiAwardLine className="size-4" aria-hidden="true" />
                </div>
              </div>
              <div className="text-2xl sm:text-3xl font-bold text-text tabular-nums">4</div>
              <span className="text-xs text-purple mt-1 inline-block font-medium">STEM Honors Standing</span>
            </div>
          </div>

          {/* Live Virtual Class Banner */}
          <div className="flex items-center justify-between p-4 rounded-xl bg-cyan-bg border border-cyan/30 text-text">
            <div className="flex items-center gap-3">
              <div className="relative size-3 flex items-center justify-center shrink-0">
                <span className="absolute size-3 rounded-full bg-cyan animate-ping opacity-75" />
                <span className="relative size-2 rounded-full bg-cyan" />
              </div>
              <div>
                <div className="text-xs font-semibold text-cyan uppercase tracking-wider">
                  Live Virtual Classroom Now Active
                </div>
                <div className="text-sm font-semibold text-text">
                  Oral Communication in Context — Ms. Juna Velarde is broadcasting live
                </div>
              </div>
            </div>

            <button
              type="button"
              onClick={() => onTabChange('live')}
              className="flex items-center gap-1.5 px-4 py-2 rounded-lg text-xs font-semibold bg-cyan text-[#0e0f14] hover:brightness-105 active:scale-95 transition-all cursor-pointer shadow-xs"
            >
              <RiVideoChatLine className="size-4" aria-hidden="true" />
              <span>Join Room</span>
            </button>
          </div>

          {/* DepEd Grade Breakdown Card (25% - 50% - 25%) */}
          <div className="p-5 rounded-xl bg-bg2 border border-border space-y-4">
            <div className="flex items-center justify-between">
              <div>
                <h4 className="text-base font-bold text-text font-serif">
                  DepEd Order No. 8, s. 2015 Grading Standard
                </h4>
                <p className="text-xs text-text2">
                  Senior High School STEM Track Component Weights (Written Work 25%, Performance Tasks 50%, Quarterly Exam 25%)
                </p>
              </div>
              <span className="px-2.5 py-1 text-xs font-bold rounded-lg bg-green-bg text-green tabular-nums">
                Projected Q1 Grade: 92 (Very Satisfactory)
              </span>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div className="p-3.5 rounded-lg bg-bg3 border border-border space-y-2">
                <div className="flex items-center justify-between text-xs font-semibold text-text">
                  <span>Written Work (25%)</span>
                  <span className="tabular-nums text-accent">89.2%</span>
                </div>
                <div className="h-2 w-full bg-bg4 rounded-full overflow-hidden">
                  <div className="h-full bg-accent rounded-full" style={{ width: '89.2%' }} />
                </div>
                <p className="text-[11px] text-text3">Quizzes, concept papers, module activity sheets</p>
              </div>

              <div className="p-3.5 rounded-lg bg-bg3 border border-border space-y-2">
                <div className="flex items-center justify-between text-xs font-semibold text-text">
                  <span>Performance Tasks (50%)</span>
                  <span className="tabular-nums text-green">93.5%</span>
                </div>
                <div className="h-2 w-full bg-bg4 rounded-full overflow-hidden">
                  <div className="h-full bg-green rounded-full" style={{ width: '93.5%' }} />
                </div>
                <p className="text-[11px] text-text3">Speech performances, lab projects, research posters</p>
              </div>

              <div className="p-3.5 rounded-lg bg-bg3 border border-border space-y-2">
                <div className="flex items-center justify-between text-xs font-semibold text-text">
                  <span>Quarterly Assessment (25%)</span>
                  <span className="tabular-nums text-school-orange">94.0%</span>
                </div>
                <div className="h-2 w-full bg-bg4 rounded-full overflow-hidden">
                  <div className="h-full bg-school-orange rounded-full" style={{ width: '94%' }} />
                </div>
                <p className="text-[11px] text-text3">Periodic quarterly examination test score</p>
              </div>
            </div>
          </div>
        </>
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
