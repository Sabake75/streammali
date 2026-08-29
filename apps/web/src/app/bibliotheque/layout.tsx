"use client";

import Link from "next/link";
import { useAuthUser } from "@/lib/use-auth";

/** Auth gate for /bibliotheque — any logged-in user (viewer or creator) can have purchases. */
export default function LibraryLayout({ children }: { children: React.ReactNode }) {
  const user = useAuthUser();

  if (!user) {
    return (
      <main className="mx-auto flex w-full max-w-md flex-1 flex-col items-center justify-center px-4 py-16 text-center text-neutral-600 dark:text-neutral-400">
        <p>
          <Link href="/connexion?next=/bibliotheque" className="font-medium text-orange-600 hover:underline dark:text-orange-400">
            Connecte-toi
          </Link>{" "}
          pour retrouver les vidéos que tu as achetées.
        </p>
      </main>
    );
  }

  return children;
}
