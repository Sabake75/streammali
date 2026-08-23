import Link from "next/link";
import { notFound } from "next/navigation";
import { PurchaseButton } from "@/components/PurchaseButton";
import { RecordView } from "@/components/RecordView";
import { ReportButton } from "@/components/ReportButton";
import { VideoPlayer } from "@/components/VideoPlayer";
import { fetchVideo } from "@/lib/api";
import { formatDuration, formatPrice } from "@/lib/format";

export default async function VideoDetailPage(props: PageProps<"/videos/[id]">) {
  const { id } = await props.params;
  const video = await fetchVideo(id);

  if (!video) {
    notFound();
  }

  const canWatchFull = Boolean(video.purchased && video.playback_url);
  const canWatchPreview = !canWatchFull && Boolean(video.preview_playback_url);

  return (
    <main className="mx-auto w-full max-w-4xl flex-1 px-4 py-8 sm:px-6 lg:px-8">
      <RecordView videoId={video.id} />
      <Link href="/" className="text-sm text-neutral-500 hover:underline">
        ← Retour au catalogue
      </Link>

      <div className="mt-4 aspect-video overflow-hidden rounded-lg bg-neutral-100 dark:bg-neutral-900">
        {canWatchFull ? (
          <VideoPlayer src={video.playback_url!} poster={video.poster_path} />
        ) : canWatchPreview ? (
          <VideoPlayer src={video.preview_playback_url!} poster={video.poster_path} />
        ) : video.poster_path ? (
          // eslint-disable-next-line @next/next/no-img-element
          <img
            src={video.poster_path}
            alt={video.title}
            className="h-full w-full object-cover"
          />
        ) : (
          <div className="flex h-full w-full items-center justify-center text-sm text-neutral-400">
            Pas de jaquette
          </div>
        )}
      </div>
      {canWatchPreview && (
        <p className="mt-2 text-sm text-neutral-500 dark:text-neutral-400">
          Aperçu — achète la vidéo pour la voir en entier.
        </p>
      )}

      <div className="mt-6 flex flex-col gap-3">
        <span className="w-fit rounded bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
          {video.category.label}
        </span>
        <h1 className="text-2xl font-bold text-neutral-900 dark:text-neutral-50">
          {video.title}
        </h1>
        <p className="text-neutral-500 dark:text-neutral-400">
          Par {video.creator.name} · {formatDuration(video.duration_seconds)}
        </p>
        {video.description && (
          <p className="text-neutral-700 dark:text-neutral-300">{video.description}</p>
        )}

        <div className="mt-4 flex flex-wrap items-center gap-4">
          <span className="text-2xl font-bold text-neutral-900 dark:text-neutral-50">
            {formatPrice(video.price)}
          </span>
          <PurchaseButton videoId={video.id} />
        </div>

        <div className="mt-2">
          <ReportButton videoId={video.id} />
        </div>
      </div>
    </main>
  );
}
