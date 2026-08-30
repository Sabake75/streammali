"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { NotificationBell } from "@/components/NotificationBell";
import { logoutViewer } from "@/lib/api-client";
import { clearSession } from "@/lib/auth-client";
import { useAuthUser } from "@/lib/use-auth";

export function AuthStatus() {
  const router = useRouter();
  const user = useAuthUser();

  if (!user) {
    return (
      <Link href="/connexion" className="text-sm font-medium text-orange-600 hover:underline dark:text-orange-400">
        Connexion
      </Link>
    );
  }

  return (
    <div className="flex items-center gap-3 text-sm">
      <Link
        href="/favoris"
        className="shrink-0 font-medium text-neutral-600 hover:text-orange-600 dark:text-neutral-400 dark:hover:text-orange-400"
      >
        Favoris
      </Link>
      <Link
        href="/bibliotheque"
        className="shrink-0 font-medium text-neutral-600 hover:text-orange-600 dark:text-neutral-400 dark:hover:text-orange-400"
      >
        Mes achats
      </Link>
      <NotificationBell />
      <span className="max-w-24 truncate text-neutral-500 sm:max-w-none dark:text-neutral-400">{user.name}</span>
      <button
        type="button"
        onClick={async () => {
          await logoutViewer();
          clearSession();
          router.push("/");
        }}
        className="shrink-0 font-medium text-neutral-600 hover:text-orange-600 dark:text-neutral-400 dark:hover:text-orange-400"
      >
        Déconnexion
      </button>
    </div>
  );
}
