import Link from "next/link";
import { formatDuration, formatPrice } from "@/lib/format";
import type { VideoSummary } from "@/lib/types";

export function VideoCard({ video }: { video: VideoSummary }) {
  return (
    <Link
      href={`/videos/${video.id}`}
      className="group flex flex-col overflow-hidden rounded-lg border border-neutral-200 bg-white transition hover:border-neutral-400 hover:shadow-md dark:border-neutral-800 dark:bg-neutral-950 dark:hover:border-neutral-600"
    >
      <div className="flex aspect-video items-center justify-center bg-neutral-100 text-neutral-400 dark:bg-neutral-900">
        {video.poster_path ? (
          // eslint-disable-next-line @next/next/no-img-element
          <img
            src={video.poster_path}
            alt={video.title}
            className="h-full w-full object-cover"
          />
        ) : (
          <span className="text-sm">Pas de jaquette</span>
        )}
      </div>
      <div className="flex flex-1 flex-col gap-1 p-4">
        <span className="w-fit rounded bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
          {video.category.label}
        </span>
        <h3 className="mt-1 line-clamp-2 font-semibold text-neutral-900 group-hover:underline dark:text-neutral-50">
          {video.title}
        </h3>
        <p className="text-sm text-neutral-500 dark:text-neutral-400">
          {video.creator.name} · {formatDuration(video.duration_seconds)}
        </p>
        <p className="mt-auto pt-2 font-semibold text-neutral-900 dark:text-neutral-50">
          {formatPrice(video.price)}
        </p>
      </div>
    </Link>
  );
}
