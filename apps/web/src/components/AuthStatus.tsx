"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { NavLink } from "@/components/NavLink";
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
      <NavLink
        href="/favoris"
        exact
        className="shrink-0 rounded-full px-2.5 py-1 font-medium text-neutral-600 hover:text-orange-600 dark:text-neutral-400 dark:hover:text-orange-400"
        activeClassName="bg-orange-600 text-white shadow-sm hover:text-white dark:bg-orange-500"
      >
        Favoris
      </NavLink>
      <NavLink
        href="/bibliotheque"
        exact
        className="shrink-0 rounded-full px-2.5 py-1 font-medium text-neutral-600 hover:text-orange-600 dark:text-neutral-400 dark:hover:text-orange-400"
        activeClassName="bg-orange-600 text-white shadow-sm hover:text-white dark:bg-orange-500"
      >
        Mes achats
      </NavLink>
      <NotificationBell />
      <NavLink
        href="/compte"
        exact
        className="max-w-24 truncate rounded-full px-2.5 py-1 text-neutral-500 hover:text-orange-600 sm:max-w-none dark:text-neutral-400 dark:hover:text-orange-400"
        activeClassName="bg-orange-600 text-white shadow-sm hover:text-white dark:bg-orange-500"
      >
        {user.name}
      </NavLink>
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
