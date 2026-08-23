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

Auth + achat côté client (Bearer token Sanctum, pas de cookies/CSRF — voir `apps/api/README.md`) :
- `src/app/connexion/page.tsx`, `src/app/inscription/page.tsx` — formulaires client, appellent `POST /api/login`/`/register`, stockent le token dans `localStorage` (`src/lib/auth-client.ts`).
- `src/lib/use-auth.ts` — `useAuthToken`/`useAuthUser` via `useSyncExternalStore` (lit `localStorage` sans décalage d'hydratation SSR, se resynchronise entre onglets et après connexion/déconnexion dans le même onglet).
- `src/components/AuthStatus.tsx` (header) et `src/components/PurchaseButton.tsx` (fiche vidéo) consomment ces hooks ; `PurchaseButton` appelle `POST /api/videos/{id}/purchase` et redirige vers `payment_url`.

Vérifié en conditions réelles (requêtes cross-origin avec `Origin: http://localhost:3000` contre l'API) : le flux passe l'auth/CORS/validation de bout en bout ; l'échec final vient uniquement de l'absence de vrais credentials Orange Money côté API (déjà documenté), pas d'un problème CORS/Sanctum.

Inscription créateur (`/inscription-createur`, liée depuis `/inscription` et depuis `/creer`) : formulaire avec upload de pièce d'identité (`multipart/form-data`, `POST /api/register/creator`), redirige vers `/creer` après création du compte.

Upload vidéo côté créateur (`/creer`, lien dans le header) :
- Réservé aux comptes `role=creator` (lien vers l'inscription créateur sinon).
- `src/components/creator/NewVideoForm.tsx` — crée la vidéo (métadonnées, `POST /api/creator/videos`).
- `src/components/creator/VideoUploadWidget.tsx` — upload du fichier via **tus-js-client** contre l'`upload_url` Cloudflare Stream renvoyée par `POST /api/creator/videos/{id}/source` (protocole TUS "direct creator upload" — `uploadUrl` passé à tus-js-client, pas `endpoint`, puisque la ressource d'upload existe déjà côté Cloudflare). Barre de progression, puis sondage (`GET .../source`) toutes les 5s jusqu'à `ready`/`failed`.

Vérifié en conditions réelles contre l'API (inscription créateur avec vrai upload multipart, création vidéo, liste "mes vidéos" — tout confirmé sur PostgreSQL) ; l'appel Cloudflare échoue comme attendu faute de vrai compte (même limitation que côté API).

Champ téléphone (connexion, inscription, achat, retrait créateur) : `src/components/PhoneNumberField.tsx`, indicatif pays + chiffres seuls, longueur plafonnée par pays via `src/lib/phone.ts` (`libphonenumber-js` — tous les pays proposés, pas seulement le Mali, pour les Maliens de la diaspora).

Mot de passe (connexion, inscription, inscription créateur) : `src/components/PinCodeField.tsx`, code à 4 chiffres uniquement (masqué, `inputMode="numeric"`, plafonné à 4), au lieu d'un mot de passe classique — voir `apps/api/README.md` pour la justification et le throttling côté serveur.

Messagerie créateur ↔ modération (`/creer`) : `src/components/creator/Messaging.tsx`, style bulles de chat (message du créateur aligné à droite, réponses de la modération à gauche), `GET`/`POST /api/creator/messages`.

Signalement de vidéo (fiche vidéo) : `src/components/ReportButton.tsx`, lien discret sous le bouton d'achat, formulaire de motif repliable, `POST /api/videos/{id}/report`.

Statistiques créateur (`/creer`) : `src/components/creator/Stats.tsx`, totaux (vues/achats/revenus), graphique en barres (CSS pur, pas de librairie) du revenu sur 14 jours, tableau par vidéo, `GET /api/creator/stats`.

Comptage de vues (fiche vidéo) : `src/components/RecordView.tsx`, composant client sans rendu qui déclenche `POST /api/videos/{id}/view` au montage. Volontairement séparé de `fetchVideo()` (`src/lib/api.ts`), qui utilise `next: { revalidate: 60 }` — un effet de bord placé dans un fetch mis en cache serait silencieusement sauté pour toute requête servie depuis le cache (partagé entre visiteurs). En dev (`next dev`), React Strict Mode double-invoque l'effet (2 requêtes `/view` par visite) — artefact connu de React, sans impact en production (`next build && next start` : une visite = une requête, vérifié).

Catégories dynamiques (`GET /api/categories`, plus de liste codée en dur) : `src/lib/api.ts` (`fetchCategories`, SSR) et `src/lib/api-client.ts` (browser, `NewVideoForm`) — remplace l'ancien `VIDEO_CATEGORIES` figé côté `CatalogueFilters` et `NewVideoForm`.

Lecteur vidéo (`src/components/VideoPlayer.tsx`, fiche vidéo) : premier lecteur de l'app — `<video>` natif + `hls.js` (Safari lit le HLS nativement, les autres navigateurs non), pas d'autoplay (bouton play natif = tap-to-play). Vidéo achetée et prête → lecture complète (`playback_url`) ; sinon aperçu (`preview_playback_url`, toujours exposé même aux invités) avec mention "Aperçu — achète la vidéo pour la voir en entier."

Notation/commentaires (`src/components/Reviews.tsx`, fiche vidéo) : formulaire (étoiles + commentaire optionnel) visible uniquement si `video.purchased`, `POST /api/videos/{id}/reviews` ; liste des avis publique, `GET /api/videos/{id}/reviews`, rechargée après soumission. Composant autonome, se charge lui-même côté client (même pattern que `Messaging.tsx`) plutôt que de recevoir les données du serveur.

Pas encore fait : jaquettes via `next/image` (actuellement `<img>` brut le temps de choisir un hébergement d'images).

```
npm run dev     # dev server
npm run lint    # eslint
npm run build   # build prod
```

Variables d'env : copier `.env.example` en `.env.local` (`NEXT_PUBLIC_API_URL`).
