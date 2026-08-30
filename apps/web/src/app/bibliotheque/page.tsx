import type { Metadata } from "next";
import { LibraryPageClient } from "./LibraryPageClient";

export const metadata: Metadata = {
  title: "Mes achats — StreamMali",
};

export default function LibraryPage() {
  return <LibraryPageClient />;
}
