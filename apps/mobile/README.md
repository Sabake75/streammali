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
- `lib/services/api_client.dart` — client HTTP (`package:http`), URL de base surchargeable via `--dart-define=API_BASE_URL=...`. Sur l'émulateur Android, `localhost` pointe vers l'émulateur lui-même — utiliser `http://10.0.2.2:8000/api` pour joindre l'API sur la machine hôte.
- `lib/models/`, `lib/widgets/video_card.dart`, `lib/utils/formatting.dart`.

Auth + achat, même flux token Bearer Sanctum que le web (`apps/web/src/lib/{auth-client,api-client}.ts`) :
- `lib/screens/{login_screen,register_screen}.dart` — formulaires, appellent `POST /api/login`/`/register`.
- `lib/services/auth_controller.dart` — `ChangeNotifier` singleton, persiste le token/utilisateur avec `shared_preferences` (équivalent du `localStorage` web).
- `lib/services/api_client.dart` — méthodes `register`/`login`/`logout`/`purchaseVideo` ajoutées, mêmes endpoints que le web.
- `lib/widgets/purchase_section.dart` (fiche vidéo) — ouvre `payment_url` dans le navigateur externe via `url_launcher` après un achat réussi.
- L'AppBar du catalogue affiche l'état de connexion (nom + déconnexion, ou lien connexion).

Important : CORS ne s'applique qu'à Flutter **Web** (Android/iOS/desktop ne sont pas concernés). Pour tester sur Chrome, ajouter l'origine du serveur de dev Flutter (`flutter run -d chrome --web-port=...`) à `CORS_ALLOWED_ORIGINS` côté API.

Inscription créateur (`lib/screens/register_creator_screen.dart`, liée depuis l'inscription standard et depuis l'espace créateur) : formulaire avec sélection de pièce d'identité (`file_picker`, jpg/jpeg/png/pdf) puis upload multipart (`http.MultipartRequest`) vers `POST /api/register/creator`.

Upload vidéo côté créateur (`lib/screens/creator_screen.dart`, accessible via l'icône dans l'AppBar du catalogue) :
- Réservé aux comptes `role=creator` (bouton vers l'inscription créateur sinon).
- Formulaire de création (métadonnées) + liste "mes vidéos" avec statut de modération et de traitement.
- `lib/widgets/video_upload_widget.dart` — sélection de fichier (`file_picker`) puis upload via **`tus_client_dart`** contre l'`upload_url` Cloudflare Stream. `TusMemoryStore` pré-rempli avec l'URL fournie par l'API pour que le client cible directement la ressource déjà créée côté Cloudflare (sans re-déclencher une création). Barre de progression, puis sondage périodique du statut jusqu'à `ready`/`failed`.

Vérifié en conditions réelles contre l'API (création/liste vidéo confirmées sur PostgreSQL, en réutilisant la même approche que côté web).

Champ téléphone (connexion, inscription, achat, retrait créateur) : `lib/widgets/phone_number_field.dart`, indicatif pays + chiffres seuls, longueur plafonnée par pays via `lib/utils/phone.dart` (`phone_numbers_parser` pour les longueurs par pays, `country_code_picker` pour la liste des noms de pays — tous les pays proposés, pas seulement le Mali).

Mot de passe (connexion, inscription, inscription créateur) : `lib/widgets/pin_code_field.dart`, code à 4 chiffres uniquement (masqué, clavier numérique, plafonné à 4) — voir `apps/api/README.md` pour la justification et le throttling côté serveur.

Messagerie créateur ↔ modération (espace créateur) : `lib/widgets/messaging.dart`, style bulles de chat, `GET`/`POST /api/creator/messages` via `lib/models/message.dart`.

Signalement de vidéo (fiche vidéo) : `lib/widgets/report_section.dart`, même pattern que `purchase_section.dart` (lien repliable → formulaire de motif), `POST /api/videos/{id}/report`.

`flutter analyze`, `flutter test` (couvre la validation du formulaire de connexion) et `flutter build web` passent tous sans erreur.

```
flutter run       # lancer l'app
flutter analyze   # lint/analyse statique
flutter test      # tests widgets
```
