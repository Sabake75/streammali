# Domain\Moderation

Namespace `App\Domain\Moderation`.

Responsabilités (cahier des charges §5.3) :
- File d'attente des vidéos soumises, visionnage avant validation.
- Validation ou refus d'une vidéo (motif obligatoire en cas de refus).
- Dépublication d'une vidéo en ligne en cas de signalement.
- Gestion des comptes (suspension, blocage, vérification d'identité).
- Statistiques globales et rapports exportables.

## Sous-structure prévue

- `Models/` — modèles Eloquent propres à la modération (ex. décision de modération, signalement).
- `Actions/` — actions métier unitaires (ex. `ApproveVideo`, `RejectVideo`, `SuspendAccount`).
- `Data/` — DTOs d'entrée/sortie des endpoints API.

Rien n'est encore implémenté ici — dossier créé pour recevoir ce code au fur et à mesure des tickets. Le back-office Filament (`app/Filament`) consommera ces actions plutôt que de dupliquer la logique.
