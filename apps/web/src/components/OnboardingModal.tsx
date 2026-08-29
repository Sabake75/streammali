"use client";

import { useEffect, useState } from "react";

const SEEN_KEY = "streammali:onboarding_seen";

const STEPS = [
  {
    icon: <PreviewIcon />,
    title: "Regarde avant d'acheter",
    text: "Chaque vidéo a un aperçu gratuit. Tu sais ce que tu achètes avant de payer.",
  },
  {
    icon: <PriceIcon />,
    title: "Un prix, pas d'abonnement",
    text: "Tu payes une fois par vidéo, au prix affiché sur sa fiche — aucun engagement mensuel.",
  },
  {
    icon: <PhoneIcon />,
    title: "Mobile Money, accès immédiat",
    text: "Orange Money, Moov Money… dès le paiement confirmé, la vidéo est débloquée.",
  },
];

/**
 * Shown once per browser (localStorage flag) — explains the pay-per-view +
 * free-preview + Mobile Money model to a first-time visitor, since the hero
 * banner's pills convey the same facts but assume the reader already knows
 * what to do with them.
 */
export function OnboardingModal() {
  const [open, setOpen] = useState(false);

  useEffect(() => {
    try {
      if (!localStorage.getItem(SEEN_KEY)) queueMicrotask(() => setOpen(true));
    } catch {
      // Private browsing / storage disabled — just skip onboarding rather than crash.
    }
  }, []);

  function dismiss() {
    setOpen(false);
    try {
      localStorage.setItem(SEEN_KEY, "1");
    } catch {
      // Nothing to persist to — the modal will just reappear next visit, not a big deal.
    }
  }

  if (!open) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-end justify-center bg-black/50 p-0 sm:items-center sm:p-4" onClick={dismiss}>
      <div
        role="dialog"
        aria-modal="true"
        aria-label="Bienvenue sur StreamMali"
        className="w-full max-w-md rounded-t-2xl bg-white p-6 shadow-xl dark:bg-neutral-950 sm:rounded-2xl"
        onClick={(event) => event.stopPropagation()}
      >
        <h2 className="text-xl font-bold text-neutral-900 dark:text-neutral-50">Bienvenue sur StreamMali</h2>
        <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">Le cinéma malien, à ta façon.</p>

        <ul className="mt-5 flex flex-col gap-4">
          {STEPS.map((step) => (
            <li key={step.title} className="flex gap-3">
              <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-orange-50 text-orange-600 dark:bg-orange-950/40 dark:text-orange-400">
                {step.icon}
              </span>
              <div>
                <p className="font-semibold text-neutral-900 dark:text-neutral-50">{step.title}</p>
                <p className="text-sm text-neutral-500 dark:text-neutral-400">{step.text}</p>
              </div>
            </li>
          ))}
        </ul>

        <button type="button" onClick={dismiss} className="btn-primary mt-6 w-full">
          Compris, je découvre
        </button>
      </div>
    </div>
  );
}

function PreviewIcon() {
  return (
    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
      <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" />
      <circle cx="12" cy="12" r="3" />
    </svg>
  );
}

function PriceIcon() {
  return (
    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
      <circle cx="12" cy="12" r="9" />
      <path d="M9.5 15.5c.5.7 1.3 1 2.3 1 1.5 0 2.7-.9 2.7-2s-1-1.7-2.5-2-2.5-.9-2.5-2 1.2-2 2.7-2c1 0 1.8.3 2.3 1M12 7v1.5M12 15.5V17" />
    </svg>
  );
}

function PhoneIcon() {
  return (
    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
      <rect x="7" y="2" width="10" height="20" rx="2" />
      <path d="M11 18h2" />
    </svg>
  );
}
