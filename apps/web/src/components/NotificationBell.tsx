"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { fetchNotifications } from "@/lib/api-client";
import { useAuthToken } from "@/lib/use-auth";

const POLL_INTERVAL_MS = 45_000;

export function NotificationBell() {
  const token = useAuthToken();
  const [unreadCount, setUnreadCount] = useState(0);

  useEffect(() => {
    if (!token) return;

    let cancelled = false;

    function load() {
      fetchNotifications()
        .then((response) => {
          if (!cancelled) setUnreadCount(response.unread_count);
        })
        .catch(() => undefined);
    }

    load();
    const interval = setInterval(load, POLL_INTERVAL_MS);
    return () => {
      cancelled = true;
      clearInterval(interval);
    };
  }, [token]);

  if (!token) return null;

  return (
    <Link
      href="/notifications"
      className="relative flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-neutral-600 transition hover:bg-orange-50 hover:text-orange-600 dark:text-neutral-400 dark:hover:bg-neutral-900 dark:hover:text-orange-400"
      aria-label={unreadCount > 0 ? `Notifications (${unreadCount} non lues)` : "Notifications"}
    >
      <BellIcon />
      {unreadCount > 0 && (
        <span className="absolute -top-0.5 -right-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-accent-600 px-1 text-[10px] font-bold text-white">
          {unreadCount > 9 ? "9+" : unreadCount}
        </span>
      )}
    </Link>
  );
}

function BellIcon() {
  return (
    <svg viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
      <path d="M6 8a6 6 0 1 1 12 0c0 4.5 1.5 6 1.5 6h-15S6 12.5 6 8Z" />
      <path d="M10.5 21a1.5 1.5 0 0 0 3 0" />
    </svg>
  );
}
