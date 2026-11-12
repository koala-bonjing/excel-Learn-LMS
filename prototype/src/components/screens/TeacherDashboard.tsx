import { useState } from 'react';
import type { StudentSubmission } from '@/types';
import { INITIAL_SUBMISSIONS, STUDENT_DIRECTORY } from '@/data/mockData';
import {
  RiBookOpenLine,
  RiFileList3Line,
  RiVideoChatLine,
  RiGroupLine,
  RiCheckLine,
  RiAddLine,
  RiFilePdfLine,
  RiNotification3Line,
  RiUpload2Line,
  RiCloseLine,
  RiTimeLine,
  RiFileExcel2Line,
  RiMicLine,
  RiMicOffLine,
  RiCameraLine,
  RiTvLine,
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
  const [isEducationModalOpen, setIsEducationModalOpen] = useState(false);

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
  const [newTitle, setNewTitle] = useState('');
  const [newBody, setNewBody] = useState('');
  const [newCategory, setNewCategory] = useState('Rubric Update');
  const [newSection, setNewSection] = useState('STEM 11-A');

  // Education state (update_education.php)
  const [educationRecords, setEducationRecords] = useState([
    { degree: 'Master of Arts in Language Education', school: 'Bicol University', year: '2023' },
    { degree: 'Bachelor of Secondary Education (Major in English)', school: 'Masbate Colleges', year: '2019' },
  ]);
  const [newDegree, setNewDegree] = useState('');
  const [newSchool, setNewSchool] = useState('');
  const [newYear, setNewYear] = useState('');

  // Studio state
  const [isMuted, setIsMuted] = useState(false);
  const [isCameraOn, setIsCameraOn] = useState(true);
  const [isScreenSharing, setIsScreenSharing] = useState(false);

  // Settings state
  const [currentPass, setCurrentPass] = useState('');
  const [newPass, setNewPass] = useState('');
  const [confirmPass, setConfirmPass] = useState('');
  const [passUpdated, setPassUpdated] = useState(false);

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

  const handleAddEducation = (e: React.FormEvent) => {
    e.preventDefault();
    if (!newDegree || !newSchool) return;
    setEducationRecords([...educationRecords, { degree: newDegree, school: newSchool, year: newYear }]);
    setNewDegree('');
    setNewSchool('');
    setNewYear('');
    setIsEducationModalOpen(false);
  };

  return (
    <div className="space-y-6">
      {/* ============================================================ */}
      {/* 1. TEACHER DASHBOARD (teachers/index.php)                    */}
      {/* ============================================================ */}
      {activeTab === 'dashboard' && (
        <>
          {/* 4 KPI Stat Cards */}
          <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <div className="p-4 sm:p-5 rounded-xl bg-bg2 border border-border shadow-xs">
              <div className="flex items-center justify-between text-text3 mb-2">
                <span className="text-xs font-semibold uppercase tracking-wider">Active Subjects</span>
                <div className="p-2 rounded-lg bg-accent/10 text-accent">
                  <RiBookOpenLine className="size-4" aria-hidden="true" />
                </div>
              </div>
              <div className="text-2xl sm:text-3xl font-bold text-text tabular-nums">4</div>
              <span className="text-xs text-accent mt-1 inline-block font-medium">STEM & HUMSS Tracks</span>
            </div>

            <div className="p-4 sm:p-5 rounded-xl bg-bg2 border border-border shadow-xs">
              <div className="flex items-center justify-between text-text3 mb-2">
                <span className="text-xs font-semibold uppercase tracking-wider">Pending Reviews</span>
                <div className="p-2 rounded-lg bg-amber/10 text-amber">
                  <RiFileList3Line className="size-4" aria-hidden="true" />
                </div>
              </div>
              <div className="text-2xl sm:text-3xl font-bold text-text tabular-nums">
                {pendingSubmissions.length}
              </div>
              <span className="text-xs text-amber mt-1 inline-block font-medium">Awaiting Rubric Scoring</span>
            </div>

            <div className="p-4 sm:p-5 rounded-xl bg-bg2 border border-border shadow-xs">
              <div className="flex items-center justify-between text-text3 mb-2">
                <span className="text-xs font-semibold uppercase tracking-wider">Live Classes</span>
                <div className="p-2 rounded-lg bg-cyan-bg text-cyan">
                  <RiVideoChatLine className="size-4" aria-hidden="true" />
                </div>
              </div>
              <div className="text-2xl sm:text-3xl font-bold text-text tabular-nums">1 Active</div>
              <span className="text-xs text-cyan mt-1 inline-block font-medium">STEM 11-A In Session</span>
            </div>

            <div className="p-4 sm:p-5 rounded-xl bg-bg2 border border-border shadow-xs">
              <div className="flex items-center justify-between text-text3 mb-2">
                <span className="text-xs font-semibold uppercase tracking-wider">Enrolled Students</span>
                <div className="p-2 rounded-lg bg-purple/10 text-purple">
                  <RiGroupLine className="size-4" aria-hidden="true" />
                </div>
              </div>
              <div className="text-2xl sm:text-3xl font-bold text-text tabular-nums">142</div>
              <span className="text-xs text-purple mt-1 inline-block font-medium">Across 4 Sections</span>
            </div>
          </div>

          {/* Quick Shortcuts Bar */}
          <div className="p-4 rounded-xl bg-bg2 border border-border flex flex-wrap items-center justify-between gap-3">
            <span className="text-xs font-bold text-text3 uppercase tracking-wider">Faculty Actions:</span>
            <div className="flex flex-wrap items-center gap-2">
              <button
                type="button"
                onClick={() => setIsCreateQuizOpen(true)}
                className="px-3 py-1.5 rounded-lg bg-bg3 hover:bg-bg4 text-text text-xs font-semibold border border-border flex items-center gap-1.5 cursor-pointer"
              >
                <RiAddLine className="size-3.5 text-accent" />
                <span>+ Create Quiz</span>
              </button>
              <button
                type="button"
                onClick={() => setIsCreateTaskOpen(true)}
                className="px-3 py-1.5 rounded-lg bg-bg3 hover:bg-bg4 text-text text-xs font-semibold border border-border flex items-center gap-1.5 cursor-pointer"
              >
                <RiAddLine className="size-3.5 text-school-orange" />
                <span>+ New Task</span>
              </button>
              <button
                type="button"
                onClick={() => onTabChange('live')}
                className="px-3 py-1.5 rounded-lg bg-cyan text-[#0e0f14] text-xs font-bold flex items-center gap-1.5 cursor-pointer hover:brightness-105 shadow-xs"
              >
                <RiVideoChatLine className="size-3.5" />
                <span>Launch Live Room</span>
              </button>
            </div>
          </div>

          {/* Pending Submissions Quick Feed */}
          <div className="p-5 rounded-xl bg-bg2 border border-border space-y-3">
            <div className="flex items-center justify-between">
              <div>
                <h4 className="text-base font-bold text-text font-serif">Recent Performance Task Submissions</h4>
                <p className="text-xs text-text2">Speech performance recordings awaiting grading.</p>
              </div>
              <button
                type="button"
                onClick={() => onTabChange('tasks')}
                className="text-xs text-accent hover:underline font-semibold"
              >
                View All Deliverables &rarr;
              </button>
            </div>

            <div className="divide-y divide-border">
              {pendingSubmissions.map((subm) => (
                <div key={subm.id} className="py-3 flex items-center justify-between">
                  <div>
                    <span className="text-xs font-bold text-text">{subm.studentName}</span>
                    <span className="text-[11px] text-text3 ml-2 font-mono">{subm.studentId}</span>
                    <div className="text-[11px] text-text2 mt-0.5">{subm.taskTitle}</div>
                  </div>
                  <button
                    type="button"
                    onClick={() => setGradingModalSubmission(subm)}
                    className="px-3 py-1 rounded-lg bg-green text-[#0e0f14] text-xs font-semibold hover:brightness-105 cursor-pointer shadow-xs"
                  >
                    Grade Rubric
                  </button>
                </div>
              ))}
            </div>
          </div>
        </>
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
      {/* 7. VIRTUAL CLASSROOM BROADCASTER (teacher_virtual_class.php) */}
      {/* ============================================================ */}
      {activeTab === 'live' && (
        <div className="space-y-4">
          <div className="flex items-center justify-between">
            <div>
              <h3 className="text-xl font-bold font-serif text-text">WebRTC Live Broadcast Studio</h3>
              <p className="text-xs text-text2">Broadcasting to STEM 11-A · Oral Communication in Context</p>
            </div>
            <span className="px-3 py-1 rounded-full bg-cyan-bg text-cyan text-xs font-semibold flex items-center gap-1.5">
              <span className="size-2 rounded-full bg-cyan animate-pulse" />
              Broadcasting Live · 28 Students Tuned In
            </span>
          </div>

          <div className="rounded-2xl bg-[#090a0f] border border-border overflow-hidden shadow-2xl">
            <div className="relative aspect-video flex items-center justify-center p-6 text-white">
              <div className="text-center space-y-3">
                <div className="size-24 mx-auto rounded-full bg-cyan/20 border-2 border-cyan flex items-center justify-center text-cyan">
                  <RiVideoChatLine className="size-12" aria-hidden="true" />
                </div>
                <div>
                  <h4 className="text-xl font-bold">Studio Feed Active (Ms. Juna Velarde)</h4>
                  <p className="text-xs text-white/70">Audio, Video & Screen Signaling Negotiated via WebRTC</p>
                </div>
              </div>
            </div>

            <div className="p-4 bg-bg2 border-t border-border flex items-center justify-between">
              <div className="flex items-center gap-2">
                <button
                  type="button"
                  onClick={() => setIsMuted(!isMuted)}
                  className={`p-2.5 rounded-xl cursor-pointer ${
                    isMuted ? 'bg-red text-white' : 'bg-bg3 text-text border border-border'
                  }`}
                  aria-label={isMuted ? 'Unmute microphone' : 'Mute microphone'}
                >
                  {isMuted ? <RiMicOffLine className="size-4" /> : <RiMicLine className="size-4" />}
                </button>
                <button
                  type="button"
                  onClick={() => setIsCameraOn(!isCameraOn)}
                  className={`p-2.5 rounded-xl cursor-pointer ${
                    !isCameraOn ? 'bg-red text-white' : 'bg-bg3 text-text border border-border'
                  }`}
                  aria-label="Toggle camera"
                >
                  <RiCameraLine className="size-4" />
                </button>
                <button
                  type="button"
                  onClick={() => setIsScreenSharing(!isScreenSharing)}
                  className={`p-2.5 rounded-xl cursor-pointer ${
                    isScreenSharing ? 'bg-accent text-white' : 'bg-bg3 text-text border border-border'
                  }`}
                  aria-label="Toggle screen share"
                >
                  <RiTvLine className="size-4" />
                </button>
              </div>

              <button
                type="button"
                onClick={() => onTabChange('dashboard')}
                className="px-5 py-2 rounded-xl bg-red text-white text-xs font-semibold hover:bg-red/90 cursor-pointer"
              >
                End Live Stream
              </button>
            </div>
          </div>
        </div>
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
                  <option value="Virtual Class Schedule">Virtual Class Schedule</option>
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
        <div className="p-6 rounded-xl bg-bg2 border border-border shadow-xs space-y-4">
          <div className="flex items-center justify-between border-b border-border pb-3">
            <div>
              <h3 className="text-xl font-bold font-serif text-text">Faculty Professional Credentials</h3>
              <p className="text-xs text-text2">PRC Professional Teacher license & educational attainment records</p>
            </div>
            <span className="px-2.5 py-1 text-xs font-bold rounded-lg bg-green-bg text-green">
              Verified Faculty
            </span>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
            <div className="p-3 rounded-lg bg-bg3 border border-border space-y-1">
              <span className="text-text3 text-[10px] uppercase font-bold">Faculty Name</span>
              <div className="font-bold text-text text-sm">Ms. Juna C. Velarde</div>
              <div className="text-text2">Senior High School Languages Department</div>
            </div>
            <div className="p-3 rounded-lg bg-bg3 border border-border space-y-1">
              <span className="text-text3 text-[10px] uppercase font-bold">PRC Teacher License</span>
              <div className="font-mono font-bold text-accent text-sm">PRC-0948210 (Active)</div>
              <div className="text-text2">DepEd Verified Educator</div>
            </div>
          </div>

          <div className="pt-2">
            <div className="flex items-center justify-between mb-2">
              <h5 className="text-sm font-bold text-text">Educational Background (update_education.php)</h5>
              <button
                type="button"
                onClick={() => setIsEducationModalOpen(true)}
                className="px-2.5 py-1 rounded-lg bg-bg3 hover:bg-bg4 text-text text-xs font-semibold border border-border flex items-center gap-1 cursor-pointer"
              >
                <RiAddLine className="size-3.5" />
                <span>+ Add Degree</span>
              </button>
            </div>

            <div className="space-y-2">
              {educationRecords.map((ed, idx) => (
                <div key={idx} className="p-3 rounded-lg bg-bg3 border border-border flex items-center justify-between text-xs">
                  <div>
                    <div className="font-semibold text-text">{ed.degree}</div>
                    <div className="text-text3">{ed.school} · Class of {ed.year}</div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      )}

      {/* ============================================================ */}
      {/* 10. SETTINGS (teachers/settings.php)                          */}
      {/* ============================================================ */}
      {activeTab === 'settings' && (
        <div className="max-w-2xl p-6 rounded-xl bg-bg2 border border-border shadow-xs space-y-4">
          <h3 className="text-xl font-bold font-serif text-text">Faculty Account Settings</h3>
          <p className="text-xs text-text2">Update account password and security credentials.</p>

          {passUpdated && (
            <div className="p-3 rounded-lg bg-green-bg border border-green/30 text-xs text-green flex items-center gap-2">
              <RiCheckLine className="size-4" aria-hidden="true" />
              <span>Password successfully updated!</span>
            </div>
          )}

          <form
            onSubmit={(e) => {
              e.preventDefault();
              if (newPass !== confirmPass) return;
              setPassUpdated(true);
              setCurrentPass('');
              setNewPass('');
              setConfirmPass('');
              setTimeout(() => setPassUpdated(false), 2500);
            }}
            className="space-y-3"
          >
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
                  placeholder="Confirm new password"
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
      {/* MODAL 4: EDUCATIONAL ATTAINMENT (update_education.php)        */}
      {/* ============================================================ */}
      {isEducationModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md">
          <div className="w-full max-w-md bg-bg2 rounded-2xl border border-border shadow-2xl p-6 space-y-4 text-xs">
            <div className="flex items-center justify-between border-b border-border pb-3">
              <h4 className="text-base font-bold text-text">Add Educational Background</h4>
              <button
                type="button"
                onClick={() => setIsEducationModalOpen(false)}
                className="p-1 rounded-lg text-text3 hover:text-text cursor-pointer"
              >
                <RiCloseLine className="size-5" />
              </button>
            </div>

            <form onSubmit={handleAddEducation} className="space-y-3">
              <div>
                <label className="block font-semibold text-text2 mb-1">Degree / Qualification *</label>
                <input
                  type="text"
                  placeholder="e.g. Ph.D. in Educational Management"
                  value={newDegree}
                  onChange={(e) => setNewDegree(e.target.value)}
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-text"
                  required
                />
              </div>
              <div>
                <label className="block font-semibold text-text2 mb-1">College / University *</label>
                <input
                  type="text"
                  placeholder="e.g. University of the Philippines"
                  value={newSchool}
                  onChange={(e) => setNewSchool(e.target.value)}
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-text"
                  required
                />
              </div>
              <div>
                <label className="block font-semibold text-text2 mb-1">Year Graduated</label>
                <input
                  type="text"
                  placeholder="e.g. 2025"
                  value={newYear}
                  onChange={(e) => setNewYear(e.target.value)}
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-text"
                />
              </div>
              <div className="flex justify-end gap-2 pt-2">
                <button
                  type="button"
                  onClick={() => setIsEducationModalOpen(false)}
                  className="px-4 py-2 rounded-lg bg-bg3 text-text cursor-pointer"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-4 py-2 rounded-xl bg-accent text-white font-semibold cursor-pointer shadow-xs"
                >
                  Save Degree
                </button>
              </div>
            </form>
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
