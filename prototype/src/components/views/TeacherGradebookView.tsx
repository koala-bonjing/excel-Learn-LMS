import React, { useCallback, useEffect, useId, useMemo, useRef, useState } from 'react';
import { HoverCombobox, type HoverComboboxOption } from '@/components/base/combobox';
import { Dialog as PopoverDialog, Popover } from '@/components/base/popover';
import { cx } from '@/utils/cx';
import { useDismissOnOutsidePress } from '@/utils/use-dismiss-on-outside-press';
import {
  RiArrowUpLine,
  RiBookOpenLine,
  RiCloseLine,
  RiDownloadLine,
  RiErrorWarningLine,
  RiGroupLine,
  RiMoreFill,
  RiPrinterLine,
  RiSearchLine,
} from '@remixicon/react';

/* ============================================================================
 * 1. SQL-Aligned TypeScript Interfaces (u342202101_excelearn.sql)
 * ============================================================================ */

export type StudentStatus = 'active' | 'inactive';

/** Maps 1:1 to `tbl_teacher_subjects` JOIN `tblsubjects` JOIN `tblstrands` */
export interface GradebookSubjectOption {
  teacher_subject_id: number;
  subject_id: number;
  subject: string;
  strand: string;
  track: string;
}

/** Maps 1:1 to `tblsections` JOIN `tblgrade_levels` */
export interface GradebookSectionOption {
  section_id: number;
  grade_level_id: number;
  grade_level: string;
  section: string;
}

/** Maps 1:1 to `tbl_teacher_lessons` */
export interface TeacherLessonRecord {
  lesson_id: number;
  teacher_subject_id: number;
  title: string;
  status: 'published' | 'draft';
  order_index: number;
}

/** Maps 1:1 to `tblquizzes` */
export interface QuizDefinition {
  quiz_id: number;
  lesson_id: number;
  description: string;
  question_count: number;
  passing_score: number;
  max_score: number;
  grading_mode: 'auto' | 'manual';
}

/** Maps 1:1 to `tblquiz_results` */
export interface QuizResultRecord {
  id: number;
  student_id: number;
  quizzes_id: number;
  score: string;
  manual_score: number | null;
  time_used: number;
  updated_at: string;
}

/** Maps 1:1 to `tblperformance_tasks` */
export interface PerformanceTaskDefinition {
  task_id: number;
  teacher_subject_id: number;
  title: string;
  max_score: number;
  due_date: string | null;
  status: 'draft' | 'published';
}

/** Maps 1:1 to `tblperformance_task_submissions` */
export interface PerformanceTaskSubmissionRecord {
  submission_id: number;
  task_id: number;
  student_id: number;
  score: number | null;
  feedback: string;
  status: 'submitted' | 'graded';
  submitted_at: string;
  graded_at: string | null;
}

/** Maps 1:1 to `tblstudents` + `tbl_teachers_students` (`teacher_subject_ids`) */
export interface GradebookStudentRecord {
  id: number;
  student_id: string;
  section_id: number;
  firstname: string;
  middlename: string;
  lastname: string;
  suffix: string;
  lrn: string;
  status: StudentStatus;
  teacher_subject_ids: number[];
}

/* ============================================================================
 * 2. SQL-Aligned Seed Data
 * ============================================================================ */

const TEACHER_SUBJECTS: GradebookSubjectOption[] = [
  {
    teacher_subject_id: 124,
    subject_id: 57,
    subject: 'Basic Electrical Installation',
    strand: 'EIM',
    track: 'TVL',
  },
  {
    teacher_subject_id: 125,
    subject_id: 76,
    subject: 'BPO Simulation / Practicum',
    strand: 'CCS',
    track: 'TVL',
  },
  {
    teacher_subject_id: 126,
    subject_id: 66,
    subject: 'Broadband Installation',
    strand: 'CSS',
    track: 'TVL',
  },
];

const SECTIONS: GradebookSectionOption[] = [
  { section_id: 15, grade_level_id: 1, grade_level: 'Grade 11', section: 'A' },
  { section_id: 17, grade_level_id: 1, grade_level: 'Grade 11', section: 'B' },
  { section_id: 12, grade_level_id: 1, grade_level: 'Grade 11', section: 'C' },
  { section_id: 16, grade_level_id: 2, grade_level: 'Grade 12', section: 'B' },
];

const TEACHER_LESSONS: TeacherLessonRecord[] = [
  { lesson_id: 88, teacher_subject_id: 124, title: 'Electrical Safety Practices', status: 'published', order_index: 1 },
  { lesson_id: 90, teacher_subject_id: 124, title: 'Wiring Fundamentals', status: 'published', order_index: 2 },
  { lesson_id: 94, teacher_subject_id: 125, title: 'Customer Call Etiquette', status: 'published', order_index: 1 },
  { lesson_id: 95, teacher_subject_id: 125, title: 'CRM & Ticket Documentation', status: 'published', order_index: 2 },
  { lesson_id: 96, teacher_subject_id: 126, title: 'Fiber Optic Fundamentals', status: 'published', order_index: 1 },
  { lesson_id: 97, teacher_subject_id: 126, title: 'Router & ONT Provisioning', status: 'published', order_index: 2 },
];

const QUIZZES: QuizDefinition[] = [
  { quiz_id: 32, lesson_id: 88, description: 'PPE & Hazard Prevention Check', question_count: 2, passing_score: 75, max_score: 2, grading_mode: 'auto' },
  { quiz_id: 33, lesson_id: 88, description: 'Tools & Multimeter Identification', question_count: 10, passing_score: 75, max_score: 10, grading_mode: 'auto' },
  { quiz_id: 35, lesson_id: 90, description: 'Residential Circuit Safety Essay', question_count: 3, passing_score: 75, max_score: 20, grading_mode: 'manual' },
  { quiz_id: 40, lesson_id: 94, description: 'Active Listening & Tone Check', question_count: 10, passing_score: 75, max_score: 10, grading_mode: 'auto' },
  { quiz_id: 41, lesson_id: 94, description: 'Escalation Handling Scenarios', question_count: 4, passing_score: 75, max_score: 20, grading_mode: 'manual' },
  { quiz_id: 42, lesson_id: 95, description: 'Ticket Triage & SLA Quiz', question_count: 10, passing_score: 75, max_score: 10, grading_mode: 'auto' },
  { quiz_id: 43, lesson_id: 96, description: 'Cable Termination & Splicing', question_count: 10, passing_score: 75, max_score: 10, grading_mode: 'auto' },
  { quiz_id: 44, lesson_id: 96, description: 'Signal Attenuation Analysis', question_count: 4, passing_score: 75, max_score: 20, grading_mode: 'manual' },
  { quiz_id: 45, lesson_id: 97, description: 'Subnetting & Gateway Check', question_count: 10, passing_score: 75, max_score: 10, grading_mode: 'auto' },
];

const PERFORMANCE_TASKS: PerformanceTaskDefinition[] = [
  { task_id: 1, teacher_subject_id: 124, title: 'Basic Electrical Wiring Installation Activity', max_score: 100, due_date: '2026-07-10 17:00:00', status: 'published' },
  { task_id: 2, teacher_subject_id: 124, title: 'Circuit Breaker & Load Testing Lab', max_score: 100, due_date: '2026-07-24 17:00:00', status: 'published' },
  { task_id: 3, teacher_subject_id: 125, title: 'Mock Inbound Support Call Recording', max_score: 100, due_date: '2026-07-12 17:00:00', status: 'published' },
  { task_id: 4, teacher_subject_id: 125, title: 'CRM Case Resolution Logbook', max_score: 100, due_date: '2026-07-26 17:00:00', status: 'published' },
  { task_id: 5, teacher_subject_id: 126, title: 'UTP & Patch Panel Crimping Demo', max_score: 100, due_date: '2026-07-14 17:00:00', status: 'published' },
  { task_id: 6, teacher_subject_id: 126, title: 'Home Wi-Fi Mesh Diagnostic Report', max_score: 100, due_date: '2026-07-28 17:00:00', status: 'published' },
];

const GRADEBOOK_STUDENTS: GradebookStudentRecord[] = [
  {
    id: 70,
    student_id: 'STUD-123456',
    section_id: 12,
    firstname: 'Bryan',
    middlename: 'Fulo',
    lastname: 'Donceras',
    suffix: 'Jr.',
    lrn: '464437547543',
    status: 'active',
    teacher_subject_ids: [124, 125, 126],
  },
  {
    id: 72,
    student_id: 'STUD-2026-12345',
    section_id: 16,
    firstname: 'Janloyd',
    middlename: 'Benas',
    lastname: 'Lotino',
    suffix: '',
    lrn: '123456789101',
    status: 'active',
    teacher_subject_ids: [124, 125, 126],
  },
  {
    id: 75,
    student_id: 'STUD-2026-00412',
    section_id: 15,
    firstname: 'Maria Clara',
    middlename: 'Reyes',
    lastname: 'Santos',
    suffix: '',
    lrn: '109823450112',
    status: 'active',
    teacher_subject_ids: [124, 126],
  },
  {
    id: 76,
    student_id: 'STUD-2026-00418',
    section_id: 12,
    firstname: 'Gabriel Angelo',
    middlename: 'Velasco',
    lastname: 'Cruz',
    suffix: '',
    lrn: '109823450209',
    status: 'active',
    teacher_subject_ids: [124],
  },
  {
    id: 77,
    student_id: 'STUD-2026-00421',
    section_id: 15,
    firstname: 'Sofia',
    middlename: 'Diaz',
    lastname: 'Andres',
    suffix: '',
    lrn: '109823450317',
    status: 'active',
    teacher_subject_ids: [126],
  },
  {
    id: 78,
    student_id: 'STUD-2026-00429',
    section_id: 17,
    firstname: 'Rafael',
    middlename: 'Mendoza',
    lastname: 'Villanueva',
    suffix: '',
    lrn: '109823450445',
    status: 'active',
    teacher_subject_ids: [124, 126],
  },
  {
    id: 79,
    student_id: 'STUD-2026-00433',
    section_id: 16,
    firstname: 'Angela',
    middlename: 'Luna',
    lastname: 'Bautista',
    suffix: '',
    lrn: '109823450512',
    status: 'active',
    teacher_subject_ids: [125],
  },
  {
    id: 80,
    student_id: 'STUD-2026-00440',
    section_id: 17,
    firstname: 'Kenji',
    middlename: '',
    lastname: 'Navarro',
    suffix: '',
    lrn: '109823450608',
    status: 'inactive',
    teacher_subject_ids: [126],
  },
];

const INITIAL_QUIZ_RESULTS: QuizResultRecord[] = [
  // Bryan (70) - subjects 124, 125, 126
  { id: 101, student_id: 70, quizzes_id: 32, score: '2', manual_score: null, time_used: 12, updated_at: '2026-06-21 09:10:18' },
  { id: 102, student_id: 70, quizzes_id: 33, score: '9', manual_score: null, time_used: 18, updated_at: '2026-06-21 09:50:57' },
  { id: 103, student_id: 70, quizzes_id: 35, score: '15', manual_score: 18, time_used: 22, updated_at: '2026-07-05 10:53:18' },
  { id: 104, student_id: 70, quizzes_id: 40, score: '9', manual_score: null, time_used: 14, updated_at: '2026-07-06 08:40:00' },
  { id: 105, student_id: 70, quizzes_id: 41, score: '17', manual_score: 18.5, time_used: 20, updated_at: '2026-07-07 10:12:00' },
  { id: 106, student_id: 70, quizzes_id: 42, score: '9.5', manual_score: null, time_used: 15, updated_at: '2026-07-08 09:30:00' },
  { id: 107, student_id: 70, quizzes_id: 43, score: '9', manual_score: null, time_used: 13, updated_at: '2026-07-09 11:05:00' },
  { id: 108, student_id: 70, quizzes_id: 44, score: '18', manual_score: 18, time_used: 24, updated_at: '2026-07-10 14:20:00' },
  { id: 109, student_id: 70, quizzes_id: 45, score: '9', manual_score: null, time_used: 16, updated_at: '2026-07-11 10:00:00' },

  // Janloyd (72) - subjects 124, 125, 126
  { id: 110, student_id: 72, quizzes_id: 32, score: '2', manual_score: null, time_used: 15, updated_at: '2026-06-22 10:00:00' },
  { id: 111, student_id: 72, quizzes_id: 33, score: '8', manual_score: null, time_used: 20, updated_at: '2026-06-22 10:30:00' },
  { id: 112, student_id: 72, quizzes_id: 35, score: '16', manual_score: 16.5, time_used: 25, updated_at: '2026-07-05 11:15:00' },
  { id: 113, student_id: 72, quizzes_id: 40, score: '8.5', manual_score: null, time_used: 18, updated_at: '2026-07-06 09:00:00' },
  { id: 114, student_id: 72, quizzes_id: 41, score: '16', manual_score: 17, time_used: 21, updated_at: '2026-07-07 11:20:00' },
  { id: 115, student_id: 72, quizzes_id: 42, score: '8.5', manual_score: null, time_used: 17, updated_at: '2026-07-08 10:15:00' },
  { id: 116, student_id: 72, quizzes_id: 43, score: '8', manual_score: null, time_used: 19, updated_at: '2026-07-09 13:10:00' },
  { id: 117, student_id: 72, quizzes_id: 44, score: '17', manual_score: 17, time_used: 22, updated_at: '2026-07-10 15:00:00' },
  { id: 118, student_id: 72, quizzes_id: 45, score: '8.5', manual_score: null, time_used: 16, updated_at: '2026-07-11 11:05:00' },

  // Maria Clara (75) - subjects 124, 126
  { id: 119, student_id: 75, quizzes_id: 32, score: '2', manual_score: null, time_used: 9, updated_at: '2026-06-21 11:00:00' },
  { id: 120, student_id: 75, quizzes_id: 33, score: '9.5', manual_score: null, time_used: 14, updated_at: '2026-06-21 11:25:00' },
  { id: 121, student_id: 75, quizzes_id: 35, score: '18', manual_score: 19, time_used: 21, updated_at: '2026-07-05 09:40:00' },
  { id: 122, student_id: 75, quizzes_id: 43, score: '9.5', manual_score: null, time_used: 12, updated_at: '2026-07-09 09:10:00' },
  { id: 123, student_id: 75, quizzes_id: 44, score: '19', manual_score: 19, time_used: 19, updated_at: '2026-07-10 10:30:00' },
  { id: 124, student_id: 75, quizzes_id: 45, score: '9.5', manual_score: null, time_used: 13, updated_at: '2026-07-11 08:45:00' },

  // Gabriel Angelo (76) - subject 124 (At Risk)
  { id: 125, student_id: 76, quizzes_id: 32, score: '1', manual_score: null, time_used: 18, updated_at: '2026-06-23 14:10:00' },
  { id: 126, student_id: 76, quizzes_id: 33, score: '7', manual_score: null, time_used: 24, updated_at: '2026-06-24 15:20:00' },
  { id: 127, student_id: 76, quizzes_id: 35, score: '14', manual_score: 14.5, time_used: 27, updated_at: '2026-07-06 16:00:00' },

  // Sofia (77) - subject 126
  { id: 128, student_id: 77, quizzes_id: 43, score: '9', manual_score: null, time_used: 14, updated_at: '2026-07-09 08:30:00' },
  { id: 129, student_id: 77, quizzes_id: 44, score: '17', manual_score: 18, time_used: 21, updated_at: '2026-07-10 09:15:00' },
  { id: 130, student_id: 77, quizzes_id: 45, score: '9', manual_score: null, time_used: 15, updated_at: '2026-07-11 09:45:00' },

  // Rafael (78) - subjects 124, 126 (At Risk + Incomplete)
  { id: 131, student_id: 78, quizzes_id: 32, score: '1.5', manual_score: null, time_used: 16, updated_at: '2026-06-24 11:00:00' },
  { id: 132, student_id: 78, quizzes_id: 33, score: '7', manual_score: null, time_used: 22, updated_at: '2026-06-25 13:10:00' },
  { id: 133, student_id: 78, quizzes_id: 43, score: '7', manual_score: null, time_used: 20, updated_at: '2026-07-09 14:40:00' },
  { id: 134, student_id: 78, quizzes_id: 44, score: '13', manual_score: 13.5, time_used: 26, updated_at: '2026-07-10 16:10:00' },

  // Angela (79) - subject 125
  { id: 135, student_id: 79, quizzes_id: 40, score: '9', manual_score: null, time_used: 13, updated_at: '2026-07-06 10:05:00' },
  { id: 136, student_id: 79, quizzes_id: 41, score: '17.5', manual_score: 17.5, time_used: 19, updated_at: '2026-07-07 13:45:00' },
  { id: 137, student_id: 79, quizzes_id: 42, score: '9', manual_score: null, time_used: 14, updated_at: '2026-07-08 11:20:00' },

  // Kenji (80) - subject 126 (inactive, preserved records)
  { id: 138, student_id: 80, quizzes_id: 43, score: '8', manual_score: null, time_used: 17, updated_at: '2026-07-09 10:15:00' },
  { id: 139, student_id: 80, quizzes_id: 44, score: '15', manual_score: 15.25, time_used: 25, updated_at: '2026-07-10 11:40:00' },
];

const INITIAL_PT_SUBMISSIONS: PerformanceTaskSubmissionRecord[] = [
  // Bryan (70)
  { submission_id: 1, task_id: 1, student_id: 70, score: 100, feedback: 'Clean wiring layout and proper PPE check.', status: 'graded', submitted_at: '2026-06-21 14:47:10', graded_at: '2026-06-21 14:48:28' },
  { submission_id: 2, task_id: 2, student_id: 70, score: 96, feedback: 'Accurate multimeter readings.', status: 'graded', submitted_at: '2026-07-20 10:15:00', graded_at: '2026-07-21 09:00:00' },
  { submission_id: 3, task_id: 3, student_id: 70, score: 94, feedback: 'Clear tone and empathetic pacing.', status: 'graded', submitted_at: '2026-07-11 16:00:00', graded_at: '2026-07-12 08:30:00' },
  { submission_id: 4, task_id: 4, student_id: 70, score: 95, feedback: 'Complete ticket tags and resolution notes.', status: 'graded', submitted_at: '2026-07-22 15:10:00', graded_at: '2026-07-23 11:00:00' },
  { submission_id: 5, task_id: 5, student_id: 70, score: 97, feedback: 'T568B pinouts verified cleanly.', status: 'graded', submitted_at: '2026-07-13 13:20:00', graded_at: '2026-07-14 09:15:00' },
  { submission_id: 6, task_id: 6, student_id: 70, score: 96, feedback: 'Thorough signal heatmap documentation.', status: 'graded', submitted_at: '2026-07-24 11:45:00', graded_at: '2026-07-25 10:05:00' },

  // Janloyd (72)
  { submission_id: 7, task_id: 1, student_id: 72, score: 88, feedback: 'Working circuit; tighten terminal screw.', status: 'graded', submitted_at: '2026-06-22 15:00:00', graded_at: '2026-06-23 09:00:00' },
  { submission_id: 8, task_id: 2, student_id: 72, score: 87.5, feedback: 'Good load calculation sheet.', status: 'graded', submitted_at: '2026-07-21 14:20:00', graded_at: '2026-07-22 10:15:00' },
  { submission_id: 9, task_id: 3, student_id: 72, score: 89, feedback: 'Strong call opening.', status: 'graded', submitted_at: '2026-07-12 09:40:00', graded_at: '2026-07-13 08:45:00' },
  { submission_id: 10, task_id: 4, student_id: 72, score: 88, feedback: 'Accurate follow-up notes.', status: 'graded', submitted_at: '2026-07-23 16:00:00', graded_at: '2026-07-24 09:30:00' },
  { submission_id: 11, task_id: 5, student_id: 72, score: 86, feedback: 'Minor jacket trim length adjustment needed.', status: 'graded', submitted_at: '2026-07-14 10:30:00', graded_at: '2026-07-15 08:20:00' },
  { submission_id: 12, task_id: 6, student_id: 72, score: 89.5, feedback: 'Well-structured diagnostic summary.', status: 'graded', submitted_at: '2026-07-25 12:00:00', graded_at: '2026-07-26 11:10:00' },

  // Maria Clara (75)
  { submission_id: 13, task_id: 1, student_id: 75, score: 96, feedback: 'Exemplary safety checklist and neat bends.', status: 'graded', submitted_at: '2026-06-21 16:00:00', graded_at: '2026-06-22 08:15:00' },
  { submission_id: 14, task_id: 2, student_id: 75, score: 97, feedback: 'Precise breaker trip test log.', status: 'graded', submitted_at: '2026-07-20 13:00:00', graded_at: '2026-07-21 09:30:00' },
  { submission_id: 15, task_id: 5, student_id: 75, score: 95, feedback: 'Zero packet loss on patch test.', status: 'graded', submitted_at: '2026-07-13 15:40:00', graded_at: '2026-07-14 10:00:00' },
  { submission_id: 16, task_id: 6, student_id: 75, score: 96, feedback: 'Clear placement recommendations.', status: 'graded', submitted_at: '2026-07-24 14:20:00', graded_at: '2026-07-25 09:10:00' },

  // Gabriel Angelo (76) - 0/2 submitted (Missing)

  // Sofia (77)
  { submission_id: 17, task_id: 5, student_id: 77, score: 94, feedback: 'Clean termination and labeling.', status: 'graded', submitted_at: '2026-07-13 17:10:00', graded_at: '2026-07-14 11:00:00' },
  { submission_id: 18, task_id: 6, student_id: 77, score: 94, feedback: 'Accurate RSSI readings across rooms.', status: 'graded', submitted_at: '2026-07-24 16:30:00', graded_at: '2026-07-25 10:25:00' },

  // Rafael (78) - 1 graded, 1 pending, 2 missing
  { submission_id: 19, task_id: 1, student_id: 78, score: 70, feedback: 'Switch polarity reversed on initial test.', status: 'graded', submitted_at: '2026-06-25 11:20:00', graded_at: '2026-06-26 09:10:00' },
  { submission_id: 20, task_id: 5, student_id: 78, score: null, feedback: '', status: 'submitted', submitted_at: '2026-07-15 18:05:00', graded_at: null },

  // Angela (79)
  { submission_id: 21, task_id: 3, student_id: 79, score: 92, feedback: 'Natural rapport and clear verification.', status: 'graded', submitted_at: '2026-07-12 11:00:00', graded_at: '2026-07-13 09:40:00' },
  { submission_id: 22, task_id: 4, student_id: 79, score: 92, feedback: 'Consistent escalation tagging.', status: 'graded', submitted_at: '2026-07-23 14:15:00', graded_at: '2026-07-24 10:20:00' },

  // Kenji (80) - inactive
  { submission_id: 23, task_id: 5, student_id: 80, score: 80, feedback: 'Completed prior to leave.', status: 'graded', submitted_at: '2026-07-14 09:30:00', graded_at: '2026-07-15 09:00:00' },
];

/* ============================================================================
 * 3. Pure Formatters, Grade Computations & Helpers
 * ============================================================================ */

type StandingFilter = 'all' | 'passing' | 'at_risk' | 'incomplete';
type SortColumn = 'student' | 'quizzes' | 'tasks' | 'weighted';
type SortDirection = 'ascending' | 'descending';
type DrawerTab = 'breakdown' | 'adjust';
type StandingLabel = 'Honors' | 'Passing' | 'At Risk' | 'Inactive';

const SUBJECT_BY_ID = new Map(TEACHER_SUBJECTS.map((s) => [s.teacher_subject_id, s]));
const SECTION_BY_ID = new Map(SECTIONS.map((s) => [s.section_id, s]));
const COLLATOR = new Intl.Collator(undefined, { numeric: true, sensitivity: 'base' });

function formatRosterName(
  s: Pick<GradebookStudentRecord, 'firstname' | 'lastname' | 'suffix'>,
): string {
  return `${s.lastname}, ${s.firstname}${s.suffix ? ` ${s.suffix}` : ''}`;
}

function getInitials(s: Pick<GradebookStudentRecord, 'firstname' | 'lastname'>): string {
  return `${s.firstname.charAt(0)}${s.lastname.charAt(0)}`.toUpperCase();
}

function formatSectionLabel(sectionId: number): string {
  const sec = SECTION_BY_ID.get(sectionId);
  return sec ? `${sec.grade_level} – ${sec.section}` : 'Unassigned';
}

function formatSqlTimestamp(now: Date): string {
  const pad = (n: number) => String(n).padStart(2, '0');
  return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())} ${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
}

function formatPercent(value: number | null, decimals = 1): string {
  return value === null ? '—' : `${value.toFixed(decimals)}%`;
}

function formatScoreNumber(value: number): string {
  return Number.isInteger(value) ? String(value) : value.toFixed(2).replace(/\.?0+$/, '');
}

function getLetterGrade(weightedAvg: number | null): string {
  if (weightedAvg === null) return 'No Grade';
  if (weightedAvg >= 95) return 'A+';
  if (weightedAvg >= 90) return 'A';
  if (weightedAvg >= 85) return 'B+';
  if (weightedAvg >= 80) return 'B';
  if (weightedAvg >= 75) return 'C+';
  return 'Needs Support';
}

function escapeCsvCell(value: string | number): string {
  const raw = String(value);
  const safe = /^[=+\-@\t\r]/.test(raw) ? `'${raw}` : raw;
  return /[",\r\n]/.test(safe) ? `"${safe.replace(/"/g, '""')}"` : safe;
}

function triggerCsvDownload(filename: string, rows: Array<Array<string | number>>) {
  const csvContent = '\uFEFF' + rows.map((row) => row.map(escapeCsvCell).join(',')).join('\r\n');
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  link.remove();
  setTimeout(() => URL.revokeObjectURL(url), 1000);
}

interface QuizBreakdownItem {
  quiz: QuizDefinition;
  lesson: TeacherLessonRecord;
  subject: GradebookSubjectOption | undefined;
  result: QuizResultRecord | undefined;
  effectiveScore: number | null;
  percent: number | null;
}

interface TaskBreakdownItem {
  task: PerformanceTaskDefinition;
  subject: GradebookSubjectOption | undefined;
  submission: PerformanceTaskSubmissionRecord | undefined;
  percent: number | null;
}

interface StudentGradeMetrics {
  quizItems: QuizBreakdownItem[];
  taskItems: TaskBreakdownItem[];
  quizzesTaken: number;
  quizzesTotal: number;
  quizAvg: number | null;
  ptSubmitted: number;
  ptGraded: number;
  ptTotal: number;
  ptAvg: number | null;
  weightedAvg: number | null;
  formulaLabel: string;
  isIncomplete: boolean;
  standing: StandingLabel;
}

function computeStudentGradeMetrics(
  student: GradebookStudentRecord,
  subjectFilter: string,
  quizResults: QuizResultRecord[],
  ptSubmissions: PerformanceTaskSubmissionRecord[],
): StudentGradeMetrics {
  const scopedSubjectIds =
    subjectFilter === 'all'
      ? student.teacher_subject_ids
      : student.teacher_subject_ids.includes(Number(subjectFilter))
        ? [Number(subjectFilter)]
        : [];

  const scopedLessons = TEACHER_LESSONS.filter((l) =>
    scopedSubjectIds.includes(l.teacher_subject_id),
  );
  const lessonMap = new Map(scopedLessons.map((l) => [l.lesson_id, l]));

  const quizItems: QuizBreakdownItem[] = QUIZZES.filter((q) => lessonMap.has(q.lesson_id)).map(
    (quiz) => {
      const lesson = lessonMap.get(quiz.lesson_id)!;
      const result = quizResults.find(
        (r) => r.student_id === student.id && r.quizzes_id === quiz.quiz_id,
      );
      const raw = result ? (result.manual_score ?? Number.parseFloat(result.score)) : null;
      const effectiveScore = raw !== null && Number.isFinite(raw) ? raw : null;
      const percent =
        effectiveScore !== null && quiz.max_score > 0
          ? (effectiveScore / quiz.max_score) * 100
          : null;
      return {
        quiz,
        lesson,
        subject: SUBJECT_BY_ID.get(lesson.teacher_subject_id),
        result,
        effectiveScore,
        percent,
      };
    },
  );

  const taskItems: TaskBreakdownItem[] = PERFORMANCE_TASKS.filter((t) =>
    scopedSubjectIds.includes(t.teacher_subject_id),
  ).map((task) => {
    const submission = ptSubmissions.find(
      (sub) => sub.student_id === student.id && sub.task_id === task.task_id,
    );
    const percent =
      submission && submission.status === 'graded' && submission.score !== null && task.max_score > 0
        ? (submission.score / task.max_score) * 100
        : null;
    return {
      task,
      subject: SUBJECT_BY_ID.get(task.teacher_subject_id),
      submission,
      percent,
    };
  });

  const gradedQuizPercents = quizItems
    .map((item) => item.percent)
    .filter((p): p is number => p !== null);
  const quizzesTaken = gradedQuizPercents.length;
  const quizzesTotal = quizItems.length;
  const quizAvg =
    quizzesTaken > 0
      ? gradedQuizPercents.reduce((sum, p) => sum + p, 0) / quizzesTaken
      : null;

  const ptSubmitted = taskItems.filter((item) => item.submission !== undefined).length;
  const gradedPtPercents = taskItems
    .map((item) => item.percent)
    .filter((p): p is number => p !== null);
  const ptGraded = gradedPtPercents.length;
  const ptTotal = taskItems.length;
  const ptAvg =
    ptGraded > 0 ? gradedPtPercents.reduce((sum, p) => sum + p, 0) / ptGraded : null;

  let weightedAvg: number | null = null;
  let formulaLabel = 'Quizzes 40% · Performance Tasks 60%';

  if (quizAvg !== null && ptAvg !== null) {
    weightedAvg = quizAvg * 0.4 + ptAvg * 0.6;
  } else if (quizAvg !== null) {
    weightedAvg = quizAvg;
    formulaLabel = 'Quizzes 100% (Performance Tasks pending)';
  } else if (ptAvg !== null) {
    weightedAvg = ptAvg;
    formulaLabel = 'Performance Tasks 100% (Quizzes pending)';
  } else {
    formulaLabel = 'No graded assessments yet';
  }

  const isIncomplete = quizzesTaken < quizzesTotal || ptGraded < ptTotal;

  let standing: StandingLabel;
  if (student.status === 'inactive') {
    standing = 'Inactive';
  } else if (weightedAvg === null || weightedAvg < 75) {
    standing = 'At Risk';
  } else if (weightedAvg >= 90) {
    standing = 'Honors';
  } else {
    standing = 'Passing';
  }

  return {
    quizItems,
    taskItems,
    quizzesTaken,
    quizzesTotal,
    quizAvg,
    ptSubmitted,
    ptGraded,
    ptTotal,
    ptAvg,
    weightedAvg,
    formulaLabel,
    isIncomplete,
    standing,
  };
}

/* ============================================================================
 * 4. Light-Dismiss Fallback Hook for Native <dialog closedby="any">
 * ============================================================================ */

function useDialogLightDismiss(dialogRef: React.RefObject<HTMLDialogElement | null>) {
  useEffect(() => {
    const dialog = dialogRef.current;
    if (!dialog) return;
    if ('closedBy' in HTMLDialogElement.prototype) return;

    let pressedOutside = false;
    const isOutsideBounds = (event: PointerEvent) => {
      const rect = dialog.getBoundingClientRect();
      return (
        event.clientX < rect.left ||
        event.clientX > rect.right ||
        event.clientY < rect.top ||
        event.clientY > rect.bottom
      );
    };

    const handlePointerDown = (event: PointerEvent) => {
      pressedOutside = event.target === dialog && isOutsideBounds(event);
    };

    const handlePointerUp = (event: PointerEvent) => {
      if (pressedOutside && event.target === dialog && isOutsideBounds(event)) {
        dialog.close();
      }
      pressedOutside = false;
    };

    dialog.addEventListener('pointerdown', handlePointerDown);
    dialog.addEventListener('pointerup', handlePointerUp);
    return () => {
      dialog.removeEventListener('pointerdown', handlePointerDown);
      dialog.removeEventListener('pointerup', handlePointerUp);
    };
  }, [dialogRef]);
}

/* ============================================================================
 * 5. Compact BoardUI Row Overflow Menu (Non-Destructive Actions Only)
 * ============================================================================ */

interface RowOverflowMenuProps {
  student: GradebookStudentRecord;
  onViewBreakdown: (id: number) => void;
  onAdjustScores: (id: number) => void;
  onExportStudent: (id: number) => void;
}

function RowOverflowMenu({
  student,
  onViewBreakdown,
  onAdjustScores,
  onExportStudent,
}: RowOverflowMenuProps) {
  const [isOpen, setIsOpen] = useState(false);
  const triggerRef = useRef<HTMLButtonElement | null>(null);
  const popoverRef = useRef<HTMLDivElement | null>(null);
  const refs = useMemo(() => [triggerRef, popoverRef], []);
  const closeMenu = useCallback(() => setIsOpen(false), []);

  useDismissOnOutsidePress(isOpen, closeMenu, refs);

  const rosterName = formatRosterName(student);

  return (
    <div className="relative inline-flex" onClick={(e) => e.stopPropagation()}>
      <button
        ref={triggerRef}
        type="button"
        aria-haspopup="dialog"
        aria-expanded={isOpen}
        aria-label={`Actions for ${rosterName}`}
        onClick={(e) => {
          e.stopPropagation();
          setIsOpen((prev) => !prev);
        }}
        className="size-7 rounded-lg inline-flex items-center justify-center text-text3 hover:text-text hover:bg-bg3 transition-colors cursor-pointer outline-none focus-visible:ring-2 focus-visible:ring-accent"
      >
        <RiMoreFill className="size-4" aria-hidden="true" />
      </button>

      <Popover
        isOpen={isOpen}
        onOpenChange={setIsOpen}
        triggerRef={triggerRef}
        placement="bottom end"
        offset={6}
        maxWidthClassName="w-52 max-w-[calc(100vw-24px)]"
      >
        <PopoverDialog aria-label={`Actions for ${rosterName}`} className="p-1.5">
          <div ref={popoverRef} className="flex flex-col gap-0.5">
            <button
              type="button"
              onClick={() => {
                setIsOpen(false);
                onViewBreakdown(student.id);
              }}
              className="w-full text-start px-3 py-2 rounded-lg text-xs font-medium text-text hover:bg-bg3 transition-colors cursor-pointer"
            >
              View Score Breakdown
            </button>
            <button
              type="button"
              onClick={() => {
                setIsOpen(false);
                onAdjustScores(student.id);
              }}
              className="w-full text-start px-3 py-2 rounded-lg text-xs font-medium text-text hover:bg-bg3 transition-colors cursor-pointer"
            >
              Adjust Scores
            </button>
            <div className="my-1 border-t border-border" />
            <button
              type="button"
              onClick={() => {
                setIsOpen(false);
                onExportStudent(student.id);
              }}
              className="w-full text-start px-3 py-2 rounded-lg text-xs font-medium text-text2 hover:text-text hover:bg-bg3 transition-colors cursor-pointer"
            >
              Export Student CSV
            </button>
          </div>
        </PopoverDialog>
      </Popover>
    </div>
  );
}

/* ============================================================================
 * 6. Score Validation Helper (0..max in 0.25 increments)
 * ============================================================================ */

function getScoreValidationError(rawValue: string, maxScore: number): string | null {
  const trimmed = rawValue.trim();
  if (trimmed === '') return null;
  const num = Number(trimmed);
  if (!Number.isFinite(num) || num < 0 || num > maxScore) {
    return `Enter a score between 0 and ${maxScore}.`;
  }
  // Check 0.25 step tolerance against floating point inaccuracy
  const quarterUnits = num * 4;
  if (Math.abs(quarterUnits - Math.round(quarterUnits)) > 1e-6) {
    return `Enter a score between 0 and ${maxScore} in 0.25 increments.`;
  }
  return null;
}

/* ============================================================================
 * 7. Main Component: TeacherGradebookView
 * ============================================================================ */

interface AdjustScoresDraft {
  quizScores: Record<number, string>;
  taskScores: Record<number, string>;
  taskFeedbacks: Record<number, string>;
}

function buildAdjustDraft(metrics: StudentGradeMetrics): AdjustScoresDraft {
  const quizScores: Record<number, string> = {};
  for (const item of metrics.quizItems) {
    quizScores[item.quiz.quiz_id] =
      item.effectiveScore !== null ? formatScoreNumber(item.effectiveScore) : '';
  }

  const taskScores: Record<number, string> = {};
  const taskFeedbacks: Record<number, string> = {};
  for (const item of metrics.taskItems) {
    taskScores[item.task.task_id] =
      item.submission?.score !== null && item.submission?.score !== undefined
        ? formatScoreNumber(item.submission.score)
        : '';
    taskFeedbacks[item.task.task_id] = item.submission?.feedback ?? '';
  }

  return { quizScores, taskScores, taskFeedbacks };
}

export function TeacherGradebookView() {
  const captionId = useId();
  const drawerTitleId = useId();
  const rowsPerPageId = useId();

  const [quizResults, setQuizResults] = useState<QuizResultRecord[]>(INITIAL_QUIZ_RESULTS);
  const [ptSubmissions, setPtSubmissions] =
    useState<PerformanceTaskSubmissionRecord[]>(INITIAL_PT_SUBMISSIONS);

  // Filter, Sort & Pagination state
  const [subjectFilter, setSubjectFilter] = useState<string>('all');
  const [sectionFilter, setSectionFilter] = useState<string>('all');
  const [standingFilter, setStandingFilter] = useState<StandingFilter>('all');
  const [searchQuery, setSearchQuery] = useState<string>('');
  const [sortColumn, setSortColumn] = useState<SortColumn>('student');
  const [sortDirection, setSortDirection] = useState<SortDirection>('ascending');
  const [currentPage, setCurrentPage] = useState<number>(1);
  const [pageSize, setPageSize] = useState<number>(5);
  const [statusAnnouncement, setStatusAnnouncement] = useState<string>('');

  // Slide-over <dialog> state
  const drawerDialogRef = useRef<HTMLDialogElement | null>(null);
  const [selectedStudentId, setSelectedStudentId] = useState<number | null>(null);
  const [drawerTab, setDrawerTab] = useState<DrawerTab>('breakdown');
  const [adjustDraft, setAdjustDraft] = useState<AdjustScoresDraft | null>(null);
  const [interactedFields, setInteractedFields] = useState<Record<string, boolean>>({});
  const [submitAttempted, setSubmitAttempted] = useState<boolean>(false);

  // Toast feedback
  const [toastMessage, setToastMessage] = useState<string | null>(null);
  const toastTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const nextRecordIdRef = useRef<number>(500);

  const showToast = useCallback((msg: string) => {
    setToastMessage(msg);
    if (toastTimerRef.current) {
      clearTimeout(toastTimerRef.current);
    }
    toastTimerRef.current = setTimeout(() => {
      setToastMessage(null);
    }, 3400);
  }, []);

  useEffect(() => {
    return () => {
      if (toastTimerRef.current) clearTimeout(toastTimerRef.current);
    };
  }, []);

  useDialogLightDismiss(drawerDialogRef);

  // Combobox options
  const subjectOptions: HoverComboboxOption[] = useMemo(
    () => [
      {
        value: 'all',
        label: `All Subjects (${TEACHER_SUBJECTS.length})`,
        icon: RiBookOpenLine,
      },
      ...TEACHER_SUBJECTS.map((sub) => ({
        value: String(sub.teacher_subject_id),
        label: `${sub.subject} (${sub.strand})`,
        badge: GRADEBOOK_STUDENTS.filter((st) =>
          st.teacher_subject_ids.includes(sub.teacher_subject_id),
        ).length,
      })),
    ],
    [],
  );

  const sectionOptions: HoverComboboxOption[] = useMemo(
    () => [
      {
        value: 'all',
        label: 'All Sections',
        icon: RiGroupLine,
      },
      ...SECTIONS.map((sec) => ({
        value: String(sec.section_id),
        label: `${sec.grade_level} – ${sec.section}`,
        badge: GRADEBOOK_STUDENTS.filter((st) => st.section_id === sec.section_id).length,
      })),
    ],
    [],
  );

  // Compute grade metrics for every student under current subject scope
  const studentRows = useMemo(
    () =>
      GRADEBOOK_STUDENTS.map((student) => ({
        student,
        metrics: computeStudentGradeMetrics(student, subjectFilter, quizResults, ptSubmissions),
      })),
    [subjectFilter, quizResults, ptSubmissions],
  );

  // Cohort scoped by Subject + Section (powers the 4-column Summary Card)
  const cohortRows = useMemo(
    () =>
      studentRows.filter(({ student }) => {
        if (
          subjectFilter !== 'all' &&
          !student.teacher_subject_ids.includes(Number(subjectFilter))
        ) {
          return false;
        }
        if (sectionFilter !== 'all' && String(student.section_id) !== sectionFilter) {
          return false;
        }
        return true;
      }),
    [studentRows, subjectFilter, sectionFilter],
  );

  const summaryStats = useMemo(() => {
    const averages = cohortRows
      .map((r) => r.metrics.weightedAvg)
      .filter((v): v is number => v !== null);
    const classAvg =
      averages.length > 0 ? averages.reduce((sum, v) => sum + v, 0) / averages.length : null;
    const highestAvg = averages.length > 0 ? Math.max(...averages) : null;

    let completedItems = 0;
    let expectedItems = 0;
    for (const { metrics } of cohortRows) {
      completedItems += metrics.quizzesTaken + metrics.ptSubmitted;
      expectedItems += metrics.quizzesTotal + metrics.ptTotal;
    }
    const submissionRate =
      expectedItems > 0 ? Math.round((completedItems / expectedItems) * 100) : 0;

    const atRiskCount = cohortRows.filter(
      ({ student, metrics }) =>
        student.status === 'active' &&
        (metrics.weightedAvg === null || metrics.weightedAvg < 75),
    ).length;

    return { classAvg, highestAvg, submissionRate, atRiskCount };
  }, [cohortRows]);

  // Filtered + Sorted list
  const visibleRows = useMemo(() => {
    const q = searchQuery.trim().toLowerCase();

    const filtered = cohortRows.filter(({ student, metrics }) => {
      if (standingFilter === 'passing') {
        if (student.status !== 'active' || metrics.weightedAvg === null || metrics.weightedAvg < 75) {
          return false;
        }
      } else if (standingFilter === 'at_risk') {
        if (
          student.status !== 'active' ||
          (metrics.weightedAvg !== null && metrics.weightedAvg >= 75)
        ) {
          return false;
        }
      } else if (standingFilter === 'incomplete') {
        if (!metrics.isIncomplete) return false;
      }

      if (q) {
        const hay =
          `${formatRosterName(student)} ${student.firstname} ${student.lastname} ${student.student_id} ${student.lrn}`.toLowerCase();
        if (!hay.includes(q)) return false;
      }
      return true;
    });

    const factor = sortDirection === 'ascending' ? 1 : -1;
    return [...filtered].sort((a, b) => {
      if (sortColumn === 'quizzes') {
        return factor * ((a.metrics.quizAvg ?? -1) - (b.metrics.quizAvg ?? -1));
      }
      if (sortColumn === 'tasks') {
        return factor * ((a.metrics.ptAvg ?? -1) - (b.metrics.ptAvg ?? -1));
      }
      if (sortColumn === 'weighted') {
        return factor * ((a.metrics.weightedAvg ?? -1) - (b.metrics.weightedAvg ?? -1));
      }
      return factor * COLLATOR.compare(formatRosterName(a.student), formatRosterName(b.student));
    });
  }, [cohortRows, standingFilter, searchQuery, sortColumn, sortDirection]);

  // Pagination derived values (render-time safe)
  const totalPages = Math.max(1, Math.ceil(visibleRows.length / pageSize));
  const safePage = Math.min(currentPage, totalPages);
  const pageStartIndex = (safePage - 1) * pageSize;
  const paginatedRows = visibleRows.slice(pageStartIndex, pageStartIndex + pageSize);
  const showingStart = visibleRows.length === 0 ? 0 : pageStartIndex + 1;
  const showingEnd = Math.min(pageStartIndex + pageSize, visibleRows.length);

  const selectedRow = useMemo(
    () => studentRows.find((r) => r.student.id === selectedStudentId) ?? null,
    [studentRows, selectedStudentId],
  );

  // Handlers
  const handleSort = (column: SortColumn, label: string) => {
    const nextDir: SortDirection =
      sortColumn === column && sortDirection === 'ascending' ? 'descending' : 'ascending';
    setSortColumn(column);
    setSortDirection(nextDir);
    setStatusAnnouncement(`Sorted by ${label}, ${nextDir}, ${visibleRows.length} rows`);
  };

  const goToPage = (nextPage: number) => {
    const clamped = Math.max(1, Math.min(nextPage, totalPages));
    setCurrentPage(clamped);
    const start = visibleRows.length === 0 ? 0 : (clamped - 1) * pageSize + 1;
    const end = Math.min(clamped * pageSize, visibleRows.length);
    setStatusAnnouncement(
      `Page ${clamped} of ${totalPages}, showing ${start} to ${end} of ${visibleRows.length} students`,
    );
  };

  const openDrawer = (studentId: number, tab: DrawerTab = 'breakdown') => {
    const target = studentRows.find((r) => r.student.id === studentId);
    if (!target) return;
    setSelectedStudentId(studentId);
    setDrawerTab(tab);
    setAdjustDraft(buildAdjustDraft(target.metrics));
    setInteractedFields({});
    setSubmitAttempted(false);

    const dlg = drawerDialogRef.current;
    if (dlg && !dlg.open) {
      dlg.showModal();
    }
  };

  const closeDrawer = () => {
    const dlg = drawerDialogRef.current;
    if (dlg?.open) dlg.close();
  };

  const handleSwitchDrawerTab = (nextTab: DrawerTab) => {
    setDrawerTab(nextTab);
    if (nextTab === 'adjust' && selectedRow) {
      setAdjustDraft(buildAdjustDraft(selectedRow.metrics));
      setInteractedFields({});
      setSubmitAttempted(false);
    }
  };

  const handleExportRosterCsv = () => {
    const subjectLabel =
      subjectFilter === 'all'
        ? 'all-subjects'
        : (SUBJECT_BY_ID.get(Number(subjectFilter))?.strand.toLowerCase() ?? 'subject');

    const rows: Array<Array<string | number>> = [
      [
        'Student ID',
        'LRN',
        'Student Name',
        'Section',
        'Status',
        'Quizzes Taken',
        'Quiz Average (%)',
        'Performance Tasks Graded',
        'Performance Task Average (%)',
        'Weighted Average (%)',
        'Standing',
      ],
      ...visibleRows.map(({ student, metrics }) => [
        student.student_id,
        student.lrn,
        formatRosterName(student),
        formatSectionLabel(student.section_id),
        student.status === 'active' ? 'Active' : 'Inactive',
        `${metrics.quizzesTaken}/${metrics.quizzesTotal}`,
        metrics.quizAvg !== null ? metrics.quizAvg.toFixed(1) : 'N/A',
        `${metrics.ptGraded}/${metrics.ptTotal}`,
        metrics.ptAvg !== null ? metrics.ptAvg.toFixed(1) : 'N/A',
        metrics.weightedAvg !== null ? metrics.weightedAvg.toFixed(1) : 'N/A',
        metrics.standing,
      ]),
    ];

    triggerCsvDownload(`excelearn-gradebook-${subjectLabel}.csv`, rows);
    showToast(`Exported ${visibleRows.length} student grade records to CSV.`);
  };

  const handleExportStudentCsv = (studentId: number) => {
    const target = studentRows.find((r) => r.student.id === studentId);
    if (!target) return;
    const { student, metrics } = target;

    const rows: Array<Array<string | number>> = [
      ['Student ID', student.student_id],
      ['LRN', student.lrn],
      ['Student Name', formatRosterName(student)],
      ['Section', formatSectionLabel(student.section_id)],
      ['Weighted Formula', metrics.formulaLabel],
      [
        'Weighted Average (%)',
        metrics.weightedAvg !== null ? metrics.weightedAvg.toFixed(1) : 'N/A',
      ],
      ['Standing', metrics.standing],
      [],
      ['Category', 'Subject', 'Lesson / Assessment', 'Mode / Status', 'Score', 'Max Score', 'Percentage (%)'],
      ...metrics.quizItems.map((item) => [
        'Quiz',
        item.subject ? `${item.subject.subject} (${item.subject.strand})` : '—',
        `${item.lesson.title} — ${item.quiz.description}`,
        item.quiz.grading_mode === 'manual' ? 'Essay / Manual' : 'Auto-graded',
        item.effectiveScore !== null ? formatScoreNumber(item.effectiveScore) : 'Not taken',
        item.quiz.max_score,
        item.percent !== null ? item.percent.toFixed(1) : 'N/A',
      ]),
      ...metrics.taskItems.map((item) => [
        'Performance Task',
        item.subject ? `${item.subject.subject} (${item.subject.strand})` : '—',
        item.task.title,
        item.submission
          ? item.submission.status === 'graded'
            ? 'Graded'
            : 'Pending'
          : 'Not submitted',
        item.submission?.score !== null && item.submission?.score !== undefined
          ? formatScoreNumber(item.submission.score)
          : '—',
        item.task.max_score,
        item.percent !== null ? item.percent.toFixed(1) : 'N/A',
      ]),
    ];

    triggerCsvDownload(`gradebook-${student.student_id.toLowerCase()}.csv`, rows);
    showToast(`Exported grade report for ${formatRosterName(student)}.`);
  };

  const handleSaveAdjustedScores = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    if (!selectedRow || !adjustDraft) return;
    setSubmitAttempted(true);

    const { student, metrics } = selectedRow;

    // Validate all numeric score inputs before saving
    for (const item of metrics.quizItems) {
      const val = adjustDraft.quizScores[item.quiz.quiz_id] ?? '';
      if (getScoreValidationError(val, item.quiz.max_score)) {
        const inputEl = e.currentTarget.elements.namedItem(
          `quiz-${item.quiz.quiz_id}`,
        ) as HTMLInputElement | null;
        inputEl?.focus();
        return;
      }
    }

    for (const item of metrics.taskItems) {
      const val = adjustDraft.taskScores[item.task.task_id] ?? '';
      if (getScoreValidationError(val, item.task.max_score)) {
        const inputEl = e.currentTarget.elements.namedItem(
          `task-${item.task.task_id}`,
        ) as HTMLInputElement | null;
        inputEl?.focus();
        return;
      }
    }

    const timestamp = formatSqlTimestamp(new Date());

    // Update quiz results (`manual_score` / `score`)
    setQuizResults((prev) => {
      const next = [...prev];
      for (const item of metrics.quizItems) {
        const raw = (adjustDraft.quizScores[item.quiz.quiz_id] ?? '').trim();
        const idx = next.findIndex(
          (r) => r.student_id === student.id && r.quizzes_id === item.quiz.quiz_id,
        );
        if (raw === '') {
          // Non-destructive: if cleared on an existing entry, keep original score unless untouched
          continue;
        }
        const parsed = Number(raw);
        if (idx >= 0) {
          next[idx] = {
            ...next[idx],
            manual_score: parsed,
            updated_at: timestamp,
          };
        } else {
          next.push({
            id: nextRecordIdRef.current++,
            student_id: student.id,
            quizzes_id: item.quiz.quiz_id,
            score: String(parsed),
            manual_score: parsed,
            time_used: 0,
            updated_at: timestamp,
          });
        }
      }
      return next;
    });

    // Update performance task submissions (`score` + `feedback`)
    setPtSubmissions((prev) => {
      const next = [...prev];
      for (const item of metrics.taskItems) {
        const raw = (adjustDraft.taskScores[item.task.task_id] ?? '').trim();
        const feedback = (adjustDraft.taskFeedbacks[item.task.task_id] ?? '').trim();
        const idx = next.findIndex(
          (sub) => sub.student_id === student.id && sub.task_id === item.task.task_id,
        );
        if (raw === '') {
          if (idx >= 0 && feedback !== next[idx].feedback) {
            next[idx] = { ...next[idx], feedback };
          }
          continue;
        }
        const parsed = Number(raw);
        if (idx >= 0) {
          next[idx] = {
            ...next[idx],
            score: parsed,
            feedback,
            status: 'graded',
            graded_at: timestamp,
          };
        } else {
          next.push({
            submission_id: nextRecordIdRef.current++,
            task_id: item.task.task_id,
            student_id: student.id,
            score: parsed,
            feedback,
            status: 'graded',
            submitted_at: timestamp,
            graded_at: timestamp,
          });
        }
      }
      return next;
    });

    setDrawerTab('breakdown');
    setInteractedFields({});
    setSubmitAttempted(false);
    showToast(`Saved updated scores for ${formatRosterName(student)}.`);
  };

  // Group quiz items by lesson for Score Breakdown tab
  const groupedQuizLessons = useMemo(() => {
    if (!selectedRow) return [];
    const groups = new Map<
      number,
      { lesson: TeacherLessonRecord; subjectLabel: string; items: QuizBreakdownItem[] }
    >();

    for (const item of selectedRow.metrics.quizItems) {
      const existing = groups.get(item.lesson.lesson_id);
      if (existing) {
        existing.items.push(item);
      } else {
        groups.set(item.lesson.lesson_id, {
          lesson: item.lesson,
          subjectLabel: item.subject
            ? `${item.subject.subject} (${item.subject.strand})`
            : 'Assigned Subject',
          items: [item],
        });
      }
    }
    return Array.from(groups.values());
  }, [selectedRow]);

  return (
    <div className="space-y-4">
      {/* 1. Compact Header Strip */}
      <section className="flex flex-wrap items-end justify-between gap-4">
        <div className="space-y-1">
          <h3 className="text-2xl font-bold text-text font-serif tracking-tight">Gradebook</h3>
          <p className="text-xs text-text3">
            Review quiz scores, performance tasks, and weighted averages across your assigned classes.
          </p>
        </div>

        <div className="flex items-center gap-2">
          <button
            type="button"
            onClick={() => window.print()}
            className="btn btn-ghost btn-sm bg-bg2 border border-border px-3.5 py-2 text-xs font-semibold rounded-xl"
          >
            <RiPrinterLine className="size-4" aria-hidden="true" />
            <span>Print Sheet</span>
          </button>
          <button
            type="button"
            onClick={handleExportRosterCsv}
            className="btn btn-primary btn-sm px-3.5 py-2 text-xs font-semibold rounded-xl shadow-xs"
          >
            <RiDownloadLine className="size-4" aria-hidden="true" />
            <span>Export CSV</span>
          </button>
        </div>
      </section>

      {/* 2. Simple 4-Column Summary Card */}
      <section className="bg-bg2 border border-border rounded-2xl overflow-hidden">
        <dl className="grid grid-cols-2 sm:grid-cols-4 divide-y sm:divide-y-0 sm:divide-x divide-border">
          <div className="px-4 py-3">
            <dt className="text-xs text-text3">Class Average</dt>
            <dd className="text-xl font-semibold tabular-nums text-text mt-0.5">
              {formatPercent(summaryStats.classAvg)}
            </dd>
          </div>
          <div className="px-4 py-3">
            <dt className="text-xs text-text3">Highest Average</dt>
            <dd className="text-xl font-semibold tabular-nums text-text mt-0.5">
              {formatPercent(summaryStats.highestAvg)}
            </dd>
          </div>
          <div className="px-4 py-3">
            <dt className="text-xs text-text3">Submission Rate</dt>
            <dd className="text-xl font-semibold tabular-nums text-text mt-0.5">
              {summaryStats.submissionRate}%
            </dd>
          </div>
          <div className="px-4 py-3">
            <dt className="text-xs text-text3">At Risk (&lt;75%)</dt>
            <dd
              className={cx(
                'text-xl font-semibold tabular-nums mt-0.5',
                summaryStats.atRiskCount > 0 ? 'text-amber' : 'text-text',
              )}
            >
              {summaryStats.atRiskCount}
            </dd>
          </div>
        </dl>
      </section>

      {/* 3. Single-Row Intentional Filter Bar */}
      <section className="p-3 rounded-2xl bg-bg2 border border-border flex flex-wrap items-center gap-2.5">
        <HoverCombobox
          ariaLabel="Filter by subject"
          title="Subject Scope"
          value={subjectFilter}
          onChange={(val) => {
            setSubjectFilter(val);
            setCurrentPage(1);
          }}
          options={subjectOptions}
          menuWidthClassName="w-72 max-w-[calc(100vw-32px)]"
          showCountBadge
        />

        <HoverCombobox
          ariaLabel="Filter by section"
          title="Section"
          value={sectionFilter}
          onChange={(val) => {
            setSectionFilter(val);
            setCurrentPage(1);
          }}
          options={sectionOptions}
          menuWidthClassName="w-56 max-w-[calc(100vw-32px)]"
          showCountBadge
        />

        <div className="relative flex-1 min-w-[200px]">
          <RiSearchLine
            className="size-3.5 text-text3 absolute start-3 top-1/2 -translate-y-1/2 pointer-events-none"
            aria-hidden="true"
          />
          <input
            type="search"
            value={searchQuery}
            onChange={(e) => {
              setSearchQuery(e.target.value);
              setCurrentPage(1);
            }}
            aria-label="Search by name, Student ID, or LRN"
            placeholder="Search name, Student ID, or LRN..."
            className="w-full ps-8 pe-3 py-1.5 rounded-xl bg-bg3 border border-border text-xs text-text placeholder:text-text3 outline-none focus:border-accent focus-visible:ring-2 focus-visible:ring-accent/30"
          />
        </div>

        {/* Monochrome segmented standing toggle */}
        <div
          role="group"
          aria-label="Filter by academic standing"
          className="inline-flex rounded-xl bg-bg3 p-0.5 border border-border text-xs font-medium"
        >
          {(
            [
              { id: 'all', label: 'All' },
              { id: 'passing', label: 'Passing' },
              { id: 'at_risk', label: 'At Risk' },
              { id: 'incomplete', label: 'Incomplete' },
            ] as const
          ).map((tab) => {
            const active = standingFilter === tab.id;
            return (
              <button
                key={tab.id}
                type="button"
                aria-pressed={active}
                onClick={() => {
                  setStandingFilter(tab.id);
                  setCurrentPage(1);
                }}
                className={cx(
                  'px-2.5 py-1 rounded-lg transition-colors cursor-pointer whitespace-nowrap',
                  active
                    ? 'bg-bg2 text-text border border-border shadow-2xs font-semibold'
                    : 'text-text2 hover:text-text border border-transparent',
                )}
              >
                {tab.label}
              </button>
            );
          })}
        </div>
      </section>

      {/* 4. Calm 5-Column Sortable Data Table */}
      <section className="rounded-2xl bg-bg2 border border-border overflow-hidden">
        <div role="status" className="sr-status" aria-live="polite">
          {statusAnnouncement}
        </div>

        <div
          tabIndex={0}
          role="region"
          aria-labelledby={captionId}
          className="teacher-roster-region"
        >
          <table className="teacher-roster-table">
            <caption id={captionId} className="sr-status">
              Student gradebook averages across quizzes and performance tasks
            </caption>
            <colgroup>
              <col style={{ width: '31%' }} />
              <col style={{ width: '16%' }} />
              <col style={{ width: '17%' }} />
              <col style={{ width: '21%' }} />
              <col style={{ width: '15%' }} />
            </colgroup>
            <thead>
              <tr>
                <th
                  scope="col"
                  data-type="text"
                  aria-sort={sortColumn === 'student' ? sortDirection : undefined}
                >
                  <button type="button" onClick={() => handleSort('student', 'Student')}>
                    <span>Student</span>
                    <span className="teacher-roster-sort-icon" aria-hidden="true">
                      <RiArrowUpLine className="size-3.5" />
                    </span>
                  </button>
                </th>
                <th
                  scope="col"
                  data-type="number"
                  aria-sort={sortColumn === 'quizzes' ? sortDirection : undefined}
                >
                  <button type="button" onClick={() => handleSort('quizzes', 'Quizzes')}>
                    <span>Quizzes</span>
                    <span className="teacher-roster-sort-icon" aria-hidden="true">
                      <RiArrowUpLine className="size-3.5" />
                    </span>
                  </button>
                </th>
                <th
                  scope="col"
                  data-type="number"
                  aria-sort={sortColumn === 'tasks' ? sortDirection : undefined}
                >
                  <button type="button" onClick={() => handleSort('tasks', 'Performance Tasks')}>
                    <span>Performance Tasks</span>
                    <span className="teacher-roster-sort-icon" aria-hidden="true">
                      <RiArrowUpLine className="size-3.5" />
                    </span>
                  </button>
                </th>
                <th
                  scope="col"
                  data-type="number"
                  aria-sort={sortColumn === 'weighted' ? sortDirection : undefined}
                >
                  <button type="button" onClick={() => handleSort('weighted', 'Weighted Average')}>
                    <span>Weighted Average</span>
                    <span className="teacher-roster-sort-icon" aria-hidden="true">
                      <RiArrowUpLine className="size-3.5" />
                    </span>
                  </button>
                </th>
                <th scope="col" className="text-end">
                  Standing &amp; Action
                </th>
              </tr>
            </thead>
            <tbody>
              {paginatedRows.length === 0 ? (
                <tr>
                  <td colSpan={5} className="py-10 text-center text-xs text-text3">
                    No student grade records match the current filters.
                  </td>
                </tr>
              ) : (
                paginatedRows.map(({ student: s, metrics }, idx) => {
                  const rosterName = formatRosterName(s);
                  const barPct =
                    metrics.weightedAvg !== null
                      ? Math.max(0, Math.min(100, metrics.weightedAvg))
                      : 0;
                  const progressTone =
                    s.status === 'inactive'
                      ? 'inactive'
                      : barPct >= 80
                        ? 'high'
                        : barPct >= 65
                          ? 'mid'
                          : 'low';
                  const progressTextClass =
                    progressTone === 'inactive'
                      ? 'text-text2'
                      : progressTone === 'high'
                        ? 'text-emerald-700 dark:text-green font-semibold'
                        : progressTone === 'mid'
                          ? 'text-amber-700 dark:text-amber font-semibold'
                          : 'text-red-700 dark:text-red font-semibold';

                  return (
                    <tr
                      key={s.id}
                      onClick={() => openDrawer(s.id, 'breakdown')}
                      className={cx(
                        idx >= 5 && 'deferred-row',
                        selectedStudentId === s.id && 'is-selected',
                      )}
                    >
                      <th scope="row" data-value={rosterName}>
                        <div className="flex items-center gap-3 min-w-0">
                          <div className="size-8 rounded-full bg-bg3 border border-border flex items-center justify-center text-xs font-semibold text-text2 shrink-0">
                            {getInitials(s)}
                          </div>
                          <div className="min-w-0">
                            <button
                              type="button"
                              onClick={(e) => {
                                e.stopPropagation();
                                openDrawer(s.id, 'breakdown');
                              }}
                              className="text-xs font-semibold text-text single-line-cell text-start hover:underline cursor-pointer outline-none focus-visible:ring-2 focus-visible:ring-accent rounded"
                            >
                              {rosterName}
                            </button>
                            <div className="text-[11px] text-text3 font-mono tabular-nums single-line-cell">
                              {s.student_id} · {formatSectionLabel(s.section_id)}
                              {s.status === 'inactive' ? ' · Inactive' : ''}
                            </div>
                          </div>
                        </div>
                      </th>

                      <td data-label="Quizzes" data-value={metrics.quizAvg ?? -1}>
                        <div className="text-end sm:text-start tabular-nums">
                          <div className="text-xs font-medium text-text">
                            {formatPercent(metrics.quizAvg)}
                          </div>
                          <div className="text-[11px] text-text3">
                            {metrics.quizzesTaken}/{metrics.quizzesTotal} taken
                          </div>
                        </div>
                      </td>

                      <td data-label="Performance Tasks" data-value={metrics.ptAvg ?? -1}>
                        <div className="text-end sm:text-start tabular-nums">
                          <div className="text-xs font-medium text-text">
                            {formatPercent(metrics.ptAvg)}
                          </div>
                          {metrics.ptSubmitted === 0 && s.status === 'active' ? (
                            <div className="text-[11px] text-amber font-medium">Missing</div>
                          ) : (
                            <div className="text-[11px] text-text3">
                              {metrics.ptGraded}/{metrics.ptTotal} graded
                            </div>
                          )}
                        </div>
                      </td>

                      <td data-label="Weighted Average" data-value={metrics.weightedAvg ?? -1}>
                        <div className="space-y-1.5 min-w-[115px]">
                          <div className="flex items-center justify-between gap-2 text-xs tabular-nums">
                            <span className={progressTextClass}>
                              {formatPercent(metrics.weightedAvg)}
                            </span>
                            <span className="text-[11px] text-text3">
                              {metrics.quizAvg !== null && metrics.ptAvg !== null
                                ? '40/60'
                                : metrics.quizAvg !== null
                                  ? 'Quiz only'
                                  : metrics.ptAvg !== null
                                    ? 'PT only'
                                    : '—'}
                            </span>
                          </div>
                          <progress
                            className="roster-progress"
                            data-tone={progressTone}
                            value={barPct}
                            max={100}
                            aria-label={`${rosterName} progress ${barPct}%`}
                          />
                        </div>
                      </td>

                      <td data-label="Standing & Action">
                        <div className="flex items-center justify-end gap-2">
                          <span
                            className={cx(
                              'text-xs font-medium',
                              metrics.standing === 'At Risk'
                                ? 'text-amber'
                                : metrics.standing === 'Inactive'
                                  ? 'text-text3'
                                  : metrics.standing === 'Honors'
                                    ? 'text-text'
                                    : 'text-text2',
                            )}
                          >
                            {metrics.standing}
                          </span>
                          <RowOverflowMenu
                            student={s}
                            onViewBreakdown={(id) => openDrawer(id, 'breakdown')}
                            onAdjustScores={(id) => openDrawer(id, 'adjust')}
                            onExportStudent={handleExportStudentCsv}
                          />
                        </div>
                      </td>
                    </tr>
                  );
                })
              )}
            </tbody>
          </table>
        </div>

        {/* Accessible Pagination Footer */}
        <div className="px-4 py-2.5 bg-bg3 border-t border-border flex flex-wrap items-center justify-between gap-3 text-xs text-text3">
          <div className="flex flex-wrap items-center gap-3">
            <span className="tabular-nums">
              {visibleRows.length === 0
                ? 'Showing 0 of 0 students'
                : `Showing ${showingStart}–${showingEnd} of ${visibleRows.length} students`}
            </span>
            <div className="inline-flex items-center gap-1.5">
              <label htmlFor={rowsPerPageId} className="text-text3">
                Rows
              </label>
              <select
                id={rowsPerPageId}
                value={pageSize}
                onChange={(e) => {
                  setPageSize(Number(e.target.value));
                  setCurrentPage(1);
                }}
                className="px-2 py-1 rounded-lg bg-bg2 border border-border text-xs text-text outline-none focus:border-accent cursor-pointer"
              >
                <option value={5}>5</option>
                <option value={10}>10</option>
                <option value={25}>25</option>
              </select>
            </div>
          </div>

          <nav aria-label="Gradebook pagination" className="inline-flex items-center gap-1">
            <button
              type="button"
              aria-disabled={safePage <= 1}
              onClick={() => {
                if (safePage > 1) goToPage(safePage - 1);
              }}
              className={cx(
                'min-h-8 px-2.5 rounded-lg border border-border text-xs font-medium inline-flex items-center justify-center transition-colors outline-none focus-visible:ring-2 focus-visible:ring-accent',
                safePage <= 1
                  ? 'bg-bg3 text-text3 opacity-50 cursor-not-allowed'
                  : 'bg-bg2 text-text hover:bg-bg3 cursor-pointer',
              )}
            >
              Previous
            </button>

            {Array.from({ length: totalPages }, (_, i) => i + 1).map((page) => {
              const isCurrent = page === safePage;
              return (
                <button
                  key={page}
                  type="button"
                  aria-label={`Page ${page}`}
                  aria-current={isCurrent ? 'page' : undefined}
                  onClick={() => {
                    if (!isCurrent) goToPage(page);
                  }}
                  className={cx(
                    'min-h-8 min-w-8 px-2 rounded-lg border text-xs tabular-nums inline-flex items-center justify-center transition-colors cursor-pointer outline-none focus-visible:ring-2 focus-visible:ring-accent',
                    isCurrent
                      ? 'bg-accent text-white border-accent font-semibold'
                      : 'bg-bg2 text-text2 border-border hover:text-text hover:bg-bg3',
                  )}
                >
                  {page}
                </button>
              );
            })}

            <button
              type="button"
              aria-disabled={safePage >= totalPages}
              onClick={() => {
                if (safePage < totalPages) goToPage(safePage + 1);
              }}
              className={cx(
                'min-h-8 px-2.5 rounded-lg border border-border text-xs font-medium inline-flex items-center justify-center transition-colors outline-none focus-visible:ring-2 focus-visible:ring-accent',
                safePage >= totalPages
                  ? 'bg-bg3 text-text3 opacity-50 cursor-not-allowed'
                  : 'bg-bg2 text-text hover:bg-bg3 cursor-pointer',
              )}
            >
              Next
            </button>
          </nav>
        </div>
      </section>

      {/* ====================================================================
       * 5. Native <dialog>: Slide-Over Grade Breakdown & Score Adjustment
       * ==================================================================== */}
      <dialog
        ref={drawerDialogRef}
        className="excelearn-drawer-dialog"
        {...({ closedby: 'any' } as Record<string, string>)}
        aria-labelledby={drawerTitleId}
        onClose={() => {
          setSelectedStudentId(null);
          setInteractedFields({});
          setSubmitAttempted(false);
        }}
      >
        {selectedRow && (
          <>
            {/* Header */}
            <div className="px-5 py-4 border-b border-border flex items-start justify-between gap-3 shrink-0">
              <div className="flex items-center gap-3 min-w-0">
                <div className="size-10 rounded-full bg-bg3 border border-border flex items-center justify-center text-xs font-semibold text-text2 shrink-0">
                  {getInitials(selectedRow.student)}
                </div>
                <div className="min-w-0">
                  <h4 id={drawerTitleId} className="text-base font-semibold text-text truncate">
                    {formatRosterName(selectedRow.student)}
                  </h4>
                  <p className="text-xs text-text3 font-mono truncate tabular-nums">
                    {selectedRow.student.student_id} · LRN {selectedRow.student.lrn} ·{' '}
                    {formatSectionLabel(selectedRow.student.section_id)}
                  </p>
                </div>
              </div>
              <button
                type="button"
                onClick={closeDrawer}
                aria-label="Close grade breakdown"
                className="p-1.5 rounded-lg text-text3 hover:text-text hover:bg-bg3 cursor-pointer shrink-0 outline-none focus-visible:ring-2 focus-visible:ring-accent"
              >
                <RiCloseLine className="size-4" aria-hidden="true" />
              </button>
            </div>

            {/* 2 Tabs */}
            <div
              role="tablist"
              aria-label="Gradebook record views"
              className="px-5 pt-3 border-b border-border flex items-center gap-5 text-xs font-medium shrink-0"
            >
              {(
                [
                  { id: 'breakdown', label: 'Score Breakdown' },
                  { id: 'adjust', label: 'Adjust Scores' },
                ] as const
              ).map((tab) => {
                const isSelected = drawerTab === tab.id;
                return (
                  <button
                    key={tab.id}
                    type="button"
                    role="tab"
                    aria-selected={isSelected}
                    onClick={() => handleSwitchDrawerTab(tab.id)}
                    className={cx(
                      'pb-2.5 border-b-2 transition-colors cursor-pointer outline-none focus-visible:ring-2 focus-visible:ring-accent rounded-t',
                      isSelected
                        ? 'border-accent text-text font-semibold'
                        : 'border-transparent text-text2 hover:text-text',
                    )}
                  >
                    {tab.label}
                  </button>
                );
              })}
            </div>

            {/* Body */}
            <div className="p-5 overflow-y-auto flex-1 space-y-5 text-xs">
              {drawerTab === 'breakdown' && (
                <>
                  {/* Transparent Weight Readout Strip */}
                  <div className="p-3 rounded-xl bg-bg3 border border-border flex items-center justify-between gap-3">
                    <div className="space-y-0.5">
                      <div className="text-[11px] font-medium text-text3">Weighted Formula</div>
                      <div className="font-medium text-text">{selectedRow.metrics.formulaLabel}</div>
                    </div>
                    <div className="text-end shrink-0">
                      <div className="text-[11px] text-text3">Letter Grade</div>
                      <div
                        className={cx(
                          'text-sm font-semibold tabular-nums',
                          selectedRow.metrics.standing === 'At Risk' ? 'text-amber' : 'text-text',
                        )}
                      >
                        {getLetterGrade(selectedRow.metrics.weightedAvg)}
                      </div>
                    </div>
                  </div>

                  {/* 3 Compact Totals */}
                  <div className="grid grid-cols-3 gap-2.5">
                    <div className="p-3 rounded-xl bg-bg3 border border-border">
                      <div className="text-text2">Quizzes</div>
                      <div className="text-base font-semibold tabular-nums text-text mt-0.5">
                        {formatPercent(selectedRow.metrics.quizAvg)}
                      </div>
                    </div>
                    <div className="p-3 rounded-xl bg-bg3 border border-border">
                      <div className="text-text2">Tasks</div>
                      <div className="text-base font-semibold tabular-nums text-text mt-0.5">
                        {formatPercent(selectedRow.metrics.ptAvg)}
                      </div>
                    </div>
                    <div className="p-3 rounded-xl bg-bg3 border border-border">
                      <div className="text-text2">Weighted</div>
                      <div
                        className={cx(
                          'text-base font-semibold tabular-nums mt-0.5',
                          selectedRow.metrics.standing === 'At Risk' ? 'text-amber' : 'text-text',
                        )}
                      >
                        {formatPercent(selectedRow.metrics.weightedAvg)}
                      </div>
                    </div>
                  </div>

                  {/* Quizzes by Lesson */}
                  <div className="space-y-3">
                    <div className="font-semibold text-text">Quizzes by Lesson</div>
                    {groupedQuizLessons.length === 0 ? (
                      <div className="p-3 rounded-xl border border-border bg-bg3 text-text3">
                        No quizzes in the selected subject scope.
                      </div>
                    ) : (
                      groupedQuizLessons.map((group) => (
                        <div
                          key={group.lesson.lesson_id}
                          className="rounded-xl border border-border overflow-hidden bg-bg2"
                        >
                          <div className="px-3 py-2 bg-bg3 border-b border-border flex items-center justify-between gap-2">
                            <span className="font-semibold text-text">{group.lesson.title}</span>
                            <span className="text-[11px] text-text3 shrink-0">
                              {group.subjectLabel}
                            </span>
                          </div>
                          <ul className="divide-y divide-border">
                            {group.items.map((item) => (
                              <li
                                key={item.quiz.quiz_id}
                                className="px-3 py-2.5 flex items-center justify-between gap-3"
                              >
                                <div className="min-w-0">
                                  <div className="font-medium text-text truncate">
                                    {item.quiz.description}
                                  </div>
                                  <div className="text-[11px] text-text3">
                                    {item.quiz.grading_mode === 'manual'
                                      ? 'Essay / Manual'
                                      : 'Auto-graded'}
                                    {item.result?.manual_score !== null &&
                                    item.result?.manual_score !== undefined
                                      ? ' · Adjusted'
                                      : ''}
                                  </div>
                                </div>
                                <div className="text-end shrink-0 tabular-nums">
                                  {item.effectiveScore !== null ? (
                                    <>
                                      <div className="font-semibold text-text">
                                        {formatScoreNumber(item.effectiveScore)} /{' '}
                                        {item.quiz.max_score}
                                      </div>
                                      <div className="text-[11px] text-text3">
                                        {formatPercent(item.percent)}
                                      </div>
                                    </>
                                  ) : (
                                    <div className="text-text3">Not taken</div>
                                  )}
                                </div>
                              </li>
                            ))}
                          </ul>
                        </div>
                      ))
                    )}
                  </div>

                  {/* Performance Tasks */}
                  <div className="space-y-2">
                    <div className="font-semibold text-text">Performance Tasks</div>
                    {selectedRow.metrics.taskItems.length === 0 ? (
                      <div className="p-3 rounded-xl border border-border bg-bg3 text-text3">
                        No performance tasks in the selected subject scope.
                      </div>
                    ) : (
                      <ul className="divide-y divide-border rounded-xl border border-border overflow-hidden bg-bg2">
                        {selectedRow.metrics.taskItems.map((item) => {
                          const statusLabel = item.submission
                            ? item.submission.status === 'graded'
                              ? 'Graded'
                              : 'Pending'
                            : 'Not submitted';

                          return (
                            <li key={item.task.task_id} className="px-3 py-2.5 space-y-1">
                              <div className="flex items-center justify-between gap-3">
                                <div className="min-w-0">
                                  <div className="font-medium text-text truncate">
                                    {item.task.title}
                                  </div>
                                  <div
                                    className={cx(
                                      'text-[11px]',
                                      !item.submission && selectedRow.student.status === 'active'
                                        ? 'text-amber'
                                        : 'text-text3',
                                    )}
                                  >
                                    {statusLabel}
                                    {item.subject ? ` · ${item.subject.strand}` : ''}
                                  </div>
                                </div>
                                <div className="text-end shrink-0 tabular-nums">
                                  {item.submission?.score !== null &&
                                  item.submission?.score !== undefined ? (
                                    <>
                                      <div className="font-semibold text-text">
                                        {formatScoreNumber(item.submission.score)} /{' '}
                                        {item.task.max_score}
                                      </div>
                                      <div className="text-[11px] text-text3">
                                        {formatPercent(item.percent)}
                                      </div>
                                    </>
                                  ) : (
                                    <div className="text-text3">— / {item.task.max_score}</div>
                                  )}
                                </div>
                              </div>
                              {item.submission?.feedback && (
                                <p className="text-[11px] text-text2">
                                  Remarks: {item.submission.feedback}
                                </p>
                              )}
                            </li>
                          );
                        })}
                      </ul>
                    )}
                  </div>
                </>
              )}

              {drawerTab === 'adjust' && adjustDraft && (
                <form onSubmit={handleSaveAdjustedScores} noValidate className="space-y-5">
                  <p className="text-text2">
                    Enter decimal scores in <span className="font-mono">0.25</span> increments.
                    Historical records are preserved non-destructively.
                  </p>

                  {/* Quiz Scores */}
                  <div className="space-y-2.5">
                    <div className="font-semibold text-text3 uppercase tracking-wider">
                      Quizzes &amp; Manual Scores
                    </div>
                    {selectedRow.metrics.quizItems.map((item) => {
                      const fieldKey = `quiz-${item.quiz.quiz_id}`;
                      const inputId = `${drawerTitleId}-${fieldKey}`;
                      const errorId = `${inputId}-error`;
                      const val = adjustDraft.quizScores[item.quiz.quiz_id] ?? '';
                      const errorMsg = getScoreValidationError(val, item.quiz.max_score);
                      const showInvalid =
                        Boolean(errorMsg) && (submitAttempted || Boolean(interactedFields[fieldKey]));

                      return (
                        <div
                          key={item.quiz.quiz_id}
                          className="p-3 rounded-xl border border-border bg-bg3/50 space-y-1.5"
                        >
                          <div className="flex items-start justify-between gap-3">
                            <label htmlFor={inputId} className="font-medium text-text">
                              {item.quiz.description}
                              <span className="block text-[11px] font-normal text-text3">
                                {item.lesson.title} ·{' '}
                                {item.quiz.grading_mode === 'manual'
                                  ? 'Essay / Manual'
                                  : 'Auto-graded'}{' '}
                                (0–{item.quiz.max_score})
                              </span>
                            </label>
                            <div className="flex items-center gap-1.5 shrink-0">
                              <input
                                id={inputId}
                                name={fieldKey}
                                type="number"
                                step="0.25"
                                min={0}
                                max={item.quiz.max_score}
                                value={val}
                                aria-invalid={showInvalid ? 'true' : 'false'}
                                aria-describedby={showInvalid ? errorId : undefined}
                                onBlur={() =>
                                  setInteractedFields((prev) => ({ ...prev, [fieldKey]: true }))
                                }
                                onChange={(e) => {
                                  const nextVal = e.target.value;
                                  setAdjustDraft((d) =>
                                    d
                                      ? {
                                          ...d,
                                          quizScores: {
                                            ...d.quizScores,
                                            [item.quiz.quiz_id]: nextVal,
                                          },
                                        }
                                      : d,
                                  );
                                }}
                                placeholder="—"
                                className="grade-score-input w-20 px-2.5 py-1.5 rounded-lg bg-bg2 border border-border text-text font-mono tabular-nums text-end outline-none focus:border-accent"
                              />
                              <span className="text-text3 tabular-nums">
                                / {item.quiz.max_score}
                              </span>
                            </div>
                          </div>
                          {showInvalid && errorMsg && (
                            <p id={errorId} className="text-[11px] text-amber flex items-center gap-1">
                              <RiErrorWarningLine className="size-3.5 shrink-0" aria-hidden="true" />
                              <span>{errorMsg}</span>
                            </p>
                          )}
                        </div>
                      );
                    })}
                  </div>

                  {/* Performance Task Scores */}
                  <div className="space-y-2.5">
                    <div className="font-semibold text-text3 uppercase tracking-wider">
                      Performance Tasks
                    </div>
                    {selectedRow.metrics.taskItems.map((item) => {
                      const fieldKey = `task-${item.task.task_id}`;
                      const inputId = `${drawerTitleId}-${fieldKey}`;
                      const feedbackId = `${inputId}-feedback`;
                      const errorId = `${inputId}-error`;
                      const val = adjustDraft.taskScores[item.task.task_id] ?? '';
                      const feedback = adjustDraft.taskFeedbacks[item.task.task_id] ?? '';
                      const errorMsg = getScoreValidationError(val, item.task.max_score);
                      const showInvalid =
                        Boolean(errorMsg) && (submitAttempted || Boolean(interactedFields[fieldKey]));

                      return (
                        <div
                          key={item.task.task_id}
                          className="p-3 rounded-xl border border-border bg-bg3/50 space-y-2"
                        >
                          <div className="flex items-start justify-between gap-3">
                            <label htmlFor={inputId} className="font-medium text-text">
                              {item.task.title}
                              <span className="block text-[11px] font-normal text-text3">
                                {item.subject?.strand ?? 'Task'} · Max {item.task.max_score} pts
                              </span>
                            </label>
                            <div className="flex items-center gap-1.5 shrink-0">
                              <input
                                id={inputId}
                                name={fieldKey}
                                type="number"
                                step="0.25"
                                min={0}
                                max={item.task.max_score}
                                value={val}
                                aria-invalid={showInvalid ? 'true' : 'false'}
                                aria-describedby={showInvalid ? errorId : undefined}
                                onBlur={() =>
                                  setInteractedFields((prev) => ({ ...prev, [fieldKey]: true }))
                                }
                                onChange={(e) => {
                                  const nextVal = e.target.value;
                                  setAdjustDraft((d) =>
                                    d
                                      ? {
                                          ...d,
                                          taskScores: {
                                            ...d.taskScores,
                                            [item.task.task_id]: nextVal,
                                          },
                                        }
                                      : d,
                                  );
                                }}
                                placeholder="—"
                                className="grade-score-input w-20 px-2.5 py-1.5 rounded-lg bg-bg2 border border-border text-text font-mono tabular-nums text-end outline-none focus:border-accent"
                              />
                              <span className="text-text3 tabular-nums">
                                / {item.task.max_score}
                              </span>
                            </div>
                          </div>
                          {showInvalid && errorMsg && (
                            <p id={errorId} className="text-[11px] text-amber flex items-center gap-1">
                              <RiErrorWarningLine className="size-3.5 shrink-0" aria-hidden="true" />
                              <span>{errorMsg}</span>
                            </p>
                          )}
                          <div>
                            <label htmlFor={feedbackId} className="sr-status">
                              Remarks for {item.task.title}
                            </label>
                            <input
                              id={feedbackId}
                              type="text"
                              value={feedback}
                              onChange={(e) => {
                                const nextFeedback = e.target.value;
                                setAdjustDraft((d) =>
                                  d
                                    ? {
                                        ...d,
                                        taskFeedbacks: {
                                          ...d.taskFeedbacks,
                                          [item.task.task_id]: nextFeedback,
                                        },
                                      }
                                    : d,
                                );
                              }}
                              placeholder="Optional teacher remarks..."
                              className="w-full px-2.5 py-1.5 rounded-lg bg-bg2 border border-border text-xs text-text placeholder:text-text3 outline-none focus:border-accent"
                            />
                          </div>
                        </div>
                      );
                    })}
                  </div>

                  <div className="pt-3 border-t border-border flex items-center justify-end gap-2">
                    <button
                      type="button"
                      onClick={() => setDrawerTab('breakdown')}
                      className="btn btn-ghost btn-sm px-3.5 py-2 rounded-xl text-xs"
                    >
                      Cancel
                    </button>
                    <button
                      type="submit"
                      className="btn btn-primary btn-sm px-4 py-2 rounded-xl text-xs font-semibold"
                    >
                      Save Scores
                    </button>
                  </div>
                </form>
              )}
            </div>
          </>
        )}
      </dialog>

      {/* Quiet Non-Blocking Toast */}
      {toastMessage && (
        <div
          role="status"
          aria-live="polite"
          className="fixed bottom-5 end-5 z-50 px-4 py-2.5 rounded-xl bg-bg2 border border-border shadow-lg text-xs font-medium text-text max-w-sm"
        >
          {toastMessage}
        </div>
      )}
    </div>
  );
}
