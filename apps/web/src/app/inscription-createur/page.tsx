import type { Metadata } from "next";
import { RegisterCreatorPageClient } from "./RegisterCreatorPageClient";

export const metadata: Metadata = {
  title: "Devenir créateur - StreamMali",
  description:
    "Publie tes films, clips et sketchs sur StreamMali et touche un revenu à chaque vue, reversé chaque semaine en Mobile Money.",
};

export default function RegisterCreatorPage() {
  return (
    <main className="mx-auto w-full max-w-3xl flex-1 px-4 py-12 sm:px-6">
      <section className="text-center">
        <h1 className="text-2xl font-extrabold tracking-tight text-neutral-900 dark:text-neutral-50 sm:text-3xl">
          Tes vidéos tournent déjà partout. Pourquoi tu ne gagnes rien ?
        </h1>
        <p className="mx-auto mt-3 max-w-xl text-neutral-600 dark:text-neutral-400">
          Réalisateur, humoriste, artiste ? Mets tes créations sur StreamMali, fixe ton prix, et fais-toi payer par
          tes fans à chaque vue, directement sur Orange Money.
        </p>
        <dl className="mt-8 grid gap-4 text-left sm:grid-cols-3">
          <div className="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-950">
            <dt className="text-sm font-semibold text-orange-600 dark:text-orange-400">75 % pour toi</dt>
            <dd className="mt-1 text-sm text-neutral-600 dark:text-neutral-400">
              Sur chaque vente de 100 FCFA, StreamMali garde une commission pour l&apos;hébergement et le paiement
              Mobile Money.
            </dd>
          </div>
          <div className="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-950">
            <dt className="text-sm font-semibold text-orange-600 dark:text-orange-400">Reversé chaque semaine</dt>
            <dd className="mt-1 text-sm text-neutral-600 dark:text-neutral-400">
              Retrait vers ton Mobile Money dès 10 000 FCFA de solde.
            </dd>
          </div>
          <div className="rounded-xl border border-neutral-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-950">
            <dt className="text-sm font-semibold text-orange-600 dark:text-orange-400">Mise en ligne simple</dt>
            <dd className="mt-1 text-sm text-neutral-600 dark:text-neutral-400">
              Dépose ta vidéo et une pièce d&apos;identité, la modération vérifie puis valide la mise en ligne.
            </dd>
          </div>
        </dl>
      </section>

      <div className="mt-10">
        <RegisterCreatorPageClient />
      </div>
    </main>
  );
}
