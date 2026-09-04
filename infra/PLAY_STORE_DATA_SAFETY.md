# Formulaire "Sécurité des données" Play Console — aide-mémoire

Play Console (Présence sur le Store → Sécurité des données) demande de déclarer précisément quelles données sont collectées/partagées. Résumé basé sur ce que l'app collecte réellement (`apps/api/app/Domain/*`, `apps/mobile/lib/services/api_client.dart`) — à vérifier/cocher soi-même dans le formulaire, ceci n'est qu'un aide-mémoire, pas une soumission automatique.

## Cette app collecte-t-elle ou partage-t-elle des données utilisateur ?
Oui.

## Données collectées

| Donnée | Collectée | Partagée avec un tiers | Pourquoi | Optionnelle |
|---|---|---|---|---|
| Numéro de téléphone | Oui | Non (sauf Orange Money, voir note) | Inscription/connexion, identifiant de compte | Non (requis à l'inscription) |
| Nom | Oui | Non | Profil, affiché aux autres utilisateurs (créateur) | Non |
| Pièce d'identité (photo/PDF) | Oui, créateurs uniquement | Non | Vérification d'identité créateur (KYC), consultable par un modérateur uniquement | Non (requis pour devenir créateur) |
| Historique d'achats | Oui | Non | Fonctionnement du service (bibliothèque, reçus) | N/A (généré par l'usage) |
| Informations de paiement | Non stockées côté app — la saisie Mobile Money se fait sur la page Orange Money, hors de l'app | Oui, avec Orange Money (fournisseur Mobile Money ; PayDunya reste une option de repli, voir CLAUDE.md) | Traiter le paiement | N/A |
| Avis/notes | Oui | Non (visibles publiquement dans l'app par design) | Fonctionnalité du produit | Oui |
| Messages (créateur ↔ modération) | Oui | Non | Support | N/A |
| Identifiants de l'app (token de session) | Oui, stocké localement sur l'appareil (`shared_preferences`) | Non | Rester connecté | N/A |
| Rapports de crash (si Sentry activé) | Optionnel — inactif par défaut (voir `apps/mobile/lib/main.dart`, DSN vide tant qu'aucun compte Sentry n'est créé) | Oui, avec Sentry, si activé | Diagnostic technique | N/A |

## Pratiques de sécurité
- Données chiffrées en transit : **Oui** (HTTPS uniquement en production — le trafic non chiffré est explicitement bloqué en release, voir `network_security_config.xml` réservé au debug).
- L'utilisateur peut demander la suppression de ses données : **Oui** — "Supprimer mon compte" dans l'app (`AccountScreen`), et export des données ("Voir mes données").
- Cible principalement les enfants : **Non**.

## Notes
- Orange Money (fournisseur Mobile Money) est un sous-traitant de paiement, pas un partage marketing/publicitaire — répondre "oui, partagé" avec la finalité "traitement des paiements" dans le formulaire, pas "publicité".
- Aucune donnée n'est vendue ni utilisée à des fins publicitaires — l'app n'intègre aucun SDK publicitaire ni tracker tiers (vérifié : `pubspec.yaml` ne liste que `sentry_flutter` comme dépendance à vocation "analytics/diagnostic", et son DSN est vide par défaut).
