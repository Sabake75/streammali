"use client";

import { useEffect, useState } from "react";
import { createVideo, fetchCategories } from "@/lib/api-client";
import type { VideoCategory, VideoCategoryValue } from "@/lib/types";

export function NewVideoForm({ onCreated }: { onCreated: () => void }) {
  const [title, setTitle] = useState("");
  const [description, setDescription] = useState("");
  const [categories, setCategories] = useState<VideoCategory[]>([]);
  const [category, setCategory] = useState<VideoCategoryValue>("");
  const [price, setPrice] = useState("25");
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    fetchCategories()
      .then((fetched) => {
        setCategories(fetched);
        setCategory((current) => current || (fetched[0]?.value ?? ""));
      })
      .catch(() => undefined);
  }, []);

  async function handleSubmit(event: React.FormEvent) {
    event.preventDefault();
    setSubmitting(true);
    setError(null);

    try {
      await createVideo({
        title,
        description: description || undefined,
        category,
        price: price ? Number(price) : undefined,
      });
      setTitle("");
      setDescription("");
      setPrice("25");
      onCreated();
    } catch (err) {
      setError(err instanceof Error ? err.message : "Une erreur est survenue.");
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <form
      onSubmit={handleSubmit}
      className="flex flex-col gap-3 rounded-lg border border-neutral-200 p-4 dark:border-neutral-800"
    >
      <h2 className="font-semibold">Nouvelle vidéo</h2>
      <input
        type="text"
        required
        value={title}
        onChange={(event) => setTitle(event.target.value)}
        placeholder="Titre"
        className="rounded border border-neutral-300 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-900"
      />
      <textarea
        value={description}
        onChange={(event) => setDescription(event.target.value)}
        placeholder="Description (optionnel)"
        className="rounded border border-neutral-300 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-900"
      />
      <div className="flex flex-wrap gap-3">
        <select
          value={category}
          onChange={(event) => setCategory(event.target.value as VideoCategoryValue)}
          className="rounded border border-neutral-300 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-900"
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
          className="w-32 rounded border border-neutral-300 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-900"
        />
      </div>
      {error && <p className="text-sm text-red-600 dark:text-red-400">{error}</p>}
      <button
        type="submit"
        disabled={submitting}
        className="self-start rounded bg-neutral-900 px-4 py-2 text-sm font-medium text-white hover:bg-neutral-700 disabled:opacity-60 dark:bg-neutral-50 dark:text-neutral-900 dark:hover:bg-neutral-300"
      >
        {submitting ? "Création…" : "Créer"}
      </button>
    </form>
  );
}
