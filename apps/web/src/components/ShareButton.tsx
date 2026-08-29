"use client";

import { useState } from "react";

/**
 * Uses the native share sheet where available (Android/iOS browsers — the
 * platforms this audience actually shares from, straight into WhatsApp)
 * and falls back to copying the link on desktop browsers that don't
 * support the Web Share API. No auth check: sharing is exactly what an
 * unauthenticated visitor (or a buyer inviting a friend who has no
 * account yet) would want to do.
 */
export function ShareButton({ title }: { title: string }) {
  const [copied, setCopied] = useState(false);

  async function handleClick() {
    const url = window.location.href;

    if (navigator.share) {
      try {
        await navigator.share({ title, url });
      } catch {
        // User cancelled the share sheet — not an error, nothing to do.
      }
      return;
    }

    try {
      await navigator.clipboard.writeText(url);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    } catch {
      // Clipboard access denied — silently give up rather than show a broken button.
    }
  }

  return (
    <button type="button" onClick={handleClick} className="btn-secondary gap-1.5 px-3 py-1.5">
      <ShareIcon />
      {copied ? "Lien copié !" : "Partager"}
    </button>
  );
}

function ShareIcon() {
  return (
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
      <circle cx="18" cy="5" r="3" />
      <circle cx="6" cy="12" r="3" />
      <circle cx="18" cy="19" r="3" />
      <path d="M8.6 13.5 15.4 17.5M15.4 6.5 8.6 10.5" />
    </svg>
  );
}
