import { fetchCategories, fetchVideos } from "@/lib/api";
import { CatalogueFilters } from "@/components/CatalogueFilters";
import { Pagination } from "@/components/Pagination";
import { RecommendedVideos } from "@/components/RecommendedVideos";
import { VideoCard } from "@/components/VideoCard";

export default async function CataloguePage(props: PageProps<"/">) {
  const searchParams = await props.searchParams;
  const category = typeof searchParams.category === "string" ? searchParams.category : undefined;
  const search = typeof searchParams.search === "string" ? searchParams.search : undefined;
  const page = typeof searchParams.page === "string" ? Number(searchParams.page) || 1 : 1;

  const [catalogue, categories] = await Promise.all([
    fetchVideos({ category, search, page }),
    fetchCategories(),
  ]);

  return (
    <main className="mx-auto w-full max-w-6xl flex-1 px-4 py-8 sm:px-6 lg:px-8">
      <h1 className="text-3xl font-bold text-neutral-900 dark:text-neutral-50">
        Catalogue
      </h1>
      <p className="mt-1 text-neutral-500 dark:text-neutral-400">
        Films, clips et sketchs de créateurs maliens, 25 FCFA la vidéo.
      </p>

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
