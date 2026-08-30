# Domain\Video

Namespace `App\Domain\Video`.

Responsabilités (cahier des charges §5.1, §8) :
- Métadonnées vidéo (titre, description, catégorie, jaquette, durée) et fiche catalogue.
- Statut de transcodage/diffusion (upload → transcodage → prêt), distinct du statut de modération (`Domain\Moderation`).
- Intégration avec le service de streaming/CDN.

## Sous-structure

- `Models/Video.php` — métadonnées + `source_status` (`VideoSourceStatus` : `not_started`/`processing`/`ready`/`failed`), `provider_video_id`, `playback_url`, `preview_provider_video_id`, `preview_playback_url`. `category()` : `BelongsTo` vers `Models/Category.php`.
- `Models/Category.php` — catégories gérées par le modérateur (`app/Filament/Resources/Categories`). `slug` + `label`, `videos()` : `HasMany`.
- `Contracts/VideoStorageGateway.php` — interface découplant la logique métier du fournisseur (`createUpload`, `fetchState`, `createClip`).
- `Gateways/CloudflareStreamGateway.php` — implémentation Cloudflare Stream, flux "direct creator upload" : le backend génère une URL d'upload à usage unique, le fichier part directement du client vers Cloudflare (jamais proxié par notre API). Statut interrogeable à la demande ou poussé par webhook (`CloudflareStreamWebhookController`, qui ne fait jamais confiance au payload entrant — même logique que `Domain\Payment`). `createClip()` : Clip API Cloudflare, dérive un aperçu court (45s par défaut, `CLOUDFLARE_STREAM_PREVIEW_DURATION_SECONDS`) avec son propre `uid`/URL de lecture, distinct de l'asset complet. **Chemins/champs à vérifier contre la doc Cloudflare Stream** une fois un compte disponible — voir `config/services.php` (`services.cloudflare_stream`).
- `Actions/CreateVideoUpload.php` — démarre l'upload, passe `source_status` à `processing`.
- `Actions/RefreshVideoSourceStatus.php` — relit le statut auprès de Cloudflare, idempotent (no-op si pas en `processing`). Renseigne aussi `poster_path` (miniature) si pas déjà défini — voir plus bas.
- `Actions/CreatePreviewClip.php` — crée l'aperçu gratuit via `createClip()`, idempotent (no-op si déjà fait). Appelé uniquement depuis le webhook (pas depuis le rafraîchissement à la demande, pour ne pas recréer un clip à chaque poll manuel) — donc toute vidéo passée `ready` **avant** que le webhook Cloudflare Stream soit réellement enregistré sur le dashboard Cloudflare (URL de callback pointant vers l'API déployée) reste bloquée sans aperçu, indéfiniment, sans retry automatique. Rencontré en conditions réelles au premier déploiement (webhook jamais enregistré, deux vidéos déjà validées sans aperçu). Rattrapable avec `php artisan videos:create-missing-previews` (`app/Console/Commands/CreateMissingPreviewClips.php`, optionnellement `{video}` pour une seule vidéo) — sélectionne les vidéos `ready` sans `preview_provider_video_id` **et/ou** sans `poster_path`, sûr à relancer (mêmes gardes d'idempotence).
- `Data/` — DTOs (`VideoUploadInitiation`, `VideoSourceState`, `VideoPreviewState`).

Endpoints créateur : `POST /api/creator/videos/{id}/source` (démarre l'upload), `GET /api/creator/videos/{id}/source` (rafraîchit/consulte le statut). Endpoint public : `GET /api/categories`.

Une vidéo ne peut être **validée** par un modérateur que si `source_status = ready` (bouton désactivé sinon dans `/moderation/videos`) — inutile de valider une vidéo sans fichier exploitable.

Miniature (`poster_path`) : pas d'upload séparé côté créateur — Cloudflare Stream génère automatiquement une image par vidéo (`thumbnail` dans la même réponse que `fetchState()`, aucun appel en plus), servie telle quelle (`poster_path` stocke l'URL Cloudflare complète, pas un chemin relatif — cohérent avec la validation `POST /api/creator/videos` qui accepte déjà une chaîne libre). `RefreshVideoSourceStatus` ne l'écrase jamais si déjà défini, pour laisser la place à un choix manuel futur (créateur/modérateur) sans qu'un rafraîchissement le remplace silencieusement.

Le modérateur peut visionner la vidéo complète (pas juste l'aperçu 45s) avant de décider : action "Visionner" (`VideosTable::playerEmbedUrl()`, iframe Cloudflare Stream sur `playback_url`, pas `preview_playback_url`) — en bouton direct sur la ligne plutôt que dans le sous-menu "Actions", puisque c'est le premier geste attendu avant toute décision de modération, pas une action secondaire.

Pas de suppression de vidéo côté modération (ni `DeleteBulkAction` sur la liste, ni `DeleteAction` sur la page d'édition — retirés délibérément) : l'outil de dépublication d'un modérateur est "Refuser" (`status = rejected`), qui ne touche à rien d'autre. Un vrai `DELETE` sur `videos` cascaderait sur `payments.video_id` (`cascadeOnDelete` dans la migration) et détruirait définitivement les paiements/achats réels associés — jamais un effet de bord qu'une action de modération devrait produire.

Colonne "En vedette" (`VideosTable`) : icône étoile pleine/vide plutôt que les icônes booléennes par défaut de Filament (coche/croix verte/rouge, qui lisent "OK vs. erreur" — pas adapté à un statut simplement désactivé par défaut). La colonne a aussi sa propre `->action()` pour basculer directement au clic — sans ça, cliquer sur l'icône ne fait que déclencher le comportement par défaut de Filament (clic sur la ligne → page d'édition), pas la bascule attendue ; rencontré en conditions réelles. Guard : clic sans effet (avec notification) si la vidéo n'est pas encore validée, cohérent avec le fait que l'action "Mettre en avant" du menu reste invisible dans ce cas.

`->recordUrl(null)` sur la table entière, pour la même raison en pire : par défaut Filament ouvre la page d'édition au clic sur **n'importe quelle** cellule sans `->action()`/`->url()` propre (Titre, Statut, Signalements...) — un modérateur cliquant sur le badge "Signalements" pour voir le détail atterrissait sur le formulaire d'édition. "Modifier" reste disponible explicitement dans le menu "..." pour qui veut vraiment éditer.

L'URL de lecture (`playback_url`) n'est exposée dans `GET /api/videos/{id}` qu'aux viewers ayant acheté la vidéo **et** dont le fichier est prêt — c'est le "déverrouillage immédiat" du cahier des charges. `preview_playback_url`, lui, est toujours exposé (même aux invités) une fois l'aperçu créé — c'est un clip court à part, pas l'asset complet.

Premier lecteur vidéo de l'app (web + mobile), construit avec l'aperçu gratuit puisqu'il fallait de toute façon en créer un — corrige au passage l'absence de lecture pour les vidéos déjà achetées. Web : `<video>` natif + `hls.js` (Safari lit le HLS nativement, les autres navigateurs non). Mobile : `video_player` + `chewie`. Ni l'un ni l'autre en autoplay — bouton play natif du lecteur = tap-to-play, cohérent avec la contrainte "faible consommation de données" du cahier des charges.

Catégories : anciennement un enum PHP figé (`film`/`clip`/`sketch`/`series`), migré en table `categories` gérée par le modérateur. Migration en deux étapes déployables séparément (`2026_08_23_090100_add_category_id_to_videos_table` puis `..._090200_finalize_category_id_on_videos_table`) — la première renomme l'ancienne colonne `category` en `category_legacy` plutôt que de la laisser en place, sans quoi elle masque silencieusement la relation Eloquent `category()` du même nom (l'attribut brut de la table est résolu avant la méthode de relation). Nécessite `doctrine/dbal` (ajouté en dépendance) pour les `->change()` de colonne, y compris sous SQLite (tests).

Testé (mock HTTP, aucun appel réseau réel vers Cloudflare) dans `tests/Feature/VideoUploadApiTest.php` et `tests/Feature/CloudflareStreamWebhookTest.php`, vérifié aussi contre PostgreSQL (migrations catégories et aperçu).

Mise en avant : `videos.featured_at` (nullable) + `Video::scopeFeatured()` (triée par date de mise en avant la plus récente). Bascule via l'action Filament "Mettre en avant"/"Retirer" sur `VideosTable` (visible seulement pour une vidéo déjà validée — même pattern que `verify_identity` sur `UsersTable`). `GET /api/videos/featured`, public.

Toutes les fonctionnalités non prioritaires du cahier des charges sont maintenant faites (aperçu gratuit, notation/commentaires — `Domain\Review` —, favoris/recommandations — `Domain\Viewer` —, mise en avant). Il ne reste que le passage en production (voir `CLAUDE.md` à la racine).
