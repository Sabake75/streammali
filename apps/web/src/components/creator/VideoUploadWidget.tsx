"use client";

import { useEffect, useState } from "react";
import { createVideoUploadUrl, fetchVideoSourceStatus, uploadVideoFile } from "@/lib/api-client";
import type { VideoSourceStatusValue } from "@/lib/types";

const POLL_INTERVAL_MS = 5000;

export function VideoUploadWidget({
  videoId,
  initialStatus,
  onStatusChange,
}: {
  videoId: number;
  initialStatus: VideoSourceStatusValue;
  onStatusChange: () => void;
}) {
  const [status, setStatus] = useState(initialStatus);
  const [progress, setProgress] = useState(0);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (status !== "processing") return;

    const interval = setInterval(async () => {
      try {
        const result = await fetchVideoSourceStatus(videoId);
        setStatus(result.source_status.value as VideoSourceStatusValue);
        if (result.source_status.value !== "processing") {
          onStatusChange();
        }
      } catch {
        // transient polling failure — try again next tick
      }
    }, POLL_INTERVAL_MS);

    return () => clearInterval(interval);
  }, [status, videoId, onStatusChange]);

  async function handleFileSelected(event: React.ChangeEvent<HTMLInputElement>) {
    const file = event.target.files?.[0];
    if (!file) return;

    setError(null);
    setProgress(0);

    try {
      const { upload_url } = await createVideoUploadUrl(videoId);
      await uploadVideoFile(upload_url, file, setProgress);
      setStatus("processing");
      onStatusChange();
    } catch (err) {
      setError(err instanceof Error ? err.message : "Une erreur est survenue.");
    }
  }

  if (status === "ready") {
    return <p className="text-sm text-green-700 dark:text-green-400">Fichier vidéo prêt.</p>;
  }

  if (status === "processing") {
    return (
      <p className="text-sm text-neutral-500 dark:text-neutral-400">
        {progress > 0 && progress < 100 ? `Envoi en cours… ${progress}%` : "Traitement en cours…"}
      </p>
    );
  }

  return (
    <div className="flex flex-col gap-1">
      <label className="text-sm">
        <span className="mb-1 block text-neutral-600 dark:text-neutral-400">
          {status === "failed" ? "Échec précédent — réessayer :" : "Fichier vidéo"}
        </span>
        <input
          type="file"
          accept="video/*"
          onChange={handleFileSelected}
          className="block w-full text-sm text-neutral-500 file:mr-3 file:rounded-lg file:border-0 file:bg-orange-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-orange-700 hover:file:bg-orange-100 dark:text-neutral-400 dark:file:bg-orange-950/50 dark:file:text-orange-300"
        />
      </label>
      {progress > 0 && <p className="text-sm text-neutral-500">Envoi en cours… {progress}%</p>}
      {error && <p className="text-sm text-red-600 dark:text-red-400">{error}</p>}
    </div>
  );
}
