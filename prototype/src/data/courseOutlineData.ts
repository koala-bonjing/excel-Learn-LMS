export type CourseItemType =
  | 'announcement'
  | 'ppt'
  | 'pdf'
  | 'link'
  | 'yt'
  | 'quiz'
  | 'submission';

export interface QuizQuestion {
  id: string;
  question: string;
  options: string[];
  correctIndex: number;
  explanation: string;
}

export interface SlideItem {
  slideNumber: number;
  title: string;
  bullets: string[];
  notes?: string;
  badge?: string;
}

export interface CourseOutlineItem {
  id: string;
  sectionId: string;
  type: CourseItemType;
  title: string;
  subtitle?: string;
  badge?: string;
  initialCompleted?: boolean;
  completionType?: 'manual' | 'automatic';
  isIndented?: boolean;
  // Specific payload for interactive viewers
  slides?: SlideItem[];
  youtubeId?: string;
  youtubeUrl?: string;
  videoDuration?: string;
  videoNotes?: string[];
  pdfPages?: number;
  pdfSummary?: string;
  pdfExcerpt?: string[];
  linkUrl?: string;
  readTime?: string;
  author?: string;
  articleContent?: string[];
  quizQuestions?: QuizQuestion[];
  reflectionPrompt?: string;
  maxPoints?: number;
  submissionDeadline?: string;
  rubricCriteria?: { criteria: string; points: number }[];
}

export interface CourseOutlineSection {
  id: string;
  title: string;
  itemCountDescription?: string;
  items: CourseOutlineItem[];
}

export interface CourseOutlineData {
  courseId: string;
  courseCode: string;
  courseTitle: string;
  fullTitle: string;
  academicYear: string;
  term: string;
  instructor: string;
  sections: CourseOutlineSection[];
}

export const SOCIAL_ISSUES_COURSE_DATA: CourseOutlineData = {
  courseId: 'c-social-issues',
  courseCode: 'SIPP-301',
  courseTitle: 'Social Issues and Professional Practice',
  fullTitle: 'Social Issues and Professional Practice (AY 2024-25 BSCS 3)',
  academicYear: 'AY 2024-25',
  term: 'BSCS 3 · 1st Semester',
  instructor: 'Prof. Dennis Morales, MSCS, PECE',
  sections: [
    {
      id: 'sec-general',
      title: 'General',
      itemCountDescription: 'Announcements & syllabus essentials',
      items: [
        {
          id: 'item-gen-announcements',
          sectionId: 'sec-general',
          type: 'announcement',
          title: 'Announcements',
          subtitle: 'Official course bulletins, schedule adjustments, and consultation hours.',
          initialCompleted: true,
          completionType: 'automatic',
          articleContent: [
            'Welcome to Social Issues and Professional Practice (AY 2024-25 BSCS 3)!',
            'Synchronous class sessions are held every Tuesday and Thursday at 10:00 AM. Please make sure to review the weekly lecture slides and complete all modular readings prior to joining.',
            'Midterm group presentations will run during Weeks 6–7. Check the GROUPS section for topic assignments and the peer evaluation rubric.',
          ],
        },
        {
          id: 'item-gen-syllabus',
          sectionId: 'sec-general',
          type: 'pdf',
          title: 'COURSE SYLLABUS & CURRICULUM GUIDE (AY 2024-25)',
          subtitle: 'CHED & DepEd accredited course syllabus outlining learning outcomes, competencies, and grading weights.',
          badge: 'PDF · 12 Pages',
          initialCompleted: true,
          completionType: 'automatic',
          pdfPages: 12,
          pdfSummary: 'Comprehensive curriculum guide covering professional ethics, intellectual property rights, data privacy regulations (RA 10173), and societal accountability in computing.',
          pdfExcerpt: [
            'Course Description: This course deals with the social, legal, and ethical issues involved in the computing profession.',
            'Module 1: Foundations of Ethical Theories and Professionalism.',
            'Module 2: Professional Bodies, Codes of Conduct (ACM, IEEE-CS, BCS).',
            'Module 3: Privacy, Data Protection, and Algorithmic Accountability.',
            'Module 4: Intellectual Property, Free and Open Source Software, and Patent Law.',
          ],
        },
      ],
    },
    {
      id: 'sec-week-1-2',
      title: 'WEEK 1 - 2 | THE NATURE OF A PROFESSION',
      itemCountDescription: '3 core learning materials · 1 video · 1 assessment',
      items: [
        {
          id: 'item-sapi-wk1-l1',
          sectionId: 'sec-week-1-2',
          type: 'ppt',
          title: 'SAPI - WK 1 L1',
          subtitle: 'Interactive lecture slides: Defining a profession, specialized mastery, and public duty.',
          badge: 'PPT / H5P · 5 Slides',
          initialCompleted: false,
          completionType: 'automatic',
          slides: [
            {
              slideNumber: 1,
              title: 'The Nature of a Profession: Foundations & Criteria',
              bullets: [
                'Historical evolution: From trade guilds to modern chartered professions.',
                'The triad of professionalism: Specialized mastery, fiduciary duty, and societal trust.',
                'Why computing professionals hold unprecedented ethical leverage in 2026.',
              ],
              notes: 'Emphasize that software now controls safety-critical infrastructure: avionics, medical implants, and financial rails.',
              badge: 'Foundations',
            },
            {
              slideNumber: 2,
              title: 'What Constitutes a Profession?',
              bullets: [
                '1. Systematic and specialized body of knowledge acquired through rigorous study.',
                '2. Formal certification, licensure, or accreditation by peer bodies.',
                '3. Enforceable codes of ethics with disciplinary sanctions.',
                '4. Primacy of public welfare over immediate commercial profit.',
              ],
              notes: 'Contrast standard wage employment with professional obligations to the public good.',
              badge: 'Core Criteria',
            },
            {
              slideNumber: 3,
              title: 'Professional Autonomy vs Corporate Hierarchy',
              bullets: [
                'The engineer’s dilemma: Meeting ship deadlines versus upholding quality safeguards.',
                'Whistleblowing protocols and legal protections under Philippine law.',
                'Fiduciary duty: Protecting non-technical users from covert surveillance and data exploitation.',
              ],
              notes: 'Walk students through a simulated decision matrix where a product manager demands shipping buggy code.',
              badge: 'Autonomy',
            },
            {
              slideNumber: 4,
              title: 'Is Software Engineering a Recognized Profession?',
              bullets: [
                'Arguments in favor: Societal consequence, systemic complexity, and ethical stakes.',
                'Arguments against: Lack of unified state licensure comparable to civil or chemical engineering.',
                'The modern consensus: De facto professional status requiring proactive self-regulation.',
              ],
              notes: 'Reference IEEE-CS and ACM joint task force on Software Engineering Code of Ethics.',
              badge: 'Debate',
            },
            {
              slideNumber: 5,
              title: 'Summary & Provocative Questions',
              bullets: [
                'Does writing code that affects millions require moral accountability?',
                'Who bears liability when an autonomous AI agent produces harmful outcomes?',
                'Next Step: Read the assigned essay and complete the Week 1–2 Ethics Assessment.',
              ],
              notes: 'Direct students to the reading link and the static quiz runner below.',
              badge: 'Conclusion',
            },
          ],
        },
        {
          id: 'item-week-1-lesson-1-pdf',
          sectionId: 'sec-week-1-2',
          type: 'pdf',
          title: 'WEEK 1 LESSON 1',
          subtitle: 'This resource is a pdf version of the previous presentation. More explanations are provided in this copy.',
          badge: 'PDF · 8 Pages',
          initialCompleted: false,
          completionType: 'automatic',
          pdfPages: 8,
          pdfSummary: 'In-depth textual companion to Lecture 1. Explores Kantian deontology, utilitarian ethics, and virtue ethics as applied to modern computer system architecture.',
          pdfExcerpt: [
            'Section 1.1: Deontological Ethics in System Design: The duty to respect user agency.',
            'Section 1.2: Utilitarian Cost-Benefit Calculus: Why minimizing average latency does not justify sacrificing fringe privacy.',
            'Section 1.3: Institutional Accountability: How software failures differ epistemically from mechanical failures.',
            'Section 1.4: Philippine Legal Context: Republic Act 10175 (Cybercrime Prevention Act) and Republic Act 10173 (Data Privacy Act of 2012).',
          ],
        },
        {
          id: 'item-essay-good-professional',
          sectionId: 'sec-week-1-2',
          type: 'link',
          title: 'READ: (ESSAY) BEING A GOOD COMPUTER PROFESSIONAL',
          subtitle: 'Core ethical duties, moral character, and technical craftsmanship in the age of algorithmic dominance.',
          badge: 'Web Reading · 6 min read',
          readTime: '6 min read',
          author: 'Dr. Michael S. Davis, Center for the Study of Ethics in the Professions',
          initialCompleted: false,
          completionType: 'manual',
          articleContent: [
            'To be a good computer professional is not simply to be technically proficient at writing clean, performant algorithms. Technical competence is merely the price of admission; moral virtue and social responsibility constitute the substance of the profession.',
            'When a bridge collapses, the civil engineer faces public scrutiny, board review, and legal accountability. When an algorithm discriminates against loan applicants or a healthcare database leaks sensitive medical diagnostics, the software developer too often retreats behind corporate opacity.',
            'A true professional takes pride in what they build, but takes equal responsibility for the foreseeable consequences of what they release into the wild. True professionalism means saying "No" to architectures that exploit user vulnerability, even when instructed otherwise by management.',
            'Key Takeaway: The hallmark of a computer professional is the unwavering dedication to human dignity, safety, and transparency in every line of code deployed.',
          ],
        },
        {
          id: 'item-video-intro-ethics',
          sectionId: 'sec-week-1-2',
          type: 'yt',
          title: 'VIDEO: The Social Impact of Computing & Professional Ethics',
          subtitle: 'Educational breakdown on algorithmic accountability, societal stakes, and the engineer’s duty.',
          badge: 'YouTube · 12:45',
          youtubeId: 'j8b_29yS-0o',
          youtubeUrl: 'https://www.youtube.com/watch?v=j8b_29yS-0o',
          videoDuration: '12:45',
          initialCompleted: false,
          completionType: 'automatic',
          videoNotes: [
            '0:00 - Introduction: Why computing ethics is not optional',
            '3:15 - Historical case studies of algorithmic failures',
            '6:40 - The ACM Code of Ethics in practice',
            '9:50 - Personal responsibility in high-velocity tech teams',
            '12:10 - Takeaways for graduating computer science majors',
          ],
        },
        {
          id: 'item-quiz-week-1-2',
          sectionId: 'sec-week-1-2',
          type: 'quiz',
          title: 'ASSESSMENT: Week 1–2 Ethics & Profession Check',
          subtitle: 'Static answerable 3-question evaluation + short reflection on professional computing ethics.',
          badge: 'Interactive Quiz · 30 Points',
          initialCompleted: false,
          completionType: 'automatic',
          quizQuestions: [
            {
              id: 'q1',
              question: 'Which of the following is the defining characteristic that separates a recognized profession from a technical trade?',
              options: [
                'Higher compensation brackets and equity bonuses',
                'A specialized body of knowledge coupled with a binding ethical commitment to public welfare',
                'Possession of a four-year college degree',
                'Working in corporate office facilities without physical labor',
              ],
              correctIndex: 1,
              explanation: 'A profession is defined by specialized mastery and an institutional obligation to serve the public welfare above private profit.',
            },
            {
              id: 'q2',
              question: 'Under the ACM Code of Ethics (Clause 1.2), what is the computing professional’s primary mandate regarding harm?',
              options: [
                'Avoid harm, including unjustified physical, emotional, reputational, or data privacy damage',
                'Shield the employer from civil and criminal liability at all costs',
                'Ensure maximum feature velocity even if test coverage is deficient',
                'Prioritize shareholder returns while keeping code closed-source',
              ],
              correctIndex: 0,
              explanation: 'Clause 1.2 explicitly directs computing professionals to avoid harm and actively mitigate foreseeable risks to individuals and society.',
            },
            {
              id: 'q3',
              question: 'When discovering a critical unpatched zero-day vulnerability in production code that exposes sensitive user data, what is the engineer’s primary ethical duty?',
              options: [
                'Ignore the vulnerability if it is outside their current sprint backlog',
                'Immediately document, report, and escalate the vulnerability through responsible disclosure protocols to protect users',
                'Exploit the vulnerability secretly to demonstrate its severity to management',
                'Wait until paying customers submit bug tickets before notifying supervisors',
              ],
              correctIndex: 1,
              explanation: 'Responsible disclosure and immediate escalation to safeguard user data is the ethical cornerstone of computing practice.',
            },
          ],
          reflectionPrompt: 'Briefly explain: Why must a software engineer prioritize public safety over corporate deadlines when deploying safety-critical systems?',
        },
      ],
    },
    {
      id: 'sec-week-3',
      title: 'WEEK 3 - PROFESSIONAL BODIES IN COMPUTING',
      itemCountDescription: '3 readings · 1 comparative assessment',
      items: [
        {
          id: 'item-read-lesson-2',
          sectionId: 'sec-week-3',
          type: 'pdf',
          title: 'READ: LESSON 2 — Professional Bodies & Global Standards',
          subtitle: 'History, mandate, and organizational structures of IEEE-CS, ACM, and BCS.',
          badge: 'PDF · 10 Pages',
          initialCompleted: false,
          completionType: 'automatic',
          pdfPages: 10,
          pdfSummary: 'Explores the structural differences between national engineering councils (PRC in the Philippines), international societies (IEEE, ACM), and royal chartered bodies (BCS).',
          pdfExcerpt: [
            'Section 2.1: The Association for Computing Machinery (ACM): Founded in 1947, ACM is the world’s largest educational and scientific computing society.',
            'Section 2.2: The IEEE Computer Society: Focus on hardware/software engineering standards and technical publications.',
            'Section 2.3: The British Computer Society (BCS): The Chartered Institute for IT, granting Chartered IT Professional (CITP) status.',
          ],
        },
        {
          id: 'item-read-bcs-conduct',
          sectionId: 'sec-week-3',
          type: 'link',
          title: 'READ: BCS CODE OF CONDUCT',
          subtitle: 'The 4 Pillars of the British Computer Society: Public Interest, Competence, Duty to Authority, and Duty to Profession.',
          badge: 'Charter Standards',
          readTime: '5 min read',
          author: 'BCS, The Chartered Institute for IT',
          initialCompleted: false,
          completionType: 'manual',
          articleContent: [
            '1. The Public Interest: You shall have due regard for public health, privacy, security, and well-being of others and the environment.',
            '2. Professional Competence and Integrity: You shall only undertake work that is within your competence and not misrepresent your qualifications.',
            '3. Duty to Relevant Authority: You shall carry out your professional responsibilities with due care and diligence in accordance with the relevant authority’s wishes.',
            '4. Duty to the Profession: You shall uphold the reputation and good standing of the IT profession and support colleagues in their professional development.',
          ],
        },
        {
          id: 'item-read-acm-conduct',
          sectionId: 'sec-week-3',
          type: 'link',
          title: 'READ: ACM CODE OF CONDUCT',
          subtitle: 'ACM Code of Ethics & Professional Conduct: General ethical principles and professional responsibilities.',
          badge: 'ACM Standard',
          readTime: '7 min read',
          author: 'ACM Ethics Task Force',
          initialCompleted: false,
          completionType: 'manual',
          articleContent: [
            'Preamble: Computing professionals’ actions change the world. To act responsibly, they should reflect upon the wider impacts of their work.',
            '1.1 Contribute to society and human well-being, acknowledging that all people are stakeholders in computing.',
            '1.3 Be honest and trustworthy.',
            '1.4 Be fair and take action not to discriminate.',
            '1.6 Respect privacy: Computing professionals should only use personal information for legitimate ends and without violating the rights of individuals.',
            '2.9 Design and implement systems that are robustly and usably secure.',
          ],
        },
        {
          id: 'item-quiz-bodies',
          sectionId: 'sec-week-3',
          type: 'quiz',
          title: 'ASSESSMENT: Professional Bodies & Code Comparison',
          subtitle: 'Static answerable quiz evaluating key differences between ACM and BCS ethical frameworks.',
          badge: 'Interactive Quiz · 20 Points',
          initialCompleted: false,
          completionType: 'automatic',
          quizQuestions: [
            {
              id: 'qb1',
              question: 'Which professional organization confers the internationally recognized Chartered IT Professional (CITP) title?',
              options: [
                'Association for Computing Machinery (ACM)',
                'British Computer Society (BCS)',
                'IEEE Computer Society',
                'DepEd Curriculum Council',
              ],
              correctIndex: 1,
              explanation: 'The British Computer Society (BCS) is the Royal Chartered Institute for IT and awards the CITP credential.',
            },
            {
              id: 'qb2',
              question: 'What is the highest priority mandated across both the ACM and BCS codes of conduct?',
              options: [
                'Maximizing company revenue and commercial expansion',
                'Protection of the public interest, user privacy, and safety',
                'Adhering strictly to proprietary confidentiality without exception',
                'Publishing peer-reviewed academic papers annually',
              ],
              correctIndex: 1,
              explanation: 'Both codes place the public interest and the protection of individuals above corporate or private allegiances.',
            },
          ],
          reflectionPrompt: 'In 2–3 sentences: Why is voluntary adherence to professional codes of conduct vital for software developers in the absence of government licensure?',
        },
      ],
    },
    {
      id: 'sec-week-4',
      title: 'WEEK 4 - PROFESSIONAL RELATIONSHIPS',
      itemCountDescription: '2 presentations · 1 industry article · 1 video case study',
      items: [
        {
          id: 'item-prof-relationships',
          sectionId: 'sec-week-4',
          type: 'ppt',
          title: 'PROFESSIONAL RELATIONSHIPS',
          subtitle: 'Lecture slides on client-developer contracts, peer mentorship, and non-disclosure obligations.',
          badge: 'PPT · 4 Slides',
          initialCompleted: false,
          completionType: 'automatic',
          slides: [
            {
              slideNumber: 1,
              title: 'Navigating Professional Relationships in Tech',
              bullets: [
                'The Quadrangle of Relations: Employer, Client, Peer Colleagues, and End Users.',
                'Managing conflicts of interest and handling competing loyalties.',
                'The ethics of freelance moonlighting and intellectual property rights.',
              ],
              badge: 'Overview',
            },
            {
              slideNumber: 2,
              title: 'Client and Employer Responsibilities',
              bullets: [
                'Truth in estimating: Avoiding deceptive lowball bidding and unrealistic timelines.',
                'Trade secrets versus general professional skill portability.',
                'The boundary between legitimate work product and open-source contribution.',
              ],
              badge: 'Client Relations',
            },
            {
              slideNumber: 3,
              title: 'Peer Dynamics & Psychological Safety',
              bullets: [
                'Code reviews as mentorship, not punitive gatekeeping.',
                'Constructive feedback in distributed and asynchronous engineering teams.',
                'Mitigating "bro-culture" and fostering inclusive team environments.',
              ],
              badge: 'Peer Relations',
            },
            {
              slideNumber: 4,
              title: 'Case Study: The Unauthorized Client Data Backup',
              bullets: [
                'Scenario: An engineer copies client production databases to their personal laptop for local debugging.',
                'Ethical infractions: Gross breach of privacy, non-compliance with Philippine DPA (RA 10173).',
                'Proper remediation: Secure staging environments and synthetic mock datasets.',
              ],
              badge: 'Case Study',
            },
          ],
        },
        {
          id: 'item-accountability-in-it',
          sectionId: 'sec-week-4',
          type: 'ppt',
          title: 'ACCOUNTABILITY IN IT',
          subtitle: 'Interactive slides on algorithmic liability, safety-critical software, and blameless post-mortems.',
          badge: 'PPT · 4 Slides',
          initialCompleted: false,
          completionType: 'automatic',
          slides: [
            {
              slideNumber: 1,
              title: 'Accountability vs Mere Responsibility',
              bullets: [
                'Responsibility can be shared or delegated; accountability rests with the engineer who commits.',
                'The problem of "Many Hands": Who is at fault when an ensemble system fails?',
                'Epistemic opacity in modern deep learning and neural network classifiers.',
              ],
              badge: 'Concepts',
            },
            {
              slideNumber: 2,
              title: 'Safety-Critical Software Failures',
              bullets: [
                'The Therac-25 radiation disaster: Race conditions and deleted hardware interlocks.',
                'Boeing 737 MAX MCAS software: Single sensor dependency and concealed pilot manuals.',
                'Lessons: Software is never purely virtual; it has tangible physical consequences.',
              ],
              badge: 'Failures',
            },
            {
              slideNumber: 3,
              title: 'Blameless Engineering Post-Mortems',
              bullets: [
                'Why firing individual developers prevents root cause discovery.',
                'Focusing on systemic vulnerabilities: Guardrails, CI/CD gates, and staging tests.',
                'Creating an organizational culture where engineers openly report near-misses.',
              ],
              badge: 'Best Practice',
            },
            {
              slideNumber: 4,
              title: 'Personal Accountability Checklist',
              bullets: [
                'Have I verified inputs against injection attacks?',
                'Is sensitive data encrypted at rest and in transit?',
                'Would I feel comfortable having my implementation audited in open court?',
              ],
              badge: 'Checklist',
            },
          ],
        },
        {
          id: 'item-read-workplace-accountability',
          sectionId: 'sec-week-4',
          type: 'link',
          title: 'READ: How to make accountability a core part of your workplace culture',
          subtitle: 'Harvard Business Review & Tech Leadership insights on transparent engineering accountability.',
          badge: 'Leadership Article',
          readTime: '5 min read',
          author: 'Sarah K. Jenkins, Tech Leadership Review',
          initialCompleted: false,
          completionType: 'manual',
          isIndented: true,
          articleContent: [
            'Accountability in software teams is often misunderstood as a synonym for punishment. When a production outage occurs, toxic organizations scramble to find a scapegoat. High-performing organizations do the exact opposite.',
            'True accountability requires psychological safety. When engineers know they will not be berated for highlighting a flawed requirement or a vulnerable dependency, they surface problems early while the cost of correction is minimal.',
            'Three pillars of cultural accountability:',
            '1. Clear ownership: Every service has an assigned owner responsible for documentation and operational health.',
            '2. Radical transparency: Post-mortems are shared across all departments with actionable remediations.',
            '3. Ethics escalations: Engineers have a confidential hotline to report compliance risks directly to executive leadership.',
          ],
        },
        {
          id: 'item-video-therac-case',
          sectionId: 'sec-week-4',
          type: 'yt',
          title: 'VIDEO: Therac-25 Medical Radiation Disaster Case Study',
          subtitle: 'Investigative documentary analyzing race conditions, code reuse, and ethical software failures.',
          badge: 'YouTube · 15:20',
          youtubeId: 'lq_o_2d95Y8',
          youtubeUrl: 'https://www.youtube.com/watch?v=lq_o_2d95Y8',
          videoDuration: '15:20',
          initialCompleted: false,
          completionType: 'automatic',
          videoNotes: [
            '0:00 - Introduction to the Therac-25 medical linear accelerator',
            '3:45 - The fateful decision to remove mechanical safety interlocks',
            '7:10 - Dissecting the fatal race condition in the software controller',
            '11:30 - How corporate denial exacerbated patient casualties',
            '14:00 - Enduring software engineering principles derived from the tragedy',
          ],
        },
      ],
    },
    {
      id: 'sec-groups',
      title: 'GROUPS',
      itemCountDescription: 'Student handbook & team presentation calendar',
      items: [
        {
          id: 'item-ethics-handbook',
          sectionId: 'sec-groups',
          type: 'pdf',
          title: 'IT ETHICS HANDBOOK',
          subtitle: 'Institutional handbook on academic integrity, plagiarism safeguards, and collaborative group ethics.',
          badge: 'PDF · 16 Pages',
          initialCompleted: false,
          completionType: 'automatic',
          pdfPages: 16,
          pdfSummary: 'Official departmental handbook detailing rules on code attribution, group member participation minimums, and consequences of unauthorized AI code submission.',
          pdfExcerpt: [
            'Article I: Academic Honesty: All software assignments must include clear citations for external libraries and stackoverflow snippets.',
            'Article II: Collaborative Group Projects: Every group member must contribute to git commits and be prepared to defend code choices individually.',
            'Article III: Use of Generative AI: AI tools may be used for ideation, but core business logic and written essays must be authenticated by the student.',
          ],
        },
        {
          id: 'item-group-schedule',
          sectionId: 'sec-groups',
          type: 'ppt',
          title: 'SCHEDULE',
          subtitle: 'Midterm symposium schedule, group presentation orders, and faculty evaluation panels.',
          badge: 'Schedule Deck',
          initialCompleted: false,
          completionType: 'automatic',
          slides: [
            {
              slideNumber: 1,
              title: 'Midterm Ethics Symposium Schedule',
              bullets: [
                'Group 1: Algorithmic Bias in Facial Recognition Systems (Oct 22, 10:00 AM)',
                'Group 2: The Social Implications of Autonomous Drone Warfare (Oct 22, 10:45 AM)',
                'Group 3: Data Sovereignty and Cross-Border Cloud Hosting (Oct 24, 10:00 AM)',
                'Group 4: Dark UX Patterns and Consumer Exploitation (Oct 24, 10:45 AM)',
              ],
              badge: 'Symposium Calendar',
            },
            {
              slideNumber: 2,
              title: 'Presentation Format & Timing',
              bullets: [
                '15 minutes: Team multimedia presentation and case defense.',
                '10 minutes: Rigorous Q&A by faculty panellists and peer audience.',
                '5 minutes: Individual rubric scoring and peer feedback collection.',
              ],
              badge: 'Timing Guidelines',
            },
          ],
        },
      ],
    },
    {
      id: 'sec-week-6-10',
      title: 'WEEK 6-10 | RIGHT & WRONG FOR IT PROFESSIONALS',
      itemCountDescription: '3 case studies & readings · 1 report submission bin',
      items: [
        {
          id: 'item-brothers-keeper',
          sectionId: 'sec-week-6-10',
          type: 'pdf',
          title: 'BROTHER’S KEEPER',
          subtitle: 'In-depth case study on whistleblowing, duty to colleagues, and reporting misconduct in IT companies.',
          badge: 'PDF Case Study',
          initialCompleted: false,
          completionType: 'automatic',
          pdfPages: 14,
          pdfSummary: 'Classic ethical dilemma: When an engineer discovers their peer has installed a backdoor in an enterprise banking portal. Explores loyalty vs duty to law.',
          pdfExcerpt: [
            'The Dilemma: Marcus and Julian have been close friends since their undergraduate days. Julian confides in Marcus that he altered log retention to conceal unauthorized admin queries.',
            'Legal Repercussions: Under RA 10175, failure to report cyber attacks or unauthorized access can trigger contributory negligence penalties.',
            'Ethical Analysis: How virtue ethics and duty to the profession guide Marcus’s subsequent actions.',
          ],
        },
        {
          id: 'item-content-providing-1',
          sectionId: 'sec-week-6-10',
          type: 'pdf',
          title: 'CONTENT PROVIDING (PART ONE)',
          subtitle: 'Intellectual property rights, DMCA, digital copyright, and open-source licensing models (GPL, MIT, Apache).',
          badge: 'PDF · 18 Pages',
          initialCompleted: false,
          completionType: 'automatic',
          pdfPages: 18,
          pdfSummary: 'Explains the legal mechanisms of copyright, trademark, and patents in software. Details copyleft vs permissive open-source licenses.',
          pdfExcerpt: [
            'Section 1: The Philippine Intellectual Property Code (RA 8293) and software copyright protections.',
            'Section 2: Permissive Licenses: MIT, BSD, Apache 2.0 — rights, warranties, and commercial use.',
            'Section 3: Copyleft Licenses: GNU GPL v2/v3 — the viral reciprocity clause and proprietary compliance.',
          ],
        },
        {
          id: 'item-content-providing-2',
          sectionId: 'sec-week-6-10',
          type: 'pdf',
          title: 'CONTENT PROVIDING (PART TWO)',
          subtitle: 'Generative AI training datasets, web scraping legality, fair use doctrines, and creative content rights.',
          badge: 'PDF · 20 Pages',
          initialCompleted: false,
          completionType: 'automatic',
          pdfPages: 20,
          pdfSummary: 'Contemporary legal and ethical frontier: Scraping public websites, copyrighted artist works used for diffusion models, and the emerging EU AI Act standards.',
          pdfExcerpt: [
            'Section 1: The Transformative Fair Use Defense: Judicial precedents from Google Books to modern LLMs.',
            'Section 2: Robots.txt and Terms of Service: When does unauthorized automated data scraping constitute trespass to chattels?',
            'Section 3: Opt-Out Standards and Ethical Dataset Curating in 2026.',
          ],
        },
        {
          id: 'item-submission-bin-reports',
          sectionId: 'sec-week-6-10',
          type: 'submission',
          title: 'SUBMISSION BIN (REPORTS)',
          subtitle: 'Submit your group midterm case analysis report in PDF or DOCX format. Maximum score: 100 points.',
          badge: 'DepEd Submission Bin · 100 Pts',
          initialCompleted: false,
          completionType: 'automatic',
          maxPoints: 100,
          submissionDeadline: 'Oct 24, 2026 at 11:59 PM',
          rubricCriteria: [
            { criteria: 'Ethical Theory Application (Deontology, Utilitarianism, Virtue Ethics)', points: 30 },
            { criteria: 'Application of ACM & BCS Codes of Conduct', points: 30 },
            { criteria: 'Realistic Remediation & Policy Recommendation Plan', points: 25 },
            { criteria: 'Document Structure, Academic Citations, and DepEd Compliance', points: 15 },
          ],
        },
      ],
    },
  ],
};

export interface CourseCatalogMeta {
  id: string;
  code: string;
  title: string;
  fullTitle: string;
  semester: string;
  teacher: string;
}

export const KNOWN_COURSES_CATALOG: Record<string, CourseCatalogMeta> = {
  'c-social-issues': {
    id: 'c-social-issues',
    code: 'SIPP-301',
    title: 'Social Issues and Professional Practice',
    fullTitle: 'Social Issues and Professional Practice (AY 2024-25 BSCS 3)',
    semester: 'AY 2024 - 2025 1st Sem',
    teacher: 'Prof. Dennis Morales, MSCS, PECE',
  },
  'c-gen-math': {
    id: 'c-gen-math',
    code: 'GEN-MATH-11',
    title: 'General Mathematics',
    fullTitle: 'General Mathematics (AY 2025-26 STEM 11-A)',
    semester: 'AY 2025 - 2026 2nd Sem',
    teacher: 'Mr. Roderick Tan',
  },
  'c-oral-comm': {
    id: 'c-oral-comm',
    code: 'ORAL-COMM-11',
    title: 'Oral Communication in Context',
    fullTitle: 'Oral Communication in Context (AY 2025-26 STEM 11-A)',
    semester: 'AY 2025 - 2026 2nd Sem',
    teacher: 'Ms. Juna Velarde',
  },
  'c-earth-sci': {
    id: 'c-earth-sci',
    code: 'EARTH-SCI-11',
    title: 'Earth and Life Science',
    fullTitle: 'Earth and Life Science (AY 2025-26 STEM 11-A)',
    semester: 'AY 2025 - 2026 2nd Sem',
    teacher: 'Dr. Corazon Bautista',
  },
  'c-kom-fil': {
    id: 'c-kom-fil',
    code: 'KOM-FIL-11',
    title: 'Komunikasyon at Pananaliksik',
    fullTitle: 'Komunikasyon at Pananaliksik sa Wika at Kulturang Pilipino (AY 2025-26 STEM 11-A)',
    semester: 'AY 2025 - 2026 2nd Sem',
    teacher: 'Gng. Remedios Alcantara',
  },
  'c-gen-bio': {
    id: 'c-gen-bio',
    code: 'BIO-11',
    title: 'General Biology 1',
    fullTitle: 'General Biology 1 (AY 2025-26 STEM 11-A)',
    semester: 'AY 2025 - 2026 2nd Sem',
    teacher: 'Dr. Maria Hernandez',
  },
  'c-emp-tech': {
    id: 'c-emp-tech',
    code: 'EMP-TECH-11',
    title: 'Empowerment Technologies',
    fullTitle: 'Empowerment Technologies: ICT for Professional Tracks (AY 2025-26 STEM 11-A)',
    semester: 'AY 2025 - 2026 2nd Sem',
    teacher: 'Engr. Aris Thorne',
  },
};

/**
 * Returns complete course outline data for any selected course.
 * If the course matches Social Issues, returns the exact reference dataset.
 * For other courses, tailors the outline with the selected course's title & instructor,
 * while preserving the rich interactive sample experience (PPT, YT, PDF, Quiz, Submission).
 */
export function getCourseData(courseId?: string | null): CourseOutlineData {
  if (!courseId || courseId === 'c-social-issues') {
    return SOCIAL_ISSUES_COURSE_DATA;
  }
  const meta = KNOWN_COURSES_CATALOG[courseId];
  if (!meta) {
    return SOCIAL_ISSUES_COURSE_DATA;
  }
  return {
    ...SOCIAL_ISSUES_COURSE_DATA,
    courseId: meta.id,
    courseCode: meta.code,
    courseTitle: meta.title,
    fullTitle: meta.fullTitle,
    term: meta.semester,
    instructor: meta.teacher,
  };
}
