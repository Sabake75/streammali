import Link from "next/link";
import { formatDuration, formatPrice } from "@/lib/format";
import type { VideoSummary } from "@/lib/types";

export function VideoCard({ video }: { video: VideoSummary }) {
  return (
    <Link
      href={`/videos/${video.id}`}
      className="group flex flex-col overflow-hidden rounded-xl border border-neutral-200 bg-white shadow-sm transition hover:-translate-y-1 hover:border-orange-300 hover:shadow-lg dark:border-neutral-800 dark:bg-neutral-950 dark:hover:border-orange-800"
    >
      <div className="relative flex aspect-video items-center justify-center bg-gradient-to-br from-neutral-100 to-neutral-200 text-neutral-400 dark:from-neutral-900 dark:to-neutral-800">
        {video.poster_path ? (
          // eslint-disable-next-line @next/next/no-img-element
          <img
            src={video.poster_path}
            alt={video.title}
            className="h-full w-full object-cover"
          />
        ) : (
          <span className="text-3xl opacity-40">▶</span>
        )}
      </div>
      <div className="flex flex-1 flex-col gap-1 p-4">
        <span className="badge-category">{video.category.label}</span>
        <h3 className="mt-1 line-clamp-2 font-semibold text-neutral-900 group-hover:text-orange-600 dark:text-neutral-50 dark:group-hover:text-orange-400">
          {video.title}
        </h3>
        <p className="text-sm text-neutral-500 dark:text-neutral-400">
          {video.creator.name} · {formatDuration(video.duration_seconds)}
        </p>
        <p className="mt-3 w-fit rounded-full bg-orange-50 px-3 py-1 text-sm font-bold text-orange-700 dark:bg-orange-950/50 dark:text-orange-300">
          {formatPrice(video.price)}
        </p>
      </div>
    </Link>
  );
}
