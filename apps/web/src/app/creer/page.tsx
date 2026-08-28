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

      <div className="mt-6 grid grid-cols-2 gap-3 border-b border-neutral-200 pb-6 sm:grid-cols-4 dark:border-neutral-800">
        <ActionTile href="/creer/statistiques" icon={<StatsIcon />} label="Statistiques" />
        <ActionTile href="/creer/solde" icon={<WalletIcon />} label="Solde & retraits" />
        <ActionTile href="/creer/messagerie" icon={<ChatIcon />} label="Messagerie" />
        <ActionTile href="/creer/nouvelle-video" icon={<PlusIcon />} label="Nouvelle vidéo" highlight />
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

function ActionTile({
  href,
  icon,
  label,
  highlight,
}: {
  href: string;
  icon: React.ReactNode;
  label: string;
  highlight?: boolean;
}) {
  return (
    <Link
      href={href}
      className={`flex flex-col items-center gap-2 rounded-xl border p-4 text-center text-sm font-semibold shadow-sm transition active:scale-[0.97] ${
        highlight
          ? "border-orange-600 bg-orange-600 text-white hover:bg-orange-700 active:bg-orange-800"
          : "border-neutral-200 bg-white text-neutral-700 hover:border-orange-300 hover:text-orange-600 active:bg-orange-50 dark:border-neutral-800 dark:bg-neutral-900 dark:text-neutral-300 dark:hover:border-orange-800 dark:hover:text-orange-400 dark:active:bg-neutral-800"
      }`}
    >
      {icon}
      {label}
    </Link>
  );
}

function StatsIcon() {
  return (
    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
      <path d="M4 20V10M12 20V4M20 20v-6" />
    </svg>
  );
}

function WalletIcon() {
  return (
    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
      <rect x="2" y="6" width="20" height="13" rx="2" />
      <path d="M2 10h20" />
      <circle cx="16.5" cy="14.5" r="1" fill="currentColor" stroke="none" />
    </svg>
  );
}

function ChatIcon() {
  return (
    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
      <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5Z" />
    </svg>
  );
}

function PlusIcon() {
  return (
    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
      <path d="M12 5v14M5 12h14" />
    </svg>
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
