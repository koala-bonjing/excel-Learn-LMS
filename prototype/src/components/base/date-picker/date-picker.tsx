import React, { useState, useRef, useEffect, useMemo } from 'react';
import {
  RiCalendarLine,
  RiArrowLeftSLine,
  RiArrowRightSLine,
  RiCheckLine,
} from '@remixicon/react';
import { Popover, Dialog } from '@/components/base/popover';

export interface DatePickerProps {
  value?: Date | string | null;
  onChange: (date: Date) => void;
  label?: string;
  placeholder?: string;
  minDate?: Date;
  maxDate?: Date;
  className?: string;
  buttonClassName?: string;
  align?: 'left' | 'right';
  showPresets?: boolean;
  size?: 'small' | 'medium';
  disabled?: boolean;
}

const MONTH_NAMES = [
  'January', 'February', 'March', 'April', 'May', 'June',
  'July', 'August', 'September', 'October', 'November', 'December',
];

const WEEKDAY_SHORT = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];

const isSameDay = (a: Date, b: Date) =>
  a.getFullYear() === b.getFullYear() &&
  a.getMonth() === b.getMonth() &&
  a.getDate() === b.getDate();

export const DatePicker: React.FC<DatePickerProps> = ({
  value,
  onChange,
  label,
  placeholder = 'Select date...',
  minDate,
  maxDate,
  className = '',
  buttonClassName = '',
  align = 'left',
  showPresets = true,
  size = 'small',
  disabled = false,
}) => {
  const [isOpen, setIsOpen] = useState(false);
  const triggerRef = useRef<HTMLButtonElement | null>(null);

  // Parse initial selected date
  const selectedDate = useMemo(() => {
    if (!value) return null;
    if (value instanceof Date) return isNaN(value.getTime()) ? null : value;
    const parsed = new Date(value);
    return isNaN(parsed.getTime()) ? null : parsed;
  }, [value]);

  // Calendar view navigation state (year & month currently displayed)
  const [today] = useState(() => new Date());
  const [viewYear, setViewYear] = useState(() => (selectedDate ? selectedDate.getFullYear() : today.getFullYear()));
  const [viewMonth, setViewMonth] = useState(() => (selectedDate ? selectedDate.getMonth() : today.getMonth()));

  // Keep view in sync when value changes externally
  const prevSelectedRef = useRef<Date | null>(selectedDate);
  useEffect(() => {
    if (selectedDate && (!prevSelectedRef.current || !isSameDay(selectedDate, prevSelectedRef.current))) {
      prevSelectedRef.current = selectedDate;
      setViewYear(selectedDate.getFullYear());
      setViewMonth(selectedDate.getMonth());
    }
  }, [selectedDate]);

  // Calendar grid calculation (35 or 42 slots)
  const calendarSlots = useMemo(() => {
    const firstDayIndex = new Date(viewYear, viewMonth, 1).getDay();
    const daysInMonth = new Date(viewYear, viewMonth + 1, 0).getDate();
    const daysInPrevMonth = new Date(viewYear, viewMonth, 0).getDate();

    const slots: Array<{
      dayNumber: number;
      date: Date;
      isCurrentMonth: boolean;
      isToday: boolean;
      isSelected: boolean;
      isDisabled: boolean;
    }> = [];

    // Prev month padding
    for (let i = firstDayIndex - 1; i >= 0; i--) {
      const dNum = daysInPrevMonth - i;
      const d = new Date(viewYear, viewMonth - 1, dNum);
      const isDis = (minDate && d < minDate) || (maxDate && d > maxDate) || false;
      slots.push({
        dayNumber: dNum,
        date: d,
        isCurrentMonth: false,
        isToday: isSameDay(d, today),
        isSelected: !!selectedDate && isSameDay(d, selectedDate),
        isDisabled: isDis,
      });
    }

    // Current month days
    for (let i = 1; i <= daysInMonth; i++) {
      const d = new Date(viewYear, viewMonth, i);
      const isDis = (minDate && d < minDate) || (maxDate && d > maxDate) || false;
      slots.push({
        dayNumber: i,
        date: d,
        isCurrentMonth: true,
        isToday: isSameDay(d, today),
        isSelected: !!selectedDate && isSameDay(d, selectedDate),
        isDisabled: isDis,
      });
    }

    // Next month padding to fill complete grid
    const totalSlots = slots.length <= 35 ? 35 : 42;
    const remaining = totalSlots - slots.length;
    for (let i = 1; i <= remaining; i++) {
      const d = new Date(viewYear, viewMonth + 1, i);
      const isDis = (minDate && d < minDate) || (maxDate && d > maxDate) || false;
      slots.push({
        dayNumber: i,
        date: d,
        isCurrentMonth: false,
        isToday: isSameDay(d, today),
        isSelected: !!selectedDate && isSameDay(d, selectedDate),
        isDisabled: isDis,
      });
    }

    return slots;
  }, [viewYear, viewMonth, today, selectedDate, minDate, maxDate]);

  const handlePrevMonth = () => {
    if (viewMonth === 0) {
      setViewMonth(11);
      setViewYear((y) => y - 1);
    } else {
      setViewMonth((m) => m - 1);
    }
  };

  const handleNextMonth = () => {
    if (viewMonth === 11) {
      setViewMonth(0);
      setViewYear((y) => y + 1);
    } else {
      setViewMonth((m) => m + 1);
    }
  };

  const handleSelectDate = (date: Date) => {
    onChange(date);
    setViewYear(date.getFullYear());
    setViewMonth(date.getMonth());
    setIsOpen(false);
  };

  // Preset handlers
  const handlePresetToday = () => {
    handleSelectDate(new Date());
  };

  const handlePresetTomorrow = () => {
    const d = new Date();
    d.setDate(d.getDate() + 1);
    handleSelectDate(d);
  };

  const handlePresetNextWeek = () => {
    const d = new Date();
    d.setDate(d.getDate() + 7);
    handleSelectDate(d);
  };

  const formattedDisplayText = useMemo(() => {
    if (!selectedDate) return placeholder;
    return selectedDate.toLocaleDateString('en-US', {
      weekday: 'short',
      month: 'short',
      day: 'numeric',
      year: 'numeric',
    });
  }, [selectedDate, placeholder]);

  const yearOptions = useMemo(() => {
    const current = today.getFullYear();
    return [current - 1, current, current + 1, current + 2];
  }, [today]);

  return (
    <div className={`relative inline-block ${className}`}>
      {label && (
        <label className="block text-xs font-semibold text-text2 mb-1.5">
          {label}
        </label>
      )}

      {/* Trigger Button */}
      <button
        ref={triggerRef}
        type="button"
        disabled={disabled}
        onClick={() => setIsOpen((prev) => !prev)}
        aria-haspopup="dialog"
        aria-expanded={isOpen}
        className={`group inline-flex items-center justify-between gap-2 rounded-xl border transition-all cursor-pointer select-none outline-none focus:border-accent focus:ring-1 focus:ring-accent ${
          size === 'small' ? 'px-2.5 py-1.5 text-xs' : 'px-3.5 py-2 text-sm'
        } ${
          isOpen
            ? 'border-accent bg-bg4 text-text ring-1 ring-accent/30'
            : 'border-border bg-bg3 hover:bg-bg4 text-text'
        } ${disabled ? 'opacity-50 cursor-not-allowed pointer-events-none' : ''} ${buttonClassName}`}
      >
        <span className="flex items-center gap-1.5 truncate">
          <RiCalendarLine className="size-3.5 text-accent shrink-0" aria-hidden="true" />
          <span className={`truncate font-medium ${!selectedDate ? 'text-text3' : 'text-text'}`}>
            {formattedDisplayText}
          </span>
        </span>
      </button>

      {/* BoardUI Popover with Interactive Calendar Grid */}
      <Popover
        isOpen={isOpen}
        onOpenChange={setIsOpen}
        triggerRef={triggerRef}
        offset={8}
        placement={align === 'right' ? 'bottom end' : 'bottom start'}
        maxWidthClassName="w-[300px]"
      >
        <Dialog aria-label="Date Picker Calendar" className="p-3.5 outline-none space-y-3">
          {/* Header Controls: Month/Year navigation */}
          <div className="flex items-center justify-between gap-1 pb-2 border-b border-border/80">
            <div className="flex items-center gap-1.5">
              <select
                value={viewMonth}
                onChange={(e) => setViewMonth(Number(e.target.value))}
                aria-label="Select month"
                className="px-2 py-1 rounded-lg bg-bg3 hover:bg-bg4 border border-border text-xs font-semibold text-text outline-none cursor-pointer"
              >
                {MONTH_NAMES.map((name, idx) => (
                  <option key={name} value={idx}>
                    {name}
                  </option>
                ))}
              </select>

              <select
                value={viewYear}
                onChange={(e) => setViewYear(Number(e.target.value))}
                aria-label="Select year"
                className="px-2 py-1 rounded-lg bg-bg3 hover:bg-bg4 border border-border text-xs font-semibold text-text outline-none cursor-pointer"
              >
                {yearOptions.map((yr) => (
                  <option key={yr} value={yr}>
                    {yr}
                  </option>
                ))}
              </select>
            </div>

            <div className="inline-flex rounded-lg bg-bg3 p-0.5 border border-border">
              <button
                type="button"
                onClick={handlePrevMonth}
                aria-label="Previous month"
                className="p-1 rounded text-text3 hover:text-text hover:bg-bg4 cursor-pointer transition-colors"
              >
                <RiArrowLeftSLine className="size-4" />
              </button>
              <button
                type="button"
                onClick={handleNextMonth}
                aria-label="Next month"
                className="p-1 rounded text-text3 hover:text-text hover:bg-bg4 cursor-pointer transition-colors"
              >
                <RiArrowRightSLine className="size-4" />
              </button>
            </div>
          </div>

          {/* Weekday Row */}
          <div className="grid grid-cols-7 text-center">
            {WEEKDAY_SHORT.map((wd) => (
              <span
                key={wd}
                className="text-[10px] font-bold uppercase text-text3 tracking-wider py-1"
              >
                {wd}
              </span>
            ))}
          </div>

          {/* Calendar Day Slots Matrix */}
          <div className="grid grid-cols-7 gap-1">
            {calendarSlots.map((slot, index) => {
              const { dayNumber, date, isCurrentMonth, isToday, isSelected, isDisabled } = slot;

              return (
                <button
                  key={`${date.toISOString()}-${index}`}
                  type="button"
                  disabled={isDisabled}
                  onClick={() => handleSelectDate(date)}
                  className={`size-8 rounded-lg text-xs font-medium flex items-center justify-center transition-all cursor-pointer relative ${
                    isDisabled
                      ? 'opacity-25 cursor-not-allowed pointer-events-none text-text3'
                      : isSelected
                      ? 'bg-accent text-white font-bold shadow-sm scale-105'
                      : isToday
                      ? 'bg-accent/15 text-accent font-bold ring-1 ring-accent/40 hover:bg-accent/25'
                      : isCurrentMonth
                      ? 'text-text hover:bg-bg3 hover:text-text'
                      : 'text-text3/50 hover:bg-bg3/50'
                  }`}
                  title={date.toDateString()}
                >
                  <span>{dayNumber}</span>
                  {isToday && !isSelected && (
                    <span className="absolute bottom-1 size-1 rounded-full bg-accent" />
                  )}
                </button>
              );
            })}
          </div>

          {/* Presets Footer */}
          {showPresets && (
            <div className="pt-2 border-t border-border/80 flex items-center justify-between gap-1 text-[11px]">
              <div className="flex items-center gap-1">
                <button
                  type="button"
                  onClick={handlePresetToday}
                  className="px-2 py-1 rounded-md bg-bg3 hover:bg-bg4 text-text2 hover:text-text font-semibold cursor-pointer transition-colors"
                >
                  Today
                </button>
                <button
                  type="button"
                  onClick={handlePresetTomorrow}
                  className="px-2 py-1 rounded-md bg-bg3 hover:bg-bg4 text-text2 hover:text-text font-semibold cursor-pointer transition-colors"
                >
                  Tomorrow
                </button>
                <button
                  type="button"
                  onClick={handlePresetNextWeek}
                  className="px-2 py-1 rounded-md bg-bg3 hover:bg-bg4 text-text2 hover:text-text font-semibold cursor-pointer transition-colors"
                >
                  +1 Wk
                </button>
              </div>

              {selectedDate && (
                <button
                  type="button"
                  onClick={() => setIsOpen(false)}
                  className="px-2.5 py-1 rounded-md bg-accent/15 text-accent font-bold hover:bg-accent/25 cursor-pointer flex items-center gap-1 transition-colors"
                >
                  <RiCheckLine className="size-3" />
                  <span>Done</span>
                </button>
              )}
            </div>
          )}
        </Dialog>
      </Popover>
    </div>
  );
};
