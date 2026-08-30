import type { Metadata } from "next";
import Link from "next/link";
import { Messaging } from "@/components/creator/Messaging";

export const metadata: Metadata = {
  title: "Messagerie — StreamMali",
};

export default function MessagingPage() {
  return (
    <main className="mx-auto w-full max-w-2xl flex-1 px-4 py-8 sm:px-6 lg:px-8">
      <Link
        href="/creer"
        className="text-sm text-neutral-500 transition hover:text-orange-600 dark:text-neutral-400 dark:hover:text-orange-400"
      >
        ← Retour à l&apos;espace créateur
      </Link>
      <h1 className="mt-4 flex items-center gap-2 text-2xl font-bold text-neutral-900 dark:text-neutral-50">
        <span className="h-6 w-1.5 rounded-full bg-orange-600" />
        Messagerie avec la modération
      </h1>
      <div className="mt-6">
        <Messaging />
      </div>
    </main>
  );
}
