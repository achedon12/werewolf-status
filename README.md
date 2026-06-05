# LoupsGarous Status

Status page pour l’écosystème LoupsGarous.

Ce projet permet de surveiller l’état de plusieurs services, de garder un historique des downtimes et d’afficher une page claire pour suivre la disponibilité des endpoints.

Il fait partie d’un ensemble plus large autour de LoupsGarous, qui regroupe le site principal, des API, des outils de monitoring et une administration centralisée.

## Objectif du projet

LoupsGarous Status sert à répondre à une question simple :

> Est-ce que les services LoupsGarous sont disponibles en ce moment ?

L’application vérifie les endpoints configurés, détecte les pannes, sauvegarde les périodes de downtime en base SQL et affiche un historique visuel sur une période choisie.

## Fonctionnalités

### Page publique

* Statut global des services
* Liste des endpoints surveillés
* Pourcentage d’uptime
* Historique visuel avec 24 carrés
* Couleurs selon l’état du service
* Affichage des downtimes partiels
* Affichage de la version, de l’auteur et de l’email du projet surveillé
* Auto-refresh côté navigateur

### Panneau admin

* Connexion admin avec session PHP
* Gestion des endpoints
* Ajout d’un endpoint
* Modification d’un endpoint
* Suppression d’un endpoint
* Activation ou désactivation d’un endpoint
* Gestion de l’unité d’uptime
* Gestion des admins
* Modification de son propre mot de passe
* Choix de la durée d’affichage

### Monitoring

* Sauvegarde des downtimes en SQL
* Détection du début d’un downtime
* Détection du retour en ligne
* Calcul de l’uptime sur une période configurable
* Support des downtimes complets et partiels
* Préparation des notifications Discord

## Aperçu du fonctionnement

À chaque check, l’application suit ce cycle :

```txt
Endpoints SQL
    ↓
Requête HTTP
    ↓
Analyse du statut
    ↓
Sauvegarde downtime si besoin
    ↓
Calcul des statistiques
    ↓
Affichage public
```

Si un endpoint tombe, une ligne est ajoutée dans la table `downtimes`.

Si l’endpoint revient en ligne, cette même ligne reçoit une valeur `up_at`.

## Couleurs de l’historique

Chaque service affiche 24 carrés.

La couleur dépend de l’état du créneau :

```txt
Vert    = service en ligne pendant tout le créneau
Orange  = downtime partiel pendant le créneau
Rouge   = downtime complet pendant le créneau
Gris    = service hors ligne ou statut inexploitable
```

La durée d’un carré dépend de la durée d’affichage choisie dans l’admin.

Exemples :

```txt
24h  = 1 carré vaut 1h
48h  = 1 carré vaut 2h
72h  = 1 carré vaut 3h
168h = 1 carré vaut 7h
720h = 1 carré vaut 30h
```

## Stack technique

* PHP 8.3 ou supérieur
* SlimPHP 4
* PHP-DI
* PDO
* MySQL ou MariaDB
* TailwindCSS
* JavaScript
* Docker
* Monolog
* Dotenv

## Structure du projet

```txt
app/
  routes.php
  views/
    status.php
    admin/
      index.php
      login.php
    partials/
      ServiceCard.php
      StatusHelpers.php

public/
  index.php
  favicon.ico
  logo.png
  assets/
    js/
      status.js

src/
  Application/
    Actions/
      Admin/
      Status/
    Middleware/
    Service/

  Domain/
    Admin/
    Status/

  Infrastructure/
    Persistence/
      Admin/
      Database/
      Status/

database/
  schema.sql

logs/
```

## Prérequis

Pour lancer le projet en local :

* PHP 8.3 ou supérieur
* Composer
* MySQL ou MariaDB
* Extension PHP PDO MySQL
* Extension PHP cURL

Pour lancer avec Docker :

* Docker
* Docker Compose

## Installation en local

Clone le dépôt :

```bash
git clone <url-du-repo>
cd <nom-du-projet>
```

Installe les dépendances :

```bash
composer install
```

Copie le fichier d’environnement :

```bash
cp .env.example .env
```

Configure le fichier `.env` :

```env
APP_ENV=dev

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=status_page
DB_USERNAME=root
DB_PASSWORD=

SESSION_SECURE=false

DISCORD_WEBHOOK_URL=
```

Prépare la base de données :

```sql
CREATE DATABASE status_page CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Importe le schéma :

```bash
mysql -u root -p status_page < database/schema.sql
```

Lance le serveur local :

```bash
composer start
```

Ouvre le projet :

```txt
http://localhost:8080
```

## Installation avec Docker

Lance les conteneurs :

```bash
docker-compose up -d
```

Installe les dépendances si nécessaire :

```bash
docker-compose exec app composer install
```

Importe le schéma SQL :

```bash
docker-compose exec db mysql -u root -p status_page < database/schema.sql
```

Ouvre le projet :

```txt
http://localhost:8080
```

## Configuration SQL

Le projet utilise 4 tables principales.

### endpoints

Contient les services surveillés.

Champs importants :

```txt
id
name
check_url
public_url
uptime_unit
is_enabled
discord_notifications_enabled
created_at
updated_at
```

### downtimes

Contient l’historique des pannes.

Champs importants :

```txt
id
endpoint_id
down_at
up_at
http_code
reason
discord_notified_at
created_at
```

Un downtime en cours possède :

```txt
up_at = NULL
```

### settings

Contient les paramètres globaux.

Exemple :

```txt
display_period_hours
```

Valeurs supportées :

```txt
1
3
6
12
24
48
72
168
336
720
```

### admin_users

Contient les comptes admin.

Les mots de passe sont stockés avec `password_hash()`.

## Ajouter un premier admin

Génère un hash :

```bash
php -r "echo password_hash('motdepasse', PASSWORD_DEFAULT);"
```

Ajoute l’admin en SQL :

```sql
INSERT INTO admin_users (
    username,
    password_hash,
    role,
    is_enabled,
    created_at,
    updated_at
) VALUES (
    'admin',
    'HASH_ICI',
    'admin',
    1,
    NOW(),
    NOW()
);
```

Connexion admin :

```txt
http://localhost:8080/admin/login
```

## Ajouter un endpoint

Un endpoint se configure avec :

```txt
Nom
URL de check
URL publique
Unité d’uptime
Notifications Discord
```

Exemple :

```txt
Nom : Loups Garous
URL de check : https://loupsgarous.net/api/health
URL publique : https://loupsgarous.net
Uptime : seconds
```

## Unités d’uptime supportées

```txt
seconds
milliseconds
timestamp_seconds
timestamp_milliseconds
```

Exemples :

```txt
3600 avec seconds = 1h
3600000 avec milliseconds = 1h
1717600000 avec timestamp_seconds = timestamp Unix
1717600000000 avec timestamp_milliseconds = timestamp Unix en ms
```

## Notifications Discord

Le projet prévoit l’envoi de notifications Discord via webhook.

Règle prévue :

```txt
Endpoint tombe
    → notification envoyée une fois

Endpoint reste down
    → aucune nouvelle notification

Endpoint revient online
    → downtime fermé

Endpoint retombe plus tard
    → nouvelle notification possible
```

Configuration dans `.env` :

```env
DISCORD_WEBHOOK_URL=https://discord.com/api/webhooks/...
```

## Routes principales

```txt
GET  /                  Page publique de status
GET  /api/status        API utilisée par le frontend

GET  /admin/login       Page de connexion admin
POST /admin/login       Connexion admin
POST /admin/logout      Déconnexion admin

GET  /admin             Panneau admin

POST /admin/settings

POST /admin/endpoints
POST /admin/endpoints/{id}/update
POST /admin/endpoints/{id}/delete
POST /admin/endpoints/{id}/toggle

POST /admin/admins
POST /admin/admins/{id}/password
POST /admin/admins/{id}/toggle
POST /admin/admins/{id}/delete
```

## Déploiement

Cette section décrit une méthode simple pour déployer le projet sur un serveur Linux avec Git, Composer, PHP-FPM, Nginx et MySQL ou MariaDB.

### Prérequis serveur

Le serveur doit avoir :

* PHP 8.3 ou supérieur
* Composer
* MySQL ou MariaDB
* Nginx ou Apache
* Extension PHP PDO MySQL
* Extension PHP cURL
* Extension PHP mbstring
* Extension PHP json
* Git

### Préparer le projet

Clone le dépôt sur le serveur :

```bash
cd /var/www
git clone <url-du-repo> loupsgarous-status
cd loupsgarous-status
```

Installe les dépendances en mode production :

```bash
composer install --no-dev --optimize-autoloader
```

Copie le fichier d’environnement :

```bash
cp .env.example .env
```

Configure `.env` :

```env
APP_ENV=prod

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=status_page
DB_USERNAME=status_user
DB_PASSWORD=mot_de_passe_fort

SESSION_SECURE=true

DISCORD_WEBHOOK_URL=https://discord.com/api/webhooks/...
```

### Préparer la base de données

Connecte-toi à MySQL :

```bash
mysql -u root -p
```

Crée la base et l’utilisateur :

```sql
CREATE DATABASE status_page CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER 'status_user'@'localhost' IDENTIFIED BY 'mot_de_passe_fort';

GRANT ALL PRIVILEGES ON status_page.* TO 'status_user'@'localhost';

FLUSH PRIVILEGES;
```

Importe le schéma :

```bash
mysql -u status_user -p status_page < database/schema.sql
```

### Ajouter le premier admin en production

Génère un hash :

```bash
php -r "echo password_hash('motdepasse', PASSWORD_DEFAULT);"
```

Ajoute l’admin :

```sql
INSERT INTO admin_users (
    username,
    password_hash,
    role,
    is_enabled,
    created_at,
    updated_at
) VALUES (
    'admin',
    'HASH_ICI',
    'admin',
    1,
    NOW(),
    NOW()
);
```

### Permissions

Le dossier `logs/` doit être accessible en écriture par PHP.

Exemple avec Nginx et PHP-FPM :

```bash
sudo chown -R www-data:www-data /var/www/loupsgarous-status
sudo chmod -R 755 /var/www/loupsgarous-status
sudo chmod -R 775 /var/www/loupsgarous-status/logs
```

Si le dossier `logs/` n’existe pas :

```bash
mkdir logs
```

### Configuration Nginx

Exemple de configuration :

```nginx
server {
    listen 80;
    server_name status.example.com;

    root /var/www/loupsgarous-status/public;
    index index.php;

    access_log /var/log/nginx/loupsgarous-status.access.log;
    error_log /var/log/nginx/loupsgarous-status.error.log;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\. {
        deny all;
    }
}
```

Active le site :

```bash
sudo ln -s /etc/nginx/sites-available/loupsgarous-status /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### HTTPS avec Certbot

Installe Certbot :

```bash
sudo apt install certbot python3-certbot-nginx
```

Génère le certificat :

```bash
sudo certbot --nginx -d status.example.com
```

Après HTTPS, vérifie dans `.env` :

```env
SESSION_SECURE=true
```

### Déployer une mise à jour

À chaque mise à jour :

```bash
cd /var/www/loupsgarous-status
git pull
composer install --no-dev --optimize-autoloader
composer dump-autoload
```

Si le schéma SQL change, applique les nouvelles requêtes SQL nécessaires.

Puis recharge PHP-FPM :

```bash
sudo systemctl reload php8.3-fpm
```

Et Nginx :

```bash
sudo systemctl reload nginx
```

### Vérifications après déploiement

Vérifie la page publique :

```txt
https://status.example.com
```

Vérifie l’API :

```txt
https://status.example.com/api/status
```

Vérifie l’admin :

```txt
https://status.example.com/admin/login
```

Vérifie aussi les logs :

```bash
tail -f logs/app.log
tail -f /var/log/nginx/loupsgarous-status.error.log
```

### Déploiement avec Docker

Sur le serveur :

```bash
git clone <url-du-repo> loupsgarous-status
cd loupsgarous-status
cp .env.example .env
```

Configure `.env`, puis lance :

```bash
docker-compose up -d --build
```

Installe les dépendances dans le conteneur si elles ne sont pas déjà installées :

```bash
docker-compose exec app composer install --no-dev --optimize-autoloader
```

Importe le schéma SQL :

```bash
docker-compose exec db mysql -u root -p status_page < database/schema.sql
```

Pour mettre à jour :

```bash
git pull
docker-compose up -d --build
docker-compose exec app composer install --no-dev --optimize-autoloader
```

### Points importants en production

Ne versionne jamais le fichier `.env`.

Garde `APP_ENV=prod`.

Utilise `SESSION_SECURE=true` si le site est en HTTPS.

Utilise un mot de passe SQL fort.

Garde le dossier `public/` comme racine web.

Ne pointe jamais Nginx ou Apache vers la racine complète du projet.

Vérifie régulièrement les logs.

Sauvegarde la base SQL, car elle contient les endpoints, les admins et l’historique des downtimes.

## Commandes utiles

Régénérer l’autoload :

```bash
composer dump-autoload
```

Vérifier un fichier PHP :

```bash
php -l chemin/du/fichier.php
```

Lancer le projet :

```bash
composer start
```

Lancer avec Docker :

```bash
docker-compose up -d
```

Arrêter Docker :

```bash
docker-compose down
```

Lancer les tests :

```bash
composer test
```

## Architecture

Le projet suit une organisation proche du skeleton SlimPHP.

```txt
Application
  Actions HTTP
  Services métier
  Middlewares

Domain
  Objets métier
  Interfaces

Infrastructure
  Repositories SQL
  Connexion DB
  Services externes

Views
  Pages PHP
  Partials d’affichage

Public
  Point d’entrée
  Assets accessibles par le navigateur
```

## Règles de développement

Les routes restent simples.

La logique métier va dans les services.

Les requêtes SQL vont dans les repositories.

Les vues gèrent l’affichage.

Les endpoints ne sont pas codés en dur.

Les downtimes sont stockés en SQL.

Les actions admin passent par des sessions protégées.

## État du projet

Le projet est en développement actif.

Objectifs à venir :

```txt
Finaliser les notifications Discord
Ajouter plus de statistiques
Ajouter une vue historique détaillée
Améliorer les rôles admin
Ajouter des tests
Améliorer l’interface mobile
```

## Licence

Projet privé lié à l’écosystème [LoupsGarous](https://loupsgarous.net/) · by Léo Deroin.

