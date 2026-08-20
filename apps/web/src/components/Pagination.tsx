import Link from "next/link";

export function Pagination({
  currentPage,
  lastPage,
  searchParams,
}: {
  currentPage: number;
  lastPage: number;
  searchParams: Record<string, string | undefined>;
}) {
  if (lastPage <= 1) return null;

  const hrefForPage = (page: number) => {
    const query = new URLSearchParams();
    if (searchParams.category) query.set("category", searchParams.category);
    if (searchParams.search) query.set("search", searchParams.search);
    if (page > 1) query.set("page", String(page));
    const qs = query.toString();
    return qs ? `/?${qs}` : "/";
  };

  return (
    <nav className="mt-8 flex items-center justify-center gap-4 text-sm">
      {currentPage > 1 ? (
        <Link href={hrefForPage(currentPage - 1)} className="hover:underline">
          ← Précédent
        </Link>
      ) : (
        <span className="text-neutral-400">← Précédent</span>
      )}
      <span className="text-neutral-500">
        Page {currentPage} / {lastPage}
      </span>
      {currentPage < lastPage ? (
        <Link href={hrefForPage(currentPage + 1)} className="hover:underline">
          Suivant →
        </Link>
      ) : (
        <span className="text-neutral-400">Suivant →</span>
      )}
    </nav>
  );
}
