# Domain\Payment

Namespace `App\Domain\Payment`.

Responsabilités (cahier des charges §6-7, décision projet : MVP Orange Money uniquement) :
- Création et suivi des paiements à l'achat (25 FCFA/vidéo) via Orange Money.
- Traitement idempotent des webhooks de confirmation.
- Ledger : répartition commission plateforme / solde créateur.
- Demandes de retrait créateur (Mobile Money), minimum 10 000 FCFA.

## Sous-structure prévue

- `Models/` — modèles Eloquent (ex. `Payment`, `LedgerEntry`, `Payout`).
- `Actions/` — actions métier unitaires (ex. `InitiateOrangeMoneyPayment`, `HandlePaymentWebhook`).
- `Contracts/` — interface `PaymentGateway` pour découpler la logique métier du fournisseur (permet d'ajouter Moov Money/carte plus tard sans réécriture).
- `Data/` — DTOs d'entrée/sortie des endpoints API.

Rien n'est encore implémenté ici — dossier créé pour recevoir ce code au fur et à mesure des tickets.
