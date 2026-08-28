/**
 * Thin repeating triangle band inspired by bogolan (Malian mud-cloth)
 * motifs — used once, above the footer, as a subtle nod to local textile
 * patterns rather than a generic template look. Deliberately restrained:
 * a single accent strip, not a repeated background texture everywhere.
 *
 * CSS zigzag (not an SVG viewBox) so the pattern actually repeats at a
 * fixed tile size regardless of the strip's rendered width, rather than
 * being stretched into a handful of oversized triangles.
 */
export function BogolanStrip() {
  return (
    <div
      aria-hidden
      className="h-3 w-full"
      style={{
        backgroundImage: [
          "linear-gradient(135deg, var(--bogolan-a) 25%, transparent 25.5%)",
          "linear-gradient(225deg, var(--bogolan-a) 25%, transparent 25.5%)",
          "linear-gradient(45deg, var(--bogolan-b) 25%, transparent 25.5%)",
          "linear-gradient(315deg, var(--bogolan-b) 25%, transparent 25.5%)",
        ].join(", "),
        backgroundPosition: "0 0, 0 0, 8px 0, 8px 0",
        backgroundSize: "16px 12px",
        // Tailwind can't reach into inline gradients, so the two tones are
        // passed as CSS custom properties instead (still theme-aware).
        ["--bogolan-a" as string]: "var(--bogolan-orange)",
        ["--bogolan-b" as string]: "var(--bogolan-emerald)",
      }}
    />
  );
}
