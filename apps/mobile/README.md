# StreamMali Mobile

Application mobile Flutter (Android prioritaire).

## Rôle

- Mêmes parcours que le web (catalogue, achat, lecture) côté Viewer, adaptés au mobile.
- Cache/téléchargement des vidéos achetées pour lecture en zone à connectivité faible.
- Notifications push (nouvelle vidéo, validation/refus, paiement confirmé).

## Statut

Scaffold Flutter initialisé (`flutter create`, package `com.streammali`), `flutter analyze` sans erreur. Plateformes générées par défaut : Android, iOS, web, linux, macos, windows — Android reste prioritaire, les autres peuvent être retirées si non nécessaires.

```
flutter run       # lancer l'app
flutter analyze   # lint/analyse statique
```
