"use client";

import Link from "next/link";
import { useCallback, useEffect, useState } from "react";
import { VideoCard } from "@/components/VideoCard";
import { fetchMyPurchases } from "@/lib/api-client";
import { formatDate, formatPrice } from "@/lib/format";
import type { VideoSummary } from "@/lib/types";

export function LibraryPageClient() {
  const [videos, setVideos] = useState<VideoSummary[] | null>(null);
  const [loadError, setLoadError] = useState<string | null>(null);

  const reload = useCallback(() => {
    fetchMyPurchases()
      .then((response) => setVideos(response.data))
      .catch((err) => setLoadError(err instanceof Error ? err.message : "Une erreur est survenue."));
  }, []);

  useEffect(() => {
    reload();
  }, [reload]);

  return (
    <main className="mx-auto w-full max-w-6xl flex-1 px-4 py-8 sm:px-6 lg:px-8">
      <h1 className="flex items-center gap-2 text-3xl font-bold text-neutral-900 dark:text-neutral-50">
        <span className="h-7 w-2 rounded-full bg-orange-600" />
        Mes achats
      </h1>
      <p className="mt-1 ml-4 text-neutral-500 dark:text-neutral-400">
        Les vidéos que tu as achetées, accessibles en streaming illimité.
      </p>

      {loadError && <p className="mt-6 text-sm text-red-600 dark:text-red-400">{loadError}</p>}
      {videos === null && !loadError && <p className="mt-6 text-neutral-500">Chargement…</p>}

      {videos?.length === 0 && (
        <div className="mt-6 flex flex-col items-center gap-3 rounded-xl border border-dashed border-neutral-300 py-14 text-center dark:border-neutral-700">
          <span className="flex h-12 w-12 items-center justify-center rounded-full bg-neutral-100 text-neutral-400 dark:bg-neutral-900 dark:text-neutral-600">
            <LibraryIcon />
          </span>
          <p className="text-neutral-500 dark:text-neutral-400">Aucun achat pour l&apos;instant.</p>
          <Link href="/" className="text-sm font-medium text-orange-600 hover:underline dark:text-orange-400">
            Parcourir le catalogue
          </Link>
        </div>
      )}

      {videos && videos.length > 0 && (
        <div className="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
          {videos.map((video) => (
            <div key={video.id} className="flex flex-col gap-2">
              <VideoCard video={video} />
              {video.purchase && <PurchaseReceipt purchase={video.purchase} />}
            </div>
          ))}
        </div>
      )}
    </main>
  );
}

function PurchaseReceipt({ purchase }: { purchase: NonNullable<VideoSummary["purchase"]> }) {
  const [copied, setCopied] = useState(false);

  async function copyReference() {
    try {
      await navigator.clipboard.writeText(purchase.order_reference);
      setCopied(true);
      setTimeout(() => setCopied(false), 1500);
    } catch {
      // Clipboard access denied — the reference stays visible on screen either way.
    }
  }

  return (
    <div className="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 rounded-lg border border-neutral-200 bg-white/60 px-3 py-2 text-xs text-neutral-500 dark:border-neutral-800 dark:bg-neutral-950/60 dark:text-neutral-400">
      <span>
        Payé {formatPrice(purchase.amount)} le {formatDate(purchase.purchased_at)}
      </span>
      <button
        type="button"
        onClick={copyReference}
        title="Copier la référence"
        className="font-mono text-neutral-400 hover:text-orange-600 dark:text-neutral-500 dark:hover:text-orange-400"
      >
        {copied ? "Copié !" : `Réf. ${purchase.order_reference}`}
      </button>
    </div>
  );
}

function LibraryIcon() {
  return (
    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
      <rect x="3" y="5" width="18" height="14" rx="2" />
      <path d="M10 9.5v5l4.5-2.5-4.5-2.5Z" fill="currentColor" stroke="none" />
    </svg>
  );
}
