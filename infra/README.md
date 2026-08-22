# Infra

Orchestration locale des services StreamMali : API Laravel, PostgreSQL, Redis, Web Next.js.

## Démarrer

```
docker compose -f infra/docker-compose.yml up --build
```

- **api** — `http://localhost:8000` (PHP 8.3, `composer install` + `migrate` au premier démarrage, `.env` de `apps/api` monté tel quel ; `DB_HOST`/`REDIS_HOST` sont surchargés vers les services `postgres`/`redis` du réseau Docker).
- **web** — `http://localhost:3000` (Next.js dev server, `npm ci` au premier démarrage ; `API_INTERNAL_URL` surchargé vers `http://api:8000/api` pour que le rendu SSR — catalogue, fiche vidéo — atteigne l'API depuis le conteneur, distinct de `NEXT_PUBLIC_API_URL` qui reste `localhost:8000` pour le navigateur).
- **postgres** — `localhost:5433` côté hôte (`5432` en interne), base `streammali` (user/password `streammali`). Port hôte décalé pour ne pas entrer en conflit avec un PostgreSQL déjà installé nativement (`5432`).
- **redis** — `localhost:6380` côté hôte (`6379` en interne), même raison.

Le code source de `apps/api` et `apps/web` est monté en bind mount (hot reload). `vendor/` est partagé avec l'hôte ; `node_modules/` reste dans un volume Docker nommé pour éviter les binaires natifs (ex. lightningcss de Tailwind v4) compilés pour la mauvaise plateforme.

Réservé au développement local — pas d'image de production, pas de configuration de déploiement à ce stade (voir `CLAUDE.md` à la racine, section « Prochaine étape »).
