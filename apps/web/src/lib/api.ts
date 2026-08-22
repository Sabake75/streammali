import type { PaginatedResponse, VideoSummary } from "@/lib/types";

// Appels SSR (server components) : peuvent avoir besoin d'une URL différente de celle du
// navigateur (ex. nom de service Docker), d'où API_INTERNAL_URL en priorité sur NEXT_PUBLIC_API_URL.
const API_BASE_URL =
  process.env.API_INTERNAL_URL ?? process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api";

export const VIDEO_CATEGORIES: { value: string; label: string }[] = [
  { value: "film", label: "Film" },
  { value: "clip", label: "Clip" },
  { value: "sketch", label: "Sketch" },
  { value: "series", label: "Web-série" },
];

export type CatalogueFilters = {
  category?: string;
  search?: string;
  page?: number;
};

export async function fetchVideos(
  filters: CatalogueFilters = {},
): Promise<PaginatedResponse<VideoSummary>> {
  const query = new URLSearchParams();
  if (filters.category) query.set("category", filters.category);
  if (filters.search) query.set("search", filters.search);
  if (filters.page && filters.page > 1) query.set("page", String(filters.page));

  const response = await fetch(`${API_BASE_URL}/videos?${query.toString()}`, {
    next: { revalidate: 60 },
  });

  if (!response.ok) {
    throw new Error(`Impossible de charger le catalogue (${response.status})`);
  }

  return response.json();
}

export async function fetchVideo(id: string): Promise<VideoSummary | null> {
  const response = await fetch(`${API_BASE_URL}/videos/${id}`, {
    next: { revalidate: 60 },
  });

  if (response.status === 404) {
    return null;
  }

  if (!response.ok) {
    throw new Error(`Impossible de charger la vidéo (${response.status})`);
  }

  const json: { data: VideoSummary } = await response.json();
  return json.data;
}
