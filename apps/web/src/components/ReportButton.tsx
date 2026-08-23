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
        className="input-field"
      />
      {error && <p className="text-sm text-red-600 dark:text-red-400">{error}</p>}
      <div className="flex gap-2">
        <button type="submit" disabled={submitting} className="btn-primary w-fit">
          {submitting ? "Envoi…" : "Envoyer le signalement"}
        </button>
        <button type="button" onClick={() => setOpen(false)} className="btn-secondary w-fit">
          Annuler
        </button>
      </div>
    </form>
  );
}
