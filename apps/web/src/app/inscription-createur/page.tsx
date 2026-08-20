"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { useState } from "react";
import { FormField } from "@/components/FormField";
import { PhoneNumberField } from "@/components/PhoneNumberField";
import { registerCreator } from "@/lib/api-client";
import { setSession } from "@/lib/auth-client";

export default function RegisterCreatorPage() {
  const router = useRouter();

  const [name, setName] = useState("");
  const [phone, setPhone] = useState("");
  const [password, setPassword] = useState("");
  const [identityDocument, setIdentityDocument] = useState<File | null>(null);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function handleSubmit(event: React.FormEvent) {
    event.preventDefault();
    if (!identityDocument) {
      setError("La pièce d'identité est requise.");
      return;
    }

    setSubmitting(true);
    setError(null);

    try {
      const { token, user } = await registerCreator({ name, phone, password, identityDocument });
      setSession(token, user);
      router.push("/creer");
      router.refresh();
    } catch (err) {
      setError(err instanceof Error ? err.message : "Une erreur est survenue.");
      setSubmitting(false);
    }
  }

  return (
    <main className="mx-auto flex w-full max-w-sm flex-1 flex-col justify-center px-4 py-16">
      <h1 className="text-2xl font-bold text-neutral-900 dark:text-neutral-50">
        Inscription créateur
      </h1>
      <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
        Une pièce d&apos;identité est requise pour publier des vidéos sur StreamMali.
      </p>
      <form onSubmit={handleSubmit} className="mt-6 flex flex-col gap-4">
        <FormField id="name" label="Nom" type="text" value={name} onChange={setName} />
        <PhoneNumberField id="phone" value={phone} onChange={setPhone} />
        <FormField
          id="password"
          label="Mot de passe (8 caractères min.)"
          type="password"
          value={password}
          onChange={setPassword}
        />
        <div className="flex flex-col gap-1">
          <label htmlFor="identity_document" className="text-sm text-neutral-600 dark:text-neutral-400">
            Pièce d&apos;identité (JPG, PNG ou PDF)
          </label>
          <input
            id="identity_document"
            type="file"
            required
            accept="image/jpeg,image/png,application/pdf"
            onChange={(event) => setIdentityDocument(event.target.files?.[0] ?? null)}
            className="rounded border border-neutral-300 bg-white px-3 py-2 text-sm dark:border-neutral-700 dark:bg-neutral-900"
          />
        </div>
        {error && <p className="text-sm text-red-600 dark:text-red-400">{error}</p>}
        <button
          type="submit"
          disabled={submitting}
          className="rounded bg-neutral-900 px-4 py-2 font-medium text-white hover:bg-neutral-700 disabled:opacity-60 dark:bg-neutral-50 dark:text-neutral-900 dark:hover:bg-neutral-300"
        >
          {submitting ? "Création…" : "Créer mon compte créateur"}
        </button>
      </form>
      <p className="mt-4 text-sm text-neutral-500 dark:text-neutral-400">
        Tu es plutôt spectateur ?{" "}
        <Link href="/inscription" className="underline">
          Inscription standard
        </Link>
      </p>
      <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
        Déjà un compte ?{" "}
        <Link href="/connexion?next=/creer" className="underline">
          Se connecter
        </Link>
      </p>
    </main>
  );
}
