# Domain\Payment

Namespace `App\Domain\Payment`.

Responsabilités (cahier des charges §6-7) :
- Création et suivi des paiements à l'achat (100 FCFA/vidéo) via PayDunya (agrégateur Mobile Money).
- Traitement idempotent des webhooks de confirmation.
- Ledger : répartition commission plateforme / solde créateur.
- Demandes de retrait créateur (Mobile Money), minimum 10 000 FCFA.

## Sous-structure

- `Models/Payment.php` — un paiement (achat d'une vidéo par un viewer). `LedgerEntry`/`Payout` (répartition commission/créateur, retraits) pas encore implémentés.
- `Enums/PaymentStatus.php` — `pending` / `succeeded` / `failed`.
- `Contracts/PaymentGateway.php` — interface découplant la logique métier du fournisseur (`initiate`, `verifyStatus`).
- `Gateways/PayDunyaGateway.php` — **gateway actif** (voir le binding dans `AppServiceProvider`). Implémentation de l'API "Checkout Invoice" de PayDunya (`checkout-invoice/create` renvoie un `token`, le client complète le paiement sur `https://paydunya.com/checkout/invoice/{token}`, le statut se revérifie côté serveur via `checkout-invoice/confirm/{token}`, jamais le contenu du webhook directement). **La forme exacte du payload IPN envoyé à `callback_url` est à vérifier** une fois le compte marchand disponible — voir `config/services.php` (`services.paydunya`). Un appel réel (credentials vides) a confirmé que l'URL/la forme de la requête sont correctes : PayDunya a répondu une erreur structurée ("Invalid Masterkey Specified") plutôt qu'un 404.
- `Gateways/OrangeMoneyGateway.php` — implémentation Orange Money Web Payment directe, gardée dans le repo comme implémentation de rechange derrière la même interface (plus le binding actif). Testée isolément dans `tests/Feature/OrangeMoneyPaymentTest.php`, qui fixe elle-même le binding `PaymentGateway → OrangeMoneyGateway` plutôt que de dépendre du binding par défaut de l'app.
- `Actions/InitiatePayment.php` — crée un `Payment` et démarre le paiement côté gateway actif.
- `Actions/ConfirmPayment.php` — traitement idempotent : revérifie le statut auprès du gateway avant de marquer un paiement comme confirmé.
- `Data/` — DTOs (`PaymentInitiationResult`, `InitiatedPayment`).

Webhooks : `POST /api/webhooks/paydunya` (actif, `App\Http\Controllers\Api\PayDunyaWebhookController`) et `POST|GET /api/webhooks/orange-money` (toujours câblé, pour l'implémentation de rechange). Le binding `PaymentGateway → PayDunyaGateway` est fait dans `AppServiceProvider` — changer de fournisseur revient à ajouter une nouvelle classe et changer ce binding.

Testé (mock HTTP, aucun appel réseau réel) dans `tests/Feature/PayDunyaPaymentTest.php` et `tests/Feature/OrangeMoneyPaymentTest.php`.

**Note séparée, pas spécifique à PayDunya** : `return_url`/`cancel_url` pointent vers l'accueil web (`http://localhost:3000`) faute de pages "paiement réussi/annulé" dédiées côté web — ces pages n'existent pas encore (l'ancien `ORANGE_MONEY_RETURN_URL` pointait déjà vers des routes `/paiement/succes`/`/annule` qui n'ont jamais été construites). À faire avant la mise en prod.

**Ledger et retraits** (cahier des charges §6) :
- `Models/LedgerEntry.php` — une écriture par vente réussie (créée automatiquement par `ConfirmPayment` quand un paiement passe à `succeeded`), avec la répartition `gross_amount` / `commission_amount` / `net_amount`. Taux de commission configurable (`config/platform.php`, `PLATFORM_COMMISSION_RATE`, défaut 25 % — indicatif, cahier des charges donne une fourchette 20-30 %).
- `Actions/GetCreatorBalance.php` — solde disponible = somme des `net_amount` du créateur moins les retraits déjà `pending`/`paid` (empêche de demander deux fois le même solde).
- `Actions/RequestPayout.php` — crée une demande de retrait, rejette si en dessous du minimum (`PLATFORM_MINIMUM_PAYOUT_AMOUNT`, 10 000 FCFA — valeur exacte du cahier des charges, pas indicative) ou au-dessus du solde disponible.
- `Models/Payout.php` + `Enums/PayoutStatus.php` (`pending`/`paid`/`rejected`).
- Pas d'intégration réelle de décaissement Mobile Money — le modérateur traite les demandes manuellement dans le back-office (`/moderation/payouts`), comme pour la modération vidéo.

Testé dans `tests/Feature/PayoutApiTest.php`, vérifié aussi contre PostgreSQL (calcul de commission, réservation du solde).
