import type { Metadata } from "next";
import { NotificationsPageClient } from "./NotificationsPageClient";

export const metadata: Metadata = {
  title: "Notifications — StreamMali",
};

export default function NotificationsPage() {
  return <NotificationsPageClient />;
}
