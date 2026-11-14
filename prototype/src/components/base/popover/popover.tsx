"use client";

import type { ComponentProps } from "react";
import {
  Popover as AriaPopover,
  Dialog as AriaDialog,
  DialogTrigger as AriaDialogTrigger,
} from "react-aria-components";
import type {
  PopoverProps as AriaPopoverProps,
  DialogProps as AriaDialogProps,
} from "react-aria-components";
import { cx } from "@/utils/cx";
import { useDirection } from "@/components/foundations/direction/direction";

/**
 * Board UI Popover component, styled to match the BoardUI card and overlay family:
 *   surface   bg-background-primary-default / bg-bg2, 1px border-border,
 *             rounded-2xl, shadow-dropdown / shadow-xl, text-text-primary.
 *   motion    scale-95 + opacity-0 on enter/exit with ease-out.
 *
 * Powered by react-aria-components Popover + Dialog:
 *   - Auto-positioning next to the trigger element (via DialogTrigger or triggerRef).
 *   - Collision detection & automatic viewport flipping.
 *   - Focus management & Escape key dismissal.
 *   - Non-modal overlay support (isNonModal) for interactive background.
 */

export interface PopoverProps extends AriaPopoverProps {
  /** Custom max width class. Defaults to `w-[310px] max-w-[calc(100vw-24px)]`. */
  maxWidthClassName?: string;
}

export function Popover({
  children,
  className,
  offset = 8,
  maxWidthClassName = "w-[310px] max-w-[calc(100vw-24px)]",
  isNonModal = true,
  ...props
}: PopoverProps) {
  const direction = useDirection();

  return (
    <AriaPopover
      dir={direction}
      offset={offset}
      isNonModal={isNonModal}
      {...props}
      className={(state) =>
        cx(
          "z-50 select-text overflow-hidden rounded-2xl border border-border bg-bg2 text-text shadow-xl ring-1 ring-border/80 outline-none",
          maxWidthClassName,
          // Entering & exiting animations matching Board UI design tokens
          "transition duration-150 ease-out",
          "data-[entering]:scale-95 data-[entering]:opacity-0",
          "data-[exiting]:scale-95 data-[exiting]:opacity-0",
          typeof className === "function" ? className(state) : className,
        )
      }
    >
      {children}
    </AriaPopover>
  );
}

export interface DialogProps extends AriaDialogProps {}

export function Dialog({ children, className, ...props }: DialogProps) {
  return (
    <AriaDialog
      {...props}
      className={cx("outline-none flex flex-col focus:outline-none", className)}
    >
      {children}
    </AriaDialog>
  );
}

export const DialogTrigger = AriaDialogTrigger;
export type DialogTriggerProps = ComponentProps<typeof AriaDialogTrigger>;
