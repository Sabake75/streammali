import type { Metadata } from "next";
import { Geist, Geist_Mono } from "next/font/google";
import Link from "next/link";
import { AuthStatus } from "@/components/AuthStatus";
import { NavLink } from "@/components/NavLink";
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
  metadataBase: new URL(process.env.NEXT_PUBLIC_SITE_URL ?? "http://localhost:3000"),
  title: "StreamMali",
  description: "Films, clips et web-séries de créateurs maliens, 100 FCFA la vidéo.",
  openGraph: {
    siteName: "StreamMali",
    locale: "fr_FR",
    type: "website",
  },
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
                <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-orange-500 to-accent-600 text-base text-white">
                  ▶
                </span>
                <span>
                  Stream<span className="text-orange-600 dark:text-orange-400">Mali</span>
                </span>
              </Link>
              <NavLink
                href="/creer"
                className="shrink-0 rounded-full px-3 py-1 text-sm font-medium text-neutral-600 transition hover:text-orange-600 dark:text-neutral-400 dark:hover:text-orange-400"
                activeClassName="bg-orange-600 text-white shadow-sm hover:text-white dark:bg-orange-500"
              >
                Espace créateur
              </NavLink>
            </div>
            <AuthStatus />
          </div>
        </header>
        {children}
        <footer className="mt-16 border-t border-neutral-200 bg-neutral-50/70 dark:border-neutral-800 dark:bg-neutral-950/40">
          <div className="mx-auto grid max-w-6xl gap-10 px-4 py-12 sm:grid-cols-2 sm:px-6 lg:grid-cols-4 lg:px-8">
            <div className="col-span-2 flex flex-col gap-3 lg:col-span-2">
              <Link href="/" className="flex w-fit items-center gap-2 text-lg font-bold tracking-tight">
                <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-orange-500 to-accent-600 text-base text-white">
                  ▶
                </span>
                <span className="text-neutral-900 dark:text-neutral-50">
                  Stream<span className="text-orange-600 dark:text-orange-400">Mali</span>
                </span>
              </Link>
              <p className="max-w-xs text-sm text-neutral-500 dark:text-neutral-400">
                Le cinéma malien à portée de Mobile Money — films, clips et web-séries de créateurs maliens.
              </p>
              <div className="mt-1 flex flex-wrap gap-2 text-xs font-medium text-neutral-600 dark:text-neutral-400">
                <span className="flex items-center gap-1.5 rounded-full border border-neutral-200 px-2.5 py-1 dark:border-neutral-800">
                  <FooterWalletIcon /> 100 FCFA la vidéo
                </span>
                <span className="flex items-center gap-1.5 rounded-full border border-neutral-200 px-2.5 py-1 dark:border-neutral-800">
                  <FooterPhoneIcon /> Mobile Money
                </span>
              </div>
            </div>

            <div className="flex flex-col gap-2 text-sm">
              <h3 className="font-semibold text-neutral-900 dark:text-neutral-50">Découvrir</h3>
              <Link href="/" className="w-fit text-neutral-500 hover:text-orange-600 dark:text-neutral-400 dark:hover:text-orange-400">
                Catalogue
              </Link>
              <Link href="/creer" className="w-fit text-neutral-500 hover:text-orange-600 dark:text-neutral-400 dark:hover:text-orange-400">
                Espace créateur
              </Link>
            </div>

            <div className="flex flex-col gap-2 text-sm">
              <h3 className="font-semibold text-neutral-900 dark:text-neutral-50">Légal</h3>
              <Link
                href="/politique-de-confidentialite"
                className="w-fit text-neutral-500 hover:text-orange-600 dark:text-neutral-400 dark:hover:text-orange-400"
              >
                Politique de confidentialité
              </Link>
              <Link href="/cgu-spectateur" className="w-fit text-neutral-500 hover:text-orange-600 dark:text-neutral-400 dark:hover:text-orange-400">
                CGU spectateur
              </Link>
              <Link href="/cgu-createur" className="w-fit text-neutral-500 hover:text-orange-600 dark:text-neutral-400 dark:hover:text-orange-400">
                CGU créateur
              </Link>
            </div>
          </div>
          <div className="border-t border-neutral-200 dark:border-neutral-800">
            <p className="mx-auto max-w-6xl px-4 py-4 text-xs text-neutral-400 sm:px-6 lg:px-8 dark:text-neutral-500">
              © {new Date().getFullYear()} StreamMali. Fait pour le cinéma malien.
            </p>
          </div>
        </footer>
      </body>
    </html>
  );
}

function FooterWalletIcon() {
  return (
    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
      <rect x="2" y="6" width="20" height="13" rx="2" />
      <path d="M2 10h20" />
      <circle cx="16.5" cy="14.5" r="1" fill="currentColor" stroke="none" />
    </svg>
  );
}

function FooterPhoneIcon() {
  return (
    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
      <rect x="7" y="2" width="10" height="20" rx="2" />
      <path d="M11 18h2" />
    </svg>
  );
}
