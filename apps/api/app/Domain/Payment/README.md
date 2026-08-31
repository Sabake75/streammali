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
- `Gateways/PayDunyaGateway.php` — **gateway actif** (voir le binding dans `AppServiceProvider`). Implémentation de l'API "Checkout Invoice" de PayDunya (`checkout-invoice/create` renvoie un `token`, le client complète le paiement sur `https://paydunya.com/checkout/invoice/{token}`, le statut se revérifie côté serveur via `checkout-invoice/confirm/{token}`, jamais le contenu du webhook directement). Compte marchand sandbox connecté (2026-08-28) : les 4 clés (`PAYDUNYA_MASTER_KEY`/`PRIVATE_KEY`/`PUBLIC_KEY`/`TOKEN`) sont acceptées par PayDunya — un appel réel à `checkout-invoice/create` renvoie une réponse structurée (`response_code`/`response_text`), pas une erreur d'authentification. Bloqué pour l'instant par `response_code: "1001"` — **"Vous devez valider vos informations de KYC avant d'avoir accès au service."** : le compte marchand doit compléter sa vérification d'identité côté PayDunya avant de pouvoir réellement créer des factures, même en sandbox. **La forme exacte du payload IPN envoyé à `callback_url` reste à vérifier** une fois le KYC validé et une vraie facture payable créée.
- `Gateways/OrangeMoneyGateway.php` — implémentation Orange Money Web Payment directe, gardée dans le repo comme implémentation de rechange derrière la même interface (plus le binding actif). Testée isolément dans `tests/Feature/OrangeMoneyPaymentTest.php`, qui fixe elle-même le binding `PaymentGateway → OrangeMoneyGateway` plutôt que de dépendre du binding par défaut de l'app.
- `Actions/InitiatePayment.php` — crée un `Payment` et démarre le paiement côté gateway actif.
- `Actions/ConfirmPayment.php` — traitement idempotent : revérifie le statut auprès du gateway avant de marquer un paiement comme confirmé.
- `Exceptions/PaymentGatewayException.php` — la requête HTTP vers le gateway a réussi mais celui-ci refuse au niveau métier (ex. PayDunya, `response_code != "00"` — voir le blocage KYC ci-dessus). Distincte des `RequestException`/`ConnectionException` (l'appel HTTP lui-même échoue), gérées séparément dans `bootstrap/app.php` : même famille de problème du point de vue utilisateur, donc même message/handler. Rencontré en conditions réelles : un viewer tentant un achat sur le compte PayDunya bloqué KYC recevait un "Server Error" brut, non traduit — `PayDunyaGateway::initiate()` levait une `RuntimeException` nue qu'aucun handler ne capturait.
- `Data/` — DTOs (`PaymentInitiationResult`, `InitiatedPayment`).

Webhooks : `POST /api/webhooks/paydunya` (actif, `App\Http\Controllers\Api\PayDunyaWebhookController`) et `POST|GET /api/webhooks/orange-money` (toujours câblé, pour l'implémentation de rechange). Le binding `PaymentGateway → PayDunyaGateway` est fait dans `AppServiceProvider` — changer de fournisseur revient à ajouter une nouvelle classe et changer ce binding.

Testé (mock HTTP, aucun appel réseau réel) dans `tests/Feature/PayDunyaPaymentTest.php` et `tests/Feature/OrangeMoneyPaymentTest.php`.

`return_url`/`cancel_url` pointent vers `apps/web/src/app/paiement/{succes,annule}/page.tsx` (`.env` : `PAYDUNYA_RETURN_URL`/`PAYDUNYA_CANCEL_URL`). La page succès ne fait pas confiance au retour de redirection lui-même comme preuve d'achat (le webhook, revérifié côté serveur, reste la seule source de vérité) : elle relit l'id vidéo posé en `sessionStorage` par `PurchaseButton` avant la redirection, puis sonde `GET /api/videos/{id}` (authentifié, donc `purchased` reflète le vrai état) jusqu'à confirmation ou timeout (~30s), pendant que le webhook confirme le paiement en tâche de fond.

**Ledger et retraits** (cahier des charges §6) :
- `Models/LedgerEntry.php` — une écriture par vente réussie (créée automatiquement par `ConfirmPayment` quand un paiement passe à `succeeded`), avec la répartition `gross_amount` / `commission_amount` / `net_amount`. Taux de commission configurable (`config/platform.php`, `PLATFORM_COMMISSION_RATE`, défaut 25 % — indicatif, cahier des charges donne une fourchette 20-30 %).
- `Actions/GetCreatorBalance.php` — solde disponible = somme des `net_amount` du créateur moins les retraits déjà `pending`/`paid` (empêche de demander deux fois le même solde).
- `Actions/RequestPayout.php` — crée une demande de retrait, rejette si en dessous du minimum (`PLATFORM_MINIMUM_PAYOUT_AMOUNT`, 10 000 FCFA — valeur exacte du cahier des charges, pas indicative) ou au-dessus du solde disponible.
- `Models/Payout.php` + `Enums/PayoutStatus.php` (`pending`/`paid`/`rejected`).
- Pas d'intégration réelle de décaissement Mobile Money — le modérateur traite les demandes manuellement dans le back-office (`/moderation/payouts`), comme pour la modération vidéo.

Testé dans `tests/Feature/PayoutApiTest.php`, vérifié aussi contre PostgreSQL (calcul de commission, réservation du solde).
