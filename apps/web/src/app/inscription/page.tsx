"use client";

import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import { Suspense, useState } from "react";
import { FormField } from "@/components/FormField";
import { registerViewer } from "@/lib/api-client";
import { setSession } from "@/lib/auth-client";

function RegisterForm() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const next = searchParams.get("next") ?? "/";

  const [name, setName] = useState("");
  const [phone, setPhone] = useState("");
  const [password, setPassword] = useState("");
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function handleSubmit(event: React.FormEvent) {
    event.preventDefault();
    setSubmitting(true);
    setError(null);

    try {
      const { token, user } = await registerViewer({ name, phone, password });
      setSession(token, user);
      router.push(next);
      router.refresh();
    } catch (err) {
      setError(err instanceof Error ? err.message : "Une erreur est survenue.");
      setSubmitting(false);
    }
  }

  return (
    <>
      <h1 className="text-2xl font-bold text-neutral-900 dark:text-neutral-50">Inscription</h1>
      <form onSubmit={handleSubmit} className="mt-6 flex flex-col gap-4">
        <FormField id="name" label="Nom" type="text" value={name} onChange={setName} />
        <FormField id="phone" label="Téléphone" type="tel" value={phone} onChange={setPhone} />
        <FormField
          id="password"
          label="Mot de passe (8 caractères min.)"
          type="password"
          value={password}
          onChange={setPassword}
        />
        {error && <p className="text-sm text-red-600 dark:text-red-400">{error}</p>}
        <button
          type="submit"
          disabled={submitting}
          className="rounded bg-neutral-900 px-4 py-2 font-medium text-white hover:bg-neutral-700 disabled:opacity-60 dark:bg-neutral-50 dark:text-neutral-900 dark:hover:bg-neutral-300"
        >
          {submitting ? "Création…" : "Créer mon compte"}
        </button>
      </form>
      <p className="mt-4 text-sm text-neutral-500 dark:text-neutral-400">
        Déjà un compte ?{" "}
        <Link href={`/connexion?next=${encodeURIComponent(next)}`} className="underline">
          Se connecter
        </Link>
      </p>
    </>
  );
}

export default function RegisterPage() {
  return (
    <main className="mx-auto flex w-full max-w-sm flex-1 flex-col justify-center px-4 py-16">
      <Suspense>
        <RegisterForm />
      </Suspense>
    </main>
  );
}
