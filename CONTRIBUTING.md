# Contributing

Merci de vouloir contribuer au projet LoupsGarous Status.

Ce document explique les règles à suivre pour garder un code propre, lisible et simple à maintenir.

## Prérequis

Avant de contribuer, installe :

* PHP 8.3 ou supérieur
* Composer
* MySQL ou MariaDB
* Docker et Docker Compose si tu travailles avec Docker

## Installation

Clone le projet :

```bash
git clone https://example.com/mon-organisation/loupsgarous-status.git
cd loupsgarous-status
```

Installe les dépendances :

```bash
composer install
```

Copie l’environnement :

```bash
cp .env.example .env
```

Configure `.env` avec des valeurs locales.

Lance les migrations :

```bash
composer migrate
```

Lance les seeds :

```bash
composer seed
```

Lance l’application :

```bash
composer start
```

Lance le worker dans un autre terminal :

```bash
php bin/status-worker.php
```

## Branches

Utilise des branches claires.

Exemples :

```txt
feature/discord-notifications
feature/admin-audit-log
fix/status-cache-refresh
fix/mobile-admin-layout
refactor/status-payload-builder
```

## Commits

Utilise des messages courts et précis.

Exemples :

```txt
Add status worker cache
Fix endpoint form layout
Add Discord webhook notifications
Refactor downtime repository
Update database migrations
```

## Pull requests

Une pull request doit contenir :

* un titre clair
* une description courte
* les changements principaux
* les tests réalisés
* les migrations ajoutées si nécessaire
* les impacts sur Docker ou la production

Exemple :

```txt
Title: Add worker based status cache

Description:
This PR moves endpoint checks from user requests to a dedicated worker.

Changes:
- add status worker
- add JSON cache repository
- update /api/status to read cache
- update Docker with worker service
- update README

Tests:
- composer migrate
- composer seed
- php bin/status-worker.php
- manual check on /api/status
```

## Style de code

Règles générales :

* PHP strict types
* classes final quand possible
* logique métier dans `src/Application/Service`
* accès SQL dans `src/Infrastructure/Persistence`
* objets métier dans `src/Domain`
* routes simples dans `app/routes.php`
* pas de requêtes SQL dans les vues
* pas de logique métier dans les vues

## Structure attendue

```txt
Application
  -> Actions HTTP
  -> Services métier
  -> Middlewares

Domain
  -> Objets métier
  -> Interfaces

Infrastructure
  -> Repositories SQL
  -> Notifications externes
  -> Connexion DB

Views
  -> Pages PHP
  -> Partials

Worker
  -> Checks périodiques
  -> Génération du cache
  -> Notifications Discord
```

## Base de données

Le projet utilise Phinx.

Pour ajouter une modification SQL :

```bash
vendor/bin/phinx create AddSomethingToTable
```

Puis écris une migration avec :

```php
public function up(): void
{
    // appliquer la modification
}

public function down(): void
{
    // annuler la modification
}
```

Lance ensuite :

```bash
composer migrate
```

Évite de modifier la base à la main sans migration.

## Seeds

Les seeds servent aux données de départ.

Exemples :

```txt
SettingsSeeder.php
AdminUserSeeder.php
```

Lance les seeds :

```bash
composer seed
```

Ne mets jamais de vraie donnée sensible dans une seed.

Utilise `.env` pour les identifiants locaux.

## Worker

Le worker est responsable des checks HTTP.

Il met à jour :

```txt
var/cache/status_snapshot.json
```

La page publique lit ce cache.

Ne remets pas les checks HTTP dans `/api/status`.

## Notifications Discord

Chaque endpoint a son webhook.

Ne commit jamais :

```txt
vraie URL de webhook
token Discord
identifiant SQL
mot de passe admin
fichier .env
```

Utilise des exemples fictifs :

```txt
https://discord.com/api/webhooks/example/example-token
```

## Tests avant pull request

Avant d’ouvrir une pull request, lance :

```bash
composer dump-autoload
composer migrate
composer seed
composer test
php -l chemin/du/fichier.php
```

Avec Docker :

```bash
docker-compose up -d --build
docker-compose logs -f worker
docker-compose exec slim composer migrate
docker-compose exec slim composer seed
```

Teste au minimum :

```txt
/
 /api/status
/admin/login
/admin
```

## Checklist

Avant de demander une review :

* le code fonctionne en local
* le worker tourne
* le cache est généré
* les migrations passent
* les seeds passent
* aucune donnée sensible n’est commit
* le README est à jour
* Docker fonctionne encore
* l’interface reste correcte sur mobile

## Données sensibles

Ne commit jamais :

```txt
.env
webhooks Discord réels
mots de passe
tokens
identifiants SQL
logs
cache généré
dumps SQL privés
```

## Questions

Pour une modification importante, ouvre d’abord une issue ou une discussion technique dans la pull request.
