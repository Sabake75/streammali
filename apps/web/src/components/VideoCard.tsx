import Link from "next/link";
import { categoryStyle, formatDuration, formatPrice } from "@/lib/format";
import type { VideoSummary } from "@/lib/types";

export function VideoCard({ video }: { video: VideoSummary }) {
  const style = categoryStyle(video.category.value);

  return (
    <Link
      href={`/videos/${video.id}`}
      className="group flex flex-col overflow-hidden rounded-xl border border-neutral-200/80 bg-white shadow-[0_1px_2px_rgba(0,0,0,0.04),0_8px_20px_-12px_rgba(0,0,0,0.12)] transition duration-200 hover:-translate-y-1 hover:border-orange-300 hover:shadow-[0_1px_2px_rgba(0,0,0,0.04),0_16px_32px_-12px_rgba(234,88,12,0.25)] active:scale-[0.98] active:border-orange-300 dark:border-neutral-800 dark:bg-neutral-950 dark:hover:border-orange-800"
    >
      <div
        className={`relative flex aspect-video items-center justify-center overflow-hidden bg-gradient-to-br ${style.tint}`}
      >
        {video.poster_path ? (
          // eslint-disable-next-line @next/next/no-img-element
          <img
            src={video.poster_path}
            alt={video.title}
            className="h-full w-full object-cover"
          />
        ) : (
          <>
            <div className="pointer-events-none absolute inset-0 opacity-[0.15] [background-image:radial-gradient(currentColor_1px,transparent_1px)] [background-size:16px_16px]" />
            <span className="flex h-12 w-12 items-center justify-center rounded-full bg-white/70 text-lg text-neutral-700 shadow-sm backdrop-blur transition group-hover:scale-110 group-hover:bg-white/90 dark:bg-black/30 dark:text-white/80">
              ▶
            </span>
          </>
        )}
        <span className="absolute right-2 bottom-2 rounded-full bg-black/70 px-2 py-0.5 text-xs font-semibold text-white backdrop-blur">
          {formatPrice(video.price)}
        </span>
      </div>
      <div className="flex flex-1 flex-col gap-1 p-4">
        <span className={`w-fit rounded-full px-2.5 py-0.5 text-xs font-semibold tracking-wide ${style.badge}`}>
          {video.category.label}
        </span>
        <h3 className="mt-1 line-clamp-2 font-semibold text-neutral-900 group-hover:text-orange-600 dark:text-neutral-50 dark:group-hover:text-orange-400">
          {video.title}
        </h3>
        <p className="mt-auto text-sm text-neutral-500 dark:text-neutral-400">
          {video.creator.name} · {formatDuration(video.duration_seconds)}
        </p>
      </div>
    </Link>
  );
}
