"use client";

import Hls from "hls.js";
import { useEffect, useRef } from "react";

/**
 * `src` is an HLS manifest URL (Cloudflare Stream's `playback.hls`, see
 * apps/api CloudflareStreamGateway) — Safari plays it natively, everywhere
 * else needs hls.js. No autoplay: the browser's native play button is the
 * tap-to-play affordance (keeps data usage opt-in, see cahier des charges'
 * "faible consommation de données" constraint).
 */
export function VideoPlayer({ src, poster }: { src: string; poster?: string | null }) {
  const videoRef = useRef<HTMLVideoElement>(null);

  useEffect(() => {
    const video = videoRef.current;
    if (!video) return;

    if (video.canPlayType("application/vnd.apple.mpegurl")) {
      video.src = src;
      return;
    }

    if (Hls.isSupported()) {
      const hls = new Hls();
      hls.loadSource(src);
      hls.attachMedia(video);
      return () => hls.destroy();
    }
  }, [src]);

  return (
    <video
      ref={videoRef}
      controls
      poster={poster ?? undefined}
      className="h-full w-full rounded-lg bg-black"
    >
      Ton navigateur ne prend pas en charge la lecture vidéo.
    </video>
  );
}
