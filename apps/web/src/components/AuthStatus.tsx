"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { logoutViewer } from "@/lib/api-client";
import { clearSession } from "@/lib/auth-client";
import { useAuthUser } from "@/lib/use-auth";

export function AuthStatus() {
  const router = useRouter();
  const user = useAuthUser();

  if (!user) {
    return (
      <Link href="/connexion" className="text-sm font-medium hover:underline">
        Connexion
      </Link>
    );
  }

  return (
    <div className="flex items-center gap-3 text-sm">
      <span className="text-neutral-500 dark:text-neutral-400">{user.name}</span>
      <button
        type="button"
        onClick={async () => {
          await logoutViewer();
          clearSession();
          router.push("/");
        }}
        className="font-medium hover:underline"
      >
        Déconnexion
      </button>
    </div>
  );
}
