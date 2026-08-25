"use client";

import Link from "next/link";
import { useCallback, useEffect, useState } from "react";
import { VideoUploadWidget } from "@/components/creator/VideoUploadWidget";
import { fetchMyVideos } from "@/lib/api-client";
import { categoryStyle, formatDuration, formatPrice } from "@/lib/format";
import type { CreatorVideo } from "@/lib/types";

export default function CreatorPage() {
  const [videos, setVideos] = useState<CreatorVideo[] | null>(null);
  const [loadError, setLoadError] = useState<string | null>(null);

  const reload = useCallback(() => {
    fetchMyVideos()
      .then((response) => setVideos(response.data))
      .catch((err) => setLoadError(err instanceof Error ? err.message : "Une erreur est survenue."));
  }, []);

  useEffect(() => {
    reload();
  }, [reload]);

  return (
    <main className="mx-auto w-full max-w-6xl flex-1 px-4 py-8 sm:px-6 lg:px-8">
      <div className="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 className="flex items-center gap-2 text-3xl font-bold text-neutral-900 dark:text-neutral-50">
            <span className="h-7 w-2 rounded-full bg-orange-600" />
            Espace créateur
          </h1>
          <p className="mt-1 ml-4 text-neutral-500 dark:text-neutral-400">
            Tes vidéos, et un accès rapide au reste ci-dessous.
          </p>
        </div>
      </div>

      <div className="mt-6 flex flex-wrap gap-2 border-b border-neutral-200 pb-6 dark:border-neutral-800">
        <Link href="/creer/statistiques" className="btn-secondary">
          Statistiques
        </Link>
        <Link href="/creer/solde" className="btn-secondary">
          Solde & retraits
        </Link>
        <Link href="/creer/messagerie" className="btn-secondary">
          Messagerie
        </Link>
        <Link href="/creer/nouvelle-video" className="btn-primary">
          + Nouvelle vidéo
        </Link>
      </div>

      <section className="mt-8">
        <h2 className="flex items-center gap-2 text-xl font-semibold text-neutral-900 dark:text-neutral-50">
          <span className="h-5 w-1.5 rounded-full bg-orange-600" />
          Mes vidéos
          {videos && videos.length > 0 && (
            <span className="text-sm font-normal text-neutral-500 dark:text-neutral-400">({videos.length})</span>
          )}
        </h2>

        {loadError && <p className="mt-4 text-sm text-red-600 dark:text-red-400">{loadError}</p>}
        {videos === null && !loadError && <p className="mt-4 text-neutral-500">Chargement…</p>}
        {videos?.length === 0 && <p className="mt-4 text-neutral-500">Aucune vidéo pour l&apos;instant.</p>}

        <div className="mt-4 grid grid-cols-1 gap-6 lg:grid-cols-2">
          {videos?.map((video) => (
            <div
              key={video.id}
              className="flex flex-col gap-3 rounded-xl border border-neutral-200 p-4 shadow-sm dark:border-neutral-800"
            >
              <div className="flex flex-wrap items-start justify-between gap-2">
                <div className="flex flex-col gap-1.5">
                  <span
                    className={`w-fit rounded-full px-2.5 py-0.5 text-xs font-semibold tracking-wide ${categoryStyle(video.category.value).badge}`}
                  >
                    {video.category.label}
                  </span>
                  <p className="font-semibold text-neutral-900 dark:text-neutral-50">{video.title}</p>
                  <p className="text-sm text-neutral-500 dark:text-neutral-400">
                    {formatDuration(video.duration_seconds)} · {formatPrice(video.price)}
                  </p>
                </div>
                <StatusBadge label={video.status.label} tone={video.status.value} />
              </div>
              {video.status.value === "rejected" && video.rejection_reason && (
                <p className="text-sm text-red-600 dark:text-red-400">Motif du refus : {video.rejection_reason}</p>
              )}
              <VideoUploadWidget
                videoId={video.id}
                initialStatus={video.source_status.value}
                onStatusChange={reload}
              />
            </div>
          ))}
        </div>
      </section>
    </main>
  );
}

function StatusBadge({ label, tone }: { label: string; tone: "pending" | "approved" | "rejected" }) {
  const toneClasses = {
    pending: "bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300",
    approved: "bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300",
    rejected: "bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300",
  }[tone];

  return <span className={`w-fit rounded-full px-2.5 py-0.5 text-xs font-semibold ${toneClasses}`}>{label}</span>;
}
