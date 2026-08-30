"use client";

import Link from "next/link";
import { useEffect, useState } from "react";

const PENDING_VIDEO_KEY = "streammali:pending_purchase_video_id";

export function PaymentCancelledPageClient() {
  const [videoId, setVideoId] = useState<string | null>(null);

  useEffect(() => {
    const stored = sessionStorage.getItem(PENDING_VIDEO_KEY);
    sessionStorage.removeItem(PENDING_VIDEO_KEY);
    queueMicrotask(() => setVideoId(stored));
  }, []);

  return (
    <main className="mx-auto flex w-full max-w-md flex-1 flex-col items-center justify-center px-4 py-16 text-center">
      <span className="flex h-14 w-14 items-center justify-center rounded-full bg-neutral-100 text-2xl text-neutral-400 dark:bg-neutral-900 dark:text-neutral-600">
        ✕
      </span>
      <h1 className="mt-4 text-xl font-bold text-neutral-900 dark:text-neutral-50">Paiement annulé</h1>
      <p className="mt-2 text-neutral-500 dark:text-neutral-400">
        Aucun montant n&apos;a été débité. Tu peux réessayer quand tu veux.
      </p>
      <Link href={videoId ? `/videos/${videoId}` : "/"} className="btn-primary mt-6">
        {videoId ? "Retour à la vidéo" : "Retour au catalogue"}
      </Link>
    </main>
  );
}
