import type { Metadata } from "next";
import Link from "next/link";
import { Stats } from "@/components/creator/Stats";

export const metadata: Metadata = {
  title: "Statistiques — StreamMali",
};

export default function StatisticsPage() {
  return (
    <main className="mx-auto w-full max-w-4xl flex-1 px-4 py-8 sm:px-6 lg:px-8">
      <Link
        href="/creer"
        className="text-sm text-neutral-500 transition hover:text-orange-600 dark:text-neutral-400 dark:hover:text-orange-400"
      >
        ← Retour à l&apos;espace créateur
      </Link>
      <h1 className="mt-4 flex items-center gap-2 text-2xl font-bold text-neutral-900 dark:text-neutral-50">
        <span className="h-6 w-1.5 rounded-full bg-orange-600" />
        Statistiques
      </h1>
      <div className="mt-6">
        <Stats />
      </div>
    </main>
  );
}
