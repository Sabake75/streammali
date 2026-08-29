"use client";

import Link from "next/link";
import { useCallback, useEffect, useState } from "react";
import { VideoCard } from "@/components/VideoCard";
import { fetchMyPurchases } from "@/lib/api-client";
import type { VideoSummary } from "@/lib/types";

export default function LibraryPage() {
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
            <VideoCard key={video.id} video={video} />
          ))}
        </div>
      )}
    </main>
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
