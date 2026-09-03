# Déploiement (Render)

Ce document couvre le déploiement en production/staging — pour le dev local, voir `infra/README.md`.

Contrairement au Dockerfile de dev, `apps/api/Dockerfile.prod` a été vérifié en local (build + connexion Postgres réelle + Filament) — voir historique de conversation. `render.yaml`, en revanche, n'a **jamais été testé contre un vrai compte Render** (même limite que les passerelles Orange Money/Cloudflare Stream) : la structure suit la doc Render documentée, mais peut nécessiter de petits ajustements au premier déploiement.

Web déployé sur Render aussi (pas Vercel — bloqué sur un 404 persistant sur `/` en production malgré un `app/page.tsx` bien présent et un build local systématiquement correct ; `next start` gère nativement `$PORT`, contrairement à FrankenPHP, donc rien à bricoler côté API).

Pièges déjà rencontrés lors du premier vrai déploiement Render (2026-08-30) :
- Le plan `starter` pour la base PostgreSQL n'existe plus pour les nouvelles instances (renommé `basic-256mb`, même tarif ~7$/mois) — déjà corrigé dans `render.yaml`.
- `$PORT` : le `CMD` du Dockerfile écoutait en dur sur `:80` alors que Render route le trafic vers son propre `$PORT` (10000 par défaut), pas vers 80. Corrigé en laissant `docker/entrypoint.prod.sh` (un script shell, donc `$PORT` s'y expanse correctement — impossible dans le `CMD` en forme exec du Dockerfile) ajouter `--listen ":${PORT:-80}"` à la commande, avec repli sur `:80` hors Render.
- `/usr/local/bin/entrypoint.prod.sh: exec: frankenphp: Operation not permitted` **au lancement du binaire lui-même**, distinct du point précédent (pas un problème de port : le message vient de l'échec du `exec` shell avant même que frankenphp n'essaie d'écouter quoi que ce soit). Cause réelle : l'image de base donne à `frankenphp` la capability Linux `cap_net_bind_service` via `setcap` (`getcap /usr/local/bin/frankenphp` → `cap_net_bind_service=ep`), pour qu'il puisse bind `:80` sans tourner en root. Le runtime sandboxé de Render (gVisor) refuse d'`exec` un binaire porteur d'une capability de ce type — même en root — et renvoie EPERM. Ce conteneur ne descend jamais vers un utilisateur non-root (pas de `USER` dans le Dockerfile, pas de `su`/`gosu` dans l'entrypoint), donc la capability n'est de toute façon pas nécessaire : root peut déjà bind n'importe quel port. Corrigé avec `RUN setcap -r /usr/local/bin/frankenphp` dans `Dockerfile.prod` (juste après l'installation des extensions PHP — `setcap`/`getcap` sont déjà présents sur l'image de base, aucune dépendance à ajouter). Vérifié en local : `getcap` ne renvoie plus rien après le build, et le conteneur démarre toujours correctement (`docker run -e PORT=10000 ...` → `/up` répond 200, log `"Caddy serving PHP app on :10000"`) — impossible de reproduire l'EPERM lui-même en local (Docker local n'utilise pas gVisor), donc cette partie n'est confirmée que par les logs Render eux-mêmes après redéploiement.
- `Unsupported cipher or incorrect key length` sur `/moderation` uniquement (le reste de l'API — auth Sanctum stateless, catalogue, achats — fonctionnait normalement) : `APP_KEY` avec `generateValue: true` produisait une valeur que l'`Encrypter` de Laravel rejetait — le générateur générique de Render n'est pas au courant du format que Laravel attend (préfixe `base64:` + clé de 32 octets pour AES-256-CBC). Seul `/moderation` (session/cookie chiffrés, Filament) touche réellement l'encrypter ; Sanctum n'en a pas besoin, d'où l'écart. Corrigé : `APP_KEY` en `sync: false`, à définir manuellement avec une vraie clé Laravel (voir étape 2.3 plus bas) plutôt que de laisser Render la générer.

## 1. Cloudflare R2 (stockage des pièces d'identité)

Les pièces d'identité des créateurs ne peuvent pas rester sur le disque du conteneur Render (éphémère, effacé à chaque déploiement — voir `apps/api/app/Domain/Creator/Actions/RegisterCreator.php`).

1. Cloudflare Dashboard → R2 → créer un bucket (ex. `streammali-private`).
2. R2 → "Manage API Tokens" → créer un token avec accès lecture/écriture sur ce bucket.
3. Noter : Account ID, Access Key ID, Secret Access Key, et l'endpoint S3 (`https://<account-id>.r2.cloudflarestorage.com`).

Ces valeurs vont dans `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_BUCKET`, `AWS_ENDPOINT` sur Render (étape 3).

## 2. Render (API + web + PostgreSQL)

`render.yaml` déclare trois ressources : la base Postgres, `streammali-api` (Docker/FrankenPHP) et `streammali-web` (Next.js, runtime Node natif — `buildCommand: npm install && npm run build`, `startCommand: npm run start`, `rootDir: apps/web`).

1. Dashboard Render → New → Blueprint → connecter le repo GitHub `Sabake75/streammali`. Render détecte `render.yaml` à la racine et propose les trois ressources.
2. Confirmer le plan `basic-256mb` pour la base PostgreSQL — Render a renommé/retiré l'ancien plan "Starter" pour les nouvelles instances, `basic-256mb` (payant, ~7$/mois) est désormais le plus petit disponible ; décision déjà actée d'éviter le plan gratuit (expiration à 90 jours).
3. Une fois les services créés, remplir dans le dashboard Render (Environment) toutes les variables marquées `sync: false` dans `render.yaml`, pour `streammali-api` :
   - `APP_KEY` : lancer `php artisan key:generate --show` **dans le Shell Render de ce service** (pour que la clé ne transite jamais ailleurs) et coller la valeur `base64:...` obtenue — ne pas laisser Render en générer une lui-même (voir piège ci-dessus).
   - `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_BUCKET`, `AWS_ENDPOINT` (étape 1, R2).
   - `ORANGE_MONEY_CLIENT_ID`, `ORANGE_MONEY_CLIENT_SECRET`, `ORANGE_MONEY_MERCHANT_KEY`, `ORANGE_MONEY_RETURN_URL`, `ORANGE_MONEY_CANCEL_URL`, `ORANGE_MONEY_NOTIF_URL` — gateway de paiement actif (voir `AppServiceProvider`), compte sandbox "Orange Money WebPay Dev" du Developer Center, vérifié en réel (voir `CLAUDE.md`). `merchant_key` sans le point final affiché sur le Developer Center. `ORANGE_MONEY_COUNTRY` vaut `dev` (hardcodé dans `render.yaml`, pas `sync: false`) tant que seul ce produit sandbox est approuvé.
   - `PAYDUNYA_MASTER_KEY`, `PAYDUNYA_PRIVATE_KEY`, `PAYDUNYA_PUBLIC_KEY`, `PAYDUNYA_TOKEN` — gateway de repli, bloqué par son KYC marchand (voir `CLAUDE.md`).
   - `CLOUDFLARE_STREAM_ACCOUNT_ID`, `CLOUDFLARE_STREAM_API_TOKEN`.
4. Après le premier déploiement de chaque service, Render assigne une URL `*.onrender.com` :
   - Côté `streammali-api` : la reporter dans `APP_URL`, puis redéployer manuellement.
   - Côté `streammali-web` : la reporter dans `NEXT_PUBLIC_SITE_URL`, puis redéployer manuellement.
5. Une fois les deux URLs connues, revenir sur `streammali-api` pour renseigner `CORS_ALLOWED_ORIGINS` (l'URL de `streammali-web`) et `PAYDUNYA_RETURN_URL`/`PAYDUNYA_CANCEL_URL`/`PAYDUNYA_CALLBACK_URL`, puis redéployer.

Les migrations tournent automatiquement via le "Pre-Deploy Command" (`php artisan migrate --force`) sur `streammali-api`, avant que le nouveau conteneur ne prenne le trafic. Déploiement automatique ensuite à chaque push sur `master` pour les deux services, nativement, sans job GitHub Actions dédié.

## 3. Mobile

La CI (`.github/workflows/ci.yml`) construit un APK release à chaque push sur `master` (signé avec la config de debug — suffisant pour tester, pas pour le Play Store) et le publie comme artefact GitHub Actions téléchargeable, 30 jours de rétention. Publication sur le Play Store hors scope pour l'instant (compte développeur payant, externe).

## 4. Recommandé (à faire manuellement)

GitHub → Settings → Branches → règle de protection sur `master` exigeant que les 3 jobs CI (`API`, `Web`, `Mobile`) passent avant un merge.
