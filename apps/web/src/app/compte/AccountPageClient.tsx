"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { useState } from "react";
import { deleteAccount, exportAccountData } from "@/lib/api-client";
import { clearSession } from "@/lib/auth-client";
import { useAuthUser } from "@/lib/use-auth";

export function AccountPageClient() {
  const router = useRouter();
  const user = useAuthUser();
  const [exportError, setExportError] = useState<string | null>(null);
  const [exporting, setExporting] = useState(false);
  const [confirming, setConfirming] = useState(false);
  const [deleteError, setDeleteError] = useState<string | null>(null);
  const [deleting, setDeleting] = useState(false);

  if (!user) {
    return (
      <main className="mx-auto w-full max-w-2xl flex-1 px-4 py-8 sm:px-6 lg:px-8">
        <p className="text-neutral-500 dark:text-neutral-400">
          <Link href="/connexion" className="font-medium text-orange-600 hover:underline dark:text-orange-400">
            Connecte-toi
          </Link>{" "}
          pour accéder à ton compte.
        </p>
      </main>
    );
  }

  async function handleExport() {
    setExporting(true);
    setExportError(null);
    try {
      await exportAccountData();
    } catch (err) {
      setExportError(err instanceof Error ? err.message : "Une erreur est survenue.");
    } finally {
      setExporting(false);
    }
  }

  async function handleDelete() {
    setDeleting(true);
    setDeleteError(null);
    try {
      await deleteAccount();
      clearSession();
      router.push("/");
    } catch (err) {
      setDeleteError(err instanceof Error ? err.message : "Une erreur est survenue.");
      setDeleting(false);
    }
  }

  return (
    <main className="mx-auto w-full max-w-2xl flex-1 px-4 py-8 sm:px-6 lg:px-8">
      <h1 className="flex items-center gap-2 text-3xl font-bold text-neutral-900 dark:text-neutral-50">
        <span className="h-7 w-2 rounded-full bg-orange-600" />
        Mon compte
      </h1>
      <p className="mt-1 ml-4 text-neutral-500 dark:text-neutral-400">
        {user.name} · {user.phone}
      </p>

      <section className="mt-8 rounded-xl border border-neutral-200 p-5 dark:border-neutral-800">
        <h2 className="font-semibold text-neutral-900 dark:text-neutral-50">Télécharger mes données</h2>
        <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
          Reçois un fichier JSON avec toutes les données que StreamMali détient sur toi (profil, achats, favoris, avis
          {user.role === "creator" && ", vidéos, revenus, retraits, messages"}).
        </p>
        {exportError && <p className="mt-2 text-sm text-red-600 dark:text-red-400">{exportError}</p>}
        <button
          type="button"
          onClick={handleExport}
          disabled={exporting}
          className="mt-3 rounded-full bg-neutral-900 px-4 py-2 text-sm font-medium text-white hover:bg-neutral-800 disabled:opacity-60 dark:bg-neutral-100 dark:text-neutral-900 dark:hover:bg-neutral-200"
        >
          {exporting ? "Préparation…" : "Télécharger mes données"}
        </button>
      </section>

      <section className="mt-6 rounded-xl border border-red-200 p-5 dark:border-red-900/50">
        <h2 className="font-semibold text-red-700 dark:text-red-400">Supprimer mon compte</h2>
        <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
          Ton profil, ton numéro et ta pièce d&apos;identité sont supprimés définitivement et tu es déconnecté(e)
          partout. Action irréversible.
          {user.role === "creator" && " Retire d'abord ton solde disponible si tu en as un — sinon la suppression est refusée."}
        </p>
        {deleteError && <p className="mt-2 text-sm text-red-600 dark:text-red-400">{deleteError}</p>}

        {!confirming ? (
          <button
            type="button"
            onClick={() => setConfirming(true)}
            className="mt-3 rounded-full border border-red-300 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50 dark:border-red-900/50 dark:text-red-400 dark:hover:bg-red-950/40"
          >
            Supprimer mon compte
          </button>
        ) : (
          <div className="mt-3 flex flex-wrap items-center gap-3">
            <p className="text-sm font-medium text-red-700 dark:text-red-400">Confirmer la suppression ?</p>
            <button
              type="button"
              onClick={handleDelete}
              disabled={deleting}
              className="rounded-full bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-60"
            >
              {deleting ? "Suppression…" : "Oui, supprimer définitivement"}
            </button>
            <button
              type="button"
              onClick={() => setConfirming(false)}
              disabled={deleting}
              className="rounded-full px-4 py-2 text-sm font-medium text-neutral-600 hover:bg-neutral-100 dark:text-neutral-400 dark:hover:bg-neutral-900"
            >
              Annuler
            </button>
          </div>
        )}
      </section>
    </main>
  );
}
