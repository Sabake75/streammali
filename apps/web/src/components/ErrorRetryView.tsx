/**
 * Équivalent web de apps/mobile/lib/widgets/error_retry_view.dart — état
 * "réessayer" générique pour un chargement de données raté, à la place
 * d'un message d'erreur brut (`TypeError: Failed to fetch`, illisible pour
 * le public visé).
 */
export function ErrorRetryView({ onRetry }: { onRetry: () => void }) {
  return (
    <div className="mt-6 flex flex-col items-center gap-3 rounded-xl border border-dashed border-neutral-300 py-14 text-center dark:border-neutral-700">
      <span className="flex h-12 w-12 items-center justify-center rounded-full bg-neutral-100 text-neutral-400 dark:bg-neutral-900 dark:text-neutral-600">
        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
          <path d="M12 2a9.985 9.985 0 0 0-6.72 2.6M2 12a9.985 9.985 0 0 0 2.6 6.72M22 12a9.985 9.985 0 0 0-2.6-6.72M12 22a9.985 9.985 0 0 0 6.72-2.6" />
          <path d="M1 1l22 22" />
        </svg>
      </span>
      <p className="text-neutral-500 dark:text-neutral-400">
        Impossible de charger le contenu. Vérifie ta connexion et réessaie.
      </p>
      <button
        type="button"
        onClick={onRetry}
        className="rounded-full border border-neutral-300 px-4 py-1.5 text-sm font-medium hover:bg-neutral-100 dark:border-neutral-700 dark:hover:bg-neutral-900"
      >
        Réessayer
      </button>
    </div>
  );
}
