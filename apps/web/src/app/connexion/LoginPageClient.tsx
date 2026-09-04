"use client";

import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import { Suspense, useState } from "react";
import { PhoneNumberField } from "@/components/PhoneNumberField";
import { PinCodeField } from "@/components/PinCodeField";
import { loginViewer } from "@/lib/api-client";
import { setSession } from "@/lib/auth-client";
import { safeNextPath } from "@/lib/safe-next-path";

function LoginForm() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const next = safeNextPath(searchParams.get("next"));

  const [phone, setPhone] = useState("");
  const [password, setPassword] = useState("");
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function handleSubmit(event: React.FormEvent) {
    event.preventDefault();
    setSubmitting(true);
    setError(null);

    try {
      const { token, user } = await loginViewer({ phone, password });
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
      <h1 className="text-2xl font-bold text-neutral-900 dark:text-neutral-50">Connexion</h1>
      <form onSubmit={handleSubmit} className="mt-6 flex flex-col gap-4">
        <PhoneNumberField id="phone" value={phone} onChange={setPhone} />
        <PinCodeField id="password" value={password} onChange={setPassword} />
        {error && <p className="text-sm text-red-600 dark:text-red-400">{error}</p>}
        <button type="submit" disabled={submitting} className="btn-primary">
          {submitting ? "Connexion…" : "Se connecter"}
        </button>
      </form>
      <p className="mt-4 text-sm text-neutral-500 dark:text-neutral-400">
        Pas de compte ?{" "}
        <Link
          href={`/inscription?next=${encodeURIComponent(next)}`}
          className="font-medium text-orange-600 hover:underline dark:text-orange-400"
        >
          S&apos;inscrire
        </Link>
      </p>
    </div>
  );
}

export function LoginPageClient() {
  return (
    <main className="mx-auto flex w-full max-w-sm flex-1 flex-col justify-center px-4 py-16">
      <Suspense>
        <LoginForm />
      </Suspense>
    </main>
  );
}
