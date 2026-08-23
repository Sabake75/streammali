"use client";

import { useCallback, useEffect, useState } from "react";
import { PhoneNumberField } from "@/components/PhoneNumberField";
import { fetchBalance, fetchMyPayouts, requestPayout } from "@/lib/api-client";
import { formatPrice } from "@/lib/format";
import type { CreatorBalance, Payout } from "@/lib/types";

export function BalanceAndPayouts() {
  const [balance, setBalance] = useState<CreatorBalance | null>(null);
  const [payouts, setPayouts] = useState<Payout[] | null>(null);
  const [amount, setAmount] = useState("");
  const [destination, setDestination] = useState("");
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const reload = useCallback(() => {
    fetchBalance().then(setBalance).catch(() => undefined);
    fetchMyPayouts()
      .then((response) => setPayouts(response.data))
      .catch(() => undefined);
  }, []);

  useEffect(() => {
    reload();
  }, [reload]);

  async function handleSubmit(event: React.FormEvent) {
    event.preventDefault();
    setSubmitting(true);
    setError(null);

    try {
      await requestPayout({ amount: Number(amount), destination_msisdn: destination });
      setAmount("");
      setDestination("");
      reload();
    } catch (err) {
      setError(err instanceof Error ? err.message : "Une erreur est survenue.");
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <div className="flex h-full flex-col rounded-xl border border-neutral-200 p-4 shadow-sm dark:border-neutral-800">
      <h2 className="flex items-center gap-2 font-semibold text-neutral-900 dark:text-neutral-50">
        <span className="h-4 w-1 rounded-full bg-orange-600" />
        Solde et retraits
      </h2>

      {balance ? (
        <p className="mt-2 text-2xl font-bold text-emerald-700 dark:text-emerald-400">
          {formatPrice(balance.available_balance)}
          <span className="ml-2 text-sm font-normal text-neutral-500 dark:text-neutral-400">
            disponible (retrait min. {formatPrice(balance.minimum_payout_amount)})
          </span>
        </p>
      ) : (
        <p className="mt-2 text-neutral-500">Chargement…</p>
      )}

      <form onSubmit={handleSubmit} className="mt-4 flex flex-wrap items-end gap-2">
        <div className="flex flex-col gap-1">
          <label htmlFor="payout-amount" className="text-sm text-neutral-600 dark:text-neutral-400">
            Montant (FCFA)
          </label>
          <input
            id="payout-amount"
            type="number"
            min={1}
            required
            value={amount}
            onChange={(event) => setAmount(event.target.value)}
            className="input-field w-32"
          />
        </div>
        <PhoneNumberField
          id="payout-destination"
          label="Numéro Mobile Money"
          value={destination}
          onChange={setDestination}
        />
        <button
          type="submit"
          disabled={submitting}
          className="btn-primary"
        >
          {submitting ? "Envoi…" : "Demander un retrait"}
        </button>
      </form>
      {error && <p className="mt-2 text-sm text-red-600 dark:text-red-400">{error}</p>}

      {payouts !== null && payouts.length > 0 && (
        <div className="mt-4 flex flex-col gap-2">
          <h3 className="text-sm font-medium text-neutral-600 dark:text-neutral-400">
            Historique des demandes
          </h3>
          {payouts.map((payout) => (
            <div
              key={payout.id}
              className="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-neutral-200 px-3 py-2 text-sm dark:border-neutral-800"
            >
              <span>{formatPrice(payout.amount)}</span>
              <span>{payout.destination_msisdn}</span>
              <span className="font-medium">{payout.status.label}</span>
              {payout.status.value === "rejected" && payout.rejection_reason && (
                <span className="w-full text-red-600 dark:text-red-400">{payout.rejection_reason}</span>
              )}
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
