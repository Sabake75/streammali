# StreamMali Mobile

Application mobile Flutter (Android prioritaire).

## Rôle

- Mêmes parcours que le web (catalogue, achat, lecture) côté Viewer, adaptés au mobile.
- Cache/téléchargement des vidéos achetées pour lecture en zone à connectivité faible.
- Notifications push (nouvelle vidéo, validation/refus, paiement confirmé).

## Statut

Scaffold Flutter initialisé (`flutter create`, package `com.streammali`), `flutter analyze` sans erreur. Plateformes générées par défaut : Android, iOS, web, linux, macos, windows — Android reste prioritaire, les autres peuvent être retirées si non nécessaires.

Catalogue fonctionnel, consomme l'API `apps/api` (`GET /api/videos`, `GET /api/videos/{id}`) :
- `lib/screens/catalogue_screen.dart` — liste paginée en grille, recherche + filtre catégorie.
- `lib/screens/video_detail_screen.dart` — fiche vidéo.
- `lib/services/api_client.dart` — client HTTP (`package:http`). `baseUrl`/`webBaseUrl` pointent par défaut vers la production Render (`https://streammali-api.onrender.com/api`, `https://streammali-web.onrender.com`), pour qu'un `flutter build` sans flag produise déjà un binaire fonctionnel. Pour le dev local, surcharger via `--dart-define=API_BASE_URL=...`/`WEB_BASE_URL=...` — `http://127.0.0.1:8000/api` sur appareil physique via `adb reverse tcp:8000 tcp:8000`, ou `http://10.0.2.2:8000/api` sur l'émulateur Android (`localhost` y pointe vers l'émulateur lui-même, pas la machine hôte).
- `lib/models/`, `lib/widgets/video_card.dart`, `lib/utils/formatting.dart`.

Auth + achat, même flux token Bearer Sanctum que le web (`apps/web/src/lib/{auth-client,api-client}.ts`) :
- `lib/screens/{login_screen,register_screen}.dart` — formulaires, appellent `POST /api/login`/`/register`.
- `lib/services/auth_controller.dart` — `ChangeNotifier` singleton, persiste le token/utilisateur avec `shared_preferences` (équivalent du `localStorage` web).
- `lib/services/api_client.dart` — méthodes `register`/`login`/`logout`/`purchaseVideo` ajoutées, mêmes endpoints que le web.
- `lib/widgets/purchase_section.dart` (fiche vidéo) — ouvre `payment_url` dans le navigateur externe via `url_launcher` après un achat réussi.
- L'AppBar du catalogue affiche l'état de connexion (nom + déconnexion, ou lien connexion).

Important : CORS ne s'applique qu'à Flutter **Web** (Android/iOS/desktop ne sont pas concernés). Pour tester sur Chrome, ajouter l'origine du serveur de dev Flutter (`flutter run -d chrome --web-port=...`) à `CORS_ALLOWED_ORIGINS` côté API.

Inscription créateur (`lib/screens/register_creator_screen.dart`, liée depuis l'inscription standard et depuis l'espace créateur) : formulaire avec sélection de pièce d'identité (`file_picker`, jpg/jpeg/png/pdf) puis upload multipart (`http.MultipartRequest`) vers `POST /api/register/creator`. Si un viewer est déjà connecté, l'écran affiche un formulaire court à la place (pièce d'identité + CGU seulement, `POST /api/creator/upgrade`) qui fait évoluer son compte existant — avant ça, un viewer connecté tombait systématiquement sur l'échec de la contrainte `unique` du téléphone en tentant de recréer un compte avec le même numéro.

Upload vidéo côté créateur (`lib/screens/creator_screen.dart`, accessible via l'icône dans l'AppBar du catalogue) :
- Réservé aux comptes `role=creator` (bouton vers l'inscription créateur sinon).
- Formulaire de création (métadonnées) + liste "mes vidéos" avec statut de modération et de traitement.
- `lib/widgets/video_upload_widget.dart` — sélection de fichier (`file_picker`) puis upload via **`tus_client_dart`** contre l'`upload_url` Cloudflare Stream. `TusMemoryStore` pré-rempli avec l'URL fournie par l'API pour que le client cible directement la ressource déjà créée côté Cloudflare (sans re-déclencher une création). Barre de progression, puis sondage périodique du statut jusqu'à `ready`/`failed`.

Vérifié en conditions réelles contre l'API (création/liste vidéo confirmées sur PostgreSQL, en réutilisant la même approche que côté web).

Champ téléphone (connexion, inscription, achat, retrait créateur) : `lib/widgets/phone_number_field.dart`, indicatif pays + chiffres seuls, longueur plafonnée par pays via `lib/utils/phone.dart` (`phone_numbers_parser` pour les longueurs par pays, `country_code_picker` pour la liste des noms de pays — tous les pays proposés, pas seulement le Mali).

Mot de passe (connexion, inscription, inscription créateur) : `lib/widgets/pin_code_field.dart`, code à 4 chiffres uniquement (masqué, clavier numérique, plafonné à 4) — voir `apps/api/README.md` pour la justification et le throttling côté serveur.

Messagerie créateur ↔ modération (espace créateur) : `lib/widgets/messaging.dart`, style bulles de chat, `GET`/`POST /api/creator/messages` via `lib/models/message.dart`.

Signalement de vidéo (fiche vidéo) : `lib/widgets/report_section.dart`, même pattern que `purchase_section.dart` (lien repliable → formulaire de motif), `POST /api/videos/{id}/report`.

Statistiques créateur (espace créateur) : `lib/widgets/stats.dart`, totaux (vues/achats/revenus), graphique en barres (`Container` avec hauteur dynamique, pas de librairie) du revenu sur 14 jours, tableau par vidéo, `GET /api/creator/stats` via `lib/models/creator_stats.dart`.

Comptage de vues (fiche vidéo) : `_apiClient.recordVideoView(id)` appelé dans `initState()` de `video_detail_screen.dart`, best-effort (n'interrompt jamais l'affichage de la page en cas d'échec), `POST /api/videos/{id}/view`.

Catégories dynamiques (`GET /api/categories`, plus de liste codée en dur) : `_apiClient.fetchCategories()` dans `catalogue_screen.dart` (filtre) et `creator_screen.dart` (formulaire de création) — remplace l'ancienne const `videoCategories` figée dans `lib/models/video.dart`.

Lecteur vidéo (`lib/widgets/video_player_widget.dart`, fiche vidéo) : premier lecteur de l'app — packages `video_player` + `chewie`, pas d'autoplay (bouton play natif de Chewie = tap-to-play). Vidéo achetée et prête → lecture complète (`playbackUrl`) ; sinon aperçu (`previewPlaybackUrl`, toujours exposé même aux invités) avec mention "Aperçu — achète la vidéo pour la voir en entier."

Notation/commentaires (`lib/widgets/review_section.dart`, fiche vidéo) : formulaire (étoiles + commentaire optionnel) visible uniquement si `video.purchased`, `POST /api/videos/{id}/reviews` ; liste des avis publique, `GET /api/videos/{id}/reviews`, rechargée après soumission.

Favoris/recommandations : `lib/widgets/favorite_button.dart` (fiche vidéo, masqué si non connecté, `POST /api/videos/{id}/favorite`). "Recommandé pour vous" (`catalogue_screen.dart`, rangée horizontale) et "Vidéos similaires" (`video_detail_screen.dart`, même catégorie) réutilisent `VideoCard`.

Mise en avant (`catalogue_screen.dart`, rangée "En vedette") : `fetchFeaturedVideos()`, public, chargée directement dans `initState()` (pas besoin d'attendre l'auth contrairement à "Recommandé pour vous").

Suivi d'erreurs (`lib/main.dart`) : `SentryFlutter.init` enveloppe `runApp`, DSN vide par défaut (aucun compte Sentry lié au projet pour l'instant) — le SDK reste inactif tant qu'on n'active pas via `--dart-define=SENTRY_DSN=...`, même convention que `API_BASE_URL`.

Mon compte (`lib/screens/account_screen.dart`, accessible en tapant son nom dans l'AppBar du catalogue) : export de données (`GET /api/account/export`, affiché dans une boîte de dialogue avec copie presse-papiers plutôt qu'un fichier — évite une dépendance file-system/partage pour un bouton peu utilisé) et suppression de compte (`DELETE /api/account`, confirmation obligatoire).

Accessibilité : les étoiles de notation (`review_section.dart`) n'avaient pas de `tooltip` — seul point réellement muet pour TalkBack/VoiceOver trouvé côté mobile, le reste (`IconButton` de l'AppBar, `Tooltip` par barre sur le graphique de revenus) l'avait déjà.

Titre d'écran manquant sur `video_detail_screen.dart` : `AppBar()` était vide (juste la flèche retour), seul écran de l'app sans titre — corrigé en affichant le titre de la vidéo une fois chargée (équivalent mobile du titre d'onglet par page côté web).

`flutter analyze`, `flutter test` (couvre la validation du formulaire de connexion) et `flutter build web` passent tous sans erreur.

```
flutter run       # lancer l'app
flutter analyze   # lint/analyse statique
flutter test      # tests widgets
```
