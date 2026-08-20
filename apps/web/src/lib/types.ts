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

export type PaginatedResponse<T> = {
  data: T[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
};
