# CAHIER DES CHARGES

## Plateforme de streaming vidéo made in Mali

« StreamMali » Films, clips et web-séries des créateurs locaux

Version 1.0

Date : Août 2026

Pays cible : République du Mali

## Sommaire

## 1. Présentation du projet

StreamMali est une plateforme numérique de type « mini Netflix », pensée et adaptée au contexte malien, qui permet aux réalisateurs et créateurs de contenus locaux (films, clips musicaux, sketchs, web-séries) de publier leurs œuvres et de les monétiser directement auprès du public.

Le public (les fans) peut consulter le catalogue et acheter l'accès à un film ou un clip à l'unité, pour un tarif fixe et accessible de 25 FCFA par vidéo, payable en ligne via les moyens de paiement mobile disponibles au Mali.

La plateforme repose sur trois profils utilisateurs distincts : le Créateur, le Viewer (spectateur/fan) et le Modérateur, chacun disposant d'un espace et de droits spécifiques.

## 2. Contexte et justification

Le secteur du cinéma et de la musique au Mali (long-métrages, clips, sketchs comiques, contenus) connaît une forte production locale, mais souffre d'un manque de canal de diffusion et de monétisation adapté :

Les œuvres circulent surtout de façon informelle (clés USB, réseaux sociaux, WhatsApp), sans rémunération pour les créateurs.

Les plateformes internationales (Netflix, YouTube Premium…) sont peu adaptées au pouvoir d'achat local et ne mettent pas en avant la production malienne.

Le paiement mobile (Orange Money, Moov Money, Sama Money) est largement répandu et constitue un moyen de paiement de confiance pour les petites transactions.

StreamMali propose donc une solution locale, à très faible ticket d'entrée (25 FCFA par vidéo), qui valorise la création malienne tout en restant accessible au plus grand nombre.

## 3. Objectifs du projet

### 3.1 Objectif général

Mettre en place une plateforme web et mobile permettant la publication, la diffusion et la vente à l'unité de contenus vidéo produits par des créateurs maliens.

### 3.2 Objectifs spécifiques

Offrir aux créateurs un espace pour publier et gérer leurs films, clips et sketchs.

Permettre aux fans d'acheter et de visionner des vidéos en ligne pour 25 FCFA par film.

Garantir un contrôle qualité et une modération avant toute publication publique.

Assurer un système de paiement mobile fiable, avec répartition claire des revenus.

Fournir des statistiques de consultation et de vente aux créateurs et à l'administration.

Proposer une expérience utilisable même avec une connexion internet limitée (optimisation bande passante).

## 4. Acteurs et profils utilisateurs

La plateforme distingue trois profils, avec des droits d'accès différenciés.

## 5. Fonctionnalités détaillées par profil

### 5.1 Profil Créateur

Inscription et création d'un compte créateur (pièce d'identité).

Upload de vidéos (film, clip, sketch) avec titre, description, catégorie, affiche/jaquette, durée.

Soumission de la vidéo à la modération avant publication publique.

Suivi de statut de chaque contenu : en attente, validé, refusé (avec motif).

Fixation ou confirmation du prix de vente (par défaut 25 FCFA, ou tarif spécifique si autorisé).

Tableau de bord des statistiques : nombre de vues, nombre d'achats, revenus générés, évolution dans le temps.

Demande de retrait des revenus vers un compte Mobile Money ou bancaire.

Gestion du catalogue personnel : modification des informations, retrait d'une vidéo, mise en avant.

Messagerie ou notifications avec la modération (motif de refus, demande de correction).

### 5.2 Profil Viewer (fan)

Inscription simple (numéro de téléphone, Nom, Prénom).

Navigation dans le catalogue : recherche, filtres par catégorie (film, clip, sketch, série), par créateur, par popularité.

Aperçu gratuit (bande-annonce ou extrait de quelques secondes) avant achat.

Achat à l'unité d'une vidéo pour 25 FCFA via paiement mobile.

Accès illimité à la vidéo achetée, avec lecture en streaming.

Historique des achats et bibliothèque personnelle des vidéos acquises.

Système de favoris et de recommandations.

Notation et commentaires sur les vidéos visionnées.

Support / signalement d'un problème (paiement, lecture, contenu).

### 5.3 Profil Modérateur

Tableau de bord global : nombre de créateurs, nombre de viewers, volume de ventes, chiffre d'affaires.

File d'attente des vidéos soumises, avec visionnage avant validation.

Validation ou refus d'une vidéo, avec motif obligatoire en cas de refus (droits d'auteur, contenu inapproprié, qualité technique, etc.).

Dépublication d'une vidéo déjà en ligne en cas de signalement ou de plainte.

Gestion des comptes (créateurs et viewers) : suspension, blocage, vérification d'identité.

Suivi des transactions et des reversements aux créateurs.

Statistiques et rapports exportables (ventes, contenus les plus vus, revenus par créateur).

Gestion des catégories, mise en avant de contenus sur la page d'accueil.

## 6. Modèle économique

Le modèle repose sur la vente à l'unité (paiement à l'acte, « pay-per-view ») plutôt que sur un abonnement mensuel, afin de rester accessible :

Prix de vente standard : 25 FCFA par vidéo (film, clip ou sketch).

Commission de la plateforme : un pourcentage à définir (proposition indicative : 20 à 30 %) prélevé sur chaque vente pour couvrir les frais d'hébergement, de paiement et de fonctionnement.

Reversement au créateur : le solde restant, versé selon une périodicité hebdomadaire vers son compte Mobile Money.

Frais liés aux opérateurs de paiement mobile à la charge de la plateforme.

Montant minimum de retrait 10 000 F CFA.

## 7. Système de paiement en ligne

Compte tenu du contexte malien, le paiement doit s'appuyer prioritairement sur le Mobile Money :

Orange Money

Carte bancaire

Le parcours d'achat doit être simple : sélection de la vidéo → confirmation du prix (25 FCFA) → paiement via API Mobile Money (USSD ou push notification) → déverrouillage immédiat de la vidéo après confirmation du paiement.

## 8. Exigences fonctionnelles générales

Catalogue avec fiches vidéo (titre, jaquette, synopsis, durée, créateur, catégorie, note moyenne).

Lecteur vidéo en streaming adaptatif, avec plusieurs qualités selon la connexion.

Recherche et filtres (catégorie, créateur, popularité, nouveautés).

Système de notifications (nouvelle vidéo d'un créateur suivi, validation/refus, paiement confirmé).

Historique d'achat consultable et téléchargeable (reçu).

Gestion multilingue envisageable (français, bambara) pour l'interface.

Interface responsive : site web + application mobile (Android en priorité, vu le taux d'équipement au Mali).

## 9. Exigences non fonctionnelles

## 10. Architecture technique proposée (à valider)

Frontend web : application responsive (React, Vue ou équivalent).

Application mobile : Android natif ou multiplateforme (Flutter / React Native).

Backend : API REST sécurisée (Node.js, Laravel ou équivalent) avec base de données relationnelle.

Stockage et diffusion vidéo : service de streaming/CDN adapté (upload, transcodage, protection du flux).

Module de paiement : intégration API Mobile Money via une passerelle agrégée.

Back-office modérateur : tableau de bord d'administration séparé, avec gestion des rôles et droits.

## 11. Contraintes du projet

Connectivité internet parfois limitée dans certaines zones du Mali : prévoir une optimisation forte de la bande passante.

Nécessité de vérifier les droits d'auteur des contenus déposés par les créateurs (attestation de propriété).

Disponibilité et fiabilité des API de paiement Mobile Money (délais de confirmation, gestion des échecs).

Modération humaine nécessaire avant toute mise en ligne, ce qui implique un délai de traitement à communiquer aux créateurs.

Budget et ressources techniques disponibles pour l'hébergement vidéo (coût potentiellement élevé selon le volume).

## 12. Planning prévisionnel (indicatif)

## 13. Livrables attendus

Maquettes UI/UX (web et mobile) validées.

Application web responsive fonctionnelle.

Application mobile (au moins Android).

Back-office modérateur / administration.

Documentation technique et guide d'utilisation pour chaque profil.

Module de paiement intégré et testé.

## 14. Critères de validation et de réception

Un créateur peut s'inscrire, uploader une vidéo et suivre son statut de modération.

Un modérateur peut visionner, valider ou refuser une vidéo soumise, avec motif.

Un viewer peut acheter une vidéo validée pour 25 FCFA via Mobile Money et la visionner immédiatement.

Les statistiques (vues, ventes, revenus) sont visibles et correctes pour le créateur et le modérateur.

Les transactions sont sécurisées et tracées (historique de paiement disponible).



| Profil | Description | Accès principal |

| --- | --- | --- |

| Créateur (réalisateur, artiste, humoriste, etc.) | Producteur de contenu qui publie ses films, clips ou sketchs sur la plateforme. | Espace créateur : upload, gestion catalogue, statistiques, revenus. |

| Viewer (fan / spectateur) | Utilisateur final qui parcourt le catalogue, achète et visionne les vidéos. | Espace public : catalogue, achat, lecture, historique, favoris. |

| Modérateur (administrateur) | Contrôle la qualité et la conformité des contenus avant publication ; supervise la plateforme. | Back-office : validation des vidéos, statistiques globales, gestion des comptes, litiges. |





| Catégorie | Exigence |

| --- | --- |

| Performance | Chargement rapide même en connexion 3G/4G limitée ; compression et streaming adaptatif des vidéos. |

| Sécurité | Chiffrement des transactions, protection contre le téléchargement/piratage des vidéos, protection des données personnelles. |

| Disponibilité | Plateforme accessible 24h/24, hébergement avec sauvegarde régulière. |

| Scalabilité | Architecture capable de monter en charge (nombre de créateurs, de vidéos, de viewers). |

| Accessibilité | Interface simple, adaptée à des utilisateurs peu familiers du numérique ; faible consommation de données. |

| Conformité | Respect des droits d'auteur, mentions légales, conditions d'utilisation, politique de confidentialité. |





| Phase | Contenu | Durée estimée |

| --- | --- | --- |

| 1. Cadrage | Validation du cahier des charges, maquettes (UI/UX), choix technique | 2 semaines |

| 2. Développement MVP | Espaces créateur / viewer / modérateur, upload, catalogue, lecteur vidéo | 6 à 8 semaines |

| 3. Intégration paiement | Connexion Mobile Money, tests de transactions | 2 semaines |

| 4. Tests et recette | Tests fonctionnels, sécurité, correction des anomalies | 2 semaines |

| 5. Lancement pilote | Ouverture à un nombre restreint de créateurs et fans, ajustements | 3 à 4 semaines |

| 6. Déploiement général | Ouverture publique, communication | — |


