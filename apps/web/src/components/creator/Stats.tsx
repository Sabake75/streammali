"use client";

import { useEffect, useState } from "react";
import { fetchCreatorStats } from "@/lib/api-client";
import { formatPrice } from "@/lib/format";
import type { CreatorStats } from "@/lib/types";

export function Stats() {
  const [stats, setStats] = useState<CreatorStats | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    fetchCreatorStats()
      .then(setStats)
      .catch((err) => setError(err instanceof Error ? err.message : "Une erreur est survenue."));
  }, []);

  if (error) {
    return <p className="text-sm text-red-600 dark:text-red-400">{error}</p>;
  }

  if (!stats) {
    return <p className="text-neutral-500">Chargement…</p>;
  }

  const maxRevenue = Math.max(1, ...stats.timeseries.map((point) => point.revenue));

  return (
    <div className="rounded-xl border border-neutral-200 p-4 shadow-sm dark:border-neutral-800">
      <h2 className="flex items-center gap-2 font-semibold text-neutral-900 dark:text-neutral-50">
        <span className="h-4 w-1 rounded-full bg-orange-600" />
        Statistiques
      </h2>

      <div className="mt-3 grid grid-cols-3 gap-4 text-center">
        <Stat label="Vues" value={stats.totals.views} />
        <Stat label="Achats" value={stats.totals.purchases} />
        <Stat label="Revenus" value={formatPrice(stats.totals.revenue)} />
      </div>

      <h3 className="mt-6 text-sm font-medium text-neutral-600 dark:text-neutral-400">
        Revenus des 14 derniers jours
      </h3>
      {stats.timeseries.every((point) => point.revenue === 0) ? (
        <p className="mt-2 flex h-24 items-center justify-center text-sm text-neutral-400 dark:text-neutral-500">
          Aucune vente sur cette période.
        </p>
      ) : (
        <>
          <div className="mt-2 flex h-24 items-end gap-1">
            {stats.timeseries.map((point) => (
              <div
                key={point.date}
                title={`${point.date} : ${formatPrice(point.revenue)}`}
                className="flex-1 rounded-t bg-gradient-to-t from-orange-600 to-orange-400 dark:from-orange-500 dark:to-orange-300"
                style={{ height: `${Math.max(4, (point.revenue / maxRevenue) * 100)}%` }}
              />
            ))}
          </div>
          <div className="mt-1 flex justify-between text-xs text-neutral-500 dark:text-neutral-400">
            <span>{formatShortDate(stats.timeseries[0]?.date)}</span>
            <span>{formatShortDate(stats.timeseries[stats.timeseries.length - 1]?.date)}</span>
          </div>
        </>
      )}

      {stats.videos.length > 0 && (
        <div className="mt-6 overflow-x-auto">
          <h3 className="mb-2 text-sm font-medium text-neutral-600 dark:text-neutral-400">Par vidéo</h3>
          <table className="w-full text-left text-sm">
            <thead>
              <tr className="border-b border-neutral-200 text-neutral-500 dark:border-neutral-800 dark:text-neutral-400">
                <th className="py-1 pr-2 font-medium">Titre</th>
                <th className="py-1 pr-2 font-medium">Vues</th>
                <th className="py-1 pr-2 font-medium">Achats</th>
                <th className="py-1 font-medium">Revenus</th>
              </tr>
            </thead>
            <tbody>
              {stats.videos.map((video) => (
                <tr key={video.id} className="border-b border-neutral-100 last:border-0 dark:border-neutral-900">
                  <td className="py-1.5 pr-2">{video.title}</td>
                  <td className="py-1.5 pr-2">{video.views_count}</td>
                  <td className="py-1.5 pr-2">{video.purchases_count}</td>
                  <td className="py-1.5">{formatPrice(video.revenue)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}

function Stat({ label, value }: { label: string; value: string | number }) {
  return (
    <div>
      <p className="text-xl font-bold text-neutral-900 dark:text-neutral-50">{value}</p>
      <p className="text-xs text-neutral-500 dark:text-neutral-400">{label}</p>
    </div>
  );
}

function formatShortDate(date: string | undefined): string {
  if (!date) return "";
  const [, month, day] = date.split("-");
  return `${day}/${month}`;
}
