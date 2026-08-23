# Déploiement (Render + Vercel)

Ce document couvre le déploiement en production/staging — pour le dev local, voir `infra/README.md`.

Contrairement au Dockerfile de dev, `apps/api/Dockerfile.prod` a été vérifié en local (build + connexion Postgres réelle + Filament) — voir historique de conversation. `render.yaml`, en revanche, n'a **jamais été testé contre un vrai compte Render** (même limite que les passerelles Orange Money/Cloudflare Stream) : la structure suit la doc Render documentée, mais peut nécessiter de petits ajustements au premier déploiement.

## 1. Cloudflare R2 (stockage des pièces d'identité)

Les pièces d'identité des créateurs ne peuvent pas rester sur le disque du conteneur Render (éphémère, effacé à chaque déploiement — voir `apps/api/app/Domain/Creator/Actions/RegisterCreator.php`).

1. Cloudflare Dashboard → R2 → créer un bucket (ex. `streammali-private`).
2. R2 → "Manage API Tokens" → créer un token avec accès lecture/écriture sur ce bucket.
3. Noter : Account ID, Access Key ID, Secret Access Key, et l'endpoint S3 (`https://<account-id>.r2.cloudflarestorage.com`).

Ces valeurs vont dans `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_BUCKET`, `AWS_ENDPOINT` sur Render (étape 3).

## 2. Render (API + PostgreSQL)

1. Dashboard Render → New → Blueprint → connecter le repo GitHub `Sabake75/streammali`. Render détecte `render.yaml` à la racine.
2. Confirmer le plan "Starter" pour la base PostgreSQL (payant, ~7$/mois — décision déjà actée, évite l'expiration à 90 jours du plan gratuit).
3. Une fois les services créés, remplir dans le dashboard Render (Environment) toutes les variables marquées `sync: false` dans `render.yaml` :
   - `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_BUCKET`, `AWS_ENDPOINT` (étape 1, R2).
   - `ORANGE_MONEY_CLIENT_ID`, `ORANGE_MONEY_CLIENT_SECRET`, `ORANGE_MONEY_MERCHANT_KEY`, `ORANGE_MONEY_RETURN_URL`, `ORANGE_MONEY_CANCEL_URL`, `ORANGE_MONEY_NOTIF_URL` — vides tant que le compte marchand Orange Developer Center n'existe pas (Phase 7 de la feuille de route).
   - `CLOUDFLARE_STREAM_ACCOUNT_ID`, `CLOUDFLARE_STREAM_API_TOKEN` — idem, en attente du compte Cloudflare Stream.
4. Après le premier déploiement, Render assigne une URL (`https://streammali-api-xxxx.onrender.com`) : la reporter dans la variable `APP_URL`, puis redéployer manuellement.
5. `CORS_ALLOWED_ORIGINS` : à renseigner une fois l'URL Vercel connue (étape 3).

Les migrations tournent automatiquement via le "Pre-Deploy Command" (`php artisan migrate --force`), avant que le nouveau conteneur ne prenne le trafic.

## 3. Vercel (web Next.js)

1. Dashboard Vercel → Add New → Project → importer le repo GitHub.
2. Root Directory : `apps/web`. Framework détecté automatiquement (Next.js).
3. Variables d'environnement :
   - `NEXT_PUBLIC_API_URL` = `https://<url-render>/api`
4. Déployer. Reporter l'URL assignée par Vercel dans `CORS_ALLOWED_ORIGINS` côté Render (étape 2), puis redéployer l'API.

Déploiement automatique ensuite à chaque push sur `master` (+ preview par pull request), nativement, sans job GitHub Actions dédié.

## 4. Mobile

La CI (`.github/workflows/ci.yml`) construit un APK release à chaque push sur `master` (signé avec la config de debug — suffisant pour tester, pas pour le Play Store) et le publie comme artefact GitHub Actions téléchargeable, 30 jours de rétention. Publication sur le Play Store hors scope pour l'instant (compte développeur payant, externe).

## 5. Recommandé (à faire manuellement)

GitHub → Settings → Branches → règle de protection sur `master` exigeant que les 3 jobs CI (`API`, `Web`, `Mobile`) passent avant un merge.
