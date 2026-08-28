"use client";

import { useRef } from "react";

/**
 * Replaces the browser's native file input chrome (a plain "Choisir un
 * fichier" pill + gray "Aucun fichier choisi" text) with a styled dashed
 * drop-zone-style button, consistent everywhere a file is picked
 * (creator video upload, identity document at signup).
 */
export function FilePicker({
  id,
  label,
  accept,
  file,
  onChange,
  placeholder = "Choisir un fichier",
}: {
  id: string;
  label: string;
  accept?: string;
  file: File | null;
  onChange: (file: File | null) => void;
  placeholder?: string;
}) {
  const inputRef = useRef<HTMLInputElement>(null);

  return (
    <div className="flex flex-col gap-1 text-sm">
      <label htmlFor={id} className="text-neutral-600 dark:text-neutral-400">
        {label}
      </label>
      <button
        type="button"
        onClick={() => inputRef.current?.click()}
        className={`flex w-full items-center gap-3 rounded-xl border border-dashed px-4 py-3 text-left transition ${
          file
            ? "border-orange-300 bg-orange-50 dark:border-orange-800 dark:bg-orange-950/20"
            : "border-neutral-300 hover:border-orange-400 hover:bg-orange-50/50 dark:border-neutral-700 dark:hover:border-orange-800 dark:hover:bg-orange-950/20"
        }`}
      >
        <UploadIcon />
        <span className="min-w-0 flex-1 truncate text-neutral-700 dark:text-neutral-300">
          {file ? file.name : placeholder}
        </span>
        {file && <span className="shrink-0 text-xs font-semibold text-orange-600 dark:text-orange-400">Changer</span>}
      </button>
      {/* Native validation skips inputs that aren't rendered (display:none),
          so `required` here would be a no-op anyway — callers already
          check `file` themselves before submitting. */}
      <input
        id={id}
        ref={inputRef}
        type="file"
        accept={accept}
        onChange={(event) => onChange(event.target.files?.[0] ?? null)}
        className="hidden"
      />
    </div>
  );
}

function UploadIcon() {
  return (
    <svg
      viewBox="0 0 24 24"
      width="20"
      height="20"
      fill="none"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
      aria-hidden
      className="shrink-0 text-orange-600 dark:text-orange-400"
    >
      <path d="M12 16V4M7 9l5-5 5 5" />
      <path d="M4 16v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3" />
    </svg>
  );
}
