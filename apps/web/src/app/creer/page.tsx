"use client";

import Link from "next/link";
import { useCallback, useEffect, useState } from "react";
import { BalanceAndPayouts } from "@/components/creator/BalanceAndPayouts";
import { Messaging } from "@/components/creator/Messaging";
import { NewVideoForm } from "@/components/creator/NewVideoForm";
import { VideoUploadWidget } from "@/components/creator/VideoUploadWidget";
import { fetchMyVideos } from "@/lib/api-client";
import { useAuthUser } from "@/lib/use-auth";
import { formatDuration, formatPrice } from "@/lib/format";
import type { CreatorVideo } from "@/lib/types";

export default function CreatorPage() {
  const user = useAuthUser();
  const [videos, setVideos] = useState<CreatorVideo[] | null>(null);
  const [loadError, setLoadError] = useState<string | null>(null);

  const reload = useCallback(() => {
    fetchMyVideos()
      .then((response) => setVideos(response.data))
      .catch((err) => setLoadError(err instanceof Error ? err.message : "Une erreur est survenue."));
  }, []);

  useEffect(() => {
    if (user?.role === "creator") reload();
  }, [user, reload]);

  if (!user) {
    return (
      <CenteredMessage>
        <Link href="/connexion?next=/creer" className="underline">
          Connecte-toi
        </Link>{" "}
        ou{" "}
        <Link href="/inscription-createur" className="underline">
          crée un compte créateur
        </Link>{" "}
        pour accéder à cet espace.
      </CenteredMessage>
    );
  }

  if (user.role !== "creator") {
    return (
      <CenteredMessage>
        Cet espace est réservé aux comptes créateur.{" "}
        <Link href="/inscription-createur" className="underline">
          En créer un
        </Link>{" "}
        (pièce d&apos;identité requise).
      </CenteredMessage>
    );
  }

  return (
    <main className="mx-auto w-full max-w-3xl flex-1 px-4 py-8 sm:px-6 lg:px-8">
      <h1 className="text-3xl font-bold text-neutral-900 dark:text-neutral-50">Espace créateur</h1>

      <div className="mt-6">
        <BalanceAndPayouts />
      </div>

      <div className="mt-6">
        <Messaging />
      </div>

      <div className="mt-6">
        <NewVideoForm onCreated={reload} />
      </div>

      <h2 className="mt-10 text-xl font-semibold text-neutral-900 dark:text-neutral-50">Mes vidéos</h2>

      {loadError && <p className="mt-4 text-sm text-red-600 dark:text-red-400">{loadError}</p>}
      {videos === null && !loadError && <p className="mt-4 text-neutral-500">Chargement…</p>}
      {videos?.length === 0 && <p className="mt-4 text-neutral-500">Aucune vidéo pour l&apos;instant.</p>}

      <div className="mt-4 flex flex-col gap-4">
        {videos?.map((video) => (
          <div
            key={video.id}
            className="flex flex-col gap-3 rounded-lg border border-neutral-200 p-4 dark:border-neutral-800"
          >
            <div className="flex flex-wrap items-center justify-between gap-2">
              <div>
                <p className="font-semibold text-neutral-900 dark:text-neutral-50">{video.title}</p>
                <p className="text-sm text-neutral-500 dark:text-neutral-400">
                  {video.category.label} · {formatDuration(video.duration_seconds)} · {formatPrice(video.price)}
                </p>
              </div>
              <div className="flex gap-2">
                <StatusBadge label={video.status.label} tone={video.status.value} />
              </div>
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
    </main>
  );
}

function StatusBadge({ label, tone }: { label: string; tone: "pending" | "approved" | "rejected" }) {
  const toneClasses = {
    pending: "bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300",
    approved: "bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300",
    rejected: "bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300",
  }[tone];

  return <span className={`w-fit rounded px-2 py-0.5 text-xs font-medium ${toneClasses}`}>{label}</span>;
}

function CenteredMessage({ children }: { children: React.ReactNode }) {
  return (
    <main className="mx-auto flex w-full max-w-md flex-1 flex-col items-center justify-center px-4 py-16 text-center text-neutral-600 dark:text-neutral-400">
      <p>{children}</p>
    </main>
  );
}
