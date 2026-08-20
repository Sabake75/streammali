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

**MVP fonctionnellement complet (2026-08-20)** — les trois profils ont un parcours de bout en bout sur les trois apps (API, web, mobile), testé en local à plusieurs reprises (dernier passage : 56/56 tests backend, `flutter analyze`/`test`/`build web` et `tsc`/`lint`/`build` propres). Détail par app dans `apps/{api,web,mobile}/README.md` et les `README.md` de chaque domaine métier (`apps/api/app/Domain/*/README.md`).

Construit et vérifié :
- **Inscription/auth** : Viewer par téléphone, Créateur avec pièce d'identité (stockage privé, consultable par un modérateur connecté). Token Bearer Sanctum partagé web/mobile. Tout champ téléphone (inscription, connexion, achat, retrait) est un indicatif pays + un champ chiffres seul, longueur plafonnée par pays via une bibliothèque de référence (`libphonenumber-js` côté web, `phone_numbers_parser` côté mobile) plutôt que des règles codées en dur — tous les pays sont proposés, pas seulement le Mali, pour les Maliens de la diaspora.
- **Catalogue** : liste/filtres/recherche/pagination, fiche détail, SSR côté web.
- **Upload vidéo** : métadonnées + fichier (Cloudflare Stream, flux direct upload), branché web + mobile.
- **Modération** : file d'attente Filament, valider/refuser (motif obligatoire), validation bloquée tant que le fichier n'est pas prêt.
- **Achat** : Orange Money (intégration directe), webhook revérifié côté serveur, déverrouillage immédiat.
- **Ledger & retraits** : commission automatique par vente, solde/historique/demande de retrait côté créateur (web+mobile), traitement côté modérateur.
- **Comptes** : suspension/blocage/réactivation par le modérateur, effectif immédiatement (connexion + tokens existants).

Deux intégrations tierces (`App\Domain\Payment\Gateways\OrangeMoneyGateway`, `App\Domain\Video\Gateways\CloudflareStreamGateway`) sont écrites contre la documentation publique de chaque fournisseur mais **jamais vérifiées avec de vrais credentials** — c'est le principal risque avant mise en production.

**Prochaine étape — passage en production :**
1. Obtenir un compte marchand Orange Money Mali (Orange Developer Center) et un compte Cloudflare Stream ; confirmer le contrat API exact de chacun et ajuster `OrangeMoneyGateway`/`CloudflareStreamGateway` si besoin.
2. Choisir un hébergement (API Laravel + PostgreSQL + Redis, build Next.js, stores mobiles) et un pipeline de déploiement — rien n'est configuré à ce stade (pas de CI/CD, pas d'environnement de staging).
3. Webhook Cloudflare (actuellement le statut n'est interrogé qu'à la demande, pas poussé).
4. Fonctionnalités cahier des charges non prioritaires pour le MVP : aperçu gratuit avant achat, notation/commentaires, favoris/recommandations, statistiques créateur détaillées, gestion des catégories/mise en avant par le modérateur.

Les choix de stack (Laravel/PostgreSQL, Next.js, Flutter, Orange Money direct, Cloudflare Stream) sont ceux effectivement implémentés, plus indicatifs à ce stade.
