"use client";

import { useEffect, useRef, useState } from "react";
import {
  createVideo,
  createVideoUploadUrl,
  fetchCategories,
  fetchVideoSourceStatus,
  uploadVideoFile,
} from "@/lib/api-client";
import type { VideoCategory, VideoCategoryValue } from "@/lib/types";

const POLL_INTERVAL_MS = 5000;

type Phase = "form" | "creating" | "uploading" | "processing" | "ready" | "failed";

export function NewVideoForm({ onCreated }: { onCreated: () => void }) {
  const [title, setTitle] = useState("");
  const [description, setDescription] = useState("");
  const [categories, setCategories] = useState<VideoCategory[]>([]);
  const [category, setCategory] = useState<VideoCategoryValue>("");
  const [price, setPrice] = useState("100");
  const [file, setFile] = useState<File | null>(null);
  const [phase, setPhase] = useState<Phase>("form");
  const [progress, setProgress] = useState(0);
  const [error, setError] = useState<string | null>(null);
  const videoIdRef = useRef<number | null>(null);

  useEffect(() => {
    fetchCategories()
      .then((fetched) => {
        setCategories(fetched);
        setCategory((current) => current || (fetched[0]?.value ?? ""));
      })
      .catch(() => undefined);
  }, []);

  // Polls until Cloudflare finishes transcoding — same pattern as
  // VideoUploadWidget (which still owns this for videos created earlier).
  useEffect(() => {
    if (phase !== "processing" || videoIdRef.current === null) return;

    const interval = setInterval(async () => {
      try {
        const result = await fetchVideoSourceStatus(videoIdRef.current!);
        if (result.source_status.value === "ready") {
          setPhase("ready");
        } else if (result.source_status.value === "failed") {
          setPhase("failed");
        }
      } catch {
        // transient polling failure — try again next tick
      }
    }, POLL_INTERVAL_MS);

    return () => clearInterval(interval);
  }, [phase]);

  async function handleSubmit(event: React.FormEvent) {
    event.preventDefault();
    if (!file) {
      setError("Choisis un fichier vidéo.");
      return;
    }

    setError(null);
    setPhase("creating");

    try {
      // The file always goes straight from the browser to Cloudflare, never
      // through our API — but Cloudflare's upload URL is tied to a video
      // record that has to exist first, so this is unavoidably two calls
      // even though it's one form/one click for the creator.
      const video = await createVideo({
        title,
        description: description || undefined,
        category,
        price: price ? Number(price) : undefined,
      });
      videoIdRef.current = video.id;

      const { upload_url } = await createVideoUploadUrl(video.id);

      setPhase("uploading");
      await uploadVideoFile(upload_url, file, setProgress);
      setPhase("processing");
      onCreated();
    } catch (err) {
      setError(err instanceof Error ? err.message : "Une erreur est survenue.");
      setPhase("form");
    }
  }

  function handleAddAnother() {
    setPhase("form");
    setTitle("");
    setDescription("");
    setPrice("100");
    setFile(null);
    setProgress(0);
    videoIdRef.current = null;
  }

  if (phase !== "form") {
    return (
      <div className="flex flex-col gap-2 rounded-xl border border-neutral-200 p-4 shadow-sm dark:border-neutral-800">
        <h2 className="font-semibold">{title}</h2>
        {phase === "creating" && <p className="text-sm text-neutral-500">Création…</p>}
        {phase === "uploading" && (
          <p className="text-sm text-neutral-500">Envoi en cours… {progress}%</p>
        )}
        {phase === "processing" && <p className="text-sm text-neutral-500">Traitement en cours…</p>}
        {phase === "ready" && (
          <p className="text-sm text-green-700 dark:text-green-400">Fichier vidéo prêt.</p>
        )}
        {phase === "failed" && (
          <p className="text-sm text-red-600 dark:text-red-400">Échec du traitement.</p>
        )}
        {(phase === "ready" || phase === "failed") && (
          <button
            type="button"
            onClick={handleAddAnother}
            className="self-start text-sm text-neutral-500 underline hover:text-neutral-700 dark:text-neutral-400 dark:hover:text-neutral-200"
          >
            Ajouter une autre vidéo
          </button>
        )}
      </div>
    );
  }

  return (
    <form
      onSubmit={handleSubmit}
      className="flex flex-col gap-3 rounded-xl border border-neutral-200 p-4 shadow-sm dark:border-neutral-800"
    >
      <h2 className="flex items-center gap-2 font-semibold">
        <span className="h-4 w-1 rounded-full bg-orange-600" />
        Nouvelle vidéo
      </h2>
      <input
        type="text"
        required
        value={title}
        onChange={(event) => setTitle(event.target.value)}
        placeholder="Titre"
        className="input-field"
      />
      <textarea
        value={description}
        onChange={(event) => setDescription(event.target.value)}
        placeholder="Description (optionnel)"
        className="input-field"
      />
      <div className="flex flex-wrap gap-3">
        <select
          value={category}
          onChange={(event) => setCategory(event.target.value as VideoCategoryValue)}
          className="input-field"
        >
          {categories.map((cat) => (
            <option key={cat.value} value={cat.value}>
              {cat.label}
            </option>
          ))}
        </select>
        <input
          type="number"
          min={0}
          value={price}
          onChange={(event) => setPrice(event.target.value)}
          placeholder="Prix (FCFA)"
          className="input-field w-32"
        />
      </div>
      <label className="text-sm">
        <span className="mb-1 block text-neutral-600 dark:text-neutral-400">Fichier vidéo</span>
        <input
          type="file"
          accept="video/*"
          required
          onChange={(event) => setFile(event.target.files?.[0] ?? null)}
          className="block w-full text-sm text-neutral-500 file:mr-3 file:rounded-lg file:border-0 file:bg-orange-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-orange-700 hover:file:bg-orange-100 dark:text-neutral-400 dark:file:bg-orange-950/50 dark:file:text-orange-300"
        />
      </label>
      {error && <p className="text-sm text-red-600 dark:text-red-400">{error}</p>}
      <button type="submit" className="btn-primary self-start">
        Créer et envoyer
      </button>
    </form>
  );
}
