"use client";

import Link from "next/link";
import { useAuthUser } from "@/lib/use-auth";

/** Auth gate for /notifications — same shape as /bibliotheque, any logged-in user. */
export default function NotificationsLayout({ children }: { children: React.ReactNode }) {
  const user = useAuthUser();

  if (!user) {
    return (
      <main className="mx-auto flex w-full max-w-md flex-1 flex-col items-center justify-center px-4 py-16 text-center text-neutral-600 dark:text-neutral-400">
        <p>
          <Link href="/connexion?next=/notifications" className="font-medium text-orange-600 hover:underline dark:text-orange-400">
            Connecte-toi
          </Link>{" "}
          pour voir tes notifications.
        </p>
      </main>
    );
  }

  return children;
}
