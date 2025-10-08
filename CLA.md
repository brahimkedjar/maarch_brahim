**Maarch Courrier**

# Politique de contribution

## 1. Objectifs

Les objectifs de cette politique de contribution au logiciel Maarch Courrier sont de : 

- fixer les règles et principes à respecter pour l'ouverture du code source
- accompagner les contributeurs et partager les bonnes pratiques 
- définir la gouvernance de la politique de contribution de Maarch 

Ce document est à destination des développeurs agissant en qualité de personne physique ou à titre de personne morale, pour le compte d’une organisation.

## 2. Périmètre

Cette politique de contribution vise les nouveaux développements afin qu'ils respectent les bonnes pratiques. 

Pour l'ouverture de codes sources existants, des actions complémentaires seront nécessaires, telles que la définition du périmètre d'ouverture du code, sa revue qualité, sa revue sécurité, l'analyse de conformité et l'analyse de la propriété intellectuelle.

## 3. Responsabilités

La société éditrice Maarch produit et maintient ce document.

Pour toute question, ou demande d'évolutions, veuillez contacter nos services via le formulaire en ligne à l’adresse suivante : [https://maarch.com/contact/](https://maarch.com/contact/)

## 4.Principes d’ouverture des codes sources

### Reconnaissance des contributions

Afin de reconnaître la paternité des contributions, l'adresse électronique individuelle du développeur est utilisée.

Toutefois, au cas où un développeur ne souhaiterait pas voir son identité publiée, il peut utiliser un pseudonyme.

En revanche, l'utilisation d'adresses électroniques génériques ou anonymes est à proscrire.

Il est possible pour un développeur de contribuer sur un même projet dans le cadre du milieu professionnel et à titre personnel. Les contributions réalisées sur le temps professionnel doivent être associées à une adresse électronique professionnelle.

### Publication du code source sans obligation ni garantie

Aucune obligation de support et de prise en compte des demandes des utilisateurs ni plus généralement d'obligation d'animer la communauté. 

Pas de garanties au-delà de ce qui est prévu par la licence.

### Certification de l'origine des contributions (DCO)

Il est demandé aux contributeurs de signer un Certificat d'origine des contributions (Developer Certificate of Origin).

Le document en anglais est disponible à l’adresse suivante : [https://developercertificate.org/](https://developercertificate.org/)

## 5. Bonnes pratiques de contribution

### Système de suivi du code source

L'utilisation du système de suivi de version distribué Git est obligatoire.

### Choix de la licence

Les licences recommandées par défaut sont : 

- Permissive : Apache 2.0 
- Avec obligation de réciprocité : GNU GPL v3 (standard, lesser ou affero en fonction)

### Gestion des versions

Avoir une politique de gestion des versions est recommandé. Le guide de versioning sémantique ([Gestion sémantique de version 2.0.0 | Semantic Versioning](https://semver.org/lang/fr/)) est un exemple à suivre.

### Fichiers présents dans le dépôt

Assurez-vous d'avoir au minimum les fichiers **`README`**, **`CONTRIBUTING`** et **`LICENSE`**.

- `README` : description du projet. Peut décrire l'objectif à l'origine de la publication.
- `CONTRIBUTING` : guide de contribution, comment s'impliquer et identification du processus contribution et des licences.
- `LICENSE` : licence de publication du logiciel
- `MAINTAINERS` : liste des mainteneurs du projet (avec des droits de vote ou de commit généralement)

Ces fichiers doivent être en texte simple ou avec du marquage minimum (ie Markdown). Il n'est pas recommandé d'utiliser des formats binaires (ie PDF).

### Entête des fichiers sources

Conformément aux recommandations détaillées dans [https://reuse.software](https://reuse.software) chaque fichier de code source doit disposer de son auteur, de son identifiant de licence SPDX, ainsi que d'une copie de la licence dans le repository local.

Ces identifiants permettent de générer automatiquement des inventaires des licences sous la forme de « Bill of Material », afin de garantir la conformité du logiciel. L'ensemble des identifiants SPDX est disponible à cette adresse : [SPDX License List | Software Package Data Exchange (SPDX)](https://spdx.org/licenses/) 

### Traçabilité des développements (DCO)

Afin de garantir l'origine des contributions soumises, la mise en œuvre d'un **Developer's Certificate of Origin** est recommandée. Une traduction française est mise à disposition sur [https://github.com/DISIC/politique-de-contribution-open-source/blob/master/DCO-fr.txt](https://github.com/DISIC/politique-de-contribution-open-source/blob/master/DCO-fr.txt)

## 6. Bonnes pratiques de développement

Les bonnes pratiques de développement courantes s'appliquent également en contexte de développement ouvert, et notamment celles liées au respect des référentiels suivants : 

- [Référentiel général d'interopérabilité (RGI) | numerique.gouv.fr](https://www.numerique.gouv.fr/publications/interoperabilite/)

- [Référentiel général d’amélioration de l’accessibilité (RGAA) | numerique.gouv.fr](https://www.numerique.gouv.fr/publications/rgaa-accessibilite-numerique/)

- [Référentiel général de sécurité (RGS) | numerique.gouv.fr](https://www.numerique.gouv.fr/publications/referentiel-general-de-securite/)

L'ouverture du code vient par ailleurs amplifier l'importance de certaines de ces bonnes pratiques : 

- Documentation, à l'intérieur du code (commentaires et messages de commit) et hors du code.
- Conformité juridique dans l'utilisation de bibliothèques tierces. La très grande majorité des développements actuels reposant sur des bibliothèques Open Source tierces, il est nécessaire de s'assurer de la compatibilité de leurs licences respectives et du respect des obligations de celles-ci.
- Modularisation des développements afin de maximiser la réutilisation de code mais aussi d'isoler les éventuelles sources d'erreur
- Respect d'une unique convention de développement par projet.

## 7. Sécurité et confidentialité

### Interlocuteur identifié

Il est recommandé d'identifier un responsable de la sécurité du projet qui sera garant de vérifier le respect des bonnes pratiques mises en œuvre durant le développement, et de traiter les éventuels incidents de sécurité. Il est également préférable d'avoir recours à une adresse électronique dédiée, à destination du responsable identifié au moins, pour traiter des incidents de sécurité ou des problèmes liés à la propriété intellectuelle qui seraient découverts par un tiers.

### Développement sécurisé

- Écrire du code qui respecte des pratiques de sécurité reconnues et qui ne fait pas usage de constructions dangereuses dans le langage utilisé.
- Éliminer tous les messages de debug (par compilation conditionnelle ou par un contrôle via une variable à l'exécution) et toute information inutile pour l'utilisateur dans les messages d'erreur (e.g. trace d'appel PHP/JS) lors de la mise en production.
- Éliminer tout le code mort (i.e. code non appelé/non atteignable) car il pourrait prêter à confusion et/ou laisser penser qu'il est toujours fonctionnel et testé ; ce code, non maintenu, pourrait être réintégré à tort par un développeur.
- Toutes les entrées externes (e.g. de l'utilisateur) doivent être contrôlées avant leur utilisation ou leur stockage, selon les bonnes pratiques de sécurité en fonction de leur destination.

### Données secrètes/sensibles, cryptographie

- Aucun élément secret (tel qu'un mot de passe ou une clé cryptographique) ne doit être stocké dans le code ou dans les commentaires; avoir recours à des fichiers de configuration qui ne sont pas versionnés (cf .gitignore)
- Aucun élément secret ne doit être écrit par le programme en clair dans un fichier (y compris un fichier de journalisation) ou dans une base de données, toujours préférer une version hachée par une fonction de hachage reconnue à l'état de l'art et correctement utilisée (i.e salée pour chaque entrée)
- Aucun élément secret ne doit transiter en clair sur le réseau
- Ne pas implémenter soi-même de mécanisme cryptographique mais utiliser des bibliothèques reconnues en utilisant des paramètres et des suites cryptographiques robustes.

### Outils de développement et dépendances

- Utiliser, le cas échéant, des logiciels et des bibliothèques tierces maintenus et à jour des correctifs sécurité; préférer des bibliothèques reconnues, et les plus simples possibles
- Utiliser les services d'analyse de code offerts par la plateforme d'hébergement et traiter systématiquement avant intégration les problèmes remontés
- Ne pousser que des commits de code qui compilent, testés et fonctionnels, accompagnés des tests unitaires correspondants ; certaines plateformes offrent la possibilité de rejouer automatiquement les tests unitaires d'un projet afin d'assurer la non-régression.
- Respecter les recommandations et bonnes pratiques de sécurité émises par l'ANSSI applicables au projet.

***
