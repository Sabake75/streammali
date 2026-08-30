"use client";

import { useEffect } from "react";

/**
 * Catches an error in the root layout itself — the one case error.tsx
 * can't cover, since it doesn't wrap the layout above it. Deliberately
 * plain, inline-styled markup rather than Tailwind classes: this replaces
 * the whole document (its own <html>/<body>), and Next.js explicitly
 * doesn't guarantee global styles reach it, since the thing that broke
 * might be the root layout that would normally provide them.
 */
export default function GlobalError({
  error,
  retry,
}: {
  error: Error & { digest?: string };
  retry: () => void;
}) {
  useEffect(() => {
    console.error(error);
  }, [error]);

  return (
    <html lang="fr">
      <body
        style={{
          margin: 0,
          minHeight: "100vh",
          display: "flex",
          flexDirection: "column",
          alignItems: "center",
          justifyContent: "center",
          gap: "12px",
          padding: "24px",
          textAlign: "center",
          fontFamily: "ui-sans-serif, system-ui, sans-serif",
          background: "#fffaf3",
          color: "#1c1712",
        }}
      >
        <h1 style={{ fontSize: "24px", fontWeight: 700, margin: 0 }}>StreamMali a rencontré un problème</h1>
        <p style={{ margin: 0, color: "#6b6b6b", maxWidth: "36ch" }}>
          Ce n&apos;est pas de ta faute. Réessaie, ou reviens plus tard.
        </p>
        <button
          type="button"
          onClick={() => retry()}
          style={{
            marginTop: "12px",
            padding: "10px 20px",
            borderRadius: "999px",
            border: "none",
            background: "#0f2d52",
            color: "#fff",
            fontWeight: 600,
            fontSize: "14px",
            cursor: "pointer",
          }}
        >
          Réessayer
        </button>
      </body>
    </html>
  );
}
