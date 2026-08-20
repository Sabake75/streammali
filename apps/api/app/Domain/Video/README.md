# Domain\Video

Namespace `App\Domain\Video`.

Responsabilités (cahier des charges §5.1, §8) :
- Métadonnées vidéo (titre, description, catégorie, jaquette, durée) et fiche catalogue.
- Statut de transcodage/diffusion (upload → transcodage → prêt), distinct du statut de modération (`Domain\Moderation`).
- Intégration avec le service de streaming/CDN.

## Sous-structure

- `Models/Video.php` — métadonnées + `source_status` (`VideoSourceStatus` : `not_started`/`processing`/`ready`/`failed`), `provider_video_id`, `playback_url`.
- `Contracts/VideoStorageGateway.php` — interface découplant la logique métier du fournisseur (`createUpload`, `fetchState`).
- `Gateways/CloudflareStreamGateway.php` — implémentation Cloudflare Stream, flux "direct creator upload" : le backend génère une URL d'upload à usage unique, le fichier part directement du client vers Cloudflare (jamais proxié par notre API), puis le statut est interrogé côté serveur (jamais un webhook entrant pris au mot, même logique que `Domain\Payment`). **Chemins/champs à vérifier contre la doc Cloudflare Stream** une fois un compte disponible — voir `config/services.php` (`services.cloudflare_stream`).
- `Actions/CreateVideoUpload.php` — démarre l'upload, passe `source_status` à `processing`.
- `Actions/RefreshVideoSourceStatus.php` — relit le statut auprès de Cloudflare, idempotent (no-op si pas en `processing`).
- `Data/` — DTOs (`VideoUploadInitiation`, `VideoSourceState`).

Endpoints créateur : `POST /api/creator/videos/{id}/source` (démarre l'upload), `GET /api/creator/videos/{id}/source` (rafraîchit/consulte le statut).

Une vidéo ne peut être **validée** par un modérateur que si `source_status = ready` (bouton désactivé sinon dans `/moderation/videos`) — inutile de valider une vidéo sans fichier exploitable.

L'URL de lecture (`playback_url`) n'est exposée dans `GET /api/videos/{id}` qu'aux viewers ayant acheté la vidéo **et** dont le fichier est prêt — c'est le "déverrouillage immédiat" du cahier des charges.

Testé (mock HTTP, aucun appel réseau réel vers Cloudflare) dans `tests/Feature/VideoUploadApiTest.php`, vérifié aussi contre PostgreSQL.

Pas encore fait : webhook Cloudflare (statut actuellement interrogé à la demande plutôt que poussé), transcodage de l'aperçu gratuit avant achat.
