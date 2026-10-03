# Paie Bénin

Application web de gestion du personnel pour une petite entreprise au Bénin : congés, absences, contrats et bulletins de paie en francs CFA (XOF). L'interface métier est en français. L'authentification reprend le kit Laravel (Fortify) déjà en place.

## Déjà en place

- Connexion, inscription, réinitialisation du mot de passe, vérification d'e-mail, double authentification et clés d'accès.
- Profil, sécurité et apparence.
- API JSON (Sanctum) : inscription, connexion, déconnexion, utilisateur courant, et CRUD des salariés.
- Modèle de données : salariés, postes, départements, catégories, contrats, congés, absences, périodes de paie, rubriques et bulletins.

## Corrections

- Les migrations des postes et des salariés échouaient (`contrained` au lieu de `constrained`) : la clé étrangère n'était pas créée et `migrate` s'arrêtait.
- La colonne `payroll_items.isActive` ne correspondait pas au modèle (`is_active`).
- L'inscription web n'enregistrait pas `users.login`, colonne obligatoire : la création du compte échouait.
- La connexion API n'enregistrait pas `last_login_at`, absent des champs assignables.
- Le filtre « salariés actifs » cherchait le statut `actif`, alors que la base utilise `active`.
- La validation des salariés acceptait un statut et un genre incompatibles avec le schéma (`on_leave`, genre facultatif) et ne vérifiait pas le format de l'e-mail.
- La fiche salarié API ne renvoyait pas le département : la relation était appelée comme une méthode et la ressource cherchait une relation inexistante.

## Ajouts

- Demandes de congé et d'absence, avec approbation ou rejet.
- Notification dans l'application et e-mail à chaque nouvelle demande, approbation, rejet, et à la mise à disposition du bulletin.
- Période de paie : un bulletin par salarié couvert par un contrat. Le salaire suit le contrat, au prorata des jours calendaires couverts. Les congés sans solde et les absences non justifiées approuvés réduisent le brut. Les autres absences ne sont pas déduites.
- Ligne ITS verrouillée, calculée par `App\Payroll\BeninItsCalculator` (barème du Code général des impôts 2025, articles 122 et 125, redevance ORTB/SRTB de mars et de juin). Une tentative de modification est refusée.
- Le bulletin est visible dans l'espace Bulletins et envoyé par e-mail.
- Contrats enregistrés et PDF généré automatiquement.
- Rappels des contrats échus ou à moins de 30 jours de l'échéance (tableau de bord, liste des contrats, notification, e-mail). La commande `php artisan contracts:remind` est planifiée chaque jour ; l'ouverture du tableau de bord ou des contrats déclenche aussi les rappels dus.

Le calcul ne couvre pas les cotisations CNSS, les avantages en nature ni l'atténuation des rémunérations exceptionnelles (fin de carrière, treizième mois) : ces règles ne sont pas toutes confirmées ici et ne font pas partie de ce sous-ensemble.

## Lancer l'application

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
npm install
npm run dev
```

Dans un autre terminal : `php artisan serve`.

Comptes de démonstration après le seed (mot de passe `password`) :

- Ressources humaines : `rh@paie.bj`
- Salarié : `employe@paie.bj`

Le salarié de démonstration a un CDD qui se termine à la fin du mois en cours et un contrat déjà échu. La période du mois en cours est ouverte : le compte RH peut lancer la paie.

```bash
npm run build
php artisan test
```

## Variables d'environnement

Courriel : le transport se règle avec `MAIL_MAILER`. La valeur `log` écrit le message dans `storage/logs/laravel.log` (repli de développement, aucun serveur SMTP). Pour un envoi réel :

- `MAIL_MAILER=smtp`
- `MAIL_SCHEME` (`smtp` ou `smtps`)
- `MAIL_HOST`
- `MAIL_PORT`
- `MAIL_USERNAME`
- `MAIL_PASSWORD`
- `MAIL_FROM_ADDRESS`
- `MAIL_FROM_NAME`

Aucun mot de passe n'est versionné.

Paie et contrats :

- `PAYROLL_EMPLOYER_NAME` : raison sociale imprimée sur le contrat
- `PAYROLL_EMPLOYER_ADDRESS`
- `PAYROLL_EMPLOYER_CITY` (Cotonou par défaut)
- `PAYROLL_CONTRACT_REMINDER_DAYS` : délai avant échéance, 30 jours par défaut

Les montants sont en F CFA (XOF).
