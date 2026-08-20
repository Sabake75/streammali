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

Les dossiers `Domain/*` et `Filament/` ci-dessus ne sont pas encore créés — seule la structure Laravel par défaut est en place pour l'instant.

Extension `pdo_pgsql`, serveur PostgreSQL, utilisateur/base `streammali` en place et migrations par défaut exécutées (`users`, `sessions`, `jobs`, `cache`, ...).

Identifiants dans `.env` (`DB_DATABASE=streammali`, `DB_USERNAME=streammali`, `DB_PASSWORD=streammali`) — à changer avant tout déploiement au-delà du dev local.
