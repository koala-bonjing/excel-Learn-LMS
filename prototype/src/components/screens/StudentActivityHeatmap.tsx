import { useState, useMemo } from 'react';
import { RiFireFill } from '@remixicon/react';

interface DayActivity {
  date: string; // YYYY-MM-DD
  displayDate: string; // "Wednesday, Nov 11, 2026"
  count: number;
  level: 0 | 1 | 2 | 3 | 4;
  activities: string[];
}

interface HoveredTooltip {
  day: DayActivity;
  x: number;
  y: number;
}

export function StudentActivityHeatmap() {
  const [viewMode, setViewMode] = useState<'weekly' | 'monthly' | 'yearly'>('yearly');
  const [hoveredDay, setHoveredDay] = useState<HoveredTooltip | null>(null);

  // Generate deterministic realistic school year activity data (Aug 2026 to Nov 2026)
  const fullCalendar = useMemo(() => {
    const days: DayActivity[] = [];
    const startDate = new Date(2026, 7, 3); // Monday, Aug 3, 2026 (DepEd SHS start)
    const totalDays = 28 * 7; // 28 weeks = 196 days

    const sampleSubjects = [
      'General Mathematics',
      'Oral Communication',
      'Earth & Life Science',
      'Empowerment Technologies',
      'Komunikasyon at Pananaliksik',
      'PE & Health',
    ];

    for (let i = 0; i < totalDays; i++) {
      const current = new Date(startDate);
      current.setDate(startDate.getDate() + i);

      const dateStr = current.toISOString().split('T')[0];
      const displayDate = current.toLocaleDateString('en-US', {
        weekday: 'long',
        month: 'short',
        day: 'numeric',
        year: 'numeric',
      });

      const dayOfWeek = current.getDay(); // 0 is Sunday, 6 is Saturday
      const isWeekend = dayOfWeek === 0 || dayOfWeek === 6;

      // Realistic pseudo-random pattern: higher on weekdays, streak in last 14 days
      let count = 0;
      const daysFromEnd = totalDays - 1 - i;

      if (daysFromEnd < 7) {
        // Active 7-day streak!
        count = [3, 4, 5, 2, 4, 3, 2][daysFromEnd] || 3;
      } else if (daysFromEnd < 14) {
        // Previous week
        count = [2, 3, 4, 0, 3, 4, 1][daysFromEnd - 7] || 2;
      } else {
        const seed = (i * 9301 + 49297) % 233280;
        const rand = seed / 233280;
        if (isWeekend) {
          count = rand > 0.75 ? Math.floor(rand * 3) : 0;
        } else {
          count = rand > 0.25 ? Math.floor(rand * 5) + 1 : 0;
        }
      }

      let level: 0 | 1 | 2 | 3 | 4 = 0;
      if (count === 1) level = 1;
      else if (count === 2) level = 2;
      else if (count >= 3 && count <= 4) level = 3;
      else if (count >= 5) level = 4;

      const activities: string[] = [];
      if (count > 0) {
        const subj1 = sampleSubjects[i % sampleSubjects.length];
        const subj2 = sampleSubjects[(i + 2) % sampleSubjects.length];
        activities.push(`Completed Module lesson in ${subj1}`);
        if (count >= 2) activities.push(`Submitted assessment quiz in ${subj2}`);
        if (count >= 3) activities.push(`Joined live review session (45 mins)`);
        if (count >= 4) activities.push(`Uploaded performance task deliverable`);
      }

      days.push({
        date: dateStr,
        displayDate,
        count,
        level,
        activities,
      });
    }

    return days;
  }, []);

  // Filter based on viewMode
  const displayedDays = useMemo(() => {
    if (viewMode === 'weekly') {
      return fullCalendar.slice(-8 * 7); // Last 8 weeks
    }
    if (viewMode === 'monthly') {
      return fullCalendar.slice(-16 * 7); // Last 16 weeks
    }
    return fullCalendar; // All 28 weeks
  }, [fullCalendar, viewMode]);

  // Group into columns of 7 days (weeks)
  const weeks = useMemo(() => {
    const cols: DayActivity[][] = [];
    for (let i = 0; i < displayedDays.length; i += 7) {
      cols.push(displayedDays.slice(i, i + 7));
    }
    return cols;
  }, [displayedDays]);

  const totalContributions = useMemo(() => {
    return displayedDays.reduce((acc, d) => acc + d.count, 0);
  }, [displayedDays]);

  // Colors matching Image 2
  const getLevelClass = (level: 0 | 1 | 2 | 3 | 4) => {
    switch (level) {
      case 0:
        return 'bg-slate-200/80 dark:bg-bg4 border border-border/40';
      case 1:
        return 'bg-purple-300 dark:bg-purple-950/80 border border-purple-400/30';
      case 2:
        return 'bg-purple-400 dark:bg-purple-700/80 border border-purple-400/50';
      case 3:
        return 'bg-purple-600 dark:bg-purple-500 border border-purple-500/60';
      case 4:
        return 'bg-purple-800 dark:bg-purple-400 border border-purple-400/80 shadow-xs';
    }
  };

  const dayLabels = ['Mon', '', 'Wed', '', 'Fri', '', 'Sun'];

  // Month positions for the header row above week columns
  const monthPositions = useMemo(() => {
    const labels: { colIndex: number; month: string }[] = [];
    let lastMonth = '';
    weeks.forEach((w, idx) => {
      if (!w[0]) return;
      const m = new Date(w[0].date).toLocaleDateString('en-US', { month: 'short' });
      if (m !== lastMonth) {
        labels.push({ colIndex: idx, month: m });
        lastMonth = m;
      }
    });
    return labels;
  }, [weeks]);

  // Dynamic scale configurations based on viewMode so the card has a balanced, elegant size
  const viewConfig = useMemo(() => {
    if (viewMode === 'weekly') {
      return {
        cellClass: 'w-[36px] h-[28px] sm:w-[48px] sm:h-[32px] lg:w-[58px] lg:h-[36px] rounded-md sm:rounded-lg',
        labelHeightClass: 'h-[28px] sm:h-[32px] lg:h-[36px]',
        gapClass: 'gap-2 sm:gap-2.5',
      };
    }
    if (viewMode === 'monthly') {
      return {
        cellClass: 'w-[22px] h-[22px] sm:w-[26px] sm:h-[26px] lg:w-[30px] lg:h-[30px] rounded-[5px] sm:rounded-md',
        labelHeightClass: 'h-[22px] sm:h-[26px] lg:h-[30px]',
        gapClass: 'gap-1.5 sm:gap-2',
      };
    }
    // Yearly: 28 weeks, balanced ~19-20px crisp squares (sweet spot: not tiny dots, not bulky)
    return {
      cellClass: 'w-[15px] h-[15px] sm:w-[17px] sm:h-[17px] lg:w-[19px] lg:h-[19px] xl:w-[20px] xl:h-[20px] rounded-[3.5px] sm:rounded-[4px]',
      labelHeightClass: 'h-[15px] sm:h-[17px] lg:h-[19px] xl:h-[20px]',
      gapClass: 'gap-1.5 sm:gap-[6px]',
    };
  }, [viewMode]);

  return (
    <div className="card h-full flex flex-col justify-between relative overflow-visible p-4 sm:p-5">
      {/* Top Bar: Title & Total Contributions on left + Segmented Control on right */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-border/50">
        <div>
          <div className="text-xs font-semibold text-text3 uppercase tracking-wider mb-1">
            Study Activity & Contributions
          </div>
          <div className="flex flex-wrap items-baseline gap-3">
            <span className="text-2xl sm:text-3xl font-extrabold tracking-tight text-text tabular-nums">
              {totalContributions || 348}
            </span>
            <span className="inline-flex items-center gap-1 text-xs font-bold px-2 py-0.5 rounded-full bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/25">
              +14.8%
            </span>
            <span className="text-xs text-text3 font-medium hidden md:inline">
              · Active school year consistency ({viewMode} overview)
            </span>
          </div>
        </div>

        {/* Right: Quick metric chip + Segmented Control */}
        <div className="flex items-center gap-2.5 shrink-0 self-start sm:self-auto flex-wrap">
          <div className="hidden xl:flex items-center gap-2 text-xs font-medium text-text3">
            <span className="px-2 py-1 rounded-lg bg-bg3 border border-border/60 text-text2">
              <strong>64h 20m</strong> Study
            </span>
            <span className="px-2 py-1 rounded-lg bg-bg3 border border-border/60 text-text2">
              <strong>24/28</strong> Lessons
            </span>
          </div>

          {/* Segmented Control */}
          <div className="inline-flex p-1 rounded-lg bg-bg3 border border-border/60 text-xs">
            <button
              type="button"
              onClick={() => setViewMode('weekly')}
              className={`px-3 py-1 rounded-md text-xs font-semibold cursor-pointer transition-all ${
                viewMode === 'weekly'
                  ? 'bg-bg2 text-accent shadow-xs'
                  : 'text-text3 hover:text-text'
              }`}
            >
              Weekly
            </button>
            <button
              type="button"
              onClick={() => setViewMode('monthly')}
              className={`px-3 py-1 rounded-md text-xs font-semibold cursor-pointer transition-all ${
                viewMode === 'monthly'
                  ? 'bg-bg2 text-accent shadow-xs'
                  : 'text-text3 hover:text-text'
              }`}
            >
              Monthly
            </button>
            <button
              type="button"
              onClick={() => setViewMode('yearly')}
              className={`px-3 py-1 rounded-md text-xs font-semibold cursor-pointer transition-all ${
                viewMode === 'yearly'
                  ? 'bg-bg2 text-accent shadow-xs'
                  : 'text-text3 hover:text-text'
              }`}
            >
              Yearly
            </button>
          </div>
        </div>
      </div>

      {/* Main Contribution Heatmap Grid (Balanced sweet-spot sizing) */}
      <div className="py-2.5 my-auto overflow-x-auto no-scrollbar">
        <div className="inline-flex items-start gap-2.5 min-w-full justify-start lg:justify-center">
          {/* Day of Week Labels (Pixel-aligned with rows) */}
          <div className="flex flex-col shrink-0 select-none pt-6 pr-1">
            <div className={`flex flex-col ${viewConfig.gapClass}`}>
              {dayLabels.map((label, idx) => (
                <div
                  key={idx}
                  className={`${viewConfig.labelHeightClass} flex items-center justify-end text-[10px] sm:text-[11px] font-semibold text-text3`}
                >
                  {label}
                </div>
              ))}
            </div>
          </div>

          {/* Weeks Columns with Month Header Strip */}
          <div className="flex flex-col">
            {/* Month Headers aligned above the week columns */}
            <div className={`flex ${viewConfig.gapClass} mb-2 h-4 select-none relative`}>
              {weeks.map((_, colIdx) => {
                const monthItem = monthPositions.find((m) => m.colIndex === colIdx);
                return (
                  <div
                    key={colIdx}
                    className={`${viewConfig.cellClass.split(' ')[0]} shrink-0 relative overflow-visible`}
                  >
                    {monthItem && (
                      <span className="absolute left-0 top-0 text-[11px] sm:text-xs font-bold text-text2 tracking-tight whitespace-nowrap">
                        {monthItem.month}
                      </span>
                    )}
                  </div>
                );
              })}
            </div>

            {/* Weeks Columns */}
            <div className={`flex ${viewConfig.gapClass} items-center`}>
              {weeks.map((week, wIdx) => (
                <div key={wIdx} className={`flex flex-col ${viewConfig.gapClass}`}>
                  {week.map((day, dIdx) => (
                    <div
                      key={dIdx}
                      onMouseEnter={(e) => {
                        const rect = e.currentTarget.getBoundingClientRect();
                        setHoveredDay({
                          day,
                          x: rect.left + rect.width / 2,
                          y: rect.top,
                        });
                      }}
                      onMouseLeave={() => setHoveredDay(null)}
                      className={`${viewConfig.cellClass} cursor-pointer transition-transform duration-150 hover:scale-125 hover:z-20 hover:ring-2 hover:ring-accent ${getLevelClass(
                        day.level
                      )}`}
                    />
                  ))}
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>

      {/* Bottom Legend & Motivation Note */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pt-3 border-t border-border/50 text-xs text-text3">
        <div className="flex items-center gap-1.5 font-medium text-text2">
          <span className="text-amber font-bold flex items-center gap-1">
            <RiFireFill className="size-3.5 text-amber" aria-hidden="true" />
            7-day streak!
          </span>
          <span>You completed 3 learning activities yesterday. Keep it going!</span>
        </div>

        {/* GitHub-style Less/More Legend */}
        <div className="flex items-center gap-1.5 self-end sm:self-auto font-medium">
          <span>Less</span>
          <div className="flex gap-1.5 items-center">
            <span className="size-3 rounded-[3px] bg-slate-200/80 dark:bg-bg4 border border-border/40" />
            <span className="size-3 rounded-[3px] bg-purple-300 dark:bg-purple-950 border border-purple-400/30" />
            <span className="size-3 rounded-[3px] bg-purple-400 dark:bg-purple-700 border border-purple-400/50" />
            <span className="size-3 rounded-[3px] bg-purple-600 dark:bg-purple-500 border border-purple-500/60" />
            <span className="size-3 rounded-[3px] bg-purple-800 dark:bg-purple-400 border border-purple-400/80" />
          </div>
          <span>More</span>
        </div>
      </div>

      {/* Floating Interactive GitHub-style Tooltip */}
      {hoveredDay && (
        <div
          className="fixed z-50 pointer-events-none transform -translate-x-1/2 -translate-y-full mb-2"
          style={{
            left: `${hoveredDay.x}px`,
            top: `${hoveredDay.y - 8}px`,
          }}
        >
          <div className="bg-bg2/95 dark:bg-bg3/95 backdrop-blur-md border border-border2 rounded-xl p-3 shadow-xl text-left max-w-xs animate-in fade-in zoom-in-95 duration-150">
            <div className="text-[11px] font-semibold text-text3 mb-0.5">
              {hoveredDay.day.displayDate}
            </div>
            <div className="text-xs font-bold text-text flex items-center gap-1.5 mb-1.5">
              <span
                className={`w-2 h-2 rounded-full ${
                  hoveredDay.day.count > 0 ? 'bg-purple-500' : 'bg-text3'
                }`}
              />
              <span>
                {hoveredDay.day.count === 0
                  ? 'No activity'
                  : `${hoveredDay.day.count} ${
                      hoveredDay.day.count === 1 ? 'activity' : 'activities'
                    } logged`}
              </span>
            </div>
            {hoveredDay.day.activities.length > 0 ? (
              <ul className="space-y-1 text-[11px] text-text2 border-t border-border/50 pt-1.5">
                {hoveredDay.day.activities.map((act, i) => (
                  <li key={i} className="flex items-start gap-1.5">
                    <span className="text-purple-500 font-bold shrink-0">•</span>
                    <span className="truncate">{act}</span>
                  </li>
                ))}
              </ul>
            ) : (
              <div className="text-[11px] text-text3 italic">
                Rest or study day — no platform submissions.
              </div>
            )}
          </div>
        </div>
      )}
    </div>
  );
}
