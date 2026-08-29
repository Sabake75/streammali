import type { PaginatedResponse, VideoCategory, VideoSummary } from "@/lib/types";

// Appels SSR (server components) : peuvent avoir besoin d'une URL différente de celle du
// navigateur (ex. nom de service Docker), d'où API_INTERNAL_URL en priorité sur NEXT_PUBLIC_API_URL.
const API_BASE_URL =
  process.env.API_INTERNAL_URL ?? process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api";

export async function fetchCategories(): Promise<VideoCategory[]> {
  const response = await fetch(`${API_BASE_URL}/categories`, {
    next: { revalidate: 300 },
  });

  if (!response.ok) {
    throw new Error(`Impossible de charger les catégories (${response.status})`);
  }

  const json: { data: VideoCategory[] } = await response.json();
  return json.data;
}

export type CatalogueFilters = {
  category?: string;
  search?: string;
  page?: number;
  creatorId?: number;
};

export async function fetchVideos(
  filters: CatalogueFilters = {},
): Promise<PaginatedResponse<VideoSummary>> {
  const query = new URLSearchParams();
  if (filters.category) query.set("category", filters.category);
  if (filters.search) query.set("search", filters.search);
  if (filters.page && filters.page > 1) query.set("page", String(filters.page));
  if (filters.creatorId) query.set("creator_id", String(filters.creatorId));

  const response = await fetch(`${API_BASE_URL}/videos?${query.toString()}`, {
    next: { revalidate: 60 },
  });

  if (!response.ok) {
    throw new Error(`Impossible de charger le catalogue (${response.status})`);
  }

  return response.json();
}

export async function fetchFeaturedVideos(): Promise<VideoSummary[]> {
  const response = await fetch(`${API_BASE_URL}/videos/featured`, {
    next: { revalidate: 60 },
  });

  if (!response.ok) {
    throw new Error(`Impossible de charger les vidéos en vedette (${response.status})`);
  }

  const json: { data: VideoSummary[] } = await response.json();
  return json.data;
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
