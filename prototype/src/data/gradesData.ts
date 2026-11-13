export interface QuizAttemptDetail {
  id: number;
  quizTitle: string;
  lessonTitle: string;
  score: number;
  maxScore: number;
  percentage: number;
  passingScore: number;
  passed: boolean;
  dateTaken: string;
  attemptNumber: number;
}

export interface QuarterComponentBreakdown {
  writtenWorks: {
    score: number;
    maxScore: number;
    percentage: number;
    weight: number; // e.g. 25%
    weightedScore: number;
  };
  performanceTasks: {
    score: number;
    maxScore: number;
    percentage: number;
    weight: number; // e.g. 50%
    weightedScore: number;
  };
  quarterlyExam: {
    score: number;
    maxScore: number;
    percentage: number;
    weight: number; // e.g. 25%
    weightedScore: number;
  };
  initialGrade: number;
  transmutedGrade: number;
}

export interface SubjectGradeRecord {
  id: string;
  code: string;
  title: string;
  category: 'core' | 'applied' | 'specialized';
  categoryLabel: 'Core Subject' | 'Applied Track Subject' | 'Specialized STEM Subject';
  instructor: string;
  instructorTitle: string;
  semester: 1 | 2;
  q1?: number;
  q2?: number;
  q3?: number;
  q4?: number;
  semFinal: number;
  actionTaken: 'Passed' | 'Failed';
  remarks?: string;
  quizzesTaken: number;
  totalScore: number;
  totalMax: number;
  averageGrade: number;
  passingScore: number;
  passed: boolean;
  lastActivity: string;
  quarterDetails?: {
    q1?: QuarterComponentBreakdown;
    q2?: QuarterComponentBreakdown;
    q3?: QuarterComponentBreakdown;
    q4?: QuarterComponentBreakdown;
  };
  quizzes: QuizAttemptDetail[];
}

export interface ObservedCoreValue {
  coreValue: 'Maka-Diyos' | 'Makatao' | 'Makakalikasan' | 'Makabansa';
  behaviorStatements: string[];
  q1: 'AO' | 'SO' | 'RO' | 'NO';
  q2: 'AO' | 'SO' | 'RO' | 'NO';
  q3: 'AO' | 'SO' | 'RO' | 'NO';
  q4: 'AO' | 'SO' | 'RO' | 'NO';
}

export interface MonthlyAttendance {
  month: string;
  daysOfSchool: number;
  daysPresent: number;
  daysAbsent: number;
}

export interface LearnerReportCardProfile {
  studentName: string;
  studentInitials: string;
  lrn: string;
  studentId: string;
  gradeLevel: string;
  section: string;
  track: string;
  strand: string;
  schoolYear: string;
  age: number;
  sex: 'Male' | 'Female';
  classAdviser: string;
  principal: string;
  schoolName: string;
  schoolId: string;
  division: string;
  region: string;
  firstSemGWA: number;
  secondSemGWA: number;
  generalAverage: number;
  promotionStatus: string;
  eligibleForAdmissionTo: string;
}

export const STUDENT_REPORT_PROFILE: LearnerReportCardProfile = {
  studentName: 'DONCERAS, BRYAN C.',
  studentInitials: 'BD',
  lrn: '109283746512',
  studentId: 'STU-2025-0182',
  gradeLevel: 'Grade 11',
  section: 'STEM 11-A',
  track: 'Academic Track',
  strand: 'Science, Technology, Engineering, and Mathematics (STEM)',
  schoolYear: '2025–2026',
  age: 16,
  sex: 'Male',
  classAdviser: 'Prof. Maria Santos, PhD',
  principal: 'Dr. Eduardo V. Morales',
  schoolName: 'Excelsior Polytechnic Academy',
  schoolId: '302194',
  division: 'Division of Albay',
  region: 'Region V - Bicol',
  firstSemGWA: 93.3,
  secondSemGWA: 92.5,
  generalAverage: 92.9,
  promotionStatus: 'Promoted to Grade 12 (With Honors)',
  eligibleForAdmissionTo: 'Grade 12 STEM Senior High School',
};

export const FIRST_SEMESTER_GRADES: SubjectGradeRecord[] = [
  {
    id: 's1-oral-comm',
    code: 'ENG-11',
    title: 'Oral Communication in Context',
    category: 'core',
    categoryLabel: 'Core Subject',
    instructor: 'Prof. Maria Santos, PhD',
    instructorTitle: 'Master Teacher II · English Dept',
    semester: 1,
    q1: 91,
    q2: 95,
    semFinal: 93,
    actionTaken: 'Passed',
    remarks: 'Demonstrates exemplary public delivery and rhetorical mastery.',
    quizzesTaken: 4,
    totalScore: 186,
    totalMax: 200,
    averageGrade: 93.0,
    passingScore: 75,
    passed: true,
    lastActivity: 'Nov 12, 2025',
    quarterDetails: {
      q1: {
        writtenWorks: { score: 92, maxScore: 100, percentage: 92, weight: 0.25, weightedScore: 23.0 },
        performanceTasks: { score: 91, maxScore: 100, percentage: 91, weight: 0.50, weightedScore: 45.5 },
        quarterlyExam: { score: 90, maxScore: 100, percentage: 90, weight: 0.25, weightedScore: 22.5 },
        initialGrade: 91.0,
        transmutedGrade: 91,
      },
      q2: {
        writtenWorks: { score: 94, maxScore: 100, percentage: 94, weight: 0.25, weightedScore: 23.5 },
        performanceTasks: { score: 96, maxScore: 100, percentage: 96, weight: 0.50, weightedScore: 48.0 },
        quarterlyExam: { score: 95, maxScore: 100, percentage: 95, weight: 0.25, weightedScore: 23.75 },
        initialGrade: 95.25,
        transmutedGrade: 95,
      },
    },
    quizzes: [
      { id: 101, quizTitle: 'Speech Acts & Non-Verbal Communication', lessonTitle: 'Functions of Communication', score: 48, maxScore: 50, percentage: 96.0, passingScore: 75, passed: true, dateTaken: 'Aug 24, 2025', attemptNumber: 1 },
      { id: 102, quizTitle: 'Intercultural Communication Scenarios', lessonTitle: 'Communicative Competence', score: 45, maxScore: 50, percentage: 90.0, passingScore: 75, passed: true, dateTaken: 'Sep 18, 2025', attemptNumber: 1 },
      { id: 103, quizTitle: 'Extemporaneous Speech Principles', lessonTitle: 'Principles of Speech Writing', score: 46, maxScore: 50, percentage: 92.0, passingScore: 75, passed: true, dateTaken: 'Oct 14, 2025', attemptNumber: 2 },
      { id: 104, quizTitle: 'Persuasive Delivery & Debate Formats', lessonTitle: 'Principles of Speech Delivery', score: 47, maxScore: 50, percentage: 94.0, passingScore: 75, passed: true, dateTaken: 'Nov 12, 2025', attemptNumber: 1 },
    ],
  },
  {
    id: 's1-gen-math',
    code: 'MATH-11',
    title: 'General Mathematics',
    category: 'core',
    categoryLabel: 'Core Subject',
    instructor: 'Engr. Ronald Gomez',
    instructorTitle: 'Subject Group Head · Mathematics',
    semester: 1,
    q1: 92,
    q2: 94,
    semFinal: 93,
    actionTaken: 'Passed',
    remarks: 'Consistent analytical precision in functions and business finance.',
    quizzesTaken: 4,
    totalScore: 186,
    totalMax: 200,
    averageGrade: 93.0,
    passingScore: 75,
    passed: true,
    lastActivity: 'Nov 14, 2025',
    quarterDetails: {
      q1: {
        writtenWorks: { score: 90, maxScore: 100, percentage: 90, weight: 0.25, weightedScore: 22.5 },
        performanceTasks: { score: 94, maxScore: 100, percentage: 94, weight: 0.50, weightedScore: 47.0 },
        quarterlyExam: { score: 92, maxScore: 100, percentage: 92, weight: 0.25, weightedScore: 23.0 },
        initialGrade: 92.5,
        transmutedGrade: 92,
      },
      q2: {
        writtenWorks: { score: 93, maxScore: 100, percentage: 93, weight: 0.25, weightedScore: 23.25 },
        performanceTasks: { score: 95, maxScore: 100, percentage: 95, weight: 0.50, weightedScore: 47.5 },
        quarterlyExam: { score: 94, maxScore: 100, percentage: 94, weight: 0.25, weightedScore: 23.5 },
        initialGrade: 94.25,
        transmutedGrade: 94,
      },
    },
    quizzes: [
      { id: 105, quizTitle: 'Functions, Inverses, & Rational Equations', lessonTitle: 'Rational Functions and Models', score: 47, maxScore: 50, percentage: 94.0, passingScore: 75, passed: true, dateTaken: 'Aug 29, 2025', attemptNumber: 1 },
      { id: 106, quizTitle: 'Exponential & Logarithmic Applications', lessonTitle: 'Logarithmic Functions & Equations', score: 45, maxScore: 50, percentage: 90.0, passingScore: 75, passed: true, dateTaken: 'Sep 25, 2025', attemptNumber: 1 },
      { id: 107, quizTitle: 'Simple & Compound Interest Calculations', lessonTitle: 'Basic Business Mathematics', score: 46, maxScore: 50, percentage: 92.0, passingScore: 75, passed: true, dateTaken: 'Oct 21, 2025', attemptNumber: 1 },
      { id: 108, quizTitle: 'Annuities, Stocks, & Propositional Logic', lessonTitle: 'Logic and Truth Tables', score: 48, maxScore: 50, percentage: 96.0, passingScore: 75, passed: true, dateTaken: 'Nov 14, 2025', attemptNumber: 1 },
    ],
  },
  {
    id: 's1-earth-life',
    code: 'SCI-11',
    title: 'Earth and Life Science',
    category: 'core',
    categoryLabel: 'Core Subject',
    instructor: 'Dr. Maria Hernandez',
    instructorTitle: 'Special Science Teacher III · Science Dept',
    semester: 1,
    q1: 94,
    q2: 96,
    semFinal: 95,
    actionTaken: 'Passed',
    remarks: 'Deep conceptual grasp of biogeochemical and cellular processes.',
    quizzesTaken: 4,
    totalScore: 190,
    totalMax: 200,
    averageGrade: 95.0,
    passingScore: 75,
    passed: true,
    lastActivity: 'Nov 15, 2025',
    quizzes: [
      { id: 109, quizTitle: 'Planetary Systems & Earth Subsystems', lessonTitle: 'Origin and Structure of the Earth', score: 47, maxScore: 50, percentage: 94.0, passingScore: 75, passed: true, dateTaken: 'Aug 30, 2025', attemptNumber: 1 },
      { id: 110, quizTitle: 'Plate Tectonics & Internal Heat of Earth', lessonTitle: 'Geologic Processes on Earth', score: 47, maxScore: 50, percentage: 94.0, passingScore: 75, passed: true, dateTaken: 'Sep 27, 2025', attemptNumber: 1 },
      { id: 111, quizTitle: 'Cellular Basis of Life & Bioenergetics', lessonTitle: 'Cellular Structures & Functions', score: 48, maxScore: 50, percentage: 96.0, passingScore: 75, passed: true, dateTaken: 'Oct 24, 2025', attemptNumber: 1 },
      { id: 112, quizTitle: 'Genetics, Evolution, & Ecological Ecosystems', lessonTitle: 'Evolutionary Mechanisms & Ecology', score: 48, maxScore: 50, percentage: 96.0, passingScore: 75, passed: true, dateTaken: 'Nov 15, 2025', attemptNumber: 1 },
    ],
  },
  {
    id: 's1-filipino',
    code: 'FIL-11',
    title: 'Komunikasyon at Pananaliksik sa Wika at Kulturang Pilipino',
    category: 'core',
    categoryLabel: 'Core Subject',
    instructor: 'Gng. Remedios Alcantara',
    instructorTitle: 'Dalubguro II · Kagawaran ng Filipino',
    semester: 1,
    q1: 88,
    q2: 92,
    semFinal: 90,
    actionTaken: 'Passed',
    remarks: 'Mahusay sa pagsusuri ng gamit ng wika sa midya at lipunan.',
    quizzesTaken: 3,
    totalScore: 135,
    totalMax: 150,
    averageGrade: 90.0,
    passingScore: 75,
    passed: true,
    lastActivity: 'Nov 10, 2025',
    quizzes: [
      { id: 113, quizTitle: 'Mga Konseptong Pangwika at Varayti', lessonTitle: 'Wika, Diyalekto, at Sosyolek', score: 43, maxScore: 50, percentage: 86.0, passingScore: 75, passed: true, dateTaken: 'Sep 05, 2025', attemptNumber: 1 },
      { id: 114, quizTitle: 'Kasaysayan ng Wikang Pambansa', lessonTitle: 'Ebolusyon ng Ortograpiyang Filipino', score: 45, maxScore: 50, percentage: 90.0, passingScore: 75, passed: true, dateTaken: 'Oct 08, 2025', attemptNumber: 1 },
      { id: 115, quizTitle: 'Kakayahang Lingguwistiko at Diskorsal', lessonTitle: 'Komunikatibong Paggamit ng Wika', score: 47, maxScore: 50, percentage: 94.0, passingScore: 75, passed: true, dateTaken: 'Nov 10, 2025', attemptNumber: 1 },
    ],
  },
  {
    id: 's1-lit-21',
    code: 'LIT-11',
    title: '21st Century Literature from the Philippines and the World',
    category: 'core',
    categoryLabel: 'Core Subject',
    instructor: 'Ms. Juna Velarde',
    instructorTitle: 'Senior High Faculty · Humanities',
    semester: 1,
    q1: 90,
    q2: 94,
    semFinal: 92,
    actionTaken: 'Passed',
    remarks: 'Insightful literary critique across regional and international texts.',
    quizzesTaken: 3,
    totalScore: 138,
    totalMax: 150,
    averageGrade: 92.0,
    passingScore: 75,
    passed: true,
    lastActivity: 'Nov 11, 2025',
    quizzes: [
      { id: 116, quizTitle: 'Canonical Philippine Regional Literature', lessonTitle: 'Luzon, Visayas, and Mindanao Heritage', score: 44, maxScore: 50, percentage: 88.0, passingScore: 75, passed: true, dateTaken: 'Sep 12, 2025', attemptNumber: 1 },
      { id: 117, quizTitle: 'Speculative Fiction & Digital Genres', lessonTitle: 'Hyperpoetry and Graphic Novels', score: 46, maxScore: 50, percentage: 92.0, passingScore: 75, passed: true, dateTaken: 'Oct 15, 2025', attemptNumber: 1 },
      { id: 118, quizTitle: 'World Literature & Postcolonial Themes', lessonTitle: 'Contemporary Global Authors', score: 48, maxScore: 50, percentage: 96.0, passingScore: 75, passed: true, dateTaken: 'Nov 11, 2025', attemptNumber: 1 },
    ],
  },
  {
    id: 's1-pe-1',
    code: 'PEH-11',
    title: 'Physical Education and Health 1',
    category: 'core',
    categoryLabel: 'Core Subject',
    instructor: 'Coach Danilo Ramos',
    instructorTitle: 'Sports Coordinator & Faculty',
    semester: 1,
    q1: 95,
    q2: 97,
    semFinal: 96,
    actionTaken: 'Passed',
    remarks: 'High fitness assessment performance and active leadership in drills.',
    quizzesTaken: 3,
    totalScore: 144,
    totalMax: 150,
    averageGrade: 96.0,
    passingScore: 75,
    passed: true,
    lastActivity: 'Nov 08, 2025',
    quizzes: [
      { id: 119, quizTitle: 'Health-Related Fitness & Physiological Baseline', lessonTitle: 'Fitness Testing & Nutrition', score: 48, maxScore: 50, percentage: 96.0, passingScore: 75, passed: true, dateTaken: 'Sep 02, 2025', attemptNumber: 1 },
      { id: 120, quizTitle: 'Aerobic Exercise & Target Heart Rate', lessonTitle: 'Aerobic and Muscle-Bone Exercises', score: 47, maxScore: 50, percentage: 94.0, passingScore: 75, passed: true, dateTaken: 'Oct 04, 2025', attemptNumber: 1 },
      { id: 121, quizTitle: 'Injury Prevention & Athletic Training', lessonTitle: 'First Aid and Safety in Sports', score: 49, maxScore: 50, percentage: 98.0, passingScore: 75, passed: true, dateTaken: 'Nov 08, 2025', attemptNumber: 1 },
    ],
  },
  {
    id: 's1-emp-tech',
    code: 'EMP-TECH-11',
    title: 'Empowerment Technologies: ICT for STEM',
    category: 'applied',
    categoryLabel: 'Applied Track Subject',
    instructor: 'Engr. Paulo Santos',
    instructorTitle: 'ICT Laboratory Coordinator & Instructor',
    semester: 1,
    q1: 95,
    q2: 96,
    semFinal: 95.5,
    actionTaken: 'Passed',
    remarks: 'Outstanding software prototyping, web semantics, and cybersecurity posture.',
    quizzesTaken: 4,
    totalScore: 191,
    totalMax: 200,
    averageGrade: 95.5,
    passingScore: 75,
    passed: true,
    lastActivity: 'Nov 16, 2025',
    quizzes: [
      { id: 122, quizTitle: 'Current State of ICT & Web 3.0 Trends', lessonTitle: 'Introduction to ICT Contexts', score: 48, maxScore: 50, percentage: 96.0, passingScore: 75, passed: true, dateTaken: 'Sep 01, 2025', attemptNumber: 1 },
      { id: 123, quizTitle: 'Advanced Productivity Software Techniques', lessonTitle: 'Mail Merge and Complex Spreadsheets', score: 47, maxScore: 50, percentage: 94.0, passingScore: 75, passed: true, dateTaken: 'Sep 29, 2025', attemptNumber: 1 },
      { id: 124, quizTitle: 'Web Page Creation & Visual Design Principles', lessonTitle: 'UI Architecture and Prototyping', score: 48, maxScore: 50, percentage: 96.0, passingScore: 75, passed: true, dateTaken: 'Oct 26, 2025', attemptNumber: 1 },
      { id: 125, quizTitle: 'Collaborative Online Tools & ICT Social Impact', lessonTitle: 'ICT for Social Change & Advocacy', score: 48, maxScore: 50, percentage: 96.0, passingScore: 75, passed: true, dateTaken: 'Nov 16, 2025', attemptNumber: 1 },
    ],
  },
  {
    id: 's1-eapp',
    code: 'EAPP-11',
    title: 'English for Academic and Professional Purposes',
    category: 'applied',
    categoryLabel: 'Applied Track Subject',
    instructor: 'Prof. Maria Santos, PhD',
    instructorTitle: 'Master Teacher II · English Dept',
    semester: 1,
    q1: 92,
    q2: 94,
    semFinal: 93,
    actionTaken: 'Passed',
    remarks: 'Superb academic synthesis, critical review, and formal abstract drafting.',
    quizzesTaken: 3,
    totalScore: 140,
    totalMax: 150,
    averageGrade: 93.3,
    passingScore: 75,
    passed: true,
    lastActivity: 'Nov 13, 2025',
    quizzes: [
      { id: 126, quizTitle: 'Text Structures & Academic Language Features', lessonTitle: 'Reading Academic Texts', score: 46, maxScore: 50, percentage: 92.0, passingScore: 75, passed: true, dateTaken: 'Sep 08, 2025', attemptNumber: 1 },
      { id: 127, quizTitle: 'Critical Writing & Review Methodologies', lessonTitle: 'Writing the Reaction Paper & Review', score: 47, maxScore: 50, percentage: 94.0, passingScore: 75, passed: true, dateTaken: 'Oct 12, 2025', attemptNumber: 1 },
      { id: 128, quizTitle: 'Concept Paper Formulation & Defense', lessonTitle: 'Writing the Concept Paper', score: 47, maxScore: 50, percentage: 94.0, passingScore: 75, passed: true, dateTaken: 'Nov 13, 2025', attemptNumber: 1 },
    ],
  },
  {
    id: 's1-pre-calc',
    code: 'CALC-11',
    title: 'Pre-Calculus',
    category: 'specialized',
    categoryLabel: 'Specialized STEM Subject',
    instructor: 'Engr. Ronald Gomez',
    instructorTitle: 'Subject Group Head · Mathematics',
    semester: 1,
    q1: 93,
    q2: 95,
    semFinal: 94,
    actionTaken: 'Passed',
    remarks: 'Mastery in conic sections, trigonometric identities, and polar coordinates.',
    quizzesTaken: 4,
    totalScore: 188,
    totalMax: 200,
    averageGrade: 94.0,
    passingScore: 75,
    passed: true,
    lastActivity: 'Nov 17, 2025',
    quizzes: [
      { id: 129, quizTitle: 'Conic Sections: Circles & Parabolas', lessonTitle: 'Analytic Geometry Fundamentals', score: 47, maxScore: 50, percentage: 94.0, passingScore: 75, passed: true, dateTaken: 'Sep 03, 2025', attemptNumber: 1 },
      { id: 130, quizTitle: 'Ellipses, Hyperbolas, & Degenerate Cases', lessonTitle: 'Advanced Conic Equations', score: 46, maxScore: 50, percentage: 92.0, passingScore: 75, passed: true, dateTaken: 'Oct 01, 2025', attemptNumber: 1 },
      { id: 131, quizTitle: 'Unit Circle & Trigonometric Identities', lessonTitle: 'Trigonometric Functions & Proofs', score: 47, maxScore: 50, percentage: 94.0, passingScore: 75, passed: true, dateTaken: 'Oct 28, 2025', attemptNumber: 1 },
      { id: 132, quizTitle: 'Mathematical Induction & Polar Systems', lessonTitle: 'Series, Induction, and Binomial Theorem', score: 48, maxScore: 50, percentage: 96.0, passingScore: 75, passed: true, dateTaken: 'Nov 17, 2025', attemptNumber: 1 },
    ],
  },
  {
    id: 's1-bio-1',
    code: 'BIO-11',
    title: 'General Biology 1',
    category: 'specialized',
    categoryLabel: 'Specialized STEM Subject',
    instructor: 'Dr. Maria Hernandez',
    instructorTitle: 'Special Science Teacher III · Science Dept',
    semester: 1,
    q1: 90,
    q2: 92,
    semFinal: 91,
    actionTaken: 'Passed',
    remarks: 'Rigorous laboratory technique in microscopy and bioenergetic enzymatic cycles.',
    quizzesTaken: 3,
    totalScore: 137,
    totalMax: 150,
    averageGrade: 91.3,
    passingScore: 75,
    passed: true,
    lastActivity: 'Nov 14, 2025',
    quizzes: [
      { id: 133, quizTitle: 'Cell Biology & Membrane Transport', lessonTitle: 'Prokaryotic vs Eukaryotic Cells', score: 45, maxScore: 50, percentage: 90.0, passingScore: 75, passed: true, dateTaken: 'Sep 09, 2025', attemptNumber: 1 },
      { id: 134, quizTitle: 'Biological Macromolecules & Enzymes', lessonTitle: 'Proteins, Lipids, and Carbohydrates', score: 46, maxScore: 50, percentage: 92.0, passingScore: 75, passed: true, dateTaken: 'Oct 10, 2025', attemptNumber: 1 },
      { id: 135, quizTitle: 'Cellular Respiration & Photosynthesis', lessonTitle: 'ATP Synthesis and Glycolysis', score: 46, maxScore: 50, percentage: 92.0, passingScore: 75, passed: true, dateTaken: 'Nov 14, 2025', attemptNumber: 1 },
    ],
  },
];

export const SECOND_SEMESTER_GRADES: SubjectGradeRecord[] = [
  {
    id: 's2-reading-writing',
    code: 'ENG-12',
    title: 'Reading and Writing Skills',
    category: 'core',
    categoryLabel: 'Core Subject',
    instructor: 'Prof. Maria Santos, PhD',
    instructorTitle: 'Master Teacher II · English Dept',
    semester: 2,
    q3: 92,
    q4: 94,
    semFinal: 93,
    actionTaken: 'Passed',
    remarks: 'Well-structured discursive essays with strong thesis synthesis.',
    quizzesTaken: 3,
    totalScore: 139,
    totalMax: 150,
    averageGrade: 92.7,
    passingScore: 75,
    passed: true,
    lastActivity: 'Nov 09, 2026',
    quarterDetails: {
      q3: {
        writtenWorks: { score: 92, maxScore: 100, percentage: 92, weight: 0.25, weightedScore: 23.0 },
        performanceTasks: { score: 93, maxScore: 100, percentage: 93, weight: 0.50, weightedScore: 46.5 },
        quarterlyExam: { score: 91, maxScore: 100, percentage: 91, weight: 0.25, weightedScore: 22.75 },
        initialGrade: 92.25,
        transmutedGrade: 92,
      },
      q4: {
        writtenWorks: { score: 94, maxScore: 100, percentage: 94, weight: 0.25, weightedScore: 23.5 },
        performanceTasks: { score: 95, maxScore: 100, percentage: 95, weight: 0.50, weightedScore: 47.5 },
        quarterlyExam: { score: 93, maxScore: 100, percentage: 93, weight: 0.25, weightedScore: 23.25 },
        initialGrade: 94.25,
        transmutedGrade: 94,
      },
    },
    quizzes: [
      { id: 201, quizTitle: 'Patterns of Paragraph Development', lessonTitle: 'Exemplification and Cause-Effect', score: 46, maxScore: 50, percentage: 92.0, passingScore: 75, passed: true, dateTaken: 'Feb 12, 2026', attemptNumber: 1 },
      { id: 202, quizTitle: 'Properties of a Well-Written Text', lessonTitle: 'Cohesion, Coherence, & Mechanics', score: 46, maxScore: 50, percentage: 92.0, passingScore: 75, passed: true, dateTaken: 'Mar 18, 2026', attemptNumber: 1 },
      { id: 203, quizTitle: 'Critical Reading as Reasoning & Intertextuality', lessonTitle: 'Hypertext and Intertext Analysis', score: 47, maxScore: 50, percentage: 94.0, passingScore: 75, passed: true, dateTaken: 'Apr 22, 2026', attemptNumber: 1 },
    ],
  },
  {
    id: 's2-stat-prob',
    code: 'MATH-12',
    title: 'Statistics and Probability',
    category: 'core',
    categoryLabel: 'Core Subject',
    instructor: 'Engr. Ronald Gomez',
    instructorTitle: 'Subject Group Head · Mathematics',
    semester: 2,
    q3: 91,
    q4: 93,
    semFinal: 92,
    actionTaken: 'Passed',
    remarks: 'Accurate hypothesis testing and normal distribution modeling.',
    quizzesTaken: 3,
    totalScore: 138,
    totalMax: 150,
    averageGrade: 92.0,
    passingScore: 75,
    passed: true,
    lastActivity: 'Nov 07, 2026',
    quizzes: [
      { id: 204, quizTitle: 'Random Variables & Probability Distributions', lessonTitle: 'Discrete & Continuous Distributions', score: 45, maxScore: 50, percentage: 90.0, passingScore: 75, passed: true, dateTaken: 'Feb 15, 2026', attemptNumber: 1 },
      { id: 205, quizTitle: 'The Normal Curve & Central Limit Theorem', lessonTitle: 'Standard Z-Scores & Sampling', score: 46, maxScore: 50, percentage: 92.0, passingScore: 75, passed: true, dateTaken: 'Mar 20, 2026', attemptNumber: 1 },
      { id: 206, quizTitle: 'Confidence Intervals & Z/T-Test Hypothesis', lessonTitle: 'Testing Population Means', score: 47, maxScore: 50, percentage: 94.0, passingScore: 75, passed: true, dateTaken: 'Apr 25, 2026', attemptNumber: 1 },
    ],
  },
  {
    id: 's2-drrr',
    code: 'SCI-12',
    title: 'Disaster Readiness and Risk Reduction (DRRR)',
    category: 'core',
    categoryLabel: 'Core Subject',
    instructor: 'Dr. Maria Hernandez',
    instructorTitle: 'Special Science Teacher III · Science Dept',
    semester: 2,
    q3: 94,
    q4: 96,
    semFinal: 95,
    actionTaken: 'Passed',
    remarks: 'Comprehensive hazard vulnerability mapping and mitigation plans.',
    quizzesTaken: 3,
    totalScore: 142,
    totalMax: 150,
    averageGrade: 94.7,
    passingScore: 75,
    passed: true,
    lastActivity: 'Nov 11, 2026',
    quizzes: [
      { id: 207, quizTitle: 'Basic Concepts of Hazard, Risk, & Vulnerability', lessonTitle: 'Disaster Risk Reduction Framework', score: 47, maxScore: 50, percentage: 94.0, passingScore: 75, passed: true, dateTaken: 'Feb 20, 2026', attemptNumber: 1 },
      { id: 208, quizTitle: 'Earthquake, Tsunami, & Volcanic Hazards', lessonTitle: 'Geological Hazards & Mitigation', score: 47, maxScore: 50, percentage: 94.0, passingScore: 75, passed: true, dateTaken: 'Mar 24, 2026', attemptNumber: 1 },
      { id: 209, quizTitle: 'Hydrometeorological & Fire Safety Hazards', lessonTitle: 'Typhoons, Floods, and Incident Command', score: 48, maxScore: 50, percentage: 96.0, passingScore: 75, passed: true, dateTaken: 'Apr 28, 2026', attemptNumber: 1 },
    ],
  },
  {
    id: 's2-fil-pagbasa',
    code: 'FIL-12',
    title: "Pagbasa at Pagsusuri ng Iba't Ibang Teksto Tungo sa Pananaliksik",
    category: 'core',
    categoryLabel: 'Core Subject',
    instructor: 'Gng. Remedios Alcantara',
    instructorTitle: 'Dalubguro II · Kagawaran ng Filipino',
    semester: 2,
    q3: 89,
    q4: 91,
    semFinal: 90,
    actionTaken: 'Passed',
    remarks: 'Mabisang pagsusuri ng tekstong impormatibo, deskriptibo, at persuweysib.',
    quizzesTaken: 3,
    totalScore: 135,
    totalMax: 150,
    averageGrade: 90.0,
    passingScore: 75,
    passed: true,
    lastActivity: 'Nov 05, 2026',
    quizzes: [
      { id: 210, quizTitle: 'Tekstong Impormatibo, Deskriptibo, at Naratibo', lessonTitle: 'Mga Uri ng Teksto', score: 44, maxScore: 50, percentage: 88.0, passingScore: 75, passed: true, dateTaken: 'Feb 18, 2026', attemptNumber: 1 },
      { id: 211, quizTitle: 'Tekstong Argumentatibo at Prosidyural', lessonTitle: 'Lohikal na Pangangatwiran', score: 45, maxScore: 50, percentage: 90.0, passingScore: 75, passed: true, dateTaken: 'Mar 22, 2026', attemptNumber: 1 },
      { id: 212, quizTitle: 'Hakbang sa Pagbuo ng Panimulang Pananaliksik', lessonTitle: 'Etika sa Pananaliksik at Plagyarismo', score: 46, maxScore: 50, percentage: 92.0, passingScore: 75, passed: true, dateTaken: 'Apr 26, 2026', attemptNumber: 1 },
    ],
  },
  {
    id: 's2-pe-2',
    code: 'PEH-12',
    title: 'Physical Education and Health 2',
    category: 'core',
    categoryLabel: 'Core Subject',
    instructor: 'Coach Danilo Ramos',
    instructorTitle: 'Sports Coordinator & Faculty',
    semester: 2,
    q3: 96,
    q4: 98,
    semFinal: 97,
    actionTaken: 'Passed',
    remarks: 'Exemplary badminton and table tennis sportsmanship and stamina.',
    quizzesTaken: 3,
    totalScore: 145,
    totalMax: 150,
    averageGrade: 96.7,
    passingScore: 75,
    passed: true,
    lastActivity: 'Nov 02, 2026',
    quizzes: [
      { id: 213, quizTitle: 'Individual & Dual Sports Mechanics (Badminton)', lessonTitle: 'Racket Skills and Court Footwork', score: 48, maxScore: 50, percentage: 96.0, passingScore: 75, passed: true, dateTaken: 'Feb 14, 2026', attemptNumber: 1 },
      { id: 214, quizTitle: 'Table Tennis Rules & Offensive Strategies', lessonTitle: 'Spin Mechanics & Officiating', score: 48, maxScore: 50, percentage: 96.0, passingScore: 75, passed: true, dateTaken: 'Mar 15, 2026', attemptNumber: 1 },
      { id: 215, quizTitle: 'Nutritional Guidelines for High-Intensity Sports', lessonTitle: 'Energy Systems and Hydration', score: 49, maxScore: 50, percentage: 98.0, passingScore: 75, passed: true, dateTaken: 'Apr 20, 2026', attemptNumber: 1 },
    ],
  },
  {
    id: 's2-prac-research',
    code: 'RES-11',
    title: 'Practical Research 1 (Qualitative Research)',
    category: 'applied',
    categoryLabel: 'Applied Track Subject',
    instructor: 'Dr. Maria Hernandez',
    instructorTitle: 'Special Science Teacher III · Science Dept',
    semester: 2,
    q3: 94,
    q4: 95,
    semFinal: 94.5,
    actionTaken: 'Passed',
    remarks: 'Exemplary phenomenological research design and qualitative coding.',
    quizzesTaken: 3,
    totalScore: 142,
    totalMax: 150,
    averageGrade: 94.7,
    passingScore: 75,
    passed: true,
    lastActivity: 'Nov 12, 2026',
    quizzes: [
      { id: 216, quizTitle: 'Nature of Qualitative Research & Inquiries', lessonTitle: 'Research Paradigms and Design', score: 47, maxScore: 50, percentage: 94.0, passingScore: 75, passed: true, dateTaken: 'Feb 26, 2026', attemptNumber: 1 },
      { id: 217, quizTitle: 'Literature Review Synthesizing & APA 7th Edition', lessonTitle: 'Thematic Literature Synthesis', score: 47, maxScore: 50, percentage: 94.0, passingScore: 75, passed: true, dateTaken: 'Mar 28, 2026', attemptNumber: 1 },
      { id: 218, quizTitle: 'Qualitative Data Collection & Thematic Analysis', lessonTitle: 'Semi-Structured Interviews & Coding', score: 48, maxScore: 50, percentage: 96.0, passingScore: 75, passed: true, dateTaken: 'May 02, 2026', attemptNumber: 1 },
    ],
  },
  {
    id: 's2-basic-calc',
    code: 'CALC-12',
    title: 'Basic Calculus',
    category: 'specialized',
    categoryLabel: 'Specialized STEM Subject',
    instructor: 'Engr. Ronald Gomez',
    instructorTitle: 'Subject Group Head · Mathematics',
    semester: 2,
    q3: 92,
    q4: 94,
    semFinal: 93,
    actionTaken: 'Passed',
    remarks: 'Strong computational skill in limits, continuity, and derivatives.',
    quizzesTaken: 4,
    totalScore: 186,
    totalMax: 200,
    averageGrade: 93.0,
    passingScore: 75,
    passed: true,
    lastActivity: 'Nov 10, 2026',
    quizzes: [
      { id: 219, quizTitle: 'Limits of Functions & Limit Theorems', lessonTitle: 'Evaluating Limits Analytically', score: 46, maxScore: 50, percentage: 92.0, passingScore: 75, passed: true, dateTaken: 'Feb 22, 2026', attemptNumber: 1 },
      { id: 220, quizTitle: 'Continuity, Intermediate Value Theorem, & Infinity', lessonTitle: 'Continuity over Intervals', score: 46, maxScore: 50, percentage: 92.0, passingScore: 75, passed: true, dateTaken: 'Mar 25, 2026', attemptNumber: 1 },
      { id: 221, quizTitle: 'The Derivative as Tangent Slope & Power Rule', lessonTitle: 'Differentiation Formulas', score: 47, maxScore: 50, percentage: 94.0, passingScore: 75, passed: true, dateTaken: 'Apr 24, 2026', attemptNumber: 1 },
      { id: 222, quizTitle: 'Product, Quotient, Chain Rule, & Optimization', lessonTitle: 'Applications of Derivatives', score: 47, maxScore: 50, percentage: 94.0, passingScore: 75, passed: true, dateTaken: 'May 05, 2026', attemptNumber: 1 },
    ],
  },
  {
    id: 's2-bio-2',
    code: 'BIO-12',
    title: 'General Biology 2',
    category: 'specialized',
    categoryLabel: 'Specialized STEM Subject',
    instructor: 'Dr. Maria Hernandez',
    instructorTitle: 'Special Science Teacher III · Science Dept',
    semester: 2,
    q3: 91,
    q4: 93,
    semFinal: 92,
    actionTaken: 'Passed',
    remarks: 'Clear understanding of organismal anatomy, plant physiology, and taxonomy.',
    quizzesTaken: 3,
    totalScore: 138,
    totalMax: 150,
    averageGrade: 92.0,
    passingScore: 75,
    passed: true,
    lastActivity: 'Nov 08, 2026',
    quizzes: [
      { id: 223, quizTitle: 'Plant and Animal Organ Systems Comparison', lessonTitle: 'Form and Function in Living Systems', score: 46, maxScore: 50, percentage: 92.0, passingScore: 75, passed: true, dateTaken: 'Mar 02, 2026', attemptNumber: 1 },
      { id: 224, quizTitle: 'Immune, Nervous, & Endocrine Feedback Loops', lessonTitle: 'Homeostasis and Regulation', score: 46, maxScore: 50, percentage: 92.0, passingScore: 75, passed: true, dateTaken: 'Apr 04, 2026', attemptNumber: 1 },
      { id: 225, quizTitle: 'Phylogeny, Cladistics, & Biodiversity Conservation', lessonTitle: 'Systematics and Evolutionary Trees', score: 46, maxScore: 50, percentage: 92.0, passingScore: 75, passed: true, dateTaken: 'May 08, 2026', attemptNumber: 1 },
    ],
  },
  {
    id: 's2-chem-1',
    code: 'CHEM-11',
    title: 'General Chemistry 1',
    category: 'specialized',
    categoryLabel: 'Specialized STEM Subject',
    instructor: 'Engr. Paulo Santos',
    instructorTitle: 'Faculty · Physical Sciences',
    semester: 2,
    q3: 85,
    q4: 87,
    semFinal: 86,
    actionTaken: 'Passed',
    remarks: 'Good grasp of stoichiometry; recommend extra rehearsal in Lewis structures.',
    quizzesTaken: 3,
    totalScore: 129,
    totalMax: 150,
    averageGrade: 86.0,
    passingScore: 75,
    passed: true,
    lastActivity: 'Nov 04, 2026',
    quarterDetails: {
      q3: {
        writtenWorks: { score: 84, maxScore: 100, percentage: 84, weight: 0.25, weightedScore: 21.0 },
        performanceTasks: { score: 86, maxScore: 100, percentage: 86, weight: 0.50, weightedScore: 43.0 },
        quarterlyExam: { score: 84, maxScore: 100, percentage: 84, weight: 0.25, weightedScore: 21.0 },
        initialGrade: 85.0,
        transmutedGrade: 85,
      },
      q4: {
        writtenWorks: { score: 86, maxScore: 100, percentage: 86, weight: 0.25, weightedScore: 21.5 },
        performanceTasks: { score: 88, maxScore: 100, percentage: 88, weight: 0.50, weightedScore: 44.0 },
        quarterlyExam: { score: 86, maxScore: 100, percentage: 86, weight: 0.25, weightedScore: 21.5 },
        initialGrade: 87.0,
        transmutedGrade: 87,
      },
    },
    quizzes: [
      { id: 226, quizTitle: 'Matter, Particulate Nature, & Stoichiometry', lessonTitle: 'Atomic Theory and Mole Concept', score: 42, maxScore: 50, percentage: 84.0, passingScore: 75, passed: true, dateTaken: 'Mar 05, 2026', attemptNumber: 1 },
      { id: 227, quizTitle: 'Electronic Structure of Atoms & Periodic Trends', lessonTitle: 'Quantum Numbers and Orbitals', score: 43, maxScore: 50, percentage: 86.0, passingScore: 75, passed: true, dateTaken: 'Apr 08, 2026', attemptNumber: 1 },
      { id: 228, quizTitle: 'Chemical Bonding: Ionic, Covalent, & Metallic', lessonTitle: 'Lewis Structures and Molecular Geometry', score: 44, maxScore: 50, percentage: 88.0, passingScore: 75, passed: true, dateTaken: 'May 06, 2026', attemptNumber: 1 },
    ],
  },
];

export const OBSERVED_CORE_VALUES: ObservedCoreValue[] = [
  {
    coreValue: 'Maka-Diyos',
    behaviorStatements: [
      "Expresses one's spiritual beliefs while respecting the spiritual beliefs of others",
      'Shows adhering to truth, honesty, and intellectual integrity in school tasks',
    ],
    q1: 'AO',
    q2: 'AO',
    q3: 'AO',
    q4: 'AO',
  },
  {
    coreValue: 'Makatao',
    behaviorStatements: [
      'Is sensitive to individual, social, and cultural differences among peers',
      'Demonstrates contributions toward solidarity and teamwork in group projects',
    ],
    q1: 'AO',
    q2: 'AO',
    q3: 'AO',
    q4: 'AO',
  },
  {
    coreValue: 'Makakalikasan',
    behaviorStatements: [
      'Cares for the environment and utilizes school resources wisely, judiciously, and economically',
    ],
    q1: 'AO',
    q2: 'AO',
    q3: 'AO',
    q4: 'AO',
  },
  {
    coreValue: 'Makabansa',
    behaviorStatements: [
      'Demonstrates pride in being a Filipino and exercises the rights and duties of a responsible citizen',
      'Demonstrates appropriate behavior in carrying out school duties and community advocacies',
    ],
    q1: 'AO',
    q2: 'AO',
    q3: 'AO',
    q4: 'AO',
  },
];

export const MONTHLY_ATTENDANCE_RECORD: MonthlyAttendance[] = [
  { month: 'Aug', daysOfSchool: 21, daysPresent: 21, daysAbsent: 0 },
  { month: 'Sep', daysOfSchool: 22, daysPresent: 22, daysAbsent: 0 },
  { month: 'Oct', daysOfSchool: 21, daysPresent: 20, daysAbsent: 1 },
  { month: 'Nov', daysOfSchool: 20, daysPresent: 20, daysAbsent: 0 },
  { month: 'Dec', daysOfSchool: 16, daysPresent: 16, daysAbsent: 0 },
  { month: 'Jan', daysOfSchool: 21, daysPresent: 21, daysAbsent: 0 },
  { month: 'Feb', daysOfSchool: 20, daysPresent: 20, daysAbsent: 0 },
  { month: 'Mar', daysOfSchool: 22, daysPresent: 22, daysAbsent: 0 },
  { month: 'Apr', daysOfSchool: 21, daysPresent: 20, daysAbsent: 1 },
  { month: 'May', daysOfSchool: 20, daysPresent: 20, daysAbsent: 0 },
];
