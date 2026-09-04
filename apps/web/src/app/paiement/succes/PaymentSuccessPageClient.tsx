"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { fetchVideoStatus } from "@/lib/api-client";
import type { VideoSummary } from "@/lib/types";

const PENDING_VIDEO_KEY = "streammali:pending_purchase_video_id";
const POLL_INTERVAL_MS = 2500;
const MAX_POLLS = 12; // ~30s — Mobile Money confirmations are usually near-instant, but PayDunya's webhook can lag.

type Status = "no-pending" | "checking" | "confirmed" | "timeout";

export function PaymentSuccessPageClient() {
  const [status, setStatus] = useState<Status>("checking");
  const [video, setVideo] = useState<VideoSummary | null>(null);

  useEffect(() => {
    const stored = sessionStorage.getItem(PENDING_VIDEO_KEY);
    if (!stored) {
      queueMicrotask(() => setStatus("no-pending"));
      return;
    }

    const videoId = Number(stored);
    let cancelled = false;
    let attempts = 0;

    async function poll() {
      attempts += 1;
      try {
        const result = await fetchVideoStatus(videoId);
        if (cancelled) return;

        if (result.purchased) {
          setVideo(result);
          setStatus("confirmed");
          sessionStorage.removeItem(PENDING_VIDEO_KEY);
          return;
        }
      } catch {
        // A single flaky request shouldn't give up on the whole poll —
        // treated the same as "not confirmed yet", retried below like any
        // other attempt, up to MAX_POLLS.
      }

      if (cancelled) return;

      if (attempts >= MAX_POLLS) {
        setStatus("timeout");
        sessionStorage.removeItem(PENDING_VIDEO_KEY);
        return;
      }

      setTimeout(poll, POLL_INTERVAL_MS);
    }

    poll();

    return () => {
      cancelled = true;
    };
  }, []);

  return (
    <main className="mx-auto flex w-full max-w-md flex-1 flex-col items-center justify-center px-4 py-16 text-center">
      {status === "checking" && (
        <>
          <Spinner />
          <h1 className="mt-4 text-xl font-bold text-neutral-900 dark:text-neutral-50">
            Confirmation du paiement…
          </h1>
          <p className="mt-2 text-neutral-500 dark:text-neutral-400">
            Ça ne prend généralement que quelques secondes.
          </p>
        </>
      )}

      {status === "confirmed" && (
        <>
          <span className="flex h-14 w-14 items-center justify-center rounded-full bg-accent-100 text-2xl text-accent-600 dark:bg-accent-900/40 dark:text-accent-400">
            ✓
          </span>
          <h1 className="mt-4 text-xl font-bold text-neutral-900 dark:text-neutral-50">Achat confirmé !</h1>
          <p className="mt-2 text-neutral-500 dark:text-neutral-400">
            {video ? <>« {video.title} » est prête à être regardée.</> : "Ta vidéo est prête à être regardée."}
          </p>
          <Link href={video ? `/videos/${video.id}` : "/bibliotheque"} className="btn-primary mt-6">
            Regarder maintenant
          </Link>
        </>
      )}

      {status === "no-pending" && (
        <>
          <h1 className="text-xl font-bold text-neutral-900 dark:text-neutral-50">Paiement reçu</h1>
          <p className="mt-2 text-neutral-500 dark:text-neutral-400">
            Retrouve tes vidéos dans ta bibliothèque.
          </p>
          <Link href="/bibliotheque" className="btn-primary mt-6">
            Mes achats
          </Link>
        </>
      )}

      {status === "timeout" && (
        <>
          <h1 className="text-xl font-bold text-neutral-900 dark:text-neutral-50">
            La confirmation prend plus de temps que prévu
          </h1>
          <p className="mt-2 text-neutral-500 dark:text-neutral-400">
            Le paiement Mobile Money peut prendre quelques minutes à être validé. Reviens vérifier dans
            « Mes achats » dans un instant — pas besoin de payer une seconde fois.
          </p>
          <Link href="/bibliotheque" className="btn-primary mt-6">
            Vérifier mes achats
          </Link>
        </>
      )}
    </main>
  );
}

function Spinner() {
  return (
    <svg
      className="h-10 w-10 animate-spin text-orange-600 dark:text-orange-400"
      viewBox="0 0 24 24"
      fill="none"
      aria-hidden
    >
      <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
      <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z" />
    </svg>
  );
}
