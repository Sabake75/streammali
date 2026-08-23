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
          <div className="mx-auto flex max-w-6xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
            <div className="flex items-center gap-6">
              <Link href="/" className="flex items-center gap-2 text-lg font-bold tracking-tight">
                <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-orange-500 to-emerald-600 text-base text-white">
                  ▶
                </span>
                <span>
                  Stream<span className="text-orange-600 dark:text-orange-400">Mali</span>
                </span>
              </Link>
              <Link
                href="/creer"
                className="text-sm font-medium text-neutral-600 transition hover:text-orange-600 dark:text-neutral-400 dark:hover:text-orange-400"
              >
                Espace créateur
              </Link>
            </div>
            <AuthStatus />
          </div>
        </header>
        {children}
      </body>
    </html>
  );
}
