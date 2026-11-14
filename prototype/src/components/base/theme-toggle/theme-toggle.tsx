"use client";

import { useRef, type ButtonHTMLAttributes, type Ref } from "react";
import { RiSunLine, RiMoonLine } from "@remixicon/react";
import { cx, sortCx } from "@/utils/cx";
import { useTheme, toggleThemeWithTransition } from "@/utils/theme";

export type ThemeToggleSize = "sm" | "md" | "lg";
export type ThemeToggleVariant = "ghost" | "secondary" | "pill";

export interface ThemeToggleProps
  extends Omit<ButtonHTMLAttributes<HTMLButtonElement>, "children" | "onChange"> {
  size?: ThemeToggleSize;
  variant?: ThemeToggleVariant;
  ref?: Ref<HTMLButtonElement>;
}

const styles = sortCx({
  base: "relative inline-flex items-center justify-center cursor-pointer select-none transition-all duration-200 active:scale-92 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent",
  size: {
    sm: "size-7 rounded-full p-1",
    md: "size-8 rounded-full p-1.5",
    lg: "size-10 rounded-full p-2",
  },
  iconSize: {
    sm: "size-3.5",
    md: "size-4",
    lg: "size-5",
  },
  variant: {
    ghost: "text-text2 hover:text-text hover:bg-bg2/90",
    secondary: "bg-bg3/80 hover:bg-bg2 text-text2 hover:text-text border border-border2/60 shadow-xs",
    pill: "bg-bg2/90 hover:bg-bg3 text-text2 hover:text-text border border-border shadow-xs",
  },
});

export function ThemeToggle({
  size = "md",
  variant = "ghost",
  className,
  onClick,
  ref,
  ...props
}: ThemeToggleProps) {
  const theme = useTheme();
  const isDark = theme === "dark";
  const buttonRef = useRef<HTMLButtonElement | null>(null);

  const handleClick = (e: React.MouseEvent<HTMLButtonElement>) => {
    onClick?.(e);
    if (e.defaultPrevented) return;
    void toggleThemeWithTransition({
      event: e,
      targetElement: buttonRef.current,
    });
  };

  const iconClass = styles.iconSize[size];

  return (
    <button
      ref={(node) => {
        buttonRef.current = node;
        if (typeof ref === "function") {
          ref(node);
        } else if (ref && "current" in ref) {
          (ref as React.MutableRefObject<HTMLButtonElement | null>).current = node;
        }
      }}
      type="button"
      onClick={handleClick}
      title={isDark ? "Switch to Light Theme" : "Switch to Dark Theme"}
      aria-label={isDark ? "Switch to Light Theme" : "Switch to Dark Theme"}
      className={cx(styles.base, styles.size[size], styles.variant[variant], className)}
      {...props}
    >
      <span className="relative flex items-center justify-center size-full overflow-hidden">
        {/* Sun Icon (displayed in dark mode to invite switching to light mode) */}
        <RiSunLine
          className={cx(
            iconClass,
            "text-amber-400 drop-shadow-[0_0_8px_rgba(251,191,36,0.5)] transition-all duration-300 ease-out transform",
            isDark
              ? "rotate-0 scale-100 opacity-100"
              : "rotate-90 scale-0 opacity-0 pointer-events-none"
          )}
          aria-hidden="true"
        />
        {/* Moon Icon (displayed in light mode to invite switching to dark mode) */}
        <RiMoonLine
          className={cx(
            iconClass,
            "text-accent drop-shadow-[0_0_8px_rgba(77,111,255,0.4)] absolute inset-0 m-auto transition-all duration-300 ease-out transform",
            isDark
              ? "-rotate-90 scale-0 opacity-0 pointer-events-none"
              : "rotate-0 scale-100 opacity-100"
          )}
          aria-hidden="true"
        />
      </span>
    </button>
  );
}
