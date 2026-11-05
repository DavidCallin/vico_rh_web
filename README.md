# VICO RH PAIE

![Laravel](https://img.shields.io/badge/Laravel-FF2D20?style=flat-square)
![PHP](https://img.shields.io/badge/PHP-777BB4?style=flat-square)
![Database](https://img.shields.io/badge/Database-MySQL-4479A1?style=flat-square)
![API](https://img.shields.io/badge/API-REST-blue?style=flat-square)
![Status](https://img.shields.io/badge/Status-In%20Development-orange?style=flat-square)

**VICO RH PAIE** est une application web de gestion des ressources humaines et de la paie, conçue pour répondre aux besoins des petites entreprises au Bénin.

Elle centralise la gestion du personnel, des contrats de travail, des congés, des absences et des bulletins de paie. Elle intègre également un mécanisme de calcul de l'Impôt sur les traitements et salaires (ITS), adapté au contexte fiscal béninois.

L'interface métier est en français et les montants sont exprimés en francs CFA (XOF).

---

## Sommaire

- [Fonctionnalités](#fonctionnalités)
- [Gestion de la paie](#gestion-de-la-paie)
- [Gestion des contrats](#gestion-des-contrats)
- [Architecture et technologies](#architecture-et-technologies)
- [Modèle de données](#modèle-de-données)
- [API REST](#api-rest)
- [Installation et configuration](#installation-et-configuration)
- [Variables d'environnement](#variables-denvironnement)
- [Comptes de démonstration](#comptes-de-démonstration)
- [Tests et compilation](#tests-et-compilation)
- [Limites fonctionnelles](#limites-fonctionnelles)
- [Auteur](#auteur)

---

## Fonctionnalités

### 1. Gestion du personnel

- Création et gestion des fiches salariés.
- Gestion des postes et des départements.
- Gestion des catégories de personnel.
- Consultation des informations professionnelles.
- Filtrage des salariés selon leur statut.
- Gestion des contrats associés aux salariés.

### 2. Gestion des congés et absences

- Soumission des demandes de congé.
- Déclaration des absences.
- Approbation ou rejet des demandes par les ressources humaines.
- Suivi des demandes et de leur statut.
- Notifications dans l'application et par e-mail lors des différentes étapes.

### 3. Gestion de la paie

- Création et gestion des périodes de paie.
- Génération des bulletins pour les salariés couverts par un contrat.
- Calcul du salaire selon les conditions contractuelles.
- Prise en compte des jours calendaires couverts par la période.
- Déduction des congés sans solde et des absences non justifiées approuvés.
- Calcul automatique de l'ITS selon le barème intégré.
- Mise à disposition des bulletins dans l'espace salarié.
- Envoi des bulletins par e-mail.

### 4. Gestion des contrats

- Enregistrement des contrats de travail.
- Génération automatique des contrats au format PDF.
- Suivi des dates de début et de fin.
- Détection des contrats échus ou arrivant prochainement à échéance.
- Rappels automatiques avant expiration.

### 5. Notifications

Le système informe les utilisateurs des événements importants :

- Nouvelle demande de congé ou d'absence.
- Approbation ou rejet d'une demande.
- Mise à disposition d'un bulletin de paie.
- Contrat arrivé à échéance ou proche de son expiration.

Les notifications sont disponibles dans l'application et peuvent également être envoyées par e-mail.

### 6. Authentification et sécurité

L'application s'appuie sur les fonctionnalités d'authentification du kit Laravel déjà intégré.

Fonctionnalités disponibles :

- Inscription et connexion.
- Réinitialisation du mot de passe.
- Vérification de l'adresse e-mail.
- Authentification à deux facteurs.
- Clés d'accès (Passkeys).
- Gestion du profil.
- Paramètres de sécurité.
- Gestion de l'apparence.

Une API JSON sécurisée avec Laravel Sanctum permet également l'authentification et l'accès aux fonctionnalités prévues.

---

## Gestion de la paie

Le moteur de paie applique les règles de calcul définies dans l'application.

### Calcul du salaire

Pour chaque période de paie, un bulletin est généré pour chaque salarié couvert par un contrat.

Le calcul tient compte :

- Du salaire défini dans le contrat.
- Du nombre de jours calendaires couverts par la période.
- Des congés sans solde approuvés.
- Des absences non justifiées approuvées.

Les autres absences ne sont pas déduites du salaire.

### Calcul de l'ITS

Le calcul de l'Impôt sur les traitements et salaires est centralisé dans la classe :

`App\Payroll\BeninItsCalculator`

Le calcul repose sur le barème du Code général des impôts 2025, notamment les articles 122 et 125, ainsi que sur les dispositions relatives à la redevance ORTB/SRTB de mars et de juin.

La ligne ITS du bulletin est verrouillée : toute tentative de modification manuelle est refusée.

Le moteur de paie couvre actuellement un sous-ensemble des règles de calcul. Les limites sont détaillées dans la section [Limites fonctionnelles](#limites-fonctionnelles).

---

## Gestion des contrats

La gestion des contrats comprend un système de surveillance des échéances.

Les rappels concernent :

- Les contrats déjà échus.
- Les contrats arrivant à échéance dans les 30 prochains jours.

Les alertes sont accessibles depuis :

- Le tableau de bord.
- La liste des contrats.
- Les notifications de l'application.
- Les e-mails.

La commande Artisan suivante permet de déclencher les rappels :

```bash
php artisan contracts:remind
```

Cette commande est planifiée quotidiennement. L'ouverture du tableau de bord ou de la liste des contrats déclenche également les rappels dus.

---

## Architecture et technologies

### Backend et API

- Laravel
- PHP
- Laravel Fortify pour l'authentification et la sécurité.
- Laravel Sanctum pour l'authentification de l'API.
- API REST JSON.

### Base de données

- MySQL
- Migrations Laravel pour la gestion du schéma.
- Seeders pour l'initialisation des données.

### Frontend

L'application utilise l'environnement frontend configuré dans le projet. npm est utilisé pour la gestion des dépendances et la compilation des ressources.

---

## Modèle de données

Le modèle de données est organisé autour des principales entités métier :

| Entité | Description |
|---|---|
| Users | Comptes utilisateurs et authentification |
| Employees | Informations des salariés |
| Positions | Postes occupés |
| Departments | Départements de l'entreprise |
| Categories | Catégories de personnel |
| Contracts | Contrats de travail |
| Leaves | Demandes de congé |
| Absences | Déclarations d'absence |
| Payroll Periods | Périodes de paie |
| Payroll Items | Rubriques de paie |
| Payslips | Bulletins de paie |

---

## API REST

L'application expose une API JSON permettant notamment de gérer l'authentification et les salariés.

### Authentification

Les fonctionnalités disponibles comprennent :

- Inscription.
- Connexion.
- Déconnexion.
- Récupération des informations de l'utilisateur connecté.

### Gestion des salariés

L'API prend en charge les opérations CRUD :

- Création d'un salarié.
- Consultation de la liste des salariés.
- Consultation d'une fiche salarié.
- Modification des informations.
- Suppression d'un salarié.

L'authentification des requêtes API est assurée par Laravel Sanctum.

---

## Installation et configuration

### Prérequis

- PHP compatible avec la version de Laravel utilisée.
- Composer.
- Node.js et npm.
- MySQL.
- Git.

### 1. Cloner le dépôt

```bash
git clone https://github.com/DavidCallin/vico_rh_web.git
cd paie-benin
```

### 2. Installer les dépendances PHP

```bash
composer install
```

### 3. Configurer l'environnement

Créer le fichier d'environnement à partir du modèle fourni :

```bash
cp .env.example .env
```

Générer ensuite la clé de l'application :

```bash
php artisan key:generate
```

### 4. Créer la base de données MySQL

Créer une base de données nommée `paie_benin` dans MySQL.

Exemple avec le client MySQL :

```sql
CREATE DATABASE paie_benin
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
```

### 5. Configurer la connexion MySQL

Dans le fichier `.env`, renseigner les informations de connexion :

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=paie_benin
DB_USERNAME=root
DB_PASSWORD=
```

Adapter le nom d'utilisateur et le mot de passe selon la configuration de votre serveur MySQL.

### 6. Exécuter les migrations et seeders

```bash
php artisan migrate --seed
```

Cette commande crée les tables nécessaires et initialise les données de démonstration.

### 7. Installer les dépendances frontend

```bash
npm install
```

### 8. Démarrer l'application

Dans un premier terminal :

```bash
npm run dev
```

Dans un second terminal :

```bash
php artisan serve
```

L'application sera accessible à l'adresse :

`http://127.0.0.1:8000`

---

## Variables d'environnement

### Configuration de la base de données

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=paie_benin
DB_USERNAME=root
DB_PASSWORD=
```

### Configuration des e-mails

Le transport des e-mails est défini avec `MAIL_MAILER`.

En développement, la configuration suivante permet de consulter les e-mails générés dans les journaux Laravel :

```env
MAIL_MAILER=log
```

Pour utiliser un serveur SMTP :

```env
MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=
MAIL_PORT=
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME="${APP_NAME}"
```

### Configuration des contrats et de la paie

```env
PAYROLL_EMPLOYER_NAME=
PAYROLL_EMPLOYER_ADDRESS=
PAYROLL_EMPLOYER_CITY=Cotonou
PAYROLL_CONTRACT_REMINDER_DAYS=30
```

| Variable | Description |
|---|---|
| `PAYROLL_EMPLOYER_NAME` | Raison sociale imprimée sur les contrats |
| `PAYROLL_EMPLOYER_ADDRESS` | Adresse de l'entreprise |
| `PAYROLL_EMPLOYER_CITY` | Ville de l'entreprise |
| `PAYROLL_CONTRACT_REMINDER_DAYS` | Nombre de jours avant l'échéance pour déclencher un rappel |

Aucun mot de passe ni secret ne doit être versionné dans le dépôt Git. Le fichier `.env` doit rester exclu du suivi Git.

---

## Comptes de démonstration

Après l'exécution des seeders, deux comptes de démonstration sont disponibles.

| Profil | Adresse e-mail | Mot de passe |
|---|---|---|
| Ressources humaines | `rh@paie.bj` | `password` |
| Salarié | `employe@paie.bj` | `password` |

Le salarié de démonstration dispose :

- D'un CDD qui se termine à la fin du mois en cours.
- D'un contrat déjà arrivé à échéance.

La période de paie du mois en cours est ouverte. Le compte RH peut donc lancer la génération de la paie.

Ces identifiants sont réservés à la démonstration locale. Ils doivent être supprimés ou remplacés avant toute mise en production.

---

## Tests et compilation

### Exécuter les tests

```bash
php artisan test
```

### Compiler les ressources frontend

```bash
npm run build
```

---

## Limites fonctionnelles

Le calcul de paie actuel ne prend pas encore en charge :

- Les cotisations CNSS.
- Les avantages en nature.
- L'atténuation des rémunérations exceptionnelles, notamment les indemnités de fin de carrière et le treizième mois.

Ces règles nécessitent des confirmations complémentaires et ne font pas partie du périmètre actuel du moteur de calcul.

L'application ne doit donc pas être considérée comme couvrant à elle seule l'intégralité des obligations légales et fiscales liées à la paie au Bénin.

---

## Objectif du projet

Paie Bénin vise à simplifier la gestion administrative et salariale des petites entreprises béninoises en regroupant dans un même outil les opérations essentielles liées au personnel, aux contrats, aux absences et à la rémunération.

Le projet met l'accent sur l'automatisation des tâches répétitives, la centralisation des informations et la prise en compte des particularités du contexte béninois.

---

## Auteur

**Callin HOUNGA**

Développeur Web Backend / Full-Stack

Conception et développement de solutions numériques, d'applications web et d'outils de digitalisation des processus métiers.
