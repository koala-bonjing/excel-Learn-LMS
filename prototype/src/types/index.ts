export type Role = 'login' | 'student' | 'teacher' | 'admin';

export interface UserProfile {
  id: string;
  name: string;
  role: 'student' | 'teacher' | 'admin';
  roleLabel: string;
  subLabel: string;
  avatarUrl?: string;
  initials: string;
}

export interface StudentTask {
  id: string;
  title: string;
  subject: string;
  category: 'Quiz' | 'Performance Task' | 'Written Work' | 'Study Task';
  dueDate: string;
  dueTime: string;
  points: number;
  status: 'todo' | 'in-progress' | 'submitted';
  urgency: 'high' | 'normal' | 'done';
  grade?: number;
  feedback?: string;
  priorityRank?: number;
  priorityLabel?: string;
  kanbanColumn?: 'urgent' | 'high' | 'normal' | 'submitted';
  deliverableType?: string;
  instructor?: string;
  subjectCode?: string;
  description?: string;
  rubricPreview?: string;
  allowedFormats?: string;
  hoursLeft?: number;
  draftNote?: string;
  submissionAttachment?: string;
}

export interface SubjectModule {
  id: string;
  code: string;
  title: string;
  teacher: string;
  schedule: string;
  currentModule: string;
  progressPercent: number;
  totalModules: number;
  color: string;
  hasLive?: boolean;
}

export interface FacultyApproval {
  id: string;
  name: string;
  email: string;
  department: string;
  strand: string;
  prcLicense: string;
  submittedAt: string;
  status: 'pending' | 'approved' | 'rejected';
}

export interface StudentSubmission {
  id: string;
  studentName: string;
  studentId: string;
  taskTitle: string;
  subject: string;
  submittedTime: string;
  fileAttachment: string;
  fileSize: string;
  status: 'pending' | 'graded';
  score?: number;
  maxScore: number;
}

export interface SHSStrand {
  code: string;
  name: string;
  sectionsCount: number;
  enrolled: number;
  capacity: number;
  color: string;
}

export interface StudentDirectoryItem {
  id: string;
  lrn: string;
  studentId: string;
  name: string;
  strand: string;
  gradeLevel: string;
  section: string;
  status: 'Active' | 'Pending Requirements' | 'Transferred';
  gwa: number;
}

export interface TeacherResourceLink {
  id: string;
  title: string;
  url: string;
  type: 'slides' | 'document' | 'video' | 'link' | 'code' | 'worksheet';
  description?: string;
  addedByTeacher?: boolean;
}

export interface ClassSession {
  id: string;
  subjectCode: string;
  subjectName: string;
  courseId?: string;
  teacher: {
    name: string;
    role: string;
    department: string;
    initials: string;
    avatarUrl?: string;
  };
  scheduleDays: number[]; // 0 = Sunday, 1 = Monday, 2 = Tuesday, etc.
  scheduleDaysText: string;
  startTime: string;
  endTime: string;
  timeRange: string;
  durationMinutes: number;
  deliveryMode: 'virtual' | 'physical';
  room: string;
  strand: string;
  semester: string;
  topic: string;
  description: string;
  color: 'cyan' | 'accent' | 'green' | 'amber' | 'purple' | 'teal';
  virtualDetails?: {
    meetingUrl: string;
    platform: string;
    meetingId: string;
    passcode: string;
    serverRegion: string;
    videoQuality: string;
    attendanceRequirement: string;
    isLiveNow?: boolean;
    activeLearnersCount?: number;
    recordingArchived: boolean;
  };
  additionalLinks: TeacherResourceLink[];
  teacherNotes?: string;
}

