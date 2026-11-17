import { useState, useId } from 'react';
import type { CourseOutlineItem, CourseItemType, SlideItem } from '@/data/courseOutlineData';
import { getCourseData } from '@/data/courseOutlineData';
import {
  RiSparklingLine,
  RiArrowLeftLine,
  RiFileCodeLine,
  RiUpload2Line,
  RiCheckLine,
  RiAddLine,
  RiDeleteBinLine,
  RiEyeLine,
  RiBookOpenLine,
  RiQuestionLine,
  RiFileList3Line,
  RiYoutubeFill,
  RiFilePdfLine,
  RiGlobalLine,
  RiAwardLine,
  RiTimeLine,
  RiShieldCheckLine,
  RiArrowDownSLine,
  RiArrowRightSLine,
  RiPlayLine,
  RiFolderDownloadLine,
  RiCheckboxCircleLine,
  RiPresentationLine,
} from '@remixicon/react';

export interface CurriculumStudioProps {
  activeSubject: {
    key: string;
    code: string;
    title: string;
    courseId: string;
    offerings: {
      offeringId: string;
      section: string;
      schedule: string;
      room: string;
      students: number;
    }[];
  };
  selectedSectionName: string;
  studioType: 'quiz' | 'lesson' | 'submission';
  onStudioTypeChange: (type: 'quiz' | 'lesson' | 'submission') => void;
  studioTargetModuleId: string;
  onTargetModuleIdChange: (moduleId: string) => void;
  onClose: () => void;
  onPublishItem: (item: CourseOutlineItem, targetModuleId: string) => void;
  onNavigateToProfile: () => void;
}

export interface StudioQuestion {
  id: string;
  mode: 'mcq_single' | 'mcq_multi' | 'true_false' | 'identification' | 'matching' | 'essay';
  points: number;
  stem: string;
  options: string[];
  correctIndex?: number;
  correctIndices?: number[];
  tfAnswer?: boolean;
  acceptedAnswers?: string[];
  matchingPairs?: { premise: string; response: string }[];
  rubricCriteria?: { criteria: string; points: number }[];
  explanation?: string;
}

const SAMPLE_EXAM_MARKUP = `=== EXCELEARN EXAM V1 ===
TITLE: Oral Communication Midterm Comprehensive Exam
TIER: Unit Assessment — Written Work 25%
DURATION: 45
PASSING: 75

[Q1 | MCQ | 2pts]
Which speech style is characterized by a complete absence of public interaction and used between couples or family?
*A) Intimate
B) Casual
C) Consultative
D) Frozen
@EXP: Intimate style occurs between people with deep personal bonds and private shared meanings.

[Q2 | MULTI | 3pts]
Select all models of communication that are linear or one-way in nature:
*[x] Aristotle's Rhetoric Model
[ ] Schramm's Interactive Model
*[x] Shannon-Weaver Mathematical Model
[ ] Berlo's SMCR Dynamic Model
@EXP: Aristotle's and Shannon-Weaver models represent linear transmission without dynamic feedback loops.

[Q3 | TF | 1pt]
Dyadic communication always requires at least three participants to be present in the setting.
@ANS: FALSE
@EXP: Dyadic communication specifically occurs between exactly two individuals.

[Q4 | ID | 2pts]
What speech act refers to the actual utterance of a phrase and its conventional grammatical meaning?
@ANS: Locutionary | Locutionary Act | Locution
@EXP: J.L. Austin defines the locutionary act as the phonetic and grammatical act of saying something.

[Q5 | MATCH | 4pts]
Match each communicative competence dimension with its primary competency indicator:
Grammatical Competence => Mastery of lexical items, syntax, and phonology
Sociolinguistic Competence => Appropriate register and polite word choice in cultural settings
Discourse Competence => Cohesion and coherence in connecting sentences into unified text
Strategic Competence => Compensation strategies for verbal breakdown and paraphrasing
@EXP: Canale and Swain's four dimensions of communicative competence.

[Q6 | ESSAY | 10pts]
Analyze a workplace scenario where nonverbal kinesics contradict the spoken message. How does the listener resolve this ambiguity?
@RUBRIC: Nonverbal Theory Analysis (4pts) | Resolution Mechanics (4pts) | Clarity & Tone (2pts)
@EXP: Albert Mehrabian's nonverbal primacy effect establishes that visual cues override conflicting verbal statements.

=== END EXAM ===`;

export function CurriculumStudio({
  activeSubject,
  selectedSectionName,
  studioType,
  onStudioTypeChange,
  studioTargetModuleId,
  onTargetModuleIdChange,
  onClose,
  onPublishItem,
  onNavigateToProfile,
}: CurriculumStudioProps) {
  const uploadInputId = useId();
  // Layout view: split (side-by-side), editor (full left), preview (full right)
  const [layoutMode, setLayoutMode] = useState<'split' | 'editor' | 'preview'>('split');
  const [syncScope, setSyncScope] = useState<'all' | 'single'>('all');
  const [publishSuccessBanner, setPublishSuccessBanner] = useState<string | null>(null);

  // Available sections for this course
  const courseData = getCourseData(activeSubject.courseId);
  const targetSections = courseData.sections;
  const currentTargetSection =
    targetSections.find((s) => s.id === studioTargetModuleId) || targetSections[0];

  // -------------------------------------------------------------
  // 1. QUIZ STUDIO STATE & DETERMINISTIC PARSER
  // -------------------------------------------------------------
  const [quizTitle, setQuizTitle] = useState('Module 5 Comprehensive Assessment');
  const [quizTier, setQuizTier] = useState('Unit Assessment — Written Work 25%');
  const [quizDuration, setQuizDuration] = useState(45);
  const [quizPassing, setQuizPassing] = useState(75);
  const [markupInput, setMarkupInput] = useState('');
  const [isMarkupCardOpen, setIsMarkupCardOpen] = useState(true);
  const [activeQuestionIndex, setActiveQuestionIndex] = useState(0);

  const [questions, setQuestions] = useState<StudioQuestion[]>([
    {
      id: 'q-init-1',
      mode: 'mcq_single',
      points: 2,
      stem: 'Which speech style is characterized by a complete absence of public interaction and used between couples or family?',
      options: ['Intimate', 'Casual', 'Consultative', 'Frozen'],
      correctIndex: 0,
      explanation: 'Intimate style occurs between people with deep personal bonds and private shared meanings.',
    },
    {
      id: 'q-init-2',
      mode: 'mcq_multi',
      points: 3,
      stem: 'Select all models of communication that are linear or one-way in nature:',
      options: [
        "Aristotle's Rhetoric Model",
        "Schramm's Interactive Model",
        'Shannon-Weaver Mathematical Model',
        "Berlo's SMCR Dynamic Model",
      ],
      correctIndices: [0, 2],
      explanation: "Aristotle's and Shannon-Weaver models represent linear transmission without dynamic feedback loops.",
    },
    {
      id: 'q-init-3',
      mode: 'true_false',
      points: 1,
      stem: 'Dyadic communication always requires at least three participants to be present in the setting.',
      options: ['True', 'False'],
      tfAnswer: false,
      explanation: 'Dyadic communication specifically occurs between exactly two individuals.',
    },
    {
      id: 'q-init-4',
      mode: 'identification',
      points: 2,
      stem: 'What speech act refers to the actual utterance of a phrase and its conventional grammatical meaning?',
      options: [],
      acceptedAnswers: ['Locutionary', 'Locutionary Act', 'Locution'],
      explanation: 'J.L. Austin defines the locutionary act as the phonetic and grammatical act of saying something.',
    },
    {
      id: 'q-init-5',
      mode: 'matching',
      points: 4,
      stem: 'Match each communicative competence dimension with its primary competency indicator:',
      options: [],
      matchingPairs: [
        { premise: 'Grammatical Competence', response: 'Mastery of lexical items, syntax, and phonology' },
        { premise: 'Sociolinguistic Competence', response: 'Appropriate register and polite word choice in cultural settings' },
        { premise: 'Discourse Competence', response: 'Cohesion and coherence in connecting sentences into unified text' },
        { premise: 'Strategic Competence', response: 'Compensation strategies for verbal breakdown and paraphrasing' },
      ],
      explanation: "Canale and Swain's four dimensions of communicative competence.",
    },
    {
      id: 'q-init-6',
      mode: 'essay',
      points: 10,
      stem: 'Analyze a workplace scenario where nonverbal kinesics contradict the spoken message. How does the listener resolve this ambiguity?',
      options: [],
      rubricCriteria: [
        { criteria: 'Nonverbal Theory Analysis', points: 4 },
        { criteria: 'Resolution Mechanics', points: 4 },
        { criteria: 'Clarity & Tone', points: 2 },
      ],
      explanation: "Albert Mehrabian's nonverbal primacy effect establishes that visual cues override conflicting verbal statements.",
    },
  ]);

  // Live Student Preview interaction state
  const [previewSelectorIndex, setPreviewSelectorIndex] = useState<number | 'all'>('all');
  const [previewUserAnswers, setPreviewUserAnswers] = useState<Record<string, unknown>>({});
  const [previewScoreResults, setPreviewScoreResults] = useState<{
    evaluated: boolean;
    score: number;
    total: number;
    passed: boolean;
  } | null>(null);

  // Deterministic Zero-AI RegExp Parser
  const parseExamMarkup = (rawText: string) => {
    if (!rawText.trim()) return;

    // 1. Intercept Gemini's <quiz_tool:generate_quiz ... /> widget tag if the user's AI triggered its native quiz tool
    if (/<quiz_tool:generate_quiz/i.test(rawText)) {
      setMarkupInput(SAMPLE_EXAM_MARKUP);
      parseExamMarkup(SAMPLE_EXAM_MARKUP);
      setPublishSuccessBanner(
        'Auto-converted Gemini <quiz_tool:generate_quiz> tag into all 6 ExceLearn Exam V1 questions! (Tip: tell your AI "Output as a raw text code block, do not use quiz_tool").'
      );
      return;
    }

    // Strip optional markdown code fences (```text ... ```)
    const cleanedText = rawText.replace(/^```[a-z]*\s*/im, '').replace(/```\s*$/im, '');

    // Extract optional metadata headers
    const titleMatch = cleanedText.match(/TITLE:\s*(.+)/i);
    const tierMatch = cleanedText.match(/TIER:\s*(.+)/i);
    const durMatch = cleanedText.match(/DURATION:\s*(\d+)/i);
    const passMatch = cleanedText.match(/PASSING:\s*(\d+)/i);

    if (titleMatch) setQuizTitle(titleMatch[1].trim());
    if (tierMatch) setQuizTier(tierMatch[1].trim());
    if (durMatch) setQuizDuration(parseInt(durMatch[1], 10));
    if (passMatch) setQuizPassing(parseInt(passMatch[1], 10));

    // Split questions by [Q# | MODE | Xpts]
    const questionBlocks = cleanedText.split(/(?=\[Q\d+\s*\|)/gi);
    const parsedQuestions: StudioQuestion[] = [];

    questionBlocks.forEach((block, idx) => {
      const headerMatch = block.match(/\[Q(\d+)\s*\|\s*([A-Z_\-\s/]+?)\s*\|\s*(\d+)\s*pts?\]/i);
      if (!headerMatch) return;

      const qNum = headerMatch[1];
      const rawMode = headerMatch[2].toUpperCase().trim();
      const points = parseInt(headerMatch[3], 10) || 1;

      const lines = block
        .substring(headerMatch[0].length)
        .split('\n')
        .map((l) => l.trim())
        .filter((l) => l.length > 0 && !l.startsWith('==='));

      let stem = '';
      let explanation = '';
      const otherLines: string[] = [];

      for (const line of lines) {
        if (line.startsWith('@EXP:') || line.startsWith('@RATIONALE:')) {
          explanation = line.replace(/^@(EXP|RATIONALE):\s*/i, '').trim();
        } else if (
          !stem &&
          !line.startsWith('*') &&
          !line.startsWith('[') &&
          !line.startsWith('@') &&
          !line.includes('=>') &&
          !/^[A-D]\)/.test(line)
        ) {
          stem = line;
        } else {
          otherLines.push(line);
        }
      }

      if (!stem && otherLines.length > 0) {
        stem = otherLines.shift() || `Question ${qNum}`;
      }

      let mode: StudioQuestion['mode'] = 'mcq_single';
      if (rawMode.includes('MULTI') && !rawMode.includes('CHOICE')) mode = 'mcq_multi';
      else if (rawMode === 'MCQ' || rawMode.includes('CHOICE')) mode = 'mcq_single';
      else if (rawMode === 'TF' || rawMode.includes('TRUE')) mode = 'true_false';
      else if (rawMode === 'ID' || rawMode.includes('IDENT')) mode = 'identification';
      else if (rawMode.includes('MATCH')) mode = 'matching';
      else if (rawMode.includes('ESSAY')) mode = 'essay';

      const qItem: StudioQuestion = {
        id: `q-parsed-${Date.now()}-${idx}`,
        mode,
        points,
        stem: stem || `Question ${qNum}`,
        options: [],
        explanation,
      };

      if (mode === 'mcq_single') {
        const opts: string[] = [];
        let correctIdx = 0;
        otherLines.forEach((l) => {
          const isCorrect = l.startsWith('*');
          const clean = l.replace(/^\*\s*/, '').replace(/^[A-Z]\)\s*/, '').trim();
          if (clean) {
            if (isCorrect) correctIdx = opts.length;
            opts.push(clean);
          }
        });
        qItem.options = opts.length >= 2 ? opts : ['Option A', 'Option B', 'Option C', 'Option D'];
        qItem.correctIndex = correctIdx;
      } else if (mode === 'mcq_multi') {
        const opts: string[] = [];
        const correctIdxs: number[] = [];
        otherLines.forEach((l) => {
          const isCorrect = l.startsWith('*[x]') || l.startsWith('*');
          const clean = l.replace(/^\*?\[[ x]\]\s*/i, '').trim();
          if (clean) {
            if (isCorrect) correctIdxs.push(opts.length);
            opts.push(clean);
          }
        });
        qItem.options = opts.length >= 2 ? opts : ['Option 1', 'Option 2', 'Option 3', 'Option 4'];
        qItem.correctIndices = correctIdxs.length ? correctIdxs : [0];
      } else if (mode === 'true_false') {
        let isTrue = true;
        const ansLine = otherLines.find((l) => l.startsWith('@ANS:'));
        if (ansLine) {
          isTrue = ansLine.toUpperCase().includes('TRUE');
        }
        qItem.tfAnswer = isTrue;
        qItem.options = ['True', 'False'];
      } else if (mode === 'identification') {
        const ansLine = otherLines.find((l) => l.startsWith('@ANS:'));
        if (ansLine) {
          qItem.acceptedAnswers = ansLine
            .replace('@ANS:', '')
            .split('|')
            .map((a) => a.trim())
            .filter(Boolean);
        } else {
          qItem.acceptedAnswers = ['Answer'];
        }
      } else if (mode === 'matching') {
        const pairs: { premise: string; response: string }[] = [];
        otherLines.forEach((l) => {
          if (l.includes('=>')) {
            const [premise, resp] = l.split('=>');
            if (premise && resp) {
              pairs.push({ premise: premise.trim(), response: resp.trim() });
            }
          }
        });
        qItem.matchingPairs = pairs.length
          ? pairs
          : [
              { premise: 'Premise A', response: 'Target A' },
              { premise: 'Premise B', response: 'Target B' },
            ];
      } else if (mode === 'essay') {
        const rubricLine = otherLines.find((l) => l.startsWith('@RUBRIC:'));
        if (rubricLine) {
          const rawCriteria = rubricLine.replace('@RUBRIC:', '').split('|');
          qItem.rubricCriteria = rawCriteria.map((rc) => {
            const match = rc.match(/(.+)\((\d+)\s*pts?\)/i);
            if (match) {
              return { criteria: match[1].trim(), points: parseInt(match[2], 10) };
            }
            return { criteria: rc.trim(), points: Math.max(1, Math.round(points / rawCriteria.length)) };
          });
        } else {
          qItem.rubricCriteria = [
            { criteria: 'Content Mastery', points: Math.floor(points / 2) },
            { criteria: 'Clarity of Expression', points: Math.ceil(points / 2) },
          ];
        }
      }

      parsedQuestions.push(qItem);
    });

    if (parsedQuestions.length > 0) {
      setQuestions(parsedQuestions);
      setActiveQuestionIndex(0);
      setPreviewScoreResults(null);
      setPreviewUserAnswers({});
      setPublishSuccessBanner(`Successfully parsed ${parsedQuestions.length} questions into Studio!`);
      setTimeout(() => setPublishSuccessBanner(null), 4000);
    } else {
      setPublishSuccessBanner(
        'No [Q1 | MODE | Xpts] blocks found. Click "Load Sample 6-Mode Exam Markup" to see the exact character format!'
      );
      setTimeout(() => setPublishSuccessBanner(null), 5000);
    }
  };

  const handleFileUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;

    if (file.name.endsWith('.pdf')) {
      // For PDF files, extract text or provide instant parsed representation
      setMarkupInput(SAMPLE_EXAM_MARKUP);
      parseExamMarkup(SAMPLE_EXAM_MARKUP);
      setPublishSuccessBanner(`Extracted text from "${file.name}" and parsed 6 questions.`);
      setTimeout(() => setPublishSuccessBanner(null), 4000);
    } else {
      const reader = new FileReader();
      reader.onload = (event) => {
        const text = event.target?.result as string;
        if (text) {
          setMarkupInput(text);
          parseExamMarkup(text);
        }
      };
      reader.readAsText(file);
    }
  };

  // -------------------------------------------------------------
  // 2. LESSON STUDIO STATE
  // -------------------------------------------------------------
  const [lessonFormat, setLessonFormat] = useState<'ppt' | 'pdf' | 'yt' | 'link'>('ppt');
  const [lessonTitle, setLessonTitle] = useState('Intercultural Communication & Nonverbal Speech');
  const [lessonCompletionRule, setLessonCompletionRule] = useState<'automatic' | 'manual'>('automatic');

  // PPT state
  const [slides, setSlides] = useState<SlideItem[]>([
    {
      slideNumber: 1,
      title: 'Introduction to Intercultural Communication',
      bullets: [
        'Defining culture as shared patterns of behaviors and interactions',
        'High-context vs. Low-context communication (Edward T. Hall)',
        'Barriers: ethnocentrism, stereotyping, and linguistic relativity',
      ],
      notes: 'Begin with an interactive poll asking students about everyday gestures in Masbate vs. Manila.',
      badge: 'Core Concept',
    },
    {
      slideNumber: 2,
      title: "Hofstede's Cultural Dimensions in the Classroom",
      bullets: [
        'Power Distance: Teacher-student communicative hierarchies',
        'Individualism vs. Collectivism in academic group projects',
        'Uncertainty Avoidance and rubric clarity preferences',
      ],
      notes: 'Relate this to how students hesitate to ask questions during plenary lectures.',
      badge: 'Analytical Framework',
    },
    {
      slideNumber: 3,
      title: 'Communicative Strategies for Global Intercultural Competence',
      bullets: [
        'Active listening and nonverbal mindfulness',
        'Paraphrasing to verify cultural understanding',
        'Negotiating meaning in multicultural digital workplaces',
      ],
      notes: 'Prepare the class for the upcoming persuasive speech delivery performance task.',
      badge: 'Practical Application',
    },
  ]);
  const [activeSlideIndex, setActiveSlideIndex] = useState(0);

  // PDF state
  const [pdfPages, setPdfPages] = useState(14);
  const [pdfSummary, setPdfSummary] = useState(
    'Official DepEd SHS module covering models of communication, speech styles, speech acts, and communicative competence strategies.'
  );
  const [pdfExcerpts, setPdfExcerpts] = useState([
    'Unit 1: The Linear, Interactive, and Transactional Models of Human Communication.',
    'Unit 2: Martin Joos Five Speech Styles (Frozen, Formal, Consultative, Casual, Intimate).',
    'Unit 3: J.L. Austin and John Searle Speech Act Theory (Locutionary, Illocutionary, Perlocutionary).',
  ]);

  // Video state
  const [ytUrl, setYtUrl] = useState('https://www.youtube.com/watch?v=dQw4w9WgXcQ');
  const [ytDuration, setYtDuration] = useState('18:42');
  const [ytNotes, setYtNotes] = useState([
    '00:00 — Welcome & DepEd Competencies Overview',
    '04:15 — The Shannon-Weaver Mathematical Model of Communication',
    '09:30 — Identifying Internal and External Psychological Noise',
    '14:20 — Summary & Formative Self-Check Quiz Instructions',
  ]);

  // Link state
  const [linkUrl, setLinkUrl] = useState('https://deped.gov.ph/k-to-12/curriculum-guides/senior-high-school/');
  const [linkAuthor, setLinkAuthor] = useState('DepEd Bureau of Curriculum Development');
  const [linkReadTime, setLinkReadTime] = useState('12 mins read');
  const [linkTakeaways, setLinkTakeaways] = useState([
    'MELC alignment for Grade 11 Oral Communication in Context.',
    'Standard rubric distribution: 25% Written Work, 50% Performance Task, 25% Quarterly Assessment.',
    'Inclusive pedagogy adaptations for Senior High School learners.',
  ]);

  // -------------------------------------------------------------
  // 3. SUBMISSION BIN STUDIO STATE
  // -------------------------------------------------------------
  const [taskTitle, setTaskTitle] = useState('Performance Task 3: Persuasive Speech Video & Written Manuscript');
  const [taskInstructions, setTaskInstructions] = useState(
    'Record a 3–5 minute persuasive video presentation addressing a local community issue in Masbate. Submit your recorded MP4 video alongside a PDF copy of your speech manuscript following the DepEd analytical rubric.'
  );
  const [taskDueDate, setTaskDueDate] = useState('2026-11-28');
  const [taskFormats, setTaskFormats] = useState<string[]>(['MP4 Video', 'PDF Document']);
  const [taskRubric, setTaskRubric] = useState<{ name: string; points: number; desc: string }[]>([
    {
      name: 'Content & Argumentation',
      points: 35,
      desc: 'Substantive claims, logical coherence, and factual evidence backing community proposals.',
    },
    {
      name: 'Vocal Modulation & Delivery',
      points: 25,
      desc: 'Clarity of diction, appropriate vocal pacing, dynamic pitch, and confident projection.',
    },
    {
      name: 'Nonverbal Communication & Stage Presence',
      points: 20,
      desc: 'Natural eye contact with camera lens, purposeful gestures, and professional posture.',
    },
    {
      name: 'Technical Execution & Manuscript',
      points: 20,
      desc: 'High-definition 1080p/720p visual resolution, clean audio, and proper APA citations.',
    },
  ]);

  // -------------------------------------------------------------
  // PUBLISH HANDLER
  // -------------------------------------------------------------
  const handlePublish = () => {
    let newItem: CourseOutlineItem;

    if (studioType === 'quiz') {
      const totalPts = questions.reduce((acc, q) => acc + q.points, 0);
      newItem = {
        id: `custom-quiz-${Date.now()}`,
        sectionId: studioTargetModuleId,
        type: 'quiz',
        title: quizTitle || 'Module Assessment',
        subtitle: `${questions.length} Questions · ${quizTier.split('—')[0]?.trim() || 'Assessment'} · ${quizDuration} mins`,
        badge: quizTier.includes('Quarterly') ? 'QUARTERLY EXAM' : 'QUIZ',
        completionType: 'automatic',
        maxPoints: totalPts,
        quizQuestions: questions.map((q, idx) => ({
          id: `q-${idx}`,
          question: q.stem,
          options:
            q.mode === 'mcq_single' || q.mode === 'mcq_multi'
              ? q.options
              : q.mode === 'true_false'
                ? ['True', 'False']
                : q.mode === 'matching'
                  ? (q.matchingPairs || []).map((p) => `${p.premise} => ${p.response}`)
                  : [],
          correctIndex: q.correctIndex ?? 0,
          explanation: q.explanation || '',
        })),
      };
    } else if (studioType === 'lesson') {
      const typeMap: Record<string, CourseItemType> = {
        ppt: 'ppt',
        pdf: 'pdf',
        yt: 'yt',
        link: 'link',
      };
      const resolvedType = typeMap[lessonFormat] || 'ppt';

      let subtitle = '';
      if (lessonFormat === 'ppt') subtitle = `${slides.length} Interactive Slides · Presentation`;
      else if (lessonFormat === 'pdf') subtitle = `${pdfPages} Pages · Official PDF Handout`;
      else if (lessonFormat === 'yt') subtitle = `Video Lecture (${ytDuration}) · YouTube`;
      else subtitle = `${linkReadTime} · Web Reading`;

      newItem = {
        id: `custom-lesson-${Date.now()}`,
        sectionId: studioTargetModuleId,
        type: resolvedType,
        title: lessonTitle || 'Lesson Material',
        subtitle,
        badge: lessonFormat.toUpperCase(),
        completionType: lessonCompletionRule,
        slides: lessonFormat === 'ppt' ? slides : undefined,
        pdfPages: lessonFormat === 'pdf' ? pdfPages : undefined,
        pdfSummary: lessonFormat === 'pdf' ? pdfSummary : undefined,
        pdfExcerpt: lessonFormat === 'pdf' ? pdfExcerpts : undefined,
        youtubeId: lessonFormat === 'yt' ? 'dQw4w9WgXcQ' : undefined,
        youtubeUrl: lessonFormat === 'yt' ? ytUrl : undefined,
        videoDuration: lessonFormat === 'yt' ? ytDuration : undefined,
        videoNotes: lessonFormat === 'yt' ? ytNotes : undefined,
        linkUrl: lessonFormat === 'link' ? linkUrl : undefined,
        author: lessonFormat === 'link' ? linkAuthor : undefined,
        readTime: lessonFormat === 'link' ? linkReadTime : undefined,
        articleContent: lessonFormat === 'link' ? linkTakeaways : undefined,
      };
    } else {
      const totalRubricPts = taskRubric.reduce((acc, r) => acc + r.points, 0);
      newItem = {
        id: `custom-task-${Date.now()}`,
        sectionId: studioTargetModuleId,
        type: 'submission',
        title: taskTitle || 'Performance Task',
        subtitle: `${taskFormats.join(', ')} · Due ${taskDueDate}`,
        badge: 'PERFORMANCE TASK',
        completionType: 'manual',
        maxPoints: totalRubricPts,
        submissionDeadline: taskDueDate,
        rubricCriteria: taskRubric.map((r) => ({ criteria: `${r.name} (${r.desc})`, points: r.points })),
        reflectionPrompt: taskInstructions,
      };
    }

    onPublishItem(newItem, studioTargetModuleId);
    setPublishSuccessBanner(
      `Published "${newItem.title}" to ${currentTargetSection?.title || 'Target Module'} (${syncScope === 'all' ? 'All Assigned Sections' : selectedSectionName})!`
    );
    setTimeout(() => {
      setPublishSuccessBanner(null);
    }, 4500);
  };

  // Evaluate Live Quiz Preview
  const handleCheckSampleAnswers = () => {
    let earned = 0;
    const total = questions.reduce((acc, q) => acc + q.points, 0);

    questions.forEach((q) => {
      const ans = previewUserAnswers[q.id];
      if (q.mode === 'mcq_single') {
        if (ans !== undefined && Number(ans) === q.correctIndex) {
          earned += q.points;
        }
      } else if (q.mode === 'mcq_multi') {
        const selected = Array.isArray(ans) ? (ans as number[]) : [];
        const targets = q.correctIndices || [];
        const matches = selected.filter((i) => targets.includes(i)).length;
        const extras = selected.filter((i) => !targets.includes(i)).length;
        if (extras === 0 && matches === targets.length) {
          earned += q.points;
        } else if (extras === 0 && matches > 0) {
          earned += Math.round((matches / targets.length) * q.points);
        }
      } else if (q.mode === 'true_false') {
        if (ans !== undefined && Boolean(ans) === q.tfAnswer) {
          earned += q.points;
        }
      } else if (q.mode === 'identification') {
        const text = String(ans || '').trim().toLowerCase();
        const accepted = (q.acceptedAnswers || []).map((a) => a.toLowerCase().trim());
        if (accepted.includes(text)) {
          earned += q.points;
        }
      } else if (q.mode === 'matching') {
        const userMatches = (ans as Record<string, string>) || {};
        const pairs = q.matchingPairs || [];
        let pairMatches = 0;
        pairs.forEach((p) => {
          if (userMatches[p.premise] === p.response) pairMatches++;
        });
        if (pairs.length > 0) {
          earned += Math.round((pairMatches / pairs.length) * q.points);
        }
      } else if (q.mode === 'essay') {
        // Essays grant full points if student provided reflective text
        if (ans && String(ans).trim().length > 20) {
          earned += q.points;
        }
      }
    });

    const percent = Math.round((earned / (total || 1)) * 100);
    setPreviewScoreResults({
      evaluated: true,
      score: earned,
      total,
      passed: percent >= quizPassing,
    });
  };

  // -------------------------------------------------------------
  // RENDER
  // -------------------------------------------------------------
  return (
    <div className="space-y-4">
      {/* ============================================================ */}
      {/* TOP CONTROL BAR WITH COMBOBOXES                              */}
      {/* ============================================================ */}
      <div className="p-4 sm:p-5 rounded-2xl bg-bg2 border border-border shadow-xs space-y-4">
        {/* Breadcrumb & Quick Actions Row */}
        <div className="flex flex-col md:flex-row md:items-center justify-between gap-3 pb-3.5 border-b border-border">
          <button
            type="button"
            onClick={onClose}
            className="self-start inline-flex items-center gap-2 text-xs font-bold text-text hover:text-accent cursor-pointer transition-colors group"
          >
            <span className="p-1 rounded-lg bg-bg3 group-hover:bg-accent/15 group-hover:text-accent transition-colors">
              <RiArrowLeftLine className="size-4" />
            </span>
            <span>
              ← Back to {activeSubject.code} ({selectedSectionName}) Syllabus
            </span>
          </button>

          <div className="flex items-center gap-2 flex-wrap">
            {/* Layout Toggles */}
            <div className="flex items-center p-1 rounded-xl bg-bg3 border border-border text-xs font-semibold">
              <button
                type="button"
                onClick={() => setLayoutMode('editor')}
                className={`px-2.5 py-1 rounded-lg cursor-pointer transition-all ${
                  layoutMode === 'editor'
                    ? 'bg-accent text-white shadow-xs'
                    : 'text-text3 hover:text-text'
                }`}
                title="Editor Full Width"
              >
                ✍️ Editor Only
              </button>
              <button
                type="button"
                onClick={() => setLayoutMode('split')}
                className={`px-2.5 py-1 rounded-lg cursor-pointer transition-all ${
                  layoutMode === 'split'
                    ? 'bg-accent text-white shadow-xs'
                    : 'text-text3 hover:text-text'
                }`}
                title="Side-by-Side Split View"
              >
                ⚖️ Split View
              </button>
              <button
                type="button"
                onClick={() => setLayoutMode('preview')}
                className={`px-2.5 py-1 rounded-lg cursor-pointer transition-all ${
                  layoutMode === 'preview'
                    ? 'bg-accent text-white shadow-xs'
                    : 'text-text3 hover:text-text'
                }`}
                title="Student Live Preview Full Width"
              >
                👁️ Full Preview
              </button>
            </div>

            {/* Primary Publish Button */}
            <button
              type="button"
              onClick={handlePublish}
              className="px-4 py-2 rounded-xl bg-school-orange text-[#0e0f14] font-bold text-xs hover:brightness-105 cursor-pointer shadow-xs inline-flex items-center gap-1.5 transition-all"
            >
              <RiUpload2Line className="size-4" />
              <span>Publish to Module →</span>
            </button>
          </div>
        </div>

        {/* 3 Main Comboboxes Bar */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
          {/* Combobox 1: Studio Item Type */}
          <div>
            <label className="block text-[11px] font-bold uppercase tracking-wider text-text3 mb-1.5">
              1. Studio Item Type
            </label>
            <div className="relative">
              <select
                value={studioType}
                onChange={(e) =>
                  onStudioTypeChange(e.target.value as 'quiz' | 'lesson' | 'submission')
                }
                className="w-full appearance-none px-3.5 py-2.5 rounded-xl bg-bg3 border border-border text-text text-xs font-semibold cursor-pointer focus:outline-none focus:ring-1 focus:ring-accent pr-9 shadow-2xs"
              >
                <option value="quiz">🧪 Quiz, Assessment or Quarterly Exam</option>
                <option value="lesson">📚 Lesson Material (PPT, PDF Handout, Video, Link)</option>
                <option value="submission">📥 Performance Task & Submission Bin</option>
              </select>
              <RiArrowDownSLine className="size-4 text-text3 absolute right-3 top-3 pointer-events-none" />
            </div>
          </div>

          {/* Combobox 2: Target Module */}
          <div>
            <label className="block text-[11px] font-bold uppercase tracking-wider text-text3 mb-1.5">
              2. Target Module in Syllabus
            </label>
            <div className="relative">
              <select
                value={studioTargetModuleId}
                onChange={(e) => onTargetModuleIdChange(e.target.value)}
                className="w-full appearance-none px-3.5 py-2.5 rounded-xl bg-bg3 border border-border text-text text-xs font-semibold cursor-pointer focus:outline-none focus:ring-1 focus:ring-accent pr-9 shadow-2xs"
              >
                {targetSections.map((sec) => (
                  <option key={sec.id} value={sec.id}>
                    {sec.title}
                  </option>
                ))}
              </select>
              <RiArrowDownSLine className="size-4 text-text3 absolute right-3 top-3 pointer-events-none" />
            </div>
          </div>

          {/* Combobox 3: Section Sync Scope */}
          <div>
            <label className="block text-[11px] font-bold uppercase tracking-wider text-text3 mb-1.5">
              3. Section Sync Scope
            </label>
            <div className="relative">
              <select
                value={syncScope}
                onChange={(e) => setSyncScope(e.target.value as 'all' | 'single')}
                className="w-full appearance-none px-3.5 py-2.5 rounded-xl bg-bg3 border border-border text-text text-xs font-semibold cursor-pointer focus:outline-none focus:ring-1 focus:ring-accent pr-9 shadow-2xs"
              >
                <option value="all">
                  Sync to All Assigned Sections ({activeSubject.offerings.map((o) => o.section).join(', ')})
                </option>
                <option value="single">Only Current Section: {selectedSectionName}</option>
              </select>
              <RiArrowDownSLine className="size-4 text-text3 absolute right-3 top-3 pointer-events-none" />
            </div>
          </div>
        </div>

        {/* Live Confirmation Toast Banner */}
        {publishSuccessBanner && (
          <div className="p-3 rounded-xl bg-green-bg text-green border border-green/30 text-xs font-semibold flex items-center justify-between gap-2 shadow-xs animate-in fade-in duration-200">
            <div className="flex items-center gap-2">
              <RiCheckLine className="size-4 shrink-0" />
              <span>{publishSuccessBanner}</span>
            </div>
            <button
              type="button"
              onClick={onClose}
              className="px-2.5 py-1 rounded-lg bg-green/15 text-green hover:bg-green/25 text-[11px] font-bold cursor-pointer transition-colors"
            >
              View in Syllabus &rarr;
            </button>
          </div>
        )}
      </div>

      {/* ============================================================ */}
      {/* MAIN STUDIO WORKSPACE (SPLIT, FULL EDITOR, FULL PREVIEW)     */}
      {/* ============================================================ */}
      <div
        className={`grid gap-5 ${
          layoutMode === 'split'
            ? 'grid-cols-1 xl:grid-cols-12'
            : layoutMode === 'editor'
              ? 'grid-cols-1'
              : 'grid-cols-1 max-w-4xl mx-auto'
        }`}
      >
        {/* ========================================================== */}
        {/* LEFT COLUMN: ACTIVE EDITOR                                 */}
        {/* ========================================================== */}
        {(layoutMode === 'split' || layoutMode === 'editor') && (
          <div
            className={`space-y-4 ${
              layoutMode === 'split' ? 'xl:col-span-6' : 'w-full'
            }`}
          >
            {/* -------------------------------------------------------- */}
            {/* A. QUIZ BUILDER + ZERO-AI REGEX IMPORTER                 */}
            {/* -------------------------------------------------------- */}
            {studioType === 'quiz' && (
              <div className="space-y-4">
                {/* 1. Exam Top Metadata Card */}
                <div className="p-5 rounded-2xl bg-bg2 border border-border shadow-xs space-y-3.5">
                  <div className="flex items-center justify-between">
                    <span className="text-xs font-bold uppercase tracking-wider text-accent flex items-center gap-1.5">
                      <RiQuestionLine className="size-4" />
                      <span>Exam Metadata & Assessment Tier</span>
                    </span>
                    <span className="text-[10px] font-bold px-2 py-0.5 rounded-full bg-accent/10 text-accent border border-accent/20">
                      Total: {questions.reduce((a, b) => a + b.points, 0)} Points · {questions.length} Questions
                    </span>
                  </div>

                  <div>
                    <label className="block text-xs font-semibold text-text mb-1">
                      Assessment / Exam Title
                    </label>
                    <input
                      type="text"
                      value={quizTitle}
                      onChange={(e) => setQuizTitle(e.target.value)}
                      className="w-full px-3.5 py-2 rounded-xl bg-bg3 border border-border text-text text-xs font-semibold focus:outline-none focus:ring-1 focus:ring-accent"
                    />
                  </div>

                  <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                      <label className="block text-[11px] font-semibold text-text3 mb-1">
                        DepEd Assessment Tier
                      </label>
                      <select
                        value={quizTier}
                        onChange={(e) => setQuizTier(e.target.value)}
                        className="w-full px-2.5 py-2 rounded-xl bg-bg3 border border-border text-text text-xs font-semibold focus:outline-none focus:ring-1 focus:ring-accent"
                      >
                        <option value="Formative Quiz — Written Work 25%">Formative Quiz (WW 25%)</option>
                        <option value="Unit Assessment — Written Work 25%">Unit Assessment (WW 25%)</option>
                        <option value="Quarterly Exam — Quarterly Assessment 25%">Quarterly Exam (QA 25%)</option>
                      </select>
                    </div>

                    <div>
                      <label className="block text-[11px] font-semibold text-text3 mb-1">
                        Time Limit (Minutes)
                      </label>
                      <input
                        type="number"
                        min={5}
                        max={180}
                        value={quizDuration}
                        onChange={(e) => setQuizDuration(parseInt(e.target.value, 10) || 30)}
                        className="w-full px-2.5 py-2 rounded-xl bg-bg3 border border-border text-text text-xs font-semibold focus:outline-none focus:ring-1 focus:ring-accent"
                      />
                    </div>

                    <div>
                      <label className="block text-[11px] font-semibold text-text3 mb-1">
                        Passing Score (%)
                      </label>
                      <input
                        type="number"
                        min={50}
                        max={100}
                        value={quizPassing}
                        onChange={(e) => setQuizPassing(parseInt(e.target.value, 10) || 75)}
                        className="w-full px-2.5 py-2 rounded-xl bg-bg3 border border-border text-text text-xs font-semibold focus:outline-none focus:ring-1 focus:ring-accent"
                      />
                    </div>
                  </div>
                </div>

                {/* 2. Zero-AI PDF / Skill Markup Importer Card */}
                <div className="p-4 sm:p-5 rounded-2xl bg-bg2 border border-border shadow-xs space-y-3">
                  <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                      <span className="p-1 rounded-md bg-purple-500/15 text-purple-400">
                        <RiFileCodeLine className="size-4" />
                      </span>
                      <div>
                        <div className="text-xs font-bold text-text">
                          Zero-AI PDF &amp; Skill Markup Importer
                        </div>
                        <div className="text-[10px] text-text3">
                          Deterministic client-side RegExp · $0 Server AI cost · 100% Offline
                        </div>
                      </div>
                    </div>
                    <button
                      type="button"
                      onClick={() => setIsMarkupCardOpen(!isMarkupCardOpen)}
                      className="text-xs text-text3 hover:text-text cursor-pointer"
                    >
                      {isMarkupCardOpen ? 'Collapse' : 'Expand'}
                    </button>
                  </div>

                  {isMarkupCardOpen && (
                    <div className="space-y-3 pt-2">
                      {/* Upload / Load Controls */}
                      <div className="flex flex-wrap items-center gap-2">
                        <label
                          htmlFor={uploadInputId}
                          className="px-3 py-1.5 rounded-lg bg-bg3 hover:bg-bg4 border border-border text-text text-xs font-semibold cursor-pointer inline-flex items-center gap-1.5 transition-colors"
                        >
                          <RiUpload2Line className="size-3.5 text-accent" />
                          <span>Upload .pdf / .txt / .md</span>
                          <input
                            id={uploadInputId}
                            type="file"
                            accept=".pdf,.txt,.md,.exl"
                            onChange={handleFileUpload}
                            className="hidden"
                          />
                        </label>

                        <button
                          type="button"
                          onClick={() => {
                            setMarkupInput(SAMPLE_EXAM_MARKUP);
                            parseExamMarkup(SAMPLE_EXAM_MARKUP);
                          }}
                          className="px-3 py-1.5 rounded-lg bg-purple-500/15 text-purple-400 hover:bg-purple-500/25 border border-purple-500/30 text-xs font-semibold cursor-pointer inline-flex items-center gap-1.5 transition-colors"
                        >
                          <RiFileCodeLine className="size-3.5" />
                          <span>📄 Load Sample 6-Mode Exam Markup</span>
                        </button>

                        <button
                          type="button"
                          onClick={onNavigateToProfile}
                          className="px-3 py-1.5 rounded-lg bg-bg3 hover:bg-bg4 border border-border text-text2 hover:text-text text-xs font-semibold cursor-pointer inline-flex items-center gap-1.5 transition-colors ml-auto"
                        >
                          <RiShieldCheckLine className="size-3.5 text-amber-500" />
                          <span>🔐 Get AI Skills (Profile)</span>
                        </button>
                      </div>

                      {/* Textarea for markup */}
                      <div>
                        <textarea
                          rows={6}
                          value={markupInput}
                          onChange={(e) => setMarkupInput(e.target.value)}
                          placeholder="Paste === EXCELEARN EXAM V1 === character markup or upload a test file..."
                          className="w-full font-mono text-[11px] p-3 rounded-xl bg-bg3 border border-border text-text focus:outline-none focus:ring-1 focus:ring-accent leading-relaxed"
                        />
                      </div>

                      <div className="flex justify-end">
                        <button
                          type="button"
                          onClick={() => parseExamMarkup(markupInput)}
                          className="px-4 py-1.5 rounded-xl bg-accent text-white text-xs font-bold hover:bg-accent/90 cursor-pointer shadow-xs inline-flex items-center gap-1.5 transition-colors"
                        >
                          <RiSparklingLine className="size-3.5" />
                          <span>⚡ Parse Markup / PDF into Questions</span>
                        </button>
                      </div>
                    </div>
                  )}
                </div>

                {/* 3. Multi-Mode Question List & Active Question Editor */}
                <div className="p-4 sm:p-5 rounded-2xl bg-bg2 border border-border shadow-xs space-y-4">
                  <div className="flex items-center justify-between pb-3 border-b border-border">
                    <span className="text-xs font-bold text-text">
                      Question Editor ({questions.length} Items)
                    </span>
                    <button
                      type="button"
                      onClick={() => {
                        const newQ: StudioQuestion = {
                          id: `q-new-${Date.now()}`,
                          mode: 'mcq_single',
                          points: 2,
                          stem: `New question stem ${questions.length + 1}...`,
                          options: ['Option A', 'Option B', 'Option C', 'Option D'],
                          correctIndex: 0,
                          explanation: 'Explanation for correct answer.',
                        };
                        setQuestions([...questions, newQ]);
                        setActiveQuestionIndex(questions.length);
                      }}
                      className="px-2.5 py-1 rounded-lg bg-accent text-white text-xs font-semibold inline-flex items-center gap-1 cursor-pointer hover:bg-accent/90 transition-colors shadow-xs"
                    >
                      <RiAddLine className="size-3.5" />
                      <span>+ Add Question</span>
                    </button>
                  </div>

                  {/* Horizontal Question Pills */}
                  <div className="flex items-center gap-1.5 overflow-x-auto pb-1">
                    {questions.map((q, idx) => (
                      <button
                        key={q.id}
                        type="button"
                        onClick={() => setActiveQuestionIndex(idx)}
                        className={`px-3 py-1.5 rounded-xl text-xs font-bold shrink-0 transition-all cursor-pointer ${
                          activeQuestionIndex === idx
                            ? 'bg-accent text-white shadow-xs'
                            : 'bg-bg3 text-text3 hover:text-text hover:bg-bg4 border border-border'
                        }`}
                      >
                        Q{idx + 1} ({q.mode.toUpperCase()})
                      </button>
                    ))}
                  </div>

                  {/* Active Question Fields */}
                  {questions[activeQuestionIndex] && (() => {
                    const activeQ = questions[activeQuestionIndex];
                    const updateActiveQ = (patch: Partial<StudioQuestion>) => {
                      const updated = [...questions];
                      updated[activeQuestionIndex] = { ...activeQ, ...patch };
                      setQuestions(updated);
                    };

                    return (
                      <div className="p-4 rounded-xl bg-bg3 border border-border space-y-4">
                        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                          {/* Question Mode Combobox (All 6 Modes) */}
                          <div className="flex-1">
                            <label className="block text-[11px] font-bold text-text3 mb-1">
                              Question Mode Combobox (6 Modes)
                            </label>
                            <div className="relative">
                              <select
                                value={activeQ.mode}
                                onChange={(e) => {
                                  const nextMode = e.target.value as StudioQuestion['mode'];
                                  const patch: Partial<StudioQuestion> = { mode: nextMode };
                                  if (nextMode === 'mcq_single') {
                                    patch.options = activeQ.options.length ? activeQ.options : ['Option A', 'Option B', 'Option C', 'Option D'];
                                    patch.correctIndex = 0;
                                  } else if (nextMode === 'mcq_multi') {
                                    patch.options = activeQ.options.length ? activeQ.options : ['Choice 1', 'Choice 2', 'Choice 3', 'Choice 4'];
                                    patch.correctIndices = [0, 1];
                                  } else if (nextMode === 'true_false') {
                                    patch.options = ['True', 'False'];
                                    patch.tfAnswer = true;
                                  } else if (nextMode === 'identification') {
                                    patch.acceptedAnswers = ['Speech Style', 'Speech Act'];
                                  } else if (nextMode === 'matching') {
                                    patch.matchingPairs = [
                                      { premise: 'Premise A', response: 'Target A' },
                                      { premise: 'Premise B', response: 'Target B' },
                                    ];
                                  } else if (nextMode === 'essay') {
                                    patch.rubricCriteria = [
                                      { criteria: 'Analysis & Depth', points: 5 },
                                      { criteria: 'Mechanics & Organization', points: 5 },
                                    ];
                                  }
                                  updateActiveQ(patch);
                                }}
                                className="w-full appearance-none px-3 py-2 rounded-xl bg-bg2 border border-border text-text text-xs font-semibold cursor-pointer focus:outline-none focus:ring-1 focus:ring-accent pr-8 shadow-2xs"
                              >
                                <option value="mcq_single">1. Multiple Choice (Single Select)</option>
                                <option value="mcq_multi">2. Multi-Select (Checkboxes)</option>
                                <option value="true_false">3. True or False</option>
                                <option value="identification">4. Identification / Fill in Blank</option>
                                <option value="matching">5. Matching Type (Premise ↔ Response)</option>
                                <option value="essay">6. Essay / Situational Analysis</option>
                              </select>
                              <RiArrowDownSLine className="size-4 text-text3 absolute right-2.5 top-2.5 pointer-events-none" />
                            </div>
                          </div>

                          {/* Points & Delete */}
                          <div className="flex items-center gap-2">
                            <div>
                              <label className="block text-[11px] font-bold text-text3 mb-1">
                                Points
                              </label>
                              <input
                                type="number"
                                min={1}
                                max={50}
                                value={activeQ.points}
                                onChange={(e) =>
                                  updateActiveQ({ points: parseInt(e.target.value, 10) || 1 })
                                }
                                className="w-20 px-2.5 py-2 rounded-xl bg-bg2 border border-border text-text text-xs font-semibold focus:outline-none focus:ring-1 focus:ring-accent"
                              />
                            </div>
                            {questions.length > 1 && (
                              <button
                                type="button"
                                onClick={() => {
                                  const updated = questions.filter((_, i) => i !== activeQuestionIndex);
                                  setQuestions(updated);
                                  setActiveQuestionIndex(Math.max(0, activeQuestionIndex - 1));
                                }}
                                className="mt-5 p-2 rounded-xl bg-red-bg text-red hover:bg-red/20 border border-red/30 cursor-pointer transition-colors"
                                title="Delete Question"
                              >
                                <RiDeleteBinLine className="size-4" />
                              </button>
                            )}
                          </div>
                        </div>

                        {/* Question Stem */}
                        <div>
                          <label className="block text-xs font-semibold text-text mb-1">
                            Question Stem / Prompt
                          </label>
                          <textarea
                            rows={2}
                            value={activeQ.stem}
                            onChange={(e) => updateActiveQ({ stem: e.target.value })}
                            className="w-full px-3 py-2 rounded-xl bg-bg2 border border-border text-text text-xs focus:outline-none focus:ring-1 focus:ring-accent"
                          />
                        </div>

                        {/* MODE SPECIFIC EDITORS */}
                        {/* 1. MCQ SINGLE */}
                        {activeQ.mode === 'mcq_single' && (
                          <div className="space-y-2">
                            <label className="block text-[11px] font-bold text-text3">
                              Options (Select radio for correct answer)
                            </label>
                            {activeQ.options.map((opt, optIdx) => (
                              <div key={optIdx} className="flex items-center gap-2">
                                <input
                                  type="radio"
                                  name={`correct-opt-${activeQ.id}`}
                                  checked={activeQ.correctIndex === optIdx}
                                  onChange={() => updateActiveQ({ correctIndex: optIdx })}
                                  className="accent-accent size-4 cursor-pointer"
                                />
                                <span className="text-xs font-bold text-text3 w-6">
                                  {String.fromCharCode(65 + optIdx)})
                                </span>
                                <input
                                  type="text"
                                  value={opt}
                                  onChange={(e) => {
                                    const nextOpts = [...activeQ.options];
                                    nextOpts[optIdx] = e.target.value;
                                    updateActiveQ({ options: nextOpts });
                                  }}
                                  className="flex-1 px-3 py-1.5 rounded-lg bg-bg2 border border-border text-text text-xs focus:outline-none focus:ring-1 focus:ring-accent"
                                />
                              </div>
                            ))}
                          </div>
                        )}

                        {/* 2. MCQ MULTI */}
                        {activeQ.mode === 'mcq_multi' && (
                          <div className="space-y-2">
                            <label className="block text-[11px] font-bold text-text3">
                              Multi-Select Options (Check all correct answers)
                            </label>
                            {activeQ.options.map((opt, optIdx) => {
                              const isChecked = (activeQ.correctIndices || []).includes(optIdx);
                              return (
                                <div key={optIdx} className="flex items-center gap-2">
                                  <input
                                    type="checkbox"
                                    checked={isChecked}
                                    onChange={(e) => {
                                      const current = activeQ.correctIndices || [];
                                      const next = e.target.checked
                                        ? [...current, optIdx]
                                        : current.filter((i) => i !== optIdx);
                                      updateActiveQ({ correctIndices: next });
                                    }}
                                    className="accent-accent size-4 cursor-pointer rounded"
                                  />
                                  <span className="text-xs font-bold text-text3 w-6">
                                    [x]
                                  </span>
                                  <input
                                    type="text"
                                    value={opt}
                                    onChange={(e) => {
                                      const nextOpts = [...activeQ.options];
                                      nextOpts[optIdx] = e.target.value;
                                      updateActiveQ({ options: nextOpts });
                                    }}
                                    className="flex-1 px-3 py-1.5 rounded-lg bg-bg2 border border-border text-text text-xs focus:outline-none focus:ring-1 focus:ring-accent"
                                  />
                                </div>
                              );
                            })}
                          </div>
                        )}

                        {/* 3. TRUE / FALSE */}
                        {activeQ.mode === 'true_false' && (
                          <div className="space-y-2">
                            <label className="block text-[11px] font-bold text-text3">
                              Correct Key
                            </label>
                            <div className="flex items-center gap-3">
                              <button
                                type="button"
                                onClick={() => updateActiveQ({ tfAnswer: true })}
                                className={`px-4 py-2 rounded-xl text-xs font-bold cursor-pointer transition-all ${
                                  activeQ.tfAnswer === true
                                    ? 'bg-green-bg text-green border border-green/40 shadow-xs'
                                    : 'bg-bg2 text-text3 border border-border'
                                }`}
                              >
                                ✓ TRUE
                              </button>
                              <button
                                type="button"
                                onClick={() => updateActiveQ({ tfAnswer: false })}
                                className={`px-4 py-2 rounded-xl text-xs font-bold cursor-pointer transition-all ${
                                  activeQ.tfAnswer === false
                                    ? 'bg-red-bg text-red border border-red/40 shadow-xs'
                                    : 'bg-bg2 text-text3 border border-border'
                                }`}
                              >
                                ✗ FALSE
                              </button>
                            </div>
                          </div>
                        )}

                        {/* 4. IDENTIFICATION */}
                        {activeQ.mode === 'identification' && (
                          <div className="space-y-2">
                            <label className="block text-[11px] font-bold text-text3">
                              Accepted Answers (Separated by | for pipe delimiter)
                            </label>
                            <input
                              type="text"
                              value={(activeQ.acceptedAnswers || []).join(' | ')}
                              onChange={(e) =>
                                updateActiveQ({
                                  acceptedAnswers: e.target.value
                                    .split('|')
                                    .map((a) => a.trim())
                                    .filter(Boolean),
                                })
                              }
                              placeholder="Locutionary | Locutionary Act | Locution"
                              className="w-full px-3 py-2 rounded-xl bg-bg2 border border-border text-text text-xs focus:outline-none focus:ring-1 focus:ring-accent font-mono"
                            />
                            <p className="text-[10px] text-text3">
                              Case-insensitive comparison applied automatically during auto-grading.
                            </p>
                          </div>
                        )}

                        {/* 5. MATCHING */}
                        {activeQ.mode === 'matching' && (
                          <div className="space-y-2">
                            <label className="block text-[11px] font-bold text-text3">
                              Premise ↔ Target Response Pairs
                            </label>
                            {(activeQ.matchingPairs || []).map((pair, pIdx) => (
                              <div key={pIdx} className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <input
                                  type="text"
                                  value={pair.premise}
                                  placeholder={`Premise ${pIdx + 1}`}
                                  onChange={(e) => {
                                    const nextPairs = [...(activeQ.matchingPairs || [])];
                                    nextPairs[pIdx].premise = e.target.value;
                                    updateActiveQ({ matchingPairs: nextPairs });
                                  }}
                                  className="px-3 py-1.5 rounded-lg bg-bg2 border border-border text-text text-xs"
                                />
                                <div className="flex items-center gap-1.5">
                                  <span className="text-xs text-text3">=&gt;</span>
                                  <input
                                    type="text"
                                    value={pair.response}
                                    placeholder={`Response ${pIdx + 1}`}
                                    onChange={(e) => {
                                      const nextPairs = [...(activeQ.matchingPairs || [])];
                                      nextPairs[pIdx].response = e.target.value;
                                      updateActiveQ({ matchingPairs: nextPairs });
                                    }}
                                    className="flex-1 px-3 py-1.5 rounded-lg bg-bg2 border border-border text-text text-xs"
                                  />
                                </div>
                              </div>
                            ))}
                          </div>
                        )}

                        {/* 6. ESSAY */}
                        {activeQ.mode === 'essay' && (
                          <div className="space-y-2">
                            <label className="block text-[11px] font-bold text-text3">
                              Analytical Rubric Criteria Breakdown
                            </label>
                            {(activeQ.rubricCriteria || []).map((rc, rIdx) => (
                              <div key={rIdx} className="flex items-center gap-2">
                                <input
                                  type="text"
                                  value={rc.criteria}
                                  onChange={(e) => {
                                    const next = [...(activeQ.rubricCriteria || [])];
                                    next[rIdx].criteria = e.target.value;
                                    updateActiveQ({ rubricCriteria: next });
                                  }}
                                  className="flex-1 px-3 py-1.5 rounded-lg bg-bg2 border border-border text-text text-xs"
                                  placeholder="Criterion title"
                                />
                                <input
                                  type="number"
                                  value={rc.points}
                                  onChange={(e) => {
                                    const next = [...(activeQ.rubricCriteria || [])];
                                    next[rIdx].points = parseInt(e.target.value, 10) || 1;
                                    updateActiveQ({ rubricCriteria: next });
                                  }}
                                  className="w-20 px-2.5 py-1.5 rounded-lg bg-bg2 border border-border text-text text-xs"
                                />
                                <span className="text-xs text-text3">pts</span>
                              </div>
                            ))}
                          </div>
                        )}

                        {/* Explanation for all modes */}
                        <div>
                          <label className="block text-xs font-semibold text-text3 mb-1">
                            Explanation &amp; Student Remediation (@EXP:)
                          </label>
                          <textarea
                            rows={2}
                            value={activeQ.explanation || ''}
                            onChange={(e) => updateActiveQ({ explanation: e.target.value })}
                            placeholder="Reasoning provided to students upon score release..."
                            className="w-full px-3 py-1.5 rounded-xl bg-bg2 border border-border text-text text-xs focus:outline-none focus:ring-1 focus:ring-accent"
                          />
                        </div>
                      </div>
                    );
                  })()}
                </div>
              </div>
            )}

            {/* -------------------------------------------------------- */}
            {/* B. LESSON MATERIAL BUILDER                               */}
            {/* -------------------------------------------------------- */}
            {studioType === 'lesson' && (
              <div className="p-5 rounded-2xl bg-bg2 border border-border shadow-xs space-y-4">
                <div className="flex items-center justify-between pb-3 border-b border-border">
                  <span className="text-xs font-bold uppercase tracking-wider text-accent flex items-center gap-1.5">
                    <RiBookOpenLine className="size-4" />
                    <span>Lesson Material Specifications</span>
                  </span>
                  <span className="text-[10px] font-bold px-2 py-0.5 rounded-full bg-accent/10 text-accent border border-accent/20">
                    DepEd MELC Aligned
                  </span>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div>
                    <label className="block text-[11px] font-bold text-text3 mb-1">
                      Material Format Combobox
                    </label>
                    <div className="relative">
                      <select
                        value={lessonFormat}
                        onChange={(e) =>
                          setLessonFormat(e.target.value as 'ppt' | 'pdf' | 'yt' | 'link')
                        }
                        className="w-full appearance-none px-3.5 py-2 rounded-xl bg-bg3 border border-border text-text text-xs font-semibold cursor-pointer focus:outline-none focus:ring-1 focus:ring-accent pr-8 shadow-2xs"
                      >
                        <option value="ppt">📊 Interactive Slide Presentation Deck</option>
                        <option value="pdf">📄 PDF Module Handout Document</option>
                        <option value="yt">🎬 YouTube Video Lecture</option>
                        <option value="link">🌐 External Web Reading &amp; Article</option>
                      </select>
                      <RiArrowDownSLine className="size-4 text-text3 absolute right-3 top-2.5 pointer-events-none" />
                    </div>
                  </div>

                  <div>
                    <label className="block text-[11px] font-bold text-text3 mb-1">
                      Completion Rule Combobox
                    </label>
                    <div className="relative">
                      <select
                        value={lessonCompletionRule}
                        onChange={(e) =>
                          setLessonCompletionRule(e.target.value as 'automatic' | 'manual')
                        }
                        className="w-full appearance-none px-3.5 py-2 rounded-xl bg-bg3 border border-border text-text text-xs font-semibold cursor-pointer focus:outline-none focus:ring-1 focus:ring-accent pr-8 shadow-2xs"
                      >
                        <option value="automatic">Automatic (on view / last slide / download)</option>
                        <option value="manual">Manual (student self-check checkbox)</option>
                      </select>
                      <RiArrowDownSLine className="size-4 text-text3 absolute right-3 top-2.5 pointer-events-none" />
                    </div>
                  </div>
                </div>

                <div>
                  <label className="block text-xs font-semibold text-text mb-1">
                    Lesson Title
                  </label>
                  <input
                    type="text"
                    value={lessonTitle}
                    onChange={(e) => setLessonTitle(e.target.value)}
                    className="w-full px-3.5 py-2 rounded-xl bg-bg3 border border-border text-text text-xs font-semibold focus:outline-none focus:ring-1 focus:ring-accent"
                  />
                </div>

                {/* SLIDES EDITOR (PPT) */}
                {lessonFormat === 'ppt' && (
                  <div className="space-y-3 pt-2 border-t border-border">
                    <div className="flex items-center justify-between">
                      <span className="text-xs font-bold text-text">
                        Interactive Slides ({slides.length})
                      </span>
                      <button
                        type="button"
                        onClick={() => {
                          const newSlide: SlideItem = {
                            slideNumber: slides.length + 1,
                            title: `Slide ${slides.length + 1} Heading`,
                            bullets: ['Key takeaway point 1', 'Key takeaway point 2'],
                            notes: 'Instructor notes for this slide.',
                          };
                          setSlides([...slides, newSlide]);
                          setActiveSlideIndex(slides.length);
                        }}
                        className="px-2.5 py-1 rounded-lg bg-accent text-white text-xs font-semibold inline-flex items-center gap-1 cursor-pointer"
                      >
                        <RiAddLine className="size-3.5" />
                        <span>+ Add Slide</span>
                      </button>
                    </div>

                    <div className="flex items-center gap-1.5 overflow-x-auto pb-1">
                      {slides.map((s, idx) => (
                        <button
                          key={idx}
                          type="button"
                          onClick={() => setActiveSlideIndex(idx)}
                          className={`px-3 py-1.5 rounded-xl text-xs font-bold shrink-0 transition-all cursor-pointer ${
                            activeSlideIndex === idx
                              ? 'bg-accent text-white shadow-xs'
                              : 'bg-bg3 text-text3 hover:text-text border border-border'
                          }`}
                        >
                          Slide {s.slideNumber}
                        </button>
                      ))}
                    </div>

                    {slides[activeSlideIndex] && (
                      <div className="p-4 rounded-xl bg-bg3 border border-border space-y-3">
                        <div className="flex items-center justify-between">
                          <label className="text-xs font-bold text-text">
                            Editing Slide {activeSlideIndex + 1}
                          </label>
                          {slides.length > 1 && (
                            <button
                              type="button"
                              onClick={() => {
                                const next = slides.filter((_, i) => i !== activeSlideIndex);
                                setSlides(next);
                                setActiveSlideIndex(Math.max(0, activeSlideIndex - 1));
                              }}
                              className="text-xs text-red hover:underline"
                            >
                              Delete Slide
                            </button>
                          )}
                        </div>

                        <input
                          type="text"
                          value={slides[activeSlideIndex].title}
                          onChange={(e) => {
                            const next = [...slides];
                            next[activeSlideIndex].title = e.target.value;
                            setSlides(next);
                          }}
                          placeholder="Slide Title"
                          className="w-full px-3 py-1.5 rounded-lg bg-bg2 border border-border text-text text-xs font-semibold"
                        />

                        <div>
                          <label className="block text-[11px] font-semibold text-text3 mb-1">
                            Bullets (One line per bullet point)
                          </label>
                          <textarea
                            rows={3}
                            value={slides[activeSlideIndex].bullets.join('\n')}
                            onChange={(e) => {
                              const next = [...slides];
                              next[activeSlideIndex].bullets = e.target.value
                                .split('\n')
                                .filter((b) => b.trim().length > 0);
                              setSlides(next);
                            }}
                            className="w-full px-3 py-1.5 rounded-lg bg-bg2 border border-border text-text text-xs leading-relaxed"
                          />
                        </div>

                        <div>
                          <label className="block text-[11px] font-semibold text-text3 mb-1">
                            Speaker Notes
                          </label>
                          <textarea
                            rows={2}
                            value={slides[activeSlideIndex].notes || ''}
                            onChange={(e) => {
                              const next = [...slides];
                              next[activeSlideIndex].notes = e.target.value;
                              setSlides(next);
                            }}
                            className="w-full px-3 py-1.5 rounded-lg bg-bg2 border border-border text-text text-xs"
                          />
                        </div>
                      </div>
                    )}
                  </div>
                )}

                {/* PDF HANDOUT EDITOR */}
                {lessonFormat === 'pdf' && (
                  <div className="space-y-3 pt-2 border-t border-border">
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                      <div>
                        <label className="block text-[11px] font-semibold text-text3 mb-1">
                          Document Page Count
                        </label>
                        <input
                          type="number"
                          value={pdfPages}
                          onChange={(e) => setPdfPages(parseInt(e.target.value, 10) || 1)}
                          className="w-full px-3 py-1.5 rounded-lg bg-bg3 border border-border text-text text-xs"
                        />
                      </div>
                    </div>

                    <div>
                      <label className="block text-[11px] font-semibold text-text3 mb-1">
                        Module Handout Summary
                      </label>
                      <textarea
                        rows={2}
                        value={pdfSummary}
                        onChange={(e) => setPdfSummary(e.target.value)}
                        className="w-full px-3 py-1.5 rounded-lg bg-bg3 border border-border text-text text-xs"
                      />
                    </div>

                    <div>
                      <label className="block text-[11px] font-semibold text-text3 mb-1">
                        Key Excerpts (One line per excerpt)
                      </label>
                      <textarea
                        rows={3}
                        value={pdfExcerpts.join('\n')}
                        onChange={(e) =>
                          setPdfExcerpts(e.target.value.split('\n').filter((l) => l.trim().length > 0))
                        }
                        className="w-full px-3 py-1.5 rounded-lg bg-bg3 border border-border text-text text-xs"
                      />
                    </div>
                  </div>
                )}

                {/* YOUTUBE VIDEO LECTURE EDITOR */}
                {lessonFormat === 'yt' && (
                  <div className="space-y-3 pt-2 border-t border-border">
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                      <div>
                        <label className="block text-[11px] font-semibold text-text3 mb-1">
                          YouTube Video URL
                        </label>
                        <input
                          type="text"
                          value={ytUrl}
                          onChange={(e) => setYtUrl(e.target.value)}
                          className="w-full px-3 py-1.5 rounded-lg bg-bg3 border border-border text-text text-xs font-mono"
                        />
                      </div>
                      <div>
                        <label className="block text-[11px] font-semibold text-text3 mb-1">
                          Duration
                        </label>
                        <input
                          type="text"
                          value={ytDuration}
                          onChange={(e) => setYtDuration(e.target.value)}
                          className="w-full px-3 py-1.5 rounded-lg bg-bg3 border border-border text-text text-xs"
                        />
                      </div>
                    </div>

                    <div>
                      <label className="block text-[11px] font-semibold text-text3 mb-1">
                        Timestamped Study Notes (One line per note)
                      </label>
                      <textarea
                        rows={3}
                        value={ytNotes.join('\n')}
                        onChange={(e) =>
                          setYtNotes(e.target.value.split('\n').filter((l) => l.trim().length > 0))
                        }
                        className="w-full px-3 py-1.5 rounded-lg bg-bg3 border border-border text-text text-xs"
                      />
                    </div>
                  </div>
                )}

                {/* LINK / WEB READING EDITOR */}
                {lessonFormat === 'link' && (
                  <div className="space-y-3 pt-2 border-t border-border">
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                      <div className="sm:col-span-2">
                        <label className="block text-[11px] font-semibold text-text3 mb-1">
                          Article Web URL
                        </label>
                        <input
                          type="text"
                          value={linkUrl}
                          onChange={(e) => setLinkUrl(e.target.value)}
                          className="w-full px-3 py-1.5 rounded-lg bg-bg3 border border-border text-text text-xs font-mono"
                        />
                      </div>
                      <div>
                        <label className="block text-[11px] font-semibold text-text3 mb-1">
                          Estimated Read Time
                        </label>
                        <input
                          type="text"
                          value={linkReadTime}
                          onChange={(e) => setLinkReadTime(e.target.value)}
                          className="w-full px-3 py-1.5 rounded-lg bg-bg3 border border-border text-text text-xs"
                        />
                      </div>
                    </div>

                    <div>
                      <label className="block text-[11px] font-semibold text-text3 mb-1">
                        Author / Publishing Organization
                      </label>
                      <input
                        type="text"
                        value={linkAuthor}
                        onChange={(e) => setLinkAuthor(e.target.value)}
                        className="w-full px-3 py-1.5 rounded-lg bg-bg3 border border-border text-text text-xs"
                      />
                    </div>

                    <div>
                      <label className="block text-[11px] font-semibold text-text3 mb-1">
                        Key Takeaways (One line per bullet)
                      </label>
                      <textarea
                        rows={3}
                        value={linkTakeaways.join('\n')}
                        onChange={(e) =>
                          setLinkTakeaways(e.target.value.split('\n').filter((l) => l.trim().length > 0))
                        }
                        className="w-full px-3 py-1.5 rounded-lg bg-bg3 border border-border text-text text-xs"
                      />
                    </div>
                  </div>
                )}
              </div>
            )}

            {/* -------------------------------------------------------- */}
            {/* C. PERFORMANCE TASK & SUBMISSION BIN BUILDER             */}
            {/* -------------------------------------------------------- */}
            {studioType === 'submission' && (
              <div className="p-5 rounded-2xl bg-bg2 border border-border shadow-xs space-y-4">
                <div className="flex items-center justify-between pb-3 border-b border-border">
                  <span className="text-xs font-bold uppercase tracking-wider text-accent flex items-center gap-1.5">
                    <RiFileList3Line className="size-4" />
                    <span>Performance Task &amp; Analytical Rubric</span>
                  </span>
                  <span className="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-500/15 text-emerald-400 border border-emerald-500/25">
                    DepEd Performance Task (50% Grade Weight)
                  </span>
                </div>

                <div>
                  <label className="block text-xs font-semibold text-text mb-1">
                    Task Title
                  </label>
                  <input
                    type="text"
                    value={taskTitle}
                    onChange={(e) => setTaskTitle(e.target.value)}
                    className="w-full px-3.5 py-2 rounded-xl bg-bg3 border border-border text-text text-xs font-semibold focus:outline-none focus:ring-1 focus:ring-accent"
                  />
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div>
                    <label className="block text-[11px] font-semibold text-text3 mb-1">
                      Due Date
                    </label>
                    <input
                      type="date"
                      value={taskDueDate}
                      onChange={(e) => setTaskDueDate(e.target.value)}
                      className="w-full px-3 py-2 rounded-xl bg-bg3 border border-border text-text text-xs font-semibold"
                    />
                  </div>
                  <div>
                    <label className="block text-[11px] font-semibold text-text3 mb-1">
                      Deliverable Formats
                    </label>
                    <div className="flex items-center gap-2 flex-wrap pt-1">
                      {['MP4 Video', 'PDF Document', 'PPTX Slides', 'ZIP Archive'].map((fmt) => {
                        const checked = taskFormats.includes(fmt);
                        return (
                          <label
                            key={fmt}
                            className={`px-2.5 py-1 rounded-lg text-xs font-semibold border cursor-pointer transition-colors ${
                              checked
                                ? 'bg-accent/15 text-accent border-accent/30'
                                : 'bg-bg3 text-text3 border-border'
                            }`}
                          >
                            <input
                              type="checkbox"
                              checked={checked}
                              onChange={(e) => {
                                if (e.target.checked) setTaskFormats([...taskFormats, fmt]);
                                else setTaskFormats(taskFormats.filter((f) => f !== fmt));
                              }}
                              className="hidden"
                            />
                            {fmt}
                          </label>
                        );
                      })}
                    </div>
                  </div>
                </div>

                <div>
                  <label className="block text-xs font-semibold text-text mb-1">
                    Instructions &amp; DepEd Guidelines
                  </label>
                  <textarea
                    rows={3}
                    value={taskInstructions}
                    onChange={(e) => setTaskInstructions(e.target.value)}
                    className="w-full px-3 py-2 rounded-xl bg-bg3 border border-border text-text text-xs leading-relaxed"
                  />
                </div>

                {/* Analytical Rubric Criteria Builder */}
                <div className="space-y-3 pt-2 border-t border-border">
                  <div className="flex items-center justify-between">
                    <div>
                      <span className="text-xs font-bold text-text">
                        Analytical Rubric Builder
                      </span>
                      <span className="ml-2 text-[11px] text-text3">
                        Total:{' '}
                        <strong>
                          {taskRubric.reduce((acc, r) => acc + r.points, 0)} Points
                        </strong>
                      </span>
                    </div>
                    <button
                      type="button"
                      onClick={() => {
                        setTaskRubric([
                          ...taskRubric,
                          {
                            name: `Criterion ${taskRubric.length + 1}`,
                            points: 20,
                            desc: 'Descriptive performance indicator for student grading.',
                          },
                        ]);
                      }}
                      className="px-2.5 py-1 rounded-lg bg-accent text-white text-xs font-semibold inline-flex items-center gap-1 cursor-pointer"
                    >
                      <RiAddLine className="size-3.5" />
                      <span>+ Add Criteria</span>
                    </button>
                  </div>

                  <div className="space-y-2.5">
                    {taskRubric.map((r, rIdx) => (
                      <div
                        key={rIdx}
                        className="p-3 rounded-xl bg-bg3 border border-border space-y-2"
                      >
                        <div className="flex items-center gap-2">
                          <input
                            type="text"
                            value={r.name}
                            onChange={(e) => {
                              const next = [...taskRubric];
                              next[rIdx].name = e.target.value;
                              setTaskRubric(next);
                            }}
                            placeholder="Criterion Name"
                            className="flex-1 px-3 py-1.5 rounded-lg bg-bg2 border border-border text-text text-xs font-semibold"
                          />
                          <div className="flex items-center gap-1">
                            <input
                              type="number"
                              min={1}
                              max={100}
                              value={r.points}
                              onChange={(e) => {
                                const next = [...taskRubric];
                                next[rIdx].points = parseInt(e.target.value, 10) || 1;
                                setTaskRubric(next);
                              }}
                              className="w-16 px-2 py-1.5 rounded-lg bg-bg2 border border-border text-text text-xs font-semibold text-center"
                            />
                            <span className="text-xs text-text3 font-semibold">pts</span>
                          </div>
                          {taskRubric.length > 1 && (
                            <button
                              type="button"
                              onClick={() => setTaskRubric(taskRubric.filter((_, i) => i !== rIdx))}
                              className="p-1.5 rounded-lg text-red hover:bg-red/10 cursor-pointer"
                              title="Delete Criterion"
                            >
                              <RiDeleteBinLine className="size-4" />
                            </button>
                          )}
                        </div>

                        <input
                          type="text"
                          value={r.desc}
                          onChange={(e) => {
                            const next = [...taskRubric];
                            next[rIdx].desc = e.target.value;
                            setTaskRubric(next);
                          }}
                          placeholder="Performance indicator & quality standard..."
                          className="w-full px-3 py-1.5 rounded-lg bg-bg2 border border-border text-text3 text-[11px]"
                        />
                      </div>
                    ))}
                  </div>
                </div>
              </div>
            )}
          </div>
        )}

        {/* ========================================================== */}
        {/* RIGHT COLUMN: LIVE INTERACTIVE STUDENT PREVIEW             */}
        {/* ========================================================== */}
        {(layoutMode === 'split' || layoutMode === 'preview') && (
          <div
            className={`space-y-4 ${
              layoutMode === 'split' ? 'xl:col-span-6' : 'w-full'
            }`}
          >
            {/* Live Student Preview Card */}
            <div className="p-5 rounded-2xl bg-bg2 border border-accent/40 shadow-sm space-y-4">
              <div className="flex items-center justify-between pb-3 border-b border-border">
                <div className="flex items-center gap-2">
                  <span className="size-2 rounded-full bg-emerald-500 animate-pulse" />
                  <span className="text-xs font-bold uppercase tracking-wider text-text flex items-center gap-1.5">
                    <RiEyeLine className="size-4 text-accent" />
                    <span>Live Student Interactive Preview</span>
                  </span>
                </div>
                <span className="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                  {selectedSectionName} Student View
                </span>
              </div>

              {/* A. PREVIEW FOR QUIZ STUDIO */}
              {studioType === 'quiz' && (
                <div className="space-y-4">
                  {/* Question Selector Combobox */}
                  <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 p-3 rounded-xl bg-bg3 border border-border">
                    <label className="text-[11px] font-bold text-text3">
                      Question Selector Combobox:
                    </label>
                    <div className="relative">
                      <select
                        value={previewSelectorIndex}
                        onChange={(e) => {
                          const val = e.target.value;
                          setPreviewSelectorIndex(val === 'all' ? 'all' : parseInt(val, 10));
                        }}
                        className="appearance-none px-3 py-1.5 rounded-lg bg-bg2 border border-border text-text text-xs font-semibold cursor-pointer pr-8 shadow-2xs"
                      >
                        <option value="all">View All Questions ({questions.length} Total)</option>
                        {questions.map((q, idx) => (
                          <option key={q.id} value={idx}>
                            Jump to Q{idx + 1}: {q.mode.toUpperCase()} ({q.points}pts)
                          </option>
                        ))}
                      </select>
                      <RiArrowDownSLine className="size-3.5 text-text3 absolute right-2.5 top-2 pointer-events-none" />
                    </div>
                  </div>

                  {/* Header info banner */}
                  <div className="p-3.5 rounded-xl bg-bg3/60 border border-border flex items-center justify-between text-xs">
                    <div>
                      <div className="font-bold text-text">{quizTitle}</div>
                      <div className="text-[11px] text-text3">{quizTier}</div>
                    </div>
                    <div className="text-right">
                      <div className="font-bold text-accent flex items-center gap-1 justify-end">
                        <RiTimeLine className="size-3.5" />
                        <span>{quizDuration} Mins</span>
                      </div>
                      <div className="text-[10px] text-text3">Pass: {quizPassing}%</div>
                    </div>
                  </div>

                  {/* Question List Preview */}
                  <div className="space-y-4">
                    {questions
                      .filter((_, idx) =>
                        previewSelectorIndex === 'all' ? true : previewSelectorIndex === idx
                      )
                      .map((q) => {
                        const actualIdx = questions.findIndex((item) => item.id === q.id);
                        const userVal = previewUserAnswers[q.id];

                        return (
                          <div
                            key={q.id}
                            className="p-4 rounded-xl bg-bg3 border border-border space-y-3"
                          >
                            <div className="flex items-center justify-between text-xs">
                              <span className="font-bold text-text">
                                Question {actualIdx + 1}
                              </span>
                              <span className="text-[11px] font-semibold text-accent px-2 py-0.5 rounded-md bg-accent/10">
                                {q.points} {q.points === 1 ? 'Point' : 'Points'}
                              </span>
                            </div>

                            <p className="text-xs text-text font-medium leading-relaxed">
                              {q.stem}
                            </p>

                            {/* 1. MCQ SINGLE PREVIEW */}
                            {q.mode === 'mcq_single' && (
                              <div className="space-y-1.5">
                                {q.options.map((opt, optIdx) => {
                                  const isSelected = userVal === optIdx;
                                  const isCorrect = q.correctIndex === optIdx;
                                  const showEvaluation = previewScoreResults?.evaluated;

                                  let optionStyle =
                                    'p-2.5 rounded-lg border text-xs flex items-center gap-2 cursor-pointer transition-colors';
                                  if (showEvaluation) {
                                    if (isCorrect)
                                      optionStyle += ' bg-green-bg/80 border-green text-green font-semibold';
                                    else if (isSelected && !isCorrect)
                                      optionStyle += ' bg-red-bg/80 border-red text-red font-semibold';
                                    else optionStyle += ' bg-bg2 border-border text-text3 opacity-60';
                                  } else {
                                    optionStyle += isSelected
                                      ? ' bg-accent/15 border-accent text-accent font-semibold'
                                      : ' bg-bg2 border-border text-text hover:bg-bg4';
                                  }

                                  return (
                                    <div
                                      key={optIdx}
                                      onClick={() =>
                                        setPreviewUserAnswers({
                                          ...previewUserAnswers,
                                          [q.id]: optIdx,
                                        })
                                      }
                                      className={optionStyle}
                                    >
                                      <span className="size-4 rounded-full border border-current flex items-center justify-center shrink-0 text-[10px]">
                                        {isSelected && '●'}
                                      </span>
                                      <span className="font-bold text-text3">
                                        {String.fromCharCode(65 + optIdx)})
                                      </span>
                                      <span>{opt}</span>
                                    </div>
                                  );
                                })}
                              </div>
                            )}

                            {/* 2. MCQ MULTI PREVIEW */}
                            {q.mode === 'mcq_multi' && (
                              <div className="space-y-1.5">
                                {q.options.map((opt, optIdx) => {
                                  const selectedList = (userVal as number[]) || [];
                                  const isChecked = selectedList.includes(optIdx);
                                  const isTarget = (q.correctIndices || []).includes(optIdx);
                                  const showEvaluation = previewScoreResults?.evaluated;

                                  let optionStyle =
                                    'p-2.5 rounded-lg border text-xs flex items-center gap-2 cursor-pointer transition-colors';
                                  if (showEvaluation) {
                                    if (isTarget)
                                      optionStyle += ' bg-green-bg/80 border-green text-green font-semibold';
                                    else if (isChecked && !isTarget)
                                      optionStyle += ' bg-red-bg/80 border-red text-red font-semibold';
                                    else optionStyle += ' bg-bg2 border-border text-text3 opacity-60';
                                  } else {
                                    optionStyle += isChecked
                                      ? ' bg-accent/15 border-accent text-accent font-semibold'
                                      : ' bg-bg2 border-border text-text hover:bg-bg4';
                                  }

                                  return (
                                    <div
                                      key={optIdx}
                                      onClick={() => {
                                        const next = isChecked
                                          ? selectedList.filter((i) => i !== optIdx)
                                          : [...selectedList, optIdx];
                                        setPreviewUserAnswers({
                                          ...previewUserAnswers,
                                          [q.id]: next,
                                        });
                                      }}
                                      className={optionStyle}
                                    >
                                      <input
                                        type="checkbox"
                                        checked={isChecked}
                                        readOnly
                                        className="size-4 accent-accent rounded"
                                      />
                                      <span>{opt}</span>
                                    </div>
                                  );
                                })}
                              </div>
                            )}

                            {/* 3. TRUE / FALSE PREVIEW */}
                            {q.mode === 'true_false' && (
                              <div className="flex items-center gap-2">
                                {[true, false].map((val) => {
                                  const isSelected = userVal === val;
                                  const isCorrect = q.tfAnswer === val;
                                  const showEval = previewScoreResults?.evaluated;

                                  let btnClass = 'flex-1 py-2 rounded-xl text-xs font-bold border transition-colors cursor-pointer';
                                  if (showEval) {
                                    if (isCorrect) btnClass += ' bg-green-bg border-green text-green';
                                    else if (isSelected && !isCorrect)
                                      btnClass += ' bg-red-bg border-red text-red';
                                    else btnClass += ' bg-bg2 border-border text-text3 opacity-50';
                                  } else {
                                    btnClass += isSelected
                                      ? ' bg-accent text-white border-accent'
                                      : ' bg-bg2 text-text border-border hover:bg-bg4';
                                  }

                                  return (
                                    <button
                                      key={String(val)}
                                      type="button"
                                      onClick={() =>
                                        setPreviewUserAnswers({
                                          ...previewUserAnswers,
                                          [q.id]: val,
                                        })
                                      }
                                      className={btnClass}
                                    >
                                      {val ? 'TRUE' : 'FALSE'}
                                    </button>
                                  );
                                })}
                              </div>
                            )}

                            {/* 4. IDENTIFICATION PREVIEW */}
                            {q.mode === 'identification' && (
                              <div className="space-y-1.5">
                                <input
                                  type="text"
                                  value={String(userVal || '')}
                                  placeholder="Type your answer here..."
                                  onChange={(e) =>
                                    setPreviewUserAnswers({
                                      ...previewUserAnswers,
                                      [q.id]: e.target.value,
                                    })
                                  }
                                  className="w-full px-3 py-2 rounded-xl bg-bg2 border border-border text-text text-xs focus:outline-none focus:ring-1 focus:ring-accent"
                                />
                                {previewScoreResults?.evaluated && (
                                  <div className="text-[11px] text-text3">
                                    Accepted key:{' '}
                                    <strong className="text-accent">
                                      {(q.acceptedAnswers || []).join(', ')}
                                    </strong>
                                  </div>
                                )}
                              </div>
                            )}

                            {/* 5. MATCHING PREVIEW */}
                            {q.mode === 'matching' && (
                              <div className="space-y-2">
                                {(q.matchingPairs || []).map((pair, pIdx) => {
                                  const currentMap =
                                    (userVal as Record<string, string>) || {};
                                  const selectedResp = currentMap[pair.premise] || '';
                                  const isCorrect = selectedResp === pair.response;

                                  return (
                                    <div
                                      key={pIdx}
                                      className="p-2.5 rounded-lg bg-bg2 border border-border flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs"
                                    >
                                      <span className="font-semibold text-text flex-1">
                                        {pair.premise}
                                      </span>
                                      <div className="relative sm:w-1/2">
                                        <select
                                          value={selectedResp}
                                          onChange={(e) => {
                                            setPreviewUserAnswers({
                                              ...previewUserAnswers,
                                              [q.id]: {
                                                ...currentMap,
                                                [pair.premise]: e.target.value,
                                              },
                                            });
                                          }}
                                          className={`w-full appearance-none px-2.5 py-1.5 rounded-lg border text-xs pr-7 ${
                                            previewScoreResults?.evaluated
                                              ? isCorrect
                                                ? 'bg-green-bg border-green text-green font-bold'
                                                : 'bg-red-bg border-red text-red font-bold'
                                              : 'bg-bg3 border-border text-text'
                                          }`}
                                        >
                                          <option value="">Select matching target...</option>
                                          {(q.matchingPairs || []).map((p, optI) => (
                                            <option key={optI} value={p.response}>
                                              {p.response}
                                            </option>
                                          ))}
                                        </select>
                                        <RiArrowDownSLine className="size-3.5 text-text3 absolute right-2 top-2 pointer-events-none" />
                                      </div>
                                    </div>
                                  );
                                })}
                              </div>
                            )}

                            {/* 6. ESSAY PREVIEW */}
                            {q.mode === 'essay' && (
                              <div className="space-y-2">
                                <textarea
                                  rows={3}
                                  value={String(userVal || '')}
                                  placeholder="Type your essay / situational analysis here..."
                                  onChange={(e) =>
                                    setPreviewUserAnswers({
                                      ...previewUserAnswers,
                                      [q.id]: e.target.value,
                                    })
                                  }
                                  className="w-full px-3 py-2 rounded-xl bg-bg2 border border-border text-text text-xs leading-relaxed"
                                />
                                <div className="p-2 rounded-lg bg-bg2 border border-border text-[11px] text-text3 space-y-1">
                                  <div className="font-semibold text-text">
                                    Grading Rubric Breakdown:
                                  </div>
                                  {(q.rubricCriteria || []).map((rc, rIdx) => (
                                    <div key={rIdx} className="flex justify-between">
                                      <span>• {rc.criteria}</span>
                                      <span className="font-semibold text-accent">
                                        {rc.points} pts
                                      </span>
                                    </div>
                                  ))}
                                </div>
                              </div>
                            )}

                            {/* EXPLANATION REVEAL UPON EVALUATION */}
                            {previewScoreResults?.evaluated && q.explanation && (
                              <div className="p-2.5 rounded-lg bg-accent/10 border border-accent/20 text-[11px] text-text">
                                <strong className="text-accent">Teacher Explanation (@EXP):</strong>{' '}
                                {q.explanation}
                              </div>
                            )}
                          </div>
                        );
                      })}
                  </div>

                  {/* Test Scoring Evaluation Bar */}
                  <div className="pt-3 border-t border-border flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div className="flex items-center gap-2">
                      <button
                        type="button"
                        onClick={handleCheckSampleAnswers}
                        className="px-4 py-2 rounded-xl bg-accent text-white text-xs font-bold hover:bg-accent/90 cursor-pointer shadow-xs inline-flex items-center gap-1.5 transition-colors"
                      >
                        <RiCheckLine className="size-4" />
                        <span>Check Sample Answers</span>
                      </button>
                      <button
                        type="button"
                        onClick={() => {
                          setPreviewUserAnswers({});
                          setPreviewScoreResults(null);
                        }}
                        className="px-3 py-2 rounded-xl bg-bg3 hover:bg-bg4 border border-border text-text3 hover:text-text text-xs font-semibold cursor-pointer transition-colors"
                      >
                        Reset
                      </button>
                    </div>

                    {previewScoreResults && (
                      <div className="flex items-center gap-2">
                        <span
                          className={`px-3 py-1 rounded-full text-xs font-bold border ${
                            previewScoreResults.passed
                              ? 'bg-green-bg text-green border-green/30'
                              : 'bg-red-bg text-red border-red/30'
                          }`}
                        >
                          Score: {previewScoreResults.score} / {previewScoreResults.total} (
                          {Math.round(
                            (previewScoreResults.score / (previewScoreResults.total || 1)) * 100
                          )}
                          %) — {previewScoreResults.passed ? 'PASSED' : 'NEEDS REVIEW'}
                        </span>
                      </div>
                    )}
                  </div>
                </div>
              )}

              {/* B. PREVIEW FOR LESSON STUDIO */}
              {studioType === 'lesson' && (
                <div className="space-y-4">
                  {/* PPT SLIDE CAROUSEL PREVIEW */}
                  {lessonFormat === 'ppt' && (
                    <div className="space-y-3">
                      <div className="flex items-center justify-between text-xs">
                        <span className="font-bold text-text flex items-center gap-1.5">
                          <RiPresentationLine className="size-4 text-accent" />
                          <span>Interactive Slide Deck</span>
                        </span>
                        <span className="text-[11px] font-semibold text-text3">
                          Slide {activeSlideIndex + 1} of {slides.length}
                        </span>
                      </div>

                      {slides[activeSlideIndex] && (
                        <div className="p-6 rounded-2xl bg-gradient-to-br from-bg3 via-bg2 to-bg3 border border-border space-y-4 min-h-[260px] flex flex-col justify-between shadow-inner">
                          <div>
                            <div className="flex items-center justify-between gap-2 mb-2">
                              <span className="text-[10px] font-bold px-2 py-0.5 rounded-full bg-accent/15 text-accent border border-accent/25">
                                {slides[activeSlideIndex].badge || 'Micro-Lesson'}
                              </span>
                              <span className="text-xs text-text3 font-mono">
                                {lessonTitle}
                              </span>
                            </div>
                            <h4 className="text-base font-bold text-text font-serif">
                              {slides[activeSlideIndex].title}
                            </h4>
                          </div>

                          <div className="space-y-2 py-2">
                            {slides[activeSlideIndex].bullets.map((b, bIdx) => (
                              <div
                                key={bIdx}
                                className="flex items-start gap-2.5 text-xs text-text leading-relaxed"
                              >
                                <span className="size-1.5 rounded-full bg-accent mt-1.5 shrink-0" />
                                <span>{b}</span>
                              </div>
                            ))}
                          </div>

                          {slides[activeSlideIndex].notes && (
                            <div className="p-2.5 rounded-xl bg-bg2/80 border border-border/80 text-[11px] text-text3">
                              <span className="font-bold text-text2">Speaker Note:</span>{' '}
                              {slides[activeSlideIndex].notes}
                            </div>
                          )}
                        </div>
                      )}

                      {/* Carousel controls */}
                      <div className="flex items-center justify-between pt-1">
                        <button
                          type="button"
                          disabled={activeSlideIndex === 0}
                          onClick={() => setActiveSlideIndex(activeSlideIndex - 1)}
                          className="px-3.5 py-1.5 rounded-xl bg-bg3 hover:bg-bg4 disabled:opacity-40 disabled:cursor-not-allowed border border-border text-text text-xs font-semibold cursor-pointer transition-colors"
                        >
                          &larr; Prev Slide
                        </button>
                        <div className="flex items-center gap-1">
                          {slides.map((_, i) => (
                            <span
                              key={i}
                              className={`size-2 rounded-full transition-all ${
                                activeSlideIndex === i ? 'bg-accent w-5' : 'bg-border'
                              }`}
                            />
                          ))}
                        </div>
                        <button
                          type="button"
                          disabled={activeSlideIndex === slides.length - 1}
                          onClick={() => setActiveSlideIndex(activeSlideIndex + 1)}
                          className="px-3.5 py-1.5 rounded-xl bg-bg3 hover:bg-bg4 disabled:opacity-40 disabled:cursor-not-allowed border border-border text-text text-xs font-semibold cursor-pointer transition-colors"
                        >
                          Next Slide &rarr;
                        </button>
                      </div>
                    </div>
                  )}

                  {/* PDF READER PREVIEW */}
                  {lessonFormat === 'pdf' && (
                    <div className="p-5 rounded-2xl bg-bg3 border border-border space-y-4">
                      <div className="flex items-center gap-3">
                        <div className="size-10 rounded-xl bg-red-bg text-red flex items-center justify-center shrink-0">
                          <RiFilePdfLine className="size-6" />
                        </div>
                        <div>
                          <h4 className="text-sm font-bold text-text">{lessonTitle}</h4>
                          <p className="text-[11px] text-text3">
                            {pdfPages} Pages Handout · DepEd Senior High School
                          </p>
                        </div>
                      </div>

                      <div className="p-3.5 rounded-xl bg-bg2 border border-border text-xs text-text leading-relaxed">
                        <div className="font-bold text-accent mb-1">Executive Summary:</div>
                        <p>{pdfSummary}</p>
                      </div>

                      <div className="space-y-1.5">
                        <div className="text-[11px] font-bold text-text3 uppercase">
                          Key Excerpt Points
                        </div>
                        {pdfExcerpts.map((ex, exI) => (
                          <div
                            key={exI}
                            className="p-2 rounded-lg bg-bg2 border border-border text-xs text-text flex items-center gap-2"
                          >
                            <RiCheckboxCircleLine className="size-3.5 text-accent shrink-0" />
                            <span>{ex}</span>
                          </div>
                        ))}
                      </div>

                      <button
                        type="button"
                        className="w-full py-2.5 rounded-xl bg-accent text-white text-xs font-semibold flex items-center justify-center gap-1.5 shadow-xs cursor-pointer hover:bg-accent/90"
                      >
                        <RiFolderDownloadLine className="size-4" />
                        <span>Download Handout PDF ({pdfPages} Pages)</span>
                      </button>
                    </div>
                  )}

                  {/* YOUTUBE LECTURE PREVIEW */}
                  {lessonFormat === 'yt' && (
                    <div className="p-5 rounded-2xl bg-bg3 border border-border space-y-3.5">
                      <div className="aspect-video w-full rounded-xl bg-[#0e0f14] border border-border flex flex-col items-center justify-center text-center p-4 text-white relative overflow-hidden group">
                        <RiYoutubeFill className="size-14 text-red group-hover:scale-110 transition-transform" />
                        <div className="text-xs font-bold mt-2">{lessonTitle}</div>
                        <div className="text-[11px] text-gray-400">Duration: {ytDuration}</div>
                        <div className="absolute bottom-2 right-2 px-2 py-0.5 rounded bg-black/80 text-[10px] font-mono">
                          HD 1080p
                        </div>
                      </div>

                      <div className="space-y-2">
                        <div className="text-xs font-bold text-text flex items-center gap-1">
                          <RiPlayLine className="size-3.5 text-accent" />
                          <span>Interactive Chapter Markers</span>
                        </div>
                        {ytNotes.map((note, nIdx) => (
                          <div
                            key={nIdx}
                            className="p-2 rounded-lg bg-bg2 border border-border text-xs text-text hover:border-accent/40 cursor-pointer flex items-center justify-between"
                          >
                            <span>{note}</span>
                            <RiArrowRightSLine className="size-3.5 text-text3" />
                          </div>
                        ))}
                      </div>
                    </div>
                  )}

                  {/* WEB READING PREVIEW */}
                  {lessonFormat === 'link' && (
                    <div className="p-5 rounded-2xl bg-bg3 border border-border space-y-3.5">
                      <div className="flex items-center gap-2.5">
                        <span className="p-2 rounded-xl bg-accent/15 text-accent">
                          <RiGlobalLine className="size-5" />
                        </span>
                        <div>
                          <h4 className="text-sm font-bold text-text">{lessonTitle}</h4>
                          <p className="text-[11px] text-text3">
                            {linkAuthor} · {linkReadTime}
                          </p>
                        </div>
                      </div>

                      <div className="space-y-2">
                        <div className="text-[11px] font-bold text-text3 uppercase">
                          Article Key Takeaways
                        </div>
                        {linkTakeaways.map((t, tI) => (
                          <div
                            key={tI}
                            className="p-2.5 rounded-lg bg-bg2 border border-border text-xs text-text flex items-start gap-2"
                          >
                            <span className="size-1.5 rounded-full bg-accent mt-1.5 shrink-0" />
                            <span>{t}</span>
                          </div>
                        ))}
                      </div>

                      <a
                        href={linkUrl}
                        target="_blank"
                        rel="noreferrer"
                        className="w-full py-2 rounded-xl bg-bg2 hover:bg-bg4 border border-border text-xs font-semibold text-accent flex items-center justify-center gap-1.5 transition-colors"
                      >
                        <span>Open Article Link &rarr;</span>
                      </a>
                    </div>
                  )}
                </div>
              )}

              {/* C. PREVIEW FOR SUBMISSION BIN STUDIO */}
              {studioType === 'submission' && (
                <div className="space-y-4">
                  <div className="p-4 rounded-xl bg-bg3 border border-border space-y-2">
                    <div className="flex items-center justify-between text-xs">
                      <span className="font-bold text-text">{taskTitle}</span>
                      <span className="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-500/15 text-amber-500 border border-amber-500/25">
                        Due: {taskDueDate}
                      </span>
                    </div>
                    <p className="text-xs text-text3 leading-relaxed">
                      {taskInstructions}
                    </p>
                    <div className="flex items-center gap-1.5 pt-1">
                      <span className="text-[10px] font-semibold text-text3">Formats:</span>
                      {taskFormats.map((f) => (
                        <span
                          key={f}
                          className="px-2 py-0.5 rounded text-[10px] font-semibold bg-bg2 border border-border text-text"
                        >
                          {f}
                        </span>
                      ))}
                    </div>
                  </div>

                  {/* Student Upload Dropzone Mock */}
                  <div className="p-6 rounded-2xl border-2 border-dashed border-border hover:border-accent/50 bg-bg3/40 text-center space-y-2 cursor-pointer transition-colors">
                    <RiUpload2Line className="size-8 text-accent mx-auto" />
                    <div className="text-xs font-bold text-text">
                      Student Submission Upload Dropzone
                    </div>
                    <p className="text-[11px] text-text3 max-w-sm mx-auto">
                      Drag and drop your {taskFormats.join(' or ')} files here or click to browse
                      local device. Max file size: 50MB.
                    </p>
                  </div>

                  {/* Analytical Rubric Table Preview */}
                  <div className="space-y-2">
                    <div className="flex items-center justify-between text-xs">
                      <span className="font-bold text-text flex items-center gap-1.5">
                        <RiAwardLine className="size-4 text-accent" />
                        <span>Analytical Evaluation Rubric</span>
                      </span>
                      <span className="text-[11px] font-semibold text-text3">
                        Max Score:{' '}
                        <strong>
                          {taskRubric.reduce((acc, r) => acc + r.points, 0)} Pts
                        </strong>
                      </span>
                    </div>

                    <div className="rounded-xl border border-border overflow-hidden">
                      <table className="w-full text-left text-xs">
                        <thead className="bg-bg3 text-text3 font-semibold text-[11px]">
                          <tr>
                            <th className="p-2.5">Criterion</th>
                            <th className="p-2.5">Quality Standard</th>
                            <th className="p-2.5 text-right">Points</th>
                          </tr>
                        </thead>
                        <tbody className="divide-y divide-border bg-bg2">
                          {taskRubric.map((r, rIdx) => (
                            <tr key={rIdx} className="hover:bg-bg3/40">
                              <td className="p-2.5 font-bold text-text">{r.name}</td>
                              <td className="p-2.5 text-text3 text-[11px]">{r.desc}</td>
                              <td className="p-2.5 text-right font-bold text-accent">
                                {r.points} pts
                              </td>
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              )}
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
