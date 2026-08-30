import * as Sentry from "@sentry/nextjs";

/**
 * Browser-side error tracking — inert until NEXT_PUBLIC_SENTRY_DSN is set.
 * No Sentry account exists for this project yet (audit finding: no error
 * tracking anywhere), so this stays disabled rather than half-wired. Once
 * a DSN is added, the CSP in next.config.ts also needs Sentry's ingest
 * domain in connect-src/script-src, or the browser will block the SDK's
 * own reporting requests.
 */
Sentry.init({
  dsn: process.env.NEXT_PUBLIC_SENTRY_DSN,
  tracesSampleRate: 0,
});

// Required by the SDK to instrument App Router navigations (confirmed by
// the build's own "ACTION REQUIRED" warning without this line).
export const onRouterTransitionStart = Sentry.captureRouterTransitionStart;
