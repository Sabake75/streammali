# StreamMali

Plateforme de streaming vidéo « mini Netflix » pensée pour le Mali. Elle permet aux créateurs locaux (réalisateurs, artistes, humoristes) de publier films, clips et web-séries, et au public de les acheter à l'unité à tarif fixe et bas, payable en Mobile Money.

Contexte : la production locale (films, clips, sketchs) circule surtout de façon informelle (USB, WhatsApp, réseaux sociaux) sans rémunération pour les créateurs, et les plateformes internationales sont mal adaptées au pouvoir d'achat et à l'offre locale. StreamMali répond à ça avec un ticket d'entrée très faible et un paiement mobile déjà familier au public malien.

Référence complète : `CAHIER_DES_CHARGES_STREAMMALI.md`.

## Profils utilisateurs

**Créateur** — producteur de contenu.
- Inscription avec pièce d'identité, upload de vidéos (titre, description, catégorie, jaquette, durée).
- Soumission à la modération avant publication ; suivi de statut (en attente / validé / refusé + motif).
- Fixation/confirmation du prix (25 FCFA par défaut).
- Dashboard : vues, achats, revenus, évolution dans le temps.
- Demande de retrait vers Mobile Money ou compte bancaire.
- Gestion du catalogue personnel, messagerie avec la modération.

**Viewer (fan)** — utilisateur final.
- Inscription simple (téléphone, nom, prénom).
- Catalogue avec recherche/filtres (catégorie, créateur, popularité), aperçu gratuit avant achat.
- Achat à l'unité (25 FCFA, Mobile Money), accès illimité en streaming à la vidéo achetée.
- Historique d'achats, bibliothèque personnelle, favoris, recommandations, notation/commentaires.
- Support / signalement de problème.

**Modérateur (administrateur)** — contrôle qualité et supervision.
- Dashboard global (créateurs, viewers, ventes, chiffre d'affaires).
- File d'attente de modération : visionnage, validation/refus (motif obligatoire).
- Dépublication en cas de signalement, gestion des comptes (suspension, blocage, vérification d'identité).
- Suivi des transactions et reversements, statistiques/rapports exportables.
- Gestion des catégories et mise en avant sur la page d'accueil.

## Modèle économique

- Vente à l'acte (pay-per-view), pas d'abonnement : **25 FCFA par vidéo**.
- Commission plateforme : **20 à 30 %** (à définir) sur chaque vente, pour couvrir hébergement/paiement/fonctionnement.
- Reversement au créateur : solde restant, **périodicité hebdomadaire**, vers Mobile Money.
- Frais des opérateurs Mobile Money à la charge de la plateforme.
- Montant minimum de retrait : **10 000 FCFA**.
- Paiement (MVP) : **Orange Money uniquement**, intégration directe (pas d'agrégateur, pas de carte bancaire dans un premier temps) ; parcours = sélection vidéo → confirmation prix → paiement Orange Money (USSD/push) → déverrouillage immédiat après confirmation. Autres opérateurs (Moov Money, Sama Money) et carte bancaire envisagés en phase ultérieure.

## Stack technique proposée (à valider)

- **Frontend web** : application responsive (React, Vue ou équivalent).
- **Mobile** : Android natif ou multiplateforme (Flutter / React Native) — Android prioritaire.
- **Backend** : API REST sécurisée (Node.js, Laravel ou équivalent) + base de données relationnelle.
- **Vidéo** : service de streaming/CDN adapté (upload, transcodage, protection du flux, streaming adaptatif multi-qualité).
- **Paiement (MVP)** : intégration directe **Orange Money Web Payment API** (OAuth2 client credentials + webhook de confirmation), sans passerelle agrégée. Prévoir une interface `PaymentGateway` côté backend pour pouvoir brancher d'autres opérateurs plus tard sans réécrire la logique métier.
- **Back-office modérateur** : dashboard d'admin séparé avec gestion des rôles/droits.

## Contraintes clés

- Connectivité souvent limitée (3G/4G) → forte optimisation bande passante, faible consommation de données.
- Vérification des droits d'auteur des contenus déposés (attestation de propriété).
- Fiabilité variable des API Mobile Money (délais, échecs à gérer).
- Modération humaine obligatoire avant mise en ligne → délai de traitement à communiquer.
- Interface multilingue envisageable (français, bambara).
- Sécurité : chiffrement des transactions, protection anti-téléchargement/piratage vidéo, protection des données personnelles.

## État du projet

- `apps/api` : scaffold Laravel initialisé et fonctionnel sur PostgreSQL (migrations par défaut exécutées). Structure de domaines métier créée (`app/Domain/{Creator,Viewer,Moderation,Payment,Video}`). Back-office modérateur Filament sur `/moderation`, accès restreint aux comptes `role = moderator`. Modèle `Video` + ressource Filament fonctionnels : file d'attente de modération avec actions Valider/Refuser (motif obligatoire). Modèle `Payment` + intégration Orange Money (`App\Domain\Payment\Gateways\OrangeMoneyGateway`, webhook `/api/webhooks/orange-money`, statut revérifié côté serveur plutôt que de faire confiance au webhook) — **implémentation basée sur la doc publique Orange Money Web Payment, à confirmer contre Orange Developer Center Mali une fois les credentials marchand obtenus**. Auth Viewer par téléphone (`POST /api/register`, `/api/login`, `/api/logout`) — `users.phone` ajouté, `users.email` rendu optionnel (le login Filament modérateur garde `email`, inchangé). API catalogue/achat publique : `GET /api/videos`, `GET /api/videos/{id}`, `POST /api/videos/{id}/purchase` (auth Sanctum). API créateur : `POST /api/creator/videos` (upload, statut `pending`), `GET /api/creator/videos` (liste tous statuts, `role=creator` requis). `php artisan test` : 29/29 verts, flux inscription→connexion→achat et upload→invisible du catalogue vérifiés aussi contre PostgreSQL. Reste à faire : streaming de la vidéo achetée, inscription Créateur (pièce d'identité), upload du fichier vidéo lui-même (CDN), gestion des comptes (suspension/blocage), ledger commission/créateur et demandes de retrait.
- `apps/web` : scaffold Next.js initialisé (TypeScript, Tailwind, App Router). Catalogue fonctionnel en SSR (liste + fiche détail + filtres). Auth + achat côté client fonctionnels : connexion/inscription, `PurchaseButton` sur la fiche vidéo, token Bearer Sanctum stocké en `localStorage` (pas de Sanctum SPA/cookies — décision volontaire pour rester cohérent avec le futur client mobile Flutter, un seul mécanisme d'auth pour tous les clients). CORS configuré côté API (`CORS_ALLOWED_ORIGINS`). Vérifié de bout en bout avec de vraies requêtes cross-origin ; seul point qui échoue encore est l'absence de vrais credentials Orange Money (déjà documenté, pas lié au CORS).
- `apps/mobile` : scaffold Flutter initialisé (`com.streammali`), `flutter analyze` sans erreur.
- Les choix de stack ci-dessus restent indicatifs pour le reste et sont à valider au fur et à mesure du développement du MVP.
