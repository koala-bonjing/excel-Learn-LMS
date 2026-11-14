import { useState, useEffect } from 'react';
import type { StudentTask } from '@/types';
import { INITIAL_STUDENT_TASKS } from '@/data/mockData';
import { AboutView } from '@/components/views/AboutView';
import { StudentProfileView } from '@/components/views/StudentProfileView';
import { StudentSettingsView } from '@/components/views/StudentSettingsView';
import { StudentActivityHeatmap } from '@/components/screens/StudentActivityHeatmap';
import { CourseSectionView } from '@/components/views/CourseSectionView';
import { StudentPerformanceTasksView } from '@/components/views/StudentPerformanceTasksView';
import { StudentGradesView } from '@/components/views/StudentGradesView';
import { StudentClassesView } from '@/components/views/StudentClassesView';
import { getCourseData } from '@/data/courseOutlineData';
import {
  RiFilePdfLine,
  RiCheckLine,
  RiFireFill,
  RiCloseLine,
  RiDownload2Line,
  RiUpload2Line,
  RiBookOpenLine,
  RiCheckboxCircleLine,
  RiAwardLine,
  RiFileList3Line,
  RiNotification3Line,
  RiNotification3Fill,
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
  RiArrowLeftSLine,
  RiArrowRightSLine,
  RiArrowDownSLine,
  RiMoreFill,
  RiMessage3Line,
  RiLayoutGridLine,
  RiListUnordered,
  RiFilter3Line,
  RiArrowUpDownLine,
  RiStarLine,
  RiStarFill,
  RiEyeOffLine,
  RiStackLine,
  RiGraduationCapFill,
  RiSendPlaneFill,
  RiCalendarLine,
  RiBookmarkLine,
  RiBookmarkFill,
  RiCheckDoubleLine,
  RiMegaphoneLine,
} from '@remixicon/react';

export interface AnnouncementItem {
  id: number;
  type: 'important' | 'info' | 'update';
  category: 'Academic' | 'Classes' | 'Virtual Classes' | 'Workshops' | 'Institutional';
  posterRole: 'admin' | 'teacher';
  posterName: string;
  timeLabel: string;
  date: string;
  title: string;
  message: string;
  tag: string;
  isRead?: boolean;
  bookmarked?: boolean;
}

const INITIAL_ANNOUNCEMENTS: AnnouncementItem[] = [
  {
    id: 1,
    type: 'important',
    category: 'Academic',
    posterRole: 'admin',
    posterName: 'ExceLearn Admin',
    timeLabel: '2 hrs ago',
    date: 'Oct 20, 2026',
    title: 'Academic Year 2026–2027 Midterm Examination Schedule',
    message:
      'Senior High School midterm examinations for Grade 11 and Grade 12 will commence on October 20–24, 2026. Please ensure all performance tasks are submitted beforehand to receive examination clearance.',
    tag: 'Grade 11 & 12 · DepEd Midterms',
    isRead: false,
    bookmarked: false,
  },
  {
    id: 2,
    type: 'info',
    category: 'Classes',
    posterRole: 'teacher',
    posterName: 'Ms. Juna Velarde',
    timeLabel: '1 day ago',
    date: 'Oct 19, 2026',
    title: 'Class Attendance & DepEd SF2 Submission Guidelines',
    message:
      'All learners are required to log in 5 minutes before scheduled classes (both WebRTC virtual and physical). Session recordings and lecture slides will be automatically published under the Classes and Subject Modules tabs.',
    tag: 'Languages & Oral Comm Lead',
    isRead: false,
    bookmarked: true,
  },
  {
    id: 3,
    type: 'update',
    category: 'Workshops',
    posterRole: 'teacher',
    posterName: 'Ms. Juna Velarde',
    timeLabel: '3 days ago',
    date: 'Oct 17, 2026',
    title: 'Science & Math Blended Workshop at Cabitan Campus',
    message:
      'Hands-on laboratory sessions for STEM learners will take place this Friday at the Cabitan Campus Science Lab. Bring your physical lab notebooks, calculators, and safety goggles.',
    tag: 'Cabitan Campus STEM Lab',
    isRead: false,
    bookmarked: false,
  },
  {
    id: 4,
    type: 'info',
    category: 'Academic',
    posterRole: 'teacher',
    posterName: 'Mr. Roderick Tan',
    timeLabel: '4 days ago',
    date: 'Oct 16, 2026',
    title: 'Division Mathematics Olympiad Qualifying Round',
    message:
      'Registrations for the SHS Math Olympiad are now open. Interested STEM learners should submit their intent forms by Wednesday. Remedial and training sessions start next week.',
    tag: 'STEM Mathematics Department',
    isRead: true,
    bookmarked: false,
  },
  {
    id: 5,
    type: 'important',
    category: 'Institutional',
    posterRole: 'admin',
    posterName: 'DepEd Registrar',
    timeLabel: '5 days ago',
    date: 'Oct 15, 2026',
    title: 'Submission of LRN & Form 137 Permanent Records',
    message:
      'All newly transferred Grade 11 students must submit their certified true copy of Form 137 / SF10 to the registrar office before the end of this month for DepEd LIS validation.',
    tag: 'Office of the Registrar',
    isRead: true,
    bookmarked: false,
  },
];

const ANNOUNCEMENT_CATEGORIES = [
  { id: 'All', label: 'All Categories', dotColor: 'bg-text3' },
  { id: 'Important', label: 'Important Notices', dotColor: 'bg-red' },
  { id: 'Academic', label: 'Academic', dotColor: 'bg-accent' },
  { id: 'Classes', label: 'Classes', dotColor: 'bg-green' },
  { id: 'Workshops', label: 'Workshops', dotColor: 'bg-purple' },
  { id: 'Institutional', label: 'Institutional', dotColor: 'bg-amber' },
] as const;

type AnnouncementCategoryFilter = (typeof ANNOUNCEMENT_CATEGORIES)[number]['id'];

interface EnrolledCourse {
  id: string;
  code: string;
  title: string;
  fullTitle: string;
  semester: string;
  teacher: string;
  progressPercent: number;
  lastAccessed: string;
  lastAccessedTime: number;
  artVariant: 'squares' | 'pink-waves' | 'branded-blue' | 'emerald-arcs' | 'purple-mesh' | 'amber-waves';
}

const ENROLLED_COURSES: EnrolledCourse[] = [
  {
    id: 'c-social-issues',
    code: 'SIPP-301',
    title: 'Social Issues and Professional Practice',
    fullTitle: 'Social Issues and Professional Practice (AY 2024-25 BSCS 3)',
    semester: 'AY 2024 - 2025 1st Sem',
    teacher: 'Prof. Dennis Morales, MSCS',
    progressPercent: 25,
    lastAccessed: 'Just now',
    lastAccessedTime: 7,
    artVariant: 'branded-blue',
  },
  {
    id: 'c-gen-math',
    code: 'GEN-MATH-11',
    title: 'General Mathematics',
    fullTitle: 'General Mathematics (AY 2025-26 STEM 11-A)',
    semester: 'AY 2025 - 2026 2nd Sem',
    teacher: 'Mr. Roderick Tan',
    progressPercent: 100,
    lastAccessed: '10 mins ago',
    lastAccessedTime: 6,
    artVariant: 'squares',
  },
  {
    id: 'c-oral-comm',
    code: 'ORAL-COMM-11',
    title: 'Oral Communication in Context',
    fullTitle: 'Oral Communication in Context (AY 2025-26 STEM 11-A)',
    semester: 'AY 2025 - 2026 2nd Sem',
    teacher: 'Ms. Juna Velarde',
    progressPercent: 100,
    lastAccessed: '2 hours ago',
    lastAccessedTime: 5,
    artVariant: 'pink-waves',
  },
  {
    id: 'c-earth-sci',
    code: 'EARTH-SCI-11',
    title: 'Earth and Life Science',
    fullTitle: 'Earth and Life Science (AY 2025-26 STEM 11-A)',
    semester: 'AY 2025 - 2026 2nd Sem',
    teacher: 'Dr. Corazon Bautista',
    progressPercent: 0,
    lastAccessed: 'Yesterday',
    lastAccessedTime: 4,
    artVariant: 'branded-blue',
  },
  {
    id: 'c-kom-fil',
    code: 'KOM-FIL-11',
    title: 'Komunikasyon at Pananaliksik',
    fullTitle: 'Komunikasyon at Pananaliksik sa Wika at Kulturang Pilipino (AY 2025-26 STEM 11-A)',
    semester: 'AY 2025 - 2026 2nd Sem',
    teacher: 'Gng. Remedios Alcantara',
    progressPercent: 9,
    lastAccessed: '3 days ago',
    lastAccessedTime: 3,
    artVariant: 'emerald-arcs',
  },
  {
    id: 'c-gen-bio',
    code: 'BIO-11',
    title: 'General Biology 1',
    fullTitle: 'General Biology 1 (AY 2025-26 STEM 11-A)',
    semester: 'AY 2025 - 2026 2nd Sem',
    teacher: 'Dr. Maria Hernandez',
    progressPercent: 75,
    lastAccessed: '4 days ago',
    lastAccessedTime: 2,
    artVariant: 'purple-mesh',
  },
  {
    id: 'c-emp-tech',
    code: 'EMP-TECH-11',
    title: 'Empowerment Technologies',
    fullTitle: 'Empowerment Technologies: ICT for STEM (AY 2025-26 STEM 11-A)',
    semester: 'AY 2025 - 2026 2nd Sem',
    teacher: 'Engr. Paulo Santos',
    progressPercent: 60,
    lastAccessed: '5 days ago',
    lastAccessedTime: 1,
    artVariant: 'amber-waves',
  },
];

function CourseCoverArt({
  variant,
  title,
  id,
}: {
  variant: 'squares' | 'pink-waves' | 'branded-blue' | 'emerald-arcs' | 'purple-mesh' | 'amber-waves';
  title: string;
  id?: string;
}) {
  const gradSuffix = id || variant;

  switch (variant) {
    case 'squares':
      return (
        <div className="w-full h-full relative overflow-hidden bg-[#eef2f6] dark:bg-[#151d2a] select-none">
          <svg className="w-full h-full object-cover" viewBox="0 0 600 200" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
            <rect width="600" height="200" fill="#f1f5f9" className="dark:fill-[#151d2a]" />
            <g opacity="0.85">
              {/* Left nested square frame */}
              <rect x="25" y="25" width="130" height="130" fill="none" stroke="#cbd5e1" strokeWidth="16" className="dark:stroke-[#2a364f]" />
              <rect x="52" y="52" width="76" height="76" fill="none" stroke="#cbd5e1" strokeWidth="10" className="dark:stroke-[#2a364f]" />
              {/* Top-center nested square frame */}
              <rect x="215" y="-25" width="140" height="140" fill="none" stroke="#cbd5e1" strokeWidth="16" className="dark:stroke-[#2a364f]" />
              <rect x="245" y="5" width="80" height="80" fill="none" stroke="#cbd5e1" strokeWidth="10" className="dark:stroke-[#2a364f]" />
              {/* Bottom-center nested square frame */}
              <rect x="225" y="115" width="130" height="130" fill="none" stroke="#cbd5e1" strokeWidth="16" className="dark:stroke-[#2a364f]" />
              <rect x="252" y="142" width="76" height="76" fill="none" stroke="#cbd5e1" strokeWidth="10" className="dark:stroke-[#2a364f]" />
              {/* Right nested square frame */}
              <rect x="415" y="25" width="135" height="135" fill="none" stroke="#cbd5e1" strokeWidth="16" className="dark:stroke-[#2a364f]" />
              <rect x="442" y="52" width="81" height="81" fill="none" stroke="#cbd5e1" strokeWidth="10" className="dark:stroke-[#2a364f]" />
              {/* Cut-off edges */}
              <rect x="-45" y="65" width="110" height="110" fill="none" stroke="#cbd5e1" strokeWidth="14" className="dark:stroke-[#2a364f]" />
              <rect x="535" y="65" width="110" height="110" fill="none" stroke="#cbd5e1" strokeWidth="14" className="dark:stroke-[#2a364f]" />
            </g>
          </svg>
        </div>
      );
    case 'pink-waves':
      return (
        <div className="w-full h-full relative overflow-hidden bg-pink-500 select-none">
          <svg className="w-full h-full object-cover" viewBox="0 0 600 200" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
            <defs>
              <linearGradient id={`pinkGradCover-${gradSuffix}`} x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stopColor="#f472b6" />
                <stop offset="50%" stopColor="#ec4899" />
                <stop offset="100%" stopColor="#db2777" />
              </linearGradient>
            </defs>
            <rect width="600" height="200" fill={`url(#pinkGradCover-${gradSuffix})`} />
            <path d="M-50,60 C150,170 320,-10 650,90 L650,220 L-50,220 Z" fill="#db2777" opacity="0.45" />
            <path d="M-50,110 C180,20 380,180 650,130 L650,220 L-50,220 Z" fill="#be185d" opacity="0.38" />
            <path d="M-50,20 C140,140 400,-10 650,50 L650,220 L-50,220 Z" fill="#fda4af" opacity="0.35" />
            <path d="M0,160 C220,90 380,200 650,120 L650,220 L0,220 Z" fill="#9d174d" opacity="0.3" />
          </svg>
        </div>
      );
    case 'branded-blue':
      return (
        <div className="w-full h-full relative overflow-hidden bg-gradient-to-r from-[#0c1a30] via-[#1a3668] to-[#162e58] flex items-center px-5 sm:px-6 select-none">
          <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top_left,rgba(59,130,246,0.35),transparent_65%)]" />
          <div className="absolute right-0 top-0 bottom-0 w-1/2 bg-[radial-gradient(circle_at_85%_50%,rgba(6,182,212,0.22),transparent_70%)]" />
          <div className="relative z-10 flex items-center gap-3.5 sm:gap-4 w-full">
            <img
              src="/img/Excelsior_Seal_Logo.png"
              alt="Excelsior"
              className="size-12 sm:size-14 object-contain drop-shadow-[0_4px_10px_rgba(0,0,0,0.6)] shrink-0"
            />
            <div className="min-w-0">
              <div className="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-amber-400">
                Excelsior Senior High
              </div>
              <div className="text-white font-bold text-sm sm:text-base tracking-tight truncate drop-shadow-sm">
                {title}
              </div>
              <div className="text-[11px] text-blue-200/90 font-medium tracking-wide">
                STEM 11-A | SY 2025-2026
              </div>
            </div>
          </div>
        </div>
      );
    case 'emerald-arcs':
      return (
        <div className="w-full h-full relative overflow-hidden bg-emerald-600 select-none">
          <svg className="w-full h-full object-cover" viewBox="0 0 600 200" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
            <defs>
              <linearGradient id={`emeraldGradCover-${gradSuffix}`} x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stopColor="#059669" />
                <stop offset="50%" stopColor="#10b981" />
                <stop offset="100%" stopColor="#14b8a6" />
              </linearGradient>
            </defs>
            <rect width="600" height="200" fill={`url(#emeraldGradCover-${gradSuffix})`} />
            <path d="M-50,150 C120,40 320,180 650,40 L650,220 L-50,220 Z" fill="#047857" opacity="0.45" />
            <path d="M-50,90 C180,190 400,20 650,120 L650,220 L-50,220 Z" fill="#065f46" opacity="0.35" />
            <path d="M-50,30 C200,120 380,-20 650,80 L650,220 L-50,220 Z" fill="#34d399" opacity="0.32" />
            <path d="M0,170 C240,110 440,210 650,150 L650,220 L0,220 Z" fill="#064e3b" opacity="0.38" />
          </svg>
        </div>
      );
    case 'purple-mesh':
      return (
        <div className="w-full h-full relative overflow-hidden bg-indigo-700 select-none">
          <svg className="w-full h-full object-cover" viewBox="0 0 600 200" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
            <defs>
              <linearGradient id={`purpleGradCover-${gradSuffix}`} x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stopColor="#3730a3" />
                <stop offset="50%" stopColor="#6366f1" />
                <stop offset="100%" stopColor="#8b5cf6" />
              </linearGradient>
            </defs>
            <rect width="600" height="200" fill={`url(#purpleGradCover-${gradSuffix})`} />
            <g opacity="0.3" stroke="#ffffff" strokeWidth="1.5" fill="none">
              <polygon points="100,20 180,60 180,140 100,180 20,140 20,60" />
              <polygon points="260,20 340,60 340,140 260,180 180,140 180,60" />
              <polygon points="420,20 500,60 500,140 420,180 340,140 340,60" />
              <polygon points="580,20 660,60 660,140 580,180 500,140 500,60" />
            </g>
            <path d="M-50,140 C150,50 350,180 650,70 L650,220 L-50,220 Z" fill="#1e1b4b" opacity="0.45" />
            <path d="M-50,60 C200,150 420,20 650,120 L650,220 L-50,220 Z" fill="#4338ca" opacity="0.35" />
          </svg>
        </div>
      );
    case 'amber-waves':
      return (
        <div className="w-full h-full relative overflow-hidden bg-amber-600 select-none">
          <svg className="w-full h-full object-cover" viewBox="0 0 600 200" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
            <defs>
              <linearGradient id={`amberGradCover-${gradSuffix}`} x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stopColor="#b45309" />
                <stop offset="50%" stopColor="#d97706" />
                <stop offset="100%" stopColor="#f59e0b" />
              </linearGradient>
            </defs>
            <rect width="600" height="200" fill={`url(#amberGradCover-${gradSuffix})`} />
            <path d="M-50,80 C160,180 360,10 650,110 L650,220 L-50,220 Z" fill="#78350f" opacity="0.45" />
            <path d="M-50,130 C200,40 420,190 650,130 L650,220 L-50,220 Z" fill="#92400e" opacity="0.35" />
            <path d="M-50,30 C180,130 380,-10 650,60 L650,220 L-50,220 Z" fill="#fbbf24" opacity="0.35" />
          </svg>
        </div>
      );
    default:
      return <div className="w-full h-full bg-accent/20" />;
  }
}

interface StudentDashboardProps {
  activeTab: string;
  onTabChange: (tab: string) => void;
  selectedCourseId?: string | null;
  onSelectCourse?: (courseId: string | null) => void;
  activeCourseSectionId?: string | null;
  onSelectCourseSection?: (sectionId: string) => void;
}

export function StudentDashboard({
  activeTab,
  onTabChange,
  selectedCourseId,
  onSelectCourse,
  activeCourseSectionId,
  onSelectCourseSection: _onSelectCourseSection,
}: StudentDashboardProps) {
  const handleOpenCourse = (courseId: string) => {
    onSelectCourse?.(courseId);
    onTabChange('courses');
  };
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

  // Student Dashboard Side A / Side B toggle state (default 'a' for Course Overview)
  const [dashboardSide, setDashboardSide] = useState<'a' | 'b'>('a');

  // Side A: Recently Accessed Courses carousel index
  const [recentCourseIndex, setRecentCourseIndex] = useState(0);

  // Side A: Course Overview toolbar filter / sort / layout view
  const [courseFilter, setCourseFilter] = useState<'all' | 'in-progress' | 'completed' | 'starred' | 'removed'>('all');
  const [courseSort, setCourseSort] = useState<'name' | 'accessed' | 'progress'>('name');
  const [courseViewMode, setCourseViewMode] = useState<'card' | 'list'>('card');
  const [filterDropdownOpen, setFilterDropdownOpen] = useState(false);
  const [sortDropdownOpen, setSortDropdownOpen] = useState(false);
  const [viewDropdownOpen, setViewDropdownOpen] = useState(false);
  const [activeMenuCourseId, setActiveMenuCourseId] = useState<string | null>(null);

  // Side A: Starred and Removed courses
  const [starredCourses, setStarredCourses] = useState<string[]>(['c-gen-math']);
  const [removedCourses, setRemovedCourses] = useState<string[]>([]);

  // Announcements State & Carousel
  const [announcements, setAnnouncements] = useState<AnnouncementItem[]>(INITIAL_ANNOUNCEMENTS);
  const [announcementIndex, setAnnouncementIndex] = useState(0);
  const [isCarouselPaused, setIsCarouselPaused] = useState(false);

  // Dedicated Announcements Tab Filter & Search
  const [annTabSearch, setAnnTabSearch] = useState('');
  const [annTabCategory, setAnnTabCategory] = useState<AnnouncementCategoryFilter>('All');
  const [annFilterRead, setAnnFilterRead] = useState<'all' | 'unread' | 'bookmarked'>('all');
  const [categoryDropdownOpen, setCategoryDropdownOpen] = useState(false);

  // Direct Message Modal State (Bryan Donceras -> Faculty Advisers)
  const [showMessageModal, setShowMessageModal] = useState(false);
  const [messageDraft, setMessageDraft] = useState('');
  const [messageSentSuccess, setMessageSentSuccess] = useState(false);
  const [messagesList, setMessagesList] = useState([
    {
      id: 1,
      sender: 'Ms. Juna Velarde',
      role: 'Adviser & Oral Comm Lead',
      text: 'Good morning Bryan! Kindly ensure your impromptu speech outline is uploaded before 5:00 PM tomorrow.',
      time: '10:30 AM',
      isTeacher: true,
    },
    {
      id: 2,
      sender: 'Mr. Roderick Tan',
      role: 'General Mathematics Faculty',
      text: 'Great work on the rational asymptotes assessment! Your solution method was very clear.',
      time: 'Yesterday',
      isTeacher: true,
    },
    {
      id: 3,
      sender: 'Dr. Corazon Bautista',
      role: 'Earth & Life Science',
      text: 'Lab schedules for Cabitan Campus are published. Please bring your safety gear on Friday.',
      time: '2 days ago',
      isTeacher: true,
    },
  ]);

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
        t.id === submitTaskModal.id
          ? { ...t, status: 'submitted', kanbanColumn: 'submitted', urgency: 'done', grade: t.grade ?? t.points }
          : t
      )
    );
    setTimeout(() => {
      setSubmitTaskModal(null);
      setSubmitSuccess(false);
      setSubmissionFileName('');
      setSubmissionRemarks('');
    }, 1500);
  };


  const recentCourses = ENROLLED_COURSES;
  const currentRecentCourse = recentCourses[recentCourseIndex % recentCourses.length];

  const handlePrevRecent = () => {
    setRecentCourseIndex((prev) => (prev === 0 ? recentCourses.length - 1 : prev - 1));
  };

  const handleNextRecent = () => {
    setRecentCourseIndex((prev) => (prev === recentCourses.length - 1 ? 0 : prev + 1));
  };

  const toggleStarCourse = (courseId: string) => {
    setStarredCourses((prev) =>
      prev.includes(courseId) ? prev.filter((id) => id !== courseId) : [...prev, courseId]
    );
    setActiveMenuCourseId(null);
  };

  const toggleRemoveCourse = (courseId: string) => {
    setRemovedCourses((prev) =>
      prev.includes(courseId) ? prev.filter((id) => id !== courseId) : [...prev, courseId]
    );
    setActiveMenuCourseId(null);
  };

  const handleSendMessage = (e: React.FormEvent) => {
    e.preventDefault();
    if (!messageDraft.trim()) return;
    setMessagesList((prev) => [
      ...prev,
      {
        id: Date.now(),
        sender: 'Bryan Donceras',
        role: 'Student (You)',
        text: messageDraft.trim(),
        time: 'Just now',
        isTeacher: false,
      },
    ]);
    setMessageDraft('');
    setMessageSentSuccess(true);
    setTimeout(() => setMessageSentSuccess(false), 2200);
  };

  // Auto advance announcements carousel every 6s unless hovered
  useEffect(() => {
    if (isCarouselPaused) return;
    const timer = setInterval(() => {
      setAnnouncementIndex((prev) => (prev + 1) % announcements.length);
    }, 6000);
    return () => clearInterval(timer);
  }, [isCarouselPaused, announcements.length]);

  const nextAnnouncement = () => {
    setAnnouncementIndex((prev) => (prev + 1) % announcements.length);
  };

  const prevAnnouncement = () => {
    setAnnouncementIndex((prev) => (prev === 0 ? announcements.length - 1 : prev - 1));
  };

  const toggleBookmark = (id: number) => {
    setAnnouncements((prev) =>
      prev.map((a) => (a.id === id ? { ...a, bookmarked: !a.bookmarked } : a))
    );
  };

  const toggleReadStatus = (id: number) => {
    setAnnouncements((prev) =>
      prev.map((a) => (a.id === id ? { ...a, isRead: !a.isRead } : a))
    );
  };

  const markAllAsRead = () => {
    setAnnouncements((prev) => prev.map((a) => ({ ...a, isRead: true })));
  };

  useEffect(() => {
    const handleClickOutside = () => {
      setFilterDropdownOpen(false);
      setSortDropdownOpen(false);
      setViewDropdownOpen(false);
      setCategoryDropdownOpen(false);
      setActiveMenuCourseId(null);
    };
    window.addEventListener('click', handleClickOutside);
    return () => window.removeEventListener('click', handleClickOutside);
  }, []);

  useEffect(() => {
    if (!showMessageModal && !categoryDropdownOpen && !filterDropdownOpen && !sortDropdownOpen && !viewDropdownOpen) return;
    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === 'Escape') {
        setShowMessageModal(false);
        setCategoryDropdownOpen(false);
        setFilterDropdownOpen(false);
        setSortDropdownOpen(false);
        setViewDropdownOpen(false);
      }
    };
    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [showMessageModal, categoryDropdownOpen, filterDropdownOpen, sortDropdownOpen, viewDropdownOpen]);

  const filteredAndSortedCourses = ENROLLED_COURSES
    .filter((c) => {
      const isRemoved = removedCourses.includes(c.id);
      if (courseFilter === 'removed') return isRemoved;
      if (isRemoved) return false;
      if (courseFilter === 'starred') return starredCourses.includes(c.id);
      if (courseFilter === 'in-progress') return c.progressPercent < 100;
      if (courseFilter === 'completed') return c.progressPercent >= 100;
      return true;
    })
    .sort((a, b) => {
      if (courseSort === 'name') {
        return a.title.localeCompare(b.title);
      }
      if (courseSort === 'accessed') {
        return b.lastAccessedTime - a.lastAccessedTime;
      }
      if (courseSort === 'progress') {
        return b.progressPercent - a.progressPercent;
      }
      return 0;
    });

  const filterLabels: Record<string, string> = {
    all: 'All (except removed from view)',
    'in-progress': 'In progress',
    completed: 'Completed',
    starred: 'Starred',
    removed: 'Removed from view',
  };

  const sortLabels: Record<string, string> = {
    name: 'Course name',
    accessed: 'Last accessed',
    progress: 'Percent complete',
  };

  const filteredAnnouncements = announcements.filter((a) => {
    if (
      annTabSearch &&
      !a.title.toLowerCase().includes(annTabSearch.toLowerCase()) &&
      !a.message.toLowerCase().includes(annTabSearch.toLowerCase()) &&
      !a.posterName.toLowerCase().includes(annTabSearch.toLowerCase())
    ) {
      return false;
    }
    if (annTabCategory === 'Important' && a.type !== 'important') return false;
    if (annTabCategory !== 'All' && annTabCategory !== 'Important' && a.category !== annTabCategory) {
      return false;
    }
    if (annFilterRead === 'unread' && a.isRead) return false;
    if (annFilterRead === 'bookmarked' && !a.bookmarked) return false;
    return true;
  });

  const activeCategoryConfig =
    ANNOUNCEMENT_CATEGORIES.find((c) => c.id === annTabCategory) || ANNOUNCEMENT_CATEGORIES[0];

  const getCategoryCount = (catId: AnnouncementCategoryFilter) => {
    if (catId === 'All') return announcements.length;
    if (catId === 'Important') return announcements.filter((a) => a.type === 'important').length;
    return announcements.filter((a) => a.category === catId).length;
  };

  return (
    <div className="space-y-6">
      {/* ============================================================ */}
      {/* 1. DASHBOARD PAGE (students/index.php lines 531-880)          */}
      {/* ============================================================ */}
      {activeTab === 'dashboard' && (
        <div className="space-y-6">
          {/* Student Profile Header Card + Side A / Side B Toggle (Screenshot 1) */}
          <div className="rounded-2xl bg-bg2 border border-border p-4 sm:p-5 flex flex-col md:flex-row md:items-center justify-between gap-4 shadow-xs">
            {/* Left: Avatar + Name + Message Button + Subtitle */}
            <div className="flex items-center gap-3.5 sm:gap-4 min-w-0">
              <div className="relative size-16 sm:size-18 rounded-full shrink-0 ring-4 ring-bg3 shadow-md overflow-hidden bg-gradient-to-br from-blue-500 via-indigo-600 to-accent flex items-center justify-center text-white select-none">
                <span className="text-xl sm:text-2xl font-bold tracking-tight">BD</span>
                <div className="absolute bottom-0 inset-x-0 bg-black/40 backdrop-blur-xs py-0.5 flex items-center justify-center">
                  <RiGraduationCapFill className="size-3 sm:size-3.5 text-amber-300" aria-hidden="true" />
                </div>
              </div>
              <div className="min-w-0">
                <div className="flex flex-wrap items-center gap-2 sm:gap-3">
                  <h1 className="font-serif text-2xl sm:text-3xl font-bold tracking-tight text-text">
                    Bryan Donceras
                  </h1>
                  <button
                    type="button"
                    onClick={() => setShowMessageModal(true)}
                    className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold text-text2 hover:text-accent bg-bg3/80 hover:bg-bg3 border border-border transition-all cursor-pointer press-scale shadow-xs"
                    aria-label="Send message to faculty advisers"
                  >
                    <RiMessage3Line className="size-3.5 text-accent" aria-hidden="true" />
                    <span>Message</span>
                  </button>
                </div>
                <div className="text-xs sm:text-sm text-text2 mt-1 flex flex-wrap items-center gap-2">
                  <span>STEM 11-A · LRN 109823456712</span>
                  <span className="size-1 rounded-full bg-border" aria-hidden="true" />
                  <span className="inline-flex items-center gap-1 text-[11px] font-semibold text-green dark:text-green">
                    <span className="size-1.5 rounded-full bg-green animate-pulse" aria-hidden="true" />
                    Active Enrolled
                  </span>
                </div>
              </div>
            </div>

            {/* Right: Side A / Side B Dashboard Switcher */}
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
                  <span>Side A: Course Overview</span>
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
                  <span>Side B: Analytics & Activity</span>
                </button>
              </div>
            </div>
          </div>

          {/* ============================================================ */}
          {/* SIDE A: COURSE OVERVIEW (Screenshots 1 & 2)                  */}
          {/* ============================================================ */}
          {dashboardSide === 'a' && (
            <div className="space-y-6">
              {/* Compact Announcement Carousel Banner on Side A */}
              {announcements.length > 0 && (
                <div
                  className="rounded-xl bg-bg2 border border-border p-3 sm:px-4 sm:py-2.5 flex items-center justify-between gap-3 shadow-xs"
                  onMouseEnter={() => setIsCarouselPaused(true)}
                  onMouseLeave={() => setIsCarouselPaused(false)}
                >
                  <div className="flex items-center gap-2.5 sm:gap-3 min-w-0 flex-1">
                    <span className="inline-flex items-center gap-1 text-[11px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-accent/10 text-accent shrink-0">
                      <RiMegaphoneLine className="size-3" aria-hidden="true" />
                      <span>Notice</span>
                    </span>
                    <div className="min-w-0 flex-1 flex items-center gap-2">
                      <span className={`text-[10px] font-bold uppercase px-1.5 py-0.5 rounded shrink-0 ${
                        announcements[announcementIndex % announcements.length].type === 'important'
                          ? 'bg-red-500/10 text-red border border-red-500/20'
                          : announcements[announcementIndex % announcements.length].type === 'info'
                          ? 'bg-blue-500/10 text-accent border border-accent/20'
                          : 'bg-purple-500/10 text-purple border border-purple-500/20'
                      }`}>
                        {announcements[announcementIndex % announcements.length].type}
                      </span>
                      <button
                        type="button"
                        onClick={() => onTabChange('announcements')}
                        className="text-xs font-semibold text-text hover:text-accent truncate transition-colors text-left cursor-pointer"
                      >
                        {announcements[announcementIndex % announcements.length].title}
                      </button>
                    </div>
                  </div>

                  <div className="flex items-center gap-2 shrink-0">
                    <button
                      type="button"
                      onClick={() => onTabChange('announcements')}
                      className="hidden sm:inline-flex text-xs font-semibold text-accent hover:underline cursor-pointer"
                    >
                      View in Announcements Tab →
                    </button>
                    <div className="flex items-center gap-1">
                      <button
                        type="button"
                        onClick={prevAnnouncement}
                        aria-label="Previous announcement"
                        className="p-1 rounded-md text-text3 hover:text-text hover:bg-bg3 cursor-pointer"
                      >
                        <RiArrowLeftSLine className="size-3.5" aria-hidden="true" />
                      </button>
                      <span className="text-[10px] text-text3 font-mono">
                        {(announcementIndex % announcements.length) + 1}/{announcements.length}
                      </span>
                      <button
                        type="button"
                        onClick={nextAnnouncement}
                        aria-label="Next announcement"
                        className="p-1 rounded-md text-text3 hover:text-text hover:bg-bg3 cursor-pointer"
                      >
                        <RiArrowRightSLine className="size-3.5" aria-hidden="true" />
                      </button>
                    </div>
                  </div>
                </div>
              )}

              {/* Recently Accessed Courses Card (Screenshot 1) */}
              <div className="rounded-2xl bg-bg2 border border-border p-5 shadow-xs space-y-4">
                <div className="flex items-center justify-between">
                  <h2 className="text-base sm:text-lg font-bold text-text">
                    Recently accessed courses
                  </h2>
                  <div className="flex items-center gap-1.5">
                    <button
                      type="button"
                      onClick={handlePrevRecent}
                      aria-label="Previous recently accessed course"
                      className="p-1.5 rounded-lg border border-border bg-bg3/60 hover:bg-bg3 text-text hover:text-accent cursor-pointer press-scale transition-colors"
                    >
                      <RiArrowLeftSLine className="size-4" aria-hidden="true" />
                    </button>
                    <button
                      type="button"
                      onClick={handleNextRecent}
                      aria-label="Next recently accessed course"
                      className="p-1.5 rounded-lg border border-border bg-bg3/60 hover:bg-bg3 text-text hover:text-accent cursor-pointer press-scale transition-colors"
                    >
                      <RiArrowRightSLine className="size-4" aria-hidden="true" />
                    </button>
                  </div>
                </div>

                {/* Featured Horizontal Course Banner Card (Screenshot 1) */}
                <div className="max-w-2xl mx-auto rounded-xl bg-bg border border-border shadow-xs overflow-hidden hover:border-accent/40 transition-all group">
                  {/* Rich branded banner header */}
                  <div
                    onClick={() => handleOpenCourse(currentRecentCourse.id)}
                    className="h-32 sm:h-36 w-full relative overflow-hidden bg-gradient-to-r from-[#0c1a30] via-[#1a3668] to-[#162e58] flex items-center px-6 cursor-pointer select-none"
                  >
                    <div className="absolute inset-0 bg-[radial-gradient(ellipse_at_top_left,rgba(59,130,246,0.35),transparent_65%)]" />
                    <div className="absolute right-0 top-0 bottom-0 w-1/3 bg-[radial-gradient(circle_at_80%_50%,rgba(6,182,212,0.25),transparent_70%)]" />
                    <div className="relative z-10 flex items-center gap-4 w-full">
                      <img
                        src="/img/Excelsior_Seal_Logo.png"
                        alt="Excelsior Seal"
                        className="size-14 sm:size-16 object-contain drop-shadow-[0_4px_10px_rgba(0,0,0,0.6)] shrink-0 group-hover:scale-105 transition-transform"
                      />
                      <div className="min-w-0">
                        <div className="text-[11px] font-bold uppercase tracking-wider text-amber-400">
                          Excelsior Senior High School
                        </div>
                        <div className="text-white font-bold text-base sm:text-xl tracking-tight truncate drop-shadow-sm">
                          {currentRecentCourse.title}
                        </div>
                        <div className="text-xs text-blue-200/90 font-medium tracking-wide">
                          STEM 11-A | SY 2025-2026
                        </div>
                      </div>
                    </div>
                  </div>

                  {/* Below banner: Term & clickable title */}
                  <div className="p-4 bg-bg2 flex items-center justify-between gap-4">
                    <div className="min-w-0 flex-1 space-y-1">
                      <div className="text-xs text-text3 font-medium">
                        {currentRecentCourse.semester}
                      </div>
                      <button
                        type="button"
                        onClick={() => handleOpenCourse(currentRecentCourse.id)}
                        className="text-left font-bold text-sm sm:text-base text-text hover:text-accent transition-colors block truncate cursor-pointer"
                      >
                        {currentRecentCourse.fullTitle}
                      </button>
                    </div>
                    {/* Carousel indicator dots */}
                    <div className="flex items-center gap-1.5 shrink-0">
                      {recentCourses.map((_, idx) => (
                        <button
                          key={idx}
                          type="button"
                          onClick={() => setRecentCourseIndex(idx)}
                          aria-label={`Go to slide ${idx + 1}`}
                          className={`size-2 rounded-full transition-all cursor-pointer ${
                            recentCourseIndex % recentCourses.length === idx
                              ? 'bg-accent w-4'
                              : 'bg-border hover:bg-text3'
                          }`}
                        />
                      ))}
                    </div>
                  </div>
                </div>
              </div>

              {/* Course Overview Card (Screenshot 2) */}
              <div className="rounded-2xl bg-bg2 border border-border p-5 shadow-xs space-y-4">
                <h2 className="text-base sm:text-lg font-bold text-text">
                  Course overview
                </h2>

                {/* Filter / Sort / View Toolbar (Screenshot 2) */}
                <div className="flex flex-wrap items-center justify-between gap-3 border-b border-border/50 pb-4">
                  {/* Left: Filter dropdown */}
                  <div className="relative">
                    <button
                      type="button"
                      onClick={(e) => {
                        e.stopPropagation();
                        setFilterDropdownOpen(!filterDropdownOpen);
                        setSortDropdownOpen(false);
                        setViewDropdownOpen(false);
                      }}
                      className="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-border bg-bg3/70 hover:bg-bg3 text-xs font-semibold text-text cursor-pointer transition-colors"
                      aria-expanded={filterDropdownOpen}
                      aria-label="Filter courses"
                    >
                      <RiFilter3Line className="size-3.5 text-text3" aria-hidden="true" />
                      <span>{filterLabels[courseFilter]}</span>
                      <RiArrowDownSLine className="size-3.5 text-text3" aria-hidden="true" />
                    </button>
                    {filterDropdownOpen && (
                      <div
                        onClick={(e) => e.stopPropagation()}
                        className="absolute left-0 mt-1 w-64 rounded-xl bg-bg2 border border-border shadow-lg p-1.5 z-30 space-y-0.5"
                      >
                        {(['all', 'in-progress', 'completed', 'starred', 'removed'] as const).map((key) => (
                          <button
                            key={key}
                            type="button"
                            onClick={() => {
                              setCourseFilter(key);
                              setFilterDropdownOpen(false);
                            }}
                            className={`w-full text-left px-3 py-1.5 rounded-lg text-xs font-medium cursor-pointer flex items-center justify-between ${
                              courseFilter === key
                                ? 'bg-accent/10 text-accent font-semibold'
                                : 'text-text2 hover:text-text hover:bg-bg3'
                            }`}
                          >
                            <span>{filterLabels[key]}</span>
                            {courseFilter === key && <RiCheckLine className="size-3.5 text-accent" />}
                          </button>
                        ))}
                      </div>
                    )}
                  </div>

                  {/* Right: Sort and View mode */}
                  <div className="flex items-center gap-2.5">
                    {/* Sort select dropdown */}
                    <div className="relative">
                      <button
                        type="button"
                        onClick={(e) => {
                          e.stopPropagation();
                          setSortDropdownOpen(!sortDropdownOpen);
                          setFilterDropdownOpen(false);
                          setViewDropdownOpen(false);
                        }}
                        className="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-border bg-bg3/70 hover:bg-bg3 text-xs font-semibold text-text cursor-pointer transition-colors"
                        aria-expanded={sortDropdownOpen}
                        aria-label="Sort courses"
                      >
                        <RiArrowUpDownLine className="size-3.5 text-text3" aria-hidden="true" />
                        <span>{sortLabels[courseSort]}</span>
                        <RiArrowDownSLine className="size-3.5 text-text3" aria-hidden="true" />
                      </button>
                      {sortDropdownOpen && (
                        <div
                          onClick={(e) => e.stopPropagation()}
                          className="absolute right-0 mt-1 w-44 rounded-xl bg-bg2 border border-border shadow-lg p-1.5 z-30 space-y-0.5"
                        >
                          {(['name', 'accessed', 'progress'] as const).map((key) => (
                            <button
                              key={key}
                              type="button"
                              onClick={() => {
                                setCourseSort(key);
                                setSortDropdownOpen(false);
                              }}
                              className={`w-full text-left px-3 py-1.5 rounded-lg text-xs font-medium cursor-pointer flex items-center justify-between ${
                                courseSort === key
                                  ? 'bg-accent/10 text-accent font-semibold'
                                  : 'text-text2 hover:text-text hover:bg-bg3'
                              }`}
                            >
                              <span>{sortLabels[key]}</span>
                              {courseSort === key && <RiCheckLine className="size-3.5 text-accent" />}
                            </button>
                          ))}
                        </div>
                      )}
                    </div>

                    {/* View layout mode dropdown (Screenshot 2: Card / List) */}
                    <div className="relative">
                      <button
                        type="button"
                        onClick={(e) => {
                          e.stopPropagation();
                          setViewDropdownOpen(!viewDropdownOpen);
                          setFilterDropdownOpen(false);
                          setSortDropdownOpen(false);
                        }}
                        className="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-border bg-bg3/70 hover:bg-bg3 text-xs font-semibold text-text cursor-pointer transition-colors"
                        aria-expanded={viewDropdownOpen}
                        aria-label="Display mode"
                      >
                        {courseViewMode === 'card' ? (
                          <RiLayoutGridLine className="size-3.5 text-text3" aria-hidden="true" />
                        ) : (
                          <RiListUnordered className="size-3.5 text-text3" aria-hidden="true" />
                        )}
                        <span>{courseViewMode === 'card' ? 'Card' : 'List'}</span>
                        <RiArrowDownSLine className="size-3.5 text-text3" aria-hidden="true" />
                      </button>
                      {viewDropdownOpen && (
                        <div
                          onClick={(e) => e.stopPropagation()}
                          className="absolute right-0 mt-1 w-36 rounded-xl bg-bg2 border border-border shadow-lg p-1.5 z-30 space-y-0.5"
                        >
                          <button
                            type="button"
                            onClick={() => {
                              setCourseViewMode('card');
                              setViewDropdownOpen(false);
                            }}
                            className={`w-full text-left px-3 py-1.5 rounded-lg text-xs font-medium cursor-pointer flex items-center justify-between ${
                              courseViewMode === 'card'
                                ? 'bg-accent/10 text-accent font-semibold'
                                : 'text-text2 hover:text-text hover:bg-bg3'
                            }`}
                          >
                            <span className="flex items-center gap-2">
                              <RiLayoutGridLine className="size-3.5" />
                              <span>Card</span>
                            </span>
                            {courseViewMode === 'card' && <RiCheckLine className="size-3.5 text-accent" />}
                          </button>
                          <button
                            type="button"
                            onClick={() => {
                              setCourseViewMode('list');
                              setViewDropdownOpen(false);
                            }}
                            className={`w-full text-left px-3 py-1.5 rounded-lg text-xs font-medium cursor-pointer flex items-center justify-between ${
                              courseViewMode === 'list'
                                ? 'bg-accent/10 text-accent font-semibold'
                                : 'text-text2 hover:text-text hover:bg-bg3'
                            }`}
                          >
                            <span className="flex items-center gap-2">
                              <RiListUnordered className="size-3.5" />
                              <span>List</span>
                            </span>
                            {courseViewMode === 'list' && <RiCheckLine className="size-3.5 text-accent" />}
                          </button>
                        </div>
                      )}
                    </div>
                  </div>
                </div>

                {/* Course Cards Grid (2-column layout matching Screenshot 2) */}
                {filteredAndSortedCourses.length === 0 ? (
                  <div className="p-8 text-center rounded-xl bg-bg3/40 border border-dashed border-border space-y-2">
                    <p className="text-sm font-semibold text-text">No courses found</p>
                    <p className="text-xs text-text3">No courses match the selected filter criteria.</p>
                    <button
                      type="button"
                      onClick={() => {
                        setCourseFilter('all');
                        setRemovedCourses([]);
                      }}
                      className="btn btn-secondary btn-sm text-xs mt-2 cursor-pointer"
                    >
                      Reset filter
                    </button>
                  </div>
                ) : courseViewMode === 'card' ? (
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-5 pt-1">
                    {filteredAndSortedCourses.map((c) => {
                      const isStarred = starredCourses.includes(c.id);
                      const isRemoved = removedCourses.includes(c.id);
                      const isMenuOpen = activeMenuCourseId === c.id;

                      return (
                        <div
                          key={c.id}
                          className="rounded-xl bg-bg2 border border-border hover:border-accent/40 shadow-xs hover:shadow-md transition-all flex flex-col justify-between group"
                        >
                          {/* Top artwork header banner (Screenshot 2) */}
                          <div
                            onClick={() => handleOpenCourse(c.id)}
                            className="h-36 sm:h-40 w-full relative overflow-hidden rounded-t-xl border-b border-border/40 cursor-pointer"
                          >
                            <CourseCoverArt variant={c.artVariant} title={c.title} id={c.id} />
                            {isStarred && (
                              <div
                                title="Starred course"
                                className="absolute top-3 left-3 size-7 rounded-full bg-black/50 backdrop-blur-xs flex items-center justify-center text-amber-400 shadow-sm"
                              >
                                <RiStarFill className="size-4" aria-hidden="true" />
                              </div>
                            )}
                          </div>

                          {/* Card body */}
                          <div className="p-4 space-y-3">
                            <div className="flex items-center justify-between relative">
                              <span className="text-xs text-text3 font-medium">
                                {c.semester}
                              </span>

                              {/* ••• Menu button (Screenshot 2) */}
                              <div className="relative">
                                <button
                                  type="button"
                                  onClick={(e) => {
                                    e.stopPropagation();
                                    setActiveMenuCourseId(isMenuOpen ? null : c.id);
                                  }}
                                  aria-label="Course options"
                                  className="p-1 rounded-md text-text3 hover:text-text hover:bg-bg3 cursor-pointer transition-colors"
                                >
                                  <RiMoreFill className="size-4" aria-hidden="true" />
                                </button>

                                {isMenuOpen && (
                                  <div
                                    onClick={(e) => e.stopPropagation()}
                                    className="absolute right-0 mt-1 w-48 rounded-xl bg-bg2 border border-border shadow-xl p-1 z-30 space-y-0.5 text-xs"
                                  >
                                    <button
                                      type="button"
                                      onClick={() => toggleStarCourse(c.id)}
                                      className="w-full text-left px-3 py-1.5 rounded-lg text-text2 hover:text-text hover:bg-bg3 cursor-pointer flex items-center gap-2"
                                    >
                                      {isStarred ? (
                                        <>
                                          <RiStarFill className="size-3.5 text-amber-400" />
                                          <span>Unstar course</span>
                                        </>
                                      ) : (
                                        <>
                                          <RiStarLine className="size-3.5 text-text3" />
                                          <span>Star course</span>
                                        </>
                                      )}
                                    </button>

                                    <button
                                      type="button"
                                      onClick={() => toggleRemoveCourse(c.id)}
                                      className="w-full text-left px-3 py-1.5 rounded-lg text-text2 hover:text-text hover:bg-bg3 cursor-pointer flex items-center gap-2"
                                    >
                                      <RiEyeOffLine className="size-3.5 text-text3" />
                                      <span>{isRemoved ? 'Restore to view' : 'Remove from view'}</span>
                                    </button>

                                    <button
                                      type="button"
                                      onClick={() => {
                                        setActiveMenuCourseId(null);
                                        setActivePdfModule({
                                          title: `${c.title} — Syllabus & Curriculum Guide`,
                                          subject: c.title,
                                        });
                                      }}
                                      className="w-full text-left px-3 py-1.5 rounded-lg text-text2 hover:text-text hover:bg-bg3 cursor-pointer flex items-center gap-2"
                                    >
                                      <RiFilePdfLine className="size-3.5 text-red" />
                                      <span>View syllabus PDF</span>
                                    </button>

                                    <button
                                      type="button"
                                      onClick={() => {
                                        setActiveMenuCourseId(null);
                                        handleOpenCourse(c.id);
                                      }}
                                      className="w-full text-left px-3 py-1.5 rounded-lg text-accent hover:bg-accent/10 cursor-pointer flex items-center gap-2 font-semibold"
                                    >
                                      <RiBookOpenLine className="size-3.5" />
                                      <span>Open course</span>
                                    </button>
                                  </div>
                                )}
                              </div>
                            </div>

                            {/* Clickable Course Title (Screenshot 2) */}
                            <button
                              type="button"
                              onClick={() => handleOpenCourse(c.id)}
                              className="text-left font-bold text-sm sm:text-base text-text hover:text-accent transition-colors block line-clamp-2 cursor-pointer"
                            >
                              {c.fullTitle}
                            </button>

                            {/* Full-width progress bar (Screenshot 2) */}
                            <div className="space-y-1.5 pt-1">
                              <div className="h-1.5 w-full bg-bg3 rounded-full overflow-hidden">
                                <div
                                  className="h-full bg-accent rounded-full transition-all duration-500"
                                  style={{ width: `${c.progressPercent}%` }}
                                />
                              </div>
                              <div className="flex items-center justify-between text-xs pt-0.5">
                                <span className="font-semibold text-text2 tabular-nums">
                                  {c.progressPercent}% complete
                                </span>
                                <span className="text-[11px] text-text3">
                                  {c.teacher}
                                </span>
                              </div>
                            </div>
                          </div>
                        </div>
                      );
                    })}
                  </div>
                ) : (
                  /* 1-Column List Layout */
                  <div className="space-y-3 pt-1">
                    {filteredAndSortedCourses.map((c) => {
                      const isStarred = starredCourses.includes(c.id);
                      const isMenuOpen = activeMenuCourseId === c.id;

                      return (
                        <div
                          key={c.id}
                          className="rounded-xl bg-bg2 border border-border hover:border-accent/40 p-3 sm:p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-xs transition-all"
                        >
                          <div className="flex items-center gap-3.5 min-w-0 flex-1">
                            <div
                              onClick={() => handleOpenCourse(c.id)}
                              className="w-32 sm:w-40 h-20 rounded-lg overflow-hidden shrink-0 cursor-pointer border border-border/60 relative"
                            >
                              <CourseCoverArt variant={c.artVariant} title={c.title} id={c.id} />
                              {isStarred && (
                                <div className="absolute top-1.5 left-1.5 size-5 rounded-full bg-black/50 flex items-center justify-center text-amber-400">
                                  <RiStarFill className="size-3" />
                                </div>
                              )}
                            </div>
                            <div className="min-w-0 flex-1 space-y-1">
                              <div className="text-[11px] text-text3 font-medium">{c.semester}</div>
                              <button
                                type="button"
                                onClick={() => handleOpenCourse(c.id)}
                                className="text-left font-bold text-sm sm:text-base text-text hover:text-accent transition-colors block truncate cursor-pointer"
                              >
                                {c.fullTitle}
                              </button>
                              <div className="flex items-center gap-3 text-xs text-text3">
                                <span>{c.teacher}</span>
                                <span>·</span>
                                <span className="font-semibold text-accent">{c.progressPercent}% complete</span>
                              </div>
                              <div className="h-1.5 max-w-xs bg-bg3 rounded-full overflow-hidden mt-1">
                                <div className="h-full bg-accent rounded-full" style={{ width: `${c.progressPercent}%` }} />
                              </div>
                            </div>
                          </div>

                          <div className="flex items-center gap-2 self-end sm:self-center shrink-0">
                            <button
                              type="button"
                              onClick={() => handleOpenCourse(c.id)}
                              className="btn btn-secondary btn-sm text-xs cursor-pointer"
                            >
                              Open Course
                            </button>
                            <div className="relative">
                              <button
                                type="button"
                                onClick={(e) => {
                                  e.stopPropagation();
                                  setActiveMenuCourseId(isMenuOpen ? null : c.id);
                                }}
                                className="p-1.5 rounded-lg text-text3 hover:text-text hover:bg-bg3 cursor-pointer"
                              >
                                <RiMoreFill className="size-4" />
                              </button>
                              {isMenuOpen && (
                                <div
                                  onClick={(e) => e.stopPropagation()}
                                  className="absolute right-0 mt-1 w-48 rounded-xl bg-bg2 border border-border shadow-xl p-1 z-30 space-y-0.5 text-xs"
                                >
                                  <button
                                    type="button"
                                    onClick={() => toggleStarCourse(c.id)}
                                    className="w-full text-left px-3 py-1.5 rounded-lg text-text2 hover:text-text hover:bg-bg3 cursor-pointer flex items-center gap-2"
                                  >
                                    {isStarred ? (
                                      <>
                                        <RiStarFill className="size-3.5 text-amber-400" />
                                        <span>Unstar course</span>
                                      </>
                                    ) : (
                                      <>
                                        <RiStarLine className="size-3.5 text-text3" />
                                        <span>Star course</span>
                                      </>
                                    )}
                                  </button>
                                  <button
                                    type="button"
                                    onClick={() => toggleRemoveCourse(c.id)}
                                    className="w-full text-left px-3 py-1.5 rounded-lg text-text2 hover:text-text hover:bg-bg3 cursor-pointer flex items-center gap-2"
                                  >
                                    <RiEyeOffLine className="size-3.5 text-text3" />
                                    <span>{removedCourses.includes(c.id) ? 'Restore to view' : 'Remove from view'}</span>
                                  </button>
                                  <button
                                    type="button"
                                    onClick={() => {
                                      setActiveMenuCourseId(null);
                                      setActivePdfModule({
                                        title: `${c.title} — Syllabus & Curriculum Guide`,
                                        subject: c.title,
                                      });
                                    }}
                                    className="w-full text-left px-3 py-1.5 rounded-lg text-text2 hover:text-text hover:bg-bg3 cursor-pointer flex items-center gap-2"
                                  >
                                    <RiFilePdfLine className="size-3.5 text-red" />
                                    <span>View syllabus PDF</span>
                                  </button>
                                  <button
                                    type="button"
                                    onClick={() => {
                                      setActiveMenuCourseId(null);
                                      handleOpenCourse(c.id);
                                    }}
                                    className="w-full text-left px-3 py-1.5 rounded-lg text-accent hover:bg-accent/10 cursor-pointer flex items-center gap-2 font-semibold"
                                  >
                                    <RiBookOpenLine className="size-3.5" />
                                    <span>Open course</span>
                                  </button>
                                </div>
                              )}
                            </div>
                          </div>
                        </div>
                      );
                    })}
                  </div>
                )}
              </div>
            </div>
          )}

          {/* ============================================================ */}
          {/* SIDE B: ANALYTICS & ACTIVITY (Retains 100% existing elements) */}
          {/* ============================================================ */}
          {dashboardSide === 'b' && (
            <div className="space-y-6">
              {/* Section Header (students/index.php lines 532-537) */}
              <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-1">
                <div>
                  <div className="flex items-center gap-2">
                    <h1 className="font-serif text-2xl sm:text-3xl font-bold tracking-tight text-text">
                      Welcome back, Bryan!
                    </h1>
                    <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/25">
                      <RiFireFill className="size-3.5 text-amber-500" />
                      <span>7-day streak</span>
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

              {/* Announcements Carousel on Side B */}
              <div
                className="card overflow-hidden"
                onMouseEnter={() => setIsCarouselPaused(true)}
                onMouseLeave={() => setIsCarouselPaused(false)}
              >
                <div className="card-header border-b border-border/50 pb-3 flex-wrap gap-2">
                  <div className="card-title flex items-center gap-2.5">
                    <div className="p-1.5 rounded-lg bg-accent/10 text-accent">
                      <RiNotification3Line size={18} />
                    </div>
                    <span className="font-bold text-text">Announcements</span>
                    <span className="badge badge-blue">
                      {announcements.filter((a) => !a.isRead).length} new notices
                    </span>
                  </div>

                  <div className="flex items-center gap-3">
                    {/* Link to Announcements Tab */}
                    <button
                      type="button"
                      onClick={() => onTabChange('announcements')}
                      className="text-xs font-semibold text-accent hover:text-accent2 hover:underline inline-flex items-center gap-1 cursor-pointer press-scale"
                    >
                      <span>View all in Announcements Tab</span>
                      <RiArrowRightLine size={13} />
                    </button>

                    {/* Carousel Navigation Buttons */}
                    <div className="flex items-center gap-1">
                      <button
                        type="button"
                        onClick={prevAnnouncement}
                        aria-label="Previous announcement"
                        className="p-1.5 rounded-lg border border-border bg-bg3/60 hover:bg-bg3 text-text hover:text-accent cursor-pointer press-scale transition-colors"
                      >
                        <RiArrowLeftSLine className="size-4" />
                      </button>
                      <button
                        type="button"
                        onClick={nextAnnouncement}
                        aria-label="Next announcement"
                        className="p-1.5 rounded-lg border border-border bg-bg3/60 hover:bg-bg3 text-text hover:text-accent cursor-pointer press-scale transition-colors"
                      >
                        <RiArrowRightSLine className="size-4" />
                      </button>
                    </div>
                  </div>
                </div>

                {/* Carousel Card Content */}
                <div className="pt-2">
                  {(() => {
                    const currentAnn = announcements[announcementIndex % announcements.length];
                    return (
                      <div
                        className={`p-4 sm:p-5 rounded-xl border transition-all duration-300 ${
                          currentAnn.type === 'important'
                            ? 'bg-red-500/5 border-red-500/25'
                            : currentAnn.type === 'info'
                            ? 'bg-blue-500/5 border-accent/25'
                            : 'bg-purple-500/5 border-purple-500/25'
                        }`}
                      >
                        <div className="flex flex-wrap items-center justify-between gap-2 mb-2.5">
                          <div className="flex items-center gap-2">
                            <span className={`announce-tag ${currentAnn.type}`}>
                              {currentAnn.type}
                            </span>
                            <span
                              className="inline-flex items-center gap-1 text-[11px] font-semibold px-2.5 py-0.5 rounded-full"
                              style={{
                                background:
                                  currentAnn.posterRole === 'admin'
                                    ? 'rgba(239, 68, 68, 0.12)'
                                    : 'rgba(139, 92, 246, 0.12)',
                                color:
                                  currentAnn.posterRole === 'admin'
                                    ? 'var(--red)'
                                    : 'var(--purple)',
                              }}
                            >
                              {currentAnn.posterRole === 'admin' ? (
                                <RiShieldCheckLine size={12} />
                              ) : (
                                <RiUserStarLine size={12} />
                              )}
                              {currentAnn.posterRole === 'admin' ? 'Admin' : 'Teacher'}: {currentAnn.posterName}
                            </span>
                            <span className="text-[11px] text-text3 font-medium hidden sm:inline">
                              {currentAnn.tag}
                            </span>
                          </div>
                          <span className="text-xs text-text3 font-medium">
                            {currentAnn.timeLabel} · {currentAnn.date}
                          </span>
                        </div>

                        <h3
                          onClick={() => onTabChange('announcements')}
                          className="text-base sm:text-lg font-bold text-text hover:text-accent transition-colors cursor-pointer"
                        >
                          {currentAnn.title}
                        </h3>

                        <p className="text-xs sm:text-sm text-text2 leading-relaxed mt-1.5">
                          {currentAnn.message}
                        </p>

                        <div className="flex items-center justify-between pt-4 mt-3 border-t border-border/40">
                          <div className="flex items-center gap-1.5">
                            {announcements.map((_, idx) => (
                              <button
                                key={idx}
                                type="button"
                                onClick={() => setAnnouncementIndex(idx)}
                                aria-label={`Announcement slide ${idx + 1}`}
                                className={`size-2 rounded-full transition-all cursor-pointer ${
                                  announcementIndex % announcements.length === idx
                                    ? 'bg-accent w-5'
                                    : 'bg-border hover:bg-text3'
                                }`}
                              />
                            ))}
                            <span className="text-[11px] text-text3 ml-2 font-mono">
                              {(announcementIndex % announcements.length) + 1} of {announcements.length}
                            </span>
                          </div>

                          <button
                            type="button"
                            onClick={() => onTabChange('announcements')}
                            className="btn btn-secondary btn-sm text-xs cursor-pointer inline-flex items-center gap-1"
                          >
                            <span>Read in Announcements Tab</span>
                            <RiArrowRightLine size={13} />
                          </button>
                        </div>
                      </div>
                    );
                  })()}
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
                            <span className={act.passed ? 'text-green font-semibold inline-flex items-center gap-0.5' : 'text-amber font-semibold inline-flex items-center gap-0.5'}>
                              {act.passed ? (
                                <>
                                  <RiCheckLine size={12} className="inline" />
                                  <span>Passed</span>
                                </>
                              ) : (
                                <>
                                  <RiCloseLine size={12} className="inline" />
                                  <span>Below passing</span>
                                </>
                              )}
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
        </div>
      )}

      {/* Direct Message Modal */}
      {showMessageModal && (
        <div
          role="dialog"
          aria-modal="true"
          onClick={() => setShowMessageModal(false)}
          className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs cursor-pointer"
        >
          <div
            onClick={(e) => e.stopPropagation()}
            className="w-full max-w-lg rounded-2xl bg-bg2 border border-border shadow-2xl overflow-hidden flex flex-col max-h-[85vh] animate-in fade-in duration-200 cursor-default"
          >
            {/* Header */}
            <div className="p-4 sm:p-5 border-b border-border flex items-center justify-between bg-bg3/40">
              <div className="flex items-center gap-3">
                <div className="p-2 rounded-xl bg-accent/10 text-accent">
                  <RiMessage3Line className="size-5" />
                </div>
                <div>
                  <h3 className="font-bold text-base text-text">Messages & Faculty Advisers</h3>
                  <p className="text-xs text-text3">Bryan Donceras · Grade 11 STEM Section A</p>
                </div>
              </div>
              <button
                type="button"
                onClick={() => setShowMessageModal(false)}
                className="p-1.5 rounded-lg text-text3 hover:text-text hover:bg-bg3 cursor-pointer"
                aria-label="Close messages"
              >
                <RiCloseLine className="size-5" />
              </button>
            </div>

            {/* Message Thread */}
            <div className="p-4 sm:p-5 overflow-y-auto space-y-3.5 flex-1 max-h-[420px]">
              {messagesList.map((m) => (
                <div
                  key={m.id}
                  className={`flex flex-col ${m.isTeacher ? 'items-start' : 'items-end'}`}
                >
                  <div className="flex items-center gap-1.5 text-[11px] text-text3 mb-1 font-medium">
                    <span className="font-semibold text-text">{m.sender}</span>
                    <span>·</span>
                    <span>{m.role}</span>
                    <span>·</span>
                    <span>{m.time}</span>
                  </div>
                  <div
                    className={`max-w-[85%] rounded-2xl p-3 text-xs leading-relaxed ${
                      m.isTeacher
                        ? 'bg-bg3 text-text rounded-tl-xs border border-border/60'
                        : 'bg-accent text-white rounded-tr-xs shadow-xs'
                    }`}
                  >
                    {m.text}
                  </div>
                </div>
              ))}
            </div>

            {/* Composer */}
            <form
              onSubmit={handleSendMessage}
              className="p-3 sm:p-4 border-t border-border bg-bg3/30 flex items-center gap-2"
            >
              <input
                type="text"
                value={messageDraft}
                onChange={(e) => setMessageDraft(e.target.value)}
                placeholder="Type your message to faculty advisers..."
                className="flex-1 px-3.5 py-2 rounded-xl border border-border bg-bg text-xs text-text placeholder:text-text3 outline-none focus:border-accent focus:ring-2 focus:ring-accent/15 transition-all"
              />
              <button
                type="submit"
                disabled={!messageDraft.trim()}
                className="btn btn-primary btn-sm text-xs px-3.5 py-2 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed"
              >
                <RiSendPlaneFill className="size-3.5" />
                <span>Send</span>
              </button>
            </form>
            {messageSentSuccess && (
              <div className="px-4 py-1.5 bg-green/10 text-green text-[11px] font-semibold text-center border-t border-green/20 flex items-center justify-center gap-1">
                <RiCheckLine className="size-3.5" />
                <span>Message successfully delivered to adviser!</span>
              </div>
            )}
          </div>
        </div>
      )}

      {/* ============================================================ */}
      {/* ANNOUNCEMENTS TAB (Official Circulars & Notices)             */}
      {/* ============================================================ */}
      {activeTab === 'announcements' && (
        <div className="space-y-6">
          {/* Header */}
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-1">
            <div>
              <div className="flex items-center gap-2.5">
                <div className="p-2 rounded-xl bg-accent/10 text-accent">
                  <RiNotification3Fill className="size-6" />
                </div>
                <div>
                  <h1 className="font-serif text-2xl sm:text-3xl font-bold tracking-tight text-text">
                    Campus Announcements & Circulars
                  </h1>
                  <p className="text-xs sm:text-sm text-text2 mt-0.5">
                    Official DepEd notices, exam schedules, and academic bulletins for Grade 11 STEM Section A
                  </p>
                </div>
              </div>
            </div>

            <div className="flex items-center gap-2 self-start sm:self-auto">
              <button
                type="button"
                onClick={markAllAsRead}
                className="btn btn-secondary btn-sm text-xs cursor-pointer inline-flex items-center gap-1.5 shadow-xs"
              >
                <RiCheckDoubleLine className="size-4 text-accent" />
                <span>Mark all as read</span>
              </button>
            </div>
          </div>

          {/* Quick Metrics Bar */}
          <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div className="p-3.5 rounded-xl bg-bg2 border border-border flex items-center gap-3 shadow-xs">
              <div className="size-9 rounded-lg bg-accent/10 text-accent flex items-center justify-center font-bold text-sm">
                {announcements.length}
              </div>
              <div className="min-w-0">
                <div className="text-[11px] text-text3 font-medium">Total Notices</div>
                <div className="text-xs font-bold text-text">All Published</div>
              </div>
            </div>
            <div className="p-3.5 rounded-xl bg-bg2 border border-border flex items-center gap-3 shadow-xs">
              <div className="size-9 rounded-lg bg-red-500/10 text-red flex items-center justify-center font-bold text-sm">
                {announcements.filter((a) => a.type === 'important').length}
              </div>
              <div className="min-w-0">
                <div className="text-[11px] text-text3 font-medium">High Priority</div>
                <div className="text-xs font-bold text-red">Action Required</div>
              </div>
            </div>
            <div className="p-3.5 rounded-xl bg-bg2 border border-border flex items-center gap-3 shadow-xs">
              <div className="size-9 rounded-lg bg-blue-500/10 text-accent flex items-center justify-center font-bold text-sm">
                {announcements.filter((a) => !a.isRead).length}
              </div>
              <div className="min-w-0">
                <div className="text-[11px] text-text3 font-medium">Unread</div>
                <div className="text-xs font-bold text-accent">Pending Review</div>
              </div>
            </div>
            <div className="p-3.5 rounded-xl bg-bg2 border border-border flex items-center gap-3 shadow-xs">
              <div className="size-9 rounded-lg bg-amber-500/10 text-amber flex items-center justify-center font-bold text-sm">
                {announcements.filter((a) => a.bookmarked).length}
              </div>
              <div className="min-w-0">
                <div className="text-[11px] text-text3 font-medium">Bookmarked</div>
                <div className="text-xs font-bold text-amber">Saved Notices</div>
              </div>
            </div>
          </div>

          {/* Filter & Search Bar */}
          <div className="rounded-2xl bg-bg2 border border-border p-4 shadow-xs space-y-3.5">
            <div className="flex flex-col md:flex-row md:items-center justify-between gap-3">
              {/* Search input */}
              <div className="relative flex-1">
                <RiSearchLine size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-text3 pointer-events-none" />
                <input
                  type="text"
                  value={annTabSearch}
                  onChange={(e) => setAnnTabSearch(e.target.value)}
                  placeholder="Search announcements by title, keyword, or faculty name..."
                  className="w-full pl-9 pr-8 py-2 rounded-xl border border-border bg-bg3 text-xs text-text placeholder:text-text3 outline-none focus:border-accent focus:ring-2 focus:ring-accent/15 transition-all"
                />
                {annTabSearch && (
                  <button
                    type="button"
                    onClick={() => setAnnTabSearch('')}
                    className="absolute right-2.5 top-1/2 -translate-y-1/2 text-text3 hover:text-text p-0.5 cursor-pointer"
                  >
                    <RiCloseLine size={14} />
                  </button>
                )}
              </div>

              {/* Status filter: All / Unread / Bookmarked */}
              <div className="inline-flex items-center p-0.5 rounded-xl border border-border bg-bg3 shrink-0">
                {(['all', 'unread', 'bookmarked'] as const).map((status) => (
                  <button
                    key={status}
                    type="button"
                    onClick={() => setAnnFilterRead(status)}
                    className={`px-3 py-1 rounded-lg text-xs font-semibold capitalize transition-all cursor-pointer ${
                      annFilterRead === status
                        ? 'bg-bg2 text-accent shadow-xs'
                        : 'text-text3 hover:text-text'
                    }`}
                  >
                    {status}
                  </button>
                ))}
              </div>
            </div>

            {/* Category Combobox */}
            <div className="flex flex-wrap items-center justify-between gap-3 pt-2.5 border-t border-border/50">
              <div className="flex items-center gap-2.5">
                <label id="category-combobox-label" className="text-xs text-text3 font-medium">
                  Category:
                </label>
                <div className="relative">
                  <button
                    type="button"
                    id="category-combobox-button"
                    aria-haspopup="listbox"
                    aria-expanded={categoryDropdownOpen}
                    aria-labelledby="category-combobox-label category-combobox-button"
                    onClick={(e) => {
                      e.stopPropagation();
                      setCategoryDropdownOpen(!categoryDropdownOpen);
                    }}
                    className="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl border border-border bg-bg3/80 hover:bg-bg3 text-xs font-semibold text-text cursor-pointer transition-colors shadow-2xs hover:border-accent/40"
                  >
                    <span className={`size-2 rounded-full shrink-0 ${activeCategoryConfig.dotColor}`} />
                    <span>{activeCategoryConfig.label}</span>
                    <span className="text-[11px] font-normal text-text3 px-1.5 py-0.2 rounded bg-bg2 border border-border/60">
                      {getCategoryCount(annTabCategory)}
                    </span>
                    <RiArrowDownSLine
                      className={`size-3.5 text-text3 transition-transform duration-200 ${categoryDropdownOpen ? 'rotate-180 text-accent' : ''}`}
                      aria-hidden="true"
                    />
                  </button>

                  {categoryDropdownOpen && (
                    <div
                      role="listbox"
                      aria-labelledby="category-combobox-label"
                      onClick={(e) => e.stopPropagation()}
                      className="absolute left-0 mt-1.5 w-60 rounded-xl bg-bg2 border border-border shadow-lg p-1.5 z-30 space-y-0.5 animate-in fade-in zoom-in-95 duration-150"
                    >
                      {ANNOUNCEMENT_CATEGORIES.map((cat) => {
                        const isSelected = annTabCategory === cat.id;
                        const count = getCategoryCount(cat.id);
                        return (
                          <button
                            key={cat.id}
                            type="button"
                            role="option"
                            aria-selected={isSelected}
                            onClick={() => {
                              setAnnTabCategory(cat.id);
                              setCategoryDropdownOpen(false);
                            }}
                            className={`w-full text-left px-3 py-1.5 rounded-lg text-xs font-medium cursor-pointer flex items-center justify-between transition-colors ${
                              isSelected
                                ? 'bg-accent/10 text-accent font-semibold'
                                : 'text-text2 hover:text-text hover:bg-bg3'
                            }`}
                          >
                            <div className="flex items-center gap-2 min-w-0">
                              <span className={`size-2 rounded-full shrink-0 ${cat.dotColor}`} />
                              <span className="truncate">{cat.label}</span>
                            </div>
                            <div className="flex items-center gap-2 shrink-0 ml-2">
                              <span
                                className={`text-[10px] px-1.5 py-0.5 rounded ${
                                  isSelected ? 'bg-accent/20 text-accent font-semibold' : 'bg-bg3 text-text3'
                                }`}
                              >
                                {count}
                              </span>
                              {isSelected && <RiCheckLine className="size-3.5 text-accent shrink-0" />}
                            </div>
                          </button>
                        );
                      })}
                    </div>
                  )}
                </div>
              </div>

              {/* Notice counter & reset link */}
              <div className="flex items-center gap-2 text-xs text-text3">
                <span>
                  Showing <strong className="text-text font-semibold">{filteredAnnouncements.length}</strong> {filteredAnnouncements.length === 1 ? 'notice' : 'notices'}
                </span>
                {annTabCategory !== 'All' && (
                  <button
                    type="button"
                    onClick={() => setAnnTabCategory('All')}
                    className="text-[11px] text-accent hover:underline cursor-pointer ml-1"
                  >
                    Reset category
                  </button>
                )}
              </div>
            </div>
          </div>

          {/* Announcements Feed */}
          {filteredAnnouncements.length === 0 ? (
            <div className="p-12 text-center rounded-2xl bg-bg2 border border-dashed border-border space-y-3">
              <div className="size-12 rounded-full bg-bg3 mx-auto flex items-center justify-center text-text3">
                <RiNotification3Line size={24} />
              </div>
              <div>
                <h3 className="font-bold text-text text-base">No announcements found</h3>
                <p className="text-xs text-text3 mt-1">Try adjusting your search keywords or category filters.</p>
              </div>
              <button
                type="button"
                onClick={() => {
                  setAnnTabSearch('');
                  setAnnTabCategory('All');
                  setAnnFilterRead('all');
                }}
                className="btn btn-secondary btn-sm text-xs cursor-pointer"
              >
                Reset all filters
              </button>
            </div>
          ) : (
            <div className="space-y-4">
                {filteredAnnouncements.map((a) => (
                  <div
                    key={a.id}
                    className={`rounded-2xl bg-bg2 border p-5 shadow-xs transition-all hover:shadow-md space-y-3 relative ${
                      !a.isRead ? 'border-accent/40 bg-accent/[0.02]' : 'border-border'
                    }`}
                  >
                    {/* Header */}
                    <div className="flex flex-wrap items-center justify-between gap-2">
                      <div className="flex items-center gap-2">
                        <span className={`announce-tag ${a.type}`}>{a.type}</span>
                        <span
                          className="inline-flex items-center gap-1 text-[11px] font-semibold px-2.5 py-0.5 rounded-full"
                          style={{
                            background:
                              a.posterRole === 'admin'
                                ? 'rgba(239, 68, 68, 0.12)'
                                : 'rgba(139, 92, 246, 0.12)',
                            color:
                              a.posterRole === 'admin'
                                ? 'var(--red)'
                                : 'var(--purple)',
                          }}
                        >
                          {a.posterRole === 'admin' ? (
                            <RiShieldCheckLine size={12} />
                          ) : (
                            <RiUserStarLine size={12} />
                          )}
                          {a.posterRole === 'admin' ? 'Admin' : 'Teacher'}: {a.posterName}
                        </span>
                        <span className="text-[11px] font-semibold px-2 py-0.5 rounded-md bg-bg3 text-text2">
                          {a.category}
                        </span>
                      </div>

                      <div className="flex items-center gap-2 text-xs text-text3">
                        <span className="flex items-center gap-1">
                          <RiCalendarLine size={13} />
                          {a.date} ({a.timeLabel})
                        </span>
                        {!a.isRead && (
                          <span className="size-2 rounded-full bg-accent" title="Unread" />
                        )}
                      </div>
                    </div>

                    {/* Title */}
                    <h2 className="text-lg font-bold text-text hover:text-accent transition-colors">
                      {a.title}
                    </h2>

                    {/* Body Message */}
                    <p className="text-sm text-text2 leading-relaxed whitespace-pre-line">
                      {a.message}
                    </p>

                    {/* Footer bar */}
                    <div className="flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-border/50 text-xs">
                      <div className="text-text3 text-[11px] font-medium">
                        Target Strand: <span className="text-text font-semibold">{a.tag}</span>
                      </div>

                      <div className="flex items-center gap-2">
                        {/* Bookmark Button */}
                        <button
                          type="button"
                          onClick={() => toggleBookmark(a.id)}
                          className={`p-1.5 rounded-lg border transition-colors cursor-pointer ${
                            a.bookmarked
                              ? 'bg-amber-500/10 text-amber border-amber-500/30'
                              : 'bg-bg3 text-text3 hover:text-text border-border'
                          }`}
                          title={a.bookmarked ? 'Remove bookmark' : 'Bookmark announcement'}
                        >
                          {a.bookmarked ? <RiBookmarkFill size={15} /> : <RiBookmarkLine size={15} />}
                        </button>

                        {/* Read status toggle */}
                        <button
                          type="button"
                          onClick={() => toggleReadStatus(a.id)}
                          className="px-2.5 py-1 rounded-lg border border-border bg-bg3 hover:bg-bg4 text-text2 hover:text-text text-xs font-medium cursor-pointer"
                        >
                          {a.isRead ? 'Mark as unread' : 'Mark as read'}
                        </button>

                        {/* Quick destination button */}
                        {(a.category === 'Classes' || a.category === 'Virtual Classes') && (
                          <button
                            type="button"
                            onClick={() => onTabChange('live')}
                            className="btn btn-primary btn-sm text-xs cursor-pointer inline-flex items-center gap-1"
                          >
                            <span>Open Classes</span>
                            <RiArrowRightLine size={13} />
                          </button>
                        )}
                        {a.category === 'Academic' && (
                          <button
                            type="button"
                            onClick={() => onTabChange('courses')}
                            className="btn btn-secondary btn-sm text-xs cursor-pointer inline-flex items-center gap-1"
                          >
                            <span>View Subjects</span>
                            <RiArrowRightLine size={13} />
                          </button>
                        )}
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            )}
        </div>
      )}

      {/* ============================================================ */}
      {/* 2. COURSE SECTION OUTLINE & EXPERIENCE                       */}
      {/* ============================================================ */}
      {activeTab === 'courses' && (
        <CourseSectionView
          courseData={getCourseData(selectedCourseId)}
          activeSectionId={activeCourseSectionId}
          onBackToCourses={() => onTabChange('dashboard')}
          onNavigateToLive={() => onTabChange('live')}
          onNavigateToTasks={() => onTabChange('tasks')}
        />
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
        <StudentPerformanceTasksView
          tasks={tasks}
          onTasksChange={setTasks}
          onSubmitTask={(task) => setSubmitTaskModal(task)}
          onTabChange={onTabChange}
        />
      )}

      {/* ============================================================ */}
      {/* 5. CLASSES & SCHEDULE (students/student_classes.php)          */}
      {/* ============================================================ */}
      {activeTab === 'live' && (
        <StudentClassesView
          userRole="student"
          onTabChange={onTabChange}
          onSelectCourse={handleOpenCourse}
        />
      )}

      {/* ============================================================ */}
      {/* 6. GRADES & CERTIFICATES (students/student_grades.php)        */}
      {/* ============================================================ */}
      {activeTab === 'grades' && (
        <StudentGradesView onTabChange={onTabChange} />
      )}

      {/* ============================================================ */}
      {/* 7. STUDENT PROFILE (students/student_profile.php)             */}
      {/* ============================================================ */}
      {activeTab === 'profile' && (
        <StudentProfileView onTabChange={onTabChange} />
      )}

      {/* ============================================================ */}
      {/* 8. SETTINGS (students/settings.php)                          */}
      {/* ============================================================ */}
      {activeTab === 'settings' && (
        <StudentSettingsView onTabChange={onTabChange} />
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
