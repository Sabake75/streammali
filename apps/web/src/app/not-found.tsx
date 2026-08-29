import Link from "next/link";

export default function NotFound() {
  return (
    <main className="mx-auto flex w-full max-w-md flex-1 flex-col items-center justify-center px-4 py-16 text-center">
      <span className="flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-orange-500 to-accent-600 text-2xl text-white">
        ▶
      </span>
      <h1 className="mt-6 text-3xl font-bold text-neutral-900 dark:text-neutral-50">Page introuvable</h1>
      <p className="mt-2 text-neutral-500 dark:text-neutral-400">
        Cette page n&apos;existe pas ou n&apos;est plus disponible.
      </p>
      <Link href="/" className="btn-primary mt-6 px-5 py-2.5">
        Retour au catalogue
      </Link>
    </main>
  );
}
