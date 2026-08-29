"use client";

import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import { Suspense, useState } from "react";
import { FormField } from "@/components/FormField";
import { PhoneNumberField } from "@/components/PhoneNumberField";
import { PinCodeField } from "@/components/PinCodeField";
import { TermsModal } from "@/components/legal/TermsModal";
import { ViewerTermsContent } from "@/components/legal/ViewerTermsContent";
import { registerViewer } from "@/lib/api-client";
import { setSession } from "@/lib/auth-client";

function RegisterForm() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const next = searchParams.get("next") ?? "/";

  const [name, setName] = useState("");
  const [phone, setPhone] = useState("");
  const [password, setPassword] = useState("");
  const [termsAccepted, setTermsAccepted] = useState(false);
  const [termsModalOpen, setTermsModalOpen] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function handleSubmit(event: React.FormEvent) {
    event.preventDefault();
    setSubmitting(true);
    setError(null);

    try {
      const { token, user } = await registerViewer({ name, phone, password, terms_accepted: termsAccepted });
      setSession(token, user);
      router.push(next);
      router.refresh();
    } catch (err) {
      setError(err instanceof Error ? err.message : "Une erreur est survenue.");
      setSubmitting(false);
    }
  }

  return (
    <div className="rounded-2xl border border-neutral-200 bg-white p-6 shadow-sm dark:border-neutral-800 dark:bg-neutral-950 sm:p-8">
      <span className="mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-gradient-to-br from-orange-500 to-accent-600 text-white">
        ▶
      </span>
      <h1 className="text-2xl font-bold text-neutral-900 dark:text-neutral-50">Inscription</h1>
      <form onSubmit={handleSubmit} className="mt-6 flex flex-col gap-4">
        <FormField id="name" label="Nom" type="text" value={name} onChange={setName} />
        <PhoneNumberField id="phone" value={phone} onChange={setPhone} />
        <PinCodeField id="password" value={password} onChange={setPassword} />
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
              CGU
            </button>
          </span>
        </label>
        {error && <p className="text-sm text-red-600 dark:text-red-400">{error}</p>}
        <button type="submit" disabled={submitting} className="btn-primary">
          {submitting ? "Création…" : "Créer mon compte"}
        </button>
      </form>
      <p className="mt-4 text-sm text-neutral-500 dark:text-neutral-400">
        Déjà un compte ?{" "}
        <Link
          href={`/connexion?next=${encodeURIComponent(next)}`}
          className="font-medium text-orange-600 hover:underline dark:text-orange-400"
        >
          Se connecter
        </Link>
      </p>
      <p className="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
        Tu es créateur ?{" "}
        <Link href="/inscription-createur" className="font-medium text-orange-600 hover:underline dark:text-orange-400">
          Inscription créateur
        </Link>
      </p>

      <TermsModal
        open={termsModalOpen}
        title="Conditions générales d'utilisation — Spectateur"
        onClose={() => setTermsModalOpen(false)}
        onAccept={() => {
          setTermsAccepted(true);
          setTermsModalOpen(false);
        }}
      >
        <ViewerTermsContent />
      </TermsModal>
    </div>
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
