"use client";

import { useState } from "react";
import { favoriteVideo } from "@/lib/api-client";
import { useAuthToken } from "@/lib/use-auth";

export function FavoriteButton({
  videoId,
  initialFavorited,
}: {
  videoId: number;
  initialFavorited: boolean;
}) {
  const token = useAuthToken();
  const [favorited, setFavorited] = useState(initialFavorited);
  const [submitting, setSubmitting] = useState(false);

  if (!token) {
    return null;
  }

  async function handleClick() {
    setSubmitting(true);
    try {
      const result = await favoriteVideo(videoId);
      setFavorited(result.favorited);
    } catch {
      // Best-effort — the button just doesn't update on failure.
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <button
      type="button"
      onClick={handleClick}
      disabled={submitting}
      aria-pressed={favorited}
      className="flex items-center gap-1.5 rounded border border-neutral-300 px-3 py-1.5 text-sm hover:bg-neutral-50 disabled:opacity-60 dark:border-neutral-700 dark:hover:bg-neutral-900"
    >
      <span className={favorited ? "text-red-500" : "text-neutral-400"}>{favorited ? "♥" : "♡"}</span>
      {favorited ? "Dans mes favoris" : "Ajouter aux favoris"}
    </button>
  );
}
