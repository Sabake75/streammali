"use client";

import { useEffect, useState } from "react";
import { VideoCard } from "@/components/VideoCard";
import { fetchRecommendedVideos } from "@/lib/api-client";
import { useAuthToken } from "@/lib/use-auth";
import type { VideoSummary } from "@/lib/types";

/**
 * Client-only: authentication is a Bearer token in localStorage, not a
 * cookie, so the server component rendering the homepage has no way to
 * know whether the visitor is signed in — this section fetches (and
 * decides whether to render at all) purely on the client.
 */
export function RecommendedVideos() {
  const token = useAuthToken();
  const [videos, setVideos] = useState<VideoSummary[] | null>(null);

  useEffect(() => {
    if (!token) return;
    fetchRecommendedVideos()
      .then(setVideos)
      .catch(() => undefined);
  }, [token]);

  if (!token || !videos || videos.length === 0) {
    return null;
  }

  return (
    <section className="mt-14">
      <h2 className="flex items-center gap-2 text-xl font-semibold text-neutral-900 dark:text-neutral-50">
        <span className="h-5 w-1.5 rounded-full bg-emerald-600" />
        Recommandé pour vous
      </h2>
      <div className="mt-4 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
        {videos.map((video) => (
          <VideoCard key={video.id} video={video} />
        ))}
      </div>
    </section>
  );
}
