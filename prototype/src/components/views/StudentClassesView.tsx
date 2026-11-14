import React, { useState, useMemo, useEffect, useRef } from 'react';
import {
  RiCalendarEventFill,
  RiCalendarLine,
  RiCalendar2Line,
  RiVideoLine,
  RiVideoChatLine,
  RiBuildingLine,
  RiTimeLine,
  RiUserLine,
  RiMapPinLine,
  RiInformationLine,
  RiGlobalLine,
  RiArrowRightLine,
  RiSlideshowLine,
  RiFilePdfLine,
  RiLinksLine,
  RiExternalLinkLine,
  RiFileCopyLine,
  RiCheckLine,
  RiCloseLine,
  RiAddLine,
  RiSearchLine,
  RiArrowLeftSLine,
  RiArrowRightSLine,
  RiMicLine,
  RiMicOffLine,
  RiCameraLine,
  RiCameraOffLine,
  RiSparklingLine,
  RiCodeBoxLine,
  RiShieldCheckLine,
  RiBookOpenLine,
  RiDeleteBinLine,
  RiTvLine,
  RiGroupLine,
} from '@remixicon/react';
import { INITIAL_CLASSES } from '@/data/classesData';
import { Popover, Dialog } from '@/components/base/popover';
import { HoverCombobox, type HoverComboboxOption } from '@/components/base/combobox';
import type { ClassSession, TeacherResourceLink } from '@/types';

export interface StudentClassesViewProps {
  onTabChange?: (tab: string) => void;
  onSelectCourse?: (courseId: string) => void;
  userRole?: 'student' | 'teacher';
}

// Helpers for calendar calculation
const DAYS_OF_WEEK = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
const MONTH_NAMES = [
  'January',
  'February',
  'March',
  'April',
  'May',
  'June',
  'July',
  'August',
  'September',
  'October',
  'November',
  'December',
];

// Reference simulated prototype date: Saturday, Nov 14, 2026
const SIMULATED_TODAY = new Date(2026, 10, 14);

export const StudentClassesView: React.FC<StudentClassesViewProps> = ({
  onTabChange,
  onSelectCourse,
  userRole = 'student',
}) => {
  // Classes state (allows teacher or student to add additional links dynamically)
  const [classesList, setClassesList] = useState<ClassSession[]>(INITIAL_CLASSES);

  // Active view: 'calendar' (monthly view) | 'week' (timetable block view) | 'catalog' (list of subjects)
  const [viewMode, setViewMode] = useState<'calendar' | 'week' | 'catalog'>('calendar');

  // Filter: 'all' | 'virtual' | 'physical'
  const [deliveryFilter, setDeliveryFilter] = useState<'all' | 'virtual' | 'physical'>('all');

  // Search filter
  const [searchQuery, setSearchQuery] = useState('');

  // Calendar date state - defaulting to November 2026 (matching system timestamp 2026-11-14)
  const [currentYear, setCurrentYear] = useState(2026);
  const [currentMonth, setCurrentMonth] = useState(10); // 0-indexed: 10 = November
  const [selectedDate, setSelectedDate] = useState<Date>(new Date(2026, 10, 14));

  // Active class for Board UI Popover card
  const [activePopupClass, setActivePopupClass] = useState<ClassSession | null>(null);
  const triggerRef = useRef<HTMLElement | null>(null);
  const calendarContainerRef = useRef<HTMLDivElement>(null);

  // Active live studio session (when user enters or broadcasts a WebRTC live class)
  const [activeLiveClass, setActiveLiveClass] = useState<ClassSession | null>(null);
  const [isMicMuted, setIsMicMuted] = useState(false);
  const [isCameraOff, setIsCameraOff] = useState(false);
  const [handRaised, setHandRaised] = useState(false);
  const [isScreenSharing, setIsScreenSharing] = useState(false);

  // Copied link toast notification with timer ref cleanup
  const [copiedLink, setCopiedLink] = useState<string | null>(null);
  const copyTimeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  // Options for Schedule Layout View Combobox
  const viewModeOptions: HoverComboboxOption<'calendar' | 'week' | 'catalog'>[] = useMemo(
    () => [
      { value: 'calendar', label: 'Full Calendar', icon: RiCalendarLine },
      { value: 'week', label: 'Weekly Timetable', icon: RiTimeLine },
      { value: 'catalog', label: 'All Classes List', icon: RiBookOpenLine },
    ],
    [],
  );

  // "Add Teacher Resource Link" modal state
  const [isAddLinkModalOpen, setIsAddLinkModalOpen] = useState(false);
  const [targetClassForLink, setTargetClassForLink] = useState<ClassSession | null>(null);
  const [newLinkTitle, setNewLinkTitle] = useState('');
  const [newLinkUrl, setNewLinkUrl] = useState('');
  const [newLinkType, setNewLinkType] = useState<TeacherResourceLink['type']>('link');
  const [newLinkDesc, setNewLinkDesc] = useState('');

  // Global Escape key listener to dismiss open modal
  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === 'Escape' && isAddLinkModalOpen) {
        setIsAddLinkModalOpen(false);
      }
    };
    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [isAddLinkModalOpen]);

  // Cleanup timers on unmount
  useEffect(() => {
    return () => {
      if (copyTimeoutRef.current) clearTimeout(copyTimeoutRef.current);
    };
  }, []);

  // Copy helper
  const handleCopy = (text: string, label: string) => {
    if (navigator?.clipboard?.writeText) {
      navigator.clipboard.writeText(text).catch(() => { });
    }
    setCopiedLink(label);
    if (copyTimeoutRef.current) {
      clearTimeout(copyTimeoutRef.current);
    }
    copyTimeoutRef.current = setTimeout(() => {
      setCopiedLink((prev) => (prev === label ? null : prev));
    }, 2500);
  };

  // Month navigation
  const handlePrevMonth = () => {
    setActivePopupClass(null);
    if (currentMonth === 0) {
      setCurrentMonth(11);
      setCurrentYear((y) => y - 1);
    } else {
      setCurrentMonth((m) => m - 1);
    }
  };

  const handleNextMonth = () => {
    setActivePopupClass(null);
    if (currentMonth === 11) {
      setCurrentMonth(0);
      setCurrentYear((y) => y + 1);
    } else {
      setCurrentMonth((m) => m + 1);
    }
  };

  const handleJumpToToday = () => {
    setActivePopupClass(null);
    setCurrentYear(2026);
    setCurrentMonth(10);
    setSelectedDate(new Date(2026, 10, 14));
  };

  // Calendar matrix calculation
  const calendarDays = useMemo(() => {
    const firstDayIndex = new Date(currentYear, currentMonth, 1).getDay();
    const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
    const daysInPrevMonth = new Date(currentYear, currentMonth, 0).getDate();

    const matrix: Array<{
      dayNumber: number;
      date: Date;
      isCurrentMonth: boolean;
      isToday: boolean;
      dayOfWeek: number;
    }> = [];

    // Prev month padding
    for (let i = firstDayIndex - 1; i >= 0; i--) {
      const dayNum = daysInPrevMonth - i;
      const d = new Date(currentYear, currentMonth - 1, dayNum);
      matrix.push({
        dayNumber: dayNum,
        date: d,
        isCurrentMonth: false,
        isToday:
          d.getFullYear() === SIMULATED_TODAY.getFullYear() &&
          d.getMonth() === SIMULATED_TODAY.getMonth() &&
          d.getDate() === SIMULATED_TODAY.getDate(),
        dayOfWeek: d.getDay(),
      });
    }

    // Current month days
    for (let i = 1; i <= daysInMonth; i++) {
      const d = new Date(currentYear, currentMonth, i);
      matrix.push({
        dayNumber: i,
        date: d,
        isCurrentMonth: true,
        isToday:
          currentYear === SIMULATED_TODAY.getFullYear() &&
          currentMonth === SIMULATED_TODAY.getMonth() &&
          i === SIMULATED_TODAY.getDate(),
        dayOfWeek: d.getDay(),
      });
    }

    // Next month padding to fill a complete 35 or 42 grid
    const totalSlots = matrix.length <= 35 ? 35 : 42;
    const remaining = totalSlots - matrix.length;
    for (let i = 1; i <= remaining; i++) {
      const d = new Date(currentYear, currentMonth + 1, i);
      matrix.push({
        dayNumber: i,
        date: d,
        isCurrentMonth: false,
        isToday:
          d.getFullYear() === SIMULATED_TODAY.getFullYear() &&
          d.getMonth() === SIMULATED_TODAY.getMonth() &&
          d.getDate() === SIMULATED_TODAY.getDate(),
        dayOfWeek: d.getDay(),
      });
    }

    return matrix;
  }, [currentYear, currentMonth]);

  // Filtered classes by delivery mode and search
  const filteredClasses = useMemo(() => {
    return classesList.filter((cls) => {
      if (deliveryFilter === 'virtual' && cls.deliveryMode !== 'virtual') return false;
      if (deliveryFilter === 'physical' && cls.deliveryMode !== 'physical') return false;
      if (searchQuery.trim()) {
        const q = searchQuery.toLowerCase();
        const matchesName = cls.subjectName.toLowerCase().includes(q);
        const matchesCode = cls.subjectCode.toLowerCase().includes(q);
        const matchesTeacher = cls.teacher.name.toLowerCase().includes(q);
        const matchesTopic = cls.topic.toLowerCase().includes(q);
        if (!matchesName && !matchesCode && !matchesTeacher && !matchesTopic) return false;
      }
      return true;
    });
  }, [classesList, deliveryFilter, searchQuery]);

  // Check if a day has classes
  const getClassesForDay = (dayOfWeek: number) => {
    return filteredClasses.filter((cls) => cls.scheduleDays.includes(dayOfWeek));
  };

  // Classes for the currently selected date in the calendar
  const classesOnSelectedDate = useMemo(() => {
    const dayOfWeek = selectedDate.getDay();
    return filteredClasses.filter((cls) => cls.scheduleDays.includes(dayOfWeek));
  }, [filteredClasses, selectedDate]);

  // Board UI Popover anchoring handler (positions compact card right NEXT to target trigger)
  const handleOpenPopover = (cls: ClassSession, targetEl: HTMLElement) => {
    const anchorEl = (targetEl.closest('[role="button"]') as HTMLElement) || targetEl;
    triggerRef.current = anchorEl;
    setActivePopupClass(cls);
  };

  // Select day handler that smoothly transitions month and opens popover if classes exist
  const handleSelectCalendarCell = (cellDate: Date, targetEl?: HTMLElement) => {
    setSelectedDate(cellDate);
    if (cellDate.getMonth() !== currentMonth || cellDate.getFullYear() !== currentYear) {
      setCurrentMonth(cellDate.getMonth());
      setCurrentYear(cellDate.getFullYear());
    }
    const dayClasses = getClassesForDay(cellDate.getDay());
    if (dayClasses.length > 0 && targetEl) {
      handleOpenPopover(dayClasses[0], targetEl);
    } else {
      setActivePopupClass(null);
    }
  };

  // Add teacher resource link handler
  const handleAddLinkSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!targetClassForLink || !newLinkTitle.trim() || !newLinkUrl.trim()) return;

    const newLink: TeacherResourceLink = {
      id: `link-${Date.now()}`,
      title: newLinkTitle.trim(),
      url: newLinkUrl.trim(),
      type: newLinkType,
      description: newLinkDesc.trim() || undefined,
      addedByTeacher: true,
    };

    setClassesList((prev) =>
      prev.map((cls) => {
        if (cls.id === targetClassForLink.id) {
          const updated = {
            ...cls,
            additionalLinks: [newLink, ...cls.additionalLinks],
          };
          if (activePopupClass?.id === cls.id) {
            setActivePopupClass(updated);
          }
          return updated;
        }
        return cls;
      }),
    );

    // Reset and close
    setNewLinkTitle('');
    setNewLinkUrl('');
    setNewLinkType('link');
    setNewLinkDesc('');
    setIsAddLinkModalOpen(false);
  };

  // Remove teacher resource link handler
  const handleDeleteLink = (classId: string, linkId: string) => {
    setClassesList((prev) =>
      prev.map((cls) => {
        if (cls.id === classId) {
          const updated = {
            ...cls,
            additionalLinks: cls.additionalLinks.filter((l) => l.id !== linkId),
          };
          if (activePopupClass?.id === cls.id) {
            setActivePopupClass(updated);
          }
          return updated;
        }
        return cls;
      }),
    );
  };

  // Statistics
  const totalClassesCount = classesList.length;
  const virtualClassesCount = classesList.filter((c) => c.deliveryMode === 'virtual').length;
  const physicalClassesCount = classesList.filter((c) => c.deliveryMode === 'physical').length;
  const classesOnSelectedDateCount = classesOnSelectedDate.length;

  // Options for Delivery Mode Filter Combobox
  const deliveryFilterOptions: HoverComboboxOption<'all' | 'virtual' | 'physical'>[] = useMemo(
    () => [
      { value: 'all', label: 'All Delivery', icon: RiGlobalLine, badge: totalClassesCount },
      { value: 'virtual', label: 'Virtual Only', icon: RiVideoLine, badge: virtualClassesCount },
      { value: 'physical', label: 'On-Campus Only', icon: RiBuildingLine, badge: physicalClassesCount },
    ],
    [totalClassesCount, virtualClassesCount, physicalClassesCount],
  );

  // Render Link Type Icon
  const renderLinkIcon = (type: TeacherResourceLink['type']) => {
    switch (type) {
      case 'slides':
        return <RiSlideshowLine className="size-4 text-amber" aria-hidden="true" />;
      case 'document':
        return <RiFilePdfLine className="size-4 text-red" aria-hidden="true" />;
      case 'video':
        return <RiVideoLine className="size-4 text-cyan" aria-hidden="true" />;
      case 'code':
        return <RiCodeBoxLine className="size-4 text-purple" aria-hidden="true" />;
      case 'worksheet':
        return <RiBookOpenLine className="size-4 text-green" aria-hidden="true" />;
      default:
        return <RiLinksLine className="size-4 text-accent" aria-hidden="true" />;
    }
  };

  // Helper for thematic subject banner styling
  const getSubjectBannerGradient = (subjectCode: string) => {
    if (subjectCode.includes('ORAL')) {
      return 'from-cyan-900/90 via-sky-950 to-slate-950 border-cyan-500/30';
    }
    if (subjectCode.includes('MATH')) {
      return 'from-blue-900/90 via-indigo-950 to-slate-950 border-blue-500/30';
    }
    if (subjectCode.includes('EARTH')) {
      return 'from-emerald-900/90 via-teal-950 to-slate-950 border-emerald-500/30';
    }
    if (subjectCode.includes('FIL')) {
      return 'from-amber-900/90 via-orange-950 to-slate-950 border-amber-500/30';
    }
    if (subjectCode.includes('TECH')) {
      return 'from-purple-900/90 via-violet-950 to-slate-950 border-purple-500/30';
    }
    return 'from-teal-900/90 via-slate-900 to-slate-950 border-teal-500/30';
  };

  // =========================================================================
  // VIEW: LIVE WEBRTC STUDIO BROADCAST / MEETING ROOM
  // =========================================================================
  if (activeLiveClass) {
    const isTeacherRole = userRole === 'teacher';

    return (
      <div className="space-y-4">
        {/* Top return bar */}
        <div className="flex flex-wrap items-center justify-between gap-3 p-4 rounded-2xl bg-bg2 border border-border shadow-xs">
          <div className="flex items-center gap-3">
            <button
              type="button"
              onClick={() => setActiveLiveClass(null)}
              className="px-3 py-1.5 rounded-xl bg-bg3 hover:bg-bg4 text-text text-xs font-semibold flex items-center gap-1.5 border border-border cursor-pointer transition-colors"
            >
              <RiArrowLeftSLine className="size-4" aria-hidden="true" />
              <span>Back to Calendar</span>
            </button>
            <div className="h-5 w-px bg-border hidden sm:block" />
            <div>
              <div className="flex items-center gap-2">
                <span className="px-2 py-0.5 rounded-md bg-cyan-bg text-cyan text-[11px] font-bold uppercase tracking-wider flex items-center gap-1">
                  <span className="size-2 rounded-full bg-cyan animate-ping" />
                  {isTeacherRole ? 'Broadcasting Live' : 'Live Virtual Class'}
                </span>
                <span className="text-xs font-semibold text-text3">{activeLiveClass.subjectCode}</span>
              </div>
              <h3 className="text-base font-bold text-text mt-0.5">{activeLiveClass.subjectName}</h3>
            </div>
          </div>

          <div className="flex items-center gap-2">
            <span className="px-3 py-1 rounded-full bg-green/10 text-green text-xs font-semibold flex items-center gap-1.5 border border-green/20">
              <span className="size-2 rounded-full bg-green animate-pulse" />
              Connected · {activeLiveClass.virtualDetails?.activeLearnersCount || 28} Students In Session
            </span>
            <button
              type="button"
              onClick={() => {
                setActiveLiveClass(null);
                if (onTabChange) {
                  onTabChange('dashboard');
                }
              }}
              className="px-4 py-1.5 rounded-xl bg-red text-white text-xs font-semibold hover:bg-red/90 cursor-pointer shadow-xs transition-colors"
            >
              {isTeacherRole ? 'End Broadcast' : 'Leave Session'}
            </button>
          </div>
        </div>

        {/* Live Broadcast Canvas and Controls */}
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
          {/* Main Stage (2 cols) */}
          <div className="lg:col-span-2 space-y-4">
            <div className="rounded-2xl bg-[#090a0f] border border-border overflow-hidden shadow-2xl">
              <div className="relative aspect-video flex items-center justify-center p-6 text-white bg-radial from-slate-900 to-black">
                {/* Status Overlay */}
                <div className="absolute top-4 left-4 flex items-center gap-2">
                  <span className="px-2.5 py-1 rounded-full bg-red text-white text-[11px] font-bold flex items-center gap-1 shadow-xs">
                    <span className="size-1.5 rounded-full bg-white animate-pulse" />
                    REC ON
                  </span>
                  <span className="px-2.5 py-1 rounded-full bg-black/60 backdrop-blur-xs text-white/90 text-[11px] font-medium border border-white/10">
                    HD 1080p · Low-Latency WebRTC
                  </span>
                </div>

                <div className="absolute top-4 right-4 flex items-center gap-1.5">
                  <span className="px-2.5 py-1 rounded-full bg-black/60 backdrop-blur-xs text-white/80 text-[11px] border border-white/10">
                    Room: {activeLiveClass.virtualDetails?.meetingId || 'EXCL-OC-882-91'}
                  </span>
                </div>

                {/* Main Video Avatar Simulation */}
                <div className="text-center space-y-3">
                  <div className="relative size-24 mx-auto rounded-full bg-cyan/20 border-2 border-cyan flex items-center justify-center text-cyan shadow-lg shadow-cyan/20">
                    <RiVideoChatLine className="size-12" aria-hidden="true" />
                    <span className="absolute bottom-1 right-1 size-5 rounded-full bg-green border-2 border-[#090a0f] flex items-center justify-center">
                      <RiMicLine className="size-3 text-white" aria-hidden="true" />
                    </span>
                  </div>
                  <div>
                    <h4 className="text-lg font-bold text-white">
                      {isTeacherRole ? 'You (Broadcasting)' : `${activeLiveClass.teacher.name} (Broadcasting)`}
                    </h4>
                    <p className="text-xs text-white/70">{activeLiveClass.topic}</p>
                  </div>
                  <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-xs text-xs text-green border border-green/30">
                    <span className="size-2 rounded-full bg-green animate-pulse" />
                    Audio & Video Stream Synchronized
                  </div>
                </div>

                {/* Bottom Overlay Label */}
                <div className="absolute bottom-4 left-4 right-4 flex items-center justify-between text-xs text-white/75 bg-black/50 backdrop-blur-xs p-2.5 rounded-xl border border-white/10">
                  <span className="truncate">
                    <strong>Agenda:</strong> {activeLiveClass.topic}
                  </span>
                  <span className="shrink-0 text-cyan font-mono text-[11px]">
                    SF2 Automated Attendance: Active
                  </span>
                </div>
              </div>

              {/* In-Call Controls */}
              <div className="p-4 bg-bg2 border-t border-border flex flex-wrap items-center justify-between gap-3">
                <div className="flex items-center gap-2">
                  <button
                    type="button"
                    onClick={() => setIsMicMuted(!isMicMuted)}
                    className={`px-3 py-2 rounded-xl text-xs font-semibold cursor-pointer flex items-center gap-1.5 transition-colors ${isMicMuted
                        ? 'bg-red text-white'
                        : 'bg-bg3 text-text border border-border hover:bg-bg4'
                      }`}
                    aria-label={isMicMuted ? 'Unmute microphone' : 'Mute microphone'}
                  >
                    {isMicMuted ? <RiMicOffLine className="size-4" /> : <RiMicLine className="size-4" />}
                    <span>{isMicMuted ? 'Mic Muted' : 'Mute Mic'}</span>
                  </button>

                  <button
                    type="button"
                    onClick={() => setIsCameraOff(!isCameraOff)}
                    className={`px-3 py-2 rounded-xl text-xs font-semibold cursor-pointer flex items-center gap-1.5 transition-colors ${isCameraOff
                        ? 'bg-red text-white'
                        : 'bg-bg3 text-text border border-border hover:bg-bg4'
                      }`}
                    aria-label={isCameraOff ? 'Turn on camera' : 'Turn off camera'}
                  >
                    {isCameraOff ? <RiCameraOffLine className="size-4" /> : <RiCameraLine className="size-4" />}
                    <span>{isCameraOff ? 'Cam Off' : 'Camera On'}</span>
                  </button>

                  {isTeacherRole ? (
                    <button
                      type="button"
                      onClick={() => setIsScreenSharing(!isScreenSharing)}
                      className={`px-3 py-2 rounded-xl text-xs font-semibold cursor-pointer flex items-center gap-1.5 transition-colors ${isScreenSharing
                          ? 'bg-accent text-white'
                          : 'bg-bg3 text-text border border-border hover:bg-bg4'
                        }`}
                    >
                      <RiTvLine className="size-4" />
                      <span>{isScreenSharing ? 'Sharing Screen' : 'Share Screen'}</span>
                    </button>
                  ) : (
                    <button
                      type="button"
                      onClick={() => setHandRaised(!handRaised)}
                      className={`px-3 py-2 rounded-xl text-xs font-semibold cursor-pointer flex items-center gap-1.5 transition-colors ${handRaised
                          ? 'bg-amber text-slate-900 font-bold'
                          : 'bg-bg3 text-text border border-border hover:bg-bg4'
                        }`}
                    >
                      <RiSparklingLine className="size-4" />
                      <span>{handRaised ? 'Hand Raised' : 'Raise Hand'}</span>
                    </button>
                  )}
                </div>

                <div className="flex items-center gap-2">
                  <button
                    type="button"
                    onClick={() =>
                      handleCopy(
                        activeLiveClass.virtualDetails?.meetingUrl || '',
                        'live-call-link',
                      )
                    }
                    className="px-3 py-2 rounded-xl bg-bg3 text-text border border-border text-xs font-semibold hover:bg-bg4 cursor-pointer flex items-center gap-1.5"
                  >
                    {copiedLink === 'live-call-link' ? (
                      <>
                        <RiCheckLine className="size-4 text-green" />
                        <span className="text-green">Link Copied!</span>
                      </>
                    ) : (
                      <>
                        <RiFileCopyLine className="size-4 text-text3" />
                        <span>Copy Meeting Link</span>
                      </>
                    )}
                  </button>
                </div>
              </div>
            </div>
          </div>

          {/* Side Panel: In-Call Teacher Links & Information (1 col) */}
          <div className="space-y-4">
            {/* Class Metadata Card */}
            <div className="p-4 rounded-2xl bg-bg2 border border-border shadow-xs space-y-3">
              <div className="flex items-center justify-between">
                <h4 className="text-xs font-bold uppercase tracking-wider text-text3">Session Metadata</h4>
                <span className="text-[11px] font-semibold text-green flex items-center gap-1">
                  <RiShieldCheckLine className="size-3.5" />
                  Verified WebRTC
                </span>
              </div>

              <div className="space-y-2 text-xs">
                <div className="flex items-center justify-between py-1.5 border-b border-border/60">
                  <span className="text-text3">Meeting ID:</span>
                  <span className="font-mono font-bold text-text">
                    {activeLiveClass.virtualDetails?.meetingId || 'EXCL-OC-882-91'}
                  </span>
                </div>
                <div className="flex items-center justify-between py-1.5 border-b border-border/60">
                  <span className="text-text3">Access Passcode:</span>
                  <span className="font-mono font-semibold text-accent">
                    {activeLiveClass.virtualDetails?.passcode || 'SPEECH2026'}
                  </span>
                </div>
                <div className="flex items-center justify-between py-1.5 border-b border-border/60">
                  <span className="text-text3">Server Gateway:</span>
                  <span className="text-text2">{activeLiveClass.virtualDetails?.serverRegion}</span>
                </div>
                <div className="flex items-center justify-between py-1.5">
                  <span className="text-text3">Attendance Rule:</span>
                  <span className="text-green font-semibold">SF2 Logged</span>
                </div>
              </div>
            </div>

            {/* In-Call Teacher Resources & Handouts */}
            <div className="p-4 rounded-2xl bg-bg2 border border-border shadow-xs space-y-3">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-1.5">
                  <RiLinksLine className="size-4 text-accent" />
                  <h4 className="text-xs font-bold uppercase tracking-wider text-text">Teacher Links & Handouts</h4>
                </div>
                <span className="text-[11px] px-2 py-0.5 rounded-full bg-accent/10 text-accent font-semibold">
                  {activeLiveClass.additionalLinks.length}
                </span>
              </div>
              <p className="text-[11px] text-text3">
                Teacher-provided lecture slides, handouts, and live drill sheets for this session:
              </p>

              <div
                className="space-y-2 max-h-72 overflow-y-auto no-scrollbar pr-1"
                style={{ scrollbarWidth: 'none', msOverflowStyle: 'none' }}
              >
                {activeLiveClass.additionalLinks.map((link) => (
                  <a
                    key={link.id}
                    href={link.url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="p-2.5 rounded-xl bg-bg3 hover:bg-bg4 border border-border flex items-start gap-2.5 group transition-colors block"
                  >
                    <div className="mt-0.5 shrink-0">{renderLinkIcon(link.type)}</div>
                    <div className="min-w-0 flex-1">
                      <div className="text-xs font-semibold text-text group-hover:text-accent transition-colors flex items-center justify-between">
                        <span className="truncate">{link.title}</span>
                        <RiExternalLinkLine className="size-3 text-text3 group-hover:text-accent shrink-0 ml-1" />
                      </div>
                      {link.description && (
                        <p className="text-[11px] text-text3 mt-0.5 line-clamp-2">{link.description}</p>
                      )}
                    </div>
                  </a>
                ))}
              </div>
            </div>

            {/* Teacher Notes Callout */}
            {activeLiveClass.teacherNotes && (
              <div className="p-4 rounded-2xl bg-amber-bg border border-amber/20 text-xs space-y-1">
                <div className="font-bold text-amber flex items-center gap-1.5">
                  <RiInformationLine className="size-4" />
                  <span>Teacher Note from {activeLiveClass.teacher.name}</span>
                </div>
                <p className="text-text2 leading-relaxed">{activeLiveClass.teacherNotes}</p>
              </div>
            )}
          </div>
        </div>
      </div>
    );
  }

  // =========================================================================
  // MAIN VIEW: FULL-WIDTH CALENDAR WITH COMPACT IN-PLACE POPOVER CARD
  // =========================================================================
  return (
    <div className="space-y-6">
      {/* ------------------------------------------------------------- */}
      {/* Header & Overview Stats                                        */}
      {/* ------------------------------------------------------------- */}
      <div className="p-5 sm:p-6 rounded-2xl bg-bg2 border border-border shadow-xs">
        <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div className="space-y-1">
            <div className="flex flex-wrap items-center gap-2">
              <span className="px-2.5 py-0.5 rounded-md bg-accent/10 text-accent text-xs font-bold uppercase tracking-wider flex items-center gap-1">
                <RiCalendarEventFill className="size-3.5" />
                STEM 11-A · AY 2026–2027 (1st Sem)
              </span>
            </div>
            <h2 className="text-2xl font-bold font-serif text-text tracking-tight">
              {userRole === 'teacher' ? 'Faculty Classes & Timetable' : 'Classes & Schedule'}
            </h2>
            <p className="text-xs text-text2 max-w-2xl">
              {userRole === 'teacher'
                ? 'Spacious full-width calendar grid. Click any class to open direct room links, student attendance credentials, and supplementary links.'
                : 'Spacious full-width calendar grid. Click any class to open live meeting links, classroom credentials, and teacher resources in a quick popover.'}
            </p>
          </div>

          {/* Quick Metrics */}
          <div className="grid grid-cols-2 sm:grid-cols-4 gap-2.5 shrink-0">
            <div className="px-3.5 py-2.5 rounded-xl bg-bg3 border border-border text-center min-w-[95px]">
              <div className="text-lg font-bold text-text">{totalClassesCount}</div>
              <div className="text-[10px] font-semibold text-text3 uppercase">Total Subjects</div>
            </div>
            <div className="px-3.5 py-2.5 rounded-xl bg-cyan-bg border border-cyan/25 text-center min-w-[95px]">
              <div className="text-lg font-bold text-cyan">{virtualClassesCount}</div>
              <div className="text-[10px] font-semibold text-cyan uppercase flex items-center justify-center gap-1">
                <RiVideoLine className="size-3" />
                Virtual
              </div>
            </div>
            <div className="px-3.5 py-2.5 rounded-xl bg-green-bg border border-green/25 text-center min-w-[95px]">
              <div className="text-lg font-bold text-green">{physicalClassesCount}</div>
              <div className="text-[10px] font-semibold text-green uppercase flex items-center justify-center gap-1">
                <RiBuildingLine className="size-3" />
                On-Campus
              </div>
            </div>
            <div className="px-3.5 py-2.5 rounded-xl bg-amber-bg border border-amber/25 text-center min-w-[95px]">
              <div className="text-lg font-bold text-amber">{classesOnSelectedDateCount}</div>
              <div className="text-[10px] font-semibold text-amber uppercase">
                {selectedDate.toLocaleDateString('en-US', { weekday: 'short' })} Classes
              </div>
            </div>
          </div>
        </div>

        {/* View Switcher & Filter Toolbar */}
        <div className="mt-5 pt-4 border-t border-border flex flex-wrap items-center justify-between gap-3">
          {/* Schedule Layout View Combobox */}
          <HoverCombobox
            value={viewMode}
            onChange={(val) => {
              setActivePopupClass(null);
              setViewMode(val);
            }}
            options={viewModeOptions}
            title="Schedule Layout"
            menuWidthClassName="w-56"
            buttonClassName="bg-bg3 hover:bg-bg4"
          />

          {/* Controls: Delivery Filter Combobox & Search */}
          <div className="flex flex-wrap items-center gap-2.5">
            {/* Delivery Filter Hoverable Combobox */}
            <HoverCombobox
              value={deliveryFilter}
              onChange={(val) => setDeliveryFilter(val)}
              options={deliveryFilterOptions}
              title="Delivery Mode"
              align="right"
              menuWidthClassName="w-52"
              showCountBadge
              activeVariant={
                deliveryFilter === 'virtual'
                  ? 'cyan'
                  : deliveryFilter === 'physical'
                    ? 'green'
                    : 'default'
              }
              buttonClassName={
                deliveryFilter === 'virtual'
                  ? 'bg-cyan-bg text-cyan border-cyan/40 shadow-xs ring-1 ring-cyan/20'
                  : deliveryFilter === 'physical'
                    ? 'bg-green-bg text-green border-green/40 shadow-xs ring-1 ring-green/20'
                    : 'bg-bg3 hover:bg-bg4 text-text border-border'
              }
            />

            {/* Quick Search */}
            <div className="relative min-w-[180px]">
              <RiSearchLine className="size-3.5 text-text3 absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none" />
              <input
                type="text"
                placeholder="Search subject or teacher..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="w-full pl-8 pr-3 py-1.5 rounded-xl bg-bg3 border border-border text-xs text-text placeholder:text-text3 outline-none focus:border-accent focus:ring-1 focus:ring-accent"
              />
              {searchQuery && (
                <button
                  type="button"
                  onClick={() => setSearchQuery('')}
                  className="absolute right-2.5 top-1/2 -translate-y-1/2 text-text3 hover:text-text cursor-pointer"
                  aria-label="Clear search"
                >
                  <RiCloseLine className="size-3.5" />
                </button>
              )}
            </div>
          </div>
        </div>
      </div>

      {/* ------------------------------------------------------------- */}
      {/* VIEW MODE 1: FULL-WIDTH CALENDAR WITH IN-PLACE POPOVER        */}
      {/* ------------------------------------------------------------- */}
      {viewMode === 'calendar' && (
        <div
          ref={calendarContainerRef}
          className="relative p-4 sm:p-6 rounded-2xl bg-bg2 border border-border shadow-xs space-y-4"
        >
          {/* Calendar Controls */}
          <div className="flex flex-wrap items-center justify-between gap-3">
            <div className="flex items-center gap-3">
              <RiCalendar2Line className="size-6 text-accent" />
              <div>
                <h3 className="text-lg sm:text-xl font-bold text-text">
                  {MONTH_NAMES[currentMonth]} {currentYear}
                </h3>
                <p className="text-xs text-text3 hidden sm:block">
                  Click any class event chip or date to open its floating popover
                </p>
              </div>
            </div>

            <div className="flex items-center gap-2">
              <button
                type="button"
                onClick={handleJumpToToday}
                className="px-3 py-1.5 rounded-xl bg-bg3 hover:bg-bg4 border border-border text-xs font-semibold text-text cursor-pointer transition-colors"
              >
                Today
              </button>
              <div className="inline-flex rounded-xl bg-bg3 p-0.5 border border-border">
                <button
                  type="button"
                  onClick={handlePrevMonth}
                  className="p-1.5 rounded-lg hover:bg-bg4 text-text cursor-pointer transition-colors"
                  aria-label="Previous month"
                >
                  <RiArrowLeftSLine className="size-4" />
                </button>
                <button
                  type="button"
                  onClick={handleNextMonth}
                  className="p-1.5 rounded-lg hover:bg-bg4 text-text cursor-pointer transition-colors"
                  aria-label="Next month"
                >
                  <RiArrowRightSLine className="size-4" />
                </button>
              </div>
            </div>
          </div>

          {/* Days of week header */}
          <div className="grid grid-cols-7 gap-2 text-center font-bold text-xs text-text3 pb-2 border-b border-border/80">
            {DAYS_OF_WEEK.map((day) => (
              <div key={day} className="py-1">
                {day}
              </div>
            ))}
          </div>

          {/* Full-Width Calendar Days Matrix */}
          <div className="grid grid-cols-7 gap-2 sm:gap-2.5">
            {calendarDays.map((cell, idx) => {
              const dayClasses = getClassesForDay(cell.dayOfWeek);
              const hasVirtual = dayClasses.some((c) => c.deliveryMode === 'virtual');
              const isSelected =
                selectedDate.getFullYear() === cell.date.getFullYear() &&
                selectedDate.getMonth() === cell.date.getMonth() &&
                selectedDate.getDate() === cell.date.getDate();

              return (
                <div
                  key={idx}
                  role="button"
                  tabIndex={0}
                  aria-label={`${cell.date.toDateString()}${dayClasses.length ? `, ${dayClasses.length} classes scheduled` : ''}`}
                  onClick={(e) => handleSelectCalendarCell(cell.date, e.currentTarget)}
                  onKeyDown={(e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                      e.preventDefault();
                      handleSelectCalendarCell(cell.date, e.currentTarget);
                    }
                  }}
                  className={`min-h-[105px] sm:min-h-[125px] p-2 rounded-2xl border transition-all text-left flex flex-col justify-between cursor-pointer outline-none focus-visible:ring-2 focus-visible:ring-accent ${!cell.isCurrentMonth
                      ? 'opacity-35 bg-bg3/30 border-transparent hover:opacity-75'
                      : isSelected
                        ? 'bg-accent/10 border-accent shadow-xs ring-1 ring-accent'
                        : cell.isToday
                          ? 'bg-bg3 border-amber/40 shadow-xs'
                          : 'bg-bg3/60 hover:bg-bg3 border-border/60 hover:border-border'
                    }`}
                >
                  {/* Day number & indicators */}
                  <div className="flex items-center justify-between">
                    <span
                      className={`text-xs font-bold size-6 flex items-center justify-center rounded-full ${cell.isToday
                          ? 'bg-amber text-slate-900 font-extrabold'
                          : isSelected
                            ? 'bg-accent text-white'
                            : cell.isCurrentMonth
                              ? 'text-text'
                              : 'text-text3'
                        }`}
                    >
                      {cell.dayNumber}
                    </span>

                    {/* Virtual Class Indicator Icon */}
                    {hasVirtual && (
                      <span
                        className="size-5 rounded-full bg-cyan-bg text-cyan flex items-center justify-center text-[11px]"
                        title="Has Virtual Class Session"
                      >
                        <RiVideoLine className="size-3" />
                      </span>
                    )}
                  </div>

                  {/* Class chips on this day */}
                  <div className="space-y-1.5 mt-2 flex-1">
                    {dayClasses.map((cls) => {
                      const isVirtual = cls.deliveryMode === 'virtual';

                      return (
                        <div
                          key={cls.id}
                          onClick={(e) => {
                            e.stopPropagation();
                            setSelectedDate(cell.date);
                            handleOpenPopover(cls, e.currentTarget);
                          }}
                          className={`text-[11px] leading-tight px-2 py-1 rounded-lg font-semibold flex items-center justify-between gap-1.5 border transition-all hover:scale-[1.02] cursor-pointer shadow-xs ${isVirtual
                              ? 'bg-cyan-bg/80 hover:bg-cyan-bg text-cyan border-cyan/30'
                              : 'bg-bg2 hover:bg-bg4 text-text2 border-border'
                            }`}
                          title={`${cls.subjectName} (${cls.timeRange})`}
                        >
                          <div className="flex items-center gap-1.5 min-w-0">
                            {isVirtual ? (
                              <RiVideoLine className="size-3 shrink-0 text-cyan" />
                            ) : (
                              <RiBuildingLine className="size-3 shrink-0 text-green" />
                            )}
                            <span className="truncate">{cls.subjectCode}</span>
                          </div>
                          <span className="text-[10px] font-mono text-text3 shrink-0 hidden sm:inline">
                            {cls.startTime.split(' ')[0]}
                          </span>
                        </div>
                      );
                    })}
                  </div>
                </div>
              );
            })}
          </div>

          {/* Calendar Legend */}
          <div className="pt-3 border-t border-border/60 flex flex-wrap items-center justify-between gap-3 text-xs text-text3">
            <div className="flex items-center gap-4">
              <span className="flex items-center gap-1.5">
                <span className="size-2.5 rounded-full bg-cyan" />
                <span className="text-text2">Virtual Class (WebRTC)</span>
              </span>
              <span className="flex items-center gap-1.5">
                <span className="size-2.5 rounded-full bg-green" />
                <span className="text-text2">On-Campus Physical</span>
              </span>
              <span className="flex items-center gap-1.5">
                <span className="size-2.5 rounded-full bg-amber" />
                <span className="text-text2">Today</span>
              </span>
            </div>
            <span className="text-[11px] text-text3">Click any class chip or day to open popover</span>
          </div>

          {/* ------------------------------------------------------------- */}
          {/* BOARD UI POPOVER CARD                                         */}
          {/* ------------------------------------------------------------- */}
          {activePopupClass && (
            <Popover
              key={selectedDate.toDateString()}
              triggerRef={triggerRef}
              isOpen={!!activePopupClass}
              onOpenChange={(isOpen) => {
                if (!isOpen) setActivePopupClass(null);
              }}
              placement="end top"
              offset={8}
              shouldFlip
              isNonModal
              maxWidthClassName="w-[310px] max-w-[calc(100vw-24px)]"
            >
              <Dialog aria-label="Class Details Popover" className="max-h-[460px] overflow-hidden flex flex-col">
                {/* Class Switcher for days with multiple classes */}
                {classesOnSelectedDate.length > 1 && (
                  <div
                    className="px-3 pt-2 pb-0 flex items-center gap-1 overflow-x-auto no-scrollbar border-b border-border/40"
                    style={{ scrollbarWidth: 'none', msOverflowStyle: 'none' }}
                  >
                    {classesOnSelectedDate.map((c) => (
                      <button
                        key={c.id}
                        type="button"
                        onClick={() => setActivePopupClass(c)}
                        className={`px-2 py-0.5 rounded-t-md text-[11px] font-semibold cursor-pointer transition-colors border-b-2 ${c.id === activePopupClass.id
                            ? 'border-accent text-accent bg-accent/5'
                            : 'border-transparent text-text3 hover:text-text'
                          }`}
                      >
                        {c.subjectCode}
                      </button>
                    ))}
                  </div>
                )}

                {/* Popover Header */}
                <div className="p-3 pb-2 flex items-start justify-between gap-2">
                  <div className="space-y-0.5 min-w-0 flex-1">
                    <h3
                      id="class-popup-title"
                      className="text-sm font-bold text-text leading-tight truncate"
                    >
                      {activePopupClass.subjectName}
                    </h3>
                    <p className="text-[11px] text-text3 flex items-center gap-1.5 font-medium">
                      <span>
                        {selectedDate.toLocaleDateString('en-US', {
                          weekday: 'short',
                          month: 'short',
                          day: 'numeric',
                        })}
                      </span>
                      <span>·</span>
                      <span className="truncate">{activePopupClass.strand}</span>
                    </p>
                  </div>

                  <button
                    type="button"
                    onClick={() => setActivePopupClass(null)}
                    className="p-1 rounded-full text-text3 hover:text-text hover:bg-bg3 cursor-pointer shrink-0 transition-colors"
                    aria-label="Close popover"
                  >
                    <RiCloseLine className="size-4" />
                  </button>
                </div>

                {/* Popover Body */}
                <div
                  className="p-3 pt-0 overflow-y-auto no-scrollbar space-y-2"
                  style={{ scrollbarWidth: 'none', msOverflowStyle: 'none' }}
                >
                  {/* Thematic Banner (matching cover photo in user reference screenshot) */}
                  <div
                    className={`relative rounded-xl overflow-hidden p-2.5 sm:p-3 text-white bg-gradient-to-br ${getSubjectBannerGradient(
                      activePopupClass.subjectCode,
                    )} border shadow-2xs h-20 sm:h-22 flex flex-col justify-between`}
                  >
                    <div className="flex items-center justify-between gap-1.5">
                      <span className="px-2 py-0.5 rounded-full bg-black/40 backdrop-blur-md text-[10px] font-bold uppercase tracking-wider text-white border border-white/15">
                        {activePopupClass.subjectCode}
                      </span>

                      {activePopupClass.deliveryMode === 'virtual' ? (
                        <span className="px-2 py-0.5 rounded-full bg-cyan text-[#0a1017] text-[10px] font-extrabold flex items-center gap-1 shadow-2xs">
                          <RiVideoLine className="size-3" />
                          Virtual
                        </span>
                      ) : (
                        <span className="px-2 py-0.5 rounded-full bg-green text-white text-[10px] font-extrabold flex items-center gap-1 shadow-2xs">
                          <RiBuildingLine className="size-3" />
                          On-Campus
                        </span>
                      )}
                    </div>

                    <div className="space-y-0.5">
                      <span className="text-[9px] uppercase font-bold text-white/70 tracking-wider block">
                        Curriculum Topic
                      </span>
                      <h4 className="text-xs font-bold text-white line-clamp-1 leading-tight">
                        {activePopupClass.topic}
                      </h4>
                    </div>
                  </div>

                  {/* Row 1: Meeting Integration Bar (matching Google Meet row in screenshot) */}
                  {activePopupClass.deliveryMode === 'virtual' && activePopupClass.virtualDetails ? (
                    <div className="p-2 rounded-xl bg-bg3 border border-border flex items-center justify-between gap-2 shadow-2xs">
                      <div className="flex items-center gap-2 min-w-0">
                        <div className="size-7 rounded-lg bg-cyan-bg text-cyan flex items-center justify-center shrink-0 border border-cyan/30">
                          <RiVideoChatLine className="size-3.5" />
                        </div>
                        <div className="min-w-0">
                          <span className="text-xs font-bold text-text block truncate">
                            {activePopupClass.virtualDetails.platform.split(' ')[0]} WebRTC
                          </span>
                          <span className="font-mono text-[10px] text-text3 truncate block">
                            {activePopupClass.virtualDetails.meetingId}
                          </span>
                        </div>
                      </div>

                      <div className="flex items-center gap-1 shrink-0">
                        <button
                          type="button"
                          onClick={() => handleCopy(activePopupClass.virtualDetails!.meetingUrl, 'popup-url')}
                          className="p-1 rounded-md text-text3 hover:text-text hover:bg-bg2 cursor-pointer transition-colors"
                          title="Copy meeting link"
                          aria-label="Copy meeting link"
                        >
                          {copiedLink === 'popup-url' ? (
                            <RiCheckLine className="size-3.5 text-green" />
                          ) : (
                            <RiFileCopyLine className="size-3.5" />
                          )}
                        </button>

                        <button
                          type="button"
                          onClick={() => {
                            setActiveLiveClass(activePopupClass);
                            setActivePopupClass(null);
                          }}
                          className="px-2.5 py-1 rounded-lg bg-accent text-white text-[11px] font-bold hover:bg-accent/90 cursor-pointer shadow-xs transition-colors"
                        >
                          {userRole === 'teacher' ? 'Start' : 'Join'}
                        </button>
                      </div>
                    </div>
                  ) : (
                    <div className="p-2 rounded-xl bg-bg3 border border-border flex items-center justify-between gap-2 shadow-2xs">
                      <div className="flex items-center gap-2 min-w-0">
                        <div className="size-7 rounded-lg bg-green-bg text-green flex items-center justify-center shrink-0 border border-green/30">
                          <RiBuildingLine className="size-3.5" />
                        </div>
                        <div className="min-w-0">
                          <span className="text-xs font-bold text-text block truncate">
                            On-Campus Physical Session
                          </span>
                          <span className="font-medium text-[10px] text-text3 truncate block">
                            {activePopupClass.room}
                          </span>
                        </div>
                      </div>

                      <span className="px-2 py-0.5 rounded-md bg-green-bg text-green text-[10px] font-bold border border-green/30 shrink-0">
                        Campus
                      </span>
                    </div>
                  )}

                  {/* Row 2: Time Range & Duration (matching 20:30 -> 23:30 3h in screenshot) */}
                  <div className="p-2 rounded-xl bg-bg3 border border-border flex items-center justify-between gap-2 text-xs">
                    <div className="flex items-center gap-1.5 text-text font-medium min-w-0 text-[11px]">
                      <RiTimeLine className="size-3.5 text-text3 shrink-0" />
                      <span className="font-semibold text-text truncate">
                        {activePopupClass.startTime}
                      </span>
                      <RiArrowRightLine className="size-3 text-text3 shrink-0" />
                      <span className="font-semibold text-text truncate">
                        {activePopupClass.endTime}
                      </span>
                    </div>

                    <span className="px-1.5 py-0.5 rounded bg-bg2 border border-border font-mono text-[10px] font-bold text-text2 shrink-0">
                      {Math.round(activePopupClass.durationMinutes / 60 * 10) / 10}h
                    </span>
                  </div>

                  {/* Row 3: Location / Room / Server Region (matching GMT+8 Amsterdam in screenshot) */}
                  <div className="p-2 rounded-xl bg-bg3 border border-border flex items-center justify-between gap-2 text-xs">
                    <div className="flex items-center gap-2 min-w-0">
                      {activePopupClass.deliveryMode === 'virtual' ? (
                        <RiGlobalLine className="size-3.5 text-text3 shrink-0" />
                      ) : (
                        <RiMapPinLine className="size-3.5 text-text3 shrink-0" />
                      )}
                      <span className="text-[11px] text-text2 truncate">
                        {activePopupClass.deliveryMode === 'virtual'
                          ? activePopupClass.virtualDetails?.serverRegion || 'PH-MNL-01 Edge Relay'
                          : activePopupClass.room}
                      </span>
                    </div>

                    <button
                      type="button"
                      onClick={() =>
                        handleCopy(
                          activePopupClass.deliveryMode === 'virtual'
                            ? activePopupClass.virtualDetails?.serverRegion || ''
                            : activePopupClass.room,
                          'popup-room',
                        )
                      }
                      className="p-1 rounded-md text-text3 hover:text-text hover:bg-bg2 cursor-pointer transition-colors"
                      title="Copy location"
                      aria-label="Copy location"
                    >
                      {copiedLink === 'popup-room' ? (
                        <RiCheckLine className="size-3 text-green" />
                      ) : (
                        <RiFileCopyLine className="size-3 text-text3" />
                      )}
                    </button>
                  </div>

                  {/* Row 4: Participants / Teacher (matching Participants list in screenshot) */}
                  <div className="p-2 rounded-xl bg-bg3 border border-border space-y-1.5 text-xs">
                    <div className="flex items-center justify-between text-text3 text-[11px]">
                      <div className="flex items-center gap-1.5 font-semibold">
                        <RiGroupLine className="size-3.5" />
                        <span>Faculty & Class</span>
                      </div>
                      <span className="text-[10px] font-mono text-text3">
                        SF2 Logged
                      </span>
                    </div>

                    <div className="space-y-1">
                      {/* Teacher Item */}
                      <div className="flex items-center justify-between gap-2 p-1 rounded-lg bg-bg2 border border-border/60">
                        <div className="flex items-center gap-1.5 min-w-0">
                          <div className="size-5 rounded-full bg-accent/20 border border-accent/40 text-accent font-bold text-[9px] flex items-center justify-center shrink-0">
                            {activePopupClass.teacher.initials}
                          </div>
                          <div className="min-w-0">
                            <span className="font-bold text-text text-[11px] truncate block leading-tight">
                              {activePopupClass.teacher.name}
                            </span>
                            <span className="text-[9px] text-text3 truncate block leading-tight">
                              {activePopupClass.teacher.role}
                            </span>
                          </div>
                        </div>
                        <span className="px-1.5 py-0.5 rounded bg-accent/10 text-accent text-[9px] font-bold shrink-0">
                          Teacher
                        </span>
                      </div>

                      {/* Section & Students count */}
                      <div className="flex items-center justify-between gap-2 p-1 rounded-lg bg-bg2 border border-border/60">
                        <div className="flex items-center gap-1.5 min-w-0">
                          <div className="size-5 rounded-full bg-green/20 border border-green/40 text-green font-bold text-[9px] flex items-center justify-center shrink-0">
                            28
                          </div>
                          <div className="min-w-0">
                            <span className="font-bold text-text text-[11px] truncate block leading-tight">
                              Grade 11 STEM Section A
                            </span>
                            <span className="text-[9px] text-text3 truncate block leading-tight">
                              28 Enrolled Senior High Learners
                            </span>
                          </div>
                        </div>
                        <span className="px-1.5 py-0.5 rounded bg-green/10 text-green text-[9px] font-bold shrink-0">
                          Enrolled
                        </span>
                      </div>
                    </div>
                  </div>

                  {/* Row 5: Teacher Resources & Additional Links (matching Reminders in screenshot) */}
                  <div className="p-2 rounded-xl bg-bg3 border border-border space-y-1.5 text-xs">
                    <div className="flex items-center justify-between">
                      <div className="flex items-center gap-1.5 text-text font-semibold text-[11px]">
                        <RiLinksLine className="size-3.5 text-accent" />
                        <span>Teacher Resources ({activePopupClass.additionalLinks.length})</span>
                      </div>

                      <button
                        type="button"
                        onClick={() => {
                          setTargetClassForLink(activePopupClass);
                          setIsAddLinkModalOpen(true);
                        }}
                        className="text-accent hover:underline font-bold text-[10px] flex items-center gap-0.5 cursor-pointer"
                      >
                        <RiAddLine className="size-3" />
                        <span>Add Link</span>
                      </button>
                    </div>

                    <div
                      className="space-y-1 max-h-24 overflow-y-auto no-scrollbar pr-0.5"
                      style={{ scrollbarWidth: 'none', msOverflowStyle: 'none' }}
                    >
                      {activePopupClass.additionalLinks.map((link) => (
                        <div
                          key={link.id}
                          className="p-1.5 rounded-lg bg-bg2 border border-border/70 flex items-center justify-between gap-1.5 group hover:border-accent/40 transition-colors"
                        >
                          <a
                            href={link.url}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="flex items-center gap-1.5 min-w-0 flex-1"
                          >
                            <div className="shrink-0">{renderLinkIcon(link.type)}</div>
                            <span className="text-[10px] font-medium text-text group-hover:text-accent truncate">
                              {link.title}
                            </span>
                          </a>

                          <div className="flex items-center gap-0.5 shrink-0">
                            {link.addedByTeacher && (
                              <button
                                type="button"
                                onClick={() => handleDeleteLink(activePopupClass.id, link.id)}
                                className="p-0.5 rounded text-text3 hover:text-red cursor-pointer"
                                title="Remove link"
                                aria-label="Remove link"
                              >
                                <RiDeleteBinLine className="size-3" />
                              </button>
                            )}
                            <a
                              href={link.url}
                              target="_blank"
                              rel="noopener noreferrer"
                              className="p-0.5 rounded text-text3 hover:text-accent cursor-pointer"
                              title="Open link"
                              aria-label="Open link"
                            >
                              <RiExternalLinkLine className="size-3" />
                            </a>
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>

                  {/* Row 6: Passcode & Credentials (if Virtual) */}
                  {activePopupClass.deliveryMode === 'virtual' && activePopupClass.virtualDetails && (
                    <div className="grid grid-cols-2 gap-1.5 text-xs">
                      <div className="p-2 rounded-xl bg-bg3 border border-border">
                        <span className="text-[9px] text-text3 uppercase font-bold block">Passcode</span>
                        <span className="font-mono font-bold text-accent text-xs">
                          {activePopupClass.virtualDetails.passcode}
                        </span>
                      </div>
                      <div className="p-2 rounded-xl bg-bg3 border border-border">
                        <span className="text-[9px] text-text3 uppercase font-bold block">Video Quality</span>
                        <span className="font-medium text-text text-[10px] truncate block">
                          {activePopupClass.virtualDetails.videoQuality.split('·')[0]}
                        </span>
                      </div>
                    </div>
                  )}
                </div>

                {/* Popover Footer Action Buttons */}
                <div className="p-2.5 bg-bg3/60 border-t border-border/60 flex items-center justify-between gap-2">
                  {onSelectCourse && activePopupClass.courseId ? (
                    <button
                      type="button"
                      onClick={() => {
                        const cId = activePopupClass.courseId!;
                        setActivePopupClass(null);
                        onSelectCourse(cId);
                      }}
                      className="px-2.5 py-1 rounded-lg bg-accent/15 text-accent hover:bg-accent hover:text-white border border-accent/30 text-[10px] font-semibold cursor-pointer flex items-center gap-1 transition-colors"
                    >
                      <RiBookOpenLine className="size-3" />
                      <span>Subject Modules</span>
                      <RiArrowRightLine className="size-3" />
                    </button>
                  ) : (
                    <span className="text-[10px] text-text3">
                      DepEd Curriculum
                    </span>
                  )}

                  <button
                    type="button"
                    onClick={() => setActivePopupClass(null)}
                    className="px-3 py-1 rounded-lg bg-bg2 text-text border border-border text-[10px] font-semibold hover:bg-bg4 cursor-pointer transition-colors"
                  >
                    Close
                  </button>
                </div>
              </Dialog>
            </Popover>
          )}
        </div>
      )}

      {/* ------------------------------------------------------------- */}
      {/* VIEW MODE 2: WEEKLY TIMETABLE GRID                             */}
      {/* ------------------------------------------------------------- */}
      {viewMode === 'week' && (
        <div className="p-4 sm:p-5 rounded-2xl bg-bg2 border border-border shadow-xs space-y-4">
          <div className="flex flex-wrap items-center justify-between gap-2 border-b border-border pb-3">
            <div>
              <h3 className="text-base font-bold text-text">Weekly Timetable Schedule (Mon – Fri)</h3>
              <p className="text-xs text-text3">
                Grade 11 STEM Section A · Block periods showing physical vs virtual modalities
              </p>
            </div>
            <div className="flex items-center gap-2 text-xs">
              <span className="flex items-center gap-1 text-cyan font-semibold">
                <RiVideoLine className="size-3.5" /> Virtual Session
              </span>
              <span className="flex items-center gap-1 text-green font-semibold">
                <RiBuildingLine className="size-3.5" /> On-Campus
              </span>
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-5 gap-3">
            {[1, 2, 3, 4, 5].map((dayNum) => {
              const dayName = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'][dayNum];
              const dayClasses = filteredClasses.filter((c) => c.scheduleDays.includes(dayNum));

              return (
                <div key={dayNum} className="space-y-3">
                  <div className="p-2.5 rounded-xl bg-bg3 border border-border text-center">
                    <span className="text-xs font-bold text-text uppercase tracking-wider">{dayName}</span>
                    <span className="text-[11px] text-text3 block mt-0.5">
                      {dayClasses.length} {dayClasses.length === 1 ? 'Subject' : 'Subjects'}
                    </span>
                  </div>

                  <div className="space-y-2.5">
                    {dayClasses.length === 0 ? (
                      <div className="p-4 rounded-xl border border-dashed border-border text-center text-xs text-text3">
                        No classes
                      </div>
                    ) : (
                      dayClasses.map((cls) => {
                        const isVirtual = cls.deliveryMode === 'virtual';

                        return (
                          <div
                            key={cls.id}
                            onClick={(e) => {
                              setViewMode('calendar');
                              setSelectedDate(new Date(2026, 10, 14 + (dayNum - 6)));
                              handleOpenPopover(cls, e.currentTarget);
                            }}
                            className={`p-3 rounded-xl border transition-all cursor-pointer group ${isVirtual
                                ? 'bg-cyan-bg/25 border-cyan/30 hover:border-cyan shadow-xs'
                                : 'bg-bg3/70 border-border hover:border-border2'
                              }`}
                          >
                            <div className="flex items-center justify-between text-[10px] font-bold">
                              <span className="text-accent">{cls.subjectCode}</span>
                              {isVirtual ? (
                                <span className="text-cyan flex items-center gap-0.5">
                                  <RiVideoLine className="size-3" />
                                  Virtual
                                </span>
                              ) : (
                                <span className="text-green flex items-center gap-0.5">
                                  <RiBuildingLine className="size-3" />
                                  Campus
                                </span>
                              )}
                            </div>

                            <h5 className="text-xs font-bold text-text mt-1 group-hover:text-accent transition-colors line-clamp-2">
                              {cls.subjectName}
                            </h5>

                            <div className="mt-2 text-[11px] text-text2 space-y-1">
                              <div className="flex items-center gap-1 font-semibold">
                                <RiTimeLine className="size-3 text-text3" />
                                <span>{cls.timeRange}</span>
                              </div>
                              <div className="flex items-center gap-1 text-text3 truncate">
                                <RiUserLine className="size-3 text-text3 shrink-0" />
                                <span className="truncate">{cls.teacher.name}</span>
                              </div>
                            </div>

                            {/* Virtual join quick button */}
                            {isVirtual && (
                              <button
                                type="button"
                                onClick={(e) => {
                                  e.stopPropagation();
                                  setActiveLiveClass(cls);
                                }}
                                className="mt-2.5 w-full py-1.5 px-2 rounded-lg bg-cyan text-[#0a1017] text-[11px] font-bold hover:brightness-105 cursor-pointer flex items-center justify-center gap-1"
                              >
                                <RiVideoChatLine className="size-3.5" />
                                <span>Launch WebRTC</span>
                              </button>
                            )}
                          </div>
                        );
                      })
                    )}
                  </div>
                </div>
              );
            })}
          </div>
        </div>
      )}

      {/* ------------------------------------------------------------- */}
      {/* VIEW MODE 3: ALL CLASSES CATALOG / DIRECTORY                   */}
      {/* ------------------------------------------------------------- */}
      {viewMode === 'catalog' && (
        <div className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {filteredClasses.map((cls) => {
              const isVirtual = cls.deliveryMode === 'virtual';

              return (
                <div
                  key={cls.id}
                  className={`p-5 rounded-2xl border transition-all flex flex-col justify-between ${isVirtual
                      ? 'bg-bg2 border-cyan/40 hover:border-cyan shadow-xs'
                      : 'bg-bg2 border-border hover:border-border2 shadow-xs'
                    }`}
                >
                  <div className="space-y-3">
                    {/* Header badge & title */}
                    <div className="flex items-start justify-between gap-2">
                      <div className="space-y-1">
                        <div className="flex items-center gap-1.5">
                          <span className="text-[11px] font-bold uppercase tracking-wider text-accent">
                            {cls.subjectCode}
                          </span>
                          {isVirtual ? (
                            <span className="px-2 py-0.5 rounded-full bg-cyan-bg text-cyan text-[10px] font-bold flex items-center gap-1 border border-cyan/30">
                              <RiVideoLine className="size-3" />
                              Virtual Class
                            </span>
                          ) : (
                            <span className="px-2 py-0.5 rounded-full bg-green-bg text-green text-[10px] font-bold flex items-center gap-1 border border-green/30">
                              <RiBuildingLine className="size-3" />
                              On-Campus
                            </span>
                          )}
                        </div>
                        <h4 className="text-base font-bold text-text">{cls.subjectName}</h4>
                      </div>

                      <div className="size-9 rounded-full bg-bg3 border border-border flex items-center justify-center font-bold text-xs text-text shrink-0">
                        {cls.teacher.initials}
                      </div>
                    </div>

                    <p className="text-xs text-text2 line-clamp-2">{cls.description}</p>

                    {/* Metadata summary */}
                    <div className="space-y-1.5 text-xs text-text2 pt-2 border-t border-border/70">
                      <div className="flex items-center justify-between">
                        <span className="text-text3 flex items-center gap-1">
                          <RiCalendarLine className="size-3.5" /> Schedule:
                        </span>
                        <span className="font-semibold text-text">{cls.scheduleDaysText}</span>
                      </div>
                      <div className="flex items-center justify-between">
                        <span className="text-text3 flex items-center gap-1">
                          <RiTimeLine className="size-3.5" /> Time:
                        </span>
                        <span className="font-semibold text-text">{cls.timeRange}</span>
                      </div>
                      <div className="flex items-center justify-between">
                        <span className="text-text3 flex items-center gap-1">
                          <RiUserLine className="size-3.5" /> Instructor:
                        </span>
                        <span className="font-semibold text-text">{cls.teacher.name}</span>
                      </div>
                      <div className="flex items-center justify-between">
                        <span className="text-text3 flex items-center gap-1">
                          <RiMapPinLine className="size-3.5" /> Location:
                        </span>
                        <span className="font-semibold text-text truncate max-w-[160px]">{cls.room}</span>
                      </div>
                    </div>

                    {/* Teacher Additional Links Preview */}
                    <div className="pt-2 border-t border-border/70">
                      <div className="flex items-center justify-between text-[11px] text-text3 font-bold uppercase mb-1.5">
                        <span className="flex items-center gap-1">
                          <RiLinksLine className="size-3 text-accent" /> Teacher Resources ({cls.additionalLinks.length})
                        </span>
                        <button
                          type="button"
                          onClick={() => {
                            setTargetClassForLink(cls);
                            setIsAddLinkModalOpen(true);
                          }}
                          className="text-accent hover:underline capitalize font-semibold cursor-pointer"
                        >
                          + Add Link
                        </button>
                      </div>

                      <div
                        className="space-y-1 max-h-24 overflow-y-auto no-scrollbar"
                        style={{ scrollbarWidth: 'none', msOverflowStyle: 'none' }}
                      >
                        {cls.additionalLinks.slice(0, 2).map((link) => (
                          <a
                            key={link.id}
                            href={link.url}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="p-1.5 rounded-lg bg-bg3 hover:bg-bg4 text-[11px] text-text flex items-center justify-between gap-1 group transition-colors block"
                          >
                            <span className="flex items-center gap-1.5 truncate">
                              {renderLinkIcon(link.type)}
                              <span className="truncate group-hover:text-accent font-medium">{link.title}</span>
                            </span>
                            <RiExternalLinkLine className="size-3 text-text3 shrink-0" />
                          </a>
                        ))}
                      </div>
                    </div>
                  </div>

                  {/* Actions */}
                  <div className="mt-4 pt-3 border-t border-border flex items-center gap-2">
                    {isVirtual ? (
                      <button
                        type="button"
                        onClick={() => setActiveLiveClass(cls)}
                        className="flex-1 py-2 px-3 rounded-xl bg-cyan text-[#0a1017] text-xs font-bold hover:brightness-105 cursor-pointer shadow-xs flex items-center justify-center gap-1.5"
                      >
                        <RiVideoChatLine className="size-4" />
                        <span>{userRole === 'teacher' ? 'Start Broadcast' : 'Join Live Class'}</span>
                      </button>
                    ) : (
                      <button
                        type="button"
                        onClick={(e) => {
                          setViewMode('calendar');
                          setSelectedDate(new Date(2026, 10, 14));
                          handleOpenPopover(cls, e.currentTarget);
                        }}
                        className="flex-1 py-2 px-3 rounded-xl bg-bg3 text-text text-xs font-semibold hover:bg-bg4 border border-border cursor-pointer flex items-center justify-center gap-1.5"
                      >
                        <RiInformationLine className="size-4 text-text3" />
                        <span>View Class Card</span>
                      </button>
                    )}

                    <button
                      type="button"
                      onClick={(e) => {
                        setViewMode('calendar');
                        setSelectedDate(new Date(2026, 10, 14));
                        handleOpenPopover(cls, e.currentTarget);
                      }}
                      className="p-2 rounded-xl bg-bg3 hover:bg-bg4 text-text border border-border cursor-pointer text-xs font-semibold"
                      title="All details & links"
                      aria-label="View details"
                    >
                      <RiInformationLine className="size-4" />
                    </button>
                  </div>
                </div>
              );
            })}
          </div>
        </div>
      )}

      {/* ------------------------------------------------------------- */}
      {/* MODAL 2: ADD RESOURCE LINK MODAL (If the teacher wants it)    */}
      {/* ------------------------------------------------------------- */}
      {isAddLinkModalOpen && targetClassForLink && (
        <div
          role="dialog"
          aria-modal="true"
          aria-labelledby="add-link-title"
          onClick={(e) => {
            if (e.target === e.currentTarget) {
              setIsAddLinkModalOpen(false);
            }
          }}
          className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-xs"
        >
          <div className="w-full max-w-lg bg-bg2 rounded-2xl border border-border shadow-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            <div className="p-5 bg-bg3 border-b border-border flex items-center justify-between">
              <div>
                <h4 id="add-link-title" className="text-base font-bold text-text">
                  Add Teacher Resource Link
                </h4>
                <p className="text-xs text-text3">For {targetClassForLink.subjectName} ({targetClassForLink.subjectCode})</p>
              </div>
              <button
                type="button"
                onClick={() => setIsAddLinkModalOpen(false)}
                className="p-1.5 rounded-lg text-text3 hover:text-text cursor-pointer"
                aria-label="Close dialog"
              >
                <RiCloseLine className="size-5" />
              </button>
            </div>

            <form onSubmit={handleAddLinkSubmit} className="p-5 space-y-4">
              <div>
                <label className="block text-xs font-semibold text-text2 mb-1.5">
                  Resource Title *
                </label>
                <input
                  type="text"
                  required
                  placeholder="e.g. Chapter 5 Presentation Slide Deck"
                  value={newLinkTitle}
                  onChange={(e) => setNewLinkTitle(e.target.value)}
                  className="w-full px-3 py-2 rounded-xl bg-bg3 border border-border text-xs text-text placeholder:text-text3 outline-none focus:border-accent focus:ring-1 focus:ring-accent"
                />
              </div>

              <div>
                <label className="block text-xs font-semibold text-text2 mb-1.5">
                  Resource URL *
                </label>
                <input
                  type="url"
                  required
                  placeholder="https://slides.google.com/... or https://..."
                  value={newLinkUrl}
                  onChange={(e) => setNewLinkUrl(e.target.value)}
                  className="w-full px-3 py-2 rounded-xl bg-bg3 border border-border text-xs text-text placeholder:text-text3 outline-none focus:border-accent focus:ring-1 focus:ring-accent"
                />
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-semibold text-text2 mb-1.5">
                    Resource Category
                  </label>
                  <select
                    value={newLinkType}
                    onChange={(e) => setNewLinkType(e.target.value as TeacherResourceLink['type'])}
                    className="w-full px-3 py-2 rounded-xl bg-bg3 border border-border text-xs text-text outline-none focus:border-accent focus:ring-1 focus:ring-accent cursor-pointer"
                  >
                    <option value="slides">Presentation Slides</option>
                    <option value="document">Document / PDF</option>
                    <option value="worksheet">Practice Worksheet</option>
                    <option value="video">Video Reference</option>
                    <option value="code">Code Repository</option>
                    <option value="link">External Web Tool</option>
                  </select>
                </div>

                <div>
                  <label className="block text-xs font-semibold text-text2 mb-1.5">
                    Modality Target
                  </label>
                  <div className="px-3 py-2 rounded-xl bg-bg3 border border-border text-xs text-text2 font-medium">
                    {targetClassForLink.deliveryMode === 'virtual' ? 'Virtual Class' : 'Physical Class'}
                  </div>
                </div>
              </div>

              <div>
                <label className="block text-xs font-semibold text-text2 mb-1.5">
                  Teacher Instructions / Description (Optional)
                </label>
                <textarea
                  rows={2}
                  placeholder="Instructions for students (e.g. Review slides before Wednesday's live discussion)..."
                  value={newLinkDesc}
                  onChange={(e) => setNewLinkDesc(e.target.value)}
                  className="w-full px-3 py-2 rounded-xl bg-bg3 border border-border text-xs text-text placeholder:text-text3 outline-none focus:border-accent focus:ring-1 focus:ring-accent"
                />
              </div>

              <div className="pt-2 flex items-center justify-end gap-2">
                <button
                  type="button"
                  onClick={() => setIsAddLinkModalOpen(false)}
                  className="px-4 py-2 rounded-xl bg-bg3 text-text border border-border text-xs font-semibold hover:bg-bg4 cursor-pointer"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  className="px-5 py-2 rounded-xl bg-accent text-white text-xs font-semibold hover:bg-accent/90 cursor-pointer shadow-xs"
                >
                  Save Resource Link
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
