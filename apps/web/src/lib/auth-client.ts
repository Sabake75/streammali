const TOKEN_KEY = "streammali_token";
const USER_KEY = "streammali_user";
const AUTH_CHANGE_EVENT = "streammali-auth-change";

export type StoredUser = {
  id: number;
  name: string;
  phone: string;
  role: string;
};

export function getToken(): string | null {
  if (typeof window === "undefined") return null;
  return localStorage.getItem(TOKEN_KEY);
}

export function getStoredUserRaw(): string | null {
  if (typeof window === "undefined") return null;
  return localStorage.getItem(USER_KEY);
}

export function getStoredUser(): StoredUser | null {
  const raw = getStoredUserRaw();
  return raw ? (JSON.parse(raw) as StoredUser) : null;
}

export function setSession(token: string, user: StoredUser): void {
  localStorage.setItem(TOKEN_KEY, token);
  localStorage.setItem(USER_KEY, JSON.stringify(user));
  window.dispatchEvent(new Event(AUTH_CHANGE_EVENT));
}

export function clearSession(): void {
  localStorage.removeItem(TOKEN_KEY);
  localStorage.removeItem(USER_KEY);
  window.dispatchEvent(new Event(AUTH_CHANGE_EVENT));
}

/** Subscribes to auth state changes, both from this tab and others. */
export function subscribeToAuthChanges(callback: () => void): () => void {
  window.addEventListener("storage", callback);
  window.addEventListener(AUTH_CHANGE_EVENT, callback);
  return () => {
    window.removeEventListener("storage", callback);
    window.removeEventListener(AUTH_CHANGE_EVENT, callback);
  };
}
