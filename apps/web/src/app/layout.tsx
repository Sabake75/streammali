import type { Metadata } from "next";
import { Geist, Geist_Mono } from "next/font/google";
import Link from "next/link";
import { AuthStatus } from "@/components/AuthStatus";
import "./globals.css";

const geistSans = Geist({
  variable: "--font-geist-sans",
  subsets: ["latin"],
});

const geistMono = Geist_Mono({
  variable: "--font-geist-mono",
  subsets: ["latin"],
});

export const metadata: Metadata = {
  title: "StreamMali",
  description: "Films, clips et web-séries de créateurs maliens, 25 FCFA la vidéo.",
};

export default function RootLayout({ children }: LayoutProps<"/">) {
  return (
    <html
      lang="fr"
      className={`${geistSans.variable} ${geistMono.variable} h-full antialiased`}
    >
      <body className="flex min-h-full flex-col bg-[var(--background)] text-[var(--foreground)]">
        <header className="sticky top-0 z-20 border-b border-orange-100 bg-[var(--background)]/85 backdrop-blur dark:border-orange-950/60">
          <div className="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-x-4 gap-y-2 px-4 py-4 sm:px-6 lg:px-8">
            <div className="flex items-center gap-4 sm:gap-6">
              <Link href="/" className="flex shrink-0 items-center gap-2 text-lg font-bold tracking-tight">
                <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-orange-500 to-emerald-600 text-base text-white">
                  ▶
                </span>
                <span>
                  Stream<span className="text-orange-600 dark:text-orange-400">Mali</span>
                </span>
              </Link>
              <Link
                href="/creer"
                className="shrink-0 text-sm font-medium text-neutral-600 transition hover:text-orange-600 dark:text-neutral-400 dark:hover:text-orange-400"
              >
                Espace créateur
              </Link>
            </div>
            <AuthStatus />
          </div>
        </header>
        {children}
        <footer className="mt-16 border-t border-neutral-200 dark:border-neutral-800">
          <div className="mx-auto flex max-w-6xl flex-col gap-2 px-4 py-8 text-sm text-neutral-500 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8 dark:text-neutral-400">
            <span className="flex items-center gap-2 font-medium text-neutral-700 dark:text-neutral-300">
              <span className="flex h-6 w-6 items-center justify-center rounded-md bg-gradient-to-br from-orange-500 to-emerald-600 text-xs text-white">
                ▶
              </span>
              StreamMali
            </span>
            <span>Films, clips et web-séries de créateurs maliens · 25 FCFA la vidéo · Paiement Orange Money</span>
          </div>
        </footer>
      </body>
    </html>
  );
}
