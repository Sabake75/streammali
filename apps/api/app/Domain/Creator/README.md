# Domain\Creator

Namespace `App\Domain\Creator`.

Responsabilités (cahier des charges §5.1) :
- Upload de vidéos (titre, description, catégorie, jaquette, durée) et soumission à la modération.
- Suivi de statut d'une vidéo (en attente / validé / refusé).
- Fixation/confirmation du prix de vente (25 FCFA par défaut).
- Statistiques (vues, achats, revenus) et demandes de retrait vers Mobile Money.
- Gestion du catalogue personnel.

## Sous-structure prévue

- `Models/` — modèles Eloquent propres au créateur (ex. profil créateur, demande de retrait).
- `Actions/` — actions métier unitaires (ex. `SubmitVideoForModeration`, `RequestPayout`).
- `Data/` — DTOs d'entrée/sortie des endpoints API.

Rien n'est encore implémenté ici — dossier créé pour recevoir ce code au fur et à mesure des tickets.
