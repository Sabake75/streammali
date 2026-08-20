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

`flutter analyze`, `flutter test` et `flutter build web` passent tous sans erreur.

Pas encore fait (comme côté web dans un premier temps) : auth (connexion/inscription) et achat — même token Bearer Sanctum prévu, à ajouter en suivant `apps/web/src/lib/{auth-client,api-client}.ts` comme référence.

```
flutter run       # lancer l'app
flutter analyze   # lint/analyse statique
flutter test      # tests widgets
```
