"use client";

import Link from "next/link";
import { Messaging } from "@/components/Messaging";
import { useAuthUser } from "@/lib/use-auth";

export function SupportPageClient() {
  const user = useAuthUser();

  if (!user) {
    return (
      <main className="mx-auto w-full max-w-2xl flex-1 px-4 py-8 sm:px-6 lg:px-8">
        <p className="text-neutral-500 dark:text-neutral-400">
          <Link href="/connexion" className="font-medium text-orange-600 hover:underline dark:text-orange-400">
            Connecte-toi
          </Link>{" "}
          pour contacter le support.
        </p>
      </main>
    );
  }

  return (
    <main className="mx-auto w-full max-w-2xl flex-1 px-4 py-8 sm:px-6 lg:px-8">
      <h1 className="flex items-center gap-2 text-3xl font-bold text-neutral-900 dark:text-neutral-50">
        <span className="h-7 w-2 rounded-full bg-orange-600" />
        Support
      </h1>
      <p className="mt-1 ml-4 text-neutral-500 dark:text-neutral-400">
        Un problème avec un paiement, une vidéo, ton compte ? Écris-nous ici.
      </p>
      <div className="mt-6">
        <Messaging />
      </div>
    </main>
  );
}
