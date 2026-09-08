import type { Metadata } from "next";
import { SupportPageClient } from "./SupportPageClient";

export const metadata: Metadata = {
  title: "Support — StreamMali",
};

export default function SupportPage() {
  return <SupportPageClient />;
}
