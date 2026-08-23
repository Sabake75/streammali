"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { useState } from "react";
import { FormField } from "@/components/FormField";
import { PhoneNumberField } from "@/components/PhoneNumberField";
import { PinCodeField } from "@/components/PinCodeField";
import { CreatorTermsContent } from "@/components/legal/CreatorTermsContent";
import { TermsModal } from "@/components/legal/TermsModal";
import { registerCreator } from "@/lib/api-client";
import { setSession } from "@/lib/auth-client";

export default function RegisterCreatorPage() {
  const router = useRouter();

  const [name, setName] = useState("");
  const [phone, setPhone] = useState("");
  const [password, setPassword] = useState("");
  const [identityDocument, setIdentityDocument] = useState<File | null>(null);
  const [termsAccepted, setTermsAccepted] = useState(false);
  const [termsModalOpen, setTermsModalOpen] = useState(false);
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
      const { token, user } = await registerCreator({
        name,
        phone,
        password,
        identityDocument,
        terms_accepted: termsAccepted,
      });
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
      <div className="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-950 sm:p-8">
        <span className="mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-gradient-to-br from-orange-500 to-emerald-600 text-white">
          ▶
        </span>
        <h1 className="text-2xl font-bold text-neutral-900 dark:text-neutral-50">
          Inscription créateur
        </h1>
        <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
          Une pièce d&apos;identité est requise pour publier des vidéos sur StreamMali.
        </p>
        <form onSubmit={handleSubmit} className="mt-6 flex flex-col gap-4">
          <FormField id="name" label="Nom" type="text" value={name} onChange={setName} />
          <PhoneNumberField id="phone" value={phone} onChange={setPhone} />
          <PinCodeField id="password" value={password} onChange={setPassword} />
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
              className="block w-full text-sm text-neutral-500 file:mr-3 file:rounded-lg file:border-0 file:bg-orange-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-orange-700 hover:file:bg-orange-100 dark:text-neutral-400 dark:file:bg-orange-950/50 dark:file:text-orange-300"
            />
          </div>
          <label className="flex items-start gap-2 text-sm text-neutral-600 dark:text-neutral-400">
            <input
              type="checkbox"
              required
              checked={termsAccepted}
              onChange={(event) => setTermsAccepted(event.target.checked)}
              className="mt-0.5 h-4 w-4 shrink-0 accent-orange-600"
            />
            <span>
              J&apos;ai lu et j&apos;accepte les{" "}
              <button
                type="button"
                onClick={() => setTermsModalOpen(true)}
                className="font-medium text-orange-600 underline hover:no-underline dark:text-orange-400"
              >
                CGU créateur
              </button>{" "}
              (dont la répartition des revenus : 75 % pour moi, 25 % pour StreamMali)
            </span>
          </label>
          {error && <p className="text-sm text-red-600 dark:text-red-400">{error}</p>}
          <button type="submit" disabled={submitting} className="btn-primary">
            {submitting ? "Création…" : "Créer mon compte créateur"}
          </button>
        </form>
        <p className="mt-4 text-sm text-neutral-500 dark:text-neutral-400">
          Tu es plutôt spectateur ?{" "}
          <Link href="/inscription" className="font-medium text-orange-600 hover:underline dark:text-orange-400">
            Inscription standard
          </Link>
        </p>
        <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
          Déjà un compte ?{" "}
          <Link href="/connexion?next=/creer" className="font-medium text-orange-600 hover:underline dark:text-orange-400">
            Se connecter
          </Link>
        </p>

        <TermsModal
          open={termsModalOpen}
          title="Conditions générales d'utilisation — Créateur"
          onClose={() => setTermsModalOpen(false)}
          onAccept={() => {
            setTermsAccepted(true);
            setTermsModalOpen(false);
          }}
        >
          <CreatorTermsContent />
        </TermsModal>
      </div>
    </main>
  );
}
