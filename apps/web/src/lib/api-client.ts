import { clearSession, getToken, type StoredUser } from "@/lib/auth-client";
import type {
  CreatorBalance,
  CreatorStats,
  CreatorVideo,
  Message,
  NotificationListResponse,
  PaginatedResponse,
  Payout,
  PayoutListResponse,
  Review,
  VideoCategory,
  VideoCategoryValue,
  VideoSummary,
} from "@/lib/types";

const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api";

type AuthResponse = {
  user: StoredUser;
  token: string;
};

export async function registerViewer(input: {
  name: string;
  phone: string;
  password: string;
  terms_accepted: boolean;
}): Promise<AuthResponse> {
  return postJson("/register", input);
}

export async function registerCreator(input: {
  name: string;
  phone: string;
  password: string;
  identityDocument: File;
  terms_accepted: boolean;
}): Promise<AuthResponse> {
  const formData = new FormData();
  formData.set("name", input.name);
  formData.set("phone", input.phone);
  formData.set("password", input.password);
  formData.set("identity_document", input.identityDocument);
  formData.set("terms_accepted", input.terms_accepted ? "1" : "0");

  const response = await fetch(`${API_BASE_URL}/register/creator`, {
    method: "POST",
    body: formData,
  });

  if (!response.ok) {
    throw new Error(await extractErrorMessage(response));
  }

  return response.json();
}

/**
 * Turns the signed-in viewer's own account into a creator account —
 * same phone/id/purchase history, not a second disconnected account
 * (registerCreator above always creates a brand-new user, which used to
 * be the only option even for an already-logged-in viewer and just
 * failed on the phone's `unique` constraint).
 */
export async function upgradeToCreator(input: {
  identityDocument: File;
  terms_accepted: boolean;
}): Promise<{ user: StoredUser }> {
  const token = getToken();
  if (!token) throw new Error("Vous devez être connecté.");

  const formData = new FormData();
  formData.set("identity_document", input.identityDocument);
  formData.set("terms_accepted", input.terms_accepted ? "1" : "0");

  const response = await fetch(`${API_BASE_URL}/creator/upgrade`, {
    method: "POST",
    headers: { Authorization: `Bearer ${token}` },
    body: formData,
  });

  if (!response.ok) {
    throw new Error(await extractErrorMessage(response));
  }

  return response.json();
}

export async function loginViewer(input: { phone: string; password: string }): Promise<AuthResponse> {
  return postJson("/login", input);
}

export async function logoutViewer(): Promise<void> {
  const token = getToken();
  if (!token) return;

  await fetch(`${API_BASE_URL}/logout`, {
    method: "POST",
    headers: { Authorization: `Bearer ${token}` },
  }).catch(() => undefined);
}

/**
 * Records a real page view. Deliberately not part of fetchVideo (@/lib/api)
 * — that fetch is cached (`next: { revalidate: 60 }`), so a side effect
 * there would silently undercount every view served from cache. Called from
 * a client component on mount instead, uncached, best-effort (never blocks
 * or breaks the page if it fails).
 */
export async function recordVideoView(videoId: number): Promise<void> {
  await fetch(`${API_BASE_URL}/videos/${videoId}/view`, { method: "POST" }).catch(() => undefined);
}

export async function purchaseVideo(
  videoId: number,
  payerMsisdn: string,
): Promise<{ payment: { id: number; order_reference: string; status: string; amount: number }; payment_url: string }> {
  return postJson(`/videos/${videoId}/purchase`, { payer_msisdn: payerMsisdn }, { authenticated: true });
}

export async function fetchMyVideos(): Promise<PaginatedResponse<CreatorVideo>> {
  return getJson("/creator/videos");
}

export async function fetchMyPurchases(): Promise<PaginatedResponse<VideoSummary>> {
  return getJson("/purchases");
}

export async function fetchMyFavorites(): Promise<PaginatedResponse<VideoSummary>> {
  return getJson("/favorites");
}

/**
 * Authenticated fetch of a single video — unlike the SSR helper in
 * lib/api.ts (no auth header, so `purchased` always reads false there),
 * this is for the /paiement/succes polling loop, which needs the real
 * per-user `purchased` flag.
 */
export async function fetchVideoStatus(videoId: number): Promise<VideoSummary> {
  const { data } = await getJson<{ data: VideoSummary }>(`/videos/${videoId}`);
  return data;
}

export async function favoriteVideo(videoId: number): Promise<{ favorited: boolean }> {
  return postJson(`/videos/${videoId}/favorite`, {}, { authenticated: true });
}

export async function fetchRecommendedVideos(): Promise<VideoSummary[]> {
  const { data } = await getJson<{ data: VideoSummary[] }>("/videos/recommended");
  return data;
}

export async function fetchCategories(): Promise<VideoCategory[]> {
  const response = await fetch(`${API_BASE_URL}/categories`);

  if (!response.ok) {
    throw new Error(await extractErrorMessage(response));
  }

  const json: { data: VideoCategory[] } = await response.json();
  return json.data;
}

export async function createVideo(input: {
  title: string;
  description?: string;
  category: VideoCategoryValue;
  price?: number;
  duration_seconds?: number;
}): Promise<CreatorVideo> {
  const { data } = await postJson<{ data: CreatorVideo }>("/creator/videos", input, { authenticated: true });
  return data;
}

export async function createVideoUploadUrl(videoId: number, fileSize: number): Promise<{ upload_url: string }> {
  return postJson(`/creator/videos/${videoId}/source`, { file_size: fileSize }, { authenticated: true });
}

const UPLOAD_RETRY_DELAYS_MS = [0, 1000, 3000, 5000];
const TUS_RESUMABLE_VERSION = "1.0.0";
// Cloudflare's recommended TUS chunk size — also a valid one (a multiple of
// 256 KiB, required for every chunk but the file's last).
const TUS_CHUNK_SIZE = 50 * 1024 * 1024;

/**
 * Cloudflare Stream's one-time upload URL speaks the TUS resumable-upload
 * protocol: the file goes up in chunks via PATCH, each carrying the byte
 * offset it starts at — no 200MB cap like the old single-POST "Basic"
 * upload, and no need for the account's secret API token (only creating the
 * URL server-side needs that, see CloudflareStreamGateway). On a failed
 * chunk, a HEAD request recovers the offset the server actually persisted
 * before retrying, rather than assuming nothing landed — 3G/4G connections
 * drop mid-request often enough that "the response never arrived" and "the
 * server never got the bytes" aren't the same thing.
 */
export async function uploadVideoFile(
  uploadUrl: string,
  file: File,
  onProgress: (percent: number) => void,
): Promise<void> {
  let offset = 0;
  onProgress(0);
  while (offset < file.size) {
    offset = await uploadTusChunkWithRetry(uploadUrl, file, offset, onProgress);
  }
  onProgress(100);
}

async function uploadTusChunkWithRetry(
  uploadUrl: string,
  file: File,
  offset: number,
  onProgress: (percent: number) => void,
): Promise<number> {
  let currentOffset = offset;
  let lastError: unknown;
  for (const delay of UPLOAD_RETRY_DELAYS_MS) {
    if (delay > 0) {
      await new Promise((resolve) => setTimeout(resolve, delay));
      try {
        currentOffset = await fetchTusOffset(uploadUrl);
      } catch (err) {
        lastError = err;
        continue;
      }
    }
    try {
      return await patchTusChunk(uploadUrl, file, currentOffset, onProgress);
    } catch (err) {
      lastError = err;
    }
  }
  throw lastError instanceof Error ? lastError : new Error("Échec de l'envoi du fichier vidéo.");
}

async function fetchTusOffset(uploadUrl: string): Promise<number> {
  const response = await fetch(uploadUrl, { method: "HEAD", headers: { "Tus-Resumable": TUS_RESUMABLE_VERSION } });
  const offset = Number(response.headers.get("Upload-Offset"));
  if (!response.ok || !Number.isFinite(offset)) {
    throw new Error(`Échec de l'envoi du fichier vidéo (${response.status}).`);
  }
  return offset;
}

function patchTusChunk(
  uploadUrl: string,
  file: File,
  offset: number,
  onProgress: (percent: number) => void,
): Promise<number> {
  return new Promise((resolve, reject) => {
    const chunk = file.slice(offset, Math.min(offset + TUS_CHUNK_SIZE, file.size));
    const xhr = new XMLHttpRequest();
    xhr.open("PATCH", uploadUrl);
    xhr.setRequestHeader("Tus-Resumable", TUS_RESUMABLE_VERSION);
    xhr.setRequestHeader("Upload-Offset", String(offset));
    xhr.setRequestHeader("Content-Type", "application/offset+octet-stream");
    xhr.upload.onprogress = (event) => {
      if (event.lengthComputable) onProgress(Math.round(((offset + event.loaded) / file.size) * 100));
    };
    xhr.onload = () => {
      if (xhr.status >= 200 && xhr.status < 300) {
        const newOffset = Number(xhr.getResponseHeader("Upload-Offset"));
        resolve(Number.isFinite(newOffset) ? newOffset : offset + chunk.size);
      } else {
        reject(new Error(`Échec de l'envoi du fichier vidéo (${xhr.status}).`));
      }
    };
    xhr.onerror = () => reject(new Error("Échec de l'envoi du fichier vidéo (réseau)."));
    xhr.send(chunk);
  });
}

export async function fetchVideoSourceStatus(videoId: number): Promise<{
  source_status: { value: string; label: string };
  playback_url: string | null;
}> {
  return getJson(`/creator/videos/${videoId}/source`);
}

export async function fetchBalance(): Promise<CreatorBalance> {
  return getJson("/creator/balance");
}

export async function fetchMyPayouts(): Promise<PayoutListResponse> {
  return getJson("/creator/payouts");
}

export async function requestPayout(input: {
  amount: number;
  destination_msisdn: string;
}): Promise<Payout> {
  return postJson("/creator/payouts", input, { authenticated: true });
}

export async function reportVideo(videoId: number, reason: string): Promise<{ message: string }> {
  return postJson(`/videos/${videoId}/report`, { reason }, { authenticated: true });
}

export async function fetchReviews(videoId: number): Promise<PaginatedResponse<Review>> {
  const response = await fetch(`${API_BASE_URL}/videos/${videoId}/reviews`);

  if (!response.ok) {
    throw new Error(await extractErrorMessage(response));
  }

  return response.json();
}

export async function submitReview(
  videoId: number,
  input: { rating: number; comment?: string },
): Promise<Review> {
  const { data } = await postJson<{ data: Review }>(`/videos/${videoId}/reviews`, input, {
    authenticated: true,
  });
  return data;
}

export async function fetchCreatorStats(): Promise<CreatorStats> {
  return getJson("/creator/stats");
}

export async function fetchMyMessages(): Promise<{ data: Message[] }> {
  return getJson("/messages");
}

export async function fetchNotifications(): Promise<NotificationListResponse> {
  return getJson("/notifications");
}

export async function markNotificationRead(id: string): Promise<{ read: boolean }> {
  return postJson(`/notifications/${id}/read`, {}, { authenticated: true });
}

export async function markAllNotificationsRead(): Promise<{ message: string }> {
  return postJson("/notifications/read-all", {}, { authenticated: true });
}

export async function sendMessage(body: string): Promise<Message> {
  return postJson("/messages", { body }, { authenticated: true });
}

/**
 * Downloads the JSON export in the browser (Content-Disposition needs the
 * Bearer token in a header, so a plain `<a href>` link can't hit this
 * endpoint directly — fetch it as a blob and trigger the save ourselves).
 */
export async function exportAccountData(): Promise<void> {
  const token = getToken();
  if (!token) throw new Error("Vous devez être connecté.");

  const response = await fetch(`${API_BASE_URL}/account/export`, {
    headers: { Authorization: `Bearer ${token}` },
  });

  if (!response.ok) {
    throw new Error(await extractErrorMessage(response));
  }

  const blob = await response.blob();
  const url = URL.createObjectURL(blob);
  const link = document.createElement("a");
  link.href = url;
  link.download = "streammali-donnees.json";
  link.click();
  URL.revokeObjectURL(url);
}

export async function deleteAccount(): Promise<void> {
  const token = getToken();
  if (!token) throw new Error("Vous devez être connecté.");

  const response = await fetch(`${API_BASE_URL}/account`, {
    method: "DELETE",
    headers: { Authorization: `Bearer ${token}` },
  });

  if (!response.ok) {
    throw new Error(await extractErrorMessage(response));
  }
}

async function getJson<T>(path: string): Promise<T> {
  const token = getToken();
  if (!token) throw new Error("Vous devez être connecté.");

  const response = await fetch(`${API_BASE_URL}${path}`, {
    headers: { Authorization: `Bearer ${token}` },
  });

  if (!response.ok) {
    throw new Error(await extractErrorMessage(response));
  }

  return response.json();
}

async function postJson<T>(
  path: string,
  body: unknown,
  options: { authenticated?: boolean } = {},
): Promise<T> {
  const headers: Record<string, string> = { "Content-Type": "application/json" };

  if (options.authenticated) {
    const token = getToken();
    if (!token) throw new Error("Vous devez être connecté.");
    headers.Authorization = `Bearer ${token}`;
  }

  const response = await fetch(`${API_BASE_URL}${path}`, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
  });

  if (!response.ok) {
    throw new Error(await extractErrorMessage(response));
  }

  return response.json();
}

async function extractErrorMessage(response: Response): Promise<string> {
  // A 401 means the stored token is no longer valid (expired, revoked, or
  // the account got suspended/blocked mid-session — see CLAUDE.md, that
  // takes effect immediately on existing tokens). Without this, the app
  // stays "logged in" client-side forever, silently failing every
  // subsequent request with no way out except manually visiting /connexion.
  if (response.status === 401) clearSession();

  try {
    const json = await response.json();
    if (json.errors) {
      return Object.values(json.errors as Record<string, string[]>).flat().join(" ");
    }
    if (json.message) return json.message;
  } catch {
    // response wasn't JSON — fall through to the generic message below
  }
  return `Une erreur est survenue (${response.status}).`;
}
