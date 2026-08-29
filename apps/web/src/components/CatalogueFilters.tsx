import type { VideoCategory } from "@/lib/types";

export function CatalogueFilters({
  categories,
  defaultCategory,
  defaultSearch,
  defaultSort,
}: {
  categories: VideoCategory[];
  defaultCategory?: string;
  defaultSearch?: string;
  defaultSort?: string;
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
          className="input-field"
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
          className="input-field"
        >
          <option value="">Toutes</option>
          {categories.map((category) => (
            <option key={category.value} value={category.value}>
              {category.label}
            </option>
          ))}
        </select>
      </div>
      <div className="flex flex-col gap-1">
        <label htmlFor="sort" className="text-sm text-neutral-600 dark:text-neutral-400">
          Trier par
        </label>
        <select
          id="sort"
          name="sort"
          defaultValue={defaultSort ?? "recent"}
          className="input-field"
        >
          <option value="recent">Plus récent</option>
          <option value="popular">Plus populaire</option>
        </select>
      </div>
      <button
        type="submit"
        className="btn-primary"
      >
        Filtrer
      </button>
    </form>
  );
}
