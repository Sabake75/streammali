# Domain\Video

Namespace `App\Domain\Video`.

Responsabilités (cahier des charges §5.1, §8) :
- Métadonnées vidéo (titre, description, catégorie, jaquette, durée) et fiche catalogue.
- Statut de transcodage/diffusion (upload → transcodage → prêt), distinct du statut de modération (`Domain\Moderation`).
- Génération de l'aperçu gratuit (extrait avant achat).
- Intégration avec le service de streaming/CDN (upload, transcodage, URLs signées).

## Sous-structure prévue

- `Models/` — modèles Eloquent (ex. `Video`, `Category`).
- `Actions/` — actions métier unitaires (ex. `UploadVideo`, `MarkVideoReady`).
- `Data/` — DTOs d'entrée/sortie des endpoints API.

Rien n'est encore implémenté ici — dossier créé pour recevoir ce code au fur et à mesure des tickets.
