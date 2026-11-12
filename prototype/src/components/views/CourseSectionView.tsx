import { useState, useMemo, useEffect, useCallback } from 'react';
import type {
  CourseOutlineData,
  CourseOutlineItem,
  CourseItemType,
} from '@/data/courseOutlineData';
import { SOCIAL_ISSUES_COURSE_DATA } from '@/data/courseOutlineData';
import {
  RiFilePdfLine,
  RiYoutubeFill,
  RiGlobalLine,
  RiListCheck2,
  RiInboxArchiveLine,
  RiCheckLine,
  RiCloseLine,
  RiArrowLeftLine,
  RiArrowRightLine,
  RiInformationLine,
  RiExternalLinkLine,
  RiDownload2Line,
  RiUpload2Line,
  RiChat3Line,
  RiZoomInLine,
  RiZoomOutLine,
  RiSparklingFill,
  RiRefreshLine,
  RiCheckDoubleLine,
  RiFileTextLine,
  RiPlayFill,
  RiCornerDownRightLine,
  RiArrowDownSLine,
  RiArrowRightSLine,
  RiPresentationLine,
  RiUser3Line,
  RiCalendarEventLine,
  RiGraduationCapLine,
} from '@remixicon/react';

interface CourseSectionViewProps {
  courseData?: CourseOutlineData;
  activeSectionId?: string | null;
  onBackToCourses: () => void;
  onNavigateToLive?: () => void;
  onNavigateToTasks?: () => void;
}

export function CourseSectionView({
  courseData = SOCIAL_ISSUES_COURSE_DATA,
  activeSectionId,
  onBackToCourses,
  onNavigateToLive: _onNavigateToLive,
  onNavigateToTasks: _onNavigateToTasks,
}: CourseSectionViewProps) {
  // Completion status of every item
  const [completedItems, setCompletedItems] = useState<Record<string, boolean>>(() => {
    const initial: Record<string, boolean> = {};
    courseData.sections.forEach((sec) => {
      sec.items.forEach((item) => {
        initial[item.id] = Boolean(item.initialCompleted);
      });
    });
    return initial;
  });

  // Active modal state
  const [activeModalItem, setActiveModalItem] = useState<CourseOutlineItem | null>(null);

  // PPT Modal State
  const [currentSlideIndex, setCurrentSlideIndex] = useState(0);

  // PDF Modal State
  const [currentPdfPage, setCurrentPdfPage] = useState(1);
  const [pdfZoom, setPdfZoom] = useState(100);

  // Quiz Modal State
  const [quizAnswers, setQuizAnswers] = useState<Record<string, number>>({});
  const [quizReflection, setQuizReflection] = useState('');
  const [quizSubmitted, setQuizSubmitted] = useState(false);
  const [quizScore, setQuizScore] = useState<number | null>(null);

  // Submission Bin Modal State
  const [_submissionFile, setSubmissionFile] = useState<File | null>(null);
  const [submissionFileName, setSubmissionFileName] = useState<string>('Bryan_Donceras_Midterm_Case_Study.pdf');
  const [submissionFileSize, setSubmissionFileSize] = useState<string>('2.4 MB');
  const [submissionStatus, setSubmissionStatus] = useState<'idle' | 'uploading' | 'submitted'>('idle');
  const [submissionComment, setSubmissionComment] = useState('');

  // Info popover for "Your progress"
  const [showProgressInfo, setShowProgressInfo] = useState(false);

  // Section collapse states
  const [collapsedSections, setCollapsedSections] = useState<Record<string, boolean>>({});

  const isSectionCollapsed = useCallback(
    (id: string) => {
      if (activeSectionId === id) return false;
      return Boolean(collapsedSections[id]);
    },
    [activeSectionId, collapsedSections]
  );

  const toggleSectionCollapse = (sectionId: string) => {
    setCollapsedSections((prev) => ({
      ...prev,
      [sectionId]: !isSectionCollapsed(sectionId),
    }));
  };

  const allSectionsCollapsed = useMemo(() => {
    return courseData.sections.length > 0 && courseData.sections.every((s) => isSectionCollapsed(s.id));
  }, [courseData.sections, isSectionCollapsed]);

  const toggleAllSections = () => {
    if (allSectionsCollapsed) {
      setCollapsedSections({});
    } else {
      const all: Record<string, boolean> = {};
      courseData.sections.forEach((s) => {
        all[s.id] = true;
      });
      setCollapsedSections(all);
    }
  };

  // Non-blocking download feedback toast
  const [downloadToast, setDownloadToast] = useState<string | null>(null);

  // Calculate overall progress
  const allItems = useMemo(() => {
    return courseData.sections.flatMap((s) => s.items);
  }, [courseData]);

  const totalCount = allItems.length;
  const completedCount = useMemo(() => {
    return allItems.filter((i) => completedItems[i.id]).length;
  }, [allItems, completedItems]);

  const progressPercentage = Math.round((completedCount / (totalCount || 1)) * 100);

  const toggleItemCompletion = useCallback((itemId: string, e?: React.MouseEvent) => {
    if (e) {
      e.stopPropagation();
    }
    setCompletedItems((prev) => ({
      ...prev,
      [itemId]: !prev[itemId],
    }));
  }, []);

  const handleOpenItem = (item: CourseOutlineItem) => {
    setActiveModalItem(item);
    // Reset specific states
    if (item.type === 'ppt') {
      setCurrentSlideIndex(0);
    } else if (item.type === 'pdf') {
      setCurrentPdfPage(1);
      setPdfZoom(100);
    } else if (item.type === 'quiz') {
      setQuizAnswers({});
      setQuizReflection('');
      setQuizSubmitted(false);
      setQuizScore(null);
    } else if (item.type === 'submission') {
      setSubmissionStatus(completedItems[item.id] ? 'submitted' : 'idle');
    }
  };

  const handleCloseModal = useCallback(() => {
    setActiveModalItem(null);
  }, []);

  // Keyboard Escape listener & PPT arrow navigation
  useEffect(() => {
    if (!activeModalItem) return;

    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === 'Escape') {
        handleCloseModal();
      } else if (activeModalItem.type === 'ppt' && activeModalItem.slides) {
        if (e.key === 'ArrowRight' || e.key === ' ') {
          setCurrentSlideIndex((prev) => Math.min(activeModalItem.slides!.length - 1, prev + 1));
        } else if (e.key === 'ArrowLeft') {
          setCurrentSlideIndex((prev) => Math.max(0, prev - 1));
        }
      }
    };

    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [activeModalItem, handleCloseModal]);

  // Lock body scroll when any modal is open
  useEffect(() => {
    if (activeModalItem) {
      const originalOverflow = document.body.style.overflow;
      document.body.style.overflow = 'hidden';
      return () => {
        document.body.style.overflow = originalOverflow;
      };
    }
  }, [activeModalItem]);

  // Scroll to activeSectionId when changed
  useEffect(() => {
    if (activeSectionId) {
      const el = document.getElementById(activeSectionId);
      if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    }
  }, [activeSectionId]);

  // Quiz submission handler
  const handleQuizSubmit = (item: CourseOutlineItem) => {
    if (!item.quizQuestions) return;
    let score = 0;
    item.quizQuestions.forEach((q) => {
      if (quizAnswers[q.id] === q.correctIndex) {
        score += 1;
      }
    });
    setQuizScore(score);
    setQuizSubmitted(true);
    // Automatically mark completed
    setCompletedItems((prev) => ({ ...prev, [item.id]: true }));
  };

  // Submission upload handler
  const handleFileUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      setSubmissionFile(file);
      setSubmissionFileName(file.name);
      setSubmissionFileSize(`${(file.size / (1024 * 1024)).toFixed(1)} MB`);
    }
  };

  const handleConfirmSubmission = (itemId: string) => {
    setSubmissionStatus('uploading');
    setTimeout(() => {
      setSubmissionStatus('submitted');
      setCompletedItems((prev) => ({ ...prev, [itemId]: true }));
    }, 700);
  };

  // Trigger non-blocking simulated PDF download
  const handleDownloadPdf = (title: string) => {
    setDownloadToast(`Saved "${title}.pdf" to your downloads.`);
    setTimeout(() => {
      setDownloadToast(null);
    }, 3500);
  };

  // Section title & badge parser
  const parseSectionTitle = (rawTitle: string): { badge: string; title: string } => {
    if (rawTitle === 'General') {
      return { badge: 'Course Essentials', title: 'Orientation & Syllabus' };
    }
    if (rawTitle.includes('|')) {
      const [badge, ...rest] = rawTitle.split('|');
      return { badge: badge.trim(), title: rest.join('|').trim() };
    }
    if (rawTitle.startsWith('WEEK ') && rawTitle.includes(' - ')) {
      const [badge, ...rest] = rawTitle.split(' - ');
      return { badge: badge.trim(), title: rest.join(' - ').trim() };
    }
    if (rawTitle === 'GROUPS') {
      return { badge: 'Team Project', title: 'Collaborative Groups & Topics' };
    }
    return { badge: 'Section', title: rawTitle };
  };

  const getSectionBadgeStyle = (id: string): string => {
    if (id.includes('general')) {
      return 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20';
    }
    if (id.includes('week-1-2')) {
      return 'bg-accent/10 text-accent border border-accent/20';
    }
    if (id.includes('week-3')) {
      return 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20';
    }
    if (id.includes('week-4')) {
      return 'bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 border border-cyan-500/20';
    }
    if (id.includes('groups')) {
      return 'bg-purple-500/10 text-purple-600 dark:text-purple-400 border border-purple-500/20';
    }
    if (id.includes('week-6-10')) {
      return 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20';
    }
    return 'bg-bg3 text-text3 border border-border/60';
  };

  const getActionLabel = (type: CourseItemType, isDone: boolean): string => {
    if (isDone) return 'Review';
    switch (type) {
      case 'ppt':
        return 'View Slides';
      case 'pdf':
        return 'Read PDF';
      case 'yt':
        return 'Watch Video';
      case 'quiz':
        return 'Start Quiz';
      case 'submission':
        return 'Submit Work';
      case 'link':
        return 'Read Article';
      case 'announcement':
        return 'Read Notice';
      default:
        return 'Open';
    }
  };

  // Modern Apple-grade item icon renderer
  const renderItemIcon = (type: CourseItemType) => {
    switch (type) {
      case 'announcement':
        return (
          <div className="size-11 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 flex items-center justify-center shrink-0">
            <RiChat3Line className="size-5" aria-hidden="true" />
          </div>
        );
      case 'ppt':
        return (
          <div className="size-11 rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border border-indigo-500/20 flex items-center justify-center shrink-0">
            <RiPresentationLine className="size-5" aria-hidden="true" />
          </div>
        );
      case 'pdf':
        return (
          <div className="size-11 rounded-xl bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 flex items-center justify-center shrink-0">
            <RiFileTextLine className="size-5" aria-hidden="true" />
          </div>
        );
      case 'link':
        return (
          <div className="size-11 rounded-xl bg-teal-500/10 text-teal-600 dark:text-teal-400 border border-teal-500/20 flex items-center justify-center shrink-0">
            <RiGlobalLine className="size-5" aria-hidden="true" />
          </div>
        );
      case 'yt':
        return (
          <div className="size-11 rounded-xl bg-red-500/10 text-red-600 dark:text-red-400 border border-red-500/20 flex items-center justify-center shrink-0">
            <RiYoutubeFill className="size-5" aria-hidden="true" />
          </div>
        );
      case 'quiz':
        return (
          <div className="size-11 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 flex items-center justify-center shrink-0">
            <RiListCheck2 className="size-5" aria-hidden="true" />
          </div>
        );
      case 'submission':
        return (
          <div className="size-11 rounded-xl bg-purple-500/10 text-purple-600 dark:text-purple-400 border border-purple-500/20 flex items-center justify-center shrink-0">
            <RiInboxArchiveLine className="size-5" aria-hidden="true" />
          </div>
        );
      default:
        return (
          <div className="size-11 rounded-xl bg-bg3 text-text3 border border-border/60 flex items-center justify-center shrink-0">
            <RiFileTextLine className="size-5" aria-hidden="true" />
          </div>
        );
    }
  };

  return (
    <div className="space-y-6 pb-20 w-full">
      {/* ============================================================ */}
      {/* 1. Modern Course Hero Card (Elevated, Spacious)             */}
      {/* ============================================================ */}
      <div className="rounded-2xl border border-border bg-gradient-to-br from-bg2 via-bg2 to-bg3/60 p-5 sm:p-7 shadow-xs space-y-5">
        {/* Top: Breadcrumb & Meta Badges */}
        <div className="flex flex-wrap items-center justify-between gap-3">
          <nav
            aria-label="Breadcrumb navigation"
            className="flex flex-wrap items-center gap-1.5 text-xs text-text2 font-medium"
          >
            <button
              type="button"
              onClick={onBackToCourses}
              className="px-2.5 py-1 rounded-lg bg-bg3/80 hover:bg-bg3 hover:text-accent text-text2 transition-colors cursor-pointer"
            >
              Dashboard
            </button>
            <span className="text-text3 select-none">›</span>
            <button
              type="button"
              onClick={onBackToCourses}
              className="px-2.5 py-1 rounded-lg bg-bg3/80 hover:bg-bg3 hover:text-accent text-text2 transition-colors cursor-pointer"
            >
              My Courses
            </button>
            <span className="text-text3 select-none">›</span>
            <span className="px-2.5 py-1 rounded-lg bg-accent/10 text-accent font-semibold truncate max-w-[240px] sm:max-w-sm">
              {courseData.courseTitle}
            </span>
          </nav>

          <div className="flex items-center gap-2">
            <span className="px-2.5 py-1 rounded-full text-xs font-bold bg-accent/10 text-accent border border-accent/20">
              {courseData.courseCode}
            </span>
            <span className="px-2.5 py-1 rounded-full text-xs font-semibold bg-bg3 text-text3 border border-border/60">
              3.0 Units
            </span>
          </div>
        </div>

        {/* Middle: Course Title & Instructor Meta */}
        <div className="space-y-2.5">
          <h1 className="text-2xl sm:text-3xl lg:text-4xl font-bold tracking-tight text-text leading-tight">
            {courseData.fullTitle}
          </h1>

          <div className="flex flex-wrap items-center gap-3 sm:gap-4 text-xs text-text2 pt-0.5">
            <div className="flex items-center gap-1.5 font-semibold text-text">
              <div className="size-6 rounded-full bg-accent/15 text-accent flex items-center justify-center text-[10px] font-bold">
                <RiUser3Line className="size-3.5" />
              </div>
              <span>{courseData.instructor}</span>
            </div>
            <span className="size-1 rounded-full bg-border" aria-hidden="true" />
            <div className="flex items-center gap-1.5">
              <RiGraduationCapLine className="size-3.5 text-text3" />
              <span>{courseData.term}</span>
            </div>
            <span className="size-1 rounded-full bg-border" aria-hidden="true" />
            <div className="flex items-center gap-1.5">
              <RiCalendarEventLine className="size-3.5 text-text3" />
              <span>{courseData.academicYear}</span>
            </div>
          </div>
        </div>

        {/* Bottom: Progress Bar & Section Controls */}
        <div className="pt-4 border-t border-border/70 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div className="space-y-1.5 flex-1 max-w-xl">
            <div className="relative flex items-center justify-between text-xs">
              <button
                type="button"
                onClick={() => setShowProgressInfo(!showProgressInfo)}
                className="inline-flex items-center gap-1.5 font-semibold text-text hover:text-accent cursor-pointer group"
                aria-label="View completion progress details"
              >
                <span>Course Completion Progress</span>
                <RiInformationLine className="size-3.5 text-text3 group-hover:text-accent" />
              </button>

              <span className="font-bold text-accent tabular-nums">
                {completedCount} of {totalCount} completed ({progressPercentage}%)
              </span>

              {/* Info Popover */}
              {showProgressInfo && (
                <div
                  className="absolute left-0 top-6 w-72 rounded-xl bg-bg2 border border-border shadow-xl p-3.5 z-30 text-xs space-y-2 animate-in fade-in zoom-in-95 duration-150"
                  onClick={(e) => e.stopPropagation()}
                >
                  <div className="flex items-center justify-between font-bold text-text">
                    <span>DepEd Progress Standards</span>
                    <button
                      type="button"
                      onClick={() => setShowProgressInfo(false)}
                      className="p-1 rounded text-text3 hover:text-text cursor-pointer"
                    >
                      <RiCloseLine className="size-3.5" />
                    </button>
                  </div>
                  <p className="text-text3 text-[11px] leading-relaxed">
                    Items with dashed square checkboxes are automatically marked complete once opened, viewed, or submitted. Solid square checkboxes can be checked manually.
                  </p>
                  <div className="pt-1 text-[11px] font-semibold text-accent flex items-center justify-between">
                    <span>Completion Rate</span>
                    <span className="font-mono">{progressPercentage}%</span>
                  </div>
                </div>
              )}
            </div>

            <div className="w-full h-2.5 bg-bg3 rounded-full overflow-hidden border border-border/50">
              <div
                className="h-full bg-gradient-to-r from-accent to-emerald-500 rounded-full transition-all duration-500 ease-out"
                style={{ width: `${progressPercentage}%` }}
              />
            </div>
          </div>

          <div className="flex items-center gap-2 self-start sm:self-auto shrink-0">
            <button
              type="button"
              onClick={toggleAllSections}
              className="px-3 py-1.5 rounded-lg border border-border bg-bg3/80 hover:bg-bg3 text-xs font-semibold text-text transition-colors cursor-pointer"
            >
              {allSectionsCollapsed ? 'Expand All Sections' : 'Collapse All Sections'}
            </button>
          </div>
        </div>
      </div>

      {/* ============================================================ */}
      {/* 2. Elevated Course Section Cards (Spacious, Clear Hierarchy) */}
      {/* ============================================================ */}
      <div className="space-y-6 pt-1">
        {courseData.sections.map((sec) => {
          const parsed = parseSectionTitle(sec.title);
          const isSecCollapsed = isSectionCollapsed(sec.id);
          const secDoneCount = sec.items.filter((item) => completedItems[item.id]).length;

          return (
            <section
              key={sec.id}
              id={sec.id}
              className="scroll-mt-24 rounded-2xl border border-border/80 bg-bg2 shadow-xs overflow-hidden transition-all duration-200"
            >
              {/* Section Header Bar */}
              <div
                onClick={() => toggleSectionCollapse(sec.id)}
                className="p-4 sm:px-6 sm:py-4.5 bg-bg3/30 hover:bg-bg3/60 transition-colors border-b border-border/60 flex items-center justify-between gap-3 cursor-pointer select-none"
              >
                <div className="flex items-center gap-3 min-w-0">
                  <span className={`px-2.5 py-1 rounded-lg text-xs font-bold uppercase tracking-wider ${getSectionBadgeStyle(sec.id)}`}>
                    {parsed.badge}
                  </span>
                  <h2 className="text-base sm:text-lg font-bold text-text truncate">
                    {parsed.title}
                  </h2>
                </div>

                <div className="flex items-center gap-3 shrink-0">
                  <span className="text-xs font-semibold text-text3 hidden sm:inline">
                    {secDoneCount} of {sec.items.length} completed
                  </span>
                  <div
                    className={`p-1.5 rounded-lg text-text3 hover:text-text transition-transform duration-200 ${
                      isSecCollapsed ? '' : 'rotate-180'
                    }`}
                  >
                    <RiArrowDownSLine className="size-4" />
                  </div>
                </div>
              </div>

              {/* Items List in this Section */}
              {!isSecCollapsed && (
                <div className="p-3 sm:p-5 space-y-2.5">
                  {sec.items.map((item) => {
                    const isDone = Boolean(completedItems[item.id]);
                    const isAutomatic =
                      item.completionType === 'automatic' ||
                      item.type === 'quiz' ||
                      item.type === 'submission' ||
                      item.type === 'ppt';

                    return (
                      <div
                        key={item.id}
                        onClick={() => handleOpenItem(item)}
                        className={`group rounded-xl p-3.5 sm:p-4 border transition-all cursor-pointer select-none flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4 ${
                          isDone
                            ? 'bg-bg2/40 border-border/40 hover:border-border hover:bg-bg3/40'
                            : 'bg-bg2 border-border/70 hover:border-accent/60 hover:bg-bg3/60 shadow-2xs hover:shadow-xs'
                        } ${item.isIndented ? 'ml-4 sm:ml-8 border-l-4 border-l-accent/60 bg-accent/5' : ''}`}
                      >
                        {/* Left: Icon + Title + Meta + Subtitle */}
                        <div className="flex items-start gap-3.5 min-w-0 flex-1">
                          {item.isIndented && (
                            <RiCornerDownRightLine className="size-4 text-accent shrink-0 mt-3" aria-hidden="true" />
                          )}

                          {renderItemIcon(item.type)}

                          <div className="min-w-0 flex-1 space-y-1">
                            <div className="flex flex-wrap items-center gap-2">
                              <span
                                className={`text-sm sm:text-base font-semibold leading-snug transition-colors ${
                                  isDone
                                    ? 'text-text2 line-through decoration-text3/50'
                                    : 'text-text group-hover:text-accent'
                                }`}
                              >
                                {item.title}
                              </span>
                              {item.badge && (
                                <span className="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-bg3 text-text3 border border-border/60">
                                  {item.badge}
                                </span>
                              )}
                            </div>

                            {item.subtitle && (
                              <p className="text-xs text-text3 font-normal leading-relaxed">
                                {item.subtitle}
                              </p>
                            )}
                          </div>
                        </div>

                        {/* Right: Action Hint & Completion Checkbox */}
                        <div className="flex items-center justify-between sm:justify-end gap-3 shrink-0 pt-2 sm:pt-0 border-t sm:border-t-0 border-border/40">
                          <span className="inline-flex items-center gap-1 text-xs font-semibold text-text3 group-hover:text-accent transition-colors">
                            <span>{getActionLabel(item.type, isDone)}</span>
                            <RiArrowRightSLine className="size-3.5 group-hover:translate-x-0.5 transition-transform" />
                          </span>

                          <button
                            type="button"
                            onClick={(e) => toggleItemCompletion(item.id, e)}
                            title={
                              isDone
                                ? 'Mark as incomplete'
                                : isAutomatic
                                ? 'Automatically marked on completion'
                                : 'Click to mark as complete'
                            }
                            aria-label={`Mark ${item.title} as ${isDone ? 'incomplete' : 'complete'}`}
                            className={`size-6 rounded-lg flex items-center justify-center transition-all cursor-pointer ${
                              isAutomatic
                                ? 'border-2 border-dashed border-text3/40 hover:border-accent'
                                : 'border-2 border-text3/50 hover:border-accent'
                            } ${
                              isDone ? 'bg-green/15 border-green text-green' : 'bg-bg hover:bg-bg3'
                            }`}
                          >
                            <RiCheckLine
                              className={`size-4 stroke-[2.5] text-green transition-all ${
                                isDone ? 'opacity-100 scale-100' : 'opacity-0 scale-75'
                              }`}
                            />
                          </button>
                        </div>
                      </div>
                    );
                  })}
                </div>
              )}
            </section>
          );
        })}
      </div>

      {/* ============================================================ */}
      {/* 3. MODAL EXPERIENCES (PPT, YT, PDF, LINK, QUIZ, SUBMISSION)  */}
      {/* ============================================================ */}

      {/* ----------------- PPT / PRESENTATION MODAL ----------------- */}
      {activeModalItem && activeModalItem.type === 'ppt' && activeModalItem.slides && (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-5 bg-black/70 backdrop-blur-xs animate-in fade-in duration-200"
          onClick={handleCloseModal}
        >
          <div
            className="w-full max-w-3xl rounded-2xl bg-bg2 border border-border shadow-2xl overflow-hidden flex flex-col max-h-[90vh]"
            onClick={(e) => e.stopPropagation()}
          >
            {/* Modal Header */}
            <div className="p-4 border-b border-border flex items-center justify-between bg-bg3/50">
              <div className="flex items-center gap-2.5 min-w-0">
                <div className="size-7 rounded bg-[#1e293b] text-white flex items-center justify-center font-bold text-[10px]">
                  H5P
                </div>
                <div className="min-w-0">
                  <h3 className="font-bold text-sm sm:text-base text-text truncate">
                    {activeModalItem.title}
                  </h3>
                  <p className="text-[11px] text-text3">Interactive Presentation Viewer · Use ← → arrows to navigate</p>
                </div>
              </div>

              <div className="flex items-center gap-2">
                <span className="text-xs font-mono font-semibold px-2 py-1 rounded bg-bg3 text-text2">
                  Slide {currentSlideIndex + 1} of {activeModalItem.slides.length}
                </span>
                <button
                  type="button"
                  onClick={handleCloseModal}
                  aria-label="Close presentation"
                  className="p-1.5 rounded-lg text-text3 hover:text-text hover:bg-bg3 cursor-pointer"
                >
                  <RiCloseLine className="size-5" />
                </button>
              </div>
            </div>

            {/* Slide Frame Canvas */}
            <div className="p-6 sm:p-8 flex-1 overflow-y-auto flex flex-col justify-between bg-gradient-to-br from-bg via-bg2 to-bg3/40 min-h-[300px]">
              <div className="space-y-4">
                <div className="flex items-center justify-between">
                  {activeModalItem.slides[currentSlideIndex].badge && (
                    <span className="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-accent/10 text-accent">
                      {activeModalItem.slides[currentSlideIndex].badge}
                    </span>
                  )}
                  <span className="text-xs text-text3 font-mono">
                    Module Topic {currentSlideIndex + 1}
                  </span>
                </div>

                <h2 className="text-lg sm:text-2xl font-bold font-serif text-text">
                  {activeModalItem.slides[currentSlideIndex].title}
                </h2>

                <ul className="space-y-2.5 pt-2 text-xs sm:text-sm text-text2">
                  {activeModalItem.slides[currentSlideIndex].bullets.map((bullet, idx) => (
                    <li key={idx} className="flex items-start gap-2.5">
                      <span className="size-1.5 rounded-full bg-accent shrink-0 mt-2" />
                      <span>{bullet}</span>
                    </li>
                  ))}
                </ul>

                {activeModalItem.slides[currentSlideIndex].notes && (
                  <div className="mt-4 p-3 rounded-xl bg-accent/5 border border-accent/20 text-xs text-text2">
                    <span className="font-bold text-accent mr-1">Faculty Note:</span>
                    {activeModalItem.slides[currentSlideIndex].notes}
                  </div>
                )}
              </div>

              {/* Navigation Controls */}
              <div className="pt-6 border-t border-border flex items-center justify-between gap-4 mt-6">
                <button
                  type="button"
                  disabled={currentSlideIndex === 0}
                  onClick={() => setCurrentSlideIndex((prev) => Math.max(0, prev - 1))}
                  className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-border bg-bg3/70 hover:bg-bg3 text-xs font-semibold text-text disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
                >
                  <RiArrowLeftLine className="size-3.5" />
                  <span>Previous</span>
                </button>

                {/* Slide indicator dots */}
                <div className="flex items-center gap-1.5">
                  {activeModalItem.slides.map((_, idx) => (
                    <button
                      key={idx}
                      type="button"
                      onClick={() => setCurrentSlideIndex(idx)}
                      className={`size-2 rounded-full transition-all cursor-pointer ${
                        currentSlideIndex === idx ? 'bg-accent w-5' : 'bg-border hover:bg-text3'
                      }`}
                      aria-label={`Go to slide ${idx + 1}`}
                    />
                  ))}
                </div>

                {currentSlideIndex < activeModalItem.slides.length - 1 ? (
                  <button
                    type="button"
                    onClick={() => setCurrentSlideIndex((prev) => Math.min(activeModalItem.slides!.length - 1, prev + 1))}
                    className="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-accent text-white text-xs font-semibold hover:brightness-105 cursor-pointer shadow-xs"
                  >
                    <span>Next</span>
                    <RiArrowRightLine className="size-3.5" />
                  </button>
                ) : (
                  <button
                    type="button"
                    onClick={() => {
                      toggleItemCompletion(activeModalItem.id);
                      handleCloseModal();
                    }}
                    className="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-green text-white text-xs font-semibold hover:brightness-105 cursor-pointer shadow-xs"
                  >
                    <RiCheckLine className="size-3.5" />
                    <span>Complete Slide Deck</span>
                  </button>
                )}
              </div>
            </div>
          </div>
        </div>
      )}

      {/* ----------------- YOUTUBE VIDEO MODAL ----------------- */}
      {activeModalItem && activeModalItem.type === 'yt' && (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-5 bg-black/75 backdrop-blur-xs animate-in fade-in duration-200"
          onClick={handleCloseModal}
        >
          <div
            className="w-full max-w-3xl rounded-2xl bg-bg2 border border-border shadow-2xl overflow-hidden flex flex-col max-h-[92vh]"
            onClick={(e) => e.stopPropagation()}
          >
            {/* Header */}
            <div className="p-4 border-b border-border flex items-center justify-between bg-bg3/50">
              <div className="flex items-center gap-2.5 min-w-0">
                <RiYoutubeFill className="size-6 text-red-600 shrink-0" />
                <div className="min-w-0">
                  <h3 className="font-bold text-sm sm:text-base text-text truncate">
                    {activeModalItem.title}
                  </h3>
                  <p className="text-[11px] text-text3">YouTube Video Lesson · Duration: {activeModalItem.videoDuration || '12:00'}</p>
                </div>
              </div>
              <button
                type="button"
                onClick={handleCloseModal}
                aria-label="Close video"
                className="p-1.5 rounded-lg text-text3 hover:text-text hover:bg-bg3 cursor-pointer"
              >
                <RiCloseLine className="size-5" />
              </button>
            </div>

            {/* Video Frame */}
            <div className="relative w-full aspect-video bg-black flex items-center justify-center overflow-hidden">
              {activeModalItem.youtubeId ? (
                <iframe
                  className="w-full h-full"
                  src={`https://www.youtube-nocookie.com/embed/${activeModalItem.youtubeId}?autoplay=1&rel=0`}
                  title={activeModalItem.title}
                  allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                  allowFullScreen
                />
              ) : (
                <div className="text-center p-6 text-white space-y-2">
                  <RiYoutubeFill className="size-14 text-red-500 mx-auto" />
                  <p className="text-sm font-semibold">Video Stream Player</p>
                  <p className="text-xs text-white/70">Sample educational computing lecture stream.</p>
                </div>
              )}
            </div>

            {/* Video Notes & Timestamps */}
            {activeModalItem.videoNotes && (
              <div className="p-4 bg-bg space-y-2 border-t border-border">
                <h4 className="text-xs font-bold uppercase tracking-wider text-text3">
                  Lecture Chapters & Key Timestamps
                </h4>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs text-text2">
                  {activeModalItem.videoNotes.map((note, idx) => (
                    <div key={idx} className="flex items-center gap-2 p-1.5 rounded-lg bg-bg2 border border-border/60">
                      <RiPlayFill className="size-3 text-accent shrink-0" />
                      <span className="truncate">{note}</span>
                    </div>
                  ))}
                </div>
              </div>
            )}

            {/* Footer */}
            <div className="p-4 border-t border-border flex items-center justify-between bg-bg2">
              <a
                href={activeModalItem.youtubeUrl || `https://youtube.com/watch?v=${activeModalItem.youtubeId}`}
                target="_blank"
                rel="noreferrer noopener"
                className="inline-flex items-center gap-1.5 text-xs text-accent hover:underline font-semibold"
              >
                <span>Open in YouTube</span>
                <RiExternalLinkLine className="size-3.5" />
              </a>

              <button
                type="button"
                onClick={() => {
                  toggleItemCompletion(activeModalItem.id);
                  handleCloseModal();
                }}
                className="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-accent text-white text-xs font-semibold hover:brightness-105 cursor-pointer shadow-xs"
              >
                <RiCheckLine className="size-3.5" />
                <span>Mark Video as Watched</span>
              </button>
            </div>
          </div>
        </div>
      )}

      {/* ----------------- PDF READER MODAL ----------------- */}
      {activeModalItem && activeModalItem.type === 'pdf' && (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-5 bg-black/75 backdrop-blur-xs animate-in fade-in duration-200"
          onClick={handleCloseModal}
        >
          <div
            className="w-full max-w-4xl rounded-2xl bg-bg2 border border-border shadow-2xl overflow-hidden flex flex-col max-h-[92vh]"
            onClick={(e) => e.stopPropagation()}
          >
            {/* Header with Toolbar */}
            <div className="p-4 border-b border-border flex items-center justify-between bg-bg3/60">
              <div className="flex items-center gap-2.5 min-w-0">
                <div className="size-8 rounded-lg bg-red-500/10 text-red flex items-center justify-center shrink-0">
                  <RiFilePdfLine className="size-4" />
                </div>
                <div className="min-w-0">
                  <h3 className="font-bold text-sm sm:text-base text-text truncate">
                    {activeModalItem.title}
                  </h3>
                  <p className="text-[11px] text-text3">
                    DepEd Digital PDF Reader · {activeModalItem.pdfPages || 8} Pages
                  </p>
                </div>
              </div>

              {/* PDF Toolbar */}
              <div className="flex items-center gap-2">
                <div className="hidden sm:flex items-center gap-1 bg-bg px-2 py-1 rounded-lg border border-border text-xs text-text2">
                  <button
                    type="button"
                    onClick={() => setPdfZoom((z) => Math.max(75, z - 15))}
                    title="Zoom Out"
                    aria-label="Zoom Out"
                    className="p-1 hover:text-text cursor-pointer"
                  >
                    <RiZoomOutLine className="size-3.5" />
                  </button>
                  <span className="font-mono text-[11px]">{pdfZoom}%</span>
                  <button
                    type="button"
                    onClick={() => setPdfZoom((z) => Math.min(150, z + 15))}
                    title="Zoom In"
                    aria-label="Zoom In"
                    className="p-1 hover:text-text cursor-pointer"
                  >
                    <RiZoomInLine className="size-3.5" />
                  </button>
                </div>

                <button
                  type="button"
                  onClick={handleCloseModal}
                  aria-label="Close document"
                  className="p-1.5 rounded-lg text-text3 hover:text-text hover:bg-bg3 cursor-pointer"
                >
                  <RiCloseLine className="size-5" />
                </button>
              </div>
            </div>

            {/* Document Content Canvas */}
            <div className="p-6 sm:p-10 flex-1 overflow-y-auto bg-bg space-y-6">
              {/* Document Paper Simulation */}
              <div
                className="max-w-2xl mx-auto bg-bg2 rounded-xl border border-border p-6 sm:p-10 shadow-sm space-y-6 transition-all"
                style={{ fontSize: `${(pdfZoom / 100) * 14}px` }}
              >
                {/* School Header */}
                <div className="border-b border-border/80 pb-4 text-center space-y-1">
                  <div className="text-[10px] uppercase font-bold tracking-widest text-accent">
                    ExceLearn Senior High Academic Portal
                  </div>
                  <h2 className="font-serif font-bold text-lg sm:text-xl text-text">
                    {activeModalItem.title}
                  </h2>
                  <p className="text-xs text-text3">
                    Curriculum Document · AY 2024–2025 · BSCS / STEM Track
                  </p>
                </div>

                {/* Document Summary */}
                {activeModalItem.pdfSummary && (
                  <div className="p-4 rounded-xl bg-accent/5 border border-accent/20 space-y-1 text-xs">
                    <div className="font-bold text-accent">Executive Overview & Learning Objectives</div>
                    <p className="text-text2 leading-relaxed">{activeModalItem.pdfSummary}</p>
                  </div>
                )}

                {/* Excerpt Paragraphs */}
                <div className="space-y-4 text-xs sm:text-sm text-text2 leading-relaxed">
                  {activeModalItem.pdfExcerpt ? (
                    activeModalItem.pdfExcerpt.map((para, idx) => (
                      <div key={idx} className="p-3 rounded-lg bg-bg3/40 border border-border/60">
                        <p className="font-medium text-text">{para}</p>
                      </div>
                    ))
                  ) : (
                    <p>
                      This resource is provided in full compliance with DepEd and CHED educational standards for Senior High School and undergraduate computing courses.
                    </p>
                  )}
                </div>

                {/* Watermark Footer */}
                <div className="pt-6 border-t border-border flex items-center justify-between text-[11px] text-text3">
                  <span>Student: Bryan Donceras (STEM 11-A)</span>
                  <span>Page {currentPdfPage} of {activeModalItem.pdfPages || 8}</span>
                </div>
              </div>
            </div>

            {/* Footer Pagination Controls */}
            <div className="p-4 border-t border-border flex items-center justify-between bg-bg2">
              <div className="flex items-center gap-2">
                <button
                  type="button"
                  disabled={currentPdfPage <= 1}
                  onClick={() => setCurrentPdfPage((p) => Math.max(1, p - 1))}
                  className="px-2.5 py-1 rounded-md border border-border bg-bg3 text-xs font-semibold text-text disabled:opacity-40 cursor-pointer"
                >
                  Previous Page
                </button>
                <span className="text-xs font-mono text-text2">
                  Page {currentPdfPage} / {activeModalItem.pdfPages || 8}
                </span>
                <button
                  type="button"
                  disabled={currentPdfPage >= (activeModalItem.pdfPages || 8)}
                  onClick={() => setCurrentPdfPage((p) => Math.min(activeModalItem.pdfPages || 8, p + 1))}
                  className="px-2.5 py-1 rounded-md border border-border bg-bg3 text-xs font-semibold text-text disabled:opacity-40 cursor-pointer"
                >
                  Next Page
                </button>
              </div>

              <div className="flex items-center gap-2">
                <button
                  type="button"
                  onClick={() => handleDownloadPdf(activeModalItem.title)}
                  className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-border bg-bg3 hover:bg-bg4 text-xs font-semibold text-text cursor-pointer"
                >
                  <RiDownload2Line className="size-3.5" />
                  <span className="hidden sm:inline">Download</span>
                </button>

                <button
                  type="button"
                  onClick={() => {
                    toggleItemCompletion(activeModalItem.id);
                    handleCloseModal();
                  }}
                  className="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-accent text-white text-xs font-semibold hover:brightness-105 cursor-pointer shadow-xs"
                >
                  <RiCheckLine className="size-3.5" />
                  <span>Mark PDF as Read</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      )}

      {/* ----------------- WEB / ESSAY LINK READER MODAL ----------------- */}
      {activeModalItem && (activeModalItem.type === 'link' || activeModalItem.type === 'announcement') && (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-5 bg-black/75 backdrop-blur-xs animate-in fade-in duration-200"
          onClick={handleCloseModal}
        >
          <div
            className="w-full max-w-2xl rounded-2xl bg-bg2 border border-border shadow-2xl overflow-hidden flex flex-col max-h-[90vh]"
            onClick={(e) => e.stopPropagation()}
          >
            {/* Header */}
            <div className="p-4 border-b border-border flex items-center justify-between bg-bg3/60">
              <div className="flex items-center gap-2.5 min-w-0">
                <div className="size-8 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                  <RiGlobalLine className="size-4" />
                </div>
                <div className="min-w-0">
                  <h3 className="font-bold text-sm sm:text-base text-text truncate">
                    {activeModalItem.title}
                  </h3>
                  <div className="flex items-center gap-2 text-[11px] text-text3">
                    {activeModalItem.author && <span>{activeModalItem.author}</span>}
                    {activeModalItem.readTime && <span>· {activeModalItem.readTime}</span>}
                  </div>
                </div>
              </div>

              <button
                type="button"
                onClick={handleCloseModal}
                aria-label="Close article"
                className="p-1.5 rounded-lg text-text3 hover:text-text hover:bg-bg3 cursor-pointer"
              >
                <RiCloseLine className="size-5" />
              </button>
            </div>

            {/* Article Content */}
            <div className="p-6 sm:p-8 flex-1 overflow-y-auto space-y-4 text-xs sm:text-sm text-text2 leading-relaxed">
              {activeModalItem.articleContent ? (
                activeModalItem.articleContent.map((p, idx) => (
                  <p key={idx} className="leading-relaxed">
                    {p}
                  </p>
                ))
              ) : (
                <p>
                  Official course reading resource. Please read thoroughly for upcoming quizzes and class discussions.
                </p>
              )}

              <div className="p-4 rounded-xl bg-accent/5 border border-accent/20 mt-4 space-y-1">
                <div className="font-bold text-accent text-xs">Competency Reflection</div>
                <p className="text-[11px] text-text3">
                  How does this reading apply to your upcoming midterm ethical case analysis? Keep notes in your project journal.
                </p>
              </div>
            </div>

            {/* Footer */}
            <div className="p-4 border-t border-border flex items-center justify-between bg-bg3/40">
              <span className="text-xs text-text3 font-medium">ExceLearn Course Library</span>

              <button
                type="button"
                onClick={() => {
                  toggleItemCompletion(activeModalItem.id);
                  handleCloseModal();
                }}
                className="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-accent text-white text-xs font-semibold hover:brightness-105 cursor-pointer shadow-xs"
              >
                <RiCheckLine className="size-3.5" />
                <span>Mark as Finished</span>
              </button>
            </div>
          </div>
        </div>
      )}

      {/* ----------------- STATIC ANSWERABLE QUIZ MODAL ----------------- */}
      {activeModalItem && activeModalItem.type === 'quiz' && activeModalItem.quizQuestions && (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-5 bg-black/75 backdrop-blur-xs animate-in fade-in duration-200"
          onClick={handleCloseModal}
        >
          <div
            className="w-full max-w-2xl rounded-2xl bg-bg2 border border-border shadow-2xl overflow-hidden flex flex-col max-h-[92vh]"
            onClick={(e) => e.stopPropagation()}
          >
            {/* Header */}
            <div className="p-4 border-b border-border flex items-center justify-between bg-bg3/60">
              <div className="flex items-center gap-2.5 min-w-0">
                <div className="size-8 rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                  <RiListCheck2 className="size-4" />
                </div>
                <div className="min-w-0">
                  <h3 className="font-bold text-sm sm:text-base text-text truncate">
                    {activeModalItem.title}
                  </h3>
                  <p className="text-[11px] text-text3">
                    Automated Academic Assessment · {activeModalItem.quizQuestions.length} Questions
                  </p>
                </div>
              </div>

              <button
                type="button"
                onClick={handleCloseModal}
                aria-label="Close quiz"
                className="p-1.5 rounded-lg text-text3 hover:text-text hover:bg-bg3 cursor-pointer"
              >
                <RiCloseLine className="size-5" />
              </button>
            </div>

            {/* Questions Body */}
            <div className="p-5 sm:p-6 flex-1 overflow-y-auto space-y-6">
              {/* Score Alert if Submitted */}
              {quizSubmitted && quizScore !== null && (
                <div className="p-4 rounded-xl bg-green/10 border border-green/30 text-xs space-y-1.5 animate-in fade-in">
                  <div className="flex items-center justify-between font-bold text-green">
                    <span className="flex items-center gap-1.5">
                      <RiSparklingFill className="size-4" />
                      <span>Assessment Completed!</span>
                    </span>
                    <span className="font-mono text-sm">
                      Score: {quizScore} / {activeModalItem.quizQuestions.length} (
                      {Math.round((quizScore / activeModalItem.quizQuestions.length) * 100)}%)
                    </span>
                  </div>
                  <p className="text-text2">
                    {quizScore === activeModalItem.quizQuestions.length
                      ? 'Outstanding work! All DepEd learning competency standards have been mastered.'
                      : 'Good effort! Review the detailed answer rationales below to reinforce your understanding.'}
                  </p>
                </div>
              )}

              {/* Questions */}
              {activeModalItem.quizQuestions.map((q, qIndex) => {
                const selectedOption = quizAnswers[q.id];

                return (
                  <div
                    key={q.id}
                    className="p-4 rounded-xl bg-bg border border-border/80 space-y-3"
                  >
                    <div className="flex items-start gap-2.5">
                      <span className="size-6 rounded-full bg-accent/10 text-accent font-bold text-xs flex items-center justify-center shrink-0 mt-0.5">
                        {qIndex + 1}
                      </span>
                      <h4 className="font-semibold text-xs sm:text-sm text-text leading-snug">
                        {q.question}
                      </h4>
                    </div>

                    {/* Radio Options */}
                    <div className="space-y-2 pl-8">
                      {q.options.map((opt, optIndex) => {
                        const isChosen = selectedOption === optIndex;
                        let optionStyle = 'hover:bg-bg3 text-text2 border-border';

                        if (quizSubmitted) {
                          if (optIndex === q.correctIndex) {
                            optionStyle = 'bg-green/15 text-green border-green/40 font-semibold';
                          } else if (isChosen && optIndex !== q.correctIndex) {
                            optionStyle = 'bg-red-500/15 text-red border-red-500/40 line-through';
                          }
                        } else if (isChosen) {
                          optionStyle = 'bg-accent/10 text-accent border-accent font-semibold';
                        }

                        return (
                          <label
                            key={optIndex}
                            className={`flex items-center gap-3 p-2.5 rounded-lg border text-xs cursor-pointer transition-all ${optionStyle}`}
                          >
                            <input
                              type="radio"
                              name={q.id}
                              checked={isChosen}
                              disabled={quizSubmitted}
                              onChange={() => {
                                setQuizAnswers((prev) => ({ ...prev, [q.id]: optIndex }));
                              }}
                              className="size-4 accent-accent cursor-pointer"
                            />
                            <span className="flex-1 leading-snug">{opt}</span>
                            {quizSubmitted && optIndex === q.correctIndex && (
                              <RiCheckLine className="size-4 text-green shrink-0" />
                            )}
                          </label>
                        );
                      })}
                    </div>

                    {/* Explanation after submission */}
                    {quizSubmitted && (
                      <div className="pl-8 pt-1 text-[11px] text-text3 flex items-start gap-1.5">
                        <span className="font-bold text-accent">Rationale:</span>
                        <span>{q.explanation}</span>
                      </div>
                    )}
                  </div>
                );
              })}

              {/* Reflection Input */}
              {activeModalItem.reflectionPrompt && (
                <div className="p-4 rounded-xl bg-bg border border-border/80 space-y-2">
                  <h4 className="font-semibold text-xs sm:text-sm text-text">
                    Short Answer Reflection
                  </h4>
                  <p className="text-xs text-text3">
                    {activeModalItem.reflectionPrompt}
                  </p>
                  <textarea
                    rows={3}
                    disabled={quizSubmitted}
                    value={quizReflection}
                    onChange={(e) => setQuizReflection(e.target.value)}
                    placeholder="Type your reflection answer here (graded for depth and critical thinking)..."
                    className="w-full rounded-lg border border-border bg-bg2 p-2.5 text-xs text-text focus:outline-hidden focus:border-accent resize-none placeholder:text-text3/50"
                  />
                </div>
              )}
            </div>

            {/* Footer */}
            <div className="p-4 border-t border-border flex items-center justify-between bg-bg2">
              <span className="text-xs text-text3">
                {Object.keys(quizAnswers).length} of {activeModalItem.quizQuestions.length} answered
              </span>

              <div className="flex items-center gap-2">
                {quizSubmitted ? (
                  <>
                    <button
                      type="button"
                      onClick={() => {
                        setQuizSubmitted(false);
                        setQuizAnswers({});
                        setQuizScore(null);
                      }}
                      className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-border bg-bg3 hover:bg-bg4 text-xs font-semibold text-text cursor-pointer"
                    >
                      <RiRefreshLine className="size-3.5" />
                      <span>Retake Quiz</span>
                    </button>
                    <button
                      type="button"
                      onClick={handleCloseModal}
                      className="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg bg-green text-white text-xs font-semibold hover:brightness-105 cursor-pointer shadow-xs"
                    >
                      <RiCheckLine className="size-3.5" />
                      <span>Done</span>
                    </button>
                  </>
                ) : (
                  <button
                    type="button"
                    disabled={Object.keys(quizAnswers).length === 0}
                    onClick={() => handleQuizSubmit(activeModalItem)}
                    className="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-lg bg-accent text-white text-xs font-semibold hover:brightness-105 disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer shadow-xs"
                  >
                    <RiCheckDoubleLine className="size-3.5" />
                    <span>Submit Answers</span>
                  </button>
                )}
              </div>
            </div>
          </div>
        </div>
      )}

      {/* ----------------- SUBMISSION BIN MODAL ----------------- */}
      {activeModalItem && activeModalItem.type === 'submission' && (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-5 bg-black/75 backdrop-blur-xs animate-in fade-in duration-200"
          onClick={handleCloseModal}
        >
          <div
            className="w-full max-w-xl rounded-2xl bg-bg2 border border-border shadow-2xl overflow-hidden flex flex-col max-h-[90vh]"
            onClick={(e) => e.stopPropagation()}
          >
            {/* Header */}
            <div className="p-4 border-b border-border flex items-center justify-between bg-bg3/60">
              <div className="flex items-center gap-2.5 min-w-0">
                <div className="size-8 rounded-lg bg-amber-700/15 text-amber-800 dark:text-amber-300 flex items-center justify-center shrink-0">
                  <RiInboxArchiveLine className="size-4.5" />
                </div>
                <div className="min-w-0">
                  <h3 className="font-bold text-sm sm:text-base text-text truncate">
                    {activeModalItem.title}
                  </h3>
                  <p className="text-[11px] text-text3">
                    Due: {activeModalItem.submissionDeadline || 'Oct 24, 2026'} · Max {activeModalItem.maxPoints || 100} Points
                  </p>
                </div>
              </div>

              <button
                type="button"
                onClick={handleCloseModal}
                aria-label="Close submission bin"
                className="p-1.5 rounded-lg text-text3 hover:text-text hover:bg-bg3 cursor-pointer"
              >
                <RiCloseLine className="size-5" />
              </button>
            </div>

            {/* Body */}
            <div className="p-5 sm:p-6 flex-1 overflow-y-auto space-y-4">
              {/* Submission Status Banner */}
              {submissionStatus === 'submitted' ? (
                <div className="p-4 rounded-xl bg-green/10 border border-green/30 text-xs space-y-1">
                  <div className="flex items-center gap-1.5 font-bold text-green">
                    <RiCheckDoubleLine className="size-4" />
                    <span>File Submitted Successfully!</span>
                  </div>
                  <p className="text-text2">
                    Submitted on Oct 20, 2026 at 10:45 AM. Your file is in queue for faculty review.
                  </p>
                </div>
              ) : (
                <div className="p-3 rounded-xl bg-accent/5 border border-accent/20 text-xs text-text2">
                  <span className="font-bold text-accent">Instructions:</span> Please attach your group’s comprehensive case study in PDF or DOCX format. Make sure team member names and individual contributions are documented on page 2.
                </div>
              )}

              {/* Rubric Criteria */}
              {activeModalItem.rubricCriteria && (
                <div className="space-y-2">
                  <h4 className="text-xs font-bold uppercase tracking-wider text-text3">
                    DepEd Evaluation Rubric
                  </h4>
                  <div className="divide-y divide-border/60 rounded-xl bg-bg border border-border text-xs">
                    {activeModalItem.rubricCriteria.map((r, idx) => (
                      <div key={idx} className="p-2.5 flex items-center justify-between">
                        <span className="text-text2">{r.criteria}</span>
                        <span className="font-mono font-bold text-accent shrink-0 ml-2">
                          {r.points} pts
                        </span>
                      </div>
                    ))}
                  </div>
                </div>
              )}

              {/* File Attachment Dropzone */}
              <div className="space-y-2">
                <h4 className="text-xs font-bold uppercase tracking-wider text-text3">
                  Upload Document File
                </h4>

                <label className="border-2 border-dashed border-border hover:border-accent/60 rounded-xl p-6 flex flex-col items-center justify-center gap-2 bg-bg hover:bg-bg3/40 transition-colors cursor-pointer text-center">
                  <input
                    type="file"
                    accept=".pdf,.docx,.doc,.zip"
                    onChange={handleFileUpload}
                    className="hidden"
                    disabled={submissionStatus === 'submitted'}
                  />
                  <div className="size-10 rounded-full bg-accent/10 text-accent flex items-center justify-center">
                    <RiUpload2Line className="size-5" />
                  </div>
                  <div>
                    <span className="text-xs font-semibold text-text">
                      Click to choose file or drag and drop here
                    </span>
                    <p className="text-[11px] text-text3 mt-0.5">
                      PDF, DOCX, or ZIP up to 50MB
                    </p>
                  </div>
                </label>

                {/* Selected File Card */}
                {submissionFileName && (
                  <div className="p-3 rounded-xl bg-bg border border-border flex items-center justify-between text-xs">
                    <div className="flex items-center gap-2.5 min-w-0">
                      <RiFilePdfLine className="size-4 text-red shrink-0" />
                      <div className="min-w-0">
                        <div className="font-semibold text-text truncate">{submissionFileName}</div>
                        <div className="text-[11px] text-text3">{submissionFileSize}</div>
                      </div>
                    </div>
                    {submissionStatus === 'submitted' ? (
                      <span className="text-[11px] font-bold text-green px-2 py-0.5 rounded-full bg-green/10">
                        Submitted
                      </span>
                    ) : (
                      <span className="text-[11px] font-medium text-accent">Ready</span>
                    )}
                  </div>
                )}
              </div>

              {/* Submission Notes */}
              <div className="space-y-1.5">
                <label className="text-xs font-medium text-text2">
                  Optional Submission Note to Instructor
                </label>
                <textarea
                  rows={2}
                  value={submissionComment}
                  onChange={(e) => setSubmissionComment(e.target.value)}
                  disabled={submissionStatus === 'submitted'}
                  placeholder="e.g. Group 2 completed case study on Therac-25 and algorithmic ethics."
                  className="w-full rounded-lg border border-border bg-bg p-2 text-xs text-text focus:outline-hidden focus:border-accent resize-none placeholder:text-text3/50"
                />
              </div>
            </div>

            {/* Footer */}
            <div className="p-4 border-t border-border flex items-center justify-between bg-bg2">
              <button
                type="button"
                onClick={handleCloseModal}
                className="px-3 py-1.5 rounded-lg border border-border bg-bg3 text-xs font-semibold text-text hover:bg-bg4 cursor-pointer"
              >
                Cancel
              </button>

              {submissionStatus === 'submitted' ? (
                <button
                  type="button"
                  onClick={handleCloseModal}
                  className="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-lg bg-green text-white text-xs font-semibold hover:brightness-105 cursor-pointer shadow-xs"
                >
                  <RiCheckLine className="size-3.5" />
                  <span>Done</span>
                </button>
              ) : (
                <button
                  type="button"
                  disabled={submissionStatus === 'uploading'}
                  onClick={() => handleConfirmSubmission(activeModalItem.id)}
                  className="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-lg bg-accent text-white text-xs font-semibold hover:brightness-105 disabled:opacity-40 cursor-pointer shadow-xs"
                >
                  <RiUpload2Line className="size-3.5" />
                  <span>{submissionStatus === 'uploading' ? 'Uploading...' : 'Submit Report for Grading'}</span>
                </button>
              )}
            </div>
          </div>
        </div>
      )}

      {/* Floating Download Toast */}
      {downloadToast && (
        <div className="fixed bottom-6 right-6 z-50 flex items-center gap-2.5 px-4 py-3 rounded-xl bg-bg2 border border-border shadow-2xl text-xs text-text animate-in slide-in-from-bottom-3 duration-200">
          <div className="size-5 rounded-full bg-green/15 text-green flex items-center justify-center shrink-0">
            <RiCheckLine className="size-3" />
          </div>
          <span>{downloadToast}</span>
        </div>
      )}
    </div>
  );
}
