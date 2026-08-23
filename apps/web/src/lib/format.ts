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
    badge: "bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300",
    tint: "from-rose-200 to-rose-50 dark:from-rose-950 dark:to-neutral-900",
  },
  {
    badge: "bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300",
    tint: "from-emerald-200 to-emerald-50 dark:from-emerald-950 dark:to-neutral-900",
  },
  {
    badge: "bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300",
    tint: "from-amber-200 to-amber-50 dark:from-amber-950 dark:to-neutral-900",
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
