"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import type { ReactNode } from "react";

/**
 * A nav Link that marks itself current (WCAG 2.4.8 "Location") when its
 * href matches the page you're actually on — the header previously gave no
 * visual cue for which section was active, so a user glancing at the nav
 * had no way to confirm where they were beyond the page's own heading.
 */
export function NavLink({
  href,
  exact = false,
  className = "",
  activeClassName = "",
  children,
  ...rest
}: {
  href: string;
  /** Match only the exact path — off by default so a parent link (e.g. "/creer") stays active on its subpages ("/creer/solde"). */
  exact?: boolean;
  className?: string;
  activeClassName?: string;
  children: ReactNode;
} & React.ComponentPropsWithoutRef<typeof Link>) {
  const pathname = usePathname();
  const isActive = exact ? pathname === href : pathname === href || pathname.startsWith(`${href}/`);

  return (
    <Link
      href={href}
      aria-current={isActive ? "page" : undefined}
      className={`${className} ${isActive ? activeClassName : ""}`}
      {...rest}
    >
      {children}
    </Link>
  );
}
