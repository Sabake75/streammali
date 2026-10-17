"use client";

import Link from "next/link";
import { useAuthUser } from "@/lib/use-auth";

/**
 * Client-only, like RecommendedVideos: auth is a token in localStorage, so
 * the server-rendered homepage can't know the visitor's role up front.
 * Viewers only — creators and moderators already have their own space.
 */
export function BecomeCreatorBanner() {
  const user = useAuthUser();

  if (!user || user.role !== "viewer") return null;

  return (
    <div className="mt-6 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-orange-200 bg-orange-50 px-4 py-3 text-sm dark:border-orange-900 dark:bg-orange-950/40">
      <p className="text-neutral-700 dark:text-neutral-300">
        Tu fais aussi des vidéos ?{" "}
        <span className="font-semibold text-neutral-900 dark:text-neutral-50">Sois payé à chaque vue.</span>
      </p>
      <Link href="/inscription-createur" className="btn-secondary shrink-0">
        Devenir créateur
      </Link>
    </div>
  );
}
