# Domain\Video

Namespace `App\Domain\Video`.

Responsabilités (cahier des charges §5.1, §8) :
- Métadonnées vidéo (titre, description, catégorie, jaquette, durée) et fiche catalogue.
- Statut de transcodage/diffusion (upload → transcodage → prêt), distinct du statut de modération (`Domain\Moderation`).
- Intégration avec le service de streaming/CDN.

## Sous-structure

- `Models/Video.php` — métadonnées + `source_status` (`VideoSourceStatus` : `not_started`/`processing`/`ready`/`failed`), `provider_video_id`, `playback_url`. `category()` : `BelongsTo` vers `Models/Category.php`.
- `Models/Category.php` — catégories gérées par le modérateur (`app/Filament/Resources/Categories`), plus un enum figé. `slug` + `label`, `videos()` : `HasMany`.
- `Contracts/VideoStorageGateway.php` — interface découplant la logique métier du fournisseur (`createUpload`, `fetchState`).
- `Gateways/CloudflareStreamGateway.php` — implémentation Cloudflare Stream, flux "direct creator upload" : le backend génère une URL d'upload à usage unique, le fichier part directement du client vers Cloudflare (jamais proxié par notre API). Statut interrogeable à la demande ou poussé par webhook (`CloudflareStreamWebhookController`, qui ne fait jamais confiance au payload entrant — même logique que `Domain\Payment`). **Chemins/champs à vérifier contre la doc Cloudflare Stream** une fois un compte disponible — voir `config/services.php` (`services.cloudflare_stream`).
- `Actions/CreateVideoUpload.php` — démarre l'upload, passe `source_status` à `processing`.
- `Actions/RefreshVideoSourceStatus.php` — relit le statut auprès de Cloudflare, idempotent (no-op si pas en `processing`).
- `Data/` — DTOs (`VideoUploadInitiation`, `VideoSourceState`).

Endpoints créateur : `POST /api/creator/videos/{id}/source` (démarre l'upload), `GET /api/creator/videos/{id}/source` (rafraîchit/consulte le statut). Endpoint public : `GET /api/categories`.

Une vidéo ne peut être **validée** par un modérateur que si `source_status = ready` (bouton désactivé sinon dans `/moderation/videos`) — inutile de valider une vidéo sans fichier exploitable.

L'URL de lecture (`playback_url`) n'est exposée dans `GET /api/videos/{id}` qu'aux viewers ayant acheté la vidéo **et** dont le fichier est prêt — c'est le "déverrouillage immédiat" du cahier des charges.

Catégories : anciennement un enum PHP figé (`film`/`clip`/`sketch`/`series`), migré en table `categories` gérée par le modérateur. Migration en deux étapes déployables séparément (`2026_08_23_090100_add_category_id_to_videos_table` puis `..._090200_finalize_category_id_on_videos_table`) — la première renomme l'ancienne colonne `category` en `category_legacy` plutôt que de la laisser en place, sans quoi elle masque silencieusement la relation Eloquent `category()` du même nom (l'attribut brut de la table est résolu avant la méthode de relation). Nécessite `doctrine/dbal` (ajouté en dépendance) pour les `->change()` de colonne, y compris sous SQLite (tests).

Testé (mock HTTP, aucun appel réseau réel vers Cloudflare) dans `tests/Feature/VideoUploadApiTest.php` et `tests/Feature/CloudflareStreamWebhookTest.php`, vérifié aussi contre PostgreSQL (y compris les deux migrations de catégories, séparément).

Pas encore fait : aperçu gratuit avant achat (et donc premier lecteur vidéo côté web/mobile — aucun n'existe encore).
