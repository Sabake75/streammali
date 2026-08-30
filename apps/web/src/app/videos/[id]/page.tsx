import type { Metadata } from "next";
import Image from "next/image";
import Link from "next/link";
import { notFound } from "next/navigation";
import { FavoriteButton } from "@/components/FavoriteButton";
import { PurchaseButton } from "@/components/PurchaseButton";
import { RecordView } from "@/components/RecordView";
import { ReportButton } from "@/components/ReportButton";
import { Reviews } from "@/components/Reviews";
import { ShareButton } from "@/components/ShareButton";
import { VideoCard } from "@/components/VideoCard";
import { VideoPlayer } from "@/components/VideoPlayer";
import { fetchVideo, fetchVideos } from "@/lib/api";
import { categoryStyle, formatDuration, formatPrice } from "@/lib/format";

export async function generateMetadata(props: PageProps<"/videos/[id]">): Promise<Metadata> {
  const { id } = await props.params;
  const video = await fetchVideo(id);

  if (!video) return {};

  const description =
    video.description ?? `${video.category.label} de ${video.creator.name}, ${formatPrice(video.price)} sur StreamMali.`;

  return {
    title: `${video.title} — StreamMali`,
    description,
    openGraph: {
      title: video.title,
      description,
      url: `/videos/${video.id}`,
      images: video.poster_path ? [{ url: video.poster_path }] : undefined,
      type: "video.other",
    },
    twitter: {
      card: video.poster_path ? "summary_large_image" : "summary",
      title: video.title,
      description,
      images: video.poster_path ? [video.poster_path] : undefined,
    },
  };
}

export default async function VideoDetailPage(props: PageProps<"/videos/[id]">) {
  const { id } = await props.params;
  const video = await fetchVideo(id);

  if (!video) {
    notFound();
  }

  const canWatchFull = Boolean(video.purchased && video.playback_url);
  const canWatchPreview = !canWatchFull && Boolean(video.preview_playback_url);
  const style = categoryStyle(video.category.value);

  const similar = await fetchVideos({ category: video.category.value });
  const similarVideos = similar.data.filter((v) => v.id !== video.id).slice(0, 6);

  return (
    <main className="mx-auto w-full max-w-4xl flex-1 px-4 py-8 sm:px-6 lg:px-8">
      <RecordView videoId={video.id} />
      <Link
        href="/"
        className="text-sm text-neutral-500 transition hover:text-orange-600 dark:text-neutral-400 dark:hover:text-orange-400"
      >
        ← Retour au catalogue
      </Link>

      <div
        className={`relative mt-4 aspect-video overflow-hidden rounded-xl bg-gradient-to-br shadow-sm ${style.tint}`}
      >
        {canWatchFull ? (
          <VideoPlayer src={video.playback_url!} poster={video.poster_path} />
        ) : canWatchPreview ? (
          <VideoPlayer src={video.preview_playback_url!} poster={video.poster_path} />
        ) : video.poster_path ? (
          <Image
            src={video.poster_path}
            alt={video.title}
            fill
            sizes="(min-width: 1024px) 896px, 100vw"
            priority
            className="object-cover"
          />
        ) : (
          <div className="flex h-full w-full items-center justify-center">
            <span className="flex h-16 w-16 items-center justify-center rounded-full bg-white/70 text-2xl text-neutral-700 shadow-sm backdrop-blur dark:bg-black/30 dark:text-white/80">
              ▶
            </span>
          </div>
        )}
      </div>
      {canWatchPreview && (
        <p className="mt-2 text-sm text-neutral-500 dark:text-neutral-400">
          Aperçu — achète la vidéo pour la voir en entier.
        </p>
      )}

      <div className="mt-6 flex flex-col gap-3">
        <span className={`w-fit rounded-full px-2.5 py-0.5 text-xs font-semibold tracking-wide ${style.badge}`}>
          {video.category.label}
        </span>
        <h1 className="text-2xl font-bold text-neutral-900 dark:text-neutral-50">
          {video.title}
        </h1>
        <p className="text-neutral-500 dark:text-neutral-400">
          Par{" "}
          <Link
            href={`/?creator_id=${video.creator.id}`}
            className="font-medium hover:text-orange-600 hover:underline dark:hover:text-orange-400"
          >
            {video.creator.name}
          </Link>{" "}
          · {formatDuration(video.duration_seconds)}
          {video.reviews_count > 0 && (
            <>
              {" "}
              · <span className="text-amber-500">★</span> {video.average_rating} ({video.reviews_count} avis)
            </>
          )}
        </p>
        {video.description && (
          <p className="text-neutral-700 dark:text-neutral-300">{video.description}</p>
        )}

        <div className="mt-4 rounded-xl border border-neutral-200 p-4 shadow-sm dark:border-neutral-800">
          <div className="flex flex-wrap items-center gap-4">
            <span className="text-2xl font-bold text-orange-700 dark:text-orange-400">
              {formatPrice(video.price)}
            </span>
            <PurchaseButton videoId={video.id} />
            <FavoriteButton videoId={video.id} initialFavorited={Boolean(video.favorited)} />
            <ShareButton title={video.title} />
          </div>
          <div className="mt-3 border-t border-neutral-100 pt-3 dark:border-neutral-900">
            <ReportButton videoId={video.id} />
          </div>
        </div>
      </div>

      <Reviews videoId={video.id} purchased={Boolean(video.purchased)} />

      {similarVideos.length > 0 && (
        <section className="mt-10">
          <h2 className="flex items-center gap-2 text-xl font-semibold text-neutral-900 dark:text-neutral-50">
            <span className="h-5 w-1.5 rounded-full bg-orange-600" />
            Vidéos similaires
          </h2>
          <div className="mt-4 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            {similarVideos.map((similarVideo) => (
              <VideoCard key={similarVideo.id} video={similarVideo} />
            ))}
          </div>
        </section>
      )}
    </main>
  );
}
