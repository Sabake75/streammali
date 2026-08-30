"use client";

import { useEffect } from "react";
import Link from "next/link";

/**
 * Catches unexpected runtime errors in any page/layout below the root —
 * without this, an unhandled error fell through to Next.js's generic,
 * unbranded error screen instead of something consistent with the rest
 * of the app. Doesn't need `global-error.tsx`'s own <html>/<body> or
 * inline styles: this renders inside the root layout, so it keeps the
 * shared Tailwind classes and dark mode.
 */
export default function Error({
  error,
  retry,
}: {
  error: Error & { digest?: string };
  retry: () => void;
}) {
  useEffect(() => {
    console.error(error);
  }, [error]);

  return (
    <main className="mx-auto flex w-full max-w-md flex-1 flex-col items-center justify-center px-4 py-16 text-center">
      <span className="flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-orange-500 to-accent-600 text-2xl text-white">
        !
      </span>
      <h1 className="mt-6 text-3xl font-bold text-neutral-900 dark:text-neutral-50">Une erreur est survenue</h1>
      <p className="mt-2 text-neutral-500 dark:text-neutral-400">
        Ce n&apos;est pas grave, ce n&apos;est pas de ton fait. Réessaie, ou reviens au catalogue.
      </p>
      <div className="mt-6 flex flex-wrap items-center justify-center gap-3">
        <button type="button" onClick={() => retry()} className="btn-primary px-5 py-2.5">
          Réessayer
        </button>
        <Link href="/" className="btn-secondary px-5 py-2.5">
          Retour au catalogue
        </Link>
      </div>
    </main>
  );
}
