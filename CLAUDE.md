# StreamMali

Plateforme de streaming vidéo « mini Netflix » pensée pour le Mali. Elle permet aux créateurs locaux (réalisateurs, artistes, humoristes) de publier films, clips et web-séries, et au public de les acheter à l'unité à tarif fixe et bas, payable en Mobile Money.

Contexte : la production locale (films, clips, sketchs) circule surtout de façon informelle (USB, WhatsApp, réseaux sociaux) sans rémunération pour les créateurs, et les plateformes internationales sont mal adaptées au pouvoir d'achat et à l'offre locale. StreamMali répond à ça avec un ticket d'entrée très faible et un paiement mobile déjà familier au public malien.

Référence complète : `CAHIER_DES_CHARGES_STREAMMALI.md`.

## Profils utilisateurs

**Créateur** — producteur de contenu.
- Inscription avec pièce d'identité, upload de vidéos (titre, description, catégorie, jaquette, durée).
- Soumission à la modération avant publication ; suivi de statut (en attente / validé / refusé + motif).
- Fixation/confirmation du prix (100 FCFA par défaut).
- Dashboard : vues, achats, revenus, évolution dans le temps.
- Demande de retrait vers Mobile Money ou compte bancaire.
- Gestion du catalogue personnel, messagerie avec la modération.

**Viewer (fan)** — utilisateur final.
- Inscription simple (téléphone, nom, prénom).
- Catalogue avec recherche/filtres (catégorie, créateur, popularité), aperçu gratuit avant achat.
- Achat à l'unité (100 FCFA, Mobile Money), accès illimité en streaming à la vidéo achetée.
- Historique d'achats, bibliothèque personnelle, favoris, recommandations, notation/commentaires.
- Support / signalement de problème.

**Modérateur (administrateur)** — contrôle qualité et supervision.
- Dashboard global (créateurs, viewers, ventes, chiffre d'affaires).
- File d'attente de modération : visionnage, validation/refus (motif obligatoire).
- Dépublication en cas de signalement, gestion des comptes (suspension, blocage, vérification d'identité).
- Suivi des transactions et reversements, statistiques/rapports exportables.
- Gestion des catégories et mise en avant sur la page d'accueil.

## Modèle économique

- Vente à l'acte (pay-per-view), pas d'abonnement : **100 FCFA par vidéo**.
- Commission plateforme : **20 à 30 %** (à définir) sur chaque vente, pour couvrir hébergement/paiement/fonctionnement.
- Reversement au créateur : solde restant, **périodicité hebdomadaire**, vers Mobile Money.
- Frais des opérateurs Mobile Money à la charge de la plateforme.
- Montant minimum de retrait : **10 000 FCFA**.
- Paiement (MVP) : **Orange Money** (Web Payment, intégration directe) ; parcours = sélection vidéo → confirmation prix → redirection vers la page de paiement Orange Money → déverrouillage immédiat après confirmation. **PayDunya** (agrégateur Mobile Money couvrant Orange Money, Moov Money…) a été le choix actif entre-temps (2026-08-28 → 2026-09-03, abandonné faute de KYC marchand validé côté PayDunya) et reste en repli derrière l'interface `PaymentGateway` déjà prévue pour ça, voir plus bas — l'historique complet des deux bascules est détaillé dans la section "État du projet".

## Stack technique proposée (à valider)

- **Frontend web** : application responsive (React, Vue ou équivalent).
- **Mobile** : Android natif ou multiplateforme (Flutter / React Native) — Android prioritaire.
- **Backend** : API REST sécurisée (Node.js, Laravel ou équivalent) + base de données relationnelle.
- **Vidéo** : service de streaming/CDN adapté (upload, transcodage, protection du flux, streaming adaptatif multi-qualité).
- **Paiement (MVP)** : **Orange Money** (Web Payment : redirection + webhook de confirmation revérifié côté serveur), derrière une interface `PaymentGateway` côté backend qui permet de changer de fournisseur sans réécrire la logique métier — c'est ce qui a permis de basculer vers **PayDunya** puis retour à Orange Money sans toucher au reste (voir "État du projet" pour l'historique).
- **Back-office modérateur** : dashboard d'admin séparé avec gestion des rôles/droits.

## Contraintes clés

- Connectivité souvent limitée (3G/4G) → forte optimisation bande passante, faible consommation de données.
- Vérification des droits d'auteur des contenus déposés (attestation de propriété).
- Fiabilité variable des API Mobile Money (délais, échecs à gérer).
- Modération humaine obligatoire avant mise en ligne → délai de traitement à communiquer.
- Interface multilingue envisageable (français, bambara).
- Sécurité : chiffrement des transactions, protection anti-téléchargement/piratage vidéo, protection des données personnelles.

## État du projet

**MVP fonctionnellement complet (2026-08-20), toutes les fonctionnalités du cahier des charges construites (2026-08-23)** — les trois profils ont un parcours de bout en bout sur les trois apps (API, web, mobile), y compris l'intégralité des fonctionnalités listées comme non prioritaires à l'origine (aperçu gratuit, notation/commentaires, favoris/recommandations, mise en avant). Testé en local à plusieurs reprises (dernier passage : 157/157 tests backend, 2026-09-04 — SQLite et PostgreSQL réel —, `flutter analyze`/`test`/`build web`/`build apk` et `tsc`/`lint`/`build` propres, CI GitHub Actions verte). Détail par app dans `apps/{api,web,mobile}/README.md` et les `README.md` de chaque domaine métier (`apps/api/app/Domain/*/README.md`).

Construit et vérifié :
- **Inscription/auth** : Viewer par téléphone, Créateur avec pièce d'identité (stockage privé, consultable par un modérateur connecté). Token Bearer Sanctum partagé web/mobile. Tout champ téléphone (inscription, connexion, achat, retrait) est un indicatif pays + un champ chiffres seul, longueur plafonnée par pays via une bibliothèque de référence (`libphonenumber-js` côté web, `phone_numbers_parser` côté mobile) plutôt que des règles codées en dur — tous les pays sont proposés, pas seulement le Mali, pour les Maliens de la diaspora. Le mot de passe est un **code à 4 chiffres** (public peu habitué au mot de passe), toujours hashé (bcrypt) ; `POST /api/login` est limité à 5 tentatives/minute par téléphone+IP pour compenser le faible espace de combinaisons (10 000).
- **Catalogue** : liste/filtres/recherche/pagination, fiche détail, SSR côté web.
- **Upload vidéo** : métadonnées + fichier (Cloudflare Stream, flux direct upload), branché web + mobile.
- **Modération** : file d'attente Filament, valider/refuser (motif obligatoire), validation bloquée tant que le fichier n'est pas prêt.
- **Achat** : Orange Money (Web Payment), webhook revérifié côté serveur, déverrouillage immédiat.
- **Ledger & retraits** : commission automatique par vente, solde/historique/demande de retrait côté créateur (web+mobile), traitement côté modérateur.
- **Comptes** : suspension/blocage/réactivation par le modérateur, effectif immédiatement (connexion + tokens existants).
- **Messagerie créateur ↔ modération** : fil unique par créateur (web + mobile côté créateur, action dédiée sur `/moderation/users` côté modérateur).
- **Signalement de vidéo** : n'importe quel utilisateur connecté peut signaler une vidéo (motif obligatoire) depuis sa fiche (web + mobile) ; le modérateur voit un badge et la liste des motifs sur `/moderation/videos`, et dépublie via l'action "Refuser" déjà existante (pas de mécanisme séparé).
- **Statistiques créateur** : dashboard vues/achats/revenus par vidéo + historique de revenu 14 jours (web + mobile), sur les données existantes (ledger, achats) plus un compteur de vues dédié, incrémenté par un endpoint séparé du fetch mis en cache pour ne pas sous-compter (voir `apps/api/app/Domain/Creator/README.md`).

`App\Domain\Video\Gateways\CloudflareStreamGateway` **vérifiée avec de vrais credentials** (2026-08-24) : compte Cloudflare Stream réel connecté, token API scopé Stream avec restriction d'IP, upload de test confirmé bout en bout (vidéo `ready`, URLs de lecture HLS/DASH fonctionnelles).

**Changement de fournisseur de paiement** (2026-08-28) : intégration Orange Money directe remplacée par `App\Domain\Payment\Gateways\PayDunyaGateway` (agrégateur, API "Checkout Invoice"). Compte marchand sandbox PayDunya connecté et **partiellement vérifié avec de vrais credentials** : les 4 clés sont acceptées (réponse structurée de PayDunya, pas une erreur d'authentification), mais le compte est bloqué par PayDunya tant que son **KYC** (vérification d'identité marchand) n'est pas complété côté PayDunya — impossible de créer une facture réelle avant ça.

**Retour à Orange Money** (2026-09-03) : le KYC PayDunya restant bloqué, `App\Domain\Payment\Gateways\OrangeMoneyGateway` redevient le binding actif (`AppServiceProvider`) — `PayDunyaGateway` reste dans le repo comme implémentation de rechange derrière la même interface `PaymentGateway`, prêt à reprendre le relais dès que son KYC aboutit. Compte sandbox Orange Money connecté (produit **"Orange Money WebPay Dev"** approuvé sur le Developer Center) et **vérifié avec de vrais credentials** : appel réel OAuth (`oauth/v3/token`) puis `/webpayment` réussi de bout en bout (HTTP 201, vraie `payment_url` Orange Money renvoyée). Deux pièges d'intégration trouvés et documentés dans le code (`OrangeMoneyGateway`, `config/services.php`, `.env.example`) :
- Tant que seul le produit sandbox est approuvé (pas encore le produit de production, qui nécessite sa propre validation marchand), le chemin d'API utilise le mot littéral `dev` à la place du code pays (`ORANGE_MONEY_COUNTRY=dev`, pas `ml`) — sinon 403 "Access denied".
- `merchant_key` ne prend pas le point final affiché sur le Developer Center (`35ec1887`, pas `35ec1887.`).

`return_url`/`cancel_url`/`notif_url` doivent être des URLs publiques (Orange rejette localhost/127.0.0.1 explicitement) : le flux complet redirect+webhook n'est donc testable qu'une fois déployé, un test en local ne validant que l'appel API brut — même limite déjà rencontrée avec le webhook Cloudflare Stream en local.

**Premier paiement Orange Money sandbox réussi de bout en bout** (2026-09-04, en production sur Render) : plusieurs pièges trouvés et corrigés en cours de route — `reference` ne prend pas le caractère `#` (400 "bad syntax") ; `order_id`/`reference` sont plafonnés à 30 caractères côté Orange (notre `order_reference`, un UUID de 36 caractères, passait quand même en sandbox mais risquait une troncature silencieuse ailleurs — remplacé par un ULID de 26 caractères dans `InitiatePayment`/`PaymentFactory`) ; compléter une transaction de bout en bout (pas juste l'appel `/webpayment`) demande en plus le "Partner Sandbox Simulator" d'Orange (`https://mpayment.orange-money.com/mpayment-otp/login`, identifiants marchand fournis par email séparément des clés API), qui génère l'OTP à saisir sur la page de paiement.

**Bug réel trouvé après ce premier paiement réussi côté Orange** : la transaction n'apparaissait pas dans "Mes achats" malgré la confirmation OTP — `OrangeMoneyWebhookController` cherchait le paiement par `order_id`, mais le payload de notification réel d'Orange (confirmé par leur doc officielle "Guide d'utilisation API webpayment") ne contient que `{status, notif_token, txnid}`, jamais `order_id`. Corrigé : `notif_token` (déjà renvoyé par `/webpayment` mais jamais stocké) est maintenant persisté sur `Payment` (colonne `provider_notif_token`, migration du 2026-09-04) et sert à la fois à retrouver le paiement et à authentifier la notification (non devinable de l'extérieur, contrairement à `order_reference`). Détail complet des pièges Orange Money dans la mémoire de session (`project_orange_money_sandbox_gotchas`).

CI GitHub Actions (`.github/workflows/ci.yml`) : audit de sécurité (`composer audit`/`npm audit`) + PHPUnit côté API, lint+tsc+build côté web, flutter analyze+test côté mobile, plus build d'un APK et d'un App Bundle release à chaque push sur `master` (artefacts téléchargeables, signés avec la vraie clé de release si les secrets `ANDROID_KEYSTORE_*` sont configurés, sinon repli sur la signature de debug — pas de publication automatique sur le Play Store, voir `infra/DEPLOY.md`).

**Déployé en production sur Render** (API + web, depuis le 2026-08-30 — voir `infra/DEPLOY.md` pour l'historique des pièges rencontrés au premier déploiement) : `https://streammali-api.onrender.com` et `https://streammali-web.onrender.com`, tous deux vérifiés en ligne (`/up` et `/` répondent 200, `GET /api/categories` renvoie des données réelles). Web finalement déployé sur Render plutôt que Vercel (bloqué sur un 404 persistant en prod, jamais élucidé). Stockage des pièces d'identité créateur basculé sur `FILESYSTEM_DISK` (S3/R2 en prod plutôt que le disque local du conteneur, éphémère).

Webhook Cloudflare Stream (`CloudflareStreamWebhookController`) : ne fait pas confiance au payload entrant, redéclenche juste `RefreshVideoSourceStatus` qui revérifie l'état réel auprès de Cloudflare — même logique que le webhook Orange Money.

**Catégories** gérées par le modérateur (table `categories`, Filament `app/Filament/Resources/Categories/`, endpoint public `GET /api/categories`) — remplace l'ancien enum PHP figé (`film`/`clip`/`sketch`/`series`, toujours seedé par défaut). Web et mobile récupèrent la liste dynamiquement au lieu d'un tableau codé en dur. Détail migration/piège rencontré dans `apps/api/app/Domain/Video/README.md`.

**Aperçu gratuit avant achat** + premier lecteur vidéo de l'app (web + mobile — aucun n'existait avant, corrige au passage la lecture des vidéos déjà achetées). Clip de 45s dérivé via l'API Clip de Cloudflare Stream (`CreatePreviewClip`, déclenché par le webhook quand la vidéo passe `ready`, idempotent), `preview_playback_url` toujours exposé même aux invités contrairement à `playback_url`. Web : `<video>` + `hls.js`. Mobile : `video_player` + `chewie`. Pas d'autoplay (tap-to-play, cohérent avec la contrainte bande passante).

**Notation/commentaires** (`App\Domain\Review`) : réservé aux viewers ayant acheté la vidéo (`Video::isPurchasedBy()`, promu depuis `VideoResource` pour être réutilisable). Un avis par utilisateur/vidéo, resoumettre remplace l'existant. `average_rating`/`reviews_count` exposés sur le catalogue et la fiche vidéo (web + mobile). Pas de modération des avis (pas dans le cahier des charges).

**Favoris/recommandations** (`App\Domain\Viewer`) : favoris = bascule simple (`ToggleFavorite`) + bibliothèque (`GET /api/favorites`). Recommandations volontairement non-ML (`GetRecommendedVideos`) : catégories déjà achetées/favorites par l'utilisateur, hors vidéos déjà possédées, triées par popularité — invités (ou sans historique) reçoivent juste les plus vues. Web/mobile : "Recommandé pour vous" (accueil, uniquement si connecté — l'auth n'existe que côté client via un token, donc invisible côté serveur) et "Vidéos similaires" (fiche vidéo, même catégorie, toujours visible, réutilise le catalogue existant plutôt qu'un nouvel endpoint).

**Mise en avant** : `videos.featured_at`, bascule via l'action Filament "Mettre en avant"/"Retirer" sur `/moderation/videos` (vidéo déjà validée uniquement). `GET /api/videos/featured` (public, contrairement à "recommandé" — donc directement server-renderable côté web, pas besoin d'attendre l'auth côté client). Section "En vedette" en haut de l'accueil (web + mobile).

**Au-delà du cahier des charges — feuille de route produit (2026-08-29/30)** : un audit du code (pas seulement de cette doc) a identifié des trous d'expérience non couverts par le cahier des charges d'origine, priorisés en 4 niveaux (P0 critique → P3 plus tard). Les 4 items P0 et les 5 items P1 sont faits :
- **Bibliothèque personnelle "Mes achats"** (`GET /api/purchases`, web `/bibliotheque`, mobile `LibraryScreen`) — trou le plus grave trouvé : il n'existait aucune route ni écran listant les achats d'un viewer, qui devait s'en souvenir et les refeuilleter dans le catalogue général. Triée par date d'achat réelle (`payments.confirmed_at` via `withMax`), pas par date de création de la vidéo.
- **Pages de confirmation de paiement** (`/paiement/succes`, `/paiement/annule`) — `return_url`/`cancel_url` pointaient vers l'accueil nu depuis le début du projet. La page succès ne fait pas confiance à la redirection elle-même : elle sonde `GET /api/videos/{id}` (authentifié) jusqu'à confirmation ou timeout, le webhook restant la seule source de vérité.
- **Partage social + Open Graph par vidéo** (`generateMetadata` sur `/videos/[id]`, `ShareButton`) — un lien StreamMali collé dans WhatsApp n'affichait ni jaquette, ni titre, ni prix.
- **Reçu détaillé côté viewer** — `VideoResource` expose un bloc `purchase` (montant, date, référence) uniquement sur `/api/purchases`, affiché sous chaque vidéo de la bibliothèque (web + mobile).
- **Page "Mes favoris" fonctionnelle** (web `/favoris`, mobile `FavoritesScreen`) — même trou que la bibliothèque d'achats : le bouton et `GET /api/favorites` existaient depuis longtemps, aucune page ne les affichait.
- **Notifications in-app** (`App\Notifications\VideoStatusChanged`, `NewModeratorMessage`, `GET /api/notifications`) — notifications Laravel standard (le trait `Notifiable` était déjà sur `User`, il manquait juste la table), déclenchées depuis les actions Filament "Valider"/"Refuser" et depuis `SendMessage::__invoke` (seul point de passage commun aux messages du créateur et aux réponses de la modération).
- **Onboarding à la première visite** — modale affichée une fois (localStorage web / shared_preferences mobile) expliquant aperçu gratuit, prix unique, Mobile Money.
- **Lien "toutes les vidéos de ce créateur"** — le filtre `creator_id` existait déjà côté API, jamais branché en UI ; mobile a un `CreatorVideosScreen` dédié plutôt que de réutiliser l'écran d'accueil.
- **Recherche élargie + tri par popularité** — recherche étendue au titre + description + nom du créateur, insensible à la casse via `LOWER()` des deux côtés (`LIKE` seul est insensible à la casse sur SQLite mais sensible sur PostgreSQL — piège silencieux entre tests et prod). `sort=recent|popular` (défaut recent), populaire trie sur `views_count`.

**Prochaine étape — passage en production :**
Le flux Orange Money est désormais vérifié de bout en bout en sandbox (redirection, OTP, webhook, payload réel connu et géré correctement — voir plus haut). Ce qui reste avant la vraie mise en production : obtenir l'approbation du produit **de production** Orange Money (le sandbox "WebPay Dev" ne couvre que les tests, c'est une démarche/validation marchand séparée, comme le KYC PayDunya), puis repasser `ORANGE_MONEY_COUNTRY` sur le vrai code pays et refaire un paiement réel avec de l'argent réel pour confirmer que rien ne change de comportement hors sandbox. PayDunya reste une option de repli si son KYC aboutit avant. L'IP autorisée sur le token Cloudflare Stream devra couvrir l'IP de sortie de Render, pas seulement celle utilisée pour la vérification manuelle. En parallèle, la feuille de route produit continue (items P2 : téléchargement hors-ligne, suivre un créateur, tri/réponse sur les avis, canal support viewer, squelette i18n bambara), et la publication Play Store avance (voir `infra/PLAY_STORE_LISTING.md`).

Les choix de stack (Laravel/PostgreSQL, Next.js, Flutter, Orange Money, Cloudflare Stream) sont ceux effectivement implémentés, plus indicatifs à ce stade.
