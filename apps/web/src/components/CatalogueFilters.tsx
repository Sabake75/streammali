import { VIDEO_CATEGORIES } from "@/lib/api";

export function CatalogueFilters({
  defaultCategory,
  defaultSearch,
}: {
  defaultCategory?: string;
  defaultSearch?: string;
}) {
  return (
    <form method="get" className="flex flex-wrap items-end gap-3">
      <div className="flex flex-col gap-1">
        <label htmlFor="search" className="text-sm text-neutral-600 dark:text-neutral-400">
          Recherche
        </label>
        <input
          type="search"
          id="search"
          name="search"
          defaultValue={defaultSearch}
          placeholder="Titre d'un film, clip, sketch…"
          className="rounded border border-neutral-300 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-900"
        />
      </div>
      <div className="flex flex-col gap-1">
        <label htmlFor="category" className="text-sm text-neutral-600 dark:text-neutral-400">
          Catégorie
        </label>
        <select
          id="category"
          name="category"
          defaultValue={defaultCategory ?? ""}
          className="rounded border border-neutral-300 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-900"
        >
          <option value="">Toutes</option>
          {VIDEO_CATEGORIES.map((category) => (
            <option key={category.value} value={category.value}>
              {category.label}
            </option>
          ))}
        </select>
      </div>
      <button
        type="submit"
        className="rounded bg-neutral-900 px-4 py-2 text-sm font-medium text-white hover:bg-neutral-700 dark:bg-neutral-50 dark:text-neutral-900 dark:hover:bg-neutral-300"
      >
        Filtrer
      </button>
    </form>
  );
}
