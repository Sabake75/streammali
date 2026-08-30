import type { Metadata } from "next";
import { FavoritesPageClient } from "./FavoritesPageClient";

export const metadata: Metadata = {
  title: "Mes favoris — StreamMali",
};

export default function FavoritesPage() {
  return <FavoritesPageClient />;
}
