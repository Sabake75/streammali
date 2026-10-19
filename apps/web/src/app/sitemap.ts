import type { MetadataRoute } from "next";
import { fetchVideos } from "@/lib/api";

const SITE_URL = process.env.NEXT_PUBLIC_SITE_URL ?? "https://streammali.ml";

// Le catalogue ne renvoie que les vidéos validées (voir apps/api), donc tout
// ce qu'on récupère ici est public et indexable.
async function fetchAllVideoIds(): Promise<number[]> {
  const ids: number[] = [];
  let page = 1;

  while (true) {
    const { data, meta } = await fetchVideos({ page });
    ids.push(...data.map((video) => video.id));

    if (page >= meta.last_page) break;
    page += 1;
  }

  return ids;
}

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const videoIds = await fetchAllVideoIds();

  return [
    { url: SITE_URL, changeFrequency: "daily", priority: 1 },
    { url: `${SITE_URL}/creer`, changeFrequency: "monthly", priority: 0.6 },
    { url: `${SITE_URL}/cgu-spectateur`, changeFrequency: "yearly", priority: 0.2 },
    { url: `${SITE_URL}/cgu-createur`, changeFrequency: "yearly", priority: 0.2 },
    { url: `${SITE_URL}/politique-de-confidentialite`, changeFrequency: "yearly", priority: 0.2 },
    ...videoIds.map((id) => ({
      url: `${SITE_URL}/videos/${id}`,
      changeFrequency: "weekly" as const,
      priority: 0.8,
    })),
  ];
}
