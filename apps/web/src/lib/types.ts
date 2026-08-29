// Moderator-managed (see GET /api/categories) — not a fixed set at compile time.
export type VideoCategoryValue = string;

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
  favorited?: boolean;
  playback_url?: string | null;
  preview_playback_url: string | null;
  average_rating: number | null;
  reviews_count: number;
  created_at: string;
  // Only present on GET /api/purchases ("Mes achats") — the receipt detail
  // for that specific purchase, absent everywhere else (catalogue, favorites…).
  purchase?: {
    amount: number;
    purchased_at: string;
    order_reference: string;
  };
};

export type Review = {
  id: number;
  rating: number;
  comment: string | null;
  user: {
    id: number;
    name: string;
  };
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

export type AppNotification = {
  id: string;
  data:
    | { type: "video_status_changed"; video_id: number; video_title: string; status: "approved" | "rejected"; rejection_reason: string | null }
    | { type: "new_moderator_message"; message_id: number; excerpt: string };
  read: boolean;
  created_at: string;
};

export type NotificationListResponse = PaginatedResponse<AppNotification> & { unread_count: number };

export type PaginatedResponse<T> = {
  data: T[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
};
