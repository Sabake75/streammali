# StreamMali API

Backend Laravel : API REST pour les 3 profils (Créateur, Viewer, Modérateur) + back-office modérateur (Filament).

## Rôle

- Auth API (Sanctum) et gestion des rôles/permissions.
- Domaines métier : `Creator`, `Viewer`, `Moderation`, `Payment`, `Video`.
- Intégration Orange Money (Web Payment API) — voir `CLAUDE.md` à la racine du dépôt.
- Jobs asynchrones (queue) : webhooks de paiement, orchestration de transcodage vidéo, notifications.

## Structure prévue

```
api/
├── app/
│   ├── Domain/
│   │   ├── Creator/
│   │   ├── Viewer/
│   │   ├── Moderation/
│   │   ├── Payment/
│   │   └── Video/
│   ├── Http/Controllers/Api/
│   ├── Filament/
│   └── Jobs/
├── database/migrations/
└── routes/api.php
```

## Statut

Scaffold Laravel initialisé (`composer create-project laravel/laravel`), `.env` configuré pour PostgreSQL (`DB_CONNECTION=pgsql`, base `streammali`).

Les dossiers `Domain/{Creator,Viewer,Moderation,Payment,Video}` sont créés, chacun avec un README décrivant son rôle et sa sous-structure prévue (`Models/`, `Actions/`, `Data/`) — aucune classe métier n'y est encore implémentée.

Back-office modérateur : panneau Filament sur `/moderation` (`app/Providers/Filament/ModerationPanelProvider.php`). Accès restreint via `App\Enums\UserRole` (colonne `role` sur `users`) : seuls les comptes `moderator` peuvent se connecter (`User::canAccessPanel`).

Table `videos` + modèle `App\Domain\Video\Models\Video` (catégorie `VideoCategory`, statut de modération `VideoStatus`). Ressource Filament `app/Filament/Resources/Videos/` : file d'attente avec filtres statut/catégorie et actions **Valider** / **Refuser** (motif obligatoire), conformes au cahier des charges §5.3.

Table `payments` + modèle `App\Domain\Payment\Models\Payment`, intégration Orange Money (`App\Domain\Payment\Gateways\OrangeMoneyGateway`) — voir `app/Domain/Payment/README.md` pour le détail. Config dans `.env`/`config/services.php` (`ORANGE_MONEY_*`, credentials vides tant qu'il n'y a pas de compte marchand). Webhook : `/api/webhooks/orange-money`.

Auth API (Viewer, par téléphone — cahier des charges §5.2, colonne `phone` ajoutée à `users`, `email` devenu optionnel) :
- `POST /api/register` — `{ name, phone, password }`, crée un compte `role=viewer` (`App\Domain\Viewer\Actions\RegisterViewer`) et renvoie un token Sanctum.
- `POST /api/login` — `{ phone, password }`, renvoie un token Sanctum.
- `POST /api/logout` — authentifié, révoque le token courant.

Le login Filament du modérateur (`/moderation/login`) continue d'utiliser `email`, inchangé — les deux colonnes coexistent sur `users`. Pas d'inscription Créateur (pièce d'identité) ni de vérification SMS/OTP du téléphone pour l'instant.

API catalogue et achat (publiques sauf mention) :
- `GET /api/videos` — liste paginée des vidéos **validées uniquement**, filtres `category`/`creator_id`/`search`.
- `GET /api/videos/{id}` — fiche vidéo (404 si pas encore validée).
- `POST /api/videos/{id}/purchase` — authentifié (`auth:sanctum`), body `{ payer_msisdn }`, démarre un paiement Orange Money et renvoie `payment_url` ; 404 si vidéo non validée, 409 si déjà achetée.

Gestion des comptes côté modérateur (cahier des charges §5.3) : ressource Filament `app/Filament/Resources/Users/` sur `/moderation/users` (créateurs/viewers uniquement, les comptes modérateur sont exclus de cette liste). Colonnes `account_status` (`active`/`suspended`/`blocked`) et `identity_verified_at` ajoutées à `users`. Actions : Suspendre/Bloquer (motif obligatoire), Réactiver, Vérifier l'identité. Un compte suspendu/bloqué ne peut plus se connecter (`LoginController`) ni utiliser un token existant (middleware `account.active` sur toutes les routes `auth:sanctum`). Pas de vraie vérification de pièce d'identité (upload de document) — c'est un simple horodatage que le modérateur pose manuellement.

CORS (`config/cors.php`) : `allowed_origins` piloté par `CORS_ALLOWED_ORIGINS` (défaut `http://localhost:3000`), `paths` couvre `api/*`. Auth client via **token Bearer Sanctum**, pas de cookies/CSRF — décision volontaire pour rester cohérent avec le mobile Flutter (qui devra utiliser le même flux token) plutôt que d'ajouter un second mécanisme d'auth (Sanctum SPA). Vérifié avec de vraies requêtes cross-origin (`Origin: http://localhost:3000`) : préflight OK, `Authorization` accepté, une origine non autorisée reçoit un `Access-Control-Allow-Origin` qui ne correspond pas à la sienne (bloqué côté navigateur).

API créateur (authentifié, `role=creator` requis — 403 sinon) :
- `POST /api/creator/videos` — `{ title, description?, category, poster_path?, duration_seconds?, price? }`, crée une vidéo en statut `pending` (`App\Domain\Creator\Actions\UploadVideo`). Prix par défaut 25 FCFA si omis.
- `GET /api/creator/videos` — liste paginée des vidéos du créateur connecté, **tous statuts confondus** (contrairement au catalogue public), via `CreatorVideoResource` qui expose `status`/`rejection_reason`/`source_status`.
- `POST /api/creator/videos/{id}/source` — démarre l'upload du fichier (Cloudflare Stream, flux "direct creator upload"), renvoie `upload_url` ; 409 si déjà en cours/terminé.
- `GET /api/creator/videos/{id}/source` — rafraîchit/consulte le statut de traitement (`processing`/`ready`/`failed`) et l'URL de lecture une fois prête.

Upload vidéo (détail dans `app/Domain/Video/README.md`) : intégration Cloudflare Stream — le fichier part directement du client vers Cloudflare (jamais proxié par l'API), le statut est interrogé côté serveur plutôt que via un webhook entrant. Config `CLOUDFLARE_STREAM_*` dans `.env`/`config/services.php`, credentials vides tant qu'il n'y a pas de compte. Une vidéo ne peut être validée par un modérateur que si son fichier est `ready`. L'URL de lecture n'est exposée dans `GET /api/videos/{id}` qu'aux acheteurs, une fois le fichier prêt.

Pas encore fait : inscription Créateur (le cahier des charges exige une pièce d'identité — pas construit, comptes créateur créés manuellement pour l'instant), webhook Cloudflare (statut interrogé à la demande pour l'instant), aperçu gratuit avant achat.

Ledger commission/créateur et demandes de retrait (cahier des charges §6, détail dans `app/Domain/Payment/README.md`) :
- `GET /api/creator/balance` — solde disponible du créateur connecté.
- `GET /api/creator/payouts` — historique de ses demandes de retrait.
- `POST /api/creator/payouts` — `{ amount, destination_msisdn }`, rejette sous 10 000 FCFA ou au-dessus du solde disponible.
- Back-office modérateur `/moderation/payouts` (Marquer payé / Rejeter) et `/moderation/ledger-entries` (lecture seule, historique des ventes/commissions par créateur).

Testé via `tests/Feature/` (`php artisan test`) — 50/50 au dernier passage, vérifié aussi manuellement contre PostgreSQL (upload créateur, upload vidéo/lecture, calcul de commission, réservation du solde).

Créer un utilisateur modérateur de test :

```bash
php artisan filament:make-user --name="..." --email="..." --password="..."
php artisan tinker --execute="\$u = App\Models\User::where('email','...')->first(); \$u->role = App\Enums\UserRole::Moderator; \$u->save();"
```

Extension `pdo_pgsql`, serveur PostgreSQL, utilisateur/base `streammali` en place et migrations par défaut exécutées (`users`, `sessions`, `jobs`, `cache`, ...).

Identifiants dans `.env` (`DB_DATABASE=streammali`, `DB_USERNAME=streammali`, `DB_PASSWORD=streammali`) — à changer avant tout déploiement au-delà du dev local.
