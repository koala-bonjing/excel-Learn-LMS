import React, { useEffect, useMemo, useState } from 'react';
import { Button as AriaButton } from 'react-aria-components';
import type { StudentSubmission } from '@/types';
import type { CourseOutlineItem } from '@/data/courseOutlineData';
import { Tooltip, TooltipTrigger } from '@/components/base/tooltip/tooltip';
import {
  RiAddLine,
  RiArrowLeftLine,
  RiArrowRightLine,
  RiCheckDoubleLine,
  RiCheckLine,
  RiCloseLine,
  RiDownload2Line,
  RiErrorWarningLine,
  RiExternalLinkLine,
  RiFileList3Line,
  RiFilePdfLine,
  RiFlashlightFill,
  RiFullscreenLine,
  RiKanbanView,
  RiLayoutLeftLine,
  RiMailSendLine,
  RiNotification3Line,
  RiPauseLine,
  RiPlayFill,
  RiRefreshLine,
  RiSearchLine,
  RiShieldCheckLine,
  RiTimeLine,
  RiVideoLine,
} from '@remixicon/react';

const SAMPLE_PDF_URL = '/samples/sample-student-work.pdf';

export interface TeacherPerformanceTasksViewProps {
  submissions: StudentSubmission[];
  onUpdateSubmissions: React.Dispatch<React.SetStateAction<StudentSubmission[]>>;
  gradingScores: Record<string, number>;
  onUpdateGradingScores: React.Dispatch<React.SetStateAction<Record<string, number>>>;
  feedbackRemarks: Record<string, string>;
  onUpdateFeedbackRemarks: React.Dispatch<React.SetStateAction<Record<string, string>>>;
  customAddedItems: Record<string, CourseOutlineItem[]>;
  onOpenNewTaskStudio: () => void;
}

type RosterStatus = 'pending' | 'missing' | 'revision' | 'graded';
type ViewMode = 'grader' | 'kanban';

interface RubricCriterion {
  id: string;
  title: string;
  description: string;
  points: number;
}

interface PerformanceTaskBin {
  id: string;
  title: string;
  subjectCode: string;
  subjectTitle: string;
  section: string;
  offeringKey: string;
  moduleTitle: string;
  dueDate: string;
  maxScore: number;
  allowedFormats: string;
  rubricCriteria: RubricCriterion[];
  isCustom?: boolean;
}

interface RosterSubmissionEntry {
  id: string;
  linkedSubmissionId?: string;
  studentName: string;
  studentId: string;
  lrn: string;
  email: string;
  section: string;
  offeringKey: string;
  taskBinId: string;
  taskTitle: string;
  subject: string;
  submittedTime: string;
  submissionTimingNote: string;
  fileAttachment: string;
  fileSize: string;
  studentNote?: string;
  status: RosterStatus;
  score?: number;
  maxScore: number;
}

const HANDLED_OFFERINGS = [
  { key: 'ALL', label: 'All Handled Sections (6)' },
  { key: 'ORAL-COMM-11 · STEM 11-A', label: 'ORAL-COMM-11 · STEM 11-A (38)' },
  { key: 'ORAL-COMM-11 · HUMSS 11-A', label: 'ORAL-COMM-11 · HUMSS 11-A (36)' },
  { key: 'GEN-MATH-11 · STEM 11-A', label: 'GEN-MATH-11 · STEM 11-A (42)' },
  { key: 'GEN-MATH-11 · ABM 11-B', label: 'GEN-MATH-11 · ABM 11-B (34)' },
  { key: 'EARTH-SCI-11 · ABM 11-B', label: 'EARTH-SCI-11 · ABM 11-B (35)' },
  { key: 'EMP-TECH-11 · ICT 11-A', label: 'EMP-TECH-11 · ICT 11-A (27)' },
] as const;

const BUILT_IN_TASK_BINS: PerformanceTaskBin[] = [
  {
    id: 'bin-oral-speech',
    title: 'Extemporaneous Speech Video Performance',
    subjectCode: 'ORAL-COMM-11',
    subjectTitle: 'Oral Communication in Context',
    section: 'STEM 11-A',
    offeringKey: 'ORAL-COMM-11 · STEM 11-A',
    moduleTitle: 'Module 5: Types of Speech Contexts & Delivery',
    dueDate: 'Nov 18, 2026 · 5:00 PM',
    maxScore: 100,
    allowedFormats: 'MP4 Video, WEBM, MOV',
    rubricCriteria: [
      {
        id: 'crit-vocal',
        title: 'Vocal Modulation & Delivery',
        description: 'Clarity, pacing, pitch variation, and articulate pronunciation.',
        points: 35,
      },
      {
        id: 'crit-thesis',
        title: 'Logical Thesis & Evidence',
        description: 'Cohesive argument structure supported by credible real-world examples.',
        points: 35,
      },
      {
        id: 'crit-presence',
        title: 'Stage Presence & Non-Verbal Cues',
        description: 'Camera eye contact, confident posture, and purposeful gestures.',
        points: 30,
      },
    ],
  },
  {
    id: 'bin-oral-critique',
    title: 'Intercultural Communication Critique Essay',
    subjectCode: 'ORAL-COMM-11',
    subjectTitle: 'Oral Communication in Context',
    section: 'HUMSS 11-A',
    offeringKey: 'ORAL-COMM-11 · HUMSS 11-A',
    moduleTitle: 'Module 4: Intercultural Communication Competence',
    dueDate: 'Nov 17, 2026 · 11:59 PM',
    maxScore: 50,
    allowedFormats: 'PDF Document, DOCX',
    rubricCriteria: [
      {
        id: 'crit-analysis',
        title: 'Cultural Framework Analysis',
        description: 'Depth of intercultural sensitivity and theoretical grounding.',
        points: 20,
      },
      {
        id: 'crit-evidence',
        title: 'Case Citation & Synthesis',
        description: 'Relevant situational examples and accurate academic references.',
        points: 15,
      },
      {
        id: 'crit-mechanics',
        title: 'Academic Prose & Mechanics',
        description: 'Coherence, formal register, and APA citation formatting.',
        points: 15,
      },
    ],
  },
  {
    id: 'bin-oral-listening',
    title: 'Barriers to Effective Listening Synthesis',
    subjectCode: 'ORAL-COMM-11',
    subjectTitle: 'Oral Communication in Context',
    section: 'HUMSS 11-A',
    offeringKey: 'ORAL-COMM-11 · HUMSS 11-A',
    moduleTitle: 'Module 3: Communication Breakdown & Repair',
    dueDate: 'Nov 14, 2026 · 5:00 PM',
    maxScore: 50,
    allowedFormats: 'PDF Document, DOCX',
    rubricCriteria: [
      {
        id: 'crit-diagnosis',
        title: 'Barrier Identification & Diagnosis',
        description: 'Accurately classifies semantic, physical, and psychological noise.',
        points: 20,
      },
      {
        id: 'crit-repair',
        title: 'Communication Repair Strategies',
        description: 'Practical, empathetic mitigation strategies for classroom dialogue.',
        points: 15,
      },
      {
        id: 'crit-clarity',
        title: 'Synthesis Structure & Clarity',
        description: 'Concise executive summary and clear thematic organization.',
        points: 15,
      },
    ],
  },
  {
    id: 'bin-math-logic',
    title: 'Rational Functions & Logic Modeling Portfolio',
    subjectCode: 'GEN-MATH-11',
    subjectTitle: 'General Mathematics',
    section: 'STEM 11-A',
    offeringKey: 'GEN-MATH-11 · STEM 11-A',
    moduleTitle: 'Module 6: Rational Models & Propositional Logic',
    dueDate: 'Nov 19, 2026 · 11:59 PM',
    maxScore: 100,
    allowedFormats: 'PDF Document, XLSX',
    rubricCriteria: [
      {
        id: 'crit-accuracy',
        title: 'Mathematical Accuracy & Proofs',
        description: 'Correct domain/asymptote derivations and truth-table validations.',
        points: 40,
      },
      {
        id: 'crit-modeling',
        title: 'Real-World Application Model',
        description: 'Clear formulation of contextual STEM scenarios into equations.',
        points: 35,
      },
      {
        id: 'crit-notation',
        title: 'Graphing & Symbolic Notation',
        description: 'Clean step-by-step solutions and labeled Cartesian plots.',
        points: 25,
      },
    ],
  },
  {
    id: 'bin-math-annuities',
    title: 'Simple & Compound Interest Annuities Sheet',
    subjectCode: 'GEN-MATH-11',
    subjectTitle: 'General Mathematics',
    section: 'ABM 11-B',
    offeringKey: 'GEN-MATH-11 · ABM 11-B',
    moduleTitle: 'Module 5: Business & Consumer Loans',
    dueDate: 'Nov 16, 2026 · 5:00 PM',
    maxScore: 100,
    allowedFormats: 'XLSX Spreadsheet, PDF Document',
    rubricCriteria: [
      {
        id: 'crit-formulas',
        title: 'Spreadsheet Formula Precision',
        description: 'Accurate future/present value and amortization schedule formulas.',
        points: 40,
      },
      {
        id: 'crit-decision',
        title: 'Financial Comparison Insight',
        description: 'Sound ABM recommendation comparing loan options and effective rates.',
        points: 35,
      },
      {
        id: 'crit-layout',
        title: 'Tabular Formatting & Auditability',
        description: 'Readable currency formatting, labels, and zero hardcoded totals.',
        points: 25,
      },
    ],
  },
  {
    id: 'bin-earth-field',
    title: 'Geologic Processes Field Observation Video',
    subjectCode: 'EARTH-SCI-11',
    subjectTitle: 'Earth and Life Science',
    section: 'ABM 11-B',
    offeringKey: 'EARTH-SCI-11 · ABM 11-B',
    moduleTitle: 'Module 7: Endogenic & Exogenic Geologic Processes',
    dueDate: 'Nov 18, 2026 · 5:00 PM',
    maxScore: 100,
    allowedFormats: 'MP4 Video, PDF Brief',
    rubricCriteria: [
      {
        id: 'crit-science',
        title: 'Scientific Explanation & Terminology',
        description: 'Accurate interpretation of weathering, erosion, or tectonic landforms.',
        points: 40,
      },
      {
        id: 'crit-audio',
        title: 'Audio Narration & Visual Evidence',
        description: 'Clear voiceover track paired with annotated field visuals.',
        points: 35,
      },
      {
        id: 'crit-mitigation',
        title: 'DRRM Hazard Preparedness Link',
        description: 'Connects geologic risk analysis to community safety planning.',
        points: 25,
      },
    ],
  },
  {
    id: 'bin-ict-portal',
    title: 'Interactive Multimedia & Web Portal Prototype',
    subjectCode: 'EMP-TECH-11',
    subjectTitle: 'Empowerment Technologies (ICT)',
    section: 'ICT 11-A',
    offeringKey: 'EMP-TECH-11 · ICT 11-A',
    moduleTitle: 'Module 8: Interactive Multimedia & Web Portals',
    dueDate: 'Nov 18, 2026 · 11:59 PM',
    maxScore: 100,
    allowedFormats: 'ZIP Archive, Live URL, PDF Spec',
    rubricCriteria: [
      {
        id: 'crit-ui',
        title: 'Responsive UI & Accessibility',
        description: 'Clean semantic HTML, responsive layout, and readable contrast.',
        points: 35,
      },
      {
        id: 'crit-interactivity',
        title: 'Multimedia Integration & Logic',
        description: 'Working navigation, embedded media, and error-free scripts.',
        points: 35,
      },
      {
        id: 'crit-docs',
        title: 'Technical Documentation & Ethics',
        description: 'Proper asset attribution and concise deployment README.',
        points: 30,
      },
    ],
  },
];

const COURSE_TO_SUBJECT_MAP: Record<string, { code: string; title: string; defaultSection: string }> = {
  'c-oral-comm': { code: 'ORAL-COMM-11', title: 'Oral Communication in Context', defaultSection: 'STEM 11-A' },
  'c-gen-math': { code: 'GEN-MATH-11', title: 'General Mathematics', defaultSection: 'STEM 11-A' },
  'c-earth-sci': { code: 'EARTH-SCI-11', title: 'Earth and Life Science', defaultSection: 'ABM 11-B' },
  'c-emp-tech': { code: 'EMP-TECH-11', title: 'Empowerment Technologies (ICT)', defaultSection: 'ICT 11-A' },
};

const SUPPLEMENTAL_ROSTER_ENTRIES: RosterSubmissionEntry[] = [
  {
    id: 'roster-miss-1',
    studentName: 'Gabriel Angelo Cruz',
    studentId: 'STUD-123502',
    lrn: '109823456717',
    email: 'gabriel.cruz@student.excelsior.edu.ph',
    section: 'ICT 11-A',
    offeringKey: 'EMP-TECH-11 · ICT 11-A',
    taskBinId: 'bin-ict-portal',
    taskTitle: 'Interactive Multimedia & Web Portal Prototype',
    subject: 'Empowerment Technologies (ICT)',
    submittedTime: 'Missing · Due Nov 18',
    submissionTimingNote: 'No artifact uploaded yet',
    fileAttachment: '',
    fileSize: '',
    status: 'missing',
    maxScore: 100,
  },
  {
    id: 'roster-miss-2',
    studentName: 'Daniel Padilla Jr.',
    studentId: 'STUD-123521',
    lrn: '109823456721',
    email: 'daniel.padilla@student.excelsior.edu.ph',
    section: 'STEM 11-A',
    offeringKey: 'GEN-MATH-11 · STEM 11-A',
    taskBinId: 'bin-math-logic',
    taskTitle: 'Rational Functions & Logic Modeling Portfolio',
    subject: 'General Mathematics',
    submittedTime: 'Missing · Due Nov 19',
    submissionTimingNote: 'No artifact uploaded yet',
    fileAttachment: '',
    fileSize: '',
    status: 'missing',
    maxScore: 100,
  },
  {
    id: 'roster-miss-3',
    studentName: 'Princess Nicole Reyes',
    studentId: 'STUD-123495',
    lrn: '109823456716',
    email: 'princess.reyes@student.excelsior.edu.ph',
    section: 'HUMSS 11-A',
    offeringKey: 'ORAL-COMM-11 · HUMSS 11-A',
    taskBinId: 'bin-oral-critique',
    taskTitle: 'Intercultural Communication Critique Essay',
    subject: 'Oral Communication in Context',
    submittedTime: 'Missing · Due Nov 17',
    submissionTimingNote: 'No artifact uploaded yet',
    fileAttachment: '',
    fileSize: '',
    status: 'missing',
    maxScore: 50,
  },
  {
    id: 'roster-rev-1',
    studentName: 'Carlo Santos',
    studentId: 'STUD-123490',
    lrn: '109823456715',
    email: 'carlo.santos@student.excelsior.edu.ph',
    section: 'ABM 11-B',
    offeringKey: 'EARTH-SCI-11 · ABM 11-B',
    taskBinId: 'bin-earth-field',
    taskTitle: 'Geologic Processes Field Observation Video',
    subject: 'Earth and Life Science',
    submittedTime: 'Returned 3h ago',
    submissionTimingNote: 'Revision requested · Awaiting re-upload',
    fileAttachment: 'Santos_Carlo_FieldReport_v1.mp4',
    fileSize: '31.2 MB',
    studentNote: 'Initial field recording submitted from Barangay San Isidro ridge.',
    status: 'revision',
    maxScore: 100,
  },
  {
    id: 'roster-pend-ict',
    studentName: 'Patricia Mae Flores',
    studentId: 'STUD-123510',
    lrn: '109823456718',
    email: 'patricia.flores@student.excelsior.edu.ph',
    section: 'ICT 11-A',
    offeringKey: 'EMP-TECH-11 · ICT 11-A',
    taskBinId: 'bin-ict-portal',
    taskTitle: 'Interactive Multimedia & Web Portal Prototype',
    subject: 'Empowerment Technologies (ICT)',
    submittedTime: '42 mins ago',
    submissionTimingNote: 'Submitted 2h early · On-Time',
    fileAttachment: 'Flores_ICT11A_WebPortal_v2.zip',
    fileSize: '14.6 MB',
    studentNote: 'Includes responsive mobile drawer, keyboard navigation, and compressed SVG assets.',
    status: 'pending',
    maxScore: 100,
  },
  {
    id: 'roster-grad-abm',
    studentName: 'Mark Lester Villanueva',
    studentId: 'STUD-123534',
    lrn: '109823456724',
    email: 'mark.villanueva@student.excelsior.edu.ph',
    section: 'ABM 11-B',
    offeringKey: 'GEN-MATH-11 · ABM 11-B',
    taskBinId: 'bin-math-annuities',
    taskTitle: 'Simple & Compound Interest Annuities Sheet',
    subject: 'General Mathematics',
    submittedTime: 'Yesterday, 2:10 PM',
    submissionTimingNote: 'Submitted 1d early · On-Time',
    fileAttachment: 'Villanueva_ABM11B_Annuities.xlsx',
    fileSize: '1.3 MB',
    studentNote: 'Added sensitivity comparison tab for 6% vs 8.5% annual compounding.',
    status: 'graded',
    score: 94.5,
    maxScore: 100,
  },
];

const STATUS_PRIORITY: Record<RosterStatus, number> = {
  pending: 0,
  missing: 1,
  revision: 2,
  graded: 3,
};

const formatPoints = (val: number): string => {
  const rounded = Math.round(val * 100) / 100;
  return Number.isInteger(rounded) ? String(rounded) : String(rounded);
};

const clampScore = (val: number, max: number): number => {
  if (Number.isNaN(val)) return 0;
  const clamped = Math.max(0, Math.min(max, val));
  return Math.round(clamped * 100) / 100;
};

const distributeScoreAcrossCriteria = (
  targetTotal: number,
  criteria: RubricCriterion[]
): Record<string, number> => {
  const maxSum = criteria.reduce((acc, c) => acc + c.points, 0) || 1;
  const ratio = Math.max(0, Math.min(1, targetTotal / maxSum));
  const result: Record<string, number> = {};
  let running = 0;

  criteria.forEach((crit, idx) => {
    if (idx === criteria.length - 1) {
      result[crit.id] = clampScore(targetTotal - running, crit.points);
    } else {
      const part = Math.round(crit.points * ratio * 4) / 4;
      const clampedPart = clampScore(part, crit.points);
      result[crit.id] = clampedPart;
      running += clampedPart;
    }
  });

  return result;
};

const mapSubmissionToBinAndOffering = (
  sub: StudentSubmission
): {
  taskBinId: string;
  section: string;
  offeringKey: string;
  lrn: string;
  email: string;
  timingNote: string;
  studentNote: string;
} => {
  if (sub.id === 'subm-1') {
    return {
      taskBinId: 'bin-oral-speech',
      section: 'STEM 11-A',
      offeringKey: 'ORAL-COMM-11 · STEM 11-A',
      lrn: '109823456712',
      email: 'bryan.donceras@student.excelsior.edu.ph',
      timingNote: 'Submitted 2h early · On-Time',
      studentNote:
        'Focused on media literacy and fact-checking frameworks with 3 cited DepEd case studies.',
    };
  }
  if (sub.id === 'subm-2') {
    return {
      taskBinId: 'bin-oral-speech',
      section: 'STEM 11-A',
      offeringKey: 'ORAL-COMM-11 · STEM 11-A',
      lrn: '109823456713',
      email: 'janloyd.lotino@student.excelsior.edu.ph',
      timingNote: 'Submitted 45m early · On-Time',
      studentNote:
        'Recorded in 1080p single-take with cue cards only for statistical citations.',
    };
  }
  if (sub.id === 'subm-3') {
    return {
      taskBinId: 'bin-oral-critique',
      section: 'HUMSS 11-A',
      offeringKey: 'ORAL-COMM-11 · HUMSS 11-A',
      lrn: '109823456714',
      email: 'althea.mendoza@student.excelsior.edu.ph',
      timingNote: 'Submitted 1h early · On-Time',
      studentNote:
        'Examines high-context vs. low-context workplace communication patterns in ASEAN teams.',
    };
  }
  return {
    taskBinId: 'bin-oral-listening',
    section: 'HUMSS 11-A',
    offeringKey: 'ORAL-COMM-11 · HUMSS 11-A',
    lrn: '109823456715',
    email: 'carlo.santos@student.excelsior.edu.ph',
    timingNote: 'Submitted on schedule · On-Time',
    studentNote: 'Includes reflection matrix on active listening drills.',
  };
};

export function TeacherPerformanceTasksView({
  submissions,
  onUpdateSubmissions,
  gradingScores,
  onUpdateGradingScores,
  feedbackRemarks,
  onUpdateFeedbackRemarks,
  customAddedItems,
  onOpenNewTaskStudio,
}: TeacherPerformanceTasksViewProps) {
  const [viewMode, setViewMode] = useState<ViewMode>('grader');
  const [offeringFilter, setOfferingFilter] = useState<string>('ALL');
  const [selectedTaskBinId, setSelectedTaskBinId] = useState<string>('ALL');
  const [statusFilter, setStatusFilter] = useState<string>('ALL');
  const [searchQuery, setSearchQuery] = useState<string>('');
  const [selectedEntryId, setSelectedEntryId] = useState<string>('subm-1');

  // Local state for supplemental roster items (missing, revision, extra graded/pending)
  const [supplementalStatusOverrides, setSupplementalStatusOverrides] = useState<
    Record<string, { status: RosterStatus; score?: number }>
  >({});
  // Tracks revision status for core StudentSubmission rows without mutating shared types
  const [coreRevisionIds, setCoreRevisionIds] = useState<Record<string, boolean>>({});

  // Per-entry analytical rubric criterion scores: entryId -> criterionId -> points
  const [rubricScoresByEntry, setRubricScoresByEntry] = useState<
    Record<string, Record<string, number>>
  >({});
  // Local feedback fallback for supplemental roster entries
  const [localFeedback, setLocalFeedback] = useState<Record<string, string>>({
    'roster-rev-1': 'Requested re-upload of clear audio track (ambient wind noise drowned out narration at 01:15).',
    'roster-grad-abm': 'Clean dynamic formulas and thorough sensitivity analysis. Great ABM financial reasoning!',
  });

  const [nudgedIds, setNudgedIds] = useState<Record<string, boolean>>({});
  const [isPlayingPreview, setIsPlayingPreview] = useState<boolean>(false);
  const [isPreviewModalOpen, setIsPreviewModalOpen] = useState<boolean>(false);
  const [forcePdfInModal, setForcePdfInModal] = useState<boolean>(false);
  const [toastMessage, setToastMessage] = useState<string | null>(null);

  useEffect(() => {
    if (!isPreviewModalOpen) return;
    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === 'Escape') setIsPreviewModalOpen(false);
    };
    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [isPreviewModalOpen]);

  const showToast = (msg: string) => {
    setToastMessage(msg);
    setTimeout(() => {
      setToastMessage((curr) => (curr === msg ? null : curr));
    }, 3200);
  };

  // Combine built-in bins with any newly published submission bins from CurriculumStudio
  const taskBins = useMemo<PerformanceTaskBin[]>(() => {
    const customBins: PerformanceTaskBin[] = [];

    Object.entries(customAddedItems).forEach(([compositeKey, items]) => {
      const [courseId, moduleId] = compositeKey.split('__');
      const subjectMeta = COURSE_TO_SUBJECT_MAP[courseId] || {
        code: 'ORAL-COMM-11',
        title: 'Senior High Subject',
        defaultSection: 'STEM 11-A',
      };

      items.forEach((item) => {
        if (item.type !== 'submission') return;
        const rawCriteria =
          item.rubricCriteria && item.rubricCriteria.length > 0
            ? item.rubricCriteria
            : [
                { criteria: 'Content & Mastery', points: 40 },
                { criteria: 'Execution & Delivery', points: 35 },
                { criteria: 'Organization & Clarity', points: 25 },
              ];

        const parsedCriteria: RubricCriterion[] = rawCriteria.map((rc, idx) => {
          const parenMatch = rc.criteria.match(/^(.+?)\s*\((.+)\)$/);
          return {
            id: `${item.id}-crit-${idx}`,
            title: parenMatch ? parenMatch[1].trim() : rc.criteria,
            description: parenMatch
              ? parenMatch[2].trim()
              : 'DepEd Performance Task analytical criterion.',
            points: rc.points,
          };
        });

        const totalMax =
          item.maxPoints || parsedCriteria.reduce((acc, c) => acc + c.points, 0) || 100;

        customBins.push({
          id: item.id,
          title: item.title,
          subjectCode: subjectMeta.code,
          subjectTitle: subjectMeta.title,
          section: subjectMeta.defaultSection,
          offeringKey: `${subjectMeta.code} · ${subjectMeta.defaultSection}`,
          moduleTitle: `Module: ${moduleId.replace('sec-', '').toUpperCase()}`,
          dueDate: item.submissionDeadline || 'Nov 24, 2026 · 11:59 PM',
          maxScore: totalMax,
          allowedFormats: item.subtitle?.split('·')[0]?.trim() || 'PDF, MP4, DOCX',
          rubricCriteria: parsedCriteria,
          isCustom: true,
        });
      });
    });

    return [...customBins, ...BUILT_IN_TASK_BINS];
  }, [customAddedItems]);

  // Bins visible in Combobox 2 based on Combobox 1 offeringFilter
  const availableBinsForOffering = useMemo(() => {
    if (offeringFilter === 'ALL') return taskBins;
    const [filterCode] = offeringFilter.split(' · ');
    return taskBins.filter(
      (b) => b.offeringKey === offeringFilter || b.subjectCode === filterCode
    );
  }, [taskBins, offeringFilter]);

  const effectiveTaskBinId = useMemo(() => {
    if (selectedTaskBinId === 'ALL') return 'ALL';
    return availableBinsForOffering.some((b) => b.id === selectedTaskBinId)
      ? selectedTaskBinId
      : 'ALL';
  }, [selectedTaskBinId, availableBinsForOffering]);

  // Unified roster entries combining live `submissions` + supplemental missing/revision entries
  const allRosterEntries = useMemo<RosterSubmissionEntry[]>(() => {
    const coreEntries: RosterSubmissionEntry[] = submissions.map((sub) => {
      const meta = mapSubmissionToBinAndOffering(sub);
      const isRevision = coreRevisionIds[sub.id];
      const status: RosterStatus = isRevision ? 'revision' : sub.status;
      return {
        id: sub.id,
        linkedSubmissionId: sub.id,
        studentName: sub.studentName,
        studentId: sub.studentId,
        lrn: meta.lrn,
        email: meta.email,
        section: meta.section,
        offeringKey: meta.offeringKey,
        taskBinId: meta.taskBinId,
        taskTitle: sub.taskTitle,
        subject: sub.subject,
        submittedTime: sub.submittedTime,
        submissionTimingNote: isRevision
          ? 'Revision requested · Awaiting updated upload'
          : meta.timingNote,
        fileAttachment: sub.fileAttachment,
        fileSize: sub.fileSize,
        studentNote: meta.studentNote,
        status,
        score: sub.score,
        maxScore: sub.maxScore,
      };
    });

    const extraEntries: RosterSubmissionEntry[] = SUPPLEMENTAL_ROSTER_ENTRIES.map((entry) => {
      const override = supplementalStatusOverrides[entry.id];
      if (!override) return entry;
      return {
        ...entry,
        status: override.status,
        score: override.score ?? entry.score,
        submissionTimingNote:
          override.status === 'graded'
            ? 'Graded & synced to DepEd SF9'
            : override.status === 'revision'
              ? 'Revision requested · Awaiting re-upload'
              : entry.submissionTimingNote,
      };
    });

    return [...coreEntries, ...extraEntries];
  }, [submissions, coreRevisionIds, supplementalStatusOverrides]);

  // Apply 3-level relational filters + search query, then sort by priority
  const filteredEntries = useMemo(() => {
    const q = searchQuery.trim().toLowerCase();
    return allRosterEntries
      .filter((entry) => {
        if (offeringFilter !== 'ALL' && entry.offeringKey !== offeringFilter) return false;
        if (effectiveTaskBinId !== 'ALL' && entry.taskBinId !== effectiveTaskBinId) return false;
        if (statusFilter !== 'ALL' && entry.status !== statusFilter) return false;
        if (q) {
          const matchesName = entry.studentName.toLowerCase().includes(q);
          const matchesLrn = entry.lrn.toLowerCase().includes(q);
          const matchesId = entry.studentId.toLowerCase().includes(q);
          const matchesTask = entry.taskTitle.toLowerCase().includes(q);
          if (!matchesName && !matchesLrn && !matchesId && !matchesTask) return false;
        }
        return true;
      })
      .sort((a, b) => STATUS_PRIORITY[a.status] - STATUS_PRIORITY[b.status]);
  }, [allRosterEntries, offeringFilter, effectiveTaskBinId, statusFilter, searchQuery]);

  // Summary counts (scoped to selected offering & bin, ignoring statusFilter so teacher sees full picture)
  const queueCounts = useMemo(() => {
    const scoped = allRosterEntries.filter((entry) => {
      if (offeringFilter !== 'ALL' && entry.offeringKey !== offeringFilter) return false;
      if (effectiveTaskBinId !== 'ALL' && entry.taskBinId !== effectiveTaskBinId) return false;
      return true;
    });
    return {
      pending: scoped.filter((e) => e.status === 'pending').length,
      missing: scoped.filter((e) => e.status === 'missing').length,
      revision: scoped.filter((e) => e.status === 'revision').length,
      graded: scoped.filter((e) => e.status === 'graded').length,
    };
  }, [allRosterEntries, offeringFilter, effectiveTaskBinId]);

  const activeEntry = useMemo(() => {
    return (
      filteredEntries.find((e) => e.id === selectedEntryId) ||
      filteredEntries[0] ||
      null
    );
  }, [filteredEntries, selectedEntryId]);

  const activeBin = useMemo<PerformanceTaskBin>(() => {
    if (activeEntry) {
      return (
        taskBins.find((b) => b.id === activeEntry.taskBinId) ||
        BUILT_IN_TASK_BINS[0]
      );
    }
    if (effectiveTaskBinId !== 'ALL') {
      return (
        taskBins.find((b) => b.id === effectiveTaskBinId) ||
        BUILT_IN_TASK_BINS[0]
      );
    }
    return BUILT_IN_TASK_BINS[0];
  }, [activeEntry, effectiveTaskBinId, taskBins]);

  // Resolve current criterion scores for the selected entry
  const activeCriterionScores = useMemo<Record<string, number>>(() => {
    if (!activeEntry) return {};
    const saved = rubricScoresByEntry[activeEntry.id];
    if (saved) return saved;

    if (activeEntry.status === 'missing') {
      return distributeScoreAcrossCriteria(0, activeBin.rubricCriteria);
    }

    const seedTotal =
      activeEntry.score ??
      gradingScores[activeEntry.id] ??
      Math.round(activeBin.maxScore * 0.9);
    return distributeScoreAcrossCriteria(seedTotal, activeBin.rubricCriteria);
  }, [activeEntry, activeBin, rubricScoresByEntry, gradingScores]);

  const activeTotalScore = useMemo(() => {
    const sum = activeBin.rubricCriteria.reduce(
      (acc, crit) => acc + (activeCriterionScores[crit.id] ?? 0),
      0
    );
    return clampScore(sum, activeBin.maxScore);
  }, [activeBin, activeCriterionScores]);

  const activePercentage = useMemo(() => {
    if (!activeBin.maxScore) return 0;
    return Math.round((activeTotalScore / activeBin.maxScore) * 1000) / 10;
  }, [activeTotalScore, activeBin.maxScore]);

  const activeFeedback = activeEntry
    ? feedbackRemarks[activeEntry.id] ?? localFeedback[activeEntry.id] ?? ''
    : '';

  const handleCriterionChange = (crit: RubricCriterion, rawValue: number) => {
    if (!activeEntry) return;
    const clamped = clampScore(rawValue, crit.points);
    const updatedMap = {
      ...activeCriterionScores,
      [crit.id]: clamped,
    };
    setRubricScoresByEntry((prev) => ({
      ...prev,
      [activeEntry.id]: updatedMap,
    }));
    const newTotal = clampScore(
      activeBin.rubricCriteria.reduce((acc, c) => acc + (updatedMap[c.id] ?? 0), 0),
      activeBin.maxScore
    );
    if (activeEntry.linkedSubmissionId) {
      onUpdateGradingScores((prev) => ({
        ...prev,
        [activeEntry.id]: newTotal,
      }));
    }
  };

  const handleFeedbackChange = (text: string) => {
    if (!activeEntry) return;
    if (activeEntry.linkedSubmissionId) {
      onUpdateFeedbackRemarks((prev) => ({ ...prev, [activeEntry.id]: text }));
    } else {
      setLocalFeedback((prev) => ({ ...prev, [activeEntry.id]: text }));
    }
  };

  const handleNudgeStudent = (entry: RosterSubmissionEntry, e?: React.MouseEvent) => {
    if (e) e.stopPropagation();
    setNudgedIds((prev) => ({ ...prev, [entry.id]: true }));
    showToast(`Reminder sent to ${entry.studentName} (${entry.section})!`);
  };

  const handleRequestRevision = () => {
    if (!activeEntry) return;
    if (activeEntry.linkedSubmissionId) {
      setCoreRevisionIds((prev) => ({ ...prev, [activeEntry.id]: true }));
    } else {
      setSupplementalStatusOverrides((prev) => ({
        ...prev,
        [activeEntry.id]: { status: 'revision' },
      }));
    }
    showToast(`Revision requested for ${activeEntry.studentName}. Student notified.`);
  };

  const handleApproveAndSave = () => {
    if (!activeEntry) return;
    const finalScore = activeTotalScore;

    if (activeEntry.linkedSubmissionId) {
      setCoreRevisionIds((prev) => {
        const next = { ...prev };
        delete next[activeEntry.id];
        return next;
      });
      onUpdateGradingScores((prev) => ({
        ...prev,
        [activeEntry.id]: finalScore,
      }));
      onUpdateSubmissions((prev) =>
        prev.map((s) =>
          s.id === activeEntry.linkedSubmissionId
            ? { ...s, status: 'graded', score: finalScore }
            : s
        )
      );
    } else {
      setSupplementalStatusOverrides((prev) => ({
        ...prev,
        [activeEntry.id]: { status: 'graded', score: finalScore },
      }));
    }

    showToast(
      `Saved ${formatPoints(finalScore)} / ${activeBin.maxScore} for ${activeEntry.studentName} · Synced to SF9!`
    );

    // Advance automatically to the next pending student in the filtered queue
    const nextPending = filteredEntries.find(
      (e) => e.id !== activeEntry.id && e.status === 'pending'
    );
    if (nextPending) {
      setSelectedEntryId(nextPending.id);
      setIsPlayingPreview(false);
    }
  };

  const handlePrevStudent = () => {
    if (!activeEntry || filteredEntries.length === 0) return;
    const idx = filteredEntries.findIndex((e) => e.id === activeEntry.id);
    const prevIdx = idx > 0 ? idx - 1 : filteredEntries.length - 1;
    setSelectedEntryId(filteredEntries[prevIdx].id);
    setIsPlayingPreview(false);
  };

  const handleNextUnscored = () => {
    if (filteredEntries.length === 0) return;
    const idx = activeEntry ? filteredEntries.findIndex((e) => e.id === activeEntry.id) : -1;
    const after = filteredEntries.slice(idx + 1).find((e) => e.status === 'pending');
    const wrap = filteredEntries.slice(0, Math.max(0, idx)).find((e) => e.status === 'pending');
    const target = after || wrap || filteredEntries[(idx + 1) % filteredEntries.length];
    if (target) {
      setSelectedEntryId(target.id);
      setIsPlayingPreview(false);
    }
  };

  // Kanban grouping
  const kanbanColumns = useMemo(() => {
    return {
      pending: filteredEntries.filter((e) => e.status === 'pending'),
      missing: filteredEntries.filter((e) => e.status === 'missing'),
      revision: filteredEntries.filter((e) => e.status === 'revision'),
      graded: filteredEntries.filter((e) => e.status === 'graded'),
    };
  }, [filteredEntries]);

  const getStatusBadge = (status: RosterStatus) => {
    if (status === 'pending') {
      return (
        <span className="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-500/15 text-amber-500 border border-amber-500/25">
          Needs Grading
        </span>
      );
    }
    if (status === 'missing') {
      return (
        <span className="text-[10px] font-bold px-2 py-0.5 rounded-full bg-rose-500/15 text-rose-500 border border-rose-500/25">
          Missing
        </span>
      );
    }
    if (status === 'revision') {
      return (
        <span className="text-[10px] font-bold px-2 py-0.5 rounded-full bg-purple-500/15 text-purple-400 border border-purple-500/25">
          Revision
        </span>
      );
    }
    return (
      <span className="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-500/15 text-emerald-500 border border-emerald-500/25">
        Graded
      </span>
    );
  };

  const isVideoFile = (name: string) => /\.(mp4|webm|mov)$/i.test(name);

  return (
    <div className="space-y-4 animate-in fade-in duration-200">
      {/* Toast Notification Banner */}
      {toastMessage && (
        <div className="fixed bottom-5 right-5 z-50 px-4 py-2.5 rounded-xl bg-bg2 border border-emerald-500/40 shadow-2xl flex items-center gap-2.5 text-xs font-semibold text-text animate-in fade-in slide-in-from-bottom-3">
          <span className="size-6 rounded-lg bg-emerald-500/15 text-emerald-500 flex items-center justify-center shrink-0">
            <RiCheckDoubleLine className="size-4" />
          </span>
          <span>{toastMessage}</span>
        </div>
      )}

      {/* ============================================================ */}
      {/* 1. HEADER & VIEW SWITCHER                                    */}
      {/* ============================================================ */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <h4 className="text-lg sm:text-xl font-bold text-text font-serif tracking-tight">
            Performance Tasks, Rubric Evaluation & Speed-Grader
          </h4>
          <p className="text-xs text-text2 mt-0.5">
            DepEd 50% Performance Task weight · Analytical decimal rubric scoring & section roster tracking.
          </p>
        </div>

        <div className="flex items-center gap-2 self-start sm:self-auto">
          {/* Single Icon Button View Switcher with Hover Tooltip */}
          <TooltipTrigger delay={0} closeDelay={0}>
            <AriaButton
              onPress={() => setViewMode((m) => (m === 'grader' ? 'kanban' : 'grader'))}
              aria-label={
                viewMode === 'grader'
                  ? 'Switch to 4-Column Pipeline Kanban Board'
                  : 'Switch to Split-Screen Speed-Grader'
              }
              className="size-9 rounded-xl bg-bg2 hover:bg-bg3 border border-border text-text2 hover:text-text flex items-center justify-center transition-colors cursor-pointer shadow-2xs outline-none focus-visible:ring-2 focus-visible:ring-accent"
            >
              {viewMode === 'grader' ? (
                <RiKanbanView className="size-4" />
              ) : (
                <RiLayoutLeftLine className="size-4" />
              )}
            </AriaButton>
            <Tooltip placement="top" offset={8}>
              <span className="whitespace-nowrap font-medium">
                {viewMode === 'grader'
                  ? 'Switch to 4-Column Pipeline Kanban Board'
                  : 'Switch to Split-Screen Speed-Grader'}
              </span>
            </Tooltip>
          </TooltipTrigger>

          <button
            type="button"
            onClick={onOpenNewTaskStudio}
            className="px-3.5 py-2 rounded-xl bg-school-orange text-[#0e0f14] text-xs font-bold hover:brightness-105 active:scale-95 transition-all cursor-pointer shadow-xs flex items-center gap-1.5"
          >
            <RiAddLine className="size-4" />
            <span>+ New Performance Task</span>
          </button>
        </div>
      </div>

      {/* ============================================================ */}
      {/* 2. 3-LEVEL RELATIONAL COMBOBOX FILTER BAR                    */}
      {/* ============================================================ */}
      <div className="p-3 rounded-xl bg-bg2 border border-border shadow-2xs grid grid-cols-1 md:grid-cols-3 gap-3">
        {/* Combobox 1: Subject & Class Section */}
        <div className="space-y-1">
          <label
            htmlFor="pt-offering-select"
            className="block text-[10px] font-bold uppercase tracking-wider text-text3"
          >
            1. Subject & Class Section
          </label>
          <select
            id="pt-offering-select"
            value={offeringFilter}
            onChange={(e) => {
              setOfferingFilter(e.target.value);
              setSelectedTaskBinId('ALL');
            }}
            className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs font-semibold text-text outline-none focus:border-accent cursor-pointer"
          >
            {HANDLED_OFFERINGS.map((opt) => (
              <option key={opt.key} value={opt.key}>
                {opt.label}
              </option>
            ))}
          </select>
        </div>

        {/* Combobox 2: Performance Task Deliverable Bin */}
        <div className="space-y-1">
          <label
            htmlFor="pt-bin-select"
            className="block text-[10px] font-bold uppercase tracking-wider text-text3"
          >
            2. Performance Task Deliverable Bin
          </label>
          <select
            id="pt-bin-select"
            value={effectiveTaskBinId}
            onChange={(e) => setSelectedTaskBinId(e.target.value)}
            className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs font-semibold text-text outline-none focus:border-accent cursor-pointer"
          >
            <option value="ALL">
              All Deliverable Bins ({availableBinsForOffering.length})
            </option>
            {availableBinsForOffering.map((bin) => (
              <option key={bin.id} value={bin.id}>
                {bin.isCustom ? '★ ' : ''}
                {bin.title} ({bin.maxScore} pts)
              </option>
            ))}
          </select>
        </div>

        {/* Combobox 3: Roster Turn-In Status */}
        <div className="space-y-1">
          <label
            htmlFor="pt-status-select"
            className="block text-[10px] font-bold uppercase tracking-wider text-text3"
          >
            3. Roster Turn-In Status
          </label>
          <select
            id="pt-status-select"
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value)}
            className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs font-semibold text-text outline-none focus:border-accent cursor-pointer"
          >
            <option value="ALL">All Roster Entries ({allRosterEntries.length})</option>
            <option value="pending">⚡ Needs Grading</option>
            <option value="missing">⚠️ Missing / No Submission</option>
            <option value="revision">🔄 Revision Requested</option>
            <option value="graded">✓ Graded & Synced</option>
          </select>
        </div>
      </div>

      {/* ============================================================ */}
      {/* 3A. VIEW MODE A: SPLIT-SCREEN SPEED-GRADER                   */}
      {/* ============================================================ */}
      {viewMode === 'grader' ? (
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">
          {/* Left Column (4 cols): Student Roster Queue */}
          <div className="lg:col-span-4 rounded-2xl bg-bg2 border border-border p-3.5 space-y-3 shadow-2xs">
            <div className="flex items-center justify-between gap-2 text-[11px] font-semibold text-text2 pb-2 border-b border-border">
              <span>
                <strong className="text-amber-500">{queueCounts.pending}</strong> Needs Grading ·{' '}
                <strong className="text-rose-500">{queueCounts.missing}</strong> Missing ·{' '}
                <strong className="text-emerald-500">{queueCounts.graded}</strong> Graded
              </span>
            </div>

            <div className="relative">
              <RiSearchLine className="size-3.5 text-text3 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
              <input
                type="text"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                placeholder="Search student name or LRN..."
                className="w-full pl-8 pr-3 py-1.5 rounded-lg bg-bg3 border border-border text-xs text-text placeholder:text-text3 outline-none focus:border-accent"
              />
            </div>

            <div className="space-y-2 max-h-[640px] overflow-y-auto pr-0.5">
              {filteredEntries.length === 0 ? (
                <div className="p-6 text-center rounded-xl bg-bg3/40 border border-dashed border-border text-xs text-text3">
                  No roster entries match the active filters.
                </div>
              ) : (
                filteredEntries.map((entry) => {
                  const isSelected = activeEntry?.id === entry.id;
                  const isNudged = nudgedIds[entry.id];
                  const displayScore =
                    entry.status === 'graded'
                      ? entry.score ?? gradingScores[entry.id] ?? entry.maxScore
                      : null;

                  return (
                    <div
                      key={entry.id}
                      role="button"
                      tabIndex={0}
                      onClick={() => {
                        setSelectedEntryId(entry.id);
                        setIsPlayingPreview(false);
                      }}
                      onKeyDown={(e) => {
                        if (e.key === 'Enter' || e.key === ' ') {
                          e.preventDefault();
                          setSelectedEntryId(entry.id);
                          setIsPlayingPreview(false);
                        }
                      }}
                      className={`w-full text-left p-3 rounded-xl border transition-all cursor-pointer ${
                        isSelected
                          ? 'bg-accent/10 border-accent shadow-xs'
                          : 'bg-bg3/35 border-border hover:bg-bg3/70'
                      }`}
                    >
                      <div className="flex items-start justify-between gap-2">
                        <div className="min-w-0">
                          <div className="text-xs font-bold text-text truncate">
                            {entry.studentName}
                          </div>
                          <div className="text-[10px] text-text3 font-mono mt-0.5">
                            {entry.section} · LRN {entry.lrn}
                          </div>
                        </div>
                        {getStatusBadge(entry.status)}
                      </div>

                      <div className="text-[11px] font-medium text-text2 truncate mt-1.5">
                        {entry.taskTitle}
                      </div>

                      <div className="flex items-center justify-between gap-2 mt-2 pt-1.5 border-t border-border/50 text-[10px]">
                        <span className="text-text3 flex items-center gap-1 truncate">
                          <RiTimeLine className="size-3 shrink-0" />
                          {entry.submittedTime}
                        </span>

                        {entry.status === 'missing' ? (
                          <button
                            type="button"
                            onClick={(e) => handleNudgeStudent(entry, e)}
                            className={`px-2 py-0.5 rounded-md font-bold transition-colors cursor-pointer shrink-0 ${
                              isNudged
                                ? 'bg-emerald-500/15 text-emerald-500 border border-emerald-500/30'
                                : 'bg-rose-500/15 text-rose-500 hover:bg-rose-500/25 border border-rose-500/30'
                            }`}
                          >
                            {isNudged ? '✓ Reminder sent!' : '🔔 Nudge Student'}
                          </button>
                        ) : displayScore !== null ? (
                          <span className="font-bold text-emerald-500 tabular-nums shrink-0">
                            {formatPoints(displayScore)} / {entry.maxScore}
                          </span>
                        ) : (
                          <span className="text-accent font-semibold shrink-0">
                            Open Grader &rarr;
                          </span>
                        )}
                      </div>
                    </div>
                  );
                })
              )}
            </div>
          </div>

          {/* Right Column (8 cols): Speed-Grader Pane */}
          <div className="lg:col-span-8 rounded-2xl bg-bg2 border border-border shadow-2xs overflow-hidden">
            {!activeEntry ? (
              <div className="p-10 text-center text-xs text-text3">
                Select a student from the left queue to launch the Speed-Grader.
              </div>
            ) : (
              <div className="divide-y divide-border">
                {/* Top Bar */}
                <div className="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-bg3/30">
                  <div className="space-y-1 min-w-0">
                    <div className="flex items-center gap-2 flex-wrap">
                      <h5 className="text-base font-bold text-text">{activeEntry.studentName}</h5>
                      <span className="text-[10px] font-bold px-2 py-0.5 rounded-full bg-accent/10 text-accent border border-accent/25">
                        {activeEntry.section}
                      </span>
                      {getStatusBadge(activeEntry.status)}
                    </div>
                    <div className="flex items-center gap-2.5 text-[11px] text-text3 flex-wrap">
                      <span className="font-mono">
                        {activeEntry.studentId} · LRN {activeEntry.lrn}
                      </span>
                      <span>·</span>
                      <span className="flex items-center gap-1 text-text2 font-medium">
                        <RiTimeLine className="size-3.5" />
                        {activeEntry.submissionTimingNote}
                      </span>
                    </div>
                  </div>

                  <div className="flex items-center gap-2 shrink-0">
                    <button
                      type="button"
                      onClick={handlePrevStudent}
                      className="px-2.5 py-1.5 rounded-lg bg-bg3 hover:bg-bg2 border border-border text-xs font-semibold text-text2 hover:text-text flex items-center gap-1 cursor-pointer transition-colors"
                    >
                      <RiArrowLeftLine className="size-3.5" />
                      <span>Prev</span>
                    </button>
                    <button
                      type="button"
                      onClick={handleNextUnscored}
                      className="px-3 py-1.5 rounded-lg bg-accent/15 hover:bg-accent/25 border border-accent/30 text-xs font-semibold text-accent flex items-center gap-1 cursor-pointer transition-colors"
                    >
                      <span>Next Unscored</span>
                      <RiArrowRightLine className="size-3.5" />
                    </button>
                  </div>
                </div>

                {/* Middle Split: Artifact Viewer (5 cols) + Analytical Decimal Rubric (7 cols) */}
                <div className="p-4 grid grid-cols-1 xl:grid-cols-12 gap-4">
                  {/* Left: Student Artifact Viewer */}
                  <div className="xl:col-span-5 space-y-3">
                    <div className="flex items-center justify-between text-[11px] font-bold uppercase tracking-wider text-text3">
                      <span>Student Artifact Preview</span>
                      <span className="font-mono text-[10px] text-text3">
                        {activeBin.allowedFormats}
                      </span>
                    </div>

                    {activeEntry.status === 'missing' ? (
                      <div className="p-5 rounded-xl bg-rose-500/5 border border-rose-500/30 space-y-3 text-center">
                        <div className="size-11 rounded-xl bg-rose-500/15 text-rose-500 flex items-center justify-center mx-auto">
                          <RiErrorWarningLine className="size-6" />
                        </div>
                        <div className="space-y-1">
                          <div className="text-sm font-bold text-text">
                            No Deliverable Uploaded Yet
                          </div>
                          <p className="text-xs text-text2">
                            {activeEntry.studentName} has not submitted{' '}
                            <span className="font-semibold text-text">{activeBin.title}</span>.
                          </p>
                        </div>

                        <div className="p-3 rounded-lg bg-bg3/70 border border-border text-left text-[11px] space-y-1">
                          <div className="text-text3">Student Contact & Roster Info</div>
                          <div className="font-semibold text-text flex items-center gap-1.5">
                            <RiMailSendLine className="size-3.5 text-accent" />
                            <span className="truncate">{activeEntry.email}</span>
                          </div>
                          <div className="text-text2 font-mono">
                            {activeEntry.section} · LRN {activeEntry.lrn}
                          </div>
                        </div>

                        <button
                          type="button"
                          onClick={() => handleNudgeStudent(activeEntry)}
                          className={`w-full py-2.5 px-3 rounded-xl text-xs font-bold flex items-center justify-center gap-1.5 transition-all cursor-pointer ${
                            nudgedIds[activeEntry.id]
                              ? 'bg-emerald-500/15 text-emerald-500 border border-emerald-500/30'
                              : 'bg-rose-500 text-white hover:brightness-110 shadow-xs'
                          }`}
                        >
                          <RiNotification3Line className="size-4" />
                          <span>
                            {nudgedIds[activeEntry.id]
                              ? '✓ Reminder Sent to Student!'
                              : '🔔 Send Deadline Nudge Notification'}
                          </span>
                        </button>
                      </div>
                    ) : (
                      <div className="rounded-xl bg-bg3/40 border border-border overflow-hidden">
                        {/* File Header */}
                        <div className="p-3 border-b border-border flex items-center justify-between gap-2 bg-bg3/70">
                          <div className="flex items-center gap-2 min-w-0">
                            <span className="size-7 rounded-lg bg-accent/15 text-accent flex items-center justify-center shrink-0">
                              {isVideoFile(activeEntry.fileAttachment) ? (
                                <RiVideoLine className="size-4" />
                              ) : (
                                <RiFilePdfLine className="size-4" />
                              )}
                            </span>
                            <div className="min-w-0">
                              <div className="text-xs font-bold text-text truncate">
                                {activeEntry.fileAttachment}
                              </div>
                              <div className="text-[10px] text-text3">
                                {activeEntry.fileSize} · Verified Clean Scan
                              </div>
                            </div>
                          </div>
                          <div className="flex items-center gap-1.5 shrink-0">
                            <button
                              type="button"
                              onClick={() => {
                                setForcePdfInModal(false);
                                setIsPreviewModalOpen(true);
                              }}
                              className="px-2 py-1 rounded-lg bg-accent/15 hover:bg-accent/25 border border-accent/30 text-accent text-[11px] font-bold flex items-center gap-1 cursor-pointer transition-colors"
                              title="Open full-screen preview"
                            >
                              <RiFullscreenLine className="size-3.5" />
                              <span>Full Screen</span>
                            </button>
                            <a
                              href={SAMPLE_PDF_URL}
                              download={activeEntry.fileAttachment}
                              onClick={() =>
                                showToast(`Downloading ${activeEntry.fileAttachment}...`)
                              }
                              className="p-1.5 rounded-lg bg-bg2 hover:bg-bg border border-border text-text2 hover:text-text cursor-pointer shrink-0"
                              title="Download artifact"
                            >
                              <RiDownload2Line className="size-3.5" />
                            </a>
                          </div>
                        </div>

                        {/* Simulated Video or Live PDF Document Canvas */}
                        <div className="p-4">
                          {isVideoFile(activeEntry.fileAttachment) ? (
                            <div className="rounded-xl bg-[#0e0f14] border border-border/80 aspect-video flex flex-col justify-between p-3 relative overflow-hidden">
                              <div className="flex items-center justify-between text-[10px] text-white/75">
                                <span className="px-2 py-0.5 rounded bg-white/10 font-mono">
                                  1080p HD · {isPlayingPreview ? 'PLAYING 01:24' : 'PAUSED 00:00'}
                                </span>
                                <span className="px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-300 font-semibold">
                                  Audio Track OK
                                </span>
                              </div>
                              <div className="flex items-center justify-center">
                                <button
                                  type="button"
                                  onClick={() => setIsPlayingPreview((p) => !p)}
                                  className="size-12 rounded-full bg-school-orange text-[#0e0f14] flex items-center justify-center shadow-lg hover:scale-105 transition-transform cursor-pointer"
                                  aria-label={isPlayingPreview ? 'Pause preview' : 'Play preview'}
                                >
                                  {isPlayingPreview ? (
                                    <RiPauseLine className="size-6" />
                                  ) : (
                                    <RiPlayFill className="size-6 ml-0.5" />
                                  )}
                                </button>
                              </div>
                              <div className="space-y-1">
                                <div className="h-1 w-full rounded-full bg-white/15 overflow-hidden">
                                  <div
                                    className={`h-full bg-school-orange rounded-full transition-all duration-300 ${
                                      isPlayingPreview ? 'w-2/3' : 'w-1/4'
                                    }`}
                                  />
                                </div>
                                <div className="flex justify-between text-[10px] text-white/60 font-mono">
                                  <span>{isPlayingPreview ? '01:24' : '00:48'}</span>
                                  <span>03:42</span>
                                </div>
                              </div>
                            </div>
                          ) : (
                            <div className="rounded-xl bg-bg2 border border-border overflow-hidden">
                              <div className="px-3 py-2 border-b border-border flex items-center justify-between text-[10px] text-text3 font-mono bg-bg3/40">
                                <span>LIVE PDF PREVIEW</span>
                                <button
                                  type="button"
                                  onClick={() => {
                                    setForcePdfInModal(true);
                                    setIsPreviewModalOpen(true);
                                  }}
                                  className="text-accent hover:underline font-sans font-semibold flex items-center gap-1 cursor-pointer"
                                >
                                  <RiFullscreenLine className="size-3" />
                                  <span>Expand Modal</span>
                                </button>
                              </div>
                              <iframe
                                src={`${SAMPLE_PDF_URL}#toolbar=0&navpanes=0`}
                                title={`${activeEntry.studentName} — ${activeEntry.fileAttachment}`}
                                className="w-full h-56 bg-white"
                              />
                            </div>
                          )}
                        </div>

                        {activeEntry.studentNote && (
                          <div className="px-4 pb-4">
                            <div className="p-2.5 rounded-lg bg-bg2 border border-border/70 text-[11px]">
                              <span className="font-bold text-text3 uppercase tracking-wider text-[10px] block mb-0.5">
                                Student Submission Note
                              </span>
                              <span className="text-text2">{activeEntry.studentNote}</span>
                            </div>
                          </div>
                        )}
                      </div>
                    )}
                  </div>

                  {/* Right: Analytical Rubric Breakdown (DECIMAL NUMERIC INPUTS — NO SLIDERS!) */}
                  <div className="xl:col-span-7 space-y-3">
                    <div className="flex items-center justify-between gap-2 flex-wrap">
                      <div className="flex items-center gap-1.5">
                        <RiFileList3Line className="size-4 text-accent" />
                        <span className="text-xs font-bold text-text">
                          Analytical Rubric Scoring
                        </span>
                      </div>

                      <div className="px-3 py-1 rounded-full bg-emerald-500/15 border border-emerald-500/30 text-emerald-500 text-xs font-bold tabular-nums">
                        Total: {formatPoints(activeTotalScore)} / {activeBin.maxScore} pts (
                        {activePercentage}%)
                      </div>
                    </div>

                    <div className="space-y-2.5">
                      {activeBin.rubricCriteria.map((crit) => {
                        const entered = activeCriterionScores[crit.id] ?? 0;
                        const pct = crit.points > 0 ? Math.min(100, (entered / crit.points) * 100) : 0;

                        return (
                          <div
                            key={crit.id}
                            className="p-3 rounded-xl bg-bg3/40 border border-border space-y-2"
                          >
                            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
                              <div className="min-w-0">
                                <div className="text-xs font-bold text-text">{crit.title}</div>
                                <div className="text-[11px] text-text2 leading-snug">
                                  {crit.description}
                                </div>
                              </div>

                              <div className="flex items-center gap-1.5 shrink-0">
                                <input
                                  type="number"
                                  step="0.25"
                                  min={0}
                                  max={crit.points}
                                  aria-label={`${crit.title} score`}
                                  value={entered}
                                  onChange={(e) =>
                                    handleCriterionChange(crit, Number(e.target.value))
                                  }
                                  className="w-20 px-2.5 py-1.5 rounded-lg bg-bg2 border border-border text-sm font-bold text-text text-right tabular-nums outline-none focus:border-accent"
                                />
                                <span className="text-xs font-semibold text-text3 whitespace-nowrap">
                                  / {crit.points} pts
                                </span>
                              </div>
                            </div>

                            {/* Thin read-only progress bar + quick decimal adjustment mini-buttons */}
                            <div className="space-y-1.5">
                              <div className="h-1.5 w-full rounded-full bg-bg2 overflow-hidden">
                                <div
                                  className="h-full rounded-full bg-accent transition-all duration-200"
                                  style={{ width: `${pct}%` }}
                                />
                              </div>

                              <div className="flex items-center justify-end gap-1">
                                <button
                                  type="button"
                                  onClick={() => handleCriterionChange(crit, crit.points)}
                                  className="px-2 py-0.5 rounded bg-bg2 hover:bg-accent/15 border border-border text-[10px] font-bold text-text2 hover:text-accent cursor-pointer transition-colors"
                                >
                                  Max
                                </button>
                                {[-0.5, -1, -2].map((delta) => (
                                  <button
                                    key={delta}
                                    type="button"
                                    onClick={() => handleCriterionChange(crit, entered + delta)}
                                    className="px-2 py-0.5 rounded bg-bg2 hover:bg-bg border border-border text-[10px] font-semibold text-text3 hover:text-text cursor-pointer transition-colors tabular-nums"
                                  >
                                    {delta}
                                  </button>
                                ))}
                              </div>
                            </div>
                          </div>
                        );
                      })}
                    </div>

                    {/* Qualitative Feedback Remarks */}
                    <div className="space-y-1.5 pt-1">
                      <label
                        htmlFor="speed-grader-feedback"
                        className="block text-[11px] font-bold uppercase tracking-wider text-text3"
                      >
                        Qualitative Feedback Remarks
                      </label>
                      <textarea
                        id="speed-grader-feedback"
                        rows={3}
                        placeholder="Provide constructive feedback on strengths, rubric deductions, and next steps..."
                        value={activeFeedback}
                        onChange={(e) => handleFeedbackChange(e.target.value)}
                        className="w-full px-3 py-2 rounded-xl bg-bg3/50 border border-border text-xs text-text placeholder:text-text3 outline-none focus:border-accent resize-none"
                      />
                    </div>
                  </div>
                </div>

                {/* Bottom Action Bar */}
                <div className="p-4 bg-bg3/30 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                  <div className="flex items-center gap-1.5 text-xs font-medium text-emerald-500">
                    <RiShieldCheckLine className="size-4 shrink-0" />
                    <span>✓ Auto-computes 50% Performance Task weight in DepEd SF9 Gradebook</span>
                  </div>

                  <div className="flex items-center gap-2 self-end sm:self-auto">
                    <button
                      type="button"
                      onClick={handleRequestRevision}
                      className="px-3.5 py-2 rounded-xl bg-purple-500/15 hover:bg-purple-500/25 border border-purple-500/30 text-purple-400 text-xs font-bold flex items-center gap-1.5 cursor-pointer transition-colors"
                    >
                      <RiRefreshLine className="size-3.5" />
                      <span>Request Revision</span>
                    </button>

                    <button
                      type="button"
                      onClick={handleApproveAndSave}
                      className="px-4 py-2 rounded-xl bg-green text-[#0e0f14] text-xs font-bold hover:brightness-105 active:scale-95 transition-all cursor-pointer shadow-xs flex items-center gap-1.5"
                    >
                      <RiCheckLine className="size-4" />
                      <span>Approve & Save Grade &rarr;</span>
                    </button>
                  </div>
                </div>
              </div>
            )}
          </div>
        </div>
      ) : (
        /* ============================================================ */
        /* 3B. VIEW MODE B: 4-COLUMN GRADING PIPELINE KANBAN BOARD      */
        /* ============================================================ */
        <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
          {(
            [
              {
                key: 'pending',
                title: 'Needs Grading',
                icon: RiFlashlightFill,
                headerClass: 'bg-amber-500/10 border-amber-500/30 text-amber-500',
                badgeClass: 'bg-amber-500/20 text-amber-500',
                items: kanbanColumns.pending,
              },
              {
                key: 'missing',
                title: 'Missing / No Submission',
                icon: RiErrorWarningLine,
                headerClass: 'bg-rose-500/10 border-rose-500/30 text-rose-500',
                badgeClass: 'bg-rose-500/20 text-rose-500',
                items: kanbanColumns.missing,
              },
              {
                key: 'revision',
                title: 'Revision Requested',
                icon: RiRefreshLine,
                headerClass: 'bg-purple-500/10 border-purple-500/30 text-purple-400',
                badgeClass: 'bg-purple-500/20 text-purple-400',
                items: kanbanColumns.revision,
              },
              {
                key: 'graded',
                title: 'Graded & Synced to SF9',
                icon: RiCheckDoubleLine,
                headerClass: 'bg-emerald-500/10 border-emerald-500/30 text-emerald-500',
                badgeClass: 'bg-emerald-500/20 text-emerald-500',
                items: kanbanColumns.graded,
              },
            ] as const
          ).map((col) => {
            const ColIcon = col.icon;
            return (
              <div
                key={col.key}
                className="rounded-2xl bg-bg2 border border-border p-3 space-y-3 flex flex-col min-h-[420px]"
              >
                <div
                  className={`px-3 py-2 rounded-xl border flex items-center justify-between ${col.headerClass}`}
                >
                  <div className="flex items-center gap-1.5 text-xs font-bold">
                    <ColIcon className="size-4 shrink-0" />
                    <span>{col.title}</span>
                  </div>
                  <span className={`text-[11px] font-bold px-2 py-0.5 rounded-full ${col.badgeClass}`}>
                    {col.items.length}
                  </span>
                </div>

                <div className="space-y-2.5 flex-1">
                  {col.items.length === 0 ? (
                    <div className="p-5 rounded-xl border border-dashed border-border text-center text-[11px] text-text3">
                      No students in this stage.
                    </div>
                  ) : (
                    col.items.map((entry) => {
                      const isNudged = nudgedIds[entry.id];
                      const finalScore =
                        entry.score ?? gradingScores[entry.id] ?? entry.maxScore;

                      return (
                        <div
                          key={entry.id}
                          role="button"
                          tabIndex={0}
                          onClick={() => {
                            setSelectedEntryId(entry.id);
                            setViewMode('grader');
                            setIsPlayingPreview(false);
                          }}
                          onKeyDown={(e) => {
                            if (e.key === 'Enter' || e.key === ' ') {
                              e.preventDefault();
                              setSelectedEntryId(entry.id);
                              setViewMode('grader');
                              setIsPlayingPreview(false);
                            }
                          }}
                          className="p-3 rounded-xl bg-bg3/45 hover:bg-bg3 border border-border hover:border-accent/40 transition-all cursor-pointer space-y-2"
                        >
                          <div className="flex items-start justify-between gap-2">
                            <div className="min-w-0">
                              <div className="text-xs font-bold text-text truncate">
                                {entry.studentName}
                              </div>
                              <div className="text-[10px] font-mono text-text3">
                                {entry.section} · {entry.studentId}
                              </div>
                            </div>

                            {col.key === 'graded' && (
                              <span className="text-xs font-bold text-emerald-500 tabular-nums shrink-0">
                                {formatPoints(finalScore)} / {entry.maxScore}
                              </span>
                            )}
                          </div>

                          <div className="text-[11px] font-medium text-text2 line-clamp-2">
                            {entry.taskTitle}
                          </div>

                          <div className="flex items-center justify-between gap-2 pt-1.5 border-t border-border/50 text-[10px]">
                            <span className="text-text3 truncate">{entry.submittedTime}</span>

                            {col.key === 'missing' ? (
                              <button
                                type="button"
                                onClick={(e) => handleNudgeStudent(entry, e)}
                                className={`px-2 py-0.5 rounded-md font-bold cursor-pointer shrink-0 ${
                                  isNudged
                                    ? 'bg-emerald-500/15 text-emerald-500 border border-emerald-500/30'
                                    : 'bg-rose-500/15 text-rose-500 hover:bg-rose-500/25 border border-rose-500/30'
                                }`}
                              >
                                {isNudged ? '✓ Sent' : '🔔 Nudge'}
                              </button>
                            ) : (
                              <span className="text-accent font-semibold shrink-0">
                                Score &rarr;
                              </span>
                            )}
                          </div>
                        </div>
                      );
                    })
                  )}
                </div>
              </div>
            );
          })}
        </div>
      )}

      {/* ============================================================ */}
      {/* 4. FULL-SCREEN STUDENT WORK PREVIEW MODAL                    */}
      {/* ============================================================ */}
      {isPreviewModalOpen && activeEntry && activeEntry.status !== 'missing' && (
        <div
          className="fixed inset-0 z-50 bg-black/85 backdrop-blur-md flex flex-col p-3 sm:p-5 animate-in fade-in duration-150"
          role="dialog"
          aria-modal="true"
          aria-label={`Full-screen preview for ${activeEntry.studentName}`}
          onClick={() => setIsPreviewModalOpen(false)}
        >
          <div
            className="w-full h-full max-w-7xl mx-auto rounded-2xl bg-bg2 border border-border shadow-2xl flex flex-col overflow-hidden"
            onClick={(e) => e.stopPropagation()}
          >
            {/* Modal Header */}
            <div className="px-4 py-3 border-b border-border bg-bg3/50 flex flex-wrap items-center justify-between gap-3 shrink-0">
              <div className="flex items-center gap-3 min-w-0">
                <span className="size-9 rounded-xl bg-accent/15 text-accent flex items-center justify-center shrink-0">
                  {isVideoFile(activeEntry.fileAttachment) && !forcePdfInModal ? (
                    <RiVideoLine className="size-5" />
                  ) : (
                    <RiFilePdfLine className="size-5" />
                  )}
                </span>
                <div className="min-w-0">
                  <div className="flex items-center gap-2 flex-wrap">
                    <h5 className="text-sm font-bold text-text truncate">
                      {activeEntry.studentName}
                    </h5>
                    <span className="text-[10px] font-bold px-2 py-0.5 rounded-full bg-accent/10 text-accent border border-accent/25">
                      {activeEntry.section}
                    </span>
                    <span className="text-[11px] font-mono text-text3 truncate">
                      {activeEntry.fileAttachment} ({activeEntry.fileSize})
                    </span>
                  </div>
                  <div className="text-xs text-text2 truncate">{activeEntry.taskTitle}</div>
                </div>
              </div>

              <div className="flex items-center gap-2 flex-wrap">
                {isVideoFile(activeEntry.fileAttachment) && (
                  <button
                    type="button"
                    onClick={() => setForcePdfInModal((v) => !v)}
                    className="px-3 py-1.5 rounded-lg bg-bg2 hover:bg-bg3 border border-border text-xs font-semibold text-text2 hover:text-text cursor-pointer transition-colors"
                  >
                    {forcePdfInModal ? 'Back to Video Stage' : 'Preview Attached PDF'}
                  </button>
                )}

                <span className="px-2.5 py-1 rounded-full bg-emerald-500/15 border border-emerald-500/30 text-emerald-500 text-xs font-bold tabular-nums">
                  {formatPoints(activeTotalScore)} / {activeBin.maxScore} pts
                </span>

                <a
                  href={SAMPLE_PDF_URL}
                  target="_blank"
                  rel="noreferrer"
                  className="px-2.5 py-1.5 rounded-lg bg-bg2 hover:bg-bg3 border border-border text-xs font-semibold text-text2 hover:text-text flex items-center gap-1 transition-colors"
                >
                  <RiExternalLinkLine className="size-3.5" />
                  <span className="hidden sm:inline">New Tab</span>
                </a>

                <button
                  type="button"
                  onClick={() => setIsPreviewModalOpen(false)}
                  className="px-3 py-1.5 rounded-lg bg-bg3 hover:bg-rose-500/15 border border-border hover:border-rose-500/30 text-xs font-bold text-text hover:text-rose-500 flex items-center gap-1 cursor-pointer transition-colors"
                >
                  <RiCloseLine className="size-4" />
                  <span>Close</span>
                </button>
              </div>
            </div>

            {/* Modal Body */}
            <div className="flex-1 min-h-0 p-3 sm:p-4 bg-bg flex flex-col items-center justify-center">
              {isVideoFile(activeEntry.fileAttachment) && !forcePdfInModal ? (
                <div className="w-full max-w-5xl rounded-2xl bg-[#0e0f14] border border-border aspect-video flex flex-col justify-between p-5 shadow-2xl">
                  <div className="flex items-center justify-between text-xs text-white/80">
                    <span className="px-2.5 py-1 rounded-md bg-white/10 font-mono">
                      1080p HD · {isPlayingPreview ? 'PLAYING 01:24' : 'PAUSED 00:00'}
                    </span>
                    <span className="px-2.5 py-1 rounded-md bg-emerald-500/20 text-emerald-300 font-semibold">
                      Audio Track OK · {activeEntry.fileAttachment}
                    </span>
                  </div>

                  <div className="flex items-center justify-center">
                    <button
                      type="button"
                      onClick={() => setIsPlayingPreview((p) => !p)}
                      className="size-16 rounded-full bg-school-orange text-[#0e0f14] flex items-center justify-center shadow-xl hover:scale-105 transition-transform cursor-pointer"
                      aria-label={isPlayingPreview ? 'Pause full-screen preview' : 'Play full-screen preview'}
                    >
                      {isPlayingPreview ? (
                        <RiPauseLine className="size-8" />
                      ) : (
                        <RiPlayFill className="size-8 ml-0.5" />
                      )}
                    </button>
                  </div>

                  <div className="space-y-2">
                    <div className="h-1.5 w-full rounded-full bg-white/15 overflow-hidden">
                      <div
                        className={`h-full bg-school-orange rounded-full transition-all duration-300 ${
                          isPlayingPreview ? 'w-2/3' : 'w-1/4'
                        }`}
                      />
                    </div>
                    <div className="flex justify-between text-xs text-white/60 font-mono">
                      <span>{isPlayingPreview ? '01:24' : '00:48'}</span>
                      <span>03:42</span>
                    </div>
                  </div>
                </div>
              ) : (
                <iframe
                  src={`${SAMPLE_PDF_URL}#toolbar=1`}
                  title={`Full-screen PDF preview: ${activeEntry.fileAttachment}`}
                  className="w-full h-full rounded-xl border border-border bg-white shadow-xl"
                />
              )}
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
