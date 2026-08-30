import * as Sentry from "@sentry/nextjs";

/**
 * Server + edge runtime error tracking — inert until SENTRY_DSN is set.
 * See instrumentation-client.ts for the browser-side counterpart and why
 * both start disabled.
 */
export async function register() {
  if (process.env.NEXT_RUNTIME === "nodejs" || process.env.NEXT_RUNTIME === "edge") {
    Sentry.init({
      dsn: process.env.SENTRY_DSN,
      tracesSampleRate: 0,
    });
  }
}

export const onRequestError = Sentry.captureRequestError;
