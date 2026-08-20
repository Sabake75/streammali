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

Table `videos` + modèle `App\Domain\Video\Models\Video` (catégorie `VideoCategory`, statut de modération `VideoStatus`). Ressource Filament `app/Filament/Resources/Videos/` : file d'attente avec filtres statut/catégorie et actions **Valider** / **Refuser** (motif obligatoire), conformes au cahier des charges §5.3. Testé via `tests/Feature/ModerationVideoResourceTest.php` (`php artisan test`).

Créer un utilisateur modérateur de test :

```bash
php artisan filament:make-user --name="..." --email="..." --password="..."
php artisan tinker --execute="\$u = App\Models\User::where('email','...')->first(); \$u->role = App\Enums\UserRole::Moderator; \$u->save();"
```

Extension `pdo_pgsql`, serveur PostgreSQL, utilisateur/base `streammali` en place et migrations par défaut exécutées (`users`, `sessions`, `jobs`, `cache`, ...).

Identifiants dans `.env` (`DB_DATABASE=streammali`, `DB_USERNAME=streammali`, `DB_PASSWORD=streammali`) — à changer avant tout déploiement au-delà du dev local.
