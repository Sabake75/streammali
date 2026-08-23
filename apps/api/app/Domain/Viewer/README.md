# Domain\Viewer

Namespace `App\Domain\Viewer`.

Responsabilités (cahier des charges §5.2) :
- Navigation catalogue (recherche, filtres par catégorie/créateur/popularité).
- Achat à l'unité d'une vidéo et accès en streaming à la vidéo achetée.
- Historique d'achats, bibliothèque personnelle, favoris, recommandations.
- Notation et commentaires, signalement de problème.

## Sous-structure

- `Models/Favorite.php` — `user_id`, `video_id`, `unique(user_id, video_id)`.
- `Actions/ToggleFavorite.php` — bascule (crée si absent, supprime si présent), retourne le nouvel état.
- `Actions/GetRecommendedVideos.php` — volontairement non-ML : vidéos approuvées dans les catégories déjà achetées/favorites par l'utilisateur, hors vidéos déjà possédées, triées par popularité (`views_count`). Invité (ou utilisateur sans historique) → simplement les vidéos les plus vues.

Endpoints : `POST /api/videos/{id}/favorite` (toggle, authentifié), `GET /api/favorites` (bibliothèque de favoris, authentifié, paginé), `GET /api/videos/recommended` (public — invités compris, cf. `GetRecommendedVideos`). Attention à l'ordre des routes dans `routes/api.php` : `/videos/recommended` doit être déclarée avant `/videos/{video}`, sinon le paramètre wildcard capture "recommended" et casse le binding de modèle.

`Video::isFavoritedBy()` (même pattern que `isPurchasedBy()`), `VideoResource` expose `favorited` (auth uniquement, comme `purchased`).

Testé dans `tests/Feature/VideoFavoriteApiTest.php` et `VideoRecommendationApiTest.php`, vérifié aussi contre PostgreSQL.

Reste du cahier des charges pour ce domaine (inscription, achat, notation) déjà implémenté ailleurs : `Domain\Viewer\Actions\RegisterViewer` (inscription), `VideoPurchaseController`/`Domain\Payment` (achat), `Domain\Review` (notation/commentaires).
