import { getToken, type StoredUser } from "@/lib/auth-client";
import type { CreatorVideo, PaginatedResponse, VideoCategoryValue } from "@/lib/types";

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
