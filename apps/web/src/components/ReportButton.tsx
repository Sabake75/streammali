"use client";

import { useState } from "react";
import { reportVideo } from "@/lib/api-client";
import { useAuthToken } from "@/lib/use-auth";

export function ReportButton({ videoId }: { videoId: number }) {
  const token = useAuthToken();
  const [open, setOpen] = useState(false);
  const [reason, setReason] = useState("");
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [confirmation, setConfirmation] = useState<string | null>(null);

  if (!token) {
    return (
      <a
        href={`/connexion?next=${encodeURIComponent(`/videos/${videoId}`)}`}
        className="text-sm text-neutral-500 underline hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200"
      >
        Se connecter pour signaler ce contenu
      </a>
    );
  }

  if (confirmation) {
    return <p className="text-sm text-neutral-500 dark:text-neutral-400">{confirmation}</p>;
  }

  if (!open) {
    return (
      <button
        type="button"
        onClick={() => setOpen(true)}
        className="text-sm text-neutral-500 underline hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200"
      >
        Signaler ce contenu
      </button>
    );
  }

  async function handleSubmit(event: React.FormEvent) {
    event.preventDefault();
    setSubmitting(true);
    setError(null);

    try {
      const result = await reportVideo(videoId, reason);
      setConfirmation(result.message);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Une erreur est survenue.");
      setSubmitting(false);
    }
  }

  return (
    <form onSubmit={handleSubmit} className="flex flex-col gap-2">
      <label htmlFor="report-reason" className="text-sm text-neutral-600 dark:text-neutral-400">
        Pourquoi signaler cette vidéo ?
      </label>
      <textarea
        id="report-reason"
        required
        rows={2}
        value={reason}
        onChange={(event) => setReason(event.target.value)}
        className="rounded border border-neutral-300 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-900"
      />
      {error && <p className="text-sm text-red-600 dark:text-red-400">{error}</p>}
      <div className="flex gap-2">
        <button
          type="submit"
          disabled={submitting}
          className="w-fit rounded bg-neutral-900 px-4 py-2 text-sm font-medium text-white hover:bg-neutral-700 disabled:opacity-60 dark:bg-neutral-50 dark:text-neutral-900 dark:hover:bg-neutral-300"
        >
          {submitting ? "Envoi…" : "Envoyer le signalement"}
        </button>
        <button
          type="button"
          onClick={() => setOpen(false)}
          className="w-fit rounded border border-neutral-300 px-4 py-2 text-sm font-medium hover:bg-neutral-50 dark:border-neutral-700 dark:hover:bg-neutral-900"
        >
          Annuler
        </button>
      </div>
    </form>
  );
}
