import Link from "next/link";
import { fetchCategories, fetchFeaturedVideos, fetchVideos } from "@/lib/api";
import { CatalogueFilters } from "@/components/CatalogueFilters";
import { OnboardingModal } from "@/components/OnboardingModal";
import { Pagination } from "@/components/Pagination";
import { RecommendedVideos } from "@/components/RecommendedVideos";
import { VideoCard } from "@/components/VideoCard";

export default async function CataloguePage(props: PageProps<"/">) {
  const searchParams = await props.searchParams;
  const category = typeof searchParams.category === "string" ? searchParams.category : undefined;
  const search = typeof searchParams.search === "string" ? searchParams.search : undefined;
  const page = typeof searchParams.page === "string" ? Number(searchParams.page) || 1 : 1;
  const creatorIdParam = typeof searchParams.creator_id === "string" ? searchParams.creator_id : undefined;
  const creatorId = creatorIdParam ? Number(creatorIdParam) || undefined : undefined;
  const sort = searchParams.sort === "popular" ? "popular" : "recent";

  const [catalogue, categories, featured] = await Promise.all([
    fetchVideos({ category, search, page, creatorId, sort }),
    fetchCategories(),
    fetchFeaturedVideos(),
  ]);
  const creatorName = creatorId ? catalogue.data[0]?.creator.name : undefined;

  return (
    <main className="mx-auto w-full max-w-6xl flex-1 px-4 py-8 sm:px-6 lg:px-8">
      <OnboardingModal />
      <section className="relative overflow-hidden rounded-2xl bg-gradient-to-br from-orange-700 via-orange-600 to-orange-500 px-6 py-12 text-white shadow-lg sm:px-10 sm:py-16">
        <div className="hero-dots pointer-events-none absolute inset-0" />
        <div className="pointer-events-none absolute -top-24 -right-24 h-72 w-72 rounded-full bg-accent-400/30 blur-3xl" />
        <div className="relative">
          <h1 className="max-w-2xl text-3xl font-extrabold tracking-tight sm:text-4xl">
            Le cinéma malien, à portée de Mobile Money.
          </h1>
          <p className="mt-3 max-w-xl text-orange-50">
            Films, clips et sketchs de créateurs maliens, 100 FCFA la vidéo. Paiement Mobile Money, accès immédiat.
          </p>
          <div className="mt-6 flex flex-wrap gap-2 text-sm font-medium">
            <span className="flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 backdrop-blur">
              <WalletIcon /> 100 FCFA la vidéo
            </span>
            <span className="flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 backdrop-blur">
              <PhoneIcon /> Mobile Money
            </span>
            <span className="flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 backdrop-blur">
              <ClapperIcon /> Créateurs maliens
            </span>
          </div>
        </div>
      </section>

      {featured.length > 0 && (
        <section className="mt-14">
          <h2 className="flex items-center gap-2 text-xl font-semibold text-neutral-900 dark:text-neutral-50">
            <span className="h-5 w-1.5 rounded-full bg-orange-600" />
            En vedette
          </h2>
          <div className="mt-4 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            {featured.map((video) => (
              <VideoCard key={video.id} video={video} />
            ))}
          </div>
        </section>
      )}

      <section className="mt-14">
        <h2 className="flex items-center gap-2 text-xl font-semibold text-neutral-900 dark:text-neutral-50">
          <span className="h-5 w-1.5 rounded-full bg-orange-600" />
          {creatorName ? `Vidéos de ${creatorName}` : "Catalogue"}
        </h2>

        {creatorId && (
          <p className="mt-1 ml-4 text-sm text-neutral-500 dark:text-neutral-400">
            <Link href="/" className="font-medium text-orange-600 hover:underline dark:text-orange-400">
              ← Voir tout le catalogue
            </Link>
          </p>
        )}

        <div className="mt-4 rounded-xl border border-neutral-200 bg-white/60 p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-950/60">
          <CatalogueFilters categories={categories} defaultCategory={category} defaultSearch={search} defaultSort={sort} />
        </div>

        {catalogue.data.length === 0 ? (
          <div className="mt-6 flex flex-col items-center gap-3 rounded-xl border border-dashed border-neutral-300 py-14 text-center dark:border-neutral-700">
            <span className="flex h-12 w-12 items-center justify-center rounded-full bg-neutral-100 text-neutral-400 dark:bg-neutral-900 dark:text-neutral-600">
              <SearchOffIcon />
            </span>
            <p className="text-neutral-500 dark:text-neutral-400">Aucune vidéo ne correspond à ces critères.</p>
            {(category || search || creatorId) && (
              <Link href="/" className="text-sm font-medium text-orange-600 hover:underline dark:text-orange-400">
                Réinitialiser les filtres
              </Link>
            )}
          </div>
        ) : (
          <div className="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            {catalogue.data.map((video) => (
              <VideoCard key={video.id} video={video} />
            ))}
          </div>
        )}

        <Pagination
          currentPage={catalogue.meta.current_page}
          lastPage={catalogue.meta.last_page}
          searchParams={{ category, search, creator_id: creatorIdParam, sort: sort !== "recent" ? sort : undefined }}
        />
      </section>

      <RecommendedVideos />
    </main>
  );
}

/**
 * Plain SVG rather than emoji: color-emoji fonts aren't guaranteed to be
 * installed (e.g. plain Linux desktops), which silently degrades emoji to
 * ugly monochrome fallback glyphs — an SVG renders identically everywhere.
 */
function WalletIcon() {
  return (
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
      <rect x="2" y="6" width="20" height="13" rx="2" />
      <path d="M2 10h20" />
      <circle cx="16.5" cy="14.5" r="1" fill="currentColor" stroke="none" />
    </svg>
  );
}

function PhoneIcon() {
  return (
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
      <rect x="7" y="2" width="10" height="20" rx="2" />
      <path d="M11 18h2" />
    </svg>
  );
}

function ClapperIcon() {
  return (
    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
      <path d="M3 9l1.2-4.2a1 1 0 0 1 1-.8H19a1 1 0 0 1 1 1V9" />
      <rect x="3" y="9" width="18" height="11" rx="1" />
      <path d="M7 9l1.5-5M13 9l1.5-5" />
    </svg>
  );
}

function SearchOffIcon() {
  return (
    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
      <circle cx="10" cy="10" r="6" />
      <path d="M14.5 14.5 20 20M4 4l16 16" />
    </svg>
  );
}
