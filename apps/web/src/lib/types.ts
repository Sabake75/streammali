export type VideoCategoryValue = "film" | "clip" | "sketch" | "series";

export type VideoCategory = {
  value: VideoCategoryValue;
  label: string;
};

export type VideoSummary = {
  id: number;
  title: string;
  description: string | null;
  category: VideoCategory;
  poster_path: string | null;
  duration_seconds: number | null;
  price: number;
  creator: {
    id: number;
    name: string;
  };
  purchased?: boolean;
  created_at: string;
};

export type VideoSourceStatusValue = "not_started" | "processing" | "ready" | "failed";

export type CreatorVideo = {
  id: number;
  title: string;
  description: string | null;
  category: VideoCategory;
  poster_path: string | null;
  duration_seconds: number | null;
  price: number;
  status: { value: "pending" | "approved" | "rejected"; label: string };
  rejection_reason: string | null;
  source_status: { value: VideoSourceStatusValue; label: string };
  created_at: string;
};

export type CreatorBalance = {
  available_balance: number;
  minimum_payout_amount: number;
};

export type PayoutStatusValue = "pending" | "paid" | "rejected";

export type Payout = {
  id: number;
  amount: number;
  destination_msisdn: string;
  status: { value: PayoutStatusValue; label: string };
  rejection_reason: string | null;
  processed_at: string | null;
  created_at: string;
};

// /api/creator/payouts serializes a raw Laravel paginator (pagination
// fields at the root), unlike the Resource-based endpoints below that
// nest them under `meta` — a known minor API inconsistency.
export type PayoutListResponse = {
  data: Payout[];
  total: number;
  current_page: number;
  last_page: number;
};

export type Message = {
  id: number;
  body: string;
  sender: {
    id: number;
    name: string;
    role: "creator" | "viewer" | "moderator";
  };
  created_at: string;
};

export type CreatorVideoStats = {
  id: number;
  title: string;
  views_count: number;
  purchases_count: number;
  revenue: number;
};

export type CreatorStats = {
  videos: CreatorVideoStats[];
  totals: { views: number; purchases: number; revenue: number };
  timeseries: { date: string; revenue: number }[];
};

export type PaginatedResponse<T> = {
  data: T[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
};
