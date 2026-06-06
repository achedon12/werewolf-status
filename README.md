# LoupsGarous Status

Status page pour l’écosystème LoupsGarous.

Ce projet permet de surveiller l’état de plusieurs services, de garder un historique des downtimes et d’afficher une page claire pour suivre la disponibilité des endpoints.

Il fait partie d’un ensemble plus large autour de LoupsGarous, qui regroupe le site principal, des API, des outils de monitoring et une administration centralisée.

## Objectif du projet

LoupsGarous Status sert à répondre à une question simple :

> Est-ce que les services LoupsGarous sont disponibles en ce moment ?

L’application vérifie les endpoints configurés, détecte les pannes, sauvegarde les périodes de downtime en base SQL et affiche un historique visuel sur une période choisie.

## Fonctionnement général

Le projet fonctionne avec deux parties :

```txt
Application web
  → affiche la page publique
  → affiche le panneau admin
  → lit le dernier état depuis le cache

Worker
  → vérifie les endpoints toutes les 30 secondes
  → sauvegarde les downtimes en SQL
  → met à jour le cache JSON
  → envoie les notifications Discord
```

Les visiteurs ne lancent pas directement les checks HTTP vers les endpoints.

Ils lisent le dernier état généré par le worker.

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
* Lecture depuis un cache généré par le worker

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
* Gestion des notifications Discord par endpoint
* Webhook Discord configurable par endpoint

### Monitoring

* Sauvegarde des downtimes en SQL
* Détection du début d’un downtime
* Détection du retour en ligne
* Calcul de l’uptime sur une période configurable
* Support des downtimes complets et partiels
* Notifications Discord au début et à la fin d’un downtime
* Cache JSON pour éviter de surcharger les endpoints surveillés

## Architecture de monitoring

Avant :

```txt
Utilisateur
  → /api/status
  → check tous les endpoints
  → écrit les downtimes
  → renvoie les résultats
```

Maintenant :

```txt
Worker serveur toutes les 30 secondes
  → check tous les endpoints
  → écrit les downtimes SQL
  → écrit le cache JSON

Utilisateur
  → /api/status
  → lit le cache JSON
  → affiche les résultats
```

Résultat :

```txt
1 visiteur ou 500 visiteurs
  → aucun check supplémentaire vers les endpoints surveillés
```

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
    Notification/
    Persistence/
      Admin/
      Database/
      Status/

database/
  schema.sql

logs/

var/
  cache/
```

## Prérequis

### Développement local

* PHP 8.3 ou supérieur
* Composer
* MySQL ou MariaDB
* Extension PHP PDO MySQL
* Extension PHP cURL
* Extension PHP mbstring
* Extension PHP json

### Développement avec Docker

* Docker
* Docker Compose

### Production

* PHP 8.3 ou supérieur
* Composer
* MySQL ou MariaDB
* Nginx ou Apache
* PHP-FPM
* Supervisor
* Extension PHP PDO MySQL
* Extension PHP cURL
* Extension PHP mbstring
* Extension PHP json
* Git

## Variables d’environnement

Crée un fichier `.env` à la racine du projet.

Exemple :

```env
APP_ENV=dev

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=status_page
DB_USERNAME=root
DB_PASSWORD=

SESSION_SECURE=false

STATUS_CHECK_INTERVAL=30

INFO_URL=https://loupsgarous.net/api/infos

DISCORD_WEBHOOK_URL=
```

Avec Docker, utilise plutôt :

```env
APP_ENV=dev

DB_HOST=db
DB_PORT=3306
DB_DATABASE=status_page
DB_USERNAME=status_user
DB_PASSWORD=status_password

SESSION_SECURE=false

STATUS_CHECK_INTERVAL=30

INFO_URL=https://loupsgarous.net/api/infos

DISCORD_WEBHOOK_URL=
```

En production avec HTTPS :

```env
APP_ENV=prod

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=status_page
DB_USERNAME=status_user
DB_PASSWORD=mot_de_passe_fort

SESSION_SECURE=true

STATUS_CHECK_INTERVAL=30

INFO_URL=https://loupsgarous.net/api/infos

DISCORD_WEBHOOK_URL=
```

## Installation en développement local

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

Configure `.env` avec tes accès SQL.

Crée la base de données :

```sql
CREATE DATABASE status_page CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Importe le schéma :

```bash
mysql -u root -p status_page < database/schema.sql
```

Lance l’application web :

```bash
composer start
```

Dans un deuxième terminal, lance le worker :

```bash
php bin/status-worker.php
```

Ouvre le projet :

```txt
http://localhost:8080
```

Ouvre le panneau admin :

```txt
http://localhost:8080/admin/login
```

## Développement avec Docker

Le projet contient deux services importants :

```txt
slim
  → application web

worker
  → check des endpoints toutes les 30 secondes

db
  → base MariaDB
```

Lance les conteneurs :

```bash
docker-compose up -d --build
```

Installe les dépendances si nécessaire :

```bash
docker-compose exec slim composer install
```

Importe le schéma SQL :

```bash
docker-compose exec db mysql -u status_user -pstatus_password status_page < database/schema.sql
```

Regarde les logs de l’application :

```bash
docker-compose logs -f slim
```

Regarde les logs du worker :

```bash
docker-compose logs -f worker
```

Ouvre le projet :

```txt
http://localhost:8080
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

  db:
    image: mariadb:11
    container_name: loupsgarous-status-db
    restart: unless-stopped
    environment:
      MARIADB_DATABASE: status_page
      MARIADB_USER: status_user
      MARIADB_PASSWORD: status_password
      MARIADB_ROOT_PASSWORD: root_password
    ports:
      - "3306:3306"
    volumes:
      - db_data:/var/lib/mysql

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
discord_webhook_url
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
discord_down_notified_at
discord_up_notified_at
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
Webhook Discord
```

Exemple :

```txt
Nom : Loups Garous
URL de check : https://loupsgarous.net/api/health
URL publique : https://loupsgarous.net
Uptime : seconds
Notifications Discord : activées
Webhook Discord : https://discord.com/api/webhooks/...
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

## Cache status

Le dernier état des services est stocké ici :

```txt
var/cache/status_snapshot.json
```

Ce fichier est généré par le worker.

La page publique et `/api/status` lisent ce fichier.

Si ce fichier n’existe pas encore, il faut lancer :

```bash
php bin/status-worker.php
```

ou, avec Docker :

```bash
docker-compose logs -f worker
```

## Notifications Discord

Chaque endpoint peut avoir son propre webhook Discord.

Quand un endpoint tombe :

```txt
downtime créé
notification Discord envoyée
discord_down_notified_at rempli
```

Tant que le downtime reste ouvert :

```txt
aucune nouvelle notification
```

Quand l’endpoint revient en ligne :

```txt
up_at rempli
notification Discord de retour envoyée
discord_up_notified_at rempli
```

Quand il retombe plus tard :

```txt
nouveau downtime
nouvelle notification possible
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

## Déploiement en production

Cette section décrit une méthode simple pour déployer le projet sur un serveur Linux avec Git, Composer, PHP-FPM, Nginx, Supervisor et MySQL ou MariaDB.

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

STATUS_CHECK_INTERVAL=30

INFO_URL=https://loupsgarous.net/api/infos

DISCORD_WEBHOOK_URL=
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

Le dossier `var/cache/` doit aussi être accessible en écriture par le worker et par PHP.

```bash
sudo mkdir -p logs
sudo mkdir -p var/cache

sudo chown -R www-data:www-data /var/www/loupsgarous-status
sudo chmod -R 755 /var/www/loupsgarous-status
sudo chmod -R 775 /var/www/loupsgarous-status/logs
sudo chmod -R 775 /var/www/loupsgarous-status/var/cache
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

### Lancer le worker avec Supervisor

Le worker doit tourner en permanence en production.

Installe Supervisor :

```bash
sudo apt install supervisor
```

Crée le fichier :

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

Vérifie le worker :

```bash
sudo supervisorctl status
```

Logs du worker :

```bash
tail -f logs/status-worker.out.log
tail -f logs/status-worker.err.log
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

Recharge PHP-FPM :

```bash
sudo systemctl reload php8.3-fpm
```

Recharge Nginx :

```bash
sudo systemctl reload nginx
```

Redémarre le worker :

```bash
sudo supervisorctl restart loupsgarous-status-worker
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

Vérifie que le cache existe :

```bash
ls -la var/cache
cat var/cache/status_snapshot.json
```

Vérifie les logs :

```bash
tail -f logs/app.log
tail -f logs/status-worker.out.log
tail -f logs/status-worker.err.log
tail -f /var/log/nginx/loupsgarous-status.error.log
```

## Déploiement en production avec Docker

Clone le dépôt :

```bash
git clone <url-du-repo> loupsgarous-status
cd loupsgarous-status
cp .env.example .env
```

Configure `.env` avec :

```env
DB_HOST=db
DB_PORT=3306
DB_DATABASE=status_page
DB_USERNAME=status_user
DB_PASSWORD=status_password

SESSION_SECURE=true

STATUS_CHECK_INTERVAL=30
```

Lance les conteneurs :

```bash
docker-compose up -d --build
```

Importe le schéma SQL :

```bash
docker-compose exec db mysql -u status_user -pstatus_password status_page < database/schema.sql
```

Vérifie les logs :

```bash
docker-compose logs -f slim
docker-compose logs -f worker
```

Mettre à jour en Docker :

```bash
git pull
docker-compose up -d --build
docker-compose exec slim composer install --no-dev --optimize-autoloader
docker-compose restart worker
```

## Points importants en production

Ne versionne jamais le fichier `.env`.

Garde `APP_ENV=prod`.

Utilise `SESSION_SECURE=true` si le site est en HTTPS.

Utilise un mot de passe SQL fort.

Garde le dossier `public/` comme racine web.

Ne pointe jamais Nginx ou Apache vers la racine complète du projet.

Le worker doit toujours tourner.

Le cache `var/cache/status_snapshot.json` doit être généré par le worker.

Sauvegarde régulièrement la base SQL.

La base contient :

```txt
endpoints
admins
settings
historique des downtimes
```

## Commandes utiles

Régénérer l’autoload :

```bash
composer dump-autoload
```

Vérifier un fichier PHP :

```bash
php -l chemin/du/fichier.php
```

Lancer l’application web en local :

```bash
composer start
```

Lancer le worker en local :

```bash
php bin/status-worker.php
```

Lancer avec Docker :

```bash
docker-compose up -d --build
```

Arrêter Docker :

```bash
docker-compose down
```

Voir les logs Docker :

```bash
docker-compose logs -f slim
docker-compose logs -f worker
docker-compose logs -f db
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
  Notifications externes

Views
  Pages PHP
  Partials d’affichage

Public
  Point d’entrée
  Assets accessibles par le navigateur

Worker
  Génération périodique du cache
  Checks HTTP
  Notifications Discord
```

## Règles de développement

Les routes restent simples.

La logique métier va dans les services.

Les requêtes SQL vont dans les repositories.

Les vues gèrent l’affichage.

Les endpoints ne sont pas codés en dur.

Les downtimes sont stockés en SQL.

Les résultats affichés sont lus depuis le cache.

Les checks HTTP sont faits par le worker.

Les actions admin passent par des sessions protégées.

## État du projet

Le projet est en développement actif.

Objectifs à venir :

```txt
Améliorer les notifications Discord
Ajouter plus de statistiques
Ajouter une vue historique détaillée
Améliorer les rôles admin
Ajouter des tests
Améliorer l’interface mobile
Ajouter un système d’audit admin
```

## Licence

Projet privé lié à l’écosystème LoupsGarous.
