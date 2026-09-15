"use client";

import { useCallback, useEffect, useState } from "react";
import { ErrorRetryView } from "@/components/ErrorRetryView";
import { fetchMyTransactions } from "@/lib/api-client";
import { formatPrice } from "@/lib/format";
import type { Transaction } from "@/lib/types";

/**
 * One row per vente (LedgerEntry côté API), distinct de "Historique des
 * demandes" dans BalanceAndPayouts (qui liste les demandes de retrait, pas
 * les ventes individuelles).
 */
export function TransactionHistory() {
  const [page, setPage] = useState(1);
  const [transactions, setTransactions] = useState<Transaction[] | null>(null);
  const [lastPage, setLastPage] = useState(1);
  const [loadError, setLoadError] = useState(false);

  const reload = useCallback(() => {
    fetchMyTransactions(page)
      .then((response) => {
        setTransactions(response.data);
        setLastPage(response.last_page);
        setLoadError(false);
      })
      .catch(() => setLoadError(true));
  }, [page]);

  useEffect(() => {
    reload();
  }, [reload]);

  if (loadError && transactions === null) {
    return <ErrorRetryView onRetry={reload} />;
  }

  if (transactions !== null && transactions.length === 0 && page === 1) {
    return null;
  }

  return (
    <div className="mt-6 flex flex-col gap-2">
      <h3 className="text-sm font-medium text-neutral-600 dark:text-neutral-400">Historique des transactions</h3>
      {transactions === null ? (
        <p className="text-neutral-500">Chargement…</p>
      ) : (
        transactions.map((transaction) => (
          <div
            key={transaction.id}
            className="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-neutral-200 px-3 py-2 text-sm dark:border-neutral-800"
          >
            <span className="font-medium">{transaction.video_title ?? "Vidéo supprimée"}</span>
            <span className="text-neutral-500 dark:text-neutral-400">
              {new Date(transaction.created_at).toLocaleDateString("fr-FR")}
            </span>
            <span>{formatPrice(transaction.gross_amount)} brut</span>
            <span className="font-medium text-accent-700 dark:text-accent-400">
              {formatPrice(transaction.net_amount)} net
            </span>
            {transaction.status && <span>{transaction.status.label}</span>}
          </div>
        ))
      )}
      {lastPage > 1 && (
        <div className="mt-2 flex items-center justify-center gap-3 text-sm">
          <button
            type="button"
            disabled={page <= 1}
            onClick={() => setPage((current) => current - 1)}
            className="text-orange-600 disabled:opacity-40 dark:text-orange-400"
          >
            Précédent
          </button>
          <span className="text-neutral-500 dark:text-neutral-400">
            Page {page} / {lastPage}
          </span>
          <button
            type="button"
            disabled={page >= lastPage}
            onClick={() => setPage((current) => current + 1)}
            className="text-orange-600 disabled:opacity-40 dark:text-orange-400"
          >
            Suivant
          </button>
        </div>
      )}
    </div>
  );
}
