# Domain\Payment

Namespace `App\Domain\Payment`.

Responsabilités (cahier des charges §6-7, décision projet : MVP Orange Money uniquement) :
- Création et suivi des paiements à l'achat (25 FCFA/vidéo) via Orange Money.
- Traitement idempotent des webhooks de confirmation.
- Ledger : répartition commission plateforme / solde créateur.
- Demandes de retrait créateur (Mobile Money), minimum 10 000 FCFA.

## Sous-structure

- `Models/Payment.php` — un paiement (achat d'une vidéo par un viewer). `LedgerEntry`/`Payout` (répartition commission/créateur, retraits) pas encore implémentés.
- `Enums/PaymentStatus.php` — `pending` / `succeeded` / `failed`.
- `Contracts/PaymentGateway.php` — interface découplant la logique métier du fournisseur (`initiate`, `verifyStatus`).
- `Gateways/OrangeMoneyGateway.php` — implémentation Orange Money Web Payment (OAuth2 client_credentials + endpoint de statut interrogé côté serveur, jamais le contenu du webhook directement). **Les chemins/URLs exacts sont à vérifier contre la doc Orange Developer Center Mali** une fois les credentials marchand disponibles — voir `config/services.php` (`services.orange_money`).
- `Actions/InitiatePayment.php` — crée un `Payment` et démarre le paiement côté Orange.
- `Actions/ConfirmPayment.php` — traitement idempotent : revérifie le statut auprès d'Orange avant de marquer un paiement comme confirmé.
- `Data/` — DTOs (`PaymentInitiationResult`, `InitiatedPayment`).

Webhook : `POST|GET /api/webhooks/orange-money` (`App\Http\Controllers\Api\OrangeMoneyWebhookController`). Le binding `PaymentGateway → OrangeMoneyGateway` est fait dans `AppServiceProvider` — changer de fournisseur (Moov Money, carte) revient à ajouter une nouvelle classe et changer ce binding.

Testé (mock HTTP, aucun appel réseau réel vers Orange) dans `tests/Feature/OrangeMoneyPaymentTest.php`.

**Ledger et retraits** (cahier des charges §6) :
- `Models/LedgerEntry.php` — une écriture par vente réussie (créée automatiquement par `ConfirmPayment` quand un paiement passe à `succeeded`), avec la répartition `gross_amount` / `commission_amount` / `net_amount`. Taux de commission configurable (`config/platform.php`, `PLATFORM_COMMISSION_RATE`, défaut 25 % — indicatif, cahier des charges donne une fourchette 20-30 %).
- `Actions/GetCreatorBalance.php` — solde disponible = somme des `net_amount` du créateur moins les retraits déjà `pending`/`paid` (empêche de demander deux fois le même solde).
- `Actions/RequestPayout.php` — crée une demande de retrait, rejette si en dessous du minimum (`PLATFORM_MINIMUM_PAYOUT_AMOUNT`, 10 000 FCFA — valeur exacte du cahier des charges, pas indicative) ou au-dessus du solde disponible.
- `Models/Payout.php` + `Enums/PayoutStatus.php` (`pending`/`paid`/`rejected`).
- Pas d'intégration réelle de décaissement Mobile Money — le modérateur traite les demandes manuellement dans le back-office (`/moderation/payouts`), comme pour la modération vidéo.

Testé dans `tests/Feature/PayoutApiTest.php`, vérifié aussi contre PostgreSQL (calcul de commission, réservation du solde).
