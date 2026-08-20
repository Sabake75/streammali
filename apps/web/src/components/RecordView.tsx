"use client";

import { useEffect } from "react";
import { recordVideoView } from "@/lib/api-client";

/** Renders nothing — fires a view-tracking request once per page load. */
export function RecordView({ videoId }: { videoId: number }) {
  useEffect(() => {
    recordVideoView(videoId);
  }, [videoId]);

  return null;
}
