import Link from "next/link";
import type { Metadata } from "next";
import { CreatorTermsContent } from "@/components/legal/CreatorTermsContent";

export const metadata: Metadata = {
  title: "CGU créateur — StreamMali",
  description: "Conditions générales d'utilisation de StreamMali pour les créateurs, dont la répartition des revenus.",
};

export default function TermsCreatorPage() {
  return (
    <main className="mx-auto w-full max-w-3xl flex-1 px-4 py-8 sm:px-6 lg:px-8">
      <Link
        href="/"
        className="text-sm text-neutral-500 transition hover:text-orange-600 dark:text-neutral-400 dark:hover:text-orange-400"
      >
        ← Retour au catalogue
      </Link>

      <h1 className="mt-4 flex items-center gap-2 text-3xl font-bold text-neutral-900 dark:text-neutral-50">
        <span className="h-7 w-2 rounded-full bg-orange-600" />
        Conditions générales d&apos;utilisation — Créateur
      </h1>

      <div className="mt-8">
        <CreatorTermsContent />
      </div>
    </main>
  );
}
