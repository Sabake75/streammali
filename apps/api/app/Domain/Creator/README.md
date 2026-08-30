# Domain\Creator

Namespace `App\Domain\Creator`.

Responsabilités (cahier des charges §5.1) :
- Inscription avec pièce d'identité.
- Upload de vidéos (titre, description, catégorie, jaquette, durée) et soumission à la modération.
- Suivi de statut d'une vidéo (en attente / validé / refusé).
- Fixation/confirmation du prix de vente (100 FCFA par défaut).
- Demandes de retrait vers Mobile Money.
- Statistiques par vidéo (vues, achats, revenus) et évolution du revenu.

## Sous-structure

- `Actions/RegisterCreator.php` — crée un compte `role=creator` + stocke la pièce d'identité sur le disque privé `local` (`storage/app/private`, jamais servi publiquement — voir `config/filesystems.php`).
- `Actions/UploadVideo.php` — crée une vidéo en statut `pending`.
- `Actions/GetCreatorStats.php` — agrège, par créateur : vues/achats/revenus par vidéo (revenus via `LedgerEntry`, achats via `Payment::status=Succeeded`) et un historique de revenu sur 14 jours (`GET /api/creator/stats`, 403 si appelant non-créateur).

Inscription : `POST /api/register/creator` (`multipart/form-data` : `name`, `phone`, `password`, `identity_document` — jpg/jpeg/png/pdf, 10 Mo max). Renvoie un token Sanctum comme `/api/register`.

La pièce d'identité n'est consultable que par un modérateur connecté, via `GET /moderation/creators/{user}/identity-document` (`routes/web.php`, guard session — même authentification que le panneau Filament). Bouton "Pièce d'identité" dans `/moderation/users` pour l'ouvrir. La colonne `users.identity_document_path` est masquée de toute sérialisation JSON (`#[Hidden]` sur `App\Models\User`).

La vérification d'identité elle-même (`identity_verified_at`, action "Vérifier l'identité" dans `Domain\Moderation`) reste un geste manuel du modérateur après consultation du document — pas d'OCR ni de vérification automatisée.

Testé dans `tests/Feature/CreatorRegistrationApiTest.php`, vérifié aussi contre PostgreSQL et le vrai disque (upload multipart réel, fichier confirmé privé).

Passage d'un compte viewer existant en créateur : `Actions/UpgradeToCreator.php` (`PUT` du même `User` — `role`, `identity_document_path`, `terms_accepted_at` — pas de nouvel enregistrement). `POST /api/creator/upgrade` (authentifié, `identity_document` + `terms_accepted`, sans `name`/`phone`/`password` puisque déjà connus). Corrige un vrai trou : avant ça, un viewer connecté qui cliquait "Inscription créateur" tombait sur le formulaire complet, qui échouait systématiquement sur la contrainte `unique` du téléphone (déjà pris par son propre compte) — le seul moyen de devenir créateur était de créer un second compte déconnecté avec un autre numéro. Web (`RegisterCreatorPageClient.tsx`) et mobile (`register_creator_screen.dart`) détectent une session existante et affichent ce formulaire court à la place du formulaire complet. 409 si le compte est déjà créateur, 403 si modérateur. Testé dans `tests/Feature/CreatorRegistrationApiTest.php`, vérifié aussi en conditions réelles (navigateur, vrai upload) : même utilisateur avant/après, un seul enregistrement en base.

## Vues et statistiques

`videos.views_count` est incrémenté par `POST /api/videos/{video}/view` (`VideoCatalogController::view`), volontairement **séparé** de `GET /api/videos/{video}` (`show()`) : côté web, cette dernière est mise en cache par Next.js (`revalidate: 60`), donc un incrément placé dans `show()` serait silencieusement sauté pour toute requête servie depuis le cache. Le endpoint `/view` est appelé côté client uniquement (composant `RecordView` sur web, `initState()` sur mobile), jamais depuis un rendu serveur/caché. Testé dans `tests/Feature/CreatorStatsApiTest.php`.
