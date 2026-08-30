# Domain\Account

Namespace `App\Domain\Account`.

Responsabilité : suppression et export de compte en libre-service (droit d'accès/à l'oubli), transverse aux autres domaines (Payment, Video, Review, Viewer, Moderation) plutôt que rattaché à l'un d'eux.

## Sous-structure

- `Actions/ExportAccountData.php` — rassemble tout ce que StreamMali détient sur l'utilisateur (profil, achats, favoris, avis, plus vidéos/ledger/retraits/messages pour un créateur) en un tableau, retourné en téléchargement JSON.
- `Actions/DeleteAccount.php` — anonymise les colonnes personnelles (`name`→"Compte supprimé", `email`/`phone`→`null`, mot de passe aléatoire, pièce d'identité supprimée du disque), révoque tous les tokens Sanctum, passe `account_status` à `AccountStatus::Deleted` (nouveau cas, même mécanisme que suspension/blocage — bloque la connexion via `EnsureAccountIsActive`/`LoginController`). Les enregistrements financiers (achats, `LedgerEntry`, `Payout`) sont **conservés tels quels** : ce sont des pièces comptables, pas des données personnelles à retirer. Un créateur avec un solde disponible (`GetCreatorBalance`) doit d'abord le retirer — sinon 422.

Endpoints : `GET /api/account/export` (authentifié), `DELETE /api/account` (authentifié, `throttle:write-action`).

`phone`/`email` sont `nullable` + `unique` en base, donc les repasser à `null` libère le numéro pour une future inscription plutôt que de bloquer l'unicité indéfiniment.

Testé dans `tests/Feature/AccountApiTest.php` (export viewer/créateur, suppression, suppression de la pièce d'identité stockée, blocage si solde créateur disponible, impossibilité de se reconnecter après suppression).
