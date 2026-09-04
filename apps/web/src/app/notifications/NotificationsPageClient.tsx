"use client";

import Link from "next/link";
import { useCallback, useEffect, useState } from "react";
import { ErrorRetryView } from "@/components/ErrorRetryView";
import { fetchNotifications, markAllNotificationsRead, markNotificationRead } from "@/lib/api-client";
import { formatDate } from "@/lib/format";
import type { AppNotification } from "@/lib/types";

export function NotificationsPageClient() {
  const [notifications, setNotifications] = useState<AppNotification[] | null>(null);
  const [loadError, setLoadError] = useState(false);

  const reload = useCallback(() => {
    fetchNotifications()
      .then((response) => {
        setNotifications(response.data);
        setLoadError(false);
      })
      .catch(() => setLoadError(true));
  }, []);

  useEffect(() => {
    reload();
  }, [reload]);

  async function handleRead(id: string) {
    setNotifications((current) => current?.map((n) => (n.id === id ? { ...n, read: true } : n)) ?? current);
    await markNotificationRead(id).catch(() => undefined);
  }

  async function handleReadAll() {
    setNotifications((current) => current?.map((n) => ({ ...n, read: true })) ?? current);
    await markAllNotificationsRead().catch(() => undefined);
  }

  const hasUnread = notifications?.some((n) => !n.read) ?? false;

  return (
    <main className="mx-auto w-full max-w-2xl flex-1 px-4 py-8 sm:px-6 lg:px-8">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <h1 className="flex items-center gap-2 text-3xl font-bold text-neutral-900 dark:text-neutral-50">
          <span className="h-7 w-2 rounded-full bg-orange-600" />
          Notifications
        </h1>
        {hasUnread && (
          <button type="button" onClick={handleReadAll} className="btn-secondary px-3 py-1.5 text-sm">
            Tout marquer comme lu
          </button>
        )}
      </div>

      {loadError && <ErrorRetryView onRetry={reload} />}
      {notifications === null && !loadError && <p className="mt-6 text-neutral-500">Chargement…</p>}

      {notifications?.length === 0 && (
        <div className="mt-6 flex flex-col items-center gap-3 rounded-xl border border-dashed border-neutral-300 py-14 text-center dark:border-neutral-700">
          <span className="flex h-12 w-12 items-center justify-center rounded-full bg-neutral-100 text-neutral-400 dark:bg-neutral-900 dark:text-neutral-600">
            <BellIcon />
          </span>
          <p className="text-neutral-500 dark:text-neutral-400">Aucune notification pour l&apos;instant.</p>
        </div>
      )}

      {notifications && notifications.length > 0 && (
        <ul className="mt-6 flex flex-col gap-2">
          {notifications.map((notification) => (
            <li key={notification.id}>
              <NotificationRow notification={notification} onRead={handleRead} />
            </li>
          ))}
        </ul>
      )}
    </main>
  );
}

function NotificationRow({
  notification,
  onRead,
}: {
  notification: AppNotification;
  onRead: (id: string) => void;
}) {
  const { data } = notification;
  const href = data.type === "video_status_changed" ? `/videos/${data.video_id}` : "/creer/messagerie";

  return (
    <Link
      href={href}
      onClick={() => {
        if (!notification.read) onRead(notification.id);
      }}
      className={`flex flex-col gap-1 rounded-xl border px-4 py-3 transition ${
        notification.read
          ? "border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-950"
          : "border-orange-200 bg-orange-50/60 dark:border-orange-900 dark:bg-orange-950/20"
      }`}
    >
      <div className="flex items-start justify-between gap-3">
        <p className="text-sm text-neutral-800 dark:text-neutral-200">
          <NotificationText data={data} />
        </p>
        {!notification.read && <span className="mt-1 h-2 w-2 shrink-0 rounded-full bg-orange-600" />}
      </div>
      <span className="text-xs text-neutral-400 dark:text-neutral-500">{formatDate(notification.created_at)}</span>
    </Link>
  );
}

function NotificationText({ data }: { data: AppNotification["data"] }) {
  if (data.type === "video_status_changed") {
    return data.status === "approved" ? (
      <>
        Ta vidéo <strong>« {data.video_title} »</strong> a été validée et est en ligne.
      </>
    ) : (
      <>
        Ta vidéo <strong>« {data.video_title} »</strong> a été refusée
        {data.rejection_reason ? <> : {data.rejection_reason}</> : "."}
      </>
    );
  }

  return (
    <>
      Nouveau message de la modération : <span className="italic">« {data.excerpt} »</span>
    </>
  );
}

function BellIcon() {
  return (
    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
      <path d="M6 8a6 6 0 1 1 12 0c0 4.5 1.5 6 1.5 6h-15S6 12.5 6 8Z" />
      <path d="M10.5 21a1.5 1.5 0 0 0 3 0" />
    </svg>
  );
}
