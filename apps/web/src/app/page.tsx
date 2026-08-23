import { fetchCategories, fetchFeaturedVideos, fetchVideos } from "@/lib/api";
import { CatalogueFilters } from "@/components/CatalogueFilters";
import { Pagination } from "@/components/Pagination";
import { RecommendedVideos } from "@/components/RecommendedVideos";
import { VideoCard } from "@/components/VideoCard";

export default async function CataloguePage(props: PageProps<"/">) {
  const searchParams = await props.searchParams;
  const category = typeof searchParams.category === "string" ? searchParams.category : undefined;
  const search = typeof searchParams.search === "string" ? searchParams.search : undefined;
  const page = typeof searchParams.page === "string" ? Number(searchParams.page) || 1 : 1;

  const [catalogue, categories, featured] = await Promise.all([
    fetchVideos({ category, search, page }),
    fetchCategories(),
    fetchFeaturedVideos(),
  ]);

  return (
    <main className="mx-auto w-full max-w-6xl flex-1 px-4 py-8 sm:px-6 lg:px-8">
      <section className="overflow-hidden rounded-2xl bg-gradient-to-br from-orange-600 via-orange-500 to-emerald-600 px-6 py-10 text-white shadow-lg sm:px-10 sm:py-14">
        <h1 className="max-w-2xl text-3xl font-extrabold tracking-tight sm:text-4xl">
          Le cinéma malien, à portée de Mobile Money.
        </h1>
        <p className="mt-3 max-w-xl text-orange-50">
          Films, clips et sketchs de créateurs maliens, 25 FCFA la vidéo. Paiement Orange Money, accès immédiat.
        </p>
      </section>

      {featured.length > 0 && (
        <section className="mt-10">
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

      <div className="mt-6">
        <CatalogueFilters categories={categories} defaultCategory={category} defaultSearch={search} />
      </div>

      {catalogue.data.length === 0 ? (
        <p className="mt-10 text-neutral-500 dark:text-neutral-400">
          Aucune vidéo ne correspond à ces critères.
        </p>
      ) : (
        <div className="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
          {catalogue.data.map((video) => (
            <VideoCard key={video.id} video={video} />
          ))}
        </div>
      )}

      <Pagination
        currentPage={catalogue.meta.current_page}
        lastPage={catalogue.meta.last_page}
        searchParams={{ category, search }}
      />

      <RecommendedVideos />
    </main>
  );
}
