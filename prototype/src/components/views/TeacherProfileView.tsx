import { useState } from 'react';
import { useShell } from '@/components/layout/ShellContext';
import {
  RiMailLine,
  RiPhoneLine,
  RiCheckLine,
  RiCloseLine,
  RiEditLine,
  RiLockPasswordLine,
  RiCameraLine,
  RiAlertLine,
  RiUserLine,
  RiGraduationCapLine,
  RiBookOpenLine,
  RiShieldCheckLine,
  RiInformationLine,
  RiGroupLine,
  RiAwardLine,
  RiLogoutBoxRLine,
  RiSparklingLine,
  RiDownload2Line,
  RiFileCopyLine,
  RiArrowDownSLine,
  RiArrowUpSLine,
  RiFileCodeLine,
} from '@remixicon/react';

export interface TeacherProfileViewProps {
  onTabChange?: (tabId: string) => void;
}

const EXAM_CONVERTER_SKILL = `---
name: excelearn-exam-converter
description: Converts any uploaded exam, quiz, questionnaire, or test bank (PDF, DOCX, or text) into the deterministic EXCELEARN EXAM V1 character markup supporting 6 question modes for ExceLearn LMS.
---

# ExceLearn Exam Converter Skill (BYO-AI)
You are an expert DepEd Assessment & Exam Text Formatter for ExceLearn LMS.

## CRITICAL SYSTEM RULE (FOR GOOGLE GEMINI, CHATGPT & CLAUDE)
1. DO NOT invoke, call, or output any built-in quiz widget, interactive quiz tool, Canvas tool, or XML tag such as \`<quiz_tool:generate_quiz ... />\`.
2. NEVER use \`<quiz_tool:...>\` or any native learning plugin. The user needs RAW PLAIN TEXT to copy-paste into an external LMS parser.
3. You MUST print the entire converted questionnaire directly in your chat response inside a single raw \`\`\`text code block starting with \`=== EXCELEARN EXAM V1 ===\` and ending with \`=== END EXAM ===\`.

## Header Syntax
Every output block must begin with:
\`\`\`text
=== EXCELEARN EXAM V1 ===
TITLE: [Exam Title]
TIER: Formative Quiz — Written Work 25% | Unit Assessment — Written Work 25% | Quarterly Exam — Quarterly Assessment 25%
DURATION: [Duration in minutes, e.g. 30, 45, 60]
PASSING: [Passing percentage integer, e.g. 75]
\`\`\`

## Question Formatting (All 6 Modes Supported)

### 1. Multiple Choice Single-Select (MCQ)
[Q1 | MCQ | 2pts]
Which speech style is characterized by a complete absence of public interaction and used in private conversations?
*A) Intimate
B) Casual
C) Consultative
D) Frozen
@EXP: Intimate speech style takes place between people with very close relationships such as family and couples.

### 2. Multi-Select Checkboxes (MULTI)
[Q2 | MULTI | 3pts]
Select all models of communication that are linear or one-way in nature:
*[x] Aristotle's Rhetoric Model
[ ] Schramm's Interactive Model
*[x] Shannon-Weaver Mathematical Model
[ ] Berlo's SMCR Dynamic Model
@EXP: Both Aristotle's and Shannon-Weaver models represent linear, one-way transmission without dynamic feedback loops.

### 3. True or False (TF)
[Q3 | TF | 1pt]
Dyadic communication always requires at least three participants to be present.
@ANS: FALSE
@EXP: Dyadic communication specifically occurs between exactly two individuals.

### 4. Identification / Fill in the Blank (ID)
[Q4 | ID | 2pts]
What speech act refers to the actual utterance and its ostensible grammatical meaning?
@ANS: Locutionary | Locutionary Act | Locution
@EXP: J.L. Austin defines the locutionary act as the phonetic and grammatical act of producing a meaningful expression.

### 5. Matching Type (MATCH)
[Q5 | MATCH | 4pts]
Match each communicative competence dimension with its primary competency indicator:
Grammatical Competence => Mastery of lexical items, syntax, and phonological rules
Sociolinguistic Competence => Appropriate register and polite word choice according to social setting
Discourse Competence => Cohesion and coherence in combining phrases into unified discourse
Strategic Competence => Compensation strategies for verbal breakdown and paraphrasing
@EXP: Canale and Swain's four dimensions of communicative competence.

### 6. Essay / Situational Analysis (ESSAY)
[Q6 | ESSAY | 10pts]
Analyze a workplace scenario where nonverbal kinesics contradict the spoken message. Explain how the receiver resolves this ambiguity.
@RUBRIC: Nonverbal Theory Analysis (4pts) | Resolution Mechanics (4pts) | Clarity & Tone (2pts)
@EXP: Albert Mehrabian's nonverbal primacy effect establishes that visual cues override conflicting verbal statements.

=== END EXAM ===

---
USER INSTRUCTION: Convert my attached PDF/text or topic below into the raw \`=== EXCELEARN EXAM V1 ===\` plain-text block above. Remember: DO NOT call \`<quiz_tool:generate_quiz>\` or any interactive widget—print only the raw text block:
`;

const LESSON_OUTLINER_SKILL = `---
name: excelearn-lesson-outliner
description: Converts DepEd Senior High School syllabus guides, lesson plans, or textbook chapters into interactive EXCELEARN LESSON V1 micro-lesson slide decks with speaker notes.
---

# ExceLearn Lesson Slide Outliner Skill (BYO-AI)
You are an instructional design expert converting DepEd syllabus guides, lesson plans, or textbook chapters into interactive ExceLearn micro-lesson slide decks.

## Output Format
Always output inside a single raw \`\`\`text code block:
\`\`\`text
=== EXCELEARN LESSON V1 ===
TITLE: [Lesson Title]
MODULE: [Target Module Name or Number]
COMPLETION: Automatic | Manual

[SLIDE 1]
@TITLE: Introduction to Intercultural Communication
* Communication across diverse cultural backgrounds and traditions
* High-context vs. Low-context cultures (Edward T. Hall framework)
* Overcoming ethnocentrism and cultural stereotypes in public discourse
@NOTES: Emphasize that non-verbal body language varies significantly between Eastern and Western cultures.

[SLIDE 2]
@TITLE: Hofstede's Cultural Dimensions in Education
* Individualism vs. Collectivism in classroom group dynamics
* Power Distance Index (PDI) between instructors and students
* Uncertainty Avoidance and structured curriculum preferences
@NOTES: Remind students of the Masbate Senior High School communicative norms.

=== END LESSON ===
\`\`\`
`;

const RUBRIC_BUILDER_SKILL = `---
name: excelearn-rubric-builder
description: Converts any DepEd Senior High School performance task description or activity sheet into an EXCELEARN RUBRIC V1 submission bin specification with analytical grading criteria.
---

# ExceLearn Rubric Builder Skill (BYO-AI)
You are a DepEd Senior High School authentic assessment designer. Convert any performance task description or activity sheet into an ExceLearn submission bin specification with an analytical rubric.

## Output Format
Always output inside a single raw \`\`\`text code block:
\`\`\`text
=== EXCELEARN RUBRIC V1 ===
TASK: [Performance Task Title]
FORMAT: PDF Document | MP4 Video | PPTX Slides | ZIP Archive
MAX_POINTS: 100
DUE_DAYS: 7

@CRITERIA: Content & Competence Mastery | 35pts | Depth of research, factual accuracy, and alignment with DepEd competencies.
@CRITERIA: Vocal Modulation & Delivery | 25pts | Clear articulation, appropriate pitch, tempo, and confident projection.
@CRITERIA: Organization & Coherence | 20pts | Structured introduction, smooth transitions, and definitive concluding call-to-action.
@CRITERIA: Technical Execution & Media | 20pts | High-definition video/audio clarity, clean slides, and proper APA citations.

=== END RUBRIC ===
\`\`\`
`;

/**
 * Builds a valid standard ZIP archive in memory containing a single `SKILL.md` file (Store mode, zero dependencies).
 * Compatible with both Google Gemini Skills and Anthropic Claude Skills uploaders.
 */
function createSkillZipBlob(content: string): Blob {
  const encoder = new TextEncoder();
  const fileData = encoder.encode(content);
  const fileNameBytes = encoder.encode('SKILL.md');

  // Compute standard CRC-32
  let crc = 0xffffffff;
  for (let i = 0; i < fileData.length; i++) {
    crc ^= fileData[i];
    for (let j = 0; j < 8; j++) {
      crc = (crc >>> 1) ^ (crc & 1 ? 0xedb88320 : 0);
    }
  }
  crc = (crc ^ 0xffffffff) >>> 0;

  const localHeaderSize = 30 + fileNameBytes.length;
  const centralDirSize = 46 + fileNameBytes.length;
  const endCentralSize = 22;
  const totalSize = localHeaderSize + fileData.length + centralDirSize + endCentralSize;

  const buf = new ArrayBuffer(totalSize);
  const view = new DataView(buf);
  const bytes = new Uint8Array(buf);

  // 1. Local File Header
  let offset = 0;
  view.setUint32(offset, 0x04034b50, true); offset += 4; // Signature
  view.setUint16(offset, 20, true); offset += 2;         // Version needed (2.0)
  view.setUint16(offset, 0x0800, true); offset += 2;     // UTF-8 flag
  view.setUint16(offset, 0, true); offset += 2;          // Compression (0 = Store)
  view.setUint16(offset, 0, true); offset += 2;          // Mod time
  view.setUint16(offset, 0, true); offset += 2;          // Mod date
  view.setUint32(offset, crc, true); offset += 4;        // CRC-32
  view.setUint32(offset, fileData.length, true); offset += 4; // Compressed size
  view.setUint32(offset, fileData.length, true); offset += 4; // Uncompressed size
  view.setUint16(offset, fileNameBytes.length, true); offset += 2; // Filename length
  view.setUint16(offset, 0, true); offset += 2;          // Extra field length
  bytes.set(fileNameBytes, offset); offset += fileNameBytes.length;
  bytes.set(fileData, offset); offset += fileData.length;

  // 2. Central Directory Header
  const centralDirOffset = offset;
  view.setUint32(offset, 0x02014b50, true); offset += 4; // Signature
  view.setUint16(offset, 20, true); offset += 2;         // Version made by
  view.setUint16(offset, 20, true); offset += 2;         // Version needed
  view.setUint16(offset, 0x0800, true); offset += 2;     // UTF-8 flag
  view.setUint16(offset, 0, true); offset += 2;          // Compression (0 = Store)
  view.setUint16(offset, 0, true); offset += 2;          // Mod time
  view.setUint16(offset, 0, true); offset += 2;          // Mod date
  view.setUint32(offset, crc, true); offset += 4;        // CRC-32
  view.setUint32(offset, fileData.length, true); offset += 4; // Compressed size
  view.setUint32(offset, fileData.length, true); offset += 4; // Uncompressed size
  view.setUint16(offset, fileNameBytes.length, true); offset += 2; // Filename length
  view.setUint16(offset, 0, true); offset += 2;          // Extra field length
  view.setUint16(offset, 0, true); offset += 2;          // Comment length
  view.setUint16(offset, 0, true); offset += 2;          // Disk number
  view.setUint16(offset, 0, true); offset += 2;          // Internal attrs
  view.setUint32(offset, 0, true); offset += 4;          // External attrs
  view.setUint32(offset, 0, true); offset += 4;          // Relative offset of local header
  bytes.set(fileNameBytes, offset); offset += fileNameBytes.length;

  // 3. End of Central Directory Record
  view.setUint32(offset, 0x06054b50, true); offset += 4; // Signature
  view.setUint16(offset, 0, true); offset += 2;          // Disk number
  view.setUint16(offset, 0, true); offset += 2;          // Central dir start disk
  view.setUint16(offset, 1, true); offset += 2;          // Entries on disk
  view.setUint16(offset, 1, true); offset += 2;          // Total entries
  view.setUint32(offset, centralDirSize, true); offset += 4; // Size of central dir
  view.setUint32(offset, centralDirOffset, true); offset += 4; // Offset of central dir
  view.setUint16(offset, 0, true);                       // Comment length

  return new Blob([buf], { type: 'application/zip' });
}

export function TeacherProfileView({ onTabChange }: TeacherProfileViewProps) {
  const { requestLogout } = useShell();
  // Teacher master data matching tblusers + tblteacher_education
  const [profile, setProfile] = useState({
    firstName: 'Juna',
    middleName: 'Crisologo',
    lastName: 'Velarde',
    email: 'juna.velarde@excelearn.edu.ph',
    phone: '+63 928 554 9012',
    address: 'Crossing, Uson, Masbate',
    gender: 'Female',
    age: 32,
    role: 'Senior High Faculty',
    dateJoined: 'July 15, 2023',
    status: 'active' as 'active' | 'inactive',
    isVerified: true,
    lastPasswordChange: '2 months ago',
  });

  const [education, setEducation] = useState({
    highestDegree: 'Master of Arts in English Language Teaching',
    university: 'Philippine Normal University (Manila)',
    bachelorsDegree: 'Bachelor of Secondary Education (Major in English)',
    licensure: 'PRC Board Licensure Examination for Professional Teachers',
    lptYear: '2016',
    masters: 'MAEd Graduate',
    certifications: 'TESOL Certified, DepEd Digital Facilitator, K-12 Curriculum Specialist',
  });

  const [assignedCourses] = useState([
    { id: 'sub-1', title: 'Oral Communication in Context', section: 'Grade 11 STEM-A' },
    { id: 'sub-2', title: 'Reading and Writing Skills', section: 'Grade 11 HUMSS' },
    { id: 'sub-3', title: '21st Century Literature from the Philippines and the World', section: 'Grade 12 ABM' },
    { id: 'sub-4', title: 'Creative Nonfiction', section: 'Grade 12 HUMSS' },
  ]);

  // Settings & Toggles
  const [emailNotificationsEnabled, setEmailNotificationsEnabled] = useState(true);
  const [avatarPreview, setAvatarPreview] = useState<string | null>(null);

  // Modals state
  const [isEditModalOpen, setIsEditModalOpen] = useState(false);
  const [isPasswordModalOpen, setIsPasswordModalOpen] = useState(false);
  const [isEduModalOpen, setIsEduModalOpen] = useState(false);
  const [isDeactivateModalOpen, setIsDeactivateModalOpen] = useState(false);

  // Draft states
  const [editDraft, setEditDraft] = useState({ ...profile });
  const [eduDraft, setEduDraft] = useState({ ...education });

  // Password state
  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [passwordError, setPasswordError] = useState('');

  // Toast
  const [toastMessage, setToastMessage] = useState<string | null>(null);
  const [showSyntaxCheatSheet, setShowSyntaxCheatSheet] = useState(false);

  const showToast = (msg: string) => {
    setToastMessage(msg);
    setTimeout(() => {
      setToastMessage((cur) => (cur === msg ? null : cur));
    }, 3200);
  };

  const handleDownloadSkill = (skillSlug: string, content: string, asZip = false) => {
    try {
      const blob = asZip
        ? createSkillZipBlob(content)
        : new Blob([content], { type: 'text/markdown;charset=utf-8' });
      const downloadName = asZip ? `${skillSlug}.zip` : 'SKILL.md';
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = downloadName;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
      showToast(
        asZip
          ? `Downloaded ${downloadName} (contains SKILL.md with YAML frontmatter)!`
          : `Downloaded SKILL.md (${skillSlug}) — ready to upload to Gemini or Claude!`
      );
    } catch {
      showToast(`Failed to trigger download for ${skillSlug}`);
    }
  };

  const handleCopySkill = (title: string, content: string) => {
    if (navigator.clipboard) {
      navigator.clipboard.writeText(content).then(() => {
        showToast(`Copied ${title} prompt to clipboard!`);
      }).catch(() => {
        showToast('Clipboard access denied.');
      });
    } else {
      showToast('Clipboard not supported in this browser.');
    }
  };

  const displayName = `${profile.firstName} ${profile.middleName ? profile.middleName[0] + '. ' : ''}${profile.lastName}`;
  const initials = (profile.firstName[0] || 'J') + (profile.lastName[0] || 'V');

  // Profile completion calculation (matching PHP logic: 9 fields)
  const completionFields = [
    profile.firstName,
    profile.lastName,
    profile.email,
    profile.middleName,
    profile.age ? String(profile.age) : '',
    profile.gender,
    profile.phone,
    profile.address,
    education.highestDegree,
  ];
  const filledCount = completionFields.filter((f) => f && f.trim().length > 0).length;
  const completionRate = Math.round((filledCount / completionFields.length) * 100);

  // Handlers
  const handleOpenEdit = () => {
    setEditDraft({ ...profile });
    setIsEditModalOpen(true);
  };

  const handleSaveEdit = (e: React.FormEvent) => {
    e.preventDefault();
    setProfile({ ...editDraft });
    setIsEditModalOpen(false);
    showToast('Faculty profile updated successfully!');
  };

  const handleOpenEdu = () => {
    setEduDraft({ ...education });
    setIsEduModalOpen(true);
  };

  const handleSaveEdu = (e: React.FormEvent) => {
    e.preventDefault();
    setEducation({ ...eduDraft });
    setIsEduModalOpen(false);
    showToast('Education & credentials saved!');
  };

  const handleAvatarFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      if (file.size > 2 * 1024 * 1024) {
        showToast('Image size exceeds 2MB limit.');
        return;
      }
      const reader = new FileReader();
      reader.onload = (ev) => {
        setAvatarPreview(ev.target?.result as string);
        showToast('Faculty portrait updated!');
      };
      reader.readAsDataURL(file);
    }
  };

  const handleToggleVerified = () => {
    const next = !profile.isVerified;
    setProfile((prev) => ({ ...prev, isVerified: next }));
    showToast(
      next
        ? 'Email marked as verified.'
        : 'Email verification revoked.'
    );
  };

  const handleRevokeOtherSessions = () => {
    showToast('All other sessions revoked. Current session remains active.');
  };

  const handlePasswordSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setPasswordError('');
    if (!currentPassword) {
      setPasswordError('Please enter your current password.');
      return;
    }
    if (newPassword.length < 8) {
      setPasswordError('New password must be at least 8 characters.');
      return;
    }
    if (newPassword !== confirmPassword) {
      setPasswordError('New passwords do not match.');
      return;
    }
    setIsPasswordModalOpen(false);
    setCurrentPassword('');
    setNewPassword('');
    setConfirmPassword('');
    setProfile((prev) => ({ ...prev, lastPasswordChange: 'Just now' }));
    showToast('Faculty account password updated securely!');
  };

  // 4-Bar Password Strength calculation
  const getPasswordStrength = (pwd: string) => {
    let score = 0;
    if (pwd.length >= 8) score++;
    if (/[A-Z]/.test(pwd)) score++;
    if (/[0-9]/.test(pwd)) score++;
    if (/[^A-Za-z0-9]/.test(pwd)) score++;
    return score;
  };
  const pwStrengthScore = getPasswordStrength(newPassword);

  const certList = education.certifications
    .split(',')
    .map((c) => c.trim())
    .filter(Boolean);

  return (
    <div className="space-y-6 w-full pb-12">
      {/* Toast Notification Banner */}
      {toastMessage && (
        <div className="fixed top-18 right-6 z-50 flex items-center gap-2.5 px-4 py-3 rounded-xl bg-bg2 border border-green/40 text-green shadow-xl backdrop-blur-md animate-in fade-in slide-in-from-top-3 duration-200">
          <RiCheckLine className="size-5 shrink-0 text-green" aria-hidden="true" />
          <span className="text-xs font-semibold text-text">{toastMessage}</span>
          <button
            type="button"
            onClick={() => setToastMessage(null)}
            className="ml-2 text-text3 hover:text-text cursor-pointer"
          >
            <RiCloseLine className="size-4" />
          </button>
        </div>
      )}

      {/* ============================================================ */}
      {/* 1. PROFILE BANNER (matching teachers/teacher_profile.php)    */}
      {/* ============================================================ */}
      <div className="relative rounded-2xl sm:rounded-3xl border border-border bg-bg2 overflow-hidden shadow-xs">
        {/* Banner Cover with Brand Palette Gradient */}
        <div
          className="h-36 sm:h-44 relative overflow-hidden select-none"
          style={{
            background: 'linear-gradient(135deg, #1e3c72 0%, #2a5298 45%, #f7971e 100%)',
          }}
        >
          {/* Subtle dot overlay */}
          <div
            className="absolute inset-0 pointer-events-none opacity-20"
            style={{
              backgroundImage: 'radial-gradient(rgba(255, 255, 255, 0.4) 1px, transparent 1px)',
              backgroundSize: '18px 18px',
            }}
          />
        </div>

        {/* Hero Content */}
        <div className="relative px-5 sm:px-8 pb-6 pt-0">
          <div className="flex flex-col sm:flex-row items-center sm:items-start justify-between gap-4 sm:gap-6 text-center sm:text-left">
            {/* Left Column: Avatar + Details */}
            <div className="flex flex-col sm:flex-row items-center sm:items-start gap-4 sm:gap-5 min-w-0 flex-1">
              {/* Avatar with Camera Upload (brought down to ~quarter overlap) */}
              <div className="relative group shrink-0 -mt-6 sm:-mt-7 z-10">
                <input
                  type="file"
                  id="teacherPicInput"
                  accept="image/*"
                  className="hidden"
                  onChange={handleAvatarFileChange}
                />
                <label
                  htmlFor="teacherPicInput"
                  title="Click to change portrait"
                  className="relative flex items-center justify-center size-24 sm:size-28 rounded-full border-4 border-bg2 bg-bg2 shadow-lg overflow-hidden cursor-pointer ring-2 ring-border/50 group-hover:ring-accent transition-all duration-200"
                >
                  {avatarPreview ? (
                    <img
                      src={avatarPreview}
                      alt={displayName}
                      className="w-full h-full object-cover"
                    />
                  ) : (
                    <div
                      className="w-full h-full flex items-center justify-center font-serif text-2xl sm:text-3xl font-bold text-white"
                      style={{
                        background: 'linear-gradient(135deg, var(--accent), var(--purple))',
                      }}
                    >
                      {initials}
                    </div>
                  )}
                  <div className="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex flex-col items-center justify-center text-white text-[10px] font-semibold transition-opacity duration-200">
                    <RiCameraLine className="size-5 mb-0.5" />
                    <span>Change</span>
                  </div>
                </label>
                <label
                  htmlFor="teacherPicInput"
                  className="absolute bottom-1 right-1 p-1.5 rounded-full bg-accent text-white shadow-md border-2 border-bg2 cursor-pointer hover:scale-110 active:scale-95 transition-transform"
                  title="Upload photo"
                >
                  <RiCameraLine className="size-3.5" />
                </label>
              </div>

              {/* Info Main: sits cleanly in the card body below the banner */}
              <div className="min-w-0 space-y-1 sm:space-y-1.5 pt-1 sm:pt-3 flex-1">
                <div className="flex flex-col sm:flex-row sm:items-center gap-2">
                  <h1 className="font-serif text-2xl sm:text-3xl font-bold text-text tracking-tight truncate">
                    {displayName}
                  </h1>
                  <span className="inline-flex items-center justify-center self-center sm:self-auto px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-green-bg text-green border border-green/30 shrink-0">
                    Verified Faculty
                  </span>
                </div>
                <div className="text-xs font-semibold text-accent">{profile.role}</div>
                <div className="flex flex-wrap items-center justify-center sm:justify-start gap-3 text-xs text-text2 pt-0.5">
                  <span className="inline-flex items-center gap-1">
                    <RiMailLine className="size-3.5 text-text3" />
                    <span>{profile.email}</span>
                  </span>
                  <span className="text-text3">·</span>
                  <span className="inline-flex items-center gap-1">
                    <RiPhoneLine className="size-3.5 text-text3" />
                    <span>{profile.phone}</span>
                  </span>
                  <span className="text-text3">·</span>
                  <span className="inline-flex items-center gap-1">
                    <span className="size-2 rounded-full bg-green" />
                    <span className="capitalize">{profile.status}</span>
                  </span>
                </div>
              </div>
            </div>

            {/* Actions */}
            <div className="flex items-center gap-2.5 pt-1 sm:pt-3 shrink-0 w-full sm:w-auto justify-center">
              <button
                type="button"
                onClick={handleOpenEdit}
                className="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl bg-accent text-white text-xs font-semibold hover:bg-accent2 shadow-xs cursor-pointer active:scale-95 transition-all"
              >
                <RiEditLine className="size-3.5" />
                <span>Edit Profile</span>
              </button>
              <button
                type="button"
                onClick={() => setIsPasswordModalOpen(true)}
                className="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl bg-bg3 hover:bg-bg4 text-text text-xs font-semibold border border-border cursor-pointer active:scale-95 transition-all"
              >
                <RiLockPasswordLine className="size-3.5 text-text2" />
                <span>Change Password</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      {/* ============================================================ */}
      {/* 2. QUICK STATS (matching PHP .stats-mini)                    */}
      {/* ============================================================ */}
      <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        {/* Card 1: Active Subjects */}
        <div
          onClick={() => onTabChange?.('courses')}
          role="button"
          tabIndex={0}
          onKeyDown={(e) => e.key === 'Enter' && onTabChange?.('courses')}
          className="p-4 sm:p-5 rounded-2xl bg-bg2 border border-border shadow-xs hover:border-accent/60 hover:shadow-md cursor-pointer transition-all active:scale-[0.98] group"
        >
          <div className="flex items-center justify-between mb-2">
            <span className="text-xs font-semibold text-text3 uppercase tracking-wider">
              Active Subjects
            </span>
            <span className="p-2 rounded-xl bg-accent-glow text-accent group-hover:scale-110 transition-transform">
              <RiBookOpenLine className="size-4" />
            </span>
          </div>
          <div className="text-2xl sm:text-3xl font-serif font-bold text-accent">
            {assignedCourses.length}
          </div>
          <div className="mt-3">
            <div className="h-1.5 w-full rounded-full bg-bg3 overflow-hidden">
              <div
                className="h-full rounded-full bg-accent transition-all duration-700"
                style={{ width: `${Math.min(100, assignedCourses.length * 25)}%` }}
              />
            </div>
            <div className="flex justify-between text-[11px] text-text3 mt-1.5">
              <span>Teaching Load</span>
              <span className="text-accent font-medium">View subjects →</span>
            </div>
          </div>
        </div>

        {/* Card 2: Enrolled Students */}
        <div
          onClick={() => onTabChange?.('students')}
          role="button"
          tabIndex={0}
          onKeyDown={(e) => e.key === 'Enter' && onTabChange?.('students')}
          className="p-4 sm:p-5 rounded-2xl bg-bg2 border border-border shadow-xs hover:border-green/60 hover:shadow-md cursor-pointer transition-all active:scale-[0.98] group"
        >
          <div className="flex items-center justify-between mb-2">
            <span className="text-xs font-semibold text-text3 uppercase tracking-wider">
              Enrolled Students
            </span>
            <span className="p-2 rounded-xl bg-green-bg text-green group-hover:scale-110 transition-transform">
              <RiGroupLine className="size-4" />
            </span>
          </div>
          <div className="text-2xl sm:text-3xl font-serif font-bold text-green">128</div>
          <div className="mt-3">
            <div className="h-1.5 w-full rounded-full bg-bg3 overflow-hidden">
              <div
                className="h-full rounded-full bg-linear-to-r from-green to-emerald-400 transition-all duration-700"
                style={{ width: '92%' }}
              />
            </div>
            <div className="flex justify-between text-[11px] text-text3 mt-1.5">
              <span>DepEd SHS Cohort</span>
              <span className="text-green font-medium">View directory →</span>
            </div>
          </div>
        </div>

        {/* Card 3: Profile Completion */}
        <div className="p-4 sm:p-5 rounded-2xl bg-bg2 border border-border shadow-xs hover:border-purple/60 transition-all group">
          <div className="flex items-center justify-between mb-2">
            <span className="text-xs font-semibold text-text3 uppercase tracking-wider">
              Profile Completion
            </span>
            <span className="p-2 rounded-xl bg-purple-bg text-purple group-hover:scale-110 transition-transform">
              <RiUserLine className="size-4" />
            </span>
          </div>
          <div className="text-2xl sm:text-3xl font-serif font-bold text-purple">{completionRate}%</div>
          <div className="mt-3">
            <div className="h-1.5 w-full rounded-full bg-bg3 overflow-hidden">
              <div
                className="h-full rounded-full bg-linear-to-r from-purple to-indigo-500 transition-all duration-700"
                style={{ width: `${completionRate}%` }}
              />
            </div>
            <div className="flex justify-between text-[11px] text-text3 mt-1.5">
              <span>{filledCount} of {completionFields.length} fields filled</span>
              <span className="text-purple font-medium">Complete</span>
            </div>
          </div>
        </div>

        {/* Card 4: PRC Licensure & Status */}
        <div className="p-4 sm:p-5 rounded-2xl bg-bg2 border border-border shadow-xs hover:border-amber/60 transition-all group">
          <div className="flex items-center justify-between mb-2">
            <span className="text-xs font-semibold text-text3 uppercase tracking-wider">
              PRC Licensure
            </span>
            <span className="p-2 rounded-xl bg-amber-bg text-amber group-hover:scale-110 transition-transform">
              <RiAwardLine className="size-4" />
            </span>
          </div>
          <div className="text-2xl sm:text-3xl font-serif font-bold text-amber">LPT</div>
          <div className="mt-3">
            <div className="h-1.5 w-full rounded-full bg-bg3 overflow-hidden">
              <div
                className="h-full rounded-full bg-amber transition-all duration-700"
                style={{ width: '100%' }}
              />
            </div>
            <div className="flex justify-between text-[11px] text-text3 mt-1.5">
              <span>Class of {education.lptYear}</span>
              <span className="text-amber font-medium">Verified</span>
            </div>
          </div>
        </div>
      </div>

      {/* ============================================================ */}
      {/* 3. PERSONAL INFORMATION CARD (matching PHP Personal Info)    */}
      {/* ============================================================ */}
      <div className="p-6 rounded-2xl bg-bg2 border border-border shadow-xs space-y-5">
        <div className="flex items-center justify-between border-b border-border pb-3.5">
          <div className="flex items-center gap-2">
            <span className="p-1.5 rounded-lg bg-accent-glow text-accent">
              <RiUserLine className="size-4" />
            </span>
            <h3 className="text-base font-bold font-serif text-text">Personal Information</h3>
          </div>
          <button
            type="button"
            onClick={handleOpenEdit}
            className="px-3 py-1 rounded-lg text-xs font-semibold text-text2 hover:text-text bg-bg3 hover:bg-bg4 border border-border cursor-pointer transition-colors inline-flex items-center gap-1.5"
          >
            <RiEditLine className="size-3.5" />
            <span>Edit</span>
          </button>
        </div>

        <div className="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
          <div>
            <div className="text-[11px] font-semibold text-text3 uppercase tracking-wider">First Name</div>
            <div className="mt-1 font-semibold text-text text-sm">{profile.firstName || '—'}</div>
          </div>
          <div>
            <div className="text-[11px] font-semibold text-text3 uppercase tracking-wider">Last Name</div>
            <div className="mt-1 font-semibold text-text text-sm">{profile.lastName || '—'}</div>
          </div>
          <div>
            <div className="text-[11px] font-semibold text-text3 uppercase tracking-wider">Middle Name</div>
            <div className="mt-1 font-semibold text-text text-sm">{profile.middleName || '—'}</div>
          </div>
          <div>
            <div className="text-[11px] font-semibold text-text3 uppercase tracking-wider">Role</div>
            <div className="mt-1 font-semibold text-text text-sm">{profile.role}</div>
          </div>
          <div>
            <div className="text-[11px] font-semibold text-text3 uppercase tracking-wider">Date Joined</div>
            <div className="mt-1 font-semibold text-text text-sm">{profile.dateJoined}</div>
          </div>
          <div>
            <div className="text-[11px] font-semibold text-text3 uppercase tracking-wider">Gender</div>
            <div className="mt-1 font-semibold text-text text-sm">{profile.gender}</div>
          </div>
          <div>
            <div className="text-[11px] font-semibold text-text3 uppercase tracking-wider">Age</div>
            <div className="mt-1 font-semibold text-text text-sm">{profile.age}</div>
          </div>
          <div>
            <div className="text-[11px] font-semibold text-text3 uppercase tracking-wider">Account Status</div>
            <div className="mt-1">
              <span className="px-2 py-0.5 rounded-full text-[10px] font-bold bg-green-bg text-green">
                Active
              </span>
            </div>
          </div>
        </div>

        <div className="border-t border-border/60 pt-4 grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
          <div>
            <div className="text-[11px] font-semibold text-text3 uppercase tracking-wider">Email Address</div>
            <div className="mt-1 font-semibold text-text text-sm">{profile.email}</div>
          </div>
          <div>
            <div className="text-[11px] font-semibold text-text3 uppercase tracking-wider">Phone Number</div>
            <div className="mt-1 font-semibold text-text text-sm">{profile.phone}</div>
          </div>
          <div className="sm:col-span-2">
            <div className="text-[11px] font-semibold text-text3 uppercase tracking-wider">Home Address</div>
            <div className="mt-1 font-medium text-text text-sm leading-relaxed">{profile.address}</div>
          </div>
        </div>
      </div>

      {/* ============================================================ */}
      {/* 4. EDUCATION & CREDENTIALS (matching PHP update_education)   */}
      {/* ============================================================ */}
      <div className="p-6 rounded-2xl bg-bg2 border border-border shadow-xs space-y-5">
        <div className="flex items-center justify-between border-b border-border pb-3.5">
          <div className="flex items-center gap-2">
            <span className="p-1.5 rounded-lg bg-accent-glow text-accent">
              <RiGraduationCapLine className="size-4" />
            </span>
            <h3 className="text-base font-bold font-serif text-text">Education & Credentials</h3>
          </div>
          <button
            type="button"
            onClick={handleOpenEdu}
            className="px-3 py-1 rounded-lg text-xs font-semibold text-text2 hover:text-text bg-bg3 hover:bg-bg4 border border-border cursor-pointer transition-colors inline-flex items-center gap-1.5"
          >
            <RiEditLine className="size-3.5" />
            <span>Edit</span>
          </button>
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
          <div>
            <div className="text-[11px] font-semibold text-text3 uppercase tracking-wider">Highest Degree</div>
            <div className="mt-1 font-semibold text-text text-sm">{education.highestDegree}</div>
          </div>
          <div>
            <div className="text-[11px] font-semibold text-text3 uppercase tracking-wider">University</div>
            <div className="mt-1 font-semibold text-text text-sm">{education.university}</div>
          </div>
          <div>
            <div className="text-[11px] font-semibold text-text3 uppercase tracking-wider">Bachelor's Degree</div>
            <div className="mt-1 font-semibold text-text text-sm">{education.bachelorsDegree}</div>
          </div>
          <div>
            <div className="text-[11px] font-semibold text-text3 uppercase tracking-wider">Licensure</div>
            <div className="mt-1 font-semibold text-text text-sm">{education.licensure}</div>
          </div>
        </div>

        <div className="border-t border-border/60 pt-4 flex flex-wrap gap-2">
          <span className="px-3 py-1 rounded-full text-xs font-bold bg-accent/15 text-accent border border-accent/20">
            LPT — {education.lptYear}
          </span>
          <span className="px-3 py-1 rounded-full text-xs font-bold bg-purple-bg text-purple border border-purple/20">
            {education.masters}
          </span>
          {certList.map((cert, idx) => (
            <span
              key={idx}
              className="px-3 py-1 rounded-full text-xs font-semibold bg-cyan-bg text-cyan border border-cyan/20"
            >
              {cert}
            </span>
          ))}
        </div>
      </div>

      {/* ============================================================ */}
      {/* 5. ASSIGNED COURSES (matching PHP Assigned Courses)          */}
      {/* ============================================================ */}
      <div className="p-6 rounded-2xl bg-bg2 border border-border shadow-xs space-y-4">
        <div className="flex items-center justify-between border-b border-border pb-3.5">
          <div className="flex items-center gap-2">
            <span className="p-1.5 rounded-lg bg-accent-glow text-accent">
              <RiBookOpenLine className="size-4" />
            </span>
            <h3 className="text-base font-bold font-serif text-text">Assigned Teaching Subjects</h3>
          </div>
          <button
            type="button"
            onClick={() => onTabChange?.('courses')}
            className="px-3 py-1 rounded-lg text-xs font-semibold text-text2 hover:text-text bg-bg3 hover:bg-bg4 border border-border cursor-pointer transition-colors"
          >
            Manage Subjects
          </button>
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
          {assignedCourses.map((c) => (
            <div
              key={c.id}
              onClick={() => onTabChange?.('courses')}
              role="button"
              tabIndex={0}
              onKeyDown={(e) => e.key === 'Enter' && onTabChange?.('courses')}
              className="p-3.5 rounded-xl bg-bg3 border border-border flex items-center gap-3 hover:border-accent hover:bg-bg4 cursor-pointer transition-all active:scale-[0.99] group"
            >
              <span className="size-2.5 rounded-full bg-accent shrink-0 group-hover:scale-125 transition-transform" />
              <div className="min-w-0 flex-1">
                <div className="text-xs font-semibold text-text truncate group-hover:text-accent transition-colors">{c.title}</div>
                <div className="text-[11px] text-text3">{c.section}</div>
              </div>
              <span className="text-xs text-text3 opacity-0 group-hover:opacity-100 transition-opacity">→</span>
            </div>
          ))}
        </div>
      </div>

      {/* ============================================================ */}
      {/* 6. SECURITY & ACCOUNT (matching PHP Security & Account)       */}
      {/* ============================================================ */}
      <div className="p-6 rounded-2xl bg-bg2 border border-border shadow-xs space-y-5">
        <div className="flex items-center gap-2 border-b border-border pb-3.5">
          <span className="p-1.5 rounded-lg bg-accent-glow text-accent">
            <RiShieldCheckLine className="size-4" />
          </span>
          <h3 className="text-base font-bold font-serif text-text">Security & Account</h3>
        </div>

        <div className="space-y-4 text-xs">
          {/* Password Row */}
          <div className="p-4 rounded-xl bg-bg3 border border-border flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
              <div className="font-semibold text-text text-sm">Account Password</div>
              <div className="text-text3 mt-0.5">
                Last changed {profile.lastPasswordChange}. We recommend updating credentials every 90 days.
              </div>
            </div>
            <button
              type="button"
              onClick={() => setIsPasswordModalOpen(true)}
              className="self-start sm:self-auto px-3.5 py-1.5 rounded-lg bg-bg2 hover:bg-bg4 border border-border text-text font-semibold cursor-pointer transition-colors"
            >
              Change
            </button>
          </div>

          {/* Email Verification Row */}
          <div className="p-4 rounded-xl bg-bg3 border border-border flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
              <div className="font-semibold text-text text-sm">Email Verification</div>
              <div className="text-text3 mt-0.5">
                {profile.isVerified ? (
                  <>
                    Your faculty email <strong>{profile.email}</strong> is verified.
                  </>
                ) : (
                  'Your faculty email is not yet verified. Click to verify identity.'
                )}
              </div>
            </div>
            <div className="flex items-center gap-2 self-start sm:self-auto">
              <span
                className={`px-2 py-0.5 rounded-full text-[10px] font-bold ${
                  profile.isVerified ? 'bg-green-bg text-green' : 'bg-red-bg text-red'
                }`}
              >
                {profile.isVerified ? 'Verified' : 'Not Verified'}
              </span>
              <button
                type="button"
                onClick={handleToggleVerified}
                className="px-3.5 py-1.5 rounded-lg bg-bg2 hover:bg-bg4 border border-border text-text font-semibold cursor-pointer transition-colors"
              >
                {profile.isVerified ? 'Revoke' : 'Verify Email'}
              </button>
            </div>
          </div>

          {/* Active Sessions Row */}
          <div className="p-4 rounded-xl bg-bg3 border border-border flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
              <div className="font-semibold text-text text-sm">Active Sessions</div>
              <div className="text-text3 mt-0.5">
                You are currently logged in on this browser device. Revoking will terminate all other sessions.
              </div>
            </div>
            <button
              type="button"
              onClick={handleRevokeOtherSessions}
              className="self-start sm:self-auto px-3.5 py-1.5 rounded-lg bg-bg2 hover:bg-bg4 border border-border text-text font-semibold cursor-pointer transition-colors"
            >
              Revoke Others
            </button>
          </div>

          {/* Email Notifications Row */}
          <div className="p-4 rounded-xl bg-bg3 border border-border flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
              <div className="font-semibold text-text text-sm">Email Notifications</div>
              <div className="text-text3 mt-0.5">
                Receive instant alerts for student task submissions, messages, and administrative memos.
              </div>
            </div>
            <button
              type="button"
              onClick={() => {
                setEmailNotificationsEnabled(!emailNotificationsEnabled);
                showToast(
                  !emailNotificationsEnabled
                    ? 'Email notifications enabled.'
                    : 'Email notifications muted.'
                );
              }}
              className={`self-start sm:self-auto px-3.5 py-1.5 rounded-lg border font-semibold cursor-pointer transition-colors ${
                emailNotificationsEnabled
                  ? 'bg-accent text-white border-accent'
                  : 'bg-bg2 text-text3 border-border hover:text-text'
              }`}
            >
              {emailNotificationsEnabled ? 'Enabled' : 'Disabled'}
            </button>
          </div>

          {/* Subsection: BYO-AI Curriculum & Exam Converter Skills ($0 Server AI) */}
          <div className="pt-4 border-t border-border space-y-4">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
              <div className="flex items-center gap-2">
                <span className="p-1 rounded-md bg-amber-500/15 text-amber-500">
                  <RiSparklingLine className="size-4" />
                </span>
                <span className="font-bold text-text text-sm">
                  BYO-AI Curriculum & Exam Converter Skills ($0 Server AI)
                </span>
              </div>
              <span className="px-2 py-0.5 rounded-full text-[10px] font-bold bg-green-bg text-green border border-green/20 self-start sm:self-auto">
                100% Client-Side · $0 AI Cost · Zero Data Leak
              </span>
            </div>

            <p className="text-text3 text-xs leading-relaxed">
              Download official ExceLearn formatting skills for your preferred AI provider (ChatGPT, Claude, or Gemini). Upload your raw PDF/Word exams to your AI with this skill to convert them into ExceLearn&apos;s deterministic character format (<code className="px-1.5 py-0.5 rounded bg-bg4 text-text font-mono text-[11px]">=== EXCELEARN EXAM V1 ===</code>) for instant offline parsing.
            </p>

            {/* 3 Downloadable Skill Cards */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-3.5">
              {/* Card 1: Exam Converter Skill */}
              <div className="p-4 rounded-xl bg-bg3 border border-border flex flex-col justify-between gap-3 hover:border-accent/40 transition-colors">
                <div className="space-y-2">
                  <div className="flex items-center justify-between gap-1">
                    <span className="p-1.5 rounded-lg bg-purple-500/10 text-purple-400">
                      <RiFileCodeLine className="size-4" />
                    </span>
                    <span className="text-[10px] font-bold px-2 py-0.5 rounded-md bg-purple-500/15 text-purple-400 border border-purple-500/25">
                      YAML + SKILL.md Ready
                    </span>
                  </div>
                  <div>
                    <div className="font-bold text-text text-xs">excelearn-exam-converter</div>
                    <div className="text-[10px] font-mono text-text3 mt-0.5">File: SKILL.md (kebab-case YAML)</div>
                  </div>
                  <p className="text-[11px] text-text3 leading-normal">
                    Converts test questions or test banks into character tags (<code className="text-text2">*A)</code>, <code className="text-text2">*[x]</code>, <code className="text-text2">@ANS:</code>, <code className="text-text2">=&gt;</code>, <code className="text-text2">@EXP:</code>, <code className="text-text2">@RUBRIC:</code>).
                  </p>
                </div>
                <div className="flex flex-wrap items-center gap-1.5 pt-2 border-t border-border/60">
                  <button
                    type="button"
                    onClick={() => handleCopySkill('Exam Converter Skill', EXAM_CONVERTER_SKILL)}
                    className="py-1.5 px-2.5 rounded-lg bg-bg2 hover:bg-bg4 border border-border text-text font-semibold text-[11px] inline-flex items-center justify-center gap-1 cursor-pointer transition-colors"
                    title="Copy full SKILL.md text to clipboard"
                  >
                    <RiFileCopyLine className="size-3.5" />
                    <span>Copy</span>
                  </button>
                  <button
                    type="button"
                    onClick={() => handleDownloadSkill('excelearn-exam-converter', EXAM_CONVERTER_SKILL, false)}
                    className="flex-1 py-1.5 px-2 rounded-lg bg-accent text-white hover:bg-accent/90 text-[11px] font-semibold inline-flex items-center justify-center gap-1 cursor-pointer transition-colors shadow-xs"
                    title="Downloads literally as SKILL.md for Gemini & Claude"
                  >
                    <RiDownload2Line className="size-3.5" />
                    <span>SKILL.md</span>
                  </button>
                  <button
                    type="button"
                    onClick={() => handleDownloadSkill('excelearn-exam-converter', EXAM_CONVERTER_SKILL, true)}
                    className="py-1.5 px-2.5 rounded-lg bg-purple-500/15 hover:bg-purple-500/25 border border-purple-500/30 text-purple-400 font-semibold text-[11px] inline-flex items-center justify-center gap-1 cursor-pointer transition-colors"
                    title="Downloads excelearn-exam-converter.zip containing SKILL.md"
                  >
                    <RiDownload2Line className="size-3.5" />
                    <span>.zip</span>
                  </button>
                </div>
              </div>

              {/* Card 2: Lesson Slide Outliner Skill */}
              <div className="p-4 rounded-xl bg-bg3 border border-border flex flex-col justify-between gap-3 hover:border-accent/40 transition-colors">
                <div className="space-y-2">
                  <div className="flex items-center justify-between gap-1">
                    <span className="p-1.5 rounded-lg bg-blue-500/10 text-blue-400">
                      <RiBookOpenLine className="size-4" />
                    </span>
                    <span className="text-[10px] font-bold px-2 py-0.5 rounded-md bg-blue-500/15 text-blue-400 border border-blue-500/25">
                      YAML + SKILL.md Ready
                    </span>
                  </div>
                  <div>
                    <div className="font-bold text-text text-xs">excelearn-lesson-outliner</div>
                    <div className="text-[10px] font-mono text-text3 mt-0.5">File: SKILL.md (kebab-case YAML)</div>
                  </div>
                  <p className="text-[11px] text-text3 leading-normal">
                    Converts DepEd curriculum guides or PDF chapters into micro-lesson slide blocks (<code className="text-text2">[SLIDE 1]</code>, <code className="text-text2">@TITLE:</code>, <code className="text-text2">* Bullet</code>, <code className="text-text2">@NOTES:</code>).
                  </p>
                </div>
                <div className="flex flex-wrap items-center gap-1.5 pt-2 border-t border-border/60">
                  <button
                    type="button"
                    onClick={() => handleCopySkill('Lesson Slide Outliner Skill', LESSON_OUTLINER_SKILL)}
                    className="py-1.5 px-2.5 rounded-lg bg-bg2 hover:bg-bg4 border border-border text-text font-semibold text-[11px] inline-flex items-center justify-center gap-1 cursor-pointer transition-colors"
                    title="Copy full SKILL.md text to clipboard"
                  >
                    <RiFileCopyLine className="size-3.5" />
                    <span>Copy</span>
                  </button>
                  <button
                    type="button"
                    onClick={() => handleDownloadSkill('excelearn-lesson-outliner', LESSON_OUTLINER_SKILL, false)}
                    className="flex-1 py-1.5 px-2 rounded-lg bg-accent text-white hover:bg-accent/90 text-[11px] font-semibold inline-flex items-center justify-center gap-1 cursor-pointer transition-colors shadow-xs"
                    title="Downloads literally as SKILL.md for Gemini & Claude"
                  >
                    <RiDownload2Line className="size-3.5" />
                    <span>SKILL.md</span>
                  </button>
                  <button
                    type="button"
                    onClick={() => handleDownloadSkill('excelearn-lesson-outliner', LESSON_OUTLINER_SKILL, true)}
                    className="py-1.5 px-2.5 rounded-lg bg-blue-500/15 hover:bg-blue-500/25 border border-blue-500/30 text-blue-400 font-semibold text-[11px] inline-flex items-center justify-center gap-1 cursor-pointer transition-colors"
                    title="Downloads excelearn-lesson-outliner.zip containing SKILL.md"
                  >
                    <RiDownload2Line className="size-3.5" />
                    <span>.zip</span>
                  </button>
                </div>
              </div>

              {/* Card 3: Rubric Builder Skill */}
              <div className="p-4 rounded-xl bg-bg3 border border-border flex flex-col justify-between gap-3 hover:border-accent/40 transition-colors">
                <div className="space-y-2">
                  <div className="flex items-center justify-between gap-1">
                    <span className="p-1.5 rounded-lg bg-emerald-500/10 text-emerald-400">
                      <RiAwardLine className="size-4" />
                    </span>
                    <span className="text-[10px] font-bold px-2 py-0.5 rounded-md bg-emerald-500/15 text-emerald-400 border border-emerald-500/25">
                      YAML + SKILL.md Ready
                    </span>
                  </div>
                  <div>
                    <div className="font-bold text-text text-xs">excelearn-rubric-builder</div>
                    <div className="text-[10px] font-mono text-text3 mt-0.5">File: SKILL.md (kebab-case YAML)</div>
                  </div>
                  <p className="text-[11px] text-text3 leading-normal">
                    Generates analytical rubrics (<code className="text-text2">@CRITERIA: Vocal Modulation | 35pts</code>) for Performance Task Submission Bins.
                  </p>
                </div>
                <div className="flex flex-wrap items-center gap-1.5 pt-2 border-t border-border/60">
                  <button
                    type="button"
                    onClick={() => handleCopySkill('Rubric Builder Skill', RUBRIC_BUILDER_SKILL)}
                    className="py-1.5 px-2.5 rounded-lg bg-bg2 hover:bg-bg4 border border-border text-text font-semibold text-[11px] inline-flex items-center justify-center gap-1 cursor-pointer transition-colors"
                    title="Copy full SKILL.md text to clipboard"
                  >
                    <RiFileCopyLine className="size-3.5" />
                    <span>Copy</span>
                  </button>
                  <button
                    type="button"
                    onClick={() => handleDownloadSkill('excelearn-rubric-builder', RUBRIC_BUILDER_SKILL, false)}
                    className="flex-1 py-1.5 px-2 rounded-lg bg-accent text-white hover:bg-accent/90 text-[11px] font-semibold inline-flex items-center justify-center gap-1 cursor-pointer transition-colors shadow-xs"
                    title="Downloads literally as SKILL.md for Gemini & Claude"
                  >
                    <RiDownload2Line className="size-3.5" />
                    <span>SKILL.md</span>
                  </button>
                  <button
                    type="button"
                    onClick={() => handleDownloadSkill('excelearn-rubric-builder', RUBRIC_BUILDER_SKILL, true)}
                    className="py-1.5 px-2.5 rounded-lg bg-emerald-500/15 hover:bg-emerald-500/25 border border-emerald-500/30 text-emerald-400 font-semibold text-[11px] inline-flex items-center justify-center gap-1 cursor-pointer transition-colors"
                    title="Downloads excelearn-rubric-builder.zip containing SKILL.md"
                  >
                    <RiDownload2Line className="size-3.5" />
                    <span>.zip</span>
                  </button>
                </div>
              </div>
            </div>

            {/* Collapsible Syntax Cheat Sheet Accordion */}
            <div className="rounded-xl border border-border bg-bg3 overflow-hidden">
              <button
                type="button"
                onClick={() => setShowSyntaxCheatSheet(!showSyntaxCheatSheet)}
                className="w-full p-3.5 flex items-center justify-between text-left hover:bg-bg4/50 cursor-pointer transition-colors"
              >
                <div className="flex items-center gap-2">
                  <span className="text-xs font-bold text-text">
                    View Character Syntax Cheat Sheet
                  </span>
                  <span className="text-[10px] font-semibold px-2 py-0.5 rounded bg-bg2 text-text3 border border-border">
                    Deterministic Delimiters
                  </span>
                </div>
                {showSyntaxCheatSheet ? (
                  <RiArrowUpSLine className="size-4 text-text2" />
                ) : (
                  <RiArrowDownSLine className="size-4 text-text2" />
                )}
              </button>

              {showSyntaxCheatSheet && (
                <div className="p-4 pt-1 border-t border-border space-y-3 text-[11px] bg-bg2/40">
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-2.5">
                    <div className="p-2.5 rounded-lg bg-bg3 border border-border space-y-1">
                      <div className="font-bold text-accent">1. Single Choice (MCQ)</div>
                      <code className="block font-mono text-[10px] text-text2 bg-bg2 p-1.5 rounded">
                        [Q1 | MCQ | 2pts]<br />
                        Question stem text?<br />
                        *A) Correct answer<br />
                        B) Distractor choice<br />
                        @EXP: Rationale text
                      </code>
                    </div>

                    <div className="p-2.5 rounded-lg bg-bg3 border border-border space-y-1">
                      <div className="font-bold text-accent">2. Multi-Select (MULTI)</div>
                      <code className="block font-mono text-[10px] text-text2 bg-bg2 p-1.5 rounded">
                        [Q2 | MULTI | 3pts]<br />
                        Select all that apply:<br />
                        *[x] Correct option 1<br />
                        [ ] Distractor option<br />
                        *[x] Correct option 2
                      </code>
                    </div>

                    <div className="p-2.5 rounded-lg bg-bg3 border border-border space-y-1">
                      <div className="font-bold text-accent">3. True or False (TF)</div>
                      <code className="block font-mono text-[10px] text-text2 bg-bg2 p-1.5 rounded">
                        [Q3 | TF | 1pt]<br />
                        Statement to evaluate.<br />
                        @ANS: TRUE (or FALSE)<br />
                        @EXP: Verification note
                      </code>
                    </div>

                    <div className="p-2.5 rounded-lg bg-bg3 border border-border space-y-1">
                      <div className="font-bold text-accent">4. Identification (ID)</div>
                      <code className="block font-mono text-[10px] text-text2 bg-bg2 p-1.5 rounded">
                        [Q4 | ID | 2pts]<br />
                        Question prompt?<br />
                        @ANS: answer1 | answer2<br />
                        @EXP: Case-insensitive match
                      </code>
                    </div>

                    <div className="p-2.5 rounded-lg bg-bg3 border border-border space-y-1">
                      <div className="font-bold text-accent">5. Matching Type (MATCH)</div>
                      <code className="block font-mono text-[10px] text-text2 bg-bg2 p-1.5 rounded">
                        [Q5 | MATCH | 4pts]<br />
                        Premise A =&gt; Target A<br />
                        Premise B =&gt; Target B<br />
                        Premise C =&gt; Target C
                      </code>
                    </div>

                    <div className="p-2.5 rounded-lg bg-bg3 border border-border space-y-1">
                      <div className="font-bold text-accent">6. Essay / Rubric (ESSAY)</div>
                      <code className="block font-mono text-[10px] text-text2 bg-bg2 p-1.5 rounded">
                        [Q6 | ESSAY | 10pts]<br />
                        Situational prompt?<br />
                        @RUBRIC: Thesis (5pts) | Analysis (5pts)<br />
                        @EXP: Scoring guide
                      </code>
                    </div>
                  </div>
                </div>
              )}
            </div>
          </div>
        </div>
      </div>

      {/* ============================================================ */}
      {/* 7. DANGER ZONE (matching PHP Danger Zone)                    */}
      {/* ============================================================ */}
      <div className="p-6 rounded-2xl bg-bg2 border border-red/25 shadow-xs">
        <div className="flex items-center gap-2 border-b border-border pb-3 mb-4">
          <span className="p-1.5 rounded-lg bg-red-bg text-red">
            <RiAlertLine className="size-4 text-red" />
          </span>
          <h3 className="text-sm font-bold text-red uppercase tracking-wider">Danger Zone</h3>
        </div>
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <div className="font-semibold text-sm text-text">Deactivate Faculty Account</div>
            <div className="text-xs text-text3 mt-0.5 max-w-xl">
              Temporarily deactivate your teaching account. You will need to contact the academy administrator to reactivate your portal privileges.
            </div>
          </div>
          <button
            type="button"
            onClick={() => setIsDeactivateModalOpen(true)}
            className="self-start sm:self-auto px-4 py-2 rounded-xl bg-red-bg hover:bg-red/20 text-red text-xs font-semibold border border-red/30 cursor-pointer active:scale-95 transition-all"
          >
            Deactivate
          </button>
        </div>

        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-4 mt-4 border-t border-border">
          <div>
            <div className="font-semibold text-sm text-text">Sign Out of Session</div>
            <div className="text-xs text-text3 mt-0.5 max-w-xl">
              End your active faculty session on this device. You will be prompted to confirm before sign-off.
            </div>
          </div>
          <button
            type="button"
            onClick={requestLogout}
            className="self-start sm:self-auto px-4 py-2 rounded-xl bg-red-bg hover:bg-red/20 text-red text-xs font-semibold border border-red/30 cursor-pointer active:scale-95 transition-all inline-flex items-center gap-1.5"
          >
            <RiLogoutBoxRLine className="size-3.5" aria-hidden="true" />
            <span>Sign Out</span>
          </button>
        </div>
      </div>

      {/* Footer credits */}
      <div className="text-center text-xs text-text3 pt-4">
        ExceLearn LMS v2.1 · Excelsior Polytechnic Academy © JBH: 2025–2026
      </div>

      {/* ============================================================ */}
      {/* MODAL 1: EDIT PROFILE (matching teachers/teacher_profile.php) */}
      {/* ============================================================ */}
      {isEditModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs animate-in fade-in duration-200">
          <div className="relative w-full max-w-lg rounded-2xl bg-bg2 border border-border shadow-2xl overflow-hidden animate-in zoom-in-95 duration-200">
            <div
              className="h-20 relative p-4 flex items-center justify-between"
              style={{
                background: 'linear-gradient(135deg, #1e3c72 0%, #2a5298 45%, #f7971e 100%)',
              }}
            >
              <div className="text-white">
                <h3 className="font-serif text-lg font-bold">Edit Faculty Profile</h3>
                <p className="text-xs text-white/80">Update your faculty information</p>
              </div>
              <button
                type="button"
                onClick={() => setIsEditModalOpen(false)}
                className="size-8 rounded-full bg-black/30 hover:bg-black/50 text-white flex items-center justify-center cursor-pointer transition-colors"
              >
                <RiCloseLine className="size-4" />
              </button>
            </div>

            <form onSubmit={handleSaveEdit} className="p-5 sm:p-6 space-y-4 max-h-[75vh] overflow-y-auto">
              {/* Photo Upload area inside modal */}
              <div className="flex items-center gap-4 p-3 rounded-xl bg-bg3 border border-border">
                <div className="size-14 rounded-full overflow-hidden bg-bg2 flex items-center justify-center font-bold text-accent border border-border shrink-0">
                  {avatarPreview ? (
                    <img src={avatarPreview} alt="Preview" className="w-full h-full object-cover" />
                  ) : (
                    <span>{initials}</span>
                  )}
                </div>
                <div className="flex-1 min-w-0">
                  <label
                    htmlFor="teacherModalPicInput"
                    className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-accent text-white hover:bg-accent2 cursor-pointer transition-colors shadow-xs"
                  >
                    <RiCameraLine className="size-3.5" />
                    <span>Upload New Photo</span>
                  </label>
                  <input
                    type="file"
                    id="teacherModalPicInput"
                    accept="image/*"
                    className="hidden"
                    onChange={handleAvatarFileChange}
                  />
                  <p className="text-[10px] text-text3 mt-1">Allowed: JPG, PNG, WEBP. Max 2MB.</p>
                </div>
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-semibold text-text2 mb-1">First Name *</label>
                  <input
                    type="text"
                    required
                    value={editDraft.firstName}
                    onChange={(e) => setEditDraft({ ...editDraft, firstName: e.target.value })}
                    className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text focus:border-accent outline-none"
                  />
                </div>
                <div>
                  <label className="block text-xs font-semibold text-text2 mb-1">Last Name *</label>
                  <input
                    type="text"
                    required
                    value={editDraft.lastName}
                    onChange={(e) => setEditDraft({ ...editDraft, lastName: e.target.value })}
                    className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text focus:border-accent outline-none"
                  />
                </div>
              </div>

              <div>
                <label className="block text-xs font-semibold text-text2 mb-1">Middle Name</label>
                <input
                  type="text"
                  value={editDraft.middleName}
                  onChange={(e) => setEditDraft({ ...editDraft, middleName: e.target.value })}
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text focus:border-accent outline-none"
                />
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-semibold text-text2 mb-1">Email Address *</label>
                  <input
                    type="email"
                    required
                    value={editDraft.email}
                    onChange={(e) => setEditDraft({ ...editDraft, email: e.target.value })}
                    className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text focus:border-accent outline-none"
                  />
                </div>
                <div>
                  <label className="block text-xs font-semibold text-text2 mb-1">Phone Number</label>
                  <input
                    type="tel"
                    value={editDraft.phone}
                    onChange={(e) => setEditDraft({ ...editDraft, phone: e.target.value })}
                    className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text focus:border-accent outline-none"
                  />
                </div>
              </div>

              <div>
                <label className="block text-xs font-semibold text-text2 mb-1">Home Address</label>
                <textarea
                  rows={2}
                  value={editDraft.address}
                  onChange={(e) => setEditDraft({ ...editDraft, address: e.target.value })}
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text focus:border-accent outline-none resize-none"
                />
              </div>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-semibold text-text2 mb-1">Gender</label>
                  <select
                    value={editDraft.gender}
                    onChange={(e) => setEditDraft({ ...editDraft, gender: e.target.value })}
                    className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text focus:border-accent outline-none"
                  >
                    <option value="Female">Female</option>
                    <option value="Male">Male</option>
                    <option value="Other">Prefer not to say</option>
                  </select>
                </div>
                <div>
                  <label className="block text-xs font-semibold text-text2 mb-1">Age</label>
                  <input
                    type="number"
                    min={20}
                    max={100}
                    value={editDraft.age}
                    onChange={(e) => setEditDraft({ ...editDraft, age: Number(e.target.value) })}
                    className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text focus:border-accent outline-none"
                  />
                </div>
              </div>

              <div className="pt-3 border-t border-border flex items-center justify-end gap-2.5">
                <button
                  type="button"
                  onClick={() => setIsEditModalOpen(false)}
                  className="px-4 py-2 rounded-xl text-xs font-semibold text-text2 hover:text-text bg-bg3 hover:bg-bg4 border border-border cursor-pointer transition-colors"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-5 py-2 rounded-xl text-xs font-semibold text-white bg-accent hover:bg-accent2 shadow-xs cursor-pointer active:scale-95 transition-all"
                >
                  Save Changes
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* ============================================================ */}
      {/* MODAL 2: CHANGE PASSWORD (matching PHP passwordModal)         */}
      {/* ============================================================ */}
      {isPasswordModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs animate-in fade-in duration-200">
          <div className="relative w-full max-w-md rounded-2xl bg-bg2 border border-border shadow-2xl overflow-hidden animate-in zoom-in-95 duration-200">
            <div
              className="h-20 relative p-4 flex items-center justify-between"
              style={{
                background: 'linear-gradient(135deg, #1e3c72 0%, #2a5298 100%)',
              }}
            >
              <div className="text-white">
                <h3 className="font-serif text-lg font-bold">Change Password</h3>
                <p className="text-xs text-white/80">Update faculty portal password</p>
              </div>
              <button
                type="button"
                onClick={() => setIsPasswordModalOpen(false)}
                className="size-8 rounded-full bg-black/30 hover:bg-black/50 text-white flex items-center justify-center cursor-pointer transition-colors"
              >
                <RiCloseLine className="size-4" />
              </button>
            </div>

            <form onSubmit={handlePasswordSubmit} className="p-5 sm:p-6 space-y-3.5">
              {passwordError && (
                <div className="p-3 rounded-lg bg-red-bg border border-red/30 text-xs text-red flex items-center gap-2">
                  <RiAlertLine className="size-4 shrink-0" />
                  <span>{passwordError}</span>
                </div>
              )}

              <div>
                <label className="block text-xs font-semibold text-text2 mb-1">Current Password *</label>
                <input
                  type="password"
                  placeholder="Enter current password"
                  value={currentPassword}
                  onChange={(e) => setCurrentPassword(e.target.value)}
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text focus:border-accent outline-none"
                  required
                />
              </div>

              <div>
                <label className="block text-xs font-semibold text-text2 mb-1">New Password *</label>
                <input
                  type="password"
                  placeholder="Enter new password"
                  value={newPassword}
                  onChange={(e) => setNewPassword(e.target.value)}
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text focus:border-accent outline-none"
                  required
                />
                {/* 4-bar indicator */}
                {newPassword && (
                  <div className="mt-2 space-y-1">
                    <div className="flex gap-1">
                      {[1, 2, 3, 4].map((step) => (
                        <div
                          key={step}
                          className={`h-1 flex-1 rounded-full transition-colors ${
                            pwStrengthScore >= step
                              ? pwStrengthScore === 1
                                ? 'bg-red'
                                : pwStrengthScore === 2
                                ? 'bg-amber'
                                : pwStrengthScore === 3
                                ? 'bg-accent'
                                : 'bg-green'
                              : 'bg-bg4'
                          }`}
                        />
                      ))}
                    </div>
                    <div className="text-[10px] text-text3 text-right">
                      {pwStrengthScore === 1 && 'Weak'}
                      {pwStrengthScore === 2 && 'Fair'}
                      {pwStrengthScore === 3 && 'Good'}
                      {pwStrengthScore >= 4 && 'Strong password'}
                    </div>
                  </div>
                )}
              </div>

              <div>
                <label className="block text-xs font-semibold text-text2 mb-1">Confirm New Password *</label>
                <input
                  type="password"
                  placeholder="Re-enter new password"
                  value={confirmPassword}
                  onChange={(e) => setConfirmPassword(e.target.value)}
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text focus:border-accent outline-none"
                  required
                />
              </div>

              <div className="p-3 rounded-lg bg-bg3 text-[11px] text-text3 leading-relaxed flex items-start gap-2">
                <RiInformationLine className="size-4 text-accent shrink-0 mt-0.5" />
                <span>
                  Password must be at least <strong>8 characters</strong> and include uppercase, lowercase, number, and special character.
                </span>
              </div>

              <div className="pt-3 border-t border-border flex items-center justify-end gap-2.5">
                <button
                  type="button"
                  onClick={() => setIsPasswordModalOpen(false)}
                  className="px-4 py-2 rounded-xl text-xs font-semibold text-text2 hover:text-text bg-bg3 hover:bg-bg4 border border-border cursor-pointer transition-colors"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-5 py-2 rounded-xl text-xs font-semibold text-white bg-accent hover:bg-accent2 shadow-xs cursor-pointer active:scale-95 transition-all"
                >
                  Update Password
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* ============================================================ */}
      {/* MODAL 3: EDUCATION MODAL (matching update_education.php)     */}
      {/* ============================================================ */}
      {isEduModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs animate-in fade-in duration-200">
          <div className="relative w-full max-w-lg rounded-2xl bg-bg2 border border-border shadow-2xl overflow-hidden animate-in zoom-in-95 duration-200">
            <div
              className="h-20 relative p-4 flex items-center justify-between"
              style={{
                background: 'linear-gradient(135deg, #1e3c72 0%, #4d6fff 100%)',
              }}
            >
              <div className="text-white">
                <h3 className="font-serif text-lg font-bold">Edit Education & Credentials</h3>
                <p className="text-xs text-white/80">PRC Licensure & Educational Attainment</p>
              </div>
              <button
                type="button"
                onClick={() => setIsEduModalOpen(false)}
                className="size-8 rounded-full bg-black/30 hover:bg-black/50 text-white flex items-center justify-center cursor-pointer transition-colors"
              >
                <RiCloseLine className="size-4" />
              </button>
            </div>

            <form onSubmit={handleSaveEdu} className="p-5 sm:p-6 space-y-3.5 max-h-[75vh] overflow-y-auto">
              <div>
                <label className="block text-xs font-semibold text-text2 mb-1">Highest Degree</label>
                <input
                  type="text"
                  value={eduDraft.highestDegree}
                  onChange={(e) => setEduDraft({ ...eduDraft, highestDegree: e.target.value })}
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text focus:border-accent outline-none"
                />
              </div>

              <div>
                <label className="block text-xs font-semibold text-text2 mb-1">University</label>
                <input
                  type="text"
                  value={eduDraft.university}
                  onChange={(e) => setEduDraft({ ...eduDraft, university: e.target.value })}
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text focus:border-accent outline-none"
                />
              </div>

              <div>
                <label className="block text-xs font-semibold text-text2 mb-1">Bachelor's Degree</label>
                <input
                  type="text"
                  value={eduDraft.bachelorsDegree}
                  onChange={(e) => setEduDraft({ ...eduDraft, bachelorsDegree: e.target.value })}
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text focus:border-accent outline-none"
                />
              </div>

              <div>
                <label className="block text-xs font-semibold text-text2 mb-1">Licensure</label>
                <input
                  type="text"
                  value={eduDraft.licensure}
                  onChange={(e) => setEduDraft({ ...eduDraft, licensure: e.target.value })}
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text focus:border-accent outline-none"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-semibold text-text2 mb-1">LPT Year</label>
                  <input
                    type="text"
                    value={eduDraft.lptYear}
                    onChange={(e) => setEduDraft({ ...eduDraft, lptYear: e.target.value })}
                    className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text focus:border-accent outline-none"
                  />
                </div>
                <div>
                  <label className="block text-xs font-semibold text-text2 mb-1">Masters Status</label>
                  <input
                    type="text"
                    value={eduDraft.masters}
                    onChange={(e) => setEduDraft({ ...eduDraft, masters: e.target.value })}
                    className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text focus:border-accent outline-none"
                  />
                </div>
              </div>

              <div>
                <label className="block text-xs font-semibold text-text2 mb-1">
                  Certifications (comma separated)
                </label>
                <textarea
                  rows={2}
                  value={eduDraft.certifications}
                  onChange={(e) => setEduDraft({ ...eduDraft, certifications: e.target.value })}
                  className="w-full px-3 py-2 rounded-lg bg-bg3 border border-border text-xs text-text focus:border-accent outline-none resize-none"
                />
              </div>

              <div className="pt-3 border-t border-border flex items-center justify-end gap-2.5">
                <button
                  type="button"
                  onClick={() => setIsEduModalOpen(false)}
                  className="px-4 py-2 rounded-xl text-xs font-semibold text-text2 hover:text-text bg-bg3 hover:bg-bg4 border border-border cursor-pointer transition-colors"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-5 py-2 rounded-xl text-xs font-semibold text-white bg-accent hover:bg-accent2 shadow-xs cursor-pointer active:scale-95 transition-all"
                >
                  Save Changes
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* ============================================================ */}
      {/* MODAL 4: DEACTIVATE CONFIRMATION                             */}
      {/* ============================================================ */}
      {isDeactivateModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs animate-in fade-in duration-200">
          <div className="relative w-full max-w-sm rounded-2xl bg-bg2 border border-border p-6 shadow-2xl text-center space-y-4">
            <div className="size-12 rounded-full bg-red-bg text-red flex items-center justify-center mx-auto">
              <RiAlertLine className="size-6 text-red" />
            </div>
            <div>
              <h4 className="text-base font-bold text-text">Deactivate Faculty Account?</h4>
              <p className="text-xs text-text3 mt-1.5 leading-relaxed">
                Temporarily disable your faculty account. Please contact the Excelsior Polytechnic Academy administrator to reactivate.
              </p>
            </div>
            <div className="flex gap-2.5 pt-2">
              <button
                type="button"
                onClick={() => setIsDeactivateModalOpen(false)}
                className="flex-1 py-2 rounded-xl text-xs font-semibold text-text2 bg-bg3 hover:bg-bg4 border border-border cursor-pointer transition-colors"
              >
                Cancel
              </button>
              <button
                type="button"
                onClick={() => {
                  setIsDeactivateModalOpen(false);
                  showToast('Please contact the administrator to deactivate your account.');
                }}
                className="flex-1 py-2 rounded-xl text-xs font-semibold text-white bg-red hover:bg-red/90 cursor-pointer transition-colors"
              >
                Confirm
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
