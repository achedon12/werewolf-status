# LoupsGarous Status

> Status page basée sur SlimPHP 4 pour surveiller plusieurs endpoints, historiser les downtimes et afficher un état public clair.

## Sommaire

* Présentation
* Fonctionnement général
* Fonctionnalités
* Stack technique
* Structure du projet
* Prérequis
* Variables d’environnement
* Installation en local
* Lancement avec Docker
* Base de données
* Migrations et seeds
* Worker de monitoring
* Notifications Discord
* Déploiement en production
* Commandes utiles

## Présentation

LoupsGarous Status est une application de monitoring légère.

Elle sert à afficher l’état de plusieurs services depuis une page publique, tout en gardant un historique des périodes d’indisponibilité.

Le projet s’intègre dans un écosystème plus large autour de LoupsGarous, avec un site principal, des APIs, des endpoints de statut et une interface d’administration.

## Fonctionnement général

Le projet est séparé en deux parties.

```txt
Application web
  -> affiche la page publique
  -> affiche le panneau admin
  -> lit le dernier statut depuis un cache JSON

Worker
  -> vérifie les endpoints toutes les 30 secondes
  -> sauvegarde les downtimes en SQL
  -> génère le cache JSON
  -> envoie les notifications Discord
```

Les visiteurs ne déclenchent pas les requêtes HTTP vers les endpoints surveillés.

La page publique lit le dernier état généré par le worker.

## Architecture de monitoring

Avant :

```txt
Utilisateur
  -> /api/status
  -> check de tous les endpoints
  -> sauvegarde SQL
  -> réponse JSON
```

Maintenant :

```txt
Worker serveur toutes les 30 secondes
  -> check de tous les endpoints
  -> sauvegarde SQL
  -> mise à jour du cache JSON

Utilisateur
  -> /api/status
  -> lecture du cache JSON
  -> affichage
```

Résultat :

```txt
1 visiteur ou 500 visiteurs
  -> même nombre de checks vers les endpoints surveillés
```

## Fonctionnalités

### Page publique

* Statut global des services
* Liste des endpoints surveillés
* Pourcentage d’uptime
* Historique visuel avec 24 carrés
* Affichage des downtimes partiels
* Couleurs selon l’état du service
* Lecture depuis le cache JSON
* Auto-refresh côté navigateur
* Infos projet via endpoint dédié

### Panneau admin

* Connexion admin avec session PHP
* Gestion des endpoints
* Ajout, modification et suppression d’endpoints
* Activation ou désactivation d’un endpoint
* Gestion de l’unité d’uptime
* Gestion des admins
* Modification de son propre mot de passe
* Choix de la durée d’affichage
* Toggle pour les notifications Discord
* Webhook Discord configurable par endpoint

### Monitoring

* Détection du début d’un downtime
* Détection du retour en ligne
* Historique SQL des downtimes
* Calcul d’uptime sur une période configurable
* Worker dédié aux checks
* Cache JSON pour la page publique
* Notifications Discord au début et à la fin d’un downtime

## Couleurs de l’historique

Chaque service affiche 24 carrés.

```txt
Vert    -> service en ligne pendant tout le créneau
Orange  -> downtime partiel pendant le créneau
Rouge   -> downtime complet pendant le créneau
Gris    -> statut non disponible
```

La durée d’un carré dépend du paramètre `display_period_hours`.

Exemples :

```txt
24h  -> 1 carré vaut 1h
48h  -> 1 carré vaut 2h
72h  -> 1 carré vaut 3h
168h -> 1 carré vaut 7h
720h -> 1 carré vaut 30h
```

## Stack technique

* PHP 8.3 ou supérieur
* SlimPHP 4
* PHP-DI
* PDO
* MySQL ou MariaDB
* Phinx
* TailwindCSS
* JavaScript
* Docker
* Monolog
* Dotenv
* cURL

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

bin/
  status-worker.php

database/
  schema.sql
  migrations/
    20260608154342_init_status_schema.php
  seeds/
    SettingsSeeder.php
    AdminUserSeeder.php

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
    Middleware/
    Service/

  Domain/
    Admin/
    Status/

  Infrastructure/
    Notification/
    Persistence/

var/
  cache/

logs/
```

## Prérequis

### Local

* PHP 8.3 ou supérieur
* Composer
* MySQL ou MariaDB
* Extension PHP PDO MySQL
* Extension PHP cURL
* Extension PHP mbstring
* Extension PHP json

### Docker

* Docker
* Docker Compose

### Production

* PHP 8.3 ou supérieur
* Composer
* MySQL ou MariaDB
* Nginx ou Apache
* PHP-FPM
* Supervisor
* Git

## Variables d’environnement

Copie le fichier d’exemple :

```bash
cp .env.example .env
```

Exemple local :

```env
APP_ENV=dev

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=status_page
DB_USERNAME=root
DB_PASSWORD=

SESSION_SECURE=false

STATUS_CHECK_INTERVAL=30

INFO_URL=https://mon-endpoint.com/api/infos

ADMIN_USER_NAME=admin
ADMIN_USER_PASSWORD=admin1234
```

Exemple production :

```env
APP_ENV=prod

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=status_page
DB_USERNAME=status_user
DB_PASSWORD=change_this_password

SESSION_SECURE=true

STATUS_CHECK_INTERVAL=30

INFO_URL=https://mon-endpoint.com/api/infos

ADMIN_USER_NAME=admin
ADMIN_USER_PASSWORD=change_this_admin_password
```

## Installation en local

Clone le dépôt :

```bash
git clone https://example.com/mon-organisation/loupsgarous-status.git
cd loupsgarous-status
```

Installe les dépendances :

```bash
composer install
```

Configure `.env`.

Prépare la base :

```sql
CREATE DATABASE status_page CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Lance les migrations :

```bash
composer migrate
```

Lance les seeds :

```bash
composer seed
```

Lance l’application web :

```bash
composer start
```

Dans un deuxième terminal, lance le worker :

```bash
php bin/status-worker.php
```

Accès local :

```txt
Page publique : http://localhost:8080
Admin : http://localhost:8080/admin/login
API : http://localhost:8080/api/status
```

## Lancement avec Docker

Lance les conteneurs :

```bash
docker-compose up -d --build
```

Installe les dépendances si nécessaire :

```bash
docker-compose exec slim composer install
```

Lance les migrations :

```bash
docker-compose exec slim composer migrate
```

Lance les seeds :

```bash
docker-compose exec slim composer seed
```

Logs de l’application :

```bash
docker-compose logs -f slim
```

Logs du worker :

```bash
docker-compose logs -f worker
```

Accès :

```txt
Page publique : http://localhost:8080
Admin : http://localhost:8080/admin/login
```

## Exemple de docker-compose.yml

```yaml
version: "3.8"

services:
  slim:
    build: .
    container_name: loupsgarous-status-app
    working_dir: /var/www
    command: php -S 0.0.0.0:8080 -t public
    ports:
      - "8080:8080"
    volumes:
      - .:/var/www
      - logs:/var/www/logs
      - cache:/var/www/var/cache
    env_file:
      - .env
    environment:
      docker: "true"
      DB_HOST: db
      DB_PORT: 3306
    depends_on:
      - db

  worker:
    build: .
    container_name: loupsgarous-status-worker
    working_dir: /var/www
    command: php bin/status-worker.php
    volumes:
      - .:/var/www
      - logs:/var/www/logs
      - cache:/var/www/var/cache
    env_file:
      - .env
    environment:
      docker: "true"
      DB_HOST: db
      DB_PORT: 3306
    depends_on:
      - db
    restart: unless-stopped

volumes:
  logs:
    driver: local

  cache:
    driver: local

  db_data:
    driver: local
```

## Exemple de Dockerfile

```dockerfile
FROM php:8.3-cli-alpine

WORKDIR /var/www

RUN apk add --no-cache \
    git \
    unzip \
    curl-dev \
    oniguruma-dev \
    mariadb-client

RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    curl \
    mbstring

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . .

RUN composer install
```

## Base de données

Le projet utilise 4 tables principales.

### endpoints

Stocke les services surveillés.

```txt
id
name
check_url
public_url
uptime_unit
is_enabled
discord_notifications_enabled
discord_webhook_url
created_at
updated_at
```

### downtimes

Stocke l’historique des pannes.

```txt
id
endpoint_id
down_at
up_at
http_code
reason
discord_down_notified_at
discord_up_notified_at
created_at
```

Un downtime en cours possède :

```txt
up_at = NULL
```

### settings

Stocke les paramètres globaux.

Exemples :

```txt
display_period_hours
status_check_interval
```

### admin_users

Stocke les comptes admin.

Les mots de passe sont stockés avec `password_hash()`.

## Migrations et seeds

Le projet utilise Phinx.

### Migrations

Les migrations gèrent la structure SQL.

```bash
composer migrate
```

Statut des migrations :

```bash
composer status-db
```

Rollback :

```bash
composer rollback
```

### Seeds

Les seeds ajoutent les données de départ.

```bash
composer seed
```

Seeds actuels :

```txt
SettingsSeeder.php
  -> ajoute les paramètres par défaut

AdminUserSeeder.php
  -> ajoute ou met à jour l’admin défini dans .env
```

## Cache status

Le worker génère le fichier :

```txt
var/cache/status_snapshot.json
```

La page publique et `/api/status` lisent ce fichier.

Si la page indique que le cache n’existe pas, lance le worker :

```bash
php bin/status-worker.php
```

Avec Docker :

```bash
docker-compose logs -f worker
```

## Notifications Discord

Chaque endpoint dispose de son propre webhook Discord.

Quand un endpoint tombe :

```txt
downtime ouvert
notification Discord envoyée
discord_down_notified_at rempli
```

Quand l’endpoint revient en ligne :

```txt
downtime fermé
notification Discord envoyée
discord_up_notified_at rempli
```

Tant que le downtime reste ouvert, aucune notification supplémentaire n’est envoyée.

Exemple de webhook fictif :

```txt
https://discord.com/api/webhooks/example/example-token
```

## Routes principales

```txt
GET  /                  Page publique de status
GET  /api/status        API lue par le frontend

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

## Déploiement en production

### Préparation

Clone le dépôt :

```bash
cd /var/www
git clone https://example.com/mon-organisation/loupsgarous-status.git
cd loupsgarous-status
```

Installe les dépendances :

```bash
composer install --no-dev --optimize-autoloader
```

Configure `.env`.

Prépare les dossiers :

```bash
mkdir -p logs
mkdir -p var/cache
```

Permissions :

```bash
sudo chown -R www-data:www-data /var/www/loupsgarous-status
sudo chmod -R 755 /var/www/loupsgarous-status
sudo chmod -R 775 /var/www/loupsgarous-status/logs
sudo chmod -R 775 /var/www/loupsgarous-status/var/cache
```

Lance les migrations :

```bash
composer migrate
```

Lance les seeds au premier setup :

```bash
composer seed
```

### Nginx

Exemple :

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

### HTTPS

Avec Certbot :

```bash
sudo certbot --nginx -d status.example.com
```

Dans `.env`, garde :

```env
SESSION_SECURE=true
```

### Worker avec Supervisor

Le worker doit tourner en continu.

Fichier :

```txt
/etc/supervisor/conf.d/loupsgarous-status-worker.conf
```

Contenu :

```ini
[program:loupsgarous-status-worker]
command=php /var/www/loupsgarous-status/bin/status-worker.php
directory=/var/www/loupsgarous-status
autostart=true
autorestart=true
stderr_logfile=/var/www/loupsgarous-status/logs/status-worker.err.log
stdout_logfile=/var/www/loupsgarous-status/logs/status-worker.out.log
user=www-data
```

Recharge Supervisor :

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start loupsgarous-status-worker
```

Vérification :

```bash
sudo supervisorctl status
```

## Mise à jour en production

```bash
cd /var/www/loupsgarous-status
git pull
composer install --no-dev --optimize-autoloader
composer migrate
composer dump-autoload
sudo systemctl reload php8.3-fpm
sudo systemctl reload nginx
sudo supervisorctl restart loupsgarous-status-worker
```

## Commandes utiles

```bash
composer dump-autoload
composer migrate
composer rollback
composer status-db
composer seed
composer test
composer start
php bin/status-worker.php
```

Avec Docker :

```bash
docker-compose up -d --build
docker-compose down
docker-compose logs -f slim
docker-compose logs -f worker
docker-compose exec slim composer migrate
docker-compose exec slim composer seed
```

## Sécurité

Ne versionne jamais :

```txt
.env
var/cache/status_snapshot.json
logs/*
```

Vérifie que `.gitignore` contient :

```txt
.env
logs/*
var/cache/*
!var/cache/.gitkeep
```

En production :

* utilise HTTPS
* garde `SESSION_SECURE=true`
* utilise des mots de passe forts
* protège les accès admin
* sauvegarde régulièrement la base SQL
* ne publie jamais les webhooks Discord
* ne publie jamais les identifiants SQL

## État du projet

Le projet est en développement actif.

Pistes futures :

```txt
Historique détaillé des incidents
Audit log admin
Rôles admin avancés
Stats de temps de réponse
Tests automatisés
Page publique par endpoint
```

## Licence

Voir `LICENSE.md`.
