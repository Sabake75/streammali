"use client";

import { useState } from "react";
import { PhoneNumberField } from "@/components/PhoneNumberField";
import { purchaseVideo } from "@/lib/api-client";
import { useAuthToken, useAuthUser } from "@/lib/use-auth";

export function PurchaseButton({ videoId }: { videoId: number }) {
  const token = useAuthToken();
  const user = useAuthUser();
  const [msisdn, setMsisdn] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  if (!token) {
    return (
      <a
        href={`/connexion?next=${encodeURIComponent(`/videos/${videoId}`)}`}
        className="btn-primary px-5 py-2.5"
      >
        Se connecter pour acheter
      </a>
    );
  }

  async function handleSubmit(event: React.FormEvent) {
    event.preventDefault();
    setSubmitting(true);
    setError(null);

    try {
      const result = await purchaseVideo(videoId, msisdn ?? user?.phone ?? "");
      window.location.href = result.payment_url;
    } catch (err) {
      setError(err instanceof Error ? err.message : "Une erreur est survenue.");
      setSubmitting(false);
    }
  }

  return (
    <form onSubmit={handleSubmit} className="flex flex-col gap-2">
      <div className="flex flex-wrap items-end gap-2">
        <PhoneNumberField
          id="msisdn"
          label="Numéro Mobile Money"
          value={msisdn ?? user?.phone ?? ""}
          onChange={setMsisdn}
        />
        <button
          type="submit"
          disabled={submitting}
          className="btn-primary px-5 py-2.5"
        >
          {submitting ? "Redirection…" : "Acheter"}
        </button>
      </div>
      {error && <p className="text-sm text-red-600 dark:text-red-400">{error}</p>}
    </form>
  );
}
