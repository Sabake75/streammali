# StreamMali Web

Frontend web Next.js (React) : catalogue public, achat, espaces Créateur et Viewer.

## Rôle

- Pages catalogue en SSR/ISR (SEO + légèreté pour connexions 3G/4G limitées).
- Parcours d'achat (sélection vidéo → paiement Orange Money → déblocage immédiat).
- Dashboards Créateur (upload, stats, revenus) et Viewer (bibliothèque, favoris, historique).
- Lecteur vidéo adaptatif (HLS).

## Statut

Scaffold Next.js initialisé (TypeScript, Tailwind CSS, App Router, ESLint).

Catalogue fonctionnel, consomme l'API `apps/api` en Server Components (SSR, pas d'appel client → pas de souci CORS pour l'instant) :
- `src/app/page.tsx` — liste paginée (`GET /api/videos`), filtres catégorie/recherche via un formulaire GET natif (pas de JS requis).
- `src/app/videos/[id]/page.tsx` — fiche vidéo (`GET /api/videos/{id}`), 404 si non trouvée/non validée.
- `src/lib/api.ts` — client fetch vers l'API (`NEXT_PUBLIC_API_URL`, défaut `http://localhost:8000/api`).
- `src/components/{VideoCard,CatalogueFilters,Pagination}.tsx`.

Vérifié manuellement de bout en bout (API Laravel + build prod Next.js lancés ensemble) : liste, filtre par catégorie, fiche détail, 404.

Pas encore fait : bouton d'achat (désactivé pour l'instant — nécessite l'auth côté client, donc du CORS/Sanctum SPA à configurer côté API), jaquettes via `next/image` (actuellement `<img>` brut le temps de choisir un hébergement d'images).

```
npm run dev     # dev server
npm run lint    # eslint
npm run build   # build prod
```

Variables d'env : copier `.env.example` en `.env.local` (`NEXT_PUBLIC_API_URL`).
