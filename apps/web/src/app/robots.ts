import type { MetadataRoute } from "next";

export default function robots(): MetadataRoute.Robots {
  const siteUrl = process.env.NEXT_PUBLIC_SITE_URL ?? "https://streammali.ml";

  return {
    rules: {
      userAgent: "*",
      allow: "/",
      // Pages privées (compte, bibliothèque, favoris, notifications, back-office)
      // n'apportent rien à l'indexation et nécessitent une session.
      disallow: ["/creer", "/bibliotheque", "/favoris", "/notifications", "/support", "/moderation"],
    },
    sitemap: `${siteUrl}/sitemap.xml`,
  };
}
