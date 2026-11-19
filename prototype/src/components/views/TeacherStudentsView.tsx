import React, { useCallback, useEffect, useId, useMemo, useRef, useState } from 'react';
import { HoverCombobox, type HoverComboboxOption } from '@/components/base/combobox';
import { Dialog as PopoverDialog, Popover } from '@/components/base/popover';
import { cx } from '@/utils/cx';
import { useDismissOnOutsidePress } from '@/utils/use-dismiss-on-outside-press';
import {
  RiAddLine,
  RiArrowUpLine,
  RiBookOpenLine,
  RiCheckLine,
  RiCloseLine,
  RiFileCopyLine,
  RiGroupLine,
  RiMoreFill,
  RiSearchLine,
  RiSendPlaneLine,
  RiUserAddLine,
} from '@remixicon/react';

/* ============================================================================
 * 1. SQL-Aligned TypeScript Interfaces (u342202101_excelearn.sql)
 * ============================================================================ */

export type StudentStatus = 'active' | 'inactive';

/** Maps 1:1 to `tbl_teacher_subjects` JOIN `tblsubjects` JOIN `tblstrands` */
export interface TeacherSubjectOption {
  teacher_subject_id: number;
  subject_id: number;
  subject: string;
  strand: string;
  track: string;
}

/** Maps 1:1 to `tblsections` JOIN `tblgrade_levels` */
export interface SectionOption {
  section_id: number;
  grade_level_id: number;
  grade_level: string;
  section: string;
}

/**
 * Maps 1:1 to `tblstudents` + `tbl_teachers_students` (`teacher_subject_ids`)
 * plus read-only academic summary aggregates from existing LMS tables.
 */
export interface TeacherStudentRecord {
  id: number;
  student_id: string;
  section_id: number;
  firstname: string;
  middlename: string;
  lastname: string;
  suffix: string;
  lrn: string;
  birthdate: string;
  age: number;
  gender: string;
  contact_number: string;
  guardian_name: string;
  guardian_email: string;
  address: string;
  status: StudentStatus;
  created_at: string;
  /** Scoped via `tbl_teachers_students` for the active teacher */
  teacher_subject_ids: number[];
  /** Derived from `tbllesson_progress` */
  lessons_done: number;
  lessons_total: number;
  /** Derived from `tblquiz_results` */
  quiz_avg: number;
  /** Derived from `tblperformance_task_submissions` */
  pt_done: number;
  pt_total: number;
  pt_score: number | null;
  /** Derived from `tblvirtualclass_participants` */
  live_attended: number;
  live_total: number;
}

/** Maps 1:1 to `tblnotifications` */
export interface SimulatedNotification {
  notification_id: number;
  user_id: number;
  title: string;
  message: string;
  type: string;
  status: 'unread' | 'read';
  created_at: string;
}

/* ============================================================================
 * 2. SQL-Aligned Seed Data
 * ============================================================================ */

const TEACHER_SUBJECTS: TeacherSubjectOption[] = [
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

const SECTIONS: SectionOption[] = [
  { section_id: 12, grade_level_id: 1, grade_level: 'Grade 11', section: 'C' },
  { section_id: 15, grade_level_id: 1, grade_level: 'Grade 11', section: 'A' },
  { section_id: 16, grade_level_id: 2, grade_level: 'Grade 12', section: 'B' },
  { section_id: 17, grade_level_id: 1, grade_level: 'Grade 11', section: 'B' },
];

const INITIAL_STUDENTS: TeacherStudentRecord[] = [
  {
    id: 70,
    student_id: 'STUD-123456',
    section_id: 12,
    firstname: 'Bryan',
    middlename: 'Fulo',
    lastname: 'Donceras',
    suffix: 'Jr.',
    lrn: '464437547543',
    birthdate: '2008-08-29',
    age: 18,
    gender: 'Male',
    contact_number: '09382625945',
    guardian_name: 'Elena Donceras',
    guardian_email: 'elena.donceras@gmail.com',
    address: 'Macawayan, Irosin, Sorsogon',
    status: 'active',
    created_at: '2026-06-15 08:20:00',
    teacher_subject_ids: [124, 125, 126],
    lessons_done: 7,
    lessons_total: 8,
    quiz_avg: 92,
    pt_done: 2,
    pt_total: 2,
    pt_score: 100,
    live_attended: 6,
    live_total: 6,
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
    birthdate: '2007-01-02',
    age: 19,
    gender: 'Male',
    contact_number: '09422144112',
    guardian_name: 'Loida Lotino',
    guardian_email: 'loida.lotino@gmail.com',
    address: 'Tabon-tabon, Irosin, Sorsogon',
    status: 'active',
    created_at: '2026-06-15 09:05:00',
    teacher_subject_ids: [124, 125, 126],
    lessons_done: 6,
    lessons_total: 8,
    quiz_avg: 86,
    pt_done: 2,
    pt_total: 2,
    pt_score: 88,
    live_attended: 5,
    live_total: 6,
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
    birthdate: '2009-03-14',
    age: 17,
    gender: 'Female',
    contact_number: '09174451902',
    guardian_name: 'Roberto Santos',
    guardian_email: 'r.santos@gmail.com',
    address: 'Cabitan, Mandaon, Masbate',
    status: 'active',
    created_at: '2026-06-16 10:15:00',
    teacher_subject_ids: [124, 126],
    lessons_done: 8,
    lessons_total: 8,
    quiz_avg: 95,
    pt_done: 2,
    pt_total: 2,
    pt_score: 96,
    live_attended: 6,
    live_total: 6,
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
    birthdate: '2008-11-08',
    age: 17,
    gender: 'Male',
    contact_number: '09289012344',
    guardian_name: 'Marites Cruz',
    guardian_email: 'mcruz.parent@gmail.com',
    address: 'San Juan, Mandaon, Masbate',
    status: 'active',
    created_at: '2026-06-16 11:30:00',
    teacher_subject_ids: [124],
    lessons_done: 3,
    lessons_total: 8,
    quiz_avg: 64,
    pt_done: 0,
    pt_total: 2,
    pt_score: null,
    live_attended: 2,
    live_total: 6,
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
    birthdate: '2009-06-22',
    age: 17,
    gender: 'Female',
    contact_number: '09153328910',
    guardian_name: 'Carmen Andres',
    guardian_email: 'carmen.andres@gmail.com',
    address: 'Poblacion, Mandaon, Masbate',
    status: 'active',
    created_at: '2026-06-17 08:45:00',
    teacher_subject_ids: [126],
    lessons_done: 7,
    lessons_total: 8,
    quiz_avg: 90,
    pt_done: 2,
    pt_total: 2,
    pt_score: 94,
    live_attended: 6,
    live_total: 6,
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
    birthdate: '2008-05-11',
    age: 18,
    gender: 'Male',
    contact_number: '09981203341',
    guardian_name: 'Danilo Villanueva',
    guardian_email: 'dvillanueva@gmail.com',
    address: 'Alas, Mandaon, Masbate',
    status: 'active',
    created_at: '2026-06-17 13:10:00',
    teacher_subject_ids: [124, 126],
    lessons_done: 4,
    lessons_total: 8,
    quiz_avg: 71,
    pt_done: 1,
    pt_total: 2,
    pt_score: 70,
    live_attended: 3,
    live_total: 6,
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
    birthdate: '2007-09-19',
    age: 19,
    gender: 'Female',
    contact_number: '09178823411',
    guardian_name: 'Norma Bautista',
    guardian_email: 'norma.bautista@gmail.com',
    address: 'Tagpu, Mandaon, Masbate',
    status: 'active',
    created_at: '2026-06-18 09:00:00',
    teacher_subject_ids: [125],
    lessons_done: 8,
    lessons_total: 8,
    quiz_avg: 89,
    pt_done: 2,
    pt_total: 2,
    pt_score: 92,
    live_attended: 5,
    live_total: 6,
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
    birthdate: '2008-12-03',
    age: 17,
    gender: 'Male',
    contact_number: '09397721890',
    guardian_name: 'Grace Navarro',
    guardian_email: 'gnavarro@gmail.com',
    address: 'Buri, Mandaon, Masbate',
    status: 'inactive',
    created_at: '2026-06-18 14:20:00',
    teacher_subject_ids: [126],
    lessons_done: 2,
    lessons_total: 8,
    quiz_avg: 78,
    pt_done: 1,
    pt_total: 2,
    pt_score: 80,
    live_attended: 1,
    live_total: 6,
  },
];

/** School-wide `tblstudents` records not yet linked in `tbl_teachers_students` */
const UNASSIGNED_POOL: Omit<TeacherStudentRecord, 'teacher_subject_ids'>[] = [
  {
    id: 73,
    student_id: 'STUD-2026-00914',
    section_id: 17,
    firstname: 'Kyla Mae',
    middlename: 'Soriano',
    lastname: 'Mendoza',
    suffix: '',
    lrn: '109845112233',
    birthdate: '2009-02-10',
    age: 17,
    gender: 'Female',
    contact_number: '09170001122',
    guardian_name: 'Rowena Mendoza',
    guardian_email: 'rowena.mendoza@gmail.com',
    address: 'Bugtong, Mandaon, Masbate',
    status: 'active',
    created_at: '2026-06-19 08:00:00',
    lessons_done: 0,
    lessons_total: 8,
    quiz_avg: 82,
    pt_done: 0,
    pt_total: 2,
    pt_score: null,
    live_attended: 0,
    live_total: 6,
  },
  {
    id: 74,
    student_id: 'STUD-2026-00915',
    section_id: 15,
    firstname: 'Paolo',
    middlename: 'Ramos',
    lastname: 'Enriquez',
    suffix: '',
    lrn: '109845112299',
    birthdate: '2008-07-24',
    age: 18,
    gender: 'Male',
    contact_number: '09185554433',
    guardian_name: 'Nestor Enriquez',
    guardian_email: 'n.enriquez@gmail.com',
    address: 'Dayao, Mandaon, Masbate',
    status: 'active',
    created_at: '2026-06-19 09:15:00',
    lessons_done: 0,
    lessons_total: 8,
    quiz_avg: 85,
    pt_done: 0,
    pt_total: 2,
    pt_score: null,
    live_attended: 0,
    live_total: 6,
  },
  {
    id: 81,
    student_id: 'STUD-2026-00921',
    section_id: 12,
    firstname: 'Therese Anne',
    middlename: 'Gomez',
    lastname: 'Magno',
    suffix: '',
    lrn: '109845113004',
    birthdate: '2009-04-05',
    age: 17,
    gender: 'Female',
    contact_number: '09271129801',
    guardian_name: 'Celia Magno',
    guardian_email: 'celia.magno@gmail.com',
    address: 'Poblacion, Irosin, Sorsogon',
    status: 'active',
    created_at: '2026-06-19 10:30:00',
    lessons_done: 0,
    lessons_total: 8,
    quiz_avg: 88,
    pt_done: 0,
    pt_total: 2,
    pt_score: null,
    live_attended: 0,
    live_total: 6,
  },
];

/* ============================================================================
 * 3. Pure Formatters & Helpers
 * ============================================================================ */

type StatusFilter = 'all' | 'active' | 'attention' | 'inactive';
type SortColumn = 'student' | 'section' | 'progress';
type SortDirection = 'ascending' | 'descending';
type DossierTab = 'overview' | 'profile' | 'edit';
type AddMode = 'existing' | 'new';

const SUBJECT_BY_ID = new Map(TEACHER_SUBJECTS.map((s) => [s.teacher_subject_id, s]));
const SECTION_BY_ID = new Map(SECTIONS.map((s) => [s.section_id, s]));
const COLLATOR = new Intl.Collator(undefined, { numeric: true, sensitivity: 'base' });

function formatRosterName(s: Pick<TeacherStudentRecord, 'firstname' | 'lastname' | 'suffix'>): string {
  return `${s.lastname}, ${s.firstname}${s.suffix ? ` ${s.suffix}` : ''}`;
}

function formatFullName(
  s: Pick<TeacherStudentRecord, 'firstname' | 'middlename' | 'lastname' | 'suffix'>,
): string {
  return [s.firstname, s.middlename, s.lastname, s.suffix].filter(Boolean).join(' ');
}

function getInitials(s: Pick<TeacherStudentRecord, 'firstname' | 'lastname'>): string {
  return `${s.firstname.charAt(0)}${s.lastname.charAt(0)}`.toUpperCase();
}

function computeProgress(s: Pick<TeacherStudentRecord, 'lessons_done' | 'lessons_total' | 'quiz_avg' | 'pt_done' | 'pt_total'>): number {
  const lessonPct = s.lessons_total > 0 ? (s.lessons_done / s.lessons_total) * 100 : 0;
  const ptPct = s.pt_total > 0 ? (s.pt_done / s.pt_total) * 100 : 0;
  return Math.round(lessonPct * 0.5 + (s.quiz_avg || 0) * 0.3 + ptPct * 0.2);
}

function formatSectionLabel(sectionId: number): string {
  const sec = SECTION_BY_ID.get(sectionId);
  return sec ? `${sec.grade_level} – ${sec.section}` : 'Unassigned';
}

function formatStrandLabel(subjectIds: number[]): string {
  const parts = Array.from(
    new Set(
      subjectIds
        .map((id) => {
          const sub = SUBJECT_BY_ID.get(id);
          return sub ? `${sub.strand} · ${sub.track}` : null;
        })
        .filter((x): x is string => Boolean(x)),
    ),
  );
  return parts.length > 0 ? parts.join(' / ') : 'No Strand';
}

function formatSubjectsSummary(subjectIds: number[]): string {
  const names = subjectIds
    .map((id) => SUBJECT_BY_ID.get(id)?.subject)
    .filter((x): x is string => Boolean(x));
  if (names.length === 0) return 'No subjects assigned';
  if (names.length === 1) return names[0];
  return `${names[0]} (+${names.length - 1} more)`;
}

function formatAllSubjectNames(subjectIds: number[]): string {
  return subjectIds
    .map((id) => SUBJECT_BY_ID.get(id)?.subject)
    .filter((x): x is string => Boolean(x))
    .join(', ');
}

function computeAgeFromBirthdate(birthdate: string, now: Date): number {
  if (!birthdate) return 17;
  const parsed = new Date(`${birthdate}T00:00:00`);
  if (Number.isNaN(parsed.getTime())) return 17;
  let age = now.getFullYear() - parsed.getFullYear();
  const monthDiff = now.getMonth() - parsed.getMonth();
  if (monthDiff < 0 || (monthDiff === 0 && now.getDate() < parsed.getDate())) {
    age -= 1;
  }
  return Math.max(0, age);
}

function formatSqlTimestamp(now: Date): string {
  const pad = (n: number) => String(n).padStart(2, '0');
  return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())} ${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
}

/* ============================================================================
 * 4. Light-Dismiss Fallback Hook for Native <dialog closedby="any">
 * ============================================================================ */

function useDialogLightDismiss(
  dialogRef: React.RefObject<HTMLDialogElement | null>,
) {
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
 * 5. Compact BoardUI Row Overflow Menu (Deactivate ONLY — NO Unenroll/Delete)
 * ============================================================================ */

interface RowOverflowMenuProps {
  student: TeacherStudentRecord;
  onOpenOverview: (id: number) => void;
  onOpenEdit: (id: number) => void;
  onToggleStatus: (id: number) => void;
}

function RowOverflowMenu({
  student,
  onOpenOverview,
  onOpenEdit,
  onToggleStatus,
}: RowOverflowMenuProps) {
  const [isOpen, setIsOpen] = useState(false);
  const triggerRef = useRef<HTMLButtonElement | null>(null);
  const popoverRef = useRef<HTMLDivElement | null>(null);
  const refs = useMemo(() => [triggerRef, popoverRef], []);
  const closeMenu = useCallback(() => setIsOpen(false), []);

  useDismissOnOutsidePress(isOpen, closeMenu, refs);

  const rosterName = formatRosterName(student);

  return (
    <div
      className="relative inline-flex"
      onClick={(e) => e.stopPropagation()}
    >
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
                onOpenOverview(student.id);
              }}
              className="w-full text-start px-3 py-2 rounded-lg text-xs font-medium text-text hover:bg-bg3 transition-colors cursor-pointer"
            >
              Open Student Dossier
            </button>
            <button
              type="button"
              onClick={() => {
                setIsOpen(false);
                onOpenEdit(student.id);
              }}
              className="w-full text-start px-3 py-2 rounded-lg text-xs font-medium text-text hover:bg-bg3 transition-colors cursor-pointer"
            >
              Edit Profile &amp; Subjects
            </button>
            <div className="my-1 border-t border-border" />
            <button
              type="button"
              onClick={() => {
                setIsOpen(false);
                onToggleStatus(student.id);
              }}
              className={cx(
                'w-full text-start px-3 py-2 rounded-lg text-xs font-medium transition-colors cursor-pointer',
                student.status === 'active'
                  ? 'text-amber hover:bg-bg3'
                  : 'text-green hover:bg-bg3',
              )}
            >
              {student.status === 'active' ? 'Deactivate Account' : 'Reactivate Account'}
            </button>
          </div>
        </PopoverDialog>
      </Popover>
    </div>
  );
}

/* ============================================================================
 * 6. Main Component: TeacherStudentsView
 * ============================================================================ */

interface EditDraft {
  firstname: string;
  middlename: string;
  lastname: string;
  suffix: string;
  lrn: string;
  section_id: number;
  birthdate: string;
  age: number;
  gender: string;
  contact_number: string;
  guardian_name: string;
  guardian_email: string;
  address: string;
  status: StudentStatus;
  teacher_subject_ids: number[];
  newPassword: string;
}

interface NewStudentDraft {
  student_id: string;
  lrn: string;
  firstname: string;
  middlename: string;
  lastname: string;
  suffix: string;
  section_id: number;
  initialPassword: string;
  guardian_name: string;
  guardian_email: string;
  contact_number: string;
  birthdate: string;
  age: number;
  gender: string;
  address: string;
  teacher_subject_ids: number[];
}

function buildEditDraft(student: TeacherStudentRecord): EditDraft {
  return {
    firstname: student.firstname,
    middlename: student.middlename,
    lastname: student.lastname,
    suffix: student.suffix,
    lrn: student.lrn,
    section_id: student.section_id,
    birthdate: student.birthdate,
    age: student.age,
    gender: student.gender,
    contact_number: student.contact_number,
    guardian_name: student.guardian_name,
    guardian_email: student.guardian_email,
    address: student.address,
    status: student.status,
    teacher_subject_ids: [...student.teacher_subject_ids],
    newPassword: '',
  };
}

function buildEmptyNewStudentDraft(nextSeq: number): NewStudentDraft {
  return {
    student_id: `STUD-2026-00${nextSeq}`,
    lrn: '',
    firstname: '',
    middlename: '',
    lastname: '',
    suffix: '',
    section_id: 12,
    initialPassword: '',
    guardian_name: '',
    guardian_email: '',
    contact_number: '',
    birthdate: '',
    age: 17,
    gender: 'Unspecified',
    address: '',
    teacher_subject_ids: [124],
  };
}

export function TeacherStudentsView() {
  const captionId = useId();
  const dossierTitleId = useId();
  const addTitleId = useId();
  const rowsPerPageId = useId();

  const [students, setStudents] = useState<TeacherStudentRecord[]>(INITIAL_STUDENTS);
  const [notificationsLog, setNotificationsLog] = useState<SimulatedNotification[]>([]);

  // Filter, Sort & Pagination state
  const [subjectFilter, setSubjectFilter] = useState<string>('all');
  const [sectionFilter, setSectionFilter] = useState<string>('all');
  const [statusFilter, setStatusFilter] = useState<StatusFilter>('all');
  const [searchQuery, setSearchQuery] = useState<string>('');
  const [sortColumn, setSortColumn] = useState<SortColumn>('student');
  const [sortDirection, setSortDirection] = useState<SortDirection>('ascending');
  const [currentPage, setCurrentPage] = useState<number>(1);
  const [pageSize, setPageSize] = useState<number>(5);
  const [statusAnnouncement, setStatusAnnouncement] = useState<string>('');

  // Dossier <dialog> state
  const dossierDialogRef = useRef<HTMLDialogElement | null>(null);
  const [selectedStudentId, setSelectedStudentId] = useState<number | null>(null);
  const [dossierTab, setDossierTab] = useState<DossierTab>('overview');
  const [nudgeMessage, setNudgeMessage] = useState<string>(
    'Friendly reminder to complete your pending module and performance task.',
  );
  const [copiedEmail, setCopiedEmail] = useState<boolean>(false);
  const [editDraft, setEditDraft] = useState<EditDraft | null>(null);
  const [editError, setEditError] = useState<string>('');

  // Add Student <dialog> state
  const addDialogRef = useRef<HTMLDialogElement | null>(null);
  const [addMode, setAddMode] = useState<AddMode>('existing');
  const [selectedExistingId, setSelectedExistingId] = useState<string>('73');
  const [existingSubjects, setExistingSubjects] = useState<number[]>([124]);
  const [newDraft, setNewDraft] = useState<NewStudentDraft>(() => buildEmptyNewStudentDraft(920));
  const [addError, setAddError] = useState<string>('');

  // Toast feedback
  const [toastMessage, setToastMessage] = useState<string | null>(null);
  const toastTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const nextIdRef = useRef<number>(100);

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

  useDialogLightDismiss(dossierDialogRef);
  useDialogLightDismiss(addDialogRef);

  // Combobox options
  const subjectOptions: HoverComboboxOption[] = useMemo(
    () => [
      {
        value: 'all',
        label: `All My Subjects (${TEACHER_SUBJECTS.length})`,
        icon: RiBookOpenLine,
      },
      ...TEACHER_SUBJECTS.map((sub) => ({
        value: String(sub.teacher_subject_id),
        label: `${sub.subject} (${sub.strand})`,
        badge: students.filter((st) => st.teacher_subject_ids.includes(sub.teacher_subject_id)).length,
      })),
    ],
    [students],
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
        badge: students.filter((st) => st.section_id === sec.section_id).length,
      })),
    ],
    [students],
  );

  // Summary counts (single quiet inline strip)
  const summaryStats = useMemo(() => {
    const total = students.length;
    const active = students.filter((s) => s.status === 'active').length;
    const inactive = total - active;
    const attention = students.filter(
      (s) => s.status === 'active' && computeProgress(s) < 75,
    ).length;
    return { total, active, inactive, attention };
  }, [students]);

  // Filtered + Sorted list
  const visibleStudents = useMemo(() => {
    const q = searchQuery.trim().toLowerCase();

    const filtered = students.filter((s) => {
      if (subjectFilter !== 'all' && !s.teacher_subject_ids.includes(Number(subjectFilter))) {
        return false;
      }
      if (sectionFilter !== 'all' && String(s.section_id) !== sectionFilter) {
        return false;
      }
      if (statusFilter === 'active' && s.status !== 'active') return false;
      if (statusFilter === 'inactive' && s.status !== 'inactive') return false;
      if (
        statusFilter === 'attention' &&
        !(s.status === 'active' && computeProgress(s) < 75)
      ) {
        return false;
      }
      if (q) {
        const hay = `${formatRosterName(s)} ${s.firstname} ${s.lastname} ${s.student_id} ${s.lrn}`.toLowerCase();
        if (!hay.includes(q)) return false;
      }
      return true;
    });

    const factor = sortDirection === 'ascending' ? 1 : -1;
    return [...filtered].sort((a, b) => {
      if (sortColumn === 'progress') {
        return factor * (computeProgress(a) - computeProgress(b));
      }
      if (sortColumn === 'section') {
        return factor * COLLATOR.compare(formatSectionLabel(a.section_id), formatSectionLabel(b.section_id));
      }
      return factor * COLLATOR.compare(formatRosterName(a), formatRosterName(b));
    });
  }, [students, subjectFilter, sectionFilter, statusFilter, searchQuery, sortColumn, sortDirection]);

  // Pagination derived values (render-time safe, no useEffect setState)
  const totalPages = Math.max(1, Math.ceil(visibleStudents.length / pageSize));
  const safePage = Math.min(currentPage, totalPages);
  const pageStartIndex = (safePage - 1) * pageSize;
  const paginatedStudents = visibleStudents.slice(pageStartIndex, pageStartIndex + pageSize);
  const showingStart = visibleStudents.length === 0 ? 0 : pageStartIndex + 1;
  const showingEnd = Math.min(pageStartIndex + pageSize, visibleStudents.length);

  // Available unassigned students for Mode 1 (prevent duplicates)
  const availablePool = useMemo(() => {
    const enrolledIds = new Set(students.map((s) => s.id));
    return UNASSIGNED_POOL.filter((s) => !enrolledIds.has(s.id));
  }, [students]);

  const selectedStudent = useMemo(
    () => students.find((s) => s.id === selectedStudentId) ?? null,
    [students, selectedStudentId],
  );

  // Handlers
  const handleSort = (column: SortColumn, label: string) => {
    const nextDir: SortDirection =
      sortColumn === column && sortDirection === 'ascending' ? 'descending' : 'ascending';
    setSortColumn(column);
    setSortDirection(nextDir);
    setStatusAnnouncement(`Sorted by ${label}, ${nextDir}, ${visibleStudents.length} rows`);
  };

  const goToPage = (nextPage: number) => {
    const clamped = Math.max(1, Math.min(nextPage, totalPages));
    setCurrentPage(clamped);
    const start = visibleStudents.length === 0 ? 0 : (clamped - 1) * pageSize + 1;
    const end = Math.min(clamped * pageSize, visibleStudents.length);
    setStatusAnnouncement(
      `Page ${clamped} of ${totalPages}, showing ${start} to ${end} of ${visibleStudents.length} students`,
    );
  };

  const openDossier = (studentId: number, tab: DossierTab = 'overview') => {
    const target = students.find((s) => s.id === studentId);
    if (!target) return;
    setSelectedStudentId(studentId);
    setDossierTab(tab);
    setEditDraft(buildEditDraft(target));
    setEditError('');
    setCopiedEmail(false);
    setNudgeMessage('Friendly reminder to complete your pending module and performance task.');

    const dlg = dossierDialogRef.current;
    if (dlg && !dlg.open) {
      dlg.showModal();
    }
  };

  const closeDossier = () => {
    const dlg = dossierDialogRef.current;
    if (dlg?.open) dlg.close();
  };

  const handleSwitchDossierTab = (nextTab: DossierTab) => {
    setDossierTab(nextTab);
    if (nextTab === 'edit' && selectedStudent) {
      setEditDraft(buildEditDraft(selectedStudent));
      setEditError('');
    }
  };

  const handleToggleAccountStatus = (studentId: number) => {
    const target = students.find((s) => s.id === studentId);
    if (!target) return;
    const nextStatus: StudentStatus = target.status === 'active' ? 'inactive' : 'active';
    const updatedName = formatRosterName(target);

    setStudents((prev) =>
      prev.map((s) => (s.id === studentId ? { ...s, status: nextStatus } : s)),
    );
    if (selectedStudentId === studentId) {
      setEditDraft((prev) => (prev ? { ...prev, status: nextStatus } : prev));
    }
    showToast(
      nextStatus === 'inactive'
        ? `${updatedName} marked inactive. All grades and submissions are preserved.`
        : `${updatedName} reactivated.`,
    );
  };

  const handleSendNudge = (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedStudent) return;
    const trimmed = nudgeMessage.trim();
    if (!trimmed) {
      showToast('Enter a reminder message before sending.');
      return;
    }

    const now = new Date();
    const newNotification: SimulatedNotification = {
      notification_id: nextIdRef.current++,
      user_id: selectedStudent.id,
      title: 'Teacher Reminder',
      message: trimmed,
      type: 'teacher_nudge',
      status: 'unread',
      created_at: formatSqlTimestamp(now),
    };
    setNotificationsLog((prev) => [newNotification, ...prev]);
    showToast(`Reminder sent to ${selectedStudent.firstname} ${selectedStudent.lastname}.`);
  };

  const handleCopyEmail = async (email: string) => {
    try {
      if (navigator.clipboard?.writeText) {
        await navigator.clipboard.writeText(email);
      }
      setCopiedEmail(true);
      showToast(`Copied ${email}`);
      setTimeout(() => setCopiedEmail(false), 2000);
    } catch {
      showToast(`Guardian email: ${email}`);
    }
  };

  const handleSaveEdit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedStudent || !editDraft) return;
    if (editDraft.teacher_subject_ids.length === 0) {
      setEditError(
        'Keep at least one assigned subject. To pause a learner who dropped, switch status to Inactive instead.',
      );
      return;
    }
    if (!editDraft.firstname.trim() || !editDraft.lastname.trim()) {
      setEditError('First name and last name are required.');
      return;
    }

    setStudents((prev) =>
      prev.map((s) =>
        s.id === selectedStudent.id
          ? {
              ...s,
              firstname: editDraft.firstname.trim(),
              middlename: editDraft.middlename.trim(),
              lastname: editDraft.lastname.trim(),
              suffix: editDraft.suffix.trim(),
              lrn: editDraft.lrn.trim(),
              section_id: editDraft.section_id,
              birthdate: editDraft.birthdate,
              age: editDraft.age,
              gender: editDraft.gender,
              contact_number: editDraft.contact_number.trim(),
              guardian_name: editDraft.guardian_name.trim(),
              guardian_email: editDraft.guardian_email.trim(),
              address: editDraft.address.trim(),
              status: editDraft.status,
              teacher_subject_ids: [...editDraft.teacher_subject_ids],
            }
          : s,
      ),
    );

    setEditError('');
    setDossierTab('overview');
    const pwSuffix = editDraft.newPassword.trim() ? ' (password updated)' : '';
    showToast(`Saved ${editDraft.lastname}, ${editDraft.firstname}${pwSuffix}.`);
  };

  const openAddDialog = () => {
    setAddError('');
    const firstPool = availablePool[0];
    if (firstPool) {
      setAddMode('existing');
      setSelectedExistingId(String(firstPool.id));
    } else {
      setAddMode('new');
    }
    setExistingSubjects([124]);
    setNewDraft(buildEmptyNewStudentDraft(920 + students.length));
    addDialogRef.current?.showModal();
  };

  const closeAddDialog = () => {
    if (addDialogRef.current?.open) {
      addDialogRef.current.close();
    }
  };

  const handleEnrollExisting = (e: React.FormEvent) => {
    e.preventDefault();
    if (existingSubjects.length === 0) {
      setAddError('Select at least one subject.');
      return;
    }
    const targetId = Number(selectedExistingId);
    const poolMatch = UNASSIGNED_POOL.find((p) => p.id === targetId);
    if (!poolMatch) {
      setAddError('Selected student is no longer available in the unassigned pool.');
      return;
    }
    if (students.some((s) => s.id === poolMatch.id)) {
      setAddError('This student is already in your assigned roster.');
      return;
    }

    setStudents((prev) => [
      { ...poolMatch, teacher_subject_ids: [...existingSubjects] },
      ...prev,
    ]);
    closeAddDialog();
    showToast(`Enrolled ${formatRosterName(poolMatch)}.`);
  };

  const handleRegisterNew = (e: React.FormEvent) => {
    e.preventDefault();
    if (newDraft.teacher_subject_ids.length === 0) {
      setAddError('Select at least one subject.');
      return;
    }
    if (!newDraft.student_id.trim() || !newDraft.firstname.trim() || !newDraft.lastname.trim()) {
      setAddError('Student ID, First Name, and Last Name are required.');
      return;
    }
    if (newDraft.initialPassword.trim().length < 8) {
      setAddError('Initial password must be at least 8 characters.');
      return;
    }

    const now = new Date();
    const record: TeacherStudentRecord = {
      id: nextIdRef.current++,
      student_id: newDraft.student_id.trim(),
      section_id: newDraft.section_id,
      firstname: newDraft.firstname.trim(),
      middlename: newDraft.middlename.trim(),
      lastname: newDraft.lastname.trim(),
      suffix: newDraft.suffix.trim(),
      lrn: newDraft.lrn.trim() || '109800000000',
      birthdate: newDraft.birthdate || '2009-01-01',
      age: newDraft.birthdate ? computeAgeFromBirthdate(newDraft.birthdate, now) : newDraft.age,
      gender: newDraft.gender || 'Unspecified',
      contact_number: newDraft.contact_number.trim() || '—',
      guardian_name: newDraft.guardian_name.trim() || '—',
      guardian_email: newDraft.guardian_email.trim() || '—',
      address: newDraft.address.trim() || '—',
      status: 'active',
      created_at: formatSqlTimestamp(now),
      teacher_subject_ids: [...newDraft.teacher_subject_ids],
      lessons_done: 0,
      lessons_total: 8,
      quiz_avg: 0,
      pt_done: 0,
      pt_total: 2,
      pt_score: null,
      live_attended: 0,
      live_total: 6,
    };

    setStudents((prev) => [record, ...prev]);
    closeAddDialog();
    showToast(`Registered ${formatRosterName(record)} and assigned selected subjects.`);
  };

  const toggleSubjectSelection = (list: number[], id: number): number[] =>
    list.includes(id) ? list.filter((x) => x !== id) : [...list, id];

  const studentNotificationsCount = selectedStudent
    ? notificationsLog.filter((n) => n.user_id === selectedStudent.id).length
    : 0;

  return (
    <div className="space-y-4">
      {/* 1. Compact Header Strip */}
      <section className="flex flex-wrap items-end justify-between gap-4">
        <div className="space-y-1">
          <h3 className="text-2xl font-bold text-text font-serif tracking-tight">Students</h3>
          <p className="text-xs text-text3">
            Manage learners assigned to your classes. Select any learner to inspect progress, guardian contact, or subject assignments.
          </p>
        </div>

        <button
          type="button"
          onClick={openAddDialog}
          className="btn btn-primary btn-sm px-3.5 py-2 text-xs font-semibold rounded-xl shadow-xs"
        >
          <RiAddLine className="size-4" aria-hidden="true" />
          <span>Add Student</span>
        </button>
      </section>

      {/* 2. Simple Summary Card */}
      <section aria-label="Roster summary" className="bg-bg2 border border-border rounded-2xl overflow-hidden">
        <dl className="grid grid-cols-2 sm:grid-cols-4 divide-y sm:divide-y-0 sm:divide-x divide-border">
          <div className="px-4 py-3">
            <dt className="text-xs text-text3">Assigned</dt>
            <dd className="text-xl font-semibold tabular-nums text-text mt-0.5">
              {summaryStats.total}
            </dd>
          </div>
          <div className="px-4 py-3">
            <dt className="text-xs text-text3">Active</dt>
            <dd className="text-xl font-semibold tabular-nums text-text mt-0.5">
              {summaryStats.active}
            </dd>
          </div>
          <div className="px-4 py-3">
            <dt className="text-xs text-text3">Inactive</dt>
            <dd className="text-xl font-semibold tabular-nums text-text mt-0.5">
              {summaryStats.inactive}
            </dd>
          </div>
          <div className="px-4 py-3">
            <dt className="text-xs text-text3">Need Attention</dt>
            <dd
              className={cx(
                'text-xl font-semibold tabular-nums mt-0.5',
                summaryStats.attention > 0 ? 'text-amber' : 'text-text',
              )}
            >
              {summaryStats.attention}
            </dd>
          </div>
        </dl>
      </section>

      {/* 3. Single-Row Intentional Filter Bar */}
      <section
        aria-label="Filter students"
        className="p-3 rounded-2xl bg-bg2 border border-border flex flex-wrap items-center gap-2.5"
      >
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

        {/* Quiet monochrome segmented status toggle */}
        <div
          role="group"
          aria-label="Filter by student status"
          className="inline-flex rounded-xl bg-bg3 p-0.5 border border-border text-xs font-medium"
        >
          {(
            [
              { id: 'all', label: 'All' },
              { id: 'active', label: 'Active' },
              { id: 'attention', label: 'Needs Attention' },
              { id: 'inactive', label: 'Inactive' },
            ] as const
          ).map((tab) => {
            const active = statusFilter === tab.id;
            return (
              <button
                key={tab.id}
                type="button"
                aria-pressed={active}
                onClick={() => {
                  setStatusFilter(tab.id);
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

      {/* 3. Calm 5-Column Semantic Data Table */}
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
              Assigned students roster scoped to the teacher&apos;s subjects
            </caption>
            <colgroup>
              <col style={{ width: '30%' }} />
              <col style={{ width: '17%' }} />
              <col style={{ width: '24%' }} />
              <col style={{ width: '16%' }} />
              <col style={{ width: '13%' }} />
            </colgroup>
            <thead>
              <tr>
                <th
                  scope="col"
                  data-type="text"
                  aria-sort={sortColumn === 'student' ? sortDirection : undefined}
                >
                  <button
                    type="button"
                    onClick={() => handleSort('student', 'Student')}
                  >
                    <span>Student</span>
                    <span className="teacher-roster-sort-icon" aria-hidden="true">
                      <RiArrowUpLine className="size-3.5" />
                    </span>
                  </button>
                </th>
                <th
                  scope="col"
                  data-type="text"
                  aria-sort={sortColumn === 'section' ? sortDirection : undefined}
                >
                  <button
                    type="button"
                    onClick={() => handleSort('section', 'Section & Strand')}
                  >
                    <span>Section &amp; Strand</span>
                    <span className="teacher-roster-sort-icon" aria-hidden="true">
                      <RiArrowUpLine className="size-3.5" />
                    </span>
                  </button>
                </th>
                <th scope="col">Assigned Subjects</th>
                <th
                  scope="col"
                  data-type="number"
                  aria-sort={sortColumn === 'progress' ? sortDirection : undefined}
                >
                  <button
                    type="button"
                    onClick={() => handleSort('progress', 'Progress')}
                  >
                    <span>Progress</span>
                    <span className="teacher-roster-sort-icon" aria-hidden="true">
                      <RiArrowUpLine className="size-3.5" />
                    </span>
                  </button>
                </th>
                <th scope="col" className="text-end">
                  Status
                </th>
              </tr>
            </thead>
            <tbody>
              {paginatedStudents.length === 0 ? (
                <tr>
                  <td colSpan={5} className="py-10 text-center text-xs text-text3">
                    No assigned learners match the current filters.
                  </td>
                </tr>
              ) : (
                paginatedStudents.map((s, idx) => {
                  const pct = computeProgress(s);
                  const progressTone =
                    s.status === 'inactive'
                      ? 'inactive'
                      : pct >= 80
                        ? 'high'
                        : pct >= 65
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
                  const rosterName = formatRosterName(s);
                  const allSubjects = formatAllSubjectNames(s.teacher_subject_ids);

                  return (
                    <tr
                      key={s.id}
                      onClick={() => openDossier(s.id, 'overview')}
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
                                openDossier(s.id, 'overview');
                              }}
                              className="text-xs font-semibold text-text single-line-cell text-start hover:underline cursor-pointer outline-none focus-visible:ring-2 focus-visible:ring-accent rounded"
                            >
                              {rosterName}
                            </button>
                            <div className="text-[11px] text-text3 font-mono tabular-nums single-line-cell">
                              {s.student_id} · {s.lrn}
                            </div>
                          </div>
                        </div>
                      </th>

                      <td data-label="Section & Strand" data-value={formatSectionLabel(s.section_id)}>
                        <div className="text-end sm:text-start">
                          <div className="text-xs font-medium text-text">
                            {formatSectionLabel(s.section_id)}
                          </div>
                          <div className="text-[11px] text-text3">
                            {formatStrandLabel(s.teacher_subject_ids)}
                          </div>
                        </div>
                      </td>

                      <td data-label="Assigned Subjects">
                        <span
                          className="text-xs text-text single-line-cell max-w-full"
                          title={allSubjects}
                        >
                          {formatSubjectsSummary(s.teacher_subject_ids)}
                        </span>
                      </td>

                      <td data-label="Progress" data-value={pct}>
                        <div className="space-y-1.5 min-w-[110px]">
                          <div className="flex items-center justify-between gap-2 text-xs tabular-nums">
                            <span className={progressTextClass}>{pct}%</span>
                            <span className="text-text3">
                              {s.lessons_done}/{s.lessons_total} les
                            </span>
                          </div>
                          <progress
                            className="roster-progress"
                            data-tone={progressTone}
                            value={pct}
                            max={100}
                            aria-label={`${rosterName} progress ${pct}%`}
                          />
                        </div>
                      </td>

                      <td data-label="Status">
                        <div className="flex items-center justify-end gap-2">
                          <span
                            className={cx(
                              'inline-flex items-center gap-1.5 text-xs text-text2',
                              s.status === 'inactive' && 'opacity-75',
                            )}
                          >
                            <span
                              className={cx(
                                'size-1.5 rounded-full',
                                s.status === 'active' ? 'bg-green' : 'bg-text3',
                              )}
                              aria-hidden="true"
                            />
                            {s.status === 'active' ? 'Active' : 'Inactive'}
                          </span>
                          <RowOverflowMenu
                            student={s}
                            onOpenOverview={(id) => openDossier(id, 'overview')}
                            onOpenEdit={(id) => openDossier(id, 'edit')}
                            onToggleStatus={handleToggleAccountStatus}
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

        <div className="px-4 py-2.5 bg-bg3 border-t border-border flex flex-wrap items-center justify-between gap-3 text-xs text-text3">
          <div className="flex flex-wrap items-center gap-3">
            <span className="tabular-nums">
              {visibleStudents.length === 0
                ? 'Showing 0 of 0 students'
                : `Showing ${showingStart}–${showingEnd} of ${visibleStudents.length} students`}
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

          <nav aria-label="Roster pagination" className="inline-flex items-center gap-1">
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
       * 4. Native <dialog>: Slide-Over Student Dossier Drawer
       * ==================================================================== */}
      <dialog
        ref={dossierDialogRef}
        className="excelearn-drawer-dialog"
        {...({ closedby: 'any' } as Record<string, string>)}
        aria-labelledby={dossierTitleId}
        onClose={() => setSelectedStudentId(null)}
      >
        {selectedStudent && (
          <>
            {/* Header */}
            <div className="px-5 py-4 border-b border-border flex items-start justify-between gap-3 shrink-0">
              <div className="flex items-center gap-3 min-w-0">
                <div className="size-10 rounded-full bg-bg3 border border-border flex items-center justify-center text-xs font-semibold text-text2 shrink-0">
                  {getInitials(selectedStudent)}
                </div>
                <div className="min-w-0">
                  <h4 id={dossierTitleId} className="text-base font-semibold text-text truncate">
                    {formatRosterName(selectedStudent)}
                  </h4>
                  <p className="text-xs text-text3 font-mono truncate tabular-nums">
                    {selectedStudent.student_id} · LRN {selectedStudent.lrn} ·{' '}
                    {formatSectionLabel(selectedStudent.section_id)}
                  </p>
                </div>
              </div>
              <button
                type="button"
                onClick={closeDossier}
                aria-label="Close student dossier"
                className="p-1.5 rounded-lg text-text3 hover:text-text hover:bg-bg3 cursor-pointer shrink-0 outline-none focus-visible:ring-2 focus-visible:ring-accent"
              >
                <RiCloseLine className="size-4" aria-hidden="true" />
              </button>
            </div>

            {/* 3 Tabs */}
            <div
              role="tablist"
              aria-label="Student record sections"
              className="px-5 pt-3 border-b border-border flex items-center gap-5 text-xs font-medium shrink-0"
            >
              {(
                [
                  { id: 'overview', label: 'Overview' },
                  { id: 'profile', label: 'Profile & Guardian' },
                  { id: 'edit', label: 'Edit & Subjects' },
                ] as const
              ).map((tab) => {
                const isSelected = dossierTab === tab.id;
                return (
                  <button
                    key={tab.id}
                    type="button"
                    role="tab"
                    aria-selected={isSelected}
                    onClick={() => handleSwitchDossierTab(tab.id)}
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
              {dossierTab === 'overview' && (
                <>
                  <div className="grid grid-cols-2 gap-3">
                    <div className="p-3 rounded-xl bg-bg3 border border-border">
                      <div className="text-text2">Lessons Done</div>
                      <div className="text-lg font-semibold tabular-nums text-text mt-0.5">
                        {selectedStudent.lessons_done} / {selectedStudent.lessons_total}
                      </div>
                    </div>

                    <div className="p-3 rounded-xl bg-bg3 border border-border">
                      <div className="text-text2">Quiz Average</div>
                      <div
                        className={cx(
                          'text-lg font-semibold tabular-nums mt-0.5',
                          selectedStudent.status === 'active' && selectedStudent.quiz_avg < 75
                            ? 'text-amber'
                            : 'text-text',
                        )}
                      >
                        {selectedStudent.quiz_avg}%
                      </div>
                    </div>

                    <div className="p-3 rounded-xl bg-bg3 border border-border">
                      <div className="text-text2">Performance Tasks</div>
                      <div className="text-lg font-semibold tabular-nums text-text mt-0.5">
                        {selectedStudent.pt_done} / {selectedStudent.pt_total}{' '}
                        <span className="text-xs font-medium text-text2">
                          {selectedStudent.pt_score != null
                            ? `(${selectedStudent.pt_score} pts)`
                            : '(Missing)'}
                        </span>
                      </div>
                    </div>

                    <div className="p-3 rounded-xl bg-bg3 border border-border">
                      <div className="text-text2">Virtual Attendance</div>
                      <div className="text-lg font-semibold tabular-nums text-text mt-0.5">
                        {selectedStudent.live_attended} / {selectedStudent.live_total} sessions
                      </div>
                    </div>
                  </div>

                  <div className="space-y-2">
                    <div className="font-semibold text-text">Enrolled Subjects</div>
                    <ul className="divide-y divide-border rounded-xl border border-border overflow-hidden">
                      {selectedStudent.teacher_subject_ids.map((tsid) => {
                        const sub = SUBJECT_BY_ID.get(tsid);
                        if (!sub) return null;
                        return (
                          <li
                            key={tsid}
                            className="px-3 py-2.5 flex items-center justify-between gap-2 bg-bg2"
                          >
                            <span className="font-medium text-text">{sub.subject}</span>
                            <span className="text-text3 shrink-0">
                              {sub.strand} · {sub.track}
                            </span>
                          </li>
                        );
                      })}
                    </ul>
                  </div>

                  <form
                    onSubmit={handleSendNudge}
                    className="p-3.5 rounded-xl bg-bg3 border border-border space-y-2"
                  >
                    <div className="flex items-center justify-between gap-2">
                      <div className="font-semibold text-text">Quick Nudge</div>
                      {studentNotificationsCount > 0 && (
                        <span className="text-[11px] text-text3 tabular-nums">
                          {studentNotificationsCount} sent this session
                        </span>
                      )}
                    </div>
                    <p className="text-text2">
                      Send a calm reminder directly to {selectedStudent.firstname}&apos;s portal inbox.
                    </p>
                    <div className="flex gap-2">
                      <input
                        type="text"
                        value={nudgeMessage}
                        onChange={(e) => setNudgeMessage(e.target.value)}
                        aria-label="Notification message"
                        className="flex-1 px-2.5 py-1.5 rounded-lg bg-bg2 border border-border text-xs text-text outline-none focus:border-accent"
                      />
                      <button
                        type="submit"
                        className="btn btn-primary btn-sm px-3 py-1.5 rounded-lg text-xs font-semibold shrink-0"
                      >
                        <RiSendPlaneLine className="size-3.5" aria-hidden="true" />
                        <span>Send</span>
                      </button>
                    </div>
                  </form>
                </>
              )}

              {dossierTab === 'profile' && (
                <div className="space-y-4">
                  <div className="space-y-2">
                    <div className="font-semibold text-text3 uppercase tracking-wider">
                      Learner Record
                    </div>
                    <dl className="divide-y divide-border rounded-xl border border-border overflow-hidden">
                      <div className="px-3 py-2 flex justify-between gap-4">
                        <dt className="text-text2">Full Name</dt>
                        <dd className="font-medium text-text text-end">
                          {formatFullName(selectedStudent)}
                        </dd>
                      </div>
                      <div className="px-3 py-2 flex justify-between gap-4">
                        <dt className="text-text2">Student ID</dt>
                        <dd className="font-mono text-text">{selectedStudent.student_id}</dd>
                      </div>
                      <div className="px-3 py-2 flex justify-between gap-4">
                        <dt className="text-text2">LRN</dt>
                        <dd className="font-mono text-text">{selectedStudent.lrn}</dd>
                      </div>
                      <div className="px-3 py-2 flex justify-between gap-4">
                        <dt className="text-text2">Section</dt>
                        <dd className="text-text">{formatSectionLabel(selectedStudent.section_id)}</dd>
                      </div>
                      <div className="px-3 py-2 flex justify-between gap-4">
                        <dt className="text-text2">Birthdate / Age / Gender</dt>
                        <dd className="tabular-nums text-text text-end">
                          {selectedStudent.birthdate || '—'} ({selectedStudent.age} yrs · {selectedStudent.gender})
                        </dd>
                      </div>
                      <div className="px-3 py-2 flex justify-between gap-4">
                        <dt className="text-text2">Address</dt>
                        <dd className="text-text text-end">{selectedStudent.address || '—'}</dd>
                      </div>
                      <div className="px-3 py-2 flex justify-between gap-4">
                        <dt className="text-text2">Account Status</dt>
                        <dd className="inline-flex items-center gap-1.5 text-text">
                          <span
                            className={cx(
                              'size-1.5 rounded-full',
                              selectedStudent.status === 'active' ? 'bg-green' : 'bg-text3',
                            )}
                            aria-hidden="true"
                          />
                          {selectedStudent.status === 'active' ? 'Active' : 'Inactive'}
                        </dd>
                      </div>
                    </dl>
                  </div>

                  <div className="space-y-2">
                    <div className="font-semibold text-text3 uppercase tracking-wider">
                      Guardian &amp; Contact
                    </div>
                    <dl className="divide-y divide-border rounded-xl border border-border overflow-hidden">
                      <div className="px-3 py-2 flex justify-between gap-4">
                        <dt className="text-text2">Guardian Name</dt>
                        <dd className="font-medium text-text text-end">
                          {selectedStudent.guardian_name || '—'}
                        </dd>
                      </div>
                      <div className="px-3 py-2 flex justify-between gap-4">
                        <dt className="text-text2">Contact Number</dt>
                        <dd className="font-mono text-text">{selectedStudent.contact_number || '—'}</dd>
                      </div>
                      <div className="px-3 py-2 flex items-center justify-between gap-3">
                        <dt className="text-text2 shrink-0">Guardian Email</dt>
                        <dd className="flex items-center gap-2 min-w-0">
                          <span className="font-mono text-accent truncate">
                            {selectedStudent.guardian_email || '—'}
                          </span>
                          {selectedStudent.guardian_email && selectedStudent.guardian_email !== '—' && (
                            <button
                              type="button"
                              onClick={() => handleCopyEmail(selectedStudent.guardian_email)}
                              aria-label="Copy guardian email"
                              className="p-1 rounded-md border border-border bg-bg3 text-text2 hover:text-text cursor-pointer shrink-0"
                            >
                              {copiedEmail ? (
                                <RiCheckLine className="size-3.5 text-green" aria-hidden="true" />
                              ) : (
                                <RiFileCopyLine className="size-3.5" aria-hidden="true" />
                              )}
                            </button>
                          )}
                        </dd>
                      </div>
                    </dl>
                  </div>
                </div>
              )}

              {dossierTab === 'edit' && editDraft && (
                <form onSubmit={handleSaveEdit} className="space-y-4">
                  {editError && (
                    <div
                      role="alert"
                      className="p-2.5 rounded-lg border border-amber/40 bg-amber-bg text-amber text-xs"
                    >
                      {editError}
                    </div>
                  )}

                  <div className="grid grid-cols-2 gap-2.5">
                    <div>
                      <label className="block font-medium text-text2 mb-1">First Name *</label>
                      <input
                        type="text"
                        required
                        value={editDraft.firstname}
                        onChange={(e) =>
                          setEditDraft((d) => (d ? { ...d, firstname: e.target.value } : d))
                        }
                        className="w-full px-2.5 py-1.5 rounded-lg bg-bg3 border border-border text-text outline-none focus:border-accent"
                      />
                    </div>
                    <div>
                      <label className="block font-medium text-text2 mb-1">Middle Name</label>
                      <input
                        type="text"
                        value={editDraft.middlename}
                        onChange={(e) =>
                          setEditDraft((d) => (d ? { ...d, middlename: e.target.value } : d))
                        }
                        className="w-full px-2.5 py-1.5 rounded-lg bg-bg3 border border-border text-text outline-none focus:border-accent"
                      />
                    </div>
                    <div>
                      <label className="block font-medium text-text2 mb-1">Last Name *</label>
                      <input
                        type="text"
                        required
                        value={editDraft.lastname}
                        onChange={(e) =>
                          setEditDraft((d) => (d ? { ...d, lastname: e.target.value } : d))
                        }
                        className="w-full px-2.5 py-1.5 rounded-lg bg-bg3 border border-border text-text outline-none focus:border-accent"
                      />
                    </div>
                    <div>
                      <label className="block font-medium text-text2 mb-1">Suffix</label>
                      <input
                        type="text"
                        value={editDraft.suffix}
                        placeholder="Jr., III"
                        onChange={(e) =>
                          setEditDraft((d) => (d ? { ...d, suffix: e.target.value } : d))
                        }
                        className="w-full px-2.5 py-1.5 rounded-lg bg-bg3 border border-border text-text outline-none focus:border-accent"
                      />
                    </div>
                    <div>
                      <label className="block font-medium text-text2 mb-1">LRN</label>
                      <input
                        type="text"
                        value={editDraft.lrn}
                        onChange={(e) =>
                          setEditDraft((d) => (d ? { ...d, lrn: e.target.value } : d))
                        }
                        className="w-full px-2.5 py-1.5 rounded-lg bg-bg3 border border-border text-text font-mono outline-none focus:border-accent"
                      />
                    </div>
                    <div>
                      <label className="block font-medium text-text2 mb-1">Section</label>
                      <select
                        value={editDraft.section_id}
                        onChange={(e) =>
                          setEditDraft((d) =>
                            d ? { ...d, section_id: Number(e.target.value) } : d,
                          )
                        }
                        className="w-full px-2.5 py-1.5 rounded-lg bg-bg3 border border-border text-text outline-none focus:border-accent cursor-pointer"
                      >
                        {SECTIONS.map((sec) => (
                          <option key={sec.section_id} value={sec.section_id}>
                            {sec.grade_level} – {sec.section}
                          </option>
                        ))}
                      </select>
                    </div>
                    <div>
                      <label className="block font-medium text-text2 mb-1">
                        Birthdate (Age: {editDraft.age})
                      </label>
                      <input
                        type="date"
                        value={editDraft.birthdate}
                        onChange={(e) => {
                          const nextDate = e.target.value;
                          const nextAge = computeAgeFromBirthdate(nextDate, new Date());
                          setEditDraft((d) =>
                            d ? { ...d, birthdate: nextDate, age: nextAge } : d,
                          );
                        }}
                        className="w-full px-2.5 py-1.5 rounded-lg bg-bg3 border border-border text-text outline-none focus:border-accent"
                      />
                    </div>
                    <div>
                      <label className="block font-medium text-text2 mb-1">Gender</label>
                      <select
                        value={editDraft.gender}
                        onChange={(e) =>
                          setEditDraft((d) => (d ? { ...d, gender: e.target.value } : d))
                        }
                        className="w-full px-2.5 py-1.5 rounded-lg bg-bg3 border border-border text-text outline-none focus:border-accent cursor-pointer"
                      >
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Unspecified">Unspecified</option>
                      </select>
                    </div>
                    <div>
                      <label className="block font-medium text-text2 mb-1">Contact Number</label>
                      <input
                        type="text"
                        value={editDraft.contact_number}
                        onChange={(e) =>
                          setEditDraft((d) => (d ? { ...d, contact_number: e.target.value } : d))
                        }
                        className="w-full px-2.5 py-1.5 rounded-lg bg-bg3 border border-border text-text font-mono outline-none focus:border-accent"
                      />
                    </div>
                    <div>
                      <label className="block font-medium text-text2 mb-1">Guardian Name</label>
                      <input
                        type="text"
                        value={editDraft.guardian_name}
                        onChange={(e) =>
                          setEditDraft((d) => (d ? { ...d, guardian_name: e.target.value } : d))
                        }
                        className="w-full px-2.5 py-1.5 rounded-lg bg-bg3 border border-border text-text outline-none focus:border-accent"
                      />
                    </div>
                    <div className="col-span-2">
                      <label className="block font-medium text-text2 mb-1">Guardian Email</label>
                      <input
                        type="email"
                        value={editDraft.guardian_email}
                        onChange={(e) =>
                          setEditDraft((d) => (d ? { ...d, guardian_email: e.target.value } : d))
                        }
                        className="w-full px-2.5 py-1.5 rounded-lg bg-bg3 border border-border text-text outline-none focus:border-accent"
                      />
                    </div>
                    <div className="col-span-2">
                      <label className="block font-medium text-text2 mb-1">Address</label>
                      <input
                        type="text"
                        value={editDraft.address}
                        onChange={(e) =>
                          setEditDraft((d) => (d ? { ...d, address: e.target.value } : d))
                        }
                        className="w-full px-2.5 py-1.5 rounded-lg bg-bg3 border border-border text-text outline-none focus:border-accent"
                      />
                    </div>
                  </div>

                  {/* Assigned Subjects */}
                  <fieldset className="space-y-1.5">
                    <legend className="font-semibold text-text3 uppercase tracking-wider mb-1">
                      Assigned Subjects
                    </legend>
                    {TEACHER_SUBJECTS.map((sub) => {
                      const checked = editDraft.teacher_subject_ids.includes(
                        sub.teacher_subject_id,
                      );
                      return (
                        <label
                          key={sub.teacher_subject_id}
                          className="flex items-center gap-2.5 p-2.5 rounded-xl border border-border bg-bg3/50 hover:bg-bg3 cursor-pointer"
                        >
                          <input
                            type="checkbox"
                            checked={checked}
                            onChange={() =>
                              setEditDraft((d) =>
                                d
                                  ? {
                                      ...d,
                                      teacher_subject_ids: toggleSubjectSelection(
                                        d.teacher_subject_ids,
                                        sub.teacher_subject_id,
                                      ),
                                    }
                                  : d,
                              )
                            }
                            className="accent-accent"
                          />
                          <span className="text-text font-medium">{sub.subject}</span>
                          <span className="text-text3 ms-auto">
                            {sub.strand} · {sub.track}
                          </span>
                        </label>
                      );
                    })}
                  </fieldset>

                  {/* Optional Password Reset */}
                  <div>
                    <label className="block font-medium text-text2 mb-1">
                      Reset Student Password (Optional)
                    </label>
                    <input
                      type="password"
                      value={editDraft.newPassword}
                      onChange={(e) =>
                        setEditDraft((d) => (d ? { ...d, newPassword: e.target.value } : d))
                      }
                      placeholder="Leave blank to keep existing password"
                      className="w-full px-2.5 py-1.5 rounded-lg bg-bg3 border border-border text-text outline-none focus:border-accent"
                    />
                  </div>

                  {/* Account Status & Non-Destructive Deactivation */}
                  <div className="p-3 rounded-xl bg-bg3 border border-border space-y-2">
                    <div className="flex items-center justify-between gap-3">
                      <div>
                        <div className="font-semibold text-text">
                          Account Status ({editDraft.status === 'active' ? 'Active' : 'Inactive'})
                        </div>
                        <p className="text-text3 mt-0.5">
                          Deactivating preserves all grades, submissions, and attendance logs.
                        </p>
                      </div>
                      <button
                        type="button"
                        onClick={() =>
                          setEditDraft((d) =>
                            d
                              ? { ...d, status: d.status === 'active' ? 'inactive' : 'active' }
                              : d,
                          )
                        }
                        className={cx(
                          'px-3 py-1.5 rounded-lg border text-xs font-semibold cursor-pointer shrink-0 transition-colors',
                          editDraft.status === 'active'
                            ? 'border-amber/40 bg-amber-bg text-amber hover:opacity-90'
                            : 'border-green/40 bg-green-bg text-green hover:opacity-90',
                        )}
                      >
                        {editDraft.status === 'active'
                          ? 'Deactivate Account'
                          : 'Reactivate Account'}
                      </button>
                    </div>
                  </div>

                  <div className="pt-3 border-t border-border flex items-center justify-end gap-2">
                    <button
                      type="button"
                      onClick={() => setDossierTab('overview')}
                      className="btn btn-ghost btn-sm px-3.5 py-2 rounded-xl text-xs"
                    >
                      Cancel
                    </button>
                    <button
                      type="submit"
                      className="btn btn-primary btn-sm px-4 py-2 rounded-xl text-xs font-semibold"
                    >
                      Save Changes
                    </button>
                  </div>
                </form>
              )}
            </div>
          </>
        )}
      </dialog>

      {/* ====================================================================
       * 5. Native <dialog>: Dual-Mode Add Student Modal
       * ==================================================================== */}
      <dialog
        ref={addDialogRef}
        className="excelearn-center-dialog"
        {...({ closedby: 'any' } as Record<string, string>)}
        aria-labelledby={addTitleId}
        onClose={() => setAddError('')}
      >
        <div className="px-5 py-4 border-b border-border flex items-center justify-between gap-3 shrink-0">
          <div>
            <h4 id={addTitleId} className="text-base font-semibold text-text">
              Add Student to Class
            </h4>
            <p className="text-xs text-text3 mt-0.5">
              Enroll an existing learner or register a new student account.
            </p>
          </div>
          <button
            type="button"
            onClick={closeAddDialog}
            aria-label="Close add student dialog"
            className="p-1.5 rounded-lg text-text3 hover:text-text hover:bg-bg3 cursor-pointer outline-none focus-visible:ring-2 focus-visible:ring-accent"
          >
            <RiCloseLine className="size-4" aria-hidden="true" />
          </button>
        </div>

        {/* Dual-Mode Switcher */}
        <div className="px-5 pt-4 shrink-0">
          <div className="grid grid-cols-2 gap-1 p-1 rounded-xl bg-bg3 border border-border text-xs font-medium">
            <button
              type="button"
              onClick={() => {
                setAddMode('existing');
                setAddError('');
              }}
              className={cx(
                'py-1.5 rounded-lg transition-colors cursor-pointer inline-flex items-center justify-center gap-1.5',
                addMode === 'existing'
                  ? 'bg-bg2 text-text border border-border shadow-2xs font-semibold'
                  : 'text-text2 hover:text-text border border-transparent',
              )}
            >
              <RiGroupLine className="size-3.5" aria-hidden="true" />
              <span>Enroll Existing Student</span>
            </button>
            <button
              type="button"
              onClick={() => {
                setAddMode('new');
                setAddError('');
              }}
              className={cx(
                'py-1.5 rounded-lg transition-colors cursor-pointer inline-flex items-center justify-center gap-1.5',
                addMode === 'new'
                  ? 'bg-bg2 text-text border border-border shadow-2xs font-semibold'
                  : 'text-text2 hover:text-text border border-transparent',
              )}
            >
              <RiUserAddLine className="size-3.5" aria-hidden="true" />
              <span>Register New Student</span>
            </button>
          </div>
        </div>

        {addError && (
          <div className="px-5 pt-3">
            <div
              role="alert"
              className="p-2.5 rounded-lg border border-amber/40 bg-amber-bg text-amber text-xs"
            >
              {addError}
            </div>
          </div>
        )}

        {addMode === 'existing' ? (
          <form onSubmit={handleEnrollExisting} className="p-5 space-y-4 overflow-y-auto flex-1 text-xs">
            {availablePool.length === 0 ? (
              <div className="p-4 rounded-xl bg-bg3 border border-border text-center space-y-2">
                <p className="text-text2">
                  All unassigned school-wide students in the directory have been enrolled in your roster.
                </p>
                <button
                  type="button"
                  onClick={() => setAddMode('new')}
                  className="text-accent font-semibold hover:underline cursor-pointer"
                >
                  Switch to Register New Student
                </button>
              </div>
            ) : (
              <>
                <div className="space-y-1.5">
                  <label className="block font-medium text-text">
                    Select Unassigned Student
                  </label>
                  <select
                    value={selectedExistingId}
                    onChange={(e) => setSelectedExistingId(e.target.value)}
                    required
                    className="w-full px-3 py-2 rounded-xl bg-bg3 border border-border text-xs text-text outline-none focus:border-accent cursor-pointer"
                  >
                    {availablePool.map((poolStudent) => (
                      <option key={poolStudent.id} value={String(poolStudent.id)}>
                        {poolStudent.student_id} · {poolStudent.firstname} {poolStudent.lastname} (
                        {formatSectionLabel(poolStudent.section_id)} · LRN {poolStudent.lrn})
                      </option>
                    ))}
                  </select>
                </div>

                <fieldset className="space-y-2">
                  <legend className="font-medium text-text mb-1">
                    Assign to Subject(s) *
                  </legend>
                  {TEACHER_SUBJECTS.map((sub) => (
                    <label
                      key={sub.teacher_subject_id}
                      className="flex items-center gap-2.5 p-2.5 rounded-xl border border-border bg-bg3/50 hover:bg-bg3 cursor-pointer"
                    >
                      <input
                        type="checkbox"
                        checked={existingSubjects.includes(sub.teacher_subject_id)}
                        onChange={() =>
                          setExistingSubjects((prev) =>
                            toggleSubjectSelection(prev, sub.teacher_subject_id),
                          )
                        }
                        className="accent-accent"
                      />
                      <span className="text-text font-medium">{sub.subject}</span>
                      <span className="text-text3 ms-auto">
                        {sub.strand} · {sub.track}
                      </span>
                    </label>
                  ))}
                </fieldset>
              </>
            )}

            <div className="pt-2 border-t border-border flex items-center justify-end gap-2">
              <button
                type="button"
                onClick={closeAddDialog}
                className="btn btn-ghost btn-sm px-3.5 py-2 rounded-xl text-xs"
              >
                Cancel
              </button>
              {availablePool.length > 0 && (
                <button
                  type="submit"
                  className="btn btn-primary btn-sm px-4 py-2 rounded-xl text-xs font-semibold"
                >
                  Enroll Student
                </button>
              )}
            </div>
          </form>
        ) : (
          <form onSubmit={handleRegisterNew} className="p-5 space-y-4 overflow-y-auto flex-1 text-xs">
            {/* Group 1: Learner Identity & Initial Password */}
            <div className="space-y-2.5">
              <div className="text-[11px] font-semibold text-text3 uppercase tracking-wider">
                1. Learner Identity &amp; Initial Password
              </div>
              <div className="grid grid-cols-2 gap-2.5">
                <div>
                  <label className="block font-medium text-text2 mb-1">Student ID *</label>
                  <input
                    type="text"
                    required
                    value={newDraft.student_id}
                    onChange={(e) =>
                      setNewDraft((d) => ({ ...d, student_id: e.target.value }))
                    }
                    placeholder="STUD-2026-00920"
                    className="w-full px-3 py-1.5 rounded-lg bg-bg3 border border-border text-text font-mono outline-none focus:border-accent"
                  />
                </div>
                <div>
                  <label className="block font-medium text-text2 mb-1">LRN (12 digits)</label>
                  <input
                    type="text"
                    value={newDraft.lrn}
                    onChange={(e) => setNewDraft((d) => ({ ...d, lrn: e.target.value }))}
                    placeholder="109823456789"
                    className="w-full px-3 py-1.5 rounded-lg bg-bg3 border border-border text-text font-mono outline-none focus:border-accent"
                  />
                </div>
                <div>
                  <label className="block font-medium text-text2 mb-1">First Name *</label>
                  <input
                    type="text"
                    required
                    value={newDraft.firstname}
                    onChange={(e) =>
                      setNewDraft((d) => ({ ...d, firstname: e.target.value }))
                    }
                    placeholder="Juan"
                    className="w-full px-3 py-1.5 rounded-lg bg-bg3 border border-border text-text outline-none focus:border-accent"
                  />
                </div>
                <div>
                  <label className="block font-medium text-text2 mb-1">Middle Name</label>
                  <input
                    type="text"
                    value={newDraft.middlename}
                    onChange={(e) =>
                      setNewDraft((d) => ({ ...d, middlename: e.target.value }))
                    }
                    placeholder="Santos"
                    className="w-full px-3 py-1.5 rounded-lg bg-bg3 border border-border text-text outline-none focus:border-accent"
                  />
                </div>
                <div>
                  <label className="block font-medium text-text2 mb-1">Last Name *</label>
                  <input
                    type="text"
                    required
                    value={newDraft.lastname}
                    onChange={(e) =>
                      setNewDraft((d) => ({ ...d, lastname: e.target.value }))
                    }
                    placeholder="Dela Cruz"
                    className="w-full px-3 py-1.5 rounded-lg bg-bg3 border border-border text-text outline-none focus:border-accent"
                  />
                </div>
                <div>
                  <label className="block font-medium text-text2 mb-1">Suffix</label>
                  <input
                    type="text"
                    value={newDraft.suffix}
                    onChange={(e) => setNewDraft((d) => ({ ...d, suffix: e.target.value }))}
                    placeholder="Jr."
                    className="w-full px-3 py-1.5 rounded-lg bg-bg3 border border-border text-text outline-none focus:border-accent"
                  />
                </div>
                <div>
                  <label className="block font-medium text-text2 mb-1">Section *</label>
                  <select
                    value={newDraft.section_id}
                    onChange={(e) =>
                      setNewDraft((d) => ({ ...d, section_id: Number(e.target.value) }))
                    }
                    className="w-full px-3 py-1.5 rounded-lg bg-bg3 border border-border text-text outline-none focus:border-accent cursor-pointer"
                  >
                    {SECTIONS.map((sec) => (
                      <option key={sec.section_id} value={sec.section_id}>
                        {sec.grade_level} – {sec.section}
                      </option>
                    ))}
                  </select>
                </div>
                <div>
                  <label className="block font-medium text-text2 mb-1">Initial Password *</label>
                  <input
                    type="password"
                    required
                    minLength={8}
                    value={newDraft.initialPassword}
                    onChange={(e) =>
                      setNewDraft((d) => ({ ...d, initialPassword: e.target.value }))
                    }
                    placeholder="Min. 8 characters"
                    className="w-full px-3 py-1.5 rounded-lg bg-bg3 border border-border text-text outline-none focus:border-accent"
                  />
                </div>
              </div>
            </div>

            {/* Group 2: Optional Demographics & Guardian collapsed cleanly via native <details> */}
            <details className="rounded-xl border border-border bg-bg3">
              <summary className="px-3 py-2.5 font-medium text-text cursor-pointer select-none">
                2. Guardian &amp; Demographics (Optional — Birthdate, Contact, Address)
              </summary>
              <div className="p-3 pt-2 grid grid-cols-2 gap-2.5 border-t border-border">
                <div>
                  <label className="block font-medium text-text2 mb-1">Guardian Name</label>
                  <input
                    type="text"
                    value={newDraft.guardian_name}
                    onChange={(e) =>
                      setNewDraft((d) => ({ ...d, guardian_name: e.target.value }))
                    }
                    placeholder="Maria Dela Cruz"
                    className="w-full px-2.5 py-1.5 rounded-lg bg-bg2 border border-border text-text outline-none focus:border-accent"
                  />
                </div>
                <div>
                  <label className="block font-medium text-text2 mb-1">Guardian Email</label>
                  <input
                    type="email"
                    value={newDraft.guardian_email}
                    onChange={(e) =>
                      setNewDraft((d) => ({ ...d, guardian_email: e.target.value }))
                    }
                    placeholder="guardian@email.com"
                    className="w-full px-2.5 py-1.5 rounded-lg bg-bg2 border border-border text-text outline-none focus:border-accent"
                  />
                </div>
                <div>
                  <label className="block font-medium text-text2 mb-1">Contact Number</label>
                  <input
                    type="text"
                    value={newDraft.contact_number}
                    onChange={(e) =>
                      setNewDraft((d) => ({ ...d, contact_number: e.target.value }))
                    }
                    placeholder="09171234567"
                    className="w-full px-2.5 py-1.5 rounded-lg bg-bg2 border border-border text-text font-mono outline-none focus:border-accent"
                  />
                </div>
                <div>
                  <label className="block font-medium text-text2 mb-1">
                    Birthdate {newDraft.birthdate ? `(Age: ${newDraft.age})` : ''}
                  </label>
                  <input
                    type="date"
                    value={newDraft.birthdate}
                    onChange={(e) => {
                      const nextDate = e.target.value;
                      const nextAge = computeAgeFromBirthdate(nextDate, new Date());
                      setNewDraft((d) => ({ ...d, birthdate: nextDate, age: nextAge }));
                    }}
                    className="w-full px-2.5 py-1.5 rounded-lg bg-bg2 border border-border text-text outline-none focus:border-accent"
                  />
                </div>
                <div>
                  <label className="block font-medium text-text2 mb-1">Gender</label>
                  <select
                    value={newDraft.gender}
                    onChange={(e) => setNewDraft((d) => ({ ...d, gender: e.target.value }))}
                    className="w-full px-2.5 py-1.5 rounded-lg bg-bg2 border border-border text-text outline-none focus:border-accent cursor-pointer"
                  >
                    <option value="Unspecified">Unspecified</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                  </select>
                </div>
                <div>
                  <label className="block font-medium text-text2 mb-1">Address</label>
                  <input
                    type="text"
                    value={newDraft.address}
                    onChange={(e) => setNewDraft((d) => ({ ...d, address: e.target.value }))}
                    placeholder="Barangay, Municipality"
                    className="w-full px-2.5 py-1.5 rounded-lg bg-bg2 border border-border text-text outline-none focus:border-accent"
                  />
                </div>
              </div>
            </details>

            {/* Group 3: Assign Subjects */}
            <fieldset className="space-y-1.5">
              <legend className="text-[11px] font-semibold text-text3 uppercase tracking-wider mb-1">
                3. Assign Subject(s) *
              </legend>
              {TEACHER_SUBJECTS.map((sub) => (
                <label
                  key={sub.teacher_subject_id}
                  className="flex items-center gap-2.5 p-2 rounded-xl border border-border bg-bg3/50 hover:bg-bg3 cursor-pointer"
                >
                  <input
                    type="checkbox"
                    checked={newDraft.teacher_subject_ids.includes(sub.teacher_subject_id)}
                    onChange={() =>
                      setNewDraft((d) => ({
                        ...d,
                        teacher_subject_ids: toggleSubjectSelection(
                          d.teacher_subject_ids,
                          sub.teacher_subject_id,
                        ),
                      }))
                    }
                    className="accent-accent"
                  />
                  <span className="text-text font-medium">{sub.subject}</span>
                  <span className="text-text3 ms-auto">{sub.strand}</span>
                </label>
              ))}
            </fieldset>

            <div className="pt-2 border-t border-border flex items-center justify-end gap-2">
              <button
                type="button"
                onClick={closeAddDialog}
                className="btn btn-ghost btn-sm px-3.5 py-2 rounded-xl text-xs"
              >
                Cancel
              </button>
              <button
                type="submit"
                className="btn btn-primary btn-sm px-4 py-2 rounded-xl text-xs font-semibold"
              >
                Create &amp; Enroll
              </button>
            </div>
          </form>
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
