"use client";

import Link from "next/link";
import { useAuthUser } from "@/lib/use-auth";

/** Shared auth gate for every /creer/* route — each sub-page assumes a logged-in creator. */
export default function CreatorLayout({ children }: { children: React.ReactNode }) {
  const user = useAuthUser();

  if (!user) {
    return (
      <CenteredMessage>
        <Link href="/connexion?next=/creer" className="font-medium text-orange-600 hover:underline dark:text-orange-400">
          Connecte-toi
        </Link>{" "}
        ou{" "}
        <Link href="/inscription-createur" className="font-medium text-orange-600 hover:underline dark:text-orange-400">
          crée un compte créateur
        </Link>{" "}
        pour accéder à cet espace.
      </CenteredMessage>
    );
  }

  if (user.role !== "creator") {
    return (
      <CenteredMessage>
        Cet espace est réservé aux comptes créateur.{" "}
        <Link href="/inscription-createur" className="font-medium text-orange-600 hover:underline dark:text-orange-400">
          En créer un
        </Link>{" "}
        (pièce d&apos;identité requise).
      </CenteredMessage>
    );
  }

  return children;
}

function CenteredMessage({ children }: { children: React.ReactNode }) {
  return (
    <main className="mx-auto flex w-full max-w-md flex-1 flex-col items-center justify-center px-4 py-16 text-center text-neutral-600 dark:text-neutral-400">
      <p>{children}</p>
    </main>
  );
}
