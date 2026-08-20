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

- `apps/api` : scaffold Laravel initialisé et fonctionnel sur PostgreSQL (migrations par défaut exécutées). Structure de domaines métier créée (`app/Domain/{Creator,Viewer,Moderation,Payment,Video}`). Back-office modérateur Filament sur `/moderation`, accès restreint aux comptes `role = moderator`. Modèle `Video` + ressource Filament fonctionnels : file d'attente de modération avec actions Valider/Refuser (motif obligatoire). Modèle `Payment` + intégration Orange Money (`App\Domain\Payment\Gateways\OrangeMoneyGateway`, webhook `/api/webhooks/orange-money`, statut revérifié côté serveur plutôt que de faire confiance au webhook) — **implémentation basée sur la doc publique Orange Money Web Payment, à confirmer contre Orange Developer Center Mali une fois les credentials marchand obtenus**. Auth Viewer par téléphone (`POST /api/register`, `/api/login`, `/api/logout`) — `users.phone` ajouté, `users.email` rendu optionnel (le login Filament modérateur garde `email`, inchangé). API catalogue/achat publique : `GET /api/videos`, `GET /api/videos/{id}`, `POST /api/videos/{id}/purchase` (auth Sanctum). API créateur : `POST /api/creator/videos` (upload, statut `pending`), `GET /api/creator/videos` (liste tous statuts, `role=creator` requis). Gestion des comptes modérateur (`/moderation/users`) : suspendre/bloquer (motif obligatoire)/réactiver/vérifier l'identité ; un compte non actif est rejeté à la connexion et sur toute route API authentifiée (middleware `account.active`). Ledger commission/créateur + demandes de retrait : `LedgerEntry` créé automatiquement à chaque paiement confirmé (taux configurable, défaut 25 %), `GET/POST /api/creator/{balance,payouts}` côté créateur, back-office `/moderation/{payouts,ledger-entries}` côté modérateur (retrait minimum 10 000 FCFA, pas de décaissement Mobile Money réel — traitement manuel par le modérateur). Upload du fichier vidéo : intégration Cloudflare Stream (flux "direct creator upload", credentials vides tant qu'il n'y a pas de compte — à vérifier contre la doc Cloudflare), `POST/GET /api/creator/videos/{id}/source`, validation modérateur bloquée tant que le fichier n'est pas `ready`, URL de lecture exposée uniquement aux acheteurs une fois prête. Inscription Créateur avec pièce d'identité : `POST /api/register/creator` (multipart), document stocké sur disque **privé**, consultable uniquement par un modérateur connecté (`GET /moderation/creators/{id}/identity-document`) ; la vérification elle-même reste un geste manuel du modérateur (pas d'OCR). `php artisan test` : 56/56 verts, vérifié aussi contre PostgreSQL. Reste à faire : aperçu gratuit avant achat, webhook Cloudflare (statut interrogé à la demande pour l'instant).
- `apps/web` : scaffold Next.js initialisé (TypeScript, Tailwind, App Router). Catalogue fonctionnel en SSR (liste + fiche détail + filtres). Auth + achat côté client fonctionnels : connexion/inscription, `PurchaseButton` sur la fiche vidéo, token Bearer Sanctum stocké en `localStorage` (pas de Sanctum SPA/cookies — décision volontaire pour rester cohérent avec le futur client mobile Flutter, un seul mécanisme d'auth pour tous les clients). CORS configuré côté API (`CORS_ALLOWED_ORIGINS`). Espace créateur (`/creer`) : inscription créateur avec pièce d'identité (`/inscription-createur`), création vidéo + upload du fichier via `tus-js-client` contre l'URL Cloudflare Stream renvoyée par l'API. Vérifié de bout en bout avec de vraies requêtes cross-origin (y compris l'upload multipart de la pièce d'identité) ; seuls les appels réels à Orange Money/Cloudflare échouent, faute de credentials (déjà documenté).
- `apps/mobile` : scaffold Flutter initialisé (`com.streammali`). Catalogue fonctionnel (liste paginée + filtres + fiche détail). Auth + achat portés avec le même flux token Bearer Sanctum que le web (`AuthController` en `ChangeNotifier` + `shared_preferences`, achat via `url_launcher` vers `payment_url`). Espace créateur (`CreatorScreen`) : inscription créateur avec pièce d'identité (`RegisterCreatorScreen`, upload multipart via `http.MultipartRequest`), création vidéo + upload du fichier via `tus_client_dart` + `file_picker` contre l'URL Cloudflare Stream. `flutter analyze`/`flutter test`/`flutter build web` propres.
- Les choix de stack ci-dessus restent indicatifs pour le reste et sont à valider au fur et à mesure du développement du MVP.
