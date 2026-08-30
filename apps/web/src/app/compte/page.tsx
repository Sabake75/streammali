import type { Metadata } from "next";
import { AccountPageClient } from "./AccountPageClient";

export const metadata: Metadata = {
  title: "Mon compte — StreamMali",
};

export default function AccountPage() {
  return <AccountPageClient />;
}
