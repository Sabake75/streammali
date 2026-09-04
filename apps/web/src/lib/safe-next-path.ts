/**
 * Cantonne `?next=` aux chemins internes avant tout router.push/redirect —
 * "//evil.com" est un chemin relatif valide pour `startsWith("/")` mais le
 * navigateur le traite comme protocol-relative (même origine que la page
 * courante, mais vers un autre host), donc rejeté explicitement en plus.
 */
export function safeNextPath(value: string | null): string {
  if (!value || !value.startsWith("/") || value.startsWith("//")) return "/";
  return value;
}
