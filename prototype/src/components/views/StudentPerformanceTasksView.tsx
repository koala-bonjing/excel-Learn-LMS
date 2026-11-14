import React, { useState, useMemo, useRef, useEffect } from 'react';
import { Button as AriaButton } from 'react-aria-components';
import type { StudentTask } from '@/types';
import { Tooltip, TooltipTrigger } from '@/components/base/tooltip/tooltip';
import {
  RiFlashlightFill,
  RiArrowRightLine,
  RiDraggable,
  RiCheckLine,
  RiUpload2Line,
  RiFileList3Line,
  RiTimeLine,
  RiCalendarLine,
  RiFileTextLine,
  RiKanbanView,
  RiListUnordered,
  RiArrowDownSLine,
  RiSearchLine,
  RiCloseLine,
  RiAddLine,
  RiArrowUpDownLine,
  RiDraftLine,
} from '@remixicon/react';

interface StudentPerformanceTasksViewProps {
  tasks: StudentTask[];
  onTasksChange?: React.Dispatch<React.SetStateAction<StudentTask[]>>;
  onSubmitTask: (task: StudentTask) => void;
  onTabChange?: (tab: string) => void;
}

// -------------------------------------------------------------
// Archived Submissions Mock Data (represents the 47 older items)
// -------------------------------------------------------------
interface ArchivedItem {
  id: string;
  title: string;
  subject: string;
  date: string;
  score: number;
  maxScore: number;
  gradeLetter: string;
}

const ARCHIVED_SUBMISSIONS: ArchivedItem[] = [
  { id: 'arch-1', title: 'Prelim Oral Presentation Speech Outline', subject: 'Oral Communication', date: 'Oct 28, 2026', score: 98, maxScore: 100, gradeLetter: 'A+' },
  { id: 'arch-2', title: 'Cellular Respiration Laboratory Report', subject: 'Earth and Life Science', date: 'Oct 24, 2026', score: 49, maxScore: 50, gradeLetter: 'A' },
  { id: 'arch-3', title: 'Barayti ng Wika: Sanaysay at Pagsusuri', subject: 'Komunikasyon at Pananaliksik', date: 'Oct 19, 2026', score: 29, maxScore: 30, gradeLetter: 'A' },
  { id: 'arch-4', title: 'CSS Grid & Flexbox Responsive Portfolio', subject: 'Empowerment Technologies', date: 'Oct 15, 2026', score: 48, maxScore: 50, gradeLetter: 'A' },
  { id: 'arch-5', title: 'Exponential & Logarithmic Functions Worksheet', subject: 'General Mathematics', date: 'Oct 10, 2026', score: 40, maxScore: 40, gradeLetter: 'A+' },
  { id: 'arch-6', title: 'Speech Delivery Video: Informative Pitch', subject: 'Oral Communication', date: 'Oct 04, 2026', score: 95, maxScore: 100, gradeLetter: 'A' },
  { id: 'arch-7', title: 'Plate Tectonics Field Infographic', subject: 'Earth and Life Science', date: 'Sep 29, 2026', score: 47, maxScore: 50, gradeLetter: 'A' },
  { id: 'arch-8', title: 'DepEd ICT Ethics Case Study Brief', subject: 'Empowerment Technologies', date: 'Sep 22, 2026', score: 50, maxScore: 50, gradeLetter: 'A+' },
];

const ARCHIVED_COUNT = 47; // Baseline historical archived graded count

// Subject to Assigned Faculty Lead mapping
const SUBJECT_INSTRUCTOR_MAP: Record<string, string> = {
  'Oral Communication': 'Prof. Maria Santos, PhD',
  'Earth and Life Science': 'Engr. Ronald Gomez',
  'Komunikasyon at Pananaliksik': 'Bb. Clarissa Reyes',
  'Empowerment Technologies': 'Sir Jeffrey Lim',
  'General Mathematics': 'Mr. Roderick Tan',
};

// -------------------------------------------------------------
// Generic Linear-Style Hoverable Combo Box
// -------------------------------------------------------------
interface ComboBoxOption {
  value: string;
  label: string;
  count?: number;
  dotColor?: string;
}

interface HoverableComboBoxProps {
  label: string;
  value: string;
  options: ComboBoxOption[];
  onChange: (val: string) => void;
  icon?: React.ComponentType<{ className?: string }>;
}

const HoverableComboBox: React.FC<HoverableComboBoxProps> = ({
  label,
  value,
  options,
  onChange,
  icon: Icon,
}) => {
  const [isOpen, setIsOpen] = useState(false);
  const containerRef = useRef<HTMLDivElement>(null);
  const timerRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  const activeOption = options.find((o) => o.value === value) || options[0];
  const isFiltered = value !== 'all' && value !== options[0]?.value;

  const handleMouseEnter = () => {
    if (timerRef.current) clearTimeout(timerRef.current);
    setIsOpen(true);
  };

  const handleMouseLeave = () => {
    timerRef.current = setTimeout(() => {
      setIsOpen(false);
    }, 180);
  };

  const handleClickToggle = () => {
    setIsOpen((prev) => !prev);
  };

  const handleSelect = (val: string) => {
    onChange(val);
    setIsOpen(false);
  };

  useEffect(() => {
    const handleOutsideClick = (e: MouseEvent) => {
      if (containerRef.current && !containerRef.current.contains(e.target as Node)) {
        setIsOpen(false);
      }
    };
    document.addEventListener('mousedown', handleOutsideClick);
    return () => {
      document.removeEventListener('mousedown', handleOutsideClick);
      if (timerRef.current) clearTimeout(timerRef.current);
    };
  }, []);

  return (
    <div
      ref={containerRef}
      className="relative inline-block text-left"
      onMouseEnter={handleMouseEnter}
      onMouseLeave={handleMouseLeave}
    >
      <button
        type="button"
        onClick={handleClickToggle}
        aria-haspopup="listbox"
        aria-expanded={isOpen}
        className={`px-3 py-1.5 rounded-lg text-xs font-medium border transition-all duration-150 flex items-center gap-1.5 cursor-pointer select-none shadow-2xs ${
          isFiltered
            ? 'bg-accent/10 border-accent/40 text-accent font-semibold shadow-accent/5'
            : 'bg-bg2 hover:bg-bg3 border-border text-text2 hover:text-text'
        }`}
      >
        {Icon && <Icon className="size-3.5 shrink-0 opacity-70" />}
        {activeOption?.dotColor && (
          <span className={`size-2 rounded-full shrink-0 ${activeOption.dotColor}`} />
        )}
        <span className="truncate max-w-[150px]">
          {label}: <span className="text-text font-semibold">{activeOption?.label || value}</span>
        </span>
        {typeof activeOption?.count === 'number' && (
          <span className="px-1.5 py-0.5 rounded-full text-[10px] bg-bg3 text-text3 font-mono">
            {activeOption.count}
          </span>
        )}
        <RiArrowDownSLine
          className={`size-3.5 shrink-0 text-text3 transition-transform duration-150 ${
            isOpen ? 'rotate-180' : ''
          }`}
        />
      </button>

      {isOpen && (
        <div className="absolute top-full left-0 mt-1 min-w-[210px] z-50 rounded-xl bg-bg2 border border-border shadow-xl p-1.5 space-y-0.5 backdrop-blur-md animate-in fade-in zoom-in-95 duration-100">
          <div className="px-2 py-1 text-[10px] font-bold text-text3 uppercase tracking-wider">
            Filter by {label}
          </div>
          {options.map((opt) => {
            const isSelected = opt.value === value;
            return (
              <button
                key={opt.value}
                type="button"
                onClick={() => handleSelect(opt.value)}
                className={`w-full text-left px-2.5 py-1.5 rounded-lg text-xs flex items-center justify-between cursor-pointer transition-colors duration-100 ${
                  isSelected
                    ? 'bg-accent/15 text-accent font-semibold'
                    : 'text-text2 hover:text-text hover:bg-bg3'
                }`}
              >
                <div className="flex items-center gap-2 truncate">
                  {opt.dotColor && (
                    <span className={`size-2 rounded-full shrink-0 ${opt.dotColor}`} />
                  )}
                  <span className="truncate">{opt.label}</span>
                </div>
                <div className="flex items-center gap-1.5 shrink-0 ml-2">
                  {typeof opt.count === 'number' && (
                    <span
                      className={`text-[10px] px-1.5 py-0.5 rounded-full font-mono ${
                        isSelected ? 'bg-accent/20 text-accent' : 'bg-bg3 text-text3'
                      }`}
                    >
                      {opt.count}
                    </span>
                  )}
                  {isSelected && <RiCheckLine className="size-3.5 text-accent shrink-0" />}
                </div>
              </button>
            );
          })}
        </div>
      )}
    </div>
  );
};

// -------------------------------------------------------------
// Rubric Preview Table Modal
// -------------------------------------------------------------
interface RubricModalProps {
  task: StudentTask | null;
  onClose: () => void;
  onSubmit: (task: StudentTask) => void;
}

const RubricModal: React.FC<RubricModalProps> = ({ task, onClose, onSubmit }) => {
  if (!task) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-xs animate-in fade-in duration-150">
      <div className="w-full max-w-2xl bg-bg2 rounded-2xl border border-border shadow-2xl p-6 space-y-4 max-h-[90vh] overflow-y-auto">
        <div className="flex items-center justify-between border-b border-border pb-3">
          <div>
            <div className="flex items-center gap-2">
              <span className="text-[10px] font-bold px-2 py-0.5 rounded bg-accent/15 text-accent uppercase tracking-wider">
                DepEd SHS Rubric
              </span>
              <span className="text-xs text-text3 font-mono">{task.subject}</span>
            </div>
            <h3 className="text-lg font-bold font-serif text-text mt-1">{task.title}</h3>
          </div>
          <button
            type="button"
            onClick={onClose}
            className="p-1 rounded-lg text-text3 hover:text-text hover:bg-bg3 cursor-pointer transition-colors"
          >
            <RiCloseLine className="size-5" />
          </button>
        </div>

        <div className="space-y-3">
          <p className="text-xs text-text2">
            Performance outputs are assessed across core pedagogical dimensions under the K-12
            Senior High School Standards.
          </p>

          <div className="border border-border rounded-xl overflow-hidden text-xs">
            <table className="w-full text-left border-collapse">
              <thead>
                <tr className="bg-bg3 text-text2 border-b border-border text-[11px] font-semibold uppercase tracking-wider">
                  <th className="p-3">Criterion</th>
                  <th className="p-3">Weight</th>
                  <th className="p-3">Exemplary (100%)</th>
                  <th className="p-3">Proficient (85%)</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border text-text2">
                <tr>
                  <td className="p-3 font-semibold text-text">Content & Mastery</td>
                  <td className="p-3 font-mono text-accent font-bold">40%</td>
                  <td className="p-3">Addresses all prompt concepts with high nuance and citations.</td>
                  <td className="p-3">Covers core concepts thoroughly with few minor gaps.</td>
                </tr>
                <tr>
                  <td className="p-3 font-semibold text-text">Delivery / Execution</td>
                  <td className="p-3 font-mono text-accent font-bold">30%</td>
                  <td className="p-3">Clear vocal modulation, confident posture, and zero hesitation.</td>
                  <td className="p-3">Good delivery pace with minor stutter or reading cues.</td>
                </tr>
                <tr>
                  <td className="p-3 font-semibold text-text">Technical Format</td>
                  <td className="p-3 font-mono text-accent font-bold">30%</td>
                  <td className="p-3">HD resolution, crystal-clear audio, and standard file specs.</td>
                  <td className="p-3">Clean resolution with acceptable audio quality.</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div className="p-3 rounded-xl bg-bg3 text-xs text-text2 space-y-1">
            <div className="font-semibold text-text">Accepted Formats:</div>
            <div>{task.allowedFormats || 'MP4, WEBM, PDF (Max 250MB)'}</div>
          </div>
        </div>

        <div className="flex items-center justify-end gap-2 pt-3 border-t border-border">
          <button
            type="button"
            onClick={onClose}
            className="px-4 py-2 rounded-xl bg-bg3 hover:bg-bg4 text-text text-xs font-medium cursor-pointer transition-colors"
          >
            Close Rubric
          </button>
          {task.status !== 'submitted' && (
            <button
              type="button"
              onClick={() => {
                onClose();
                onSubmit(task);
              }}
              className="px-4 py-2 rounded-xl bg-green text-[#0e0f14] text-xs font-semibold hover:brightness-105 cursor-pointer shadow-xs flex items-center gap-1.5 transition-all"
            >
              <RiUpload2Line className="size-3.5" />
              <span>Submit Deliverable Now</span>
            </button>
          )}
        </div>
      </div>
    </div>
  );
};

// -------------------------------------------------------------
// Draft Note Modal
// -------------------------------------------------------------
interface DraftNoteModalProps {
  task: StudentTask | null;
  onClose: () => void;
  onSaveNote: (taskId: string, note: string) => void;
}

const DraftNoteModal: React.FC<DraftNoteModalProps> = ({ task, onClose, onSaveNote }) => {
  const [noteText, setNoteText] = useState(task?.draftNote || '');
  const [savedToast, setSavedToast] = useState(false);

  if (!task) return null;

  const handleSave = () => {
    onSaveNote(task.id, noteText);
    setSavedToast(true);
    setTimeout(() => {
      setSavedToast(false);
      onClose();
    }, 800);
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-xs animate-in fade-in duration-150">
      <div className="w-full max-w-lg bg-bg2 rounded-2xl border border-border shadow-2xl p-6 space-y-4">
        <div className="flex items-center justify-between border-b border-border pb-3">
          <div>
            <span className="text-[10px] font-bold px-2 py-0.5 rounded bg-accent/15 text-accent uppercase tracking-wider">
              Working Draft Notes
            </span>
            <h3 className="text-base font-bold text-text mt-1">{task.title}</h3>
          </div>
          <button
            type="button"
            onClick={onClose}
            className="p-1 rounded-lg text-text3 hover:text-text hover:bg-bg3 cursor-pointer"
          >
            <RiCloseLine className="size-5" />
          </button>
        </div>

        <div className="space-y-2">
          <label className="text-xs text-text2">
            Record ideas, speech bullet points, or source links before final submission.
          </label>
          <textarea
            rows={5}
            value={noteText}
            onChange={(e) => setNoteText(e.target.value)}
            placeholder="e.g., Key speech points: 1) Hook with media statistics, 2) Define echo chambers, 3) Call to action for critical fact-checking..."
            className="w-full rounded-xl bg-bg border border-border px-3.5 py-2.5 text-xs text-text placeholder:text-text3 focus:outline-none focus:border-accent"
          />
        </div>

        {savedToast && (
          <div className="p-2.5 rounded-lg bg-green-bg text-green text-xs font-semibold flex items-center gap-1.5 animate-in fade-in">
            <RiCheckLine className="size-4" />
            <span>Draft notes saved successfully!</span>
          </div>
        )}

        <div className="flex items-center justify-end gap-2 pt-2 border-t border-border">
          <button
            type="button"
            onClick={onClose}
            className="px-4 py-2 rounded-xl bg-bg3 hover:bg-bg4 text-text text-xs font-medium cursor-pointer"
          >
            Cancel
          </button>
          <button
            type="button"
            onClick={handleSave}
            className="px-4 py-2 rounded-xl bg-accent text-white text-xs font-semibold hover:bg-accent2 cursor-pointer shadow-xs flex items-center gap-1.5"
          >
            <RiDraftLine className="size-3.5" />
            <span>Save Working Draft</span>
          </button>
        </div>
      </div>
    </div>
  );
};

// -------------------------------------------------------------
// Quick Add Study Task Modal
// -------------------------------------------------------------
interface AddStudyTaskModalProps {
  isOpen: boolean;
  onClose: () => void;
  onAdd: (newTask: Partial<StudentTask>) => void;
  defaultColumn?: 'urgent' | 'high' | 'normal';
}

const AddStudyTaskModal: React.FC<AddStudyTaskModalProps> = ({
  isOpen,
  onClose,
  onAdd,
  defaultColumn = 'high',
}) => {
  const [title, setTitle] = useState('');
  const [subject, setSubject] = useState('Oral Communication');
  const [urgency, setUrgency] = useState<'urgent' | 'high' | 'normal'>(defaultColumn);
  const [dueDate, setDueDate] = useState('Nov 16, 2026');

  if (!isOpen) return null;

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!title.trim()) return;

    onAdd({
      title: title.trim(),
      subject,
      category: 'Study Task',
      deliverableType: 'Study / Rehearsal Check',
      dueDate,
      dueTime: '5:00 PM',
      points: 0,
      status: 'in-progress',
      urgency: urgency === 'urgent' ? 'high' : 'normal',
      kanbanColumn: urgency,
      priorityRank: urgency === 'urgent' ? 1 : urgency === 'high' ? 2 : 3,
      priorityLabel: urgency === 'urgent' ? '#1 Top Priority' : urgency === 'high' ? '#2 High Priority' : '#3 Normal',
      description: 'Custom student milestone to track preparation and milestone progress.',
    });

    setTitle('');
    onClose();
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-xs animate-in fade-in duration-150">
      <div className="w-full max-w-md bg-bg2 rounded-2xl border border-border shadow-2xl p-6 space-y-4">
        <div className="flex items-center justify-between border-b border-border pb-3">
          <div className="flex items-center gap-2">
            <span className="size-6 rounded-lg bg-accent/15 text-accent flex items-center justify-center">
              <RiAddLine className="size-4" />
            </span>
            <h3 className="text-base font-bold text-text">Add Custom Study Task</h3>
          </div>
          <button
            type="button"
            onClick={onClose}
            className="p-1 rounded-lg text-text3 hover:text-text hover:bg-bg3 cursor-pointer"
          >
            <RiCloseLine className="size-5" />
          </button>
        </div>

        <form onSubmit={handleSubmit} className="space-y-3">
          <div>
            <label className="text-xs font-semibold text-text">Task / Reminder Title</label>
            <input
              type="text"
              required
              value={title}
              onChange={(e) => setTitle(e.target.value)}
              placeholder="e.g. Speech Rehearsal & Webcam Lighting Check"
              className="mt-1 w-full rounded-xl bg-bg border border-border px-3.5 py-2 text-xs text-text focus:outline-none focus:border-accent"
            />
          </div>

          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="text-xs font-semibold text-text">Subject</label>
              <select
                value={subject}
                onChange={(e) => setSubject(e.target.value)}
                className="mt-1 w-full rounded-xl bg-bg border border-border px-3 py-2 text-xs text-text focus:outline-none focus:border-accent"
              >
                <option value="Oral Communication">Oral Communication</option>
                <option value="Earth and Life Science">Earth and Life Science</option>
                <option value="Komunikasyon at Pananaliksik">Komunikasyon</option>
                <option value="Empowerment Technologies">Empowerment Tech</option>
                <option value="General Mathematics">General Mathematics</option>
              </select>
            </div>

            <div>
              <label className="text-xs font-semibold text-text">Priority Column</label>
              <select
                value={urgency}
                onChange={(e) => setUrgency(e.target.value as 'urgent' | 'high' | 'normal')}
                className="mt-1 w-full rounded-xl bg-bg border border-border px-3 py-2 text-xs text-text focus:outline-none focus:border-accent"
              >
                <option value="urgent">Urgent & Due Soon</option>
                <option value="high">High Priority (This Week)</option>
                <option value="normal">Normal / Scheduled</option>
              </select>
            </div>
          </div>

          <div>
            <label className="text-xs font-semibold text-text">Target Due Date</label>
            <input
              type="text"
              value={dueDate}
              onChange={(e) => setDueDate(e.target.value)}
              placeholder="e.g. Nov 16, 2026"
              className="mt-1 w-full rounded-xl bg-bg border border-border px-3.5 py-2 text-xs text-text focus:outline-none focus:border-accent"
            />
          </div>

          <div className="flex items-center justify-end gap-2 pt-3 border-t border-border">
            <button
              type="button"
              onClick={onClose}
              className="px-4 py-2 rounded-xl bg-bg3 hover:bg-bg4 text-text text-xs font-medium cursor-pointer"
            >
              Cancel
            </button>
            <button
              type="submit"
              className="px-4 py-2 rounded-xl bg-accent text-white text-xs font-semibold hover:bg-accent2 cursor-pointer shadow-xs flex items-center gap-1.5"
            >
              <RiAddLine className="size-3.5" />
              <span>Create Task</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

// -------------------------------------------------------------
// UNIFIED LINEAR TICKET CARD COMPONENT
// Used in both "Group by Subject" and "Kanban / Priority View"
// Avoids 1,000+ lines of duplicate rendering and layout thrashing
// -------------------------------------------------------------
interface LinearTaskCardProps {
  task: StudentTask;
  viewMode: 'subject' | 'kanban';
  isDragging: boolean;
  isDragOver: boolean;
  isCompressed?: boolean;
  onDragStart: (e: React.DragEvent, id: string) => void;
  onDragEnd: () => void;
  onDragOver: (e: React.DragEvent, id: string) => void;
  onDrop: (e: React.DragEvent, id: string) => void;
  onOpenRubric: (task: StudentTask) => void;
  onOpenDraft: (task: StudentTask) => void;
  onSubmit: (task: StudentTask) => void;
}

const LinearTaskCard: React.FC<LinearTaskCardProps> = ({
  task,
  viewMode,
  isDragging,
  isDragOver,
  isCompressed = false,
  onDragStart,
  onDragEnd,
  onDragOver,
  onDrop,
  onOpenRubric,
  onOpenDraft,
  onSubmit,
}) => {
  const isSubmitted = task.status === 'submitted';
  const isUrgent = (task.kanbanColumn === 'urgent' || task.priorityRank === 1) && !isSubmitted;

  // Helper for compact subject badges in Kanban mode
  const getSubjectBadge = () => {
    const code = task.subjectCode || (
      task.subject.includes('Oral') ? 'ORAL COMM' :
      task.subject.includes('Earth') ? 'EARTH & LIFE SCI' :
      task.subject.includes('Komunikasyon') ? 'KOMUNIKASYON' :
      task.subject.includes('Empowerment') ? 'EMPOWERMENT TECH' :
      'GEN MATH'
    );
    const colorClass = 
      task.subject.includes('Oral') ? 'bg-accent/15 text-accent' :
      task.subject.includes('Earth') ? 'bg-green-bg text-green' :
      task.subject.includes('Komunikasyon') ? 'bg-amber-bg text-amber' :
      task.subject.includes('Empowerment') ? 'bg-cyan-bg text-cyan' :
      'bg-purple-bg text-purple';

    return (
      <span className={`text-[10px] font-bold px-1.5 py-0.5 rounded uppercase tracking-wider shrink-0 ${colorClass}`}>
        {code}
      </span>
    );
  };

  const cardBg = viewMode === 'subject' ? 'bg-bg2' : 'bg-bg';
  const basePadding = isCompressed
    ? 'px-3 py-2.5'
    : viewMode === 'subject'
    ? 'p-5 space-y-3'
    : 'p-4 space-y-3';

  const cardClasses = isDragging
    ? 'px-3 py-2.5 bg-accent/5 border-dashed border-accent/60 opacity-45 scale-[0.985] shadow-none cursor-grabbing'
    : isDragOver
    ? `${basePadding} border-accent ring-2 ring-accent/30 bg-accent/10 translate-y-0.5 shadow-2xs`
    : isCompressed
    ? `${basePadding} ${cardBg} shadow-2xs transition-all hover:border-accent/40 hover:bg-bg3/40 ${
        isUrgent
          ? 'border-red/40 hover:border-red/60 ring-1 ring-red/10'
          : isSubmitted
          ? 'border-green/30 hover:border-green/50'
          : 'border-border'
      }`
    : `${basePadding} ${cardBg} transition-all hover:shadow-xs hover:border-accent/40 ${
        viewMode === 'subject' ? 'hover:bg-bg3/30' : 'hover:bg-bg2/60'
      } ${
        isUrgent
          ? 'border-red/40 hover:border-red/60 ring-1 ring-red/10'
          : isSubmitted
          ? 'border-green/30 hover:border-green/50'
          : 'border-border'
      }`;

  return (
    <div
      draggable
      onDragStart={(e) => onDragStart(e, task.id)}
      onDragEnd={onDragEnd}
      onDragOver={(e) => {
        e.stopPropagation();
        onDragOver(e, task.id);
      }}
      onDrop={(e) => onDrop(e, task.id)}
      onClick={(e) => {
        const target = e.target as HTMLElement;
        if (!target.closest('button') && !target.closest('a') && !target.closest('.cursor-grab')) {
          onOpenRubric(task);
        }
      }}
      className={`group task-card-collapsible rounded-xl border select-none cursor-grab active:cursor-grabbing ${cardClasses}`}
      data-dragging={isDragging ? 'true' : 'false'}
    >
      {isCompressed ? (
        /* Compact Summary Row when any task is being dragged across the board */
        <div className="flex items-center justify-between gap-2.5 min-w-0">
          <div className="flex items-center gap-1.5 min-w-0 flex-1 truncate">
            <span
              className="task-card-drag-handle cursor-grab active:cursor-grabbing p-1 -m-1 rounded text-text3 hover:text-text hover:bg-bg3 shrink-0 transition-colors"
              title="Drag to reorder priority"
            >
              <RiDraggable className="size-3.5" />
            </span>

            {/* Subject badge in Kanban mode */}
            {viewMode === 'kanban' && getSubjectBadge()}

            {/* Priority pill */}
            {task.priorityLabel && !isSubmitted && (
              <span
                className={`text-[10px] font-bold px-1.5 py-0.5 rounded uppercase tracking-wider shrink-0 ${
                  isUrgent
                    ? 'bg-red-bg text-red'
                    : task.priorityRank === 2 || task.kanbanColumn === 'high'
                    ? 'bg-amber-bg text-amber'
                    : 'bg-bg3 text-text2'
                }`}
              >
                {task.priorityLabel}
              </span>
            )}

            {/* Title */}
            <h4 className="font-bold text-xs text-text truncate">
              {task.title}
            </h4>
          </div>

          <div className="flex items-center gap-2 shrink-0 text-xs">
            {/* Due Date */}
            <span
              className={`text-[11px] text-text3 items-center gap-1 font-mono shrink-0 ${
                viewMode === 'kanban' ? 'hidden 2xl:inline-flex' : 'hidden sm:inline-flex'
              }`}
            >
              <RiCalendarLine className="size-3 shrink-0 text-text3" />
              <span>{task.dueDate}</span>
            </span>

            {/* Points */}
            <span className="font-mono font-bold text-xs shrink-0">
              {isSubmitted ? `${task.grade ?? 48}/${task.points}` : `${task.points} pts`}
            </span>

            {/* Graded / To Submit badge */}
            {isSubmitted ? (
              <span className="text-[10px] font-bold px-1.5 py-0.5 rounded bg-green-bg text-green shrink-0 flex items-center gap-0.5">
                <RiCheckLine className="size-3" />
                <span>Graded</span>
              </span>
            ) : (
              <span className="text-[10px] font-bold px-1.5 py-0.5 rounded bg-accent/15 text-accent shrink-0">
                To Submit
              </span>
            )}
          </div>
        </div>
      ) : (
        <>
          {/* Top Spec Bar: Drag Handle, Subject (Kanban or when dragging), Priority Rank, Deliverable Type, Points */}
          <div className="flex items-center justify-between gap-2 text-xs">
            <div className="flex items-center gap-1.5 flex-wrap min-w-0">
              <span
                className="task-card-drag-handle cursor-grab active:cursor-grabbing p-1 -m-1 rounded text-text3 hover:text-text hover:bg-bg3 shrink-0 transition-colors"
                title="Drag to reorder priority"
              >
                <RiDraggable className="size-3.5" />
              </span>

              {/* Subject badge (Kanban mode) */}
              {viewMode === 'kanban' && getSubjectBadge()}

              {/* Priority pill */}
              {task.priorityLabel && !isSubmitted && (
                <span
                  className={`text-[10px] font-bold px-1.5 py-0.5 rounded uppercase tracking-wider shrink-0 ${
                    isUrgent
                      ? 'bg-red-bg text-red'
                      : task.priorityRank === 2 || task.kanbanColumn === 'high'
                      ? 'bg-amber-bg text-amber'
                      : 'bg-bg3 text-text2'
                  }`}
                >
                  {task.priorityLabel}
                </span>
              )}

              {/* Deliverable Type (Video, Infographic, Code, Essay) */}
              {task.deliverableType && viewMode === 'subject' && (
                <span className="text-[10px] font-medium px-2 py-0.5 rounded bg-bg3 text-text2 border border-border/60 shrink-0">
                  {task.deliverableType}
                </span>
              )}
            </div>

            {/* Points Display */}
            <div className="text-right shrink-0">
              {isSubmitted ? (
                <div className="flex items-center gap-1">
                  <span className="text-xs font-mono font-extrabold text-green">
                    {task.grade ?? 48}/{task.points}
                  </span>
                  <span className="text-[10px] font-bold text-green">96% (A)</span>
                </div>
              ) : (
                <div>
                  <span className="text-sm font-extrabold text-text font-mono">
                    {task.points}
                  </span>
                  <span className="text-[10px] text-text3 ml-1 uppercase font-bold tracking-wider hidden sm:inline">
                    PTS
                  </span>
                </div>
              )}
            </div>
          </div>

          {/* Title - Stable DOM node with responsive font */}
          <div>
            <h4 className="font-bold text-text group-hover:text-accent transition-colors leading-snug text-sm sm:text-base">
              {task.title}
            </h4>
          </div>
        </>
      )}

      {/* Auto-Collapsible Card Body: Description, Feedback, Criteria Strip, Actions */}
      <div
        className="task-card-body-collapse"
        data-collapsed={isCompressed ? 'true' : 'false'}
      >
        <div className="min-h-0 space-y-3 pt-1 overflow-hidden">
          {task.description && (
            <p className={`text-xs text-text2 leading-relaxed ${viewMode === 'kanban' ? 'line-clamp-2' : ''}`}>
              {task.description}
            </p>
          )}

          {/* Submitted Feedback Box (Kanban Column 4 / Graded card) */}
          {isSubmitted && task.feedback && (
            <div className="p-2.5 rounded-lg bg-bg2 border border-border text-[11px] text-text2 italic space-y-1">
              <div className="font-semibold text-text not-italic text-[10px] text-text3 uppercase">
                {task.instructor || 'Sir Jeffrey Lim'}'s Feedback:
              </div>
              <div>"{task.feedback}"</div>
            </div>
          )}

          {/* Due Date & Criteria Specs Strip */}
          <div
            className={`p-2.5 rounded-lg border text-xs flex flex-wrap items-center justify-between gap-2 ${
              isUrgent
                ? 'bg-red-bg/40 border-red/20 text-red font-medium'
                : 'bg-bg2 border-border/80 text-text2'
            }`}
          >
            <div className="flex items-center gap-1.5">
              {isUrgent ? (
                <RiTimeLine className="size-3.5 shrink-0 text-red" />
              ) : (
                <RiCalendarLine className="size-3.5 shrink-0 text-text3" />
              )}
              <span className={`text-[11px] font-semibold uppercase tracking-wider ${isUrgent ? 'text-red' : 'text-text'}`}>
                Due {task.dueDate} {task.dueTime}
              </span>
              {task.hoursLeft && !isSubmitted && (
                <span className="px-1.5 py-0.5 rounded text-[10px] font-bold bg-red-bg text-red shrink-0">
                  {task.hoursLeft} hrs left
                </span>
              )}
            </div>

            {/* Rubric Weights Preview in Subject View */}
            {viewMode === 'subject' && task.rubricPreview && (
              <div className="hidden md:flex items-center gap-1.5 text-text2">
                <span className="text-[10px] text-text3 font-semibold uppercase">Rubric:</span>
                <span className="px-2 py-0.5 rounded bg-bg3 border border-border text-[10px] font-mono">
                  {task.rubricPreview}
                </span>
              </div>
            )}

            {/* Allowed Formats in Subject View */}
            {viewMode === 'subject' && task.allowedFormats && (
              <div className="text-[11px] text-text3 hidden lg:flex items-center gap-1">
                <RiFileTextLine className="size-3.5" />
                <span>{task.allowedFormats}</span>
              </div>
            )}

            {/* Submission attachment in Graded View */}
            {isSubmitted && (
              <div className="flex items-center gap-1 text-[11px] text-text3 font-mono">
                <RiFileTextLine className="size-3.5" />
                <span>{task.submissionAttachment || 'milestone1_final.zip'}</span>
              </div>
            )}
          </div>

          {/* Card Action Footer */}
          <div className="pt-1 flex items-center justify-between gap-2 border-t border-border/60">
            <div className="flex items-center gap-2">
              <button
                type="button"
                onClick={() => onOpenRubric(task)}
                className="text-xs text-accent hover:underline flex items-center gap-1 font-medium cursor-pointer"
              >
                <RiFileList3Line className="size-3.5" />
                <span>{isSubmitted ? 'View Rubric' : 'Rubric Table'}</span>
              </button>

              {viewMode === 'subject' && !isSubmitted && (
                <>
                  <span className="text-border">|</span>
                  <button
                    type="button"
                    onClick={() => onOpenDraft(task)}
                    className="text-xs text-text3 hover:text-text flex items-center gap-1 cursor-pointer"
                  >
                    <RiDraftLine className="size-3.5" />
                    <span>{task.draftNote ? 'Draft Note' : 'Draft'}</span>
                  </button>
                </>
              )}
            </div>

            {/* Action Buttons */}
            <div>
              {!isSubmitted ? (
                <button
                  type="button"
                  onClick={() => onSubmit(task)}
                  className="px-3.5 py-1.5 rounded-lg bg-green hover:brightness-105 text-[#0e0f14] text-xs font-semibold cursor-pointer shadow-2xs flex items-center gap-1 transition-all"
                >
                  <RiUpload2Line className="size-3.5" />
                  <span>Submit Output</span>
                </button>
              ) : (
                <span className="text-xs font-semibold text-green flex items-center gap-1">
                  <RiCheckLine className="size-3.5" />
                  <span>Graded</span>
                </span>
              )}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

// -------------------------------------------------------------
// MAIN COMPONENT: StudentPerformanceTasksView
// -------------------------------------------------------------
export const StudentPerformanceTasksView: React.FC<StudentPerformanceTasksViewProps> = ({
  tasks,
  onTasksChange,
  onSubmitTask,
}) => {
  // View mode toggle: 'subject' (Group by Subject) vs 'kanban' (Kanban / Priority View)
  const [viewMode, setViewMode] = useState<'subject' | 'kanban'>('subject');

  // Filter States
  const [statusFilter, setStatusFilter] = useState<string>('all');
  const [subjectFilter, setSubjectFilter] = useState<string>('all');
  const [priorityFilter, setPriorityFilter] = useState<string>('all');
  const [sortBy, setSortBy] = useState<string>('urgency');
  const [searchQuery, setSearchQuery] = useState<string>('');

  // Drag and Drop State
  const [draggingTaskId, setDraggingTaskId] = useState<string | null>(null);
  const [dragOverTaskId, setDragOverTaskId] = useState<string | null>(null);
  const [dragOverColumn, setDragOverColumn] = useState<string | null>(null);
  const [dragOverSubject, setDragOverSubject] = useState<string | null>(null);

  // Drag Animation Frame & Lifecycle Guards
  const dragRafRef = useRef<number | null>(null);
  const isDraggingRef = useRef<boolean>(false);

  // Collapsed Subject Groups state
  const [collapsedSubjects, setCollapsedSubjects] = useState<Record<string, boolean>>({});

  // Expand archived older submissions state (Column 4)
  const [isArchiveExpanded, setIsArchiveExpanded] = useState<boolean>(false);

  // Modals state
  const [activeRubricTask, setActiveRubricTask] = useState<StudentTask | null>(null);
  const [activeDraftTask, setActiveDraftTask] = useState<StudentTask | null>(null);
  const [isAddStudyModalOpen, setIsAddStudyModalOpen] = useState(false);
  const [addStudyColumn, setAddStudyColumn] = useState<'urgent' | 'high' | 'normal'>('high');

  // Relevant deliverables list (Performance Tasks, Written Works, and Study Tasks)
  const deliverables = useMemo(() => {
    return tasks.filter(
      (t) =>
        t.category === 'Performance Task' ||
        t.category === 'Written Work' ||
        t.category === 'Study Task'
    );
  }, [tasks]);

  // Dynamic progress counts based on live state + historical baseline
  const submittedDeliverablesCount = deliverables.filter((t) => t.status === 'submitted').length;
  const toSubmitDeliverablesCount = deliverables.filter((t) => t.status !== 'submitted').length;
  const totalSubmitted = ARCHIVED_COUNT + submittedDeliverablesCount;
  const totalOutputs = ARCHIVED_COUNT + deliverables.length;
  const progressPercent = Math.round((totalSubmitted / totalOutputs) * 100);

  // Urgent Task for Top Banner (imminent deadline not yet submitted)
  const urgentTask = useMemo(() => {
    return deliverables.find(
      (t) =>
        (t.kanbanColumn === 'urgent' || t.priorityRank === 1 || t.urgency === 'high') &&
        t.status !== 'submitted' &&
        t.category !== 'Study Task'
    );
  }, [deliverables]);

  // Drag Handlers
  const handleDragStart = (e: React.DragEvent, taskId: string) => {
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', taskId);
    isDraggingRef.current = true;
    if (dragRafRef.current !== null) {
      cancelAnimationFrame(dragRafRef.current);
    }
    dragRafRef.current = requestAnimationFrame(() => {
      dragRafRef.current = null;
      if (isDraggingRef.current) {
        setDraggingTaskId(taskId);
      }
    });
  };

  const handleDragEnd = () => {
    isDraggingRef.current = false;
    if (dragRafRef.current !== null) {
      cancelAnimationFrame(dragRafRef.current);
      dragRafRef.current = null;
    }
    setDraggingTaskId(null);
    setDragOverTaskId(null);
    setDragOverColumn(null);
    setDragOverSubject(null);
  };

  const handleDragOver = (e: React.DragEvent, targetTaskId: string) => {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    if (draggingTaskId && draggingTaskId !== targetTaskId && dragOverTaskId !== targetTaskId) {
      setDragOverTaskId(targetTaskId);
    }
  };

  // Dropping on another task: reorders list and synchronizes subject or kanban column
  const handleDropOnTask = (e: React.DragEvent, targetTaskId: string) => {
    e.preventDefault();
    e.stopPropagation();
    if (!draggingTaskId || draggingTaskId === targetTaskId || !onTasksChange) {
      handleDragEnd();
      return;
    }

    onTasksChange((prev) => {
      const list = [...prev];
      const sourceIdx = list.findIndex((t) => t.id === draggingTaskId);
      const targetIdx = list.findIndex((t) => t.id === targetTaskId);
      if (sourceIdx === -1 || targetIdx === -1) return prev;

      const targetTask = list[targetIdx];
      const [moved] = list.splice(sourceIdx, 1);

      // In subject view, synchronize subject and instructor if moved across subjects
      if (viewMode === 'subject' && targetTask.subject) {
        moved.subject = targetTask.subject;
        moved.instructor = targetTask.instructor || SUBJECT_INSTRUCTOR_MAP[targetTask.subject] || moved.instructor;
      }

      // In kanban view, synchronize column, urgency, rank, and status
      if (viewMode === 'kanban') {
        const targetColumn: 'urgent' | 'high' | 'normal' | 'submitted' =
          targetTask.status === 'submitted' || targetTask.kanbanColumn === 'submitted'
            ? 'submitted'
            : targetTask.kanbanColumn === 'urgent'
            ? 'urgent'
            : targetTask.kanbanColumn === 'high'
            ? 'high'
            : 'normal';

        moved.kanbanColumn = targetColumn;
        if (targetColumn === 'urgent') {
          moved.urgency = 'high';
          moved.priorityRank = 1;
          moved.priorityLabel = '#1 Top Priority';
          moved.status = moved.status === 'submitted' ? 'in-progress' : moved.status;
        } else if (targetColumn === 'high') {
          moved.urgency = 'high';
          moved.priorityRank = 2;
          moved.priorityLabel = '#2 High Priority';
          moved.status = moved.status === 'submitted' ? 'in-progress' : moved.status;
        } else if (targetColumn === 'normal') {
          moved.urgency = 'normal';
          moved.priorityRank = 3;
          moved.priorityLabel = '#3 Normal';
          moved.status = moved.status === 'submitted' ? 'in-progress' : moved.status;
        } else if (targetColumn === 'submitted') {
          moved.status = 'submitted';
          moved.urgency = 'done';
          moved.priorityRank = undefined;
          moved.priorityLabel = 'Graded';
        }
      }

      list.splice(targetIdx, 0, moved);
      return list;
    });

    handleDragEnd();
  };

  // Dropping directly onto a subject group header or empty dropzone in Subject View
  const handleDropOnSubject = (e: React.DragEvent, targetSubject: string) => {
    e.preventDefault();
    e.stopPropagation();
    if (!draggingTaskId || !onTasksChange) {
      handleDragEnd();
      return;
    }

    onTasksChange((prev) => {
      const list = [...prev];
      const sourceIdx = list.findIndex((t) => t.id === draggingTaskId);
      if (sourceIdx === -1) return prev;

      const [moved] = list.splice(sourceIdx, 1);
      moved.subject = targetSubject;

      if (SUBJECT_INSTRUCTOR_MAP[targetSubject]) {
        moved.instructor = SUBJECT_INSTRUCTOR_MAP[targetSubject];
      }

      const firstTargetIdx = list.findIndex((t) => t.subject === targetSubject);
      if (firstTargetIdx !== -1) {
        list.splice(firstTargetIdx, 0, moved);
      } else {
        list.push(moved);
      }
      return list;
    });

    // Ensure the subject group is uncollapsed so the user sees the newly placed task
    setCollapsedSubjects((prev) => ({
      ...prev,
      [targetSubject]: false,
    }));

    handleDragEnd();
  };

  // Dropping into an entire column container in Kanban View
  const handleDropOnColumn = (e: React.DragEvent, targetColumn: 'urgent' | 'high' | 'normal' | 'submitted') => {
    e.preventDefault();
    e.stopPropagation();
    if (!draggingTaskId || !onTasksChange) {
      handleDragEnd();
      return;
    }

    onTasksChange((prev) => {
      return prev.map((t) => {
        if (t.id === draggingTaskId) {
          if (targetColumn === 'urgent') {
            return {
              ...t,
              kanbanColumn: 'urgent',
              urgency: 'high',
              priorityRank: 1,
              priorityLabel: '#1 Top Priority',
              status: t.status === 'submitted' ? 'in-progress' : t.status,
            };
          }
          if (targetColumn === 'high') {
            return {
              ...t,
              kanbanColumn: 'high',
              urgency: 'high',
              priorityRank: 2,
              priorityLabel: '#2 High Priority',
              status: t.status === 'submitted' ? 'in-progress' : t.status,
            };
          }
          if (targetColumn === 'normal') {
            return {
              ...t,
              kanbanColumn: 'normal',
              urgency: 'normal',
              priorityRank: 3,
              priorityLabel: '#3 Normal',
              status: t.status === 'submitted' ? 'in-progress' : t.status,
            };
          }
          if (targetColumn === 'submitted') {
            return {
              ...t,
              kanbanColumn: 'submitted',
              urgency: 'done',
              status: 'submitted',
              priorityRank: undefined,
              priorityLabel: 'Graded',
            };
          }
        }
        return t;
      });
    });

    handleDragEnd();
  };

  // Handle saving draft note
  const handleSaveDraftNote = (taskId: string, note: string) => {
    if (!onTasksChange) return;
    onTasksChange((prev) =>
      prev.map((t) => (t.id === taskId ? { ...t, draftNote: note } : t))
    );
  };

  // Handle adding custom study task
  const handleAddCustomTask = (newTask: Partial<StudentTask>) => {
    if (!onTasksChange) return;
    const taskToAdd: StudentTask = {
      id: `custom-${Date.now()}`,
      title: newTask.title || 'Custom Study Task',
      subject: newTask.subject || 'Oral Communication',
      category: 'Study Task',
      deliverableType: newTask.deliverableType || 'Study / Rehearsal Check',
      dueDate: newTask.dueDate || 'Nov 16, 2026',
      dueTime: newTask.dueTime || '5:00 PM',
      points: 0,
      status: 'in-progress',
      urgency: newTask.urgency || 'normal',
      kanbanColumn: newTask.kanbanColumn || 'high',
      priorityRank: newTask.priorityRank || 2,
      priorityLabel: newTask.priorityLabel || '#2 High Priority',
      description: newTask.description || '',
    };
    onTasksChange((prev) => [taskToAdd, ...prev]);
  };

  // Toggle Collapse on a Subject Group
  const toggleSubjectCollapse = (subjectName: string) => {
    setCollapsedSubjects((prev) => ({
      ...prev,
      [subjectName]: !prev[subjectName],
    }));
  };

  // Filtered Deliverables
  const filteredDeliverables = useMemo(() => {
    return deliverables.filter((t) => {
      // Status filter
      if (statusFilter === 'to-submit' && t.status === 'submitted') return false;
      if (statusFilter === 'submitted' && t.status !== 'submitted') return false;
      if (statusFilter === 'in-progress' && t.status !== 'in-progress') return false;

      // Subject filter
      if (subjectFilter !== 'all' && t.subject !== subjectFilter) return false;

      // Priority filter
      if (priorityFilter === 'urgent' && t.kanbanColumn !== 'urgent' && t.priorityRank !== 1)
        return false;
      if (priorityFilter === 'high' && t.kanbanColumn !== 'high' && t.priorityRank !== 2)
        return false;
      if (priorityFilter === 'normal' && t.kanbanColumn !== 'normal' && t.priorityRank !== 3)
        return false;

      // Search Query
      if (searchQuery.trim()) {
        const q = searchQuery.toLowerCase();
        const matchesTitle = t.title.toLowerCase().includes(q);
        const matchesSubject = t.subject.toLowerCase().includes(q);
        const matchesDesc = (t.description || '').toLowerCase().includes(q);
        if (!matchesTitle && !matchesSubject && !matchesDesc) return false;
      }

      return true;
    });
  }, [deliverables, statusFilter, subjectFilter, priorityFilter, searchQuery]);

  // Sorted Deliverables
  const sortedDeliverables = useMemo(() => {
    const list = [...filteredDeliverables];
    list.sort((a, b) => {
      if (sortBy === 'urgency') {
        const rankA = a.priorityRank || (a.urgency === 'high' ? 1 : 99);
        const rankB = b.priorityRank || (b.urgency === 'high' ? 1 : 99);
        return rankA - rankB;
      }
      if (sortBy === 'points-high') return (b.points || 0) - (a.points || 0);
      if (sortBy === 'points-low') return (a.points || 0) - (b.points || 0);
      if (sortBy === 'subject') return a.subject.localeCompare(b.subject);
      return 0;
    });
    return list;
  }, [filteredDeliverables, sortBy]);

  // Group by Subject mapping
  const subjectGroups = useMemo(() => {
    const map = new Map<
      string,
      {
        subject: string;
        instructor?: string;
        color: string;
        tasks: StudentTask[];
      }
    >();

    const instructorMap: Record<string, string> = {
      'Oral Communication': 'Prof. Maria Santos, PhD',
      'Earth and Life Science': 'Engr. Ronald Gomez',
      'Komunikasyon at Pananaliksik': 'Bb. Clarissa Reyes',
      'Empowerment Technologies': 'Sir Jeffrey Lim',
      'General Mathematics': 'Mr. Roderick Tan',
    };

    const colorMap: Record<string, string> = {
      'Oral Communication': 'bg-accent',
      'Earth and Life Science': 'bg-green',
      'Komunikasyon at Pananaliksik': 'bg-amber',
      'Empowerment Technologies': 'bg-cyan',
      'General Mathematics': 'bg-purple',
    };

    const knownSubjects = [
      'Oral Communication',
      'Earth and Life Science',
      'Komunikasyon at Pananaliksik',
      'Empowerment Technologies',
    ];

    knownSubjects.forEach((sub) => {
      map.set(sub, {
        subject: sub,
        instructor: instructorMap[sub] || 'Senior High Faculty',
        color: colorMap[sub] || 'bg-accent',
        tasks: [],
      });
    });

    sortedDeliverables.forEach((t) => {
      if (!map.has(t.subject)) {
        map.set(t.subject, {
          subject: t.subject,
          instructor: t.instructor || instructorMap[t.subject] || 'Course Instructor',
          color: colorMap[t.subject] || 'bg-accent',
          tasks: [],
        });
      }
      map.get(t.subject)!.tasks.push(t);
    });

    return Array.from(map.values()).filter((g) => {
      if (subjectFilter !== 'all') return g.subject === subjectFilter;
      return g.tasks.length > 0;
    });
  }, [sortedDeliverables, subjectFilter]);

  // Kanban Columns Mapping - correctly synchronizes with kanbanColumn and status
  const kanbanColumns = useMemo(() => {
    const urgentList: StudentTask[] = [];
    const highList: StudentTask[] = [];
    const normalList: StudentTask[] = [];
    const submittedList: StudentTask[] = [];

    sortedDeliverables.forEach((t) => {
      if (t.status === 'submitted' || t.kanbanColumn === 'submitted') {
        submittedList.push(t);
      } else if (t.kanbanColumn === 'urgent') {
        urgentList.push(t);
      } else if (t.kanbanColumn === 'high') {
        highList.push(t);
      } else {
        normalList.push(t);
      }
    });

    return {
      urgent: urgentList,
      high: highList,
      normal: normalList,
      submitted: submittedList,
    };
  }, [sortedDeliverables]);

  // Status combo box options
  const statusOptions: ComboBoxOption[] = [
    { value: 'all', label: `All (${totalOutputs})` },
    { value: 'to-submit', label: `To Submit (${toSubmitDeliverablesCount})`, dotColor: 'bg-amber' },
    { value: 'in-progress', label: 'Under Review (0)', dotColor: 'bg-accent' },
    { value: 'submitted', label: `Submitted & Graded (${totalSubmitted})`, dotColor: 'bg-green' },
  ];

  // Subject combo box options
  const subjectOptions: ComboBoxOption[] = [
    { value: 'all', label: 'All Subjects' },
    { value: 'Oral Communication', label: 'Oral Communication', dotColor: 'bg-accent' },
    { value: 'Earth and Life Science', label: 'Earth and Life Science', dotColor: 'bg-green' },
    { value: 'Komunikasyon at Pananaliksik', label: 'Komunikasyon', dotColor: 'bg-amber' },
    { value: 'Empowerment Technologies', label: 'Empowerment Tech', dotColor: 'bg-cyan' },
  ];

  // Priority combo box options
  const priorityOptions: ComboBoxOption[] = [
    { value: 'all', label: 'All Priorities' },
    { value: 'urgent', label: 'Urgent & Due Soon (#1)', dotColor: 'bg-red' },
    { value: 'high', label: 'High Priority (This Week)', dotColor: 'bg-amber' },
    { value: 'normal', label: 'Normal / Scheduled', dotColor: 'bg-accent' },
  ];

  // Sort options
  const sortOptions: ComboBoxOption[] = [
    { value: 'urgency', label: 'Urgency / Due Date' },
    { value: 'points-high', label: 'Points (High to Low)' },
    { value: 'points-low', label: 'Points (Low to High)' },
    { value: 'subject', label: 'Subject Name (A-Z)' },
  ];

  const hasActiveFilters =
    statusFilter !== 'all' ||
    subjectFilter !== 'all' ||
    priorityFilter !== 'all' ||
    searchQuery.trim() !== '';

  const handleResetFilters = () => {
    setStatusFilter('all');
    setSubjectFilter('all');
    setPriorityFilter('all');
    setSearchQuery('');
  };

  return (
    <div
      className="space-y-5 animate-in fade-in duration-200"
      data-board-dragging={draggingTaskId !== null ? 'true' : 'false'}
    >
      {/* ============================================================ */}
      {/* 1. HEADER & MIDTERM PROGRESS WIDGET                          */}
      {/* ============================================================ */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h3 className="text-xl sm:text-2xl font-bold font-serif text-text tracking-tight">
            Performance Tasks & Submissions
          </h3>
          <p className="text-xs text-text2 mt-1">
            Upload demonstration videos, laboratory papers, and research outputs for rubric
            evaluation.
          </p>
        </div>

        {/* Midterm Output Progress Card */}
        <div className="bg-bg2 border border-border rounded-xl px-4 py-2.5 shadow-2xs min-w-[240px]">
          <div className="flex items-center justify-between text-[11px] font-semibold">
            <span className="text-text3 uppercase tracking-wider">Midterm Output Progress</span>
            <span className="text-text font-bold font-mono">
              {totalSubmitted} of {totalOutputs} Completed ({progressPercent}%)
            </span>
          </div>
          <div className="h-1.5 w-full bg-bg3 rounded-full mt-2 overflow-hidden">
            <div
              className="h-full bg-green rounded-full transition-all duration-500 ease-out"
              style={{ width: `${progressPercent}%` }}
            />
          </div>
        </div>
      </div>

      {/* ============================================================ */}
      {/* 2. URGENT PRIORITY CALLOUT BANNER                            */}
      {/* ============================================================ */}
      {urgentTask && (
        <div className="rounded-2xl border border-red/40 bg-red-bg/40 backdrop-blur-xs p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-2xs transition-all">
          <div className="flex items-center gap-3">
            <div className="size-10 rounded-xl bg-red text-white flex items-center justify-center shrink-0 shadow-xs">
              <RiFlashlightFill className="size-5" />
            </div>
            <div>
              <div className="flex items-center gap-2 flex-wrap">
                <span className="text-[10px] font-extrabold text-red uppercase tracking-wider">
                  URGENT PRIORITY #1
                </span>
                <span className="text-text3">·</span>
                <h4 className="text-sm font-bold text-text">{urgentTask.title}</h4>
              </div>
              <p className="text-xs text-text2 mt-0.5">
                Due <strong className="text-red font-semibold">{urgentTask.dueDate} at {urgentTask.dueTime}</strong>{' '}
                ({urgentTask.hoursLeft ? `less than ${urgentTask.hoursLeft} hours remaining` : 'imminent deadline'}).
                Rubric penalties apply for late uploads.
              </p>
            </div>
          </div>

          <button
            type="button"
            onClick={() => onSubmitTask(urgentTask)}
            className="shrink-0 px-4 py-2 rounded-xl bg-red hover:bg-red/90 text-white text-xs font-semibold cursor-pointer shadow-xs flex items-center gap-1.5 transition-all self-end sm:self-auto hover:translate-x-0.5"
          >
            <span>Submit Now</span>
            <RiArrowRightLine className="size-4" />
          </button>
        </div>
      )}

      {/* ============================================================ */}
      {/* 3. LINEAR TOOLBAR: CONTROLS & HOVERABLE COMBO BOX FILTERS    */}
      {/* ============================================================ */}
      <div className="bg-bg2 border border-border rounded-xl p-2.5 shadow-2xs flex flex-wrap items-center justify-between gap-3">
        {/* Left: View Mode Toggle & Linear Hoverable Combo Boxes */}
        <div className="flex items-center gap-2 flex-wrap">
          {/* Mode Switcher */}
          <div className="flex items-center bg-bg3 p-0.5 rounded-lg border border-border" role="group" aria-label="View mode">
            <TooltipTrigger delay={0} closeDelay={0}>
              <AriaButton
                onPress={() => setViewMode('subject')}
                aria-label="Group by Subject"
                aria-pressed={viewMode === 'subject'}
                className={`size-7 rounded-md flex items-center justify-center transition-colors duration-150 cursor-pointer outline-none focus-visible:ring-1 focus-visible:ring-accent ${
                  viewMode === 'subject'
                    ? 'bg-bg2 text-text shadow-2xs font-semibold'
                    : 'text-text3 hover:text-text hover:bg-bg2/50'
                }`}
              >
                <RiListUnordered className="size-4" />
              </AriaButton>
              <Tooltip placement="top" offset={10}>
                <span className="whitespace-nowrap font-medium">Group by Subject</span>
              </Tooltip>
            </TooltipTrigger>
            <TooltipTrigger delay={0} closeDelay={0}>
              <AriaButton
                onPress={() => setViewMode('kanban')}
                aria-label="Kanban / Priority View"
                aria-pressed={viewMode === 'kanban'}
                className={`size-7 rounded-md flex items-center justify-center transition-colors duration-150 cursor-pointer outline-none focus-visible:ring-1 focus-visible:ring-accent ${
                  viewMode === 'kanban'
                    ? 'bg-bg2 text-text shadow-2xs font-semibold'
                    : 'text-text3 hover:text-text hover:bg-bg2/50'
                }`}
              >
                <RiKanbanView className="size-4" />
              </AriaButton>
              <Tooltip placement="top" offset={10}>
                <span className="whitespace-nowrap font-medium">Kanban / Priority View</span>
              </Tooltip>
            </TooltipTrigger>
          </div>

          <div className="h-4 w-px bg-border mx-1 hidden sm:block" />

          {/* Hoverable Combo Box 1: Status */}
          <HoverableComboBox
            label="Status"
            value={statusFilter}
            options={statusOptions}
            onChange={setStatusFilter}
          />

          {/* Hoverable Combo Box 2: Subject */}
          <HoverableComboBox
            label="Subject"
            value={subjectFilter}
            options={subjectOptions}
            onChange={setSubjectFilter}
          />

          {/* Hoverable Combo Box 3: Priority */}
          <HoverableComboBox
            label="Priority"
            value={priorityFilter}
            options={priorityOptions}
            onChange={setPriorityFilter}
          />

          {/* Hoverable Combo Box 4: Sort */}
          <HoverableComboBox
            label="Sort"
            value={sortBy}
            options={sortOptions}
            onChange={setSortBy}
            icon={RiArrowUpDownLine}
          />

          {/* Reset Filters button */}
          {hasActiveFilters && (
            <button
              type="button"
              onClick={handleResetFilters}
              className="px-2.5 py-1.5 rounded-lg text-xs text-text3 hover:text-red hover:bg-red-bg transition-colors cursor-pointer flex items-center gap-1"
            >
              <RiCloseLine className="size-3.5" />
              <span>Reset</span>
            </button>
          )}
        </div>

        {/* Right: Search Box */}
        <div className="relative min-w-[200px] flex-1 sm:flex-initial">
          <RiSearchLine className="absolute left-2.5 top-1/2 -translate-y-1/2 size-3.5 text-text3 pointer-events-none" />
          <input
            type="text"
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            placeholder="Filter tasks..."
            className="w-full pl-8 pr-7 py-1.5 rounded-lg bg-bg border border-border text-xs text-text placeholder:text-text3 focus:outline-none focus:border-accent transition-colors"
          />
          {searchQuery && (
            <button
              type="button"
              onClick={() => setSearchQuery('')}
              className="absolute right-2 top-1/2 -translate-y-1/2 text-text3 hover:text-text p-0.5"
            >
              <RiCloseLine className="size-3.5" />
            </button>
          )}
        </div>
      </div>

      {/* ============================================================ */}
      {/* 4. VIEW 1: GROUPED BY SUBJECT (LINEAR TICKETING STYLE)       */}
      {/* ============================================================ */}
      {viewMode === 'subject' && (
        <div className="space-y-6">
          {subjectGroups.map((group) => {
            const isDraggingActive = draggingTaskId !== null;
            const draggingTask = isDraggingActive ? deliverables.find((t) => t.id === draggingTaskId) : null;
            const isSourceSubject = isDraggingActive && group.subject === draggingTask?.subject;
            const isTargetSubject = isDraggingActive && (
              dragOverSubject === group.subject ||
              group.tasks.some((t) => t.id === dragOverTaskId)
            );
            // In Group by Subject mode, since all task cards auto-collapse into compact ~44px rows during drag,
            // keep open subject groups open during drag.
            // Only collapse a subject if manually collapsed, and if a manually-collapsed subject is hovered during drag,
            // temporarily expand it so the user can drop into it.
            const isManuallyCollapsed = Boolean(collapsedSubjects[group.subject]);
            const isCollapsed = isDraggingActive
              ? (isManuallyCollapsed && !isTargetSubject && !isSourceSubject)
              : isManuallyCollapsed;
            const activeTasks = group.tasks;
            const hasUrgent = activeTasks.some(
              (t) => (t.kanbanColumn === 'urgent' || t.priorityRank === 1) && t.status !== 'submitted'
            );

            return (
              <div
                key={group.subject}
                onDragOver={(e) => {
                  e.preventDefault();
                  e.dataTransfer.dropEffect = 'move';
                  if (dragOverSubject !== group.subject) {
                    setDragOverSubject(group.subject);
                  }
                }}
                className="space-y-3"
              >
                {/* Subject Group Header - Acts as smooth dropzone during drag */}
                <div
                  onClick={() => toggleSubjectCollapse(group.subject)}
                  onDrop={(e) => handleDropOnSubject(e, group.subject)}
                  className={`flex items-center justify-between py-2 px-2.5 rounded-lg border-b border-border/80 cursor-pointer select-none group transition-all duration-150 ${
                    dragOverSubject === group.subject
                      ? 'border-accent bg-accent/10 ring-2 ring-accent/30'
                      : isDraggingActive && isSourceSubject
                      ? 'bg-accent/5 border-accent/40'
                      : 'hover:bg-bg2/60'
                  }`}
                >
                  <div className="flex items-center gap-2.5">
                    <span className={`size-2.5 rounded-full shrink-0 ${group.color}`} />
                    <h4 className="text-xs font-bold uppercase tracking-wider text-text font-mono group-hover:text-accent transition-colors">
                      {group.subject}
                    </h4>
                    <span className="text-xs text-text3 font-normal hidden sm:inline">
                      | Instructor: {group.instructor}
                    </span>
                    {isDraggingActive && dragOverSubject === group.subject && (
                      <span className="text-[10px] font-bold px-1.5 py-0.5 rounded bg-accent text-white uppercase tracking-wider animate-in fade-in duration-100">
                        Drop to move here
                      </span>
                    )}
                  </div>

                  <div className="flex items-center gap-2">
                    {hasUrgent && (
                      <span className="px-2 py-0.5 rounded text-[11px] font-bold bg-red-bg text-red">
                        1 Task Due Tomorrow
                      </span>
                    )}
                    <span className="text-xs text-text3 font-mono">
                      {activeTasks.length} {activeTasks.length === 1 ? 'Task' : 'Tasks'}
                    </span>
                    <button
                      type="button"
                      aria-label={isCollapsed ? `Expand ${group.subject}` : `Collapse ${group.subject}`}
                      aria-expanded={!isCollapsed}
                      className="p-1 rounded text-text3 group-hover:text-text transition-transform"
                    >
                      <RiArrowDownSLine
                        className={`size-4 transition-transform duration-200 ${
                          isCollapsed ? '-rotate-90' : 'rotate-0'
                        }`}
                      />
                    </button>
                  </div>
                </div>

                {/* Tasks List for this Subject - Smooth CSS Grid Auto-Collapse */}
                <div
                  className="subject-group-tasks-collapse"
                  data-collapsed={isCollapsed ? 'true' : 'false'}
                >
                  <div className="min-h-0 space-y-2.5 pl-1 overflow-hidden">
                    {activeTasks.length === 0 ? (
                      <div
                        onDragOver={(e) => {
                          e.preventDefault();
                          e.dataTransfer.dropEffect = 'move';
                          if (dragOverSubject !== group.subject) {
                            setDragOverSubject(group.subject);
                          }
                        }}
                        onDrop={(e) => handleDropOnSubject(e, group.subject)}
                        className={`p-6 rounded-xl border border-dashed text-center text-xs transition-colors ${
                          isDraggingActive && dragOverSubject === group.subject
                            ? 'border-accent bg-accent/10 text-accent font-medium'
                            : 'bg-bg2 border-border text-text3'
                        }`}
                      >
                        {isDraggingActive && dragOverSubject === group.subject
                          ? `Drop task here to assign to ${group.subject}`
                          : 'No active performance tasks under this filter.'}
                      </div>
                    ) : (
                      activeTasks.map((task) => (
                        <LinearTaskCard
                          key={task.id}
                          task={task}
                          viewMode="subject"
                          isDragging={draggingTaskId === task.id}
                          isDragOver={dragOverTaskId === task.id}
                          isCompressed={draggingTaskId !== null}
                          onDragStart={handleDragStart}
                          onDragEnd={handleDragEnd}
                          onDragOver={handleDragOver}
                          onDrop={handleDropOnTask}
                          onOpenRubric={setActiveRubricTask}
                          onOpenDraft={setActiveDraftTask}
                          onSubmit={onSubmitTask}
                        />
                      ))
                    )}
                  </div>
                </div>
              </div>
            );
          })}
        </div>
      )}

      {/* ============================================================ */}
      {/* 5. VIEW 2: KANBAN / PRIORITY VIEW (LINEAR COLUMN BOARD)       */}
      {/* ============================================================ */}
      {viewMode === 'kanban' && (
        <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 items-start">
          {/* COLUMN 1: URGENT & DUE SOON */}
          <div
            onDragOver={(e) => {
              e.preventDefault();
              e.dataTransfer.dropEffect = 'move';
              if (dragOverColumn !== 'urgent') setDragOverColumn('urgent');
            }}
            onDrop={(e) => handleDropOnColumn(e, 'urgent')}
            className={`rounded-2xl bg-bg2 border p-3.5 space-y-3 shadow-2xs transition-colors ${
              dragOverColumn === 'urgent' ? 'border-red ring-2 ring-red/20 bg-red-bg/10' : 'border-border'
            }`}
          >
            <div className="flex items-center justify-between border-b border-border pb-2.5">
              <div className="flex items-center gap-2">
                <span className="size-2 rounded-full bg-red" />
                <h4 className="text-xs font-bold uppercase tracking-wider text-text font-mono">
                  URGENT & DUE SOON
                </h4>
              </div>
              <span className="px-1.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-red-bg text-red">
                {kanbanColumns.urgent.length}
              </span>
            </div>

            <div className="space-y-2.5 min-h-[300px]">
              {kanbanColumns.urgent.map((task) => (
                <LinearTaskCard
                  key={task.id}
                  task={task}
                  viewMode="kanban"
                  isDragging={draggingTaskId === task.id}
                  isDragOver={dragOverTaskId === task.id}
                  isCompressed={draggingTaskId !== null}
                  onDragStart={handleDragStart}
                  onDragEnd={handleDragEnd}
                  onDragOver={handleDragOver}
                  onDrop={handleDropOnTask}
                  onOpenRubric={setActiveRubricTask}
                  onOpenDraft={setActiveDraftTask}
                  onSubmit={onSubmitTask}
                />
              ))}

              <button
                type="button"
                onClick={() => {
                  setAddStudyColumn('urgent');
                  setIsAddStudyModalOpen(true);
                }}
                className="w-full py-2 rounded-xl border border-dashed border-border hover:border-red/40 hover:bg-red-bg/20 text-xs text-text3 hover:text-red transition-all cursor-pointer flex items-center justify-center gap-1 font-medium"
              >
                <RiAddLine className="size-3.5" />
                <span>Add Urgent Reminder</span>
              </button>
            </div>
          </div>

          {/* COLUMN 2: HIGH PRIORITY (THIS WEEK) */}
          <div
            onDragOver={(e) => {
              e.preventDefault();
              e.dataTransfer.dropEffect = 'move';
              if (dragOverColumn !== 'high') setDragOverColumn('high');
            }}
            onDrop={(e) => handleDropOnColumn(e, 'high')}
            className={`rounded-2xl bg-bg2 border p-3.5 space-y-3 shadow-2xs transition-colors ${
              dragOverColumn === 'high' ? 'border-amber ring-2 ring-amber/20 bg-amber-bg/10' : 'border-border'
            }`}
          >
            <div className="flex items-center justify-between border-b border-border pb-2.5">
              <div className="flex items-center gap-2">
                <span className="size-2 rounded-full bg-amber" />
                <h4 className="text-xs font-bold uppercase tracking-wider text-text font-mono">
                  HIGH PRIORITY (THIS WEEK)
                </h4>
              </div>
              <span className="px-1.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-amber-bg text-amber">
                {kanbanColumns.high.length}
              </span>
            </div>

            <div className="space-y-2.5 min-h-[300px]">
              {kanbanColumns.high.map((task) => (
                <LinearTaskCard
                  key={task.id}
                  task={task}
                  viewMode="kanban"
                  isDragging={draggingTaskId === task.id}
                  isDragOver={dragOverTaskId === task.id}
                  isCompressed={draggingTaskId !== null}
                  onDragStart={handleDragStart}
                  onDragEnd={handleDragEnd}
                  onDragOver={handleDragOver}
                  onDrop={handleDropOnTask}
                  onOpenRubric={setActiveRubricTask}
                  onOpenDraft={setActiveDraftTask}
                  onSubmit={onSubmitTask}
                />
              ))}

              <button
                type="button"
                onClick={() => {
                  setAddStudyColumn('high');
                  setIsAddStudyModalOpen(true);
                }}
                className="w-full py-2 rounded-xl border border-dashed border-border hover:border-amber/40 hover:bg-amber-bg/20 text-xs text-text3 hover:text-amber transition-all cursor-pointer flex items-center justify-center gap-1 font-medium"
              >
                <RiAddLine className="size-3.5" />
                <span>Add Custom Study Task</span>
              </button>
            </div>
          </div>

          {/* COLUMN 3: NORMAL / SCHEDULED */}
          <div
            onDragOver={(e) => {
              e.preventDefault();
              e.dataTransfer.dropEffect = 'move';
              if (dragOverColumn !== 'normal') setDragOverColumn('normal');
            }}
            onDrop={(e) => handleDropOnColumn(e, 'normal')}
            className={`rounded-2xl bg-bg2 border p-3.5 space-y-3 shadow-2xs transition-colors ${
              dragOverColumn === 'normal' ? 'border-accent ring-2 ring-accent/20 bg-accent/10' : 'border-border'
            }`}
          >
            <div className="flex items-center justify-between border-b border-border pb-2.5">
              <div className="flex items-center gap-2">
                <span className="size-2 rounded-full bg-accent" />
                <h4 className="text-xs font-bold uppercase tracking-wider text-text font-mono">
                  NORMAL / SCHEDULED
                </h4>
              </div>
              <span className="px-1.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-accent/15 text-accent">
                {kanbanColumns.normal.length}
              </span>
            </div>

            <div className="space-y-2.5 min-h-[300px]">
              {kanbanColumns.normal.map((task) => (
                <LinearTaskCard
                  key={task.id}
                  task={task}
                  viewMode="kanban"
                  isDragging={draggingTaskId === task.id}
                  isDragOver={dragOverTaskId === task.id}
                  isCompressed={draggingTaskId !== null}
                  onDragStart={handleDragStart}
                  onDragEnd={handleDragEnd}
                  onDragOver={handleDragOver}
                  onDrop={handleDropOnTask}
                  onOpenRubric={setActiveRubricTask}
                  onOpenDraft={setActiveDraftTask}
                  onSubmit={onSubmitTask}
                />
              ))}

              <button
                type="button"
                onClick={() => {
                  setAddStudyColumn('normal');
                  setIsAddStudyModalOpen(true);
                }}
                className="w-full py-2 rounded-xl border border-dashed border-border hover:border-accent/40 hover:bg-accent/5 text-xs text-text3 hover:text-accent transition-all cursor-pointer flex items-center justify-center gap-1 font-medium"
              >
                <RiAddLine className="size-3.5" />
                <span>Add Scheduled Task</span>
              </button>
            </div>
          </div>

          {/* COLUMN 4: SUBMITTED & GRADED */}
          <div
            onDragOver={(e) => {
              e.preventDefault();
              e.dataTransfer.dropEffect = 'move';
              if (dragOverColumn !== 'submitted') setDragOverColumn('submitted');
            }}
            onDrop={(e) => handleDropOnColumn(e, 'submitted')}
            className={`rounded-2xl bg-bg2 border p-3.5 space-y-3 shadow-2xs transition-colors ${
              dragOverColumn === 'submitted' ? 'border-green ring-2 ring-green/20 bg-green-bg/10' : 'border-border'
            }`}
          >
            <div className="flex items-center justify-between border-b border-border pb-2.5">
              <div className="flex items-center gap-2">
                <span className="size-2 rounded-full bg-green" />
                <h4 className="text-xs font-bold uppercase tracking-wider text-text font-mono">
                  SUBMITTED & GRADED
                </h4>
              </div>
              <span className="px-1.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-green-bg text-green">
                {totalSubmitted}
              </span>
            </div>

            <div className="space-y-2.5 min-h-[300px]">
              {/* Recently graded deliverables */}
              {kanbanColumns.submitted.map((task) => (
                <LinearTaskCard
                  key={task.id}
                  task={task}
                  viewMode="kanban"
                  isDragging={draggingTaskId === task.id}
                  isDragOver={dragOverTaskId === task.id}
                  isCompressed={draggingTaskId !== null}
                  onDragStart={handleDragStart}
                  onDragEnd={handleDragEnd}
                  onDragOver={handleDragOver}
                  onDrop={handleDropOnTask}
                  onOpenRubric={setActiveRubricTask}
                  onOpenDraft={setActiveDraftTask}
                  onSubmit={onSubmitTask}
                />
              ))}

              {/* Collapsible Archive of Older Graded Submissions (matching Screenshot 2) */}
              <div className="rounded-xl border border-border bg-bg p-3 space-y-2">
                <div className="flex items-center justify-between text-xs">
                  <span className="font-bold text-text">47 Older Graded Outputs</span>
                  <span className="text-[10px] font-bold px-1.5 py-0.5 rounded bg-green-bg text-green uppercase">
                    All Passed
                  </span>
                </div>
                <p className="text-[11px] text-text3">
                  Prelim essays, laboratory journals, and interactive quizzes.
                </p>

                <button
                  type="button"
                  onClick={() => setIsArchiveExpanded((prev) => !prev)}
                  className="w-full py-1 text-xs text-accent hover:underline flex items-center justify-center gap-1 font-medium cursor-pointer"
                >
                  <span>
                    {isArchiveExpanded ? 'Hide Archived Submissions' : 'Expand Archived Submissions'}
                  </span>
                  {isArchiveExpanded ? (
                    <RiArrowDownSLine className="size-3.5 rotate-180" />
                  ) : (
                    <RiArrowDownSLine className="size-3.5" />
                  )}
                </button>

                {isArchiveExpanded && (
                  <div className="pt-2 border-t border-border space-y-2 animate-in fade-in duration-150">
                    {ARCHIVED_SUBMISSIONS.map((arch) => (
                      <div
                        key={arch.id}
                        className="p-2 rounded-lg bg-bg2 border border-border/70 text-[11px] flex items-center justify-between"
                      >
                        <div>
                          <div className="font-semibold text-text truncate max-w-[150px]">
                            {arch.title}
                          </div>
                          <div className="text-[10px] text-text3">{arch.date}</div>
                        </div>
                        <div className="text-right shrink-0">
                          <span className="font-mono font-bold text-green">
                            {arch.score}/{arch.maxScore}
                          </span>
                          <span className="text-[10px] text-text3 ml-1">({arch.gradeLetter})</span>
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      )}

      {/* ============================================================ */}
      {/* 6. MODALS & FLYOUTS                                          */}
      {/* ============================================================ */}
      <RubricModal
        task={activeRubricTask}
        onClose={() => setActiveRubricTask(null)}
        onSubmit={onSubmitTask}
      />

      <DraftNoteModal
        key={activeDraftTask?.id ?? 'draft-modal'}
        task={activeDraftTask}
        onClose={() => setActiveDraftTask(null)}
        onSaveNote={handleSaveDraftNote}
      />

      <AddStudyTaskModal
        key={addStudyColumn}
        isOpen={isAddStudyModalOpen}
        onClose={() => setIsAddStudyModalOpen(false)}
        onAdd={handleAddCustomTask}
        defaultColumn={addStudyColumn}
      />
    </div>
  );
};

export default StudentPerformanceTasksView;
