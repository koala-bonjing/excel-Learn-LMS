"use client";

import React, { useState, useRef, useEffect, useId } from "react";
import { RiArrowDownSLine, RiCheckLine } from "@remixicon/react";
import { cx } from "@/utils/cx";

export interface HoverComboboxOption<T extends string = string> {
  value: T;
  label: string;
  badge?: string | number;
  badgeVariant?: "accent" | "cyan" | "green" | "amber" | "neutral";
  icon?: React.ComponentType<{ className?: string; "aria-hidden"?: boolean | "true" | "false" }>;
  description?: string;
}

export interface HoverComboboxProps<T extends string = string> {
  id?: string;
  value: T;
  onChange: (value: T) => void;
  options: HoverComboboxOption<T>[];
  title?: string;
  prefixIcon?: React.ComponentType<{ className?: string; "aria-hidden"?: boolean | "true" | "false" }>;
  align?: "left" | "right";
  menuWidthClassName?: string;
  className?: string;
  buttonClassName?: string;
  activeVariant?: "default" | "cyan" | "green" | "amber" | "accent";
  ariaLabel?: string;
  size?: "sm" | "md";
  showCountBadge?: boolean;
}

export function HoverCombobox<T extends string = string>({
  id: customId,
  value,
  onChange,
  options,
  title,
  prefixIcon: CustomPrefixIcon,
  align = "left",
  menuWidthClassName,
  className,
  buttonClassName,
  activeVariant = "default",
  ariaLabel,
  size = "sm",
  showCountBadge = false,
}: HoverComboboxProps<T>) {
  const generatedId = useId();
  const comboboxId = customId || generatedId;
  const [isOpen, setIsOpen] = useState(false);
  const timerRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const containerRef = useRef<HTMLDivElement>(null);

  const selectedOption = options.find((opt) => opt.value === value) || options[0];
  const DisplayIcon = CustomPrefixIcon || selectedOption?.icon;

  const handleMouseEnter = () => {
    if (timerRef.current) {
      clearTimeout(timerRef.current);
      timerRef.current = null;
    }
    setIsOpen(true);
  };

  const handleMouseLeave = () => {
    timerRef.current = setTimeout(() => {
      setIsOpen(false);
    }, 180);
  };

  const handleTriggerClick = () => {
    setIsOpen((prev) => !prev);
  };

  const handleSelect = (nextValue: T) => {
    onChange(nextValue);
    setIsOpen(false);
  };

  // Click outside and Escape key listeners
  useEffect(() => {
    if (!isOpen) return;

    const handleOutsideClick = (e: MouseEvent) => {
      if (containerRef.current && !containerRef.current.contains(e.target as Node)) {
        setIsOpen(false);
      }
    };

    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === "Escape") {
        setIsOpen(false);
      }
    };

    document.addEventListener("mousedown", handleOutsideClick);
    document.addEventListener("keydown", handleKeyDown);
    return () => {
      document.removeEventListener("mousedown", handleOutsideClick);
      document.removeEventListener("keydown", handleKeyDown);
    };
  }, [isOpen]);

  // Cleanup pending timer on unmount
  useEffect(() => {
    return () => {
      if (timerRef.current) {
        clearTimeout(timerRef.current);
      }
    };
  }, []);

  // Variant styles for the trigger button
  const getVariantClasses = () => {
    if (activeVariant === "cyan") {
      return "bg-cyan-bg text-cyan border-cyan/40 shadow-xs ring-1 ring-cyan/20";
    }
    if (activeVariant === "green") {
      return "bg-green-bg text-green border-green/40 shadow-xs ring-1 ring-green/20";
    }
    if (activeVariant === "amber") {
      return "bg-amber-bg text-amber border-amber/40 shadow-xs ring-1 ring-amber/20";
    }
    if (activeVariant === "accent") {
      return "bg-accent/10 text-accent border-accent/40 shadow-xs ring-1 ring-accent/20 font-bold";
    }
    return "bg-bg border-border text-text hover:bg-bg3 hover:border-border2";
  };

  return (
    <div
      ref={containerRef}
      className={cx("relative inline-block text-left", className)}
      onMouseEnter={handleMouseEnter}
      onMouseLeave={handleMouseLeave}
    >
      <button
        id={comboboxId}
        type="button"
        onClick={handleTriggerClick}
        aria-haspopup="listbox"
        aria-expanded={isOpen}
        aria-label={ariaLabel || title || selectedOption?.label}
        className={cx(
          "flex items-center justify-between gap-2 rounded-xl border text-xs font-semibold cursor-pointer transition-all shadow-2xs select-none",
          size === "sm" ? "px-3 py-1.5" : "px-3.5 py-2",
          getVariantClasses(),
          buttonClassName,
        )}
      >
        <div className="flex items-center gap-1.5 min-w-0">
          {DisplayIcon && (
            <DisplayIcon
              className={cx(
                "size-3.5 shrink-0",
                activeVariant === "cyan"
                  ? "text-cyan"
                  : activeVariant === "green"
                  ? "text-green"
                  : activeVariant === "accent"
                  ? "text-accent"
                  : "text-text3",
              )}
              aria-hidden="true"
            />
          )}
          <span className="truncate">{selectedOption?.label}</span>
        </div>

        <div className="flex items-center gap-1 shrink-0 ml-1">
          {showCountBadge && selectedOption?.badge !== undefined && (
            <span className="text-[10px] font-mono px-1.5 py-0.2 rounded bg-bg2 text-text3 border border-border/50">
              {selectedOption.badge}
            </span>
          )}
          <RiArrowDownSLine
            className={cx(
              "size-3.5 text-text3 transition-transform duration-200",
              isOpen && "rotate-180",
            )}
            aria-hidden="true"
          />
        </div>
      </button>

      {isOpen && (
        <div
          className={cx(
            "absolute top-full z-40 pt-1.5 animate-in fade-in zoom-in-95 duration-100",
            align === "right" ? "right-0" : "left-0",
            menuWidthClassName || "w-56 max-w-[calc(100vw-32px)]",
          )}
        >
          <div
            role="listbox"
            aria-labelledby={comboboxId}
            className="rounded-2xl bg-bg2 border border-border shadow-2xl p-1.5 space-y-0.5 ring-1 ring-border/80 max-h-72 overflow-y-auto no-scrollbar"
            style={{ scrollbarWidth: "none", msOverflowStyle: "none" }}
          >
            {title && (
              <div className="px-2.5 py-1 text-[10px] uppercase font-bold text-text3 tracking-wider select-none">
                {title}
              </div>
            )}

            {options.map((opt) => {
              const isSelected = opt.value === value;
              const OptIcon = opt.icon;

              return (
                <button
                  key={opt.value}
                  type="button"
                  role="option"
                  aria-selected={isSelected}
                  onClick={() => handleSelect(opt.value)}
                  className={cx(
                    "w-full flex items-center justify-between px-2.5 py-2 rounded-xl text-xs font-semibold transition-colors cursor-pointer text-left",
                    isSelected
                      ? "bg-accent/10 text-accent font-bold"
                      : "text-text hover:bg-bg3",
                  )}
                >
                  <div className="flex items-center gap-2 min-w-0">
                    {OptIcon && (
                      <OptIcon
                        className={cx(
                          "size-3.5 shrink-0",
                          isSelected ? "text-accent" : "text-text3",
                        )}
                        aria-hidden="true"
                      />
                    )}
                    <span className="truncate">{opt.label}</span>
                  </div>

                  <div className="flex items-center gap-1.5 shrink-0 ml-2">
                    {opt.badge !== undefined && (
                      <span
                        className={cx(
                          "text-[10px] font-mono px-1.5 py-0.5 rounded",
                          isSelected ? "bg-accent/20 text-accent font-bold" : "bg-bg3 text-text3",
                        )}
                      >
                        {opt.badge}
                      </span>
                    )}
                    {isSelected && <RiCheckLine className="size-3.5 text-accent" aria-hidden="true" />}
                  </div>
                </button>
              );
            })}
          </div>
        </div>
      )}
    </div>
  );
}
