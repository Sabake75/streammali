"use client";

import { useCallback, useEffect, useState } from "react";
import { ErrorRetryView } from "@/components/ErrorRetryView";
import { fetchMyTransactions } from "@/lib/api-client";
import { formatDate, formatPrice, statusBadgeClass } from "@/lib/format";
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
            className="flex items-start justify-between gap-3 rounded-lg border border-neutral-200 px-4 py-3 text-sm dark:border-neutral-800"
          >
            <div className="min-w-0">
              <p className="truncate font-medium text-neutral-900 dark:text-neutral-50">
                {transaction.video_title ?? "Vidéo supprimée"}
              </p>
              <p className="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">
                {formatDate(transaction.created_at)} · {formatPrice(transaction.gross_amount)} brut
              </p>
            </div>
            <div className="flex shrink-0 flex-col items-end gap-1.5">
              <span className="font-semibold text-accent-700 dark:text-accent-400">
                {formatPrice(transaction.net_amount)}
              </span>
              {transaction.status && (
                <span className={`w-fit rounded-full px-2.5 py-0.5 text-xs font-semibold ${statusBadgeClass(transaction.status.value)}`}>
                  {transaction.status.label}
                </span>
              )}
            </div>
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
