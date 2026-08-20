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

Pas encore fait : lecture en streaming de la vidéo achetée (pas de champ source vidéo/CDN sur le modèle `Video`).

API créateur (authentifié, `role=creator` requis — 403 sinon) :
- `POST /api/creator/videos` — `{ title, description?, category, poster_path?, duration_seconds?, price? }`, crée une vidéo en statut `pending` (`App\Domain\Creator\Actions\UploadVideo`). Prix par défaut 25 FCFA si omis.
- `GET /api/creator/videos` — liste paginée des vidéos du créateur connecté, **tous statuts confondus** (contrairement au catalogue public), via `CreatorVideoResource` qui expose `status`/`rejection_reason`.

Pas encore fait : inscription Créateur (le cahier des charges exige une pièce d'identité — pas construit, comptes créateur créés manuellement pour l'instant), upload du fichier vidéo lui-même (`poster_path` est une simple URL texte, pas de stockage/CDN).

Testé via `tests/Feature/` (`php artisan test`) — 29/29 au dernier passage, vérifié aussi manuellement contre PostgreSQL (upload créateur confirmé invisible du catalogue public tant que non validé).

Créer un utilisateur modérateur de test :

```bash
php artisan filament:make-user --name="..." --email="..." --password="..."
php artisan tinker --execute="\$u = App\Models\User::where('email','...')->first(); \$u->role = App\Enums\UserRole::Moderator; \$u->save();"
```

Extension `pdo_pgsql`, serveur PostgreSQL, utilisateur/base `streammali` en place et migrations par défaut exécutées (`users`, `sessions`, `jobs`, `cache`, ...).

Identifiants dans `.env` (`DB_DATABASE=streammali`, `DB_USERNAME=streammali`, `DB_PASSWORD=streammali`) — à changer avant tout déploiement au-delà du dev local.
