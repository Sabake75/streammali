import type { Metadata } from "next";
import { RegisterCreatorPageClient } from "./RegisterCreatorPageClient";

export const metadata: Metadata = {
  title: "Inscription créateur — StreamMali",
};

export default function RegisterCreatorPage() {
  return <RegisterCreatorPageClient />;
}
