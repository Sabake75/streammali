"use client";

import Hls from "hls.js";
import { useEffect, useRef, useState } from "react";

function formatTime(seconds: number): string {
  if (!Number.isFinite(seconds) || seconds < 0) return "0:00";
  const total = Math.floor(seconds);
  const h = Math.floor(total / 3600);
  const m = Math.floor((total % 3600) / 60);
  const s = total % 60;
  const mm = h > 0 ? m.toString().padStart(2, "0") : String(m);
  const ss = s.toString().padStart(2, "0");
  return h > 0 ? `${h}:${mm}:${ss}` : `${mm}:${ss}`;
}

function PlayIcon() {
  return (
    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden>
      <path d="M8 5v14l11-7z" />
    </svg>
  );
}

function PauseIcon() {
  return (
    <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden>
      <path d="M7 5h4v14H7zM13 5h4v14h-4z" />
    </svg>
  );
}

function VolumeIcon({ muted }: { muted: boolean }) {
  return (
    <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden>
      <path d="M4 9v6h4l5 5V4L8 9H4z" />
      {muted && <path d="M18 9l4 6M22 9l-4 6" stroke="currentColor" strokeWidth="2" strokeLinecap="round" fill="none" />}
      {!muted && (
        <path
          d="M16.5 8.5a4.5 4.5 0 0 1 0 7"
          stroke="currentColor"
          strokeWidth="1.6"
          strokeLinecap="round"
          fill="none"
        />
      )}
    </svg>
  );
}

function FullscreenIcon() {
  return (
    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
      <path d="M8 3H5a2 2 0 0 0-2 2v3M16 3h3a2 2 0 0 1 2 2v3M21 16v3a2 2 0 0 1-2 2h-3M8 21H5a2 2 0 0 1-2-2v-3" />
    </svg>
  );
}

/**
 * `src` is an HLS manifest URL (Cloudflare Stream's `playback.hls`, see
 * apps/api CloudflareStreamGateway) — Safari plays it natively, everywhere
 * else needs hls.js. No autoplay: a centered play button is the tap-to-play
 * affordance (keeps data usage opt-in, see cahier des charges' "faible
 * consommation de données" constraint).
 *
 * Custom controls (instead of the browser's native `<video controls>`)
 * because native controls can't be restyled consistently across browsers
 * (Firefox exposes no styling hooks at all) — they looked visibly out of
 * place next to the rest of the branded UI.
 */
export function VideoPlayer({ src, poster }: { src: string; poster?: string | null }) {
  const videoRef = useRef<HTMLVideoElement>(null);
  const containerRef = useRef<HTMLDivElement>(null);
  const [playing, setPlaying] = useState(false);
  const [started, setStarted] = useState(false);
  const [currentTime, setCurrentTime] = useState(0);
  const [duration, setDuration] = useState(0);
  const [muted, setMuted] = useState(false);
  const [fullscreen, setFullscreen] = useState(false);

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

  useEffect(() => {
    function onFullscreenChange() {
      setFullscreen(document.fullscreenElement === containerRef.current);
    }
    document.addEventListener("fullscreenchange", onFullscreenChange);
    return () => document.removeEventListener("fullscreenchange", onFullscreenChange);
  }, []);

  function togglePlay() {
    const video = videoRef.current;
    if (!video) return;
    setStarted(true);
    if (video.paused) void video.play();
    else video.pause();
  }

  function toggleMute() {
    const video = videoRef.current;
    if (!video) return;
    video.muted = !video.muted;
    setMuted(video.muted);
  }

  function toggleFullscreen() {
    const container = containerRef.current;
    if (!container) return;
    if (document.fullscreenElement) void document.exitFullscreen();
    else void container.requestFullscreen();
  }

  function seek(event: React.ChangeEvent<HTMLInputElement>) {
    const video = videoRef.current;
    if (!video || !duration) return;
    const time = (Number(event.target.value) / 100) * duration;
    video.currentTime = time;
    setCurrentTime(time);
  }

  const progress = duration > 0 ? (currentTime / duration) * 100 : 0;

  return (
    <div ref={containerRef} className="group relative overflow-hidden rounded-lg bg-black">
      <video
        ref={videoRef}
        poster={poster ?? undefined}
        className="block h-full w-full"
        onClick={togglePlay}
        onPlay={() => setPlaying(true)}
        onPause={() => setPlaying(false)}
        onTimeUpdate={(e) => setCurrentTime(e.currentTarget.currentTime)}
        onDurationChange={(e) => setDuration(e.currentTarget.duration || 0)}
        onLoadedMetadata={(e) => setDuration(e.currentTarget.duration || 0)}
      >
        Ton navigateur ne prend pas en charge la lecture vidéo.
      </video>

      {!started && (
        <button
          type="button"
          onClick={togglePlay}
          aria-label="Lire la vidéo"
          className="absolute inset-0 flex items-center justify-center bg-black/25 transition hover:bg-black/35"
        >
          <span className="flex h-16 w-16 items-center justify-center rounded-full bg-orange-600/90 text-white shadow-lg transition group-hover:scale-105">
            <span className="translate-x-0.5">
              <svg viewBox="0 0 24 24" width="28" height="28" fill="currentColor" aria-hidden>
                <path d="M8 5v14l11-7z" />
              </svg>
            </span>
          </span>
        </button>
      )}

      <div
        className={`absolute inset-x-0 bottom-0 flex flex-col gap-1.5 bg-gradient-to-t from-black/80 to-transparent px-3 pb-2 pt-6 text-white transition-opacity ${
          started ? "opacity-0 group-hover:opacity-100 group-focus-within:opacity-100" : "opacity-100"
        }`}
      >
        <input
          type="range"
          min={0}
          max={100}
          step={0.1}
          value={progress}
          onChange={seek}
          aria-label="Progression de la vidéo"
          className="h-1.5 w-full cursor-pointer appearance-none rounded-full bg-white/25 accent-orange-500"
        />
        <div className="flex items-center gap-3">
          <button type="button" onClick={togglePlay} aria-label={playing ? "Mettre en pause" : "Lire"} className="hover:text-orange-300">
            {playing ? <PauseIcon /> : <PlayIcon />}
          </button>
          <button type="button" onClick={toggleMute} aria-label={muted ? "Réactiver le son" : "Couper le son"} className="hover:text-orange-300">
            <VolumeIcon muted={muted} />
          </button>
          <span className="text-xs tabular-nums text-white/80">
            {formatTime(currentTime)} / {formatTime(duration)}
          </span>
          <span className="flex-1" />
          <button
            type="button"
            onClick={toggleFullscreen}
            aria-label={fullscreen ? "Quitter le plein écran" : "Plein écran"}
            className="hover:text-orange-300"
          >
            <FullscreenIcon />
          </button>
        </div>
      </div>
    </div>
  );
}
