# StreamMali

Plateforme de streaming vidéo « mini Netflix » pensée pour le Mali. Elle permet aux créateurs locaux (réalisateurs, artistes, humoristes) de publier films, clips et web-séries, et au public de les acheter à l'unité à tarif fixe et bas, payable en Mobile Money.

Contexte : la production locale (films, clips, sketchs) circule surtout de façon informelle (USB, WhatsApp, réseaux sociaux) sans rémunération pour les créateurs, et les plateformes internationales sont mal adaptées au pouvoir d'achat et à l'offre locale. StreamMali répond à ça avec un ticket d'entrée très faible et un paiement mobile déjà familier au public malien.

Référence complète : `CAHIER_DES_CHARGES_STREAMMALI.md`.

## Profils utilisateurs

**Créateur** — producteur de contenu.
- Inscription avec pièce d'identité, upload de vidéos (titre, description, catégorie, jaquette, durée).
- Soumission à la modération avant publication ; suivi de statut (en attente / validé / refusé + motif).
- Fixation/confirmation du prix (25 FCFA par défaut).
- Dashboard : vues, achats, revenus, évolution dans le temps.
- Demande de retrait vers Mobile Money ou compte bancaire.
- Gestion du catalogue personnel, messagerie avec la modération.

**Viewer (fan)** — utilisateur final.
- Inscription simple (téléphone, nom, prénom).
- Catalogue avec recherche/filtres (catégorie, créateur, popularité), aperçu gratuit avant achat.
- Achat à l'unité (25 FCFA, Mobile Money), accès illimité en streaming à la vidéo achetée.
- Historique d'achats, bibliothèque personnelle, favoris, recommandations, notation/commentaires.
- Support / signalement de problème.

**Modérateur (administrateur)** — contrôle qualité et supervision.
- Dashboard global (créateurs, viewers, ventes, chiffre d'affaires).
- File d'attente de modération : visionnage, validation/refus (motif obligatoire).
- Dépublication en cas de signalement, gestion des comptes (suspension, blocage, vérification d'identité).
- Suivi des transactions et reversements, statistiques/rapports exportables.
- Gestion des catégories et mise en avant sur la page d'accueil.

## Modèle économique

- Vente à l'acte (pay-per-view), pas d'abonnement : **25 FCFA par vidéo**.
- Commission plateforme : **20 à 30 %** (à définir) sur chaque vente, pour couvrir hébergement/paiement/fonctionnement.
- Reversement au créateur : solde restant, **périodicité hebdomadaire**, vers Mobile Money.
- Frais des opérateurs Mobile Money à la charge de la plateforme.
- Montant minimum de retrait : **10 000 FCFA**.
- Paiement (MVP) : **Orange Money uniquement**, intégration directe (pas d'agrégateur, pas de carte bancaire dans un premier temps) ; parcours = sélection vidéo → confirmation prix → paiement Orange Money (USSD/push) → déverrouillage immédiat après confirmation. Autres opérateurs (Moov Money, Sama Money) et carte bancaire envisagés en phase ultérieure.

## Stack technique proposée (à valider)

- **Frontend web** : application responsive (React, Vue ou équivalent).
- **Mobile** : Android natif ou multiplateforme (Flutter / React Native) — Android prioritaire.
- **Backend** : API REST sécurisée (Node.js, Laravel ou équivalent) + base de données relationnelle.
- **Vidéo** : service de streaming/CDN adapté (upload, transcodage, protection du flux, streaming adaptatif multi-qualité).
- **Paiement (MVP)** : intégration directe **Orange Money Web Payment API** (OAuth2 client credentials + webhook de confirmation), sans passerelle agrégée. Prévoir une interface `PaymentGateway` côté backend pour pouvoir brancher d'autres opérateurs plus tard sans réécrire la logique métier.
- **Back-office modérateur** : dashboard d'admin séparé avec gestion des rôles/droits.

## Contraintes clés

- Connectivité souvent limitée (3G/4G) → forte optimisation bande passante, faible consommation de données.
- Vérification des droits d'auteur des contenus déposés (attestation de propriété).
- Fiabilité variable des API Mobile Money (délais, échecs à gérer).
- Modération humaine obligatoire avant mise en ligne → délai de traitement à communiquer.
- Interface multilingue envisageable (français, bambara).
- Sécurité : chiffrement des transactions, protection anti-téléchargement/piratage vidéo, protection des données personnelles.

## État du projet

**MVP fonctionnellement complet (2026-08-20)** — les trois profils ont un parcours de bout en bout sur les trois apps (API, web, mobile), testé en local à plusieurs reprises (dernier passage : 76/76 tests backend, `flutter analyze`/`test`/`build web` et `tsc`/`lint`/`build` propres). Détail par app dans `apps/{api,web,mobile}/README.md` et les `README.md` de chaque domaine métier (`apps/api/app/Domain/*/README.md`).

Construit et vérifié :
- **Inscription/auth** : Viewer par téléphone, Créateur avec pièce d'identité (stockage privé, consultable par un modérateur connecté). Token Bearer Sanctum partagé web/mobile. Tout champ téléphone (inscription, connexion, achat, retrait) est un indicatif pays + un champ chiffres seul, longueur plafonnée par pays via une bibliothèque de référence (`libphonenumber-js` côté web, `phone_numbers_parser` côté mobile) plutôt que des règles codées en dur — tous les pays sont proposés, pas seulement le Mali, pour les Maliens de la diaspora. Le mot de passe est un **code à 4 chiffres** (public peu habitué au mot de passe), toujours hashé (bcrypt) ; `POST /api/login` est limité à 5 tentatives/minute par téléphone+IP pour compenser le faible espace de combinaisons (10 000).
- **Catalogue** : liste/filtres/recherche/pagination, fiche détail, SSR côté web.
- **Upload vidéo** : métadonnées + fichier (Cloudflare Stream, flux direct upload), branché web + mobile.
- **Modération** : file d'attente Filament, valider/refuser (motif obligatoire), validation bloquée tant que le fichier n'est pas prêt.
- **Achat** : Orange Money (intégration directe), webhook revérifié côté serveur, déverrouillage immédiat.
- **Ledger & retraits** : commission automatique par vente, solde/historique/demande de retrait côté créateur (web+mobile), traitement côté modérateur.
- **Comptes** : suspension/blocage/réactivation par le modérateur, effectif immédiatement (connexion + tokens existants).
- **Messagerie créateur ↔ modération** : fil unique par créateur (web + mobile côté créateur, action dédiée sur `/moderation/users` côté modérateur).
- **Signalement de vidéo** : n'importe quel utilisateur connecté peut signaler une vidéo (motif obligatoire) depuis sa fiche (web + mobile) ; le modérateur voit un badge et la liste des motifs sur `/moderation/videos`, et dépublie via l'action "Refuser" déjà existante (pas de mécanisme séparé).
- **Statistiques créateur** : dashboard vues/achats/revenus par vidéo + historique de revenu 14 jours (web + mobile), sur les données existantes (ledger, achats) plus un compteur de vues dédié, incrémenté par un endpoint séparé du fetch mis en cache pour ne pas sous-compter (voir `apps/api/app/Domain/Creator/README.md`).

Deux intégrations tierces (`App\Domain\Payment\Gateways\OrangeMoneyGateway`, `App\Domain\Video\Gateways\CloudflareStreamGateway`) sont écrites contre la documentation publique de chaque fournisseur mais **jamais vérifiées avec de vrais credentials** — c'est le principal risque avant mise en production.

CI GitHub Actions (`.github/workflows/ci.yml`) : PHPUnit, lint+tsc+build web, flutter analyze+test, plus build d'un APK release (artefact CI, pas de publication store) à chaque push sur `master`.

Hébergement/CD choisi et préparé (jamais déployé en conditions réelles — pas de compte Render/Vercel lié) : `apps/api/Dockerfile.prod` (FrankenPHP, build vérifié en local avec une vraie base Postgres) + `render.yaml` (Blueprint API + PostgreSQL managé) + Vercel pour le web ; voir `infra/DEPLOY.md`. Stockage des pièces d'identité créateur basculé sur `FILESYSTEM_DISK` (S3/R2 en prod plutôt que le disque local du conteneur, éphémère).

Webhook Cloudflare Stream (`CloudflareStreamWebhookController`) : ne fait pas confiance au payload entrant, redéclenche juste `RefreshVideoSourceStatus` qui revérifie l'état réel auprès de Cloudflare — même logique que le webhook Orange Money.

**Catégories** gérées par le modérateur (table `categories`, Filament `app/Filament/Resources/Categories/`, endpoint public `GET /api/categories`) — remplace l'ancien enum PHP figé (`film`/`clip`/`sketch`/`series`, toujours seedé par défaut). Web et mobile récupèrent la liste dynamiquement au lieu d'un tableau codé en dur. Détail migration/piège rencontré dans `apps/api/app/Domain/Video/README.md`.

**Aperçu gratuit avant achat** + premier lecteur vidéo de l'app (web + mobile — aucun n'existait avant, corrige au passage la lecture des vidéos déjà achetées). Clip de 45s dérivé via l'API Clip de Cloudflare Stream (`CreatePreviewClip`, déclenché par le webhook quand la vidéo passe `ready`, idempotent), `preview_playback_url` toujours exposé même aux invités contrairement à `playback_url`. Web : `<video>` + `hls.js`. Mobile : `video_player` + `chewie`. Pas d'autoplay (tap-to-play, cohérent avec la contrainte bande passante).

**Notation/commentaires** (`App\Domain\Review`) : réservé aux viewers ayant acheté la vidéo (`Video::isPurchasedBy()`, promu depuis `VideoResource` pour être réutilisable). Un avis par utilisateur/vidéo, resoumettre remplace l'existant. `average_rating`/`reviews_count` exposés sur le catalogue et la fiche vidéo (web + mobile). Pas de modération des avis (pas dans le cahier des charges).

**Favoris/recommandations** (`App\Domain\Viewer`) : favoris = bascule simple (`ToggleFavorite`) + bibliothèque (`GET /api/favorites`). Recommandations volontairement non-ML (`GetRecommendedVideos`) : catégories déjà achetées/favorites par l'utilisateur, hors vidéos déjà possédées, triées par popularité — invités (ou sans historique) reçoivent juste les plus vues. Web/mobile : "Recommandé pour vous" (accueil, uniquement si connecté — l'auth n'existe que côté client via un token, donc invisible côté serveur) et "Vidéos similaires" (fiche vidéo, même catégorie, toujours visible, réutilise le catalogue existant plutôt qu'un nouvel endpoint).

**Prochaine étape — passage en production :**
1. Obtenir un compte marchand Orange Money Mali (Orange Developer Center) et un compte Cloudflare Stream ; confirmer le contrat API exact de chacun et ajuster `OrangeMoneyGateway`/`CloudflareStreamGateway` si besoin. Puis lier le repo à Render/Vercel (`infra/DEPLOY.md`) et enregistrer l'URL du webhook Cloudflare une fois l'API déployée.
2. Fonctionnalité cahier des charges non prioritaire restante pour le MVP : mise en avant sur la page d'accueil par le modérateur.

Les choix de stack (Laravel/PostgreSQL, Next.js, Flutter, Orange Money direct, Cloudflare Stream) sont ceux effectivement implémentés, plus indicatifs à ce stade.
