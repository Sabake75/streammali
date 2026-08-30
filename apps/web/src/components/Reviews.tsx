"use client";

import { useCallback, useEffect, useState } from "react";
import { fetchReviews, submitReview } from "@/lib/api-client";
import type { Review } from "@/lib/types";

export function Reviews({ videoId, purchased }: { videoId: number; purchased: boolean }) {
  const [reviews, setReviews] = useState<Review[] | null>(null);
  const [rating, setRating] = useState(5);
  const [comment, setComment] = useState("");
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const reload = useCallback(() => {
    fetchReviews(videoId)
      .then((response) => setReviews(response.data))
      .catch(() => undefined);
  }, [videoId]);

  useEffect(() => {
    reload();
  }, [reload]);

  async function handleSubmit(event: React.FormEvent) {
    event.preventDefault();
    setSubmitting(true);
    setError(null);

    try {
      await submitReview(videoId, { rating, comment: comment || undefined });
      setComment("");
      reload();
    } catch (err) {
      setError(err instanceof Error ? err.message : "Une erreur est survenue.");
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div className="mt-8">
      <h2 className="flex items-center gap-2 text-lg font-semibold text-neutral-900 dark:text-neutral-50">
        <span className="h-4 w-1 rounded-full bg-orange-600" />
        Avis
      </h2>

      {purchased && (
        <form onSubmit={handleSubmit} className="mt-3 flex flex-col gap-2">
          <div role="group" aria-label="Note (1 à 5 étoiles)" className="flex items-center gap-1">
            {[1, 2, 3, 4, 5].map((value) => (
              <button
                key={value}
                type="button"
                onClick={() => setRating(value)}
                aria-label={`${value} étoile${value > 1 ? "s" : ""}`}
                aria-pressed={value <= rating}
                className={`text-2xl leading-none ${
                  value <= rating ? "text-amber-500" : "text-neutral-300 dark:text-neutral-700"
                }`}
              >
                ★
              </button>
            ))}
          </div>
          <label htmlFor="review-comment" className="sr-only">
            Commentaire
          </label>
          <textarea
            id="review-comment"
            value={comment}
            onChange={(event) => setComment(event.target.value)}
            placeholder="Un commentaire (optionnel)"
            rows={2}
            className="input-field"
          />
          {error && <p className="text-sm text-red-600 dark:text-red-400">{error}</p>}
          <button type="submit" disabled={submitting} className="btn-primary w-fit">
            {submitting ? "Envoi…" : "Publier mon avis"}
          </button>
        </form>
      )}

      <div className="mt-4 flex flex-col gap-3">
        {reviews === null && <p className="text-sm text-neutral-500">Chargement…</p>}
        {reviews?.length === 0 && (
          <p className="text-sm text-neutral-500 dark:text-neutral-400">Aucun avis pour l&apos;instant.</p>
        )}
        {reviews?.map((review) => (
          <div key={review.id} className="rounded-xl border border-neutral-200 p-3 dark:border-neutral-800">
            <div className="flex items-center justify-between gap-2">
              <span className="text-sm font-medium text-neutral-900 dark:text-neutral-50">
                {review.user.name}
              </span>
              <span className="text-amber-500">{"★".repeat(review.rating)}</span>
            </div>
            {review.comment && (
              <p className="mt-1 text-sm text-neutral-700 dark:text-neutral-300">{review.comment}</p>
            )}
          </div>
        ))}
      </div>
    </div>
  );
}
