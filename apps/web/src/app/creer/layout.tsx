"use client";

import Link from "next/link";
import { useAuthUser } from "@/lib/use-auth";

/** Shared auth gate for every /creer/* route — each sub-page assumes a logged-in creator. */
export default function CreatorLayout({ children }: { children: React.ReactNode }) {
  const user = useAuthUser();

  if (!user) {
    return (
      <GateCard subtitle="Connecte-toi ou crée un compte créateur pour commencer à être payé à chaque vue.">
        <Link href="/inscription-createur" className="btn-primary w-full">
          Crée un compte créateur
        </Link>
        <Link
          href="/connexion?next=/creer"
          className="text-sm font-medium text-orange-600 hover:underline dark:text-orange-400"
        >
          Déjà un compte ? Connecte-toi
        </Link>
      </GateCard>
    );
  }

  if (user.role !== "creator") {
    return (
      <GateCard subtitle="Cet espace est réservé aux comptes créateur (pièce d'identité requise).">
        <Link href="/inscription-createur" className="btn-primary w-full">
          Devenir créateur
        </Link>
      </GateCard>
    );
  }

  return children;
}

function GateCard({ subtitle, children }: { subtitle: string; children: React.ReactNode }) {
  return (
    <main className="mx-auto flex w-full max-w-sm flex-1 flex-col justify-center px-4 py-16">
      <div className="rounded-2xl border border-neutral-200 bg-white p-6 text-center shadow-sm dark:border-neutral-800 dark:bg-neutral-950 sm:p-8">
        <span className="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-gradient-to-br from-orange-500 to-accent-600 text-white">
          ▶
        </span>
        <h1 className="text-xl font-bold text-neutral-900 dark:text-neutral-50">
          Tes vidéos tournent déjà partout. Pourquoi tu ne gagnes rien ?
        </h1>
        <p className="mt-2 text-sm text-neutral-500 dark:text-neutral-400">{subtitle}</p>
        <div className="mt-6 flex flex-col items-center gap-3">{children}</div>
      </div>
    </main>
  );
}
