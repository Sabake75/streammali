import type { Metadata } from "next";
import { CreatorPageClient } from "./CreatorPageClient";

export const metadata: Metadata = {
  title: "Espace créateur — StreamMali",
};

export default function CreatorPage() {
  return <CreatorPageClient />;
}
