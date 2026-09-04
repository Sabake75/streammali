"use client";

import Link from "next/link";
import { useCallback, useEffect, useState } from "react";
import { ErrorRetryView } from "@/components/ErrorRetryView";
import { VideoCard } from "@/components/VideoCard";
import { favoriteVideo, fetchMyFavorites } from "@/lib/api-client";
import type { VideoSummary } from "@/lib/types";

export function FavoritesPageClient() {
  const [videos, setVideos] = useState<VideoSummary[] | null>(null);
  const [loadError, setLoadError] = useState(false);

  const reload = useCallback(() => {
    fetchMyFavorites()
      .then((response) => {
        setVideos(response.data);
        setLoadError(false);
      })
      .catch(() => setLoadError(true));
  }, []);

  useEffect(() => {
    reload();
  }, [reload]);

  async function removeFavorite(videoId: number) {
    // Optimistic — this list only ever holds favorited videos, so toggling
    // one here always means "remove it".
    setVideos((current) => current?.filter((video) => video.id !== videoId) ?? current);
    await favoriteVideo(videoId).catch(() => reload());
  }

  return (
    <main className="mx-auto w-full max-w-6xl flex-1 px-4 py-8 sm:px-6 lg:px-8">
      <h1 className="flex items-center gap-2 text-3xl font-bold text-neutral-900 dark:text-neutral-50">
        <span className="h-7 w-2 rounded-full bg-orange-600" />
        Mes favoris
      </h1>
      <p className="mt-1 ml-4 text-neutral-500 dark:text-neutral-400">
        Les vidéos que tu as mises de côté pour plus tard.
      </p>

      {loadError && <ErrorRetryView onRetry={reload} />}
      {videos === null && !loadError && <p className="mt-6 text-neutral-500">Chargement…</p>}

      {videos?.length === 0 && (
        <div className="mt-6 flex flex-col items-center gap-3 rounded-xl border border-dashed border-neutral-300 py-14 text-center dark:border-neutral-700">
          <span className="flex h-12 w-12 items-center justify-center rounded-full bg-neutral-100 text-neutral-400 dark:bg-neutral-900 dark:text-neutral-600">
            <HeartIcon />
          </span>
          <p className="text-neutral-500 dark:text-neutral-400">Aucun favori pour l&apos;instant.</p>
          <Link href="/" className="text-sm font-medium text-orange-600 hover:underline dark:text-orange-400">
            Parcourir le catalogue
          </Link>
        </div>
      )}

      {videos && videos.length > 0 && (
        <div className="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
          {videos.map((video) => (
            <div key={video.id} className="relative">
              <VideoCard video={video} />
              <button
                type="button"
                onClick={() => removeFavorite(video.id)}
                title="Retirer des favoris"
                aria-label="Retirer des favoris"
                className="absolute top-2 left-2 flex h-8 w-8 items-center justify-center rounded-full bg-white/90 text-red-500 shadow-sm backdrop-blur transition hover:bg-white dark:bg-black/60 dark:hover:bg-black/80"
              >
                ♥
              </button>
            </div>
          ))}
        </div>
      )}
    </main>
  );
}

function HeartIcon() {
  return (
    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
      <path d="M12 20.5s-7.5-4.6-9.5-9C1 8 2.5 4.5 6 4c2-.3 3.7.6 5 2.2C12.3 4.6 14 3.7 16 4c3.5.5 5 4 3.5 7.5-2 4.4-9.5 9-9.5 9Z" />
    </svg>
  );
}
