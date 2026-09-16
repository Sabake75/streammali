/**
 * Motif géométrique inspiré du bogolan (tissu malien teint à la boue
 * fermentée) : chevrons, losanges et points, un vocabulaire visuel
 * immédiatement identifiable comme malien — contrairement à des jaquettes
 * de films qui pourraient venir de n'importe où. Dessiné en SVG plutôt
 * qu'une photo pour rester léger (quelques Ko, pas de requête image) et
 * ne dépendre d'aucun droit d'usage externe.
 */
export function HeroPattern() {
  return (
    <svg aria-hidden className="absolute inset-0 h-full w-full">
      <defs>
        <pattern id="bogolan" width="80" height="80" patternUnits="userSpaceOnUse">
          <rect width="80" height="80" fill="#3e1e09" />
          <path d="M0 20 L20 0 L40 20 L60 0 L80 20" fill="none" stroke="#5c2e0a" strokeWidth="3" />
          <path d="M0 60 L20 40 L40 60 L60 40 L80 60" fill="none" stroke="#5c2e0a" strokeWidth="3" />
          <rect x="35.5" y="35.5" width="9" height="9" fill="#7a3f0b" transform="rotate(45 40 40)" />
          <circle cx="10" cy="70" r="2.5" fill="#95520f" />
          <circle cx="70" cy="10" r="2.5" fill="#95520f" />
          <circle cx="70" cy="70" r="2.5" fill="#95520f" />
          <circle cx="10" cy="10" r="2.5" fill="#95520f" />
        </pattern>
      </defs>
      <rect width="100%" height="100%" fill="url(#bogolan)" />
    </svg>
  );
}
