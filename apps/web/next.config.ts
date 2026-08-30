import type { NextConfig } from "next";

const isProd = process.env.NODE_ENV === "production";

// Video manifests/segments and preview thumbnails are all served from
// Cloudflare Stream, at a per-customer subdomain (see playback_url in the
// API response) as well as the bare domain for public/shared clips.
const cloudflareStream = "https://*.cloudflarestream.com https://cloudflarestream.com";

// Every api-client.ts call (purchase, favorite, login, view tracking…)
// fetches this origin directly from the browser — confirmed by testing
// this exact CSP against a live page and watching it block those calls.
// Derived from the same env var api-client.ts itself reads, so this can
// never silently drift from wherever the API actually lives.
const apiOrigin = new URL(process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000/api").origin;

const contentSecurityPolicy = [
  "default-src 'self'",
  // 'unsafe-inline' on script-src: Next.js injects inline hydration
  // scripts on every page (confirmed by testing this exact build — a
  // strict 'self' blocked hydration everywhere, breaking every client
  // component: forms, buttons, the works). The fully strict alternative
  // is a per-request nonce, but that requires forcing EVERY page into
  // dynamic rendering (Next's own CSP guide: "all pages must be
  // dynamically rendered" for nonces) — a real cost for a bandwidth-
  // conscious app that's mostly static pages today, to close a gap
  // 'unsafe-inline' here doesn't actually leave open in practice: an
  // attacker able to inject a <script> tag already needs stored/reflected
  // XSS, and this app takes no raw HTML from users anywhere (no
  // dangerouslySetInnerHTML, all user content goes through React's
  // auto-escaping) — this CSP's real job is the other directives:
  // frame-ancestors, object-src, connect-src, base-uri, form-action.
  "script-src 'self' 'unsafe-inline'",
  "style-src 'self' 'unsafe-inline'",
  `img-src 'self' data: ${cloudflareStream}`,
  `media-src 'self' ${cloudflareStream}`,
  `connect-src 'self' ${apiOrigin} ${cloudflareStream}`,
  "font-src 'self'",
  "object-src 'none'",
  "frame-ancestors 'none'",
  "base-uri 'self'",
  "form-action 'self'",
].join("; ");

const nextConfig: NextConfig = {
  async headers() {
    return [
      {
        source: "/:path*",
        headers: [
          { key: "X-Frame-Options", value: "DENY" },
          { key: "X-Content-Type-Options", value: "nosniff" },
          { key: "Referrer-Policy", value: "strict-origin-when-cross-origin" },
          { key: "Permissions-Policy", value: "camera=(), microphone=(), geolocation=()" },
          // Skipped outside production: Next's dev server needs 'unsafe-eval'
          // for HMR and a websocket connect-src it doesn't need in a real
          // build, so a dev-only CSP would just be a different (weaker)
          // policy than what actually ships — not worth maintaining two.
          ...(isProd
            ? [
                { key: "Content-Security-Policy", value: contentSecurityPolicy },
                { key: "Strict-Transport-Security", value: "max-age=63072000; includeSubDomains; preload" },
              ]
            : []),
        ],
      },
    ];
  },
};

export default nextConfig;
