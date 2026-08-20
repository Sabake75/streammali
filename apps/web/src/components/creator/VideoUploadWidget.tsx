"use client";

import * as tus from "tus-js-client";
import { useEffect, useRef, useState } from "react";
import { createVideoUploadUrl, fetchVideoSourceStatus } from "@/lib/api-client";
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
  const uploadRef = useRef<tus.Upload | null>(null);

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

      // Cloudflare's direct_upload already created the upload resource
      // server-side — passing `uploadUrl` (not `endpoint`) tells tus-js-client
      // to target it directly instead of trying to create a new one.
      const upload = new tus.Upload(file, {
        uploadUrl: upload_url,
        chunkSize: 50 * 1024 * 1024,
        retryDelays: [0, 1000, 3000, 5000],
        onError: (err) => setError(err.message),
        onProgress: (bytesSent, bytesTotal) => {
          setProgress(Math.round((bytesSent / bytesTotal) * 100));
        },
        onSuccess: () => {
          setStatus("processing");
          onStatusChange();
        },
      });

      uploadRef.current = upload;
      upload.start();
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
        <input type="file" accept="video/*" onChange={handleFileSelected} />
      </label>
      {progress > 0 && <p className="text-sm text-neutral-500">Envoi en cours… {progress}%</p>}
      {error && <p className="text-sm text-red-600 dark:text-red-400">{error}</p>}
    </div>
  );
}
