# Domain\Moderation

Namespace `App\Domain\Moderation`.

Responsabilités (cahier des charges §5.3) :
- File d'attente des vidéos soumises, visionnage avant validation.
- Validation ou refus d'une vidéo (motif obligatoire en cas de refus).
- Dépublication d'une vidéo en ligne en cas de signalement.
- Gestion des comptes (suspension, blocage, vérification d'identité).
- Messagerie créateur/viewer ↔ modération (canal de support unique — voir plus bas).
- Statistiques globales et rapports exportables.

## Structure

- `Enums/VideoStatus.php`, `Enums/AccountStatus.php` — statuts consommés par `Video`/`User` (`App\Domain\Video\Models\Video`, `App\Models\User`) et par le back-office Filament (`app/Filament/Resources/{Videos,Users}`), qui portent la logique de validation/refus/suspension directement dans leurs actions de table plutôt que via des Actions dédiées ici.
- `Models/Message.php` — un message dans la conversation d'un utilisateur (créateur **ou viewer**, tout compte non-modérateur) avec la modération (`user_id` = à qui appartient le fil, `sender_id` = qui a écrit). Fil unique par utilisateur, pas de sujets/tickets séparés. Colonne renommée de `creator_id` à `user_id` (2026-09-08) quand le canal de support s'est ouvert aux viewers (P2 de la feuille de route produit, plus seulement les créateurs).
- `Actions/SendMessage.php` — crée un message ; utilisé à la fois par `Api\MessageController` (`GET`/`POST /api/messages`, ouvert à tout compte non-modérateur) et par l'action Filament "Messagerie" sur `UsersTable` (côté modérateur, répond à l'utilisateur sélectionné — visible pour tout rôle sauf modérateur).
- `Models/Report.php`, `Enums/ReportStatus.php` (`pending`/`dismissed`), `Actions/ReportVideo.php` — un viewer signale une vidéo (`Api\VideoReportController`, `POST /api/videos/{video}/report`). Pas de dépublication automatique : la dépublication réutilise l'action "Refuser" déjà présente sur `VideosTable` (elle repasse `status` à `rejected`, fonctionne aussi sur une vidéo déjà validée) — le signalement sert seulement à informer le modérateur, via un badge "Signalements" et une action listant les motifs, avec juste un "Marquer traités" pour clore le signalement une fois examiné.

Pas encore fait : statistiques/rapports exportables.

`UsersTable`/`EditUser` (`app/Filament/Resources/Users`) : mêmes correctifs que `VideosTable`/`EditVideo` (voir `Domain\Video\README`), jamais appliqués ici jusqu'à un audit QA en production (2026-08-31) — trouvé en cliquant sur le nom/téléphone/icône "Identité vérifiée" d'un compte, qui ouvrait le formulaire d'édition au lieu de rien faire (`->recordUrl(null)` manquant). Plus grave : `DeleteBulkAction` (liste) et `DeleteAction` (page d'édition) étaient toujours présents, alors qu'un `DELETE` sur `users` cascade sur `videos`/`payments`/`payouts`/`ledger_entries`/`messages`/`reviews`/`favorites`/`reports` — un modérateur supprimant un compte par erreur aurait détruit tout l'historique financier réel du créateur associé. Suspendre/bloquer (déjà dans `UsersTable`) restent les seuls outils de modération d'un compte, jamais une suppression.
