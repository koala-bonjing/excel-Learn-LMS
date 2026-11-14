/**
 * Board UI / ExceLearn Theme Management & Transition System.
 *
 * Implements the authentic Board UI click-origin theme reveal powered by the
 * native View Transitions API (`document.startViewTransition`) with an expanding
 * blurred circular mask (SVG feGaussianBlur).
 *
 * Key features:
 * - Soft organic feathered boundary (feGaussianBlur stdDeviation 2)
 * - Exact Board UI signature motion: 820ms duration, cubic-bezier(0.16, 1, 0.3, 1)
 * - React 19 synchronous reconciliation via `flushSync` preventing snapshot desync
 * - Tear-free reactive state using React's `useSyncExternalStore`
 * - Unified storage under 'excelearn-theme' with custom event dispatch
 * - Accessible keyboard fallback (origin at element center)
 * - Reduced motion (`prefers-reduced-motion`) and legacy browser fallback
 */

import { useSyncExternalStore } from 'react';
import { flushSync } from 'react-dom';

export type ThemeMode = 'light' | 'dark';

export const THEME_STORAGE_KEY = 'excelearn-theme';
export const THEME_CHANGE_EVENT = 'excelearn:theme-change';

export const THEME_TRANSITION_DURATION = 820;
export const THEME_TRANSITION_EASING = 'cubic-bezier(0.16, 1, 0.3, 1)';
export const THEME_TRANSITION_STYLE_ID = 'boardui-theme-transition-style';

export interface ThemeTransitionOrigin {
  x: number;
  y: number;
}

export interface ThemeToggleOptions {
  event?: MouseEvent | React.MouseEvent | { clientX: number; clientY: number } | null;
  targetElement?: HTMLElement | null;
  forceTheme?: ThemeMode;
  duration?: number;
  onThemeChanged?: (isDark: boolean) => void;
}

type ViewTransition = {
  ready: Promise<void>;
  finished: Promise<void>;
};

type ViewTransitionDocument = Document & {
  startViewTransition?: (update: () => void) => ViewTransition;
};

let themeTransitionRunning = false;

function createBlurCircleMask(): string {
  const svg = [
    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">',
    '<defs><filter id="blur" x="-50%" y="-50%" width="200%" height="200%">',
    '<feGaussianBlur stdDeviation="2" /></filter></defs>',
    '<circle cx="50" cy="50" r="42" fill="white" filter="url(#blur)" />',
    '</svg>',
  ].join('');

  return `url("data:image/svg+xml,${encodeURIComponent(svg)}")`;
}

function installThemeTransitionStyle(
  origin: ThemeTransitionOrigin,
  radius: number,
  duration: number
): HTMLStyleElement {
  document.getElementById(THEME_TRANSITION_STYLE_ID)?.remove();

  const mask = createBlurCircleMask();
  const finalMaskSize = radius * 2.5;
  const finalMaskX = origin.x - finalMaskSize / 2;
  const finalMaskY = origin.y - finalMaskSize / 2;
  const style = document.createElement('style');
  style.id = THEME_TRANSITION_STYLE_ID;
  style.textContent = `
    ::view-transition-new(root) {
      -webkit-mask-image: ${mask};
      mask-image: ${mask};
      -webkit-mask-repeat: no-repeat;
      mask-repeat: no-repeat;
      animation: boardui-theme-mask-reveal ${duration}ms ${THEME_TRANSITION_EASING} both;
      transform-origin: ${origin.x}px ${origin.y}px;
      will-change: -webkit-mask-size, -webkit-mask-position, mask-size, mask-position;
    }

    @keyframes boardui-theme-mask-reveal {
      from {
        -webkit-mask-position: ${origin.x}px ${origin.y}px;
        mask-position: ${origin.x}px ${origin.y}px;
        -webkit-mask-size: 0px 0px;
        mask-size: 0px 0px;
      }
      to {
        -webkit-mask-position: ${finalMaskX}px ${finalMaskY}px;
        mask-position: ${finalMaskX}px ${finalMaskY}px;
        -webkit-mask-size: ${finalMaskSize}px ${finalMaskSize}px;
        mask-size: ${finalMaskSize}px ${finalMaskSize}px;
      }
    }
  `;
  document.head.appendChild(style);
  return style;
}

export function currentTheme(): ThemeMode {
  if (typeof document === 'undefined') return 'dark';
  return document.documentElement.classList.contains('dark') ? 'dark' : 'light';
}

export function isDarkMode(): boolean {
  return currentTheme() === 'dark';
}

export function applyTheme(theme: ThemeMode, { persist = true }: { persist?: boolean } = {}): void {
  if (typeof document === 'undefined') return;
  const isDark = theme === 'dark';
  document.documentElement.classList.toggle('dark', isDark);
  if (persist) {
    try {
      localStorage.setItem(THEME_STORAGE_KEY, theme);
    } catch {
      // storage unavailable
    }
  }
  window.dispatchEvent(new CustomEvent(THEME_CHANGE_EVENT, { detail: theme }));
}

function resolveCoordinates(
  event?: ThemeToggleOptions['event'],
  targetElement?: HTMLElement | null
): ThemeTransitionOrigin {
  let x = window.innerWidth / 2;
  let y = window.innerHeight / 2;

  if (
    event &&
    'clientX' in event &&
    typeof event.clientX === 'number' &&
    (event.clientX !== 0 || event.clientY !== 0)
  ) {
    x = event.clientX;
    y = event.clientY;
  } else if (targetElement) {
    const rect = targetElement.getBoundingClientRect();
    x = rect.left + rect.width / 2;
    y = rect.top + rect.height / 2;
  } else if (
    event &&
    'currentTarget' in event &&
    event.currentTarget instanceof HTMLElement
  ) {
    const rect = event.currentTarget.getBoundingClientRect();
    x = rect.left + rect.width / 2;
    y = rect.top + rect.height / 2;
  }

  return {
    x: Math.min(Math.max(x, 0), window.innerWidth),
    y: Math.min(Math.max(y, 0), window.innerHeight),
  };
}

export async function applyThemeWithTransition(
  theme: ThemeMode,
  options: {
    origin?: ThemeTransitionOrigin | null;
    element?: HTMLElement | null;
    duration?: number;
    onThemeChanged?: (isDark: boolean) => void;
  } = {}
): Promise<void> {
  if (typeof document === 'undefined' || currentTheme() === theme) return;

  const doc = document as ViewTransitionDocument;
  const reduceMotion =
    typeof window !== 'undefined' &&
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  if (!doc.startViewTransition || reduceMotion) {
    applyTheme(theme);
    options.onThemeChanged?.(theme === 'dark');
    return;
  }

  if (themeTransitionRunning) return;

  const duration = options.duration ?? THEME_TRANSITION_DURATION;
  const origin = options.origin ?? resolveCoordinates(null, options.element);
  const radius = Math.hypot(
    Math.max(origin.x, window.innerWidth - origin.x),
    Math.max(origin.y, window.innerHeight - origin.y)
  );

  themeTransitionRunning = true;
  document.documentElement.classList.add('theme-transitioning');
  const transitionStyle = installThemeTransitionStyle(origin, radius, duration);

  try {
    const transition = doc.startViewTransition(() => {
      flushSync(() => {
        applyTheme(theme);
        options.onThemeChanged?.(theme === 'dark');
      });
    });

    await transition.ready;
    await transition.finished;
  } catch {
    if (currentTheme() !== theme) {
      applyTheme(theme);
      options.onThemeChanged?.(theme === 'dark');
    }
  } finally {
    transitionStyle.remove();
    document.documentElement.classList.remove('theme-transitioning');
    themeTransitionRunning = false;
  }
}

export function toggleThemeWithTransition(options: ThemeToggleOptions = {}): Promise<void> {
  const current = currentTheme();
  const willBeDark = options.forceTheme !== undefined ? options.forceTheme === 'dark' : current !== 'dark';
  const targetMode: ThemeMode = willBeDark ? 'dark' : 'light';

  if (current === targetMode) {
    return Promise.resolve();
  }

  const origin = resolveCoordinates(options.event, options.targetElement);

  return applyThemeWithTransition(targetMode, {
    origin,
    element: options.targetElement,
    duration: options.duration,
    onThemeChanged: options.onThemeChanged,
  });
}

function subscribe(callback: () => void) {
  const onThemeChange = () => callback();
  const onStorage = (e: StorageEvent) => {
    if (e.key === THEME_STORAGE_KEY) {
      applyTheme(e.newValue === 'dark' ? 'dark' : 'light', { persist: false });
      callback();
    }
  };
  window.addEventListener(THEME_CHANGE_EVENT, onThemeChange);
  window.addEventListener('storage', onStorage);
  return () => {
    window.removeEventListener(THEME_CHANGE_EVENT, onThemeChange);
    window.removeEventListener('storage', onStorage);
  };
}

export function useTheme(): ThemeMode {
  return useSyncExternalStore(subscribe, currentTheme, () => 'dark');
}

export function useIsDarkMode(): boolean {
  const theme = useTheme();
  return theme === 'dark';
}
