# Domain\Review

Namespace `App\Domain\Review`.

Responsabilité : notation/commentaires sur une vidéo, réservés aux viewers l'ayant achetée (cahier des charges — fonctionnalité non prioritaire pour le MVP).

## Sous-structure

- `Models/Review.php` — `video_id`, `user_id`, `rating` (1-5), `comment` (nullable). `unique(video_id, user_id)` en base : un avis par utilisateur et par vidéo.
- `Actions/SubmitReview.php` — `updateOrCreate` sur `(video_id, user_id)` : resoumettre un avis remplace l'existant plutôt que d'en créer un doublon.

Endpoints : `GET /api/videos/{id}/reviews` (public, paginé), `POST /api/videos/{id}/reviews` (authentifié **et** avoir acheté la vidéo — `Video::isPurchasedBy()`, promu depuis `Http\Resources\VideoResource` pour être réutilisé ici). 403 si pas acheté, 422 si note hors 1-5.

`GET /api/videos` et `GET /api/videos/{id}` exposent `average_rating`/`reviews_count` (`withAvg`/`withCount` côté `VideoCatalogController`, jamais recalculés en boucle par vidéo).

Pas d'interface de modération des avis côté Filament — pas dans le cahier des charges, à ajouter si un besoin se présente (signalement d'avis abusif, etc.).

Testé dans `tests/Feature/VideoReviewApiTest.php` (achat requis, resoumission remplace, note hors bornes rejetée, liste publique, moyenne/compte exposés), vérifié aussi contre PostgreSQL.
