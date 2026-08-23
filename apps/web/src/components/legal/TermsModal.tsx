"use client";

import { useEffect } from "react";

export function TermsModal({
  open,
  title,
  onClose,
  onAccept,
  children,
}: {
  open: boolean;
  title: string;
  onClose: () => void;
  onAccept: () => void;
  children: React.ReactNode;
}) {
  useEffect(() => {
    if (!open) return;

    function handleKeyDown(event: KeyboardEvent) {
      if (event.key === "Escape") onClose();
    }

    document.addEventListener("keydown", handleKeyDown);
    return () => document.removeEventListener("keydown", handleKeyDown);
  }, [open, onClose]);

  if (!open) return null;

  return (
    <div
      className="fixed inset-0 z-50 flex items-end justify-center bg-black/50 sm:items-center sm:p-4"
      onClick={onClose}
    >
      <div
        role="dialog"
        aria-modal="true"
        aria-label={title}
        className="flex max-h-[85vh] w-full max-w-2xl flex-col rounded-t-2xl bg-white shadow-xl dark:bg-neutral-950 sm:rounded-2xl"
        onClick={(event) => event.stopPropagation()}
      >
        <div className="flex items-center justify-between border-b border-neutral-200 px-5 py-4 dark:border-neutral-800">
          <h2 className="font-semibold text-neutral-900 dark:text-neutral-50">{title}</h2>
          <button
            type="button"
            onClick={onClose}
            aria-label="Fermer"
            className="text-xl leading-none text-neutral-400 transition hover:text-neutral-700 dark:hover:text-neutral-200"
          >
            ×
          </button>
        </div>
        <div className="flex-1 overflow-y-auto px-5 py-4 text-sm">{children}</div>
        <div className="flex justify-end gap-2 border-t border-neutral-200 px-5 py-4 dark:border-neutral-800">
          <button type="button" onClick={onClose} className="btn-secondary">
            Fermer
          </button>
          <button type="button" onClick={onAccept} className="btn-primary">
            J&apos;accepte les CGU
          </button>
        </div>
      </div>
    </div>
  );
}
