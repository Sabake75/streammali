# Domain\Payment

Namespace `App\Domain\Payment`.

Responsabilités (cahier des charges §6-7) :
- Création et suivi des paiements à l'achat (100 FCFA/vidéo) via Orange Money (Web Payment).
- Traitement idempotent des webhooks de confirmation.
- Ledger : répartition commission plateforme / solde créateur.
- Demandes de retrait créateur (Mobile Money), minimum 10 000 FCFA.

## Sous-structure

- `Models/Payment.php` — un paiement (achat d'une vidéo par un viewer). `LedgerEntry`/`Payout` (répartition commission/créateur, retraits) pas encore implémentés.
- `Enums/PaymentStatus.php` — `pending` / `succeeded` / `failed`.
- `Contracts/PaymentGateway.php` — interface découplant la logique métier du fournisseur (`initiate`, `verifyStatus`).
- `Gateways/OrangeMoneyGateway.php` — **gateway actif** (voir le binding dans `AppServiceProvider`, redevenu actif le 2026-09-03 après le blocage KYC de PayDunya ci-dessous). Implémentation de l'API "Orange Money Web Payment" (`/webpayment` renvoie `payment_url`/`pay_token`/`notif_token`, le client complète le paiement sur cette URL, le statut se revérifie côté serveur en **POST** sur `/transactionstatus` — jamais le contenu du webhook directement). Compte sandbox connecté (produit **"Orange Money WebPay Dev"** du Developer Center) et **vérifié en réel de bout en bout** (2026-09-04, y compris confirmation OTP via le "Partner Sandbox Simulator" d'Orange). Plusieurs pièges d'intégration trouvés en le vérifiant en réel, documentés en commentaire dans la classe et dans `config/services.php`/`.env.example` : chemin d'API `dev` (pas le code pays) tant que seul le produit sandbox est approuvé ; `merchant_key` sans le point final affiché sur le Developer Center ; `reference` sans le caractère `#` ; `/transactionstatus` en POST, pas GET (405 sinon) ; `return_url`/`cancel_url`/`notif_url` doivent être des URLs publiques (localhost rejeté).
- `Gateways/PayDunyaGateway.php` — implémentation PayDunya (agrégateur, API "Checkout Invoice"), gardée dans le repo comme option de repli derrière la même interface (plus le binding actif depuis le 2026-09-03). Compte marchand sandbox connecté (2026-08-28) : les 4 clés sont acceptées par PayDunya (réponse structurée, pas une erreur d'authentification), mais bloqué par `response_code: "1001"` — **"Vous devez valider vos informations de KYC avant d'avoir accès au service."** : à reprendre si ce KYC aboutit avant que la production Orange Money soit approuvée. **La forme exacte du payload IPN envoyé à `callback_url` reste à vérifier** une fois le KYC validé et une vraie facture payable créée. Testée isolément dans `tests/Feature/PayDunyaPaymentTest.php`, qui fixe elle-même le binding `PaymentGateway → PayDunyaGateway` plutôt que de dépendre du binding par défaut de l'app.
- `Actions/InitiatePayment.php` — crée un `Payment` et démarre le paiement côté gateway actif. `order_reference` est un **ULID** (26 caractères), pas un UUID (36) : Orange plafonne `order_id`/`reference` à 30 caractères, un UUID passait quand même en sandbox mais risquait une troncature silencieuse ailleurs.
- `Actions/ConfirmPayment.php` — traitement idempotent : revérifie le statut auprès du gateway avant de marquer un paiement comme confirmé.
- `Exceptions/PaymentGatewayException.php` — la requête HTTP vers le gateway a réussi mais celui-ci refuse au niveau métier (ex. PayDunya, `response_code != "00"` — voir le blocage KYC ci-dessus). Distincte des `RequestException`/`ConnectionException` (l'appel HTTP lui-même échoue), gérées séparément dans `bootstrap/app.php` : même famille de problème du point de vue utilisateur, donc même message/handler. Rencontré en conditions réelles : un viewer tentant un achat sur le compte PayDunya bloqué KYC recevait un "Server Error" brut, non traduit — `PayDunyaGateway::initiate()` levait une `RuntimeException` nue qu'aucun handler ne capturait.
- `Data/` — DTOs (`PaymentInitiationResult` — inclut `notifToken`, voir webhook ci-dessous —, `InitiatedPayment`).

Webhooks : `POST|GET /api/webhooks/orange-money` (actif, `App\Http\Controllers\Api\OrangeMoneyWebhookController`) et `POST /api/webhooks/paydunya` (toujours câblé, pour l'option de repli). Le binding `PaymentGateway → OrangeMoneyGateway` est fait dans `AppServiceProvider` — changer de fournisseur revient à ajouter une nouvelle classe et changer ce binding. **Piège réel trouvé en production (2026-09-04)** : le payload de notification réel d'Orange ne contient que `{status, notif_token, txnid}`, jamais `order_id` (confirmé par leur doc officielle) — chercher le paiement par `order_reference` échoue donc silencieusement (404 avalé). Corrigé en persistant `notif_token` (renvoyé par `/webpayment` mais ignoré jusque-là) sur `payments.provider_notif_token`, qui sert à la fois de clé de recherche et d'authentification de la notification (non devinable de l'extérieur, contrairement à `order_reference`).

Testé (mock HTTP, aucun appel réseau réel) dans `tests/Feature/OrangeMoneyPaymentTest.php` et `tests/Feature/PayDunyaPaymentTest.php`.

`return_url`/`cancel_url` pointent vers `apps/web/src/app/paiement/{succes,annule}/page.tsx` (`.env` : `ORANGE_MONEY_RETURN_URL`/`ORANGE_MONEY_CANCEL_URL`). La page succès ne fait pas confiance au retour de redirection lui-même comme preuve d'achat (le webhook, revérifié côté serveur, reste la seule source de vérité) : elle relit l'id vidéo posé en `sessionStorage` par `PurchaseButton` avant la redirection, puis sonde `GET /api/videos/{id}` (authentifié, donc `purchased` reflète le vrai état) jusqu'à confirmation ou timeout (~30s), pendant que le webhook confirme le paiement en tâche de fond.

**Ledger et retraits** (cahier des charges §6) :
- `Models/LedgerEntry.php` — une écriture par vente réussie (créée automatiquement par `ConfirmPayment` quand un paiement passe à `succeeded`), avec la répartition `gross_amount` / `commission_amount` / `net_amount`. Taux de commission configurable (`config/platform.php`, `PLATFORM_COMMISSION_RATE`, défaut 25 % — indicatif, cahier des charges donne une fourchette 20-30 %).
- `Actions/GetCreatorBalance.php` — solde disponible = somme des `net_amount` du créateur moins les retraits déjà `pending`/`paid` (empêche de demander deux fois le même solde).
- `Actions/RequestPayout.php` — crée une demande de retrait, rejette si en dessous du minimum (`PLATFORM_MINIMUM_PAYOUT_AMOUNT`, 10 000 FCFA — valeur exacte du cahier des charges, pas indicative) ou au-dessus du solde disponible.
- `Models/Payout.php` + `Enums/PayoutStatus.php` (`pending`/`paid`/`rejected`).
- Pas d'intégration réelle de décaissement Mobile Money — le modérateur traite les demandes manuellement dans le back-office (`/moderation/payouts`), comme pour la modération vidéo.

Testé dans `tests/Feature/PayoutApiTest.php`, vérifié aussi contre PostgreSQL (calcul de commission, réservation du solde).

`PayoutsTable`/`EditPayout` : même correctifs que `UsersTable`/`VideosTable` (`->recordUrl(null)`, pas de `DeleteAction`/`DeleteBulkAction` — voir `Domain\Video\README`), trouvés lors du même audit QA production que le correctif `UsersTable`. Une demande de retrait supprimée par erreur perdrait la trace d'un vrai retrait d'argent réel ; "Rejeter" (déjà présent) est l'outil prévu pour un retrait qu'on refuse. `LedgerEntriesTable` n'a jamais ce problème : lecture seule par construction (`canCreate() => false`, aucune page d'édition enregistrée), les écritures ne sont créées que par `ConfirmPayment`.
