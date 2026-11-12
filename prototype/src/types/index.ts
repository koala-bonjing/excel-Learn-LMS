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
  category: 'Quiz' | 'Performance Task' | 'Written Work';
  dueDate: string;
  dueTime: string;
  points: number;
  status: 'todo' | 'in-progress' | 'submitted';
  urgency: 'high' | 'normal' | 'done';
  grade?: number;
  feedback?: string;
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
