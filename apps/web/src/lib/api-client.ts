import { getToken, type StoredUser } from "@/lib/auth-client";
import type {
  CreatorBalance,
  CreatorStats,
  CreatorVideo,
  Message,
  PaginatedResponse,
  Payout,
  PayoutListResponse,
  VideoCategoryValue,
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
}): Promise<AuthResponse> {
  return postJson("/register", input);
}

export async function registerCreator(input: {
  name: string;
  phone: string;
  password: string;
  identityDocument: File;
}): Promise<AuthResponse> {
  const formData = new FormData();
  formData.set("name", input.name);
  formData.set("phone", input.phone);
  formData.set("password", input.password);
  formData.set("identity_document", input.identityDocument);

  const response = await fetch(`${API_BASE_URL}/register/creator`, {
    method: "POST",
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

export async function createVideoUploadUrl(videoId: number): Promise<{ upload_url: string }> {
  return postJson(`/creator/videos/${videoId}/source`, {}, { authenticated: true });
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

export async function fetchCreatorStats(): Promise<CreatorStats> {
  return getJson("/creator/stats");
}

export async function fetchMyMessages(): Promise<{ data: Message[] }> {
  return getJson("/creator/messages");
}

export async function sendMessage(body: string): Promise<Message> {
  return postJson("/creator/messages", { body }, { authenticated: true });
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
