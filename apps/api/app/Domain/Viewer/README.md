# Domain\Viewer

Namespace `App\Domain\Viewer`.

Responsabilités (cahier des charges §5.2) :
- Navigation catalogue (recherche, filtres par catégorie/créateur/popularité).
- Achat à l'unité d'une vidéo et accès en streaming à la vidéo achetée.
- Historique d'achats, bibliothèque personnelle, favoris, recommandations.
- Notation et commentaires, signalement de problème.

## Sous-structure prévue

- `Models/` — modèles Eloquent propres au viewer (ex. favori, achat, note).
- `Actions/` — actions métier unitaires (ex. `PurchaseVideo`, `AddToFavorites`).
- `Data/` — DTOs d'entrée/sortie des endpoints API.

Rien n'est encore implémenté ici — dossier créé pour recevoir ce code au fur et à mesure des tickets.
