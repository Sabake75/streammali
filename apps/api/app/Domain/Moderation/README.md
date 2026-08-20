# Domain\Moderation

Namespace `App\Domain\Moderation`.

Responsabilités (cahier des charges §5.3) :
- File d'attente des vidéos soumises, visionnage avant validation.
- Validation ou refus d'une vidéo (motif obligatoire en cas de refus).
- Dépublication d'une vidéo en ligne en cas de signalement.
- Gestion des comptes (suspension, blocage, vérification d'identité).
- Messagerie créateur ↔ modération.
- Statistiques globales et rapports exportables.

## Structure

- `Enums/VideoStatus.php`, `Enums/AccountStatus.php` — statuts consommés par `Video`/`User` (`App\Domain\Video\Models\Video`, `App\Models\User`) et par le back-office Filament (`app/Filament/Resources/{Videos,Users}`), qui portent la logique de validation/refus/suspension directement dans leurs actions de table plutôt que via des Actions dédiées ici.
- `Models/Message.php` — un message dans la conversation d'un créateur avec la modération (`creator_id` = à qui appartient le fil, `sender_id` = qui a écrit — le créateur ou n'importe quel modérateur). Fil unique par créateur, pas de sujets/tickets séparés.
- `Actions/SendMessage.php` — crée un message ; utilisé à la fois par `Api\Creator\MessageController` (côté créateur) et par l'action Filament "Messagerie" sur `UsersTable` (côté modérateur, répond au créateur sélectionné).
- `Models/Report.php`, `Enums/ReportStatus.php` (`pending`/`dismissed`), `Actions/ReportVideo.php` — un viewer signale une vidéo (`Api\VideoReportController`, `POST /api/videos/{video}/report`). Pas de dépublication automatique : la dépublication réutilise l'action "Refuser" déjà présente sur `VideosTable` (elle repasse `status` à `rejected`, fonctionne aussi sur une vidéo déjà validée) — le signalement sert seulement à informer le modérateur, via un badge "Signalements" et une action listant les motifs, avec juste un "Marquer traités" pour clore le signalement une fois examiné.

Pas encore fait : statistiques/rapports exportables.
