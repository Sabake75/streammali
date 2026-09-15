export function formatDuration(seconds: number | null): string {
  if (!seconds) return "Durée inconnue";

  const hours = Math.floor(seconds / 3600);
  const minutes = Math.round((seconds % 3600) / 60);

  if (hours === 0 && minutes === 0) return `${seconds} s`;
  if (hours === 0) return `${minutes} min`;
  return `${hours} h ${minutes.toString().padStart(2, "0")}`;
}

export function formatPrice(price: number): string {
  return `${price} FCFA`;
}

export function formatDate(isoDate: string): string {
  return new Intl.DateTimeFormat("fr-FR", { dateStyle: "long" }).format(new Date(isoDate));
}

/**
 * Shared badge coloring for the small set of status values shown across the
 * creator area (payment status: pending/succeeded/failed; payout status:
 * pending/paid/rejected) — "pending" means the same thing in both, the rest
 * don't overlap, so one map covers both without a naming collision.
 */
const STATUS_BADGE_TONE: Record<string, string> = {
  succeeded: "bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300",
  paid: "bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300",
  pending: "bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300",
  failed: "bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300",
  rejected: "bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300",
};

export function statusBadgeClass(value: string): string {
  return (
    STATUS_BADGE_TONE[value] ?? "bg-neutral-100 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300"
  );
}

/**
 * Categories are moderator-managed (dynamic, not a fixed enum — see
 * apps/api/app/Domain/Video/README.md), so colors can't be hardcoded per
 * known value. Hashing the category's own value into a fixed palette keeps
 * the same category visually consistent everywhere without needing to know
 * the category list in advance.
 */
const CATEGORY_PALETTE = [
  {
    badge: "bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-300",
    tint: "from-sky-200 to-sky-50 dark:from-sky-950 dark:to-neutral-900",
  },
  {
    badge: "bg-violet-100 text-violet-800 dark:bg-violet-900/40 dark:text-violet-300",
    tint: "from-violet-200 to-violet-50 dark:from-violet-950 dark:to-neutral-900",
  },
  {
    badge: "bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300",
    tint: "from-indigo-200 to-indigo-50 dark:from-indigo-950 dark:to-neutral-900",
  },
  {
    badge: "bg-cyan-100 text-cyan-800 dark:bg-cyan-900/40 dark:text-cyan-300",
    tint: "from-cyan-200 to-cyan-50 dark:from-cyan-950 dark:to-neutral-900",
  },
  {
    badge: "bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300",
    tint: "from-blue-200 to-blue-50 dark:from-blue-950 dark:to-neutral-900",
  },
  {
    badge: "bg-teal-100 text-teal-800 dark:bg-teal-900/40 dark:text-teal-300",
    tint: "from-teal-200 to-teal-50 dark:from-teal-950 dark:to-neutral-900",
  },
] as const;

export function categoryStyle(value: string): (typeof CATEGORY_PALETTE)[number] {
  let hash = 0;
  for (let i = 0; i < value.length; i += 1) hash = (hash * 31 + value.charCodeAt(i)) >>> 0;
  return CATEGORY_PALETTE[hash % CATEGORY_PALETTE.length];
}
