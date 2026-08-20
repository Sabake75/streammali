"use client";

import { useSyncExternalStore } from "react";
import {
  getStoredUserRaw,
  getToken,
  subscribeToAuthChanges,
  type StoredUser,
} from "@/lib/auth-client";

function getServerSnapshot() {
  return null;
}

export function useAuthToken(): string | null {
  return useSyncExternalStore(subscribeToAuthChanges, getToken, getServerSnapshot);
}

let cachedRaw: string | null = null;
let cachedUser: StoredUser | null = null;

function getUserSnapshot(): StoredUser | null {
  const raw = getStoredUserRaw();
  if (raw !== cachedRaw) {
    cachedRaw = raw;
    cachedUser = raw ? (JSON.parse(raw) as StoredUser) : null;
  }
  return cachedUser;
}

export function useAuthUser(): StoredUser | null {
  return useSyncExternalStore(subscribeToAuthChanges, getUserSnapshot, getServerSnapshot);
}
