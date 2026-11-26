# Production Deployment: Ubuntu VPS

This guide deploys the application without changing local development. The
development environment uses `docker-compose.yml`; production uses
`docker-compose.prod.yml` and the untracked `.env.production` file.

## Architecture

- Ubuntu 24.04 LTS VPS
- Docker Compose with Laravel 13/PHP-FPM and PostgreSQL 16
- Nginx on the host handles HTTP-to-HTTPS redirects and TLS
- Let's Encrypt certificates managed by Certbot
- Production frontend assets are built into the application image
- PostgreSQL and Laravel writable storage use Docker named volumes

Recommended starter VPS: 2 vCPU, 4 GB RAM, 50 GB SSD. Allow roughly
$12–$24/month for the server, plus domain registration. Do not expose
PostgreSQL or PHP-FPM publicly.

## Before starting

Prepare:

1. A VPS with a public IPv4 address and Ubuntu 24.04.
2. A domain with an `A` record pointing at the VPS.
3. The application Git repository and access to clone it.
4. A Google Cloud OAuth Web application client.

In Google Cloud Console, configure the OAuth consent screen and add:

- Authorized JavaScript origin: `https://your-domain`
- Authorized redirect URI: `https://your-domain/auth/google/callback`

The email address remains private to the application. Do not add OAuth
credentials to Git.

## 1. Install host packages and Docker

Connect as a sudo-capable user. Set up SSH access before enabling the firewall.

```bash
sudo apt update
sudo apt -y upgrade
sudo apt install -y ca-certificates curl git nginx certbot ufw gettext-base

curl -fsSL https://get.docker.com | sudo sh
sudo usermod -aG docker "$USER"
newgrp docker

sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw --force enable
```

Configure the DNS `A` record before requesting a certificate.

## 2. Clone and configure production environment

```bash
sudo mkdir -p /opt/quiz
sudo chown "$USER:$USER" /opt/quiz
git clone https://github.com/Abdurahmon20060926/vocabulary-quiz-platform.git /opt/quiz
cd /opt/quiz

cp .env.production.example .env.production
chmod 600 .env.production
```

Edit `.env.production`. Replace every placeholder:

```dotenv
APP_NAME=QuizVault
APP_ENV=production
APP_DEBUG=false
APP_KEY=
APP_URL=https://your-domain

DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=quizapp
DB_USERNAME=quizapp
DB_PASSWORD=replace-with-a-long-random-password

SESSION_DRIVER=file
SESSION_SECURE_COOKIE=true
CACHE_STORE=file
QUEUE_CONNECTION=sync
LOG_CHANNEL=stderr

GOOGLE_CLIENT_ID=your-google-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=replace-with-google-client-secret
GOOGLE_REDIRECT_URI=https://your-domain/auth/google/callback

DOMAIN=your-domain
```

`DOMAIN` is the bare hostname (for example `quiz.example.com`), not a URL.
`APP_URL` and `GOOGLE_REDIRECT_URI` use `https://`. Keep the `.env.production`
file out of version control and mode `600`.

Generate a unique Laravel key:

```bash
docker run --rm php:8.3-cli \
  php -r 'echo "base64:" . base64_encode(random_bytes(32)) . PHP_EOL;'
```

Put its output in `APP_KEY`. Generate a database password, for example, with
`openssl rand -hex 32`, then set it in `DB_PASSWORD`. The PostgreSQL service
initializes using the configured database, user, and password.

## 3. Start production containers

Compose reads container environment values from `.env.production` and uses the
same file for Compose interpolation via `--env-file`:

```bash
docker compose --env-file .env.production -f docker-compose.prod.yml config
docker compose --env-file .env.production -f docker-compose.prod.yml build --pull
docker compose --env-file .env.production -f docker-compose.prod.yml up -d
docker compose --env-file .env.production -f docker-compose.prod.yml ps
docker compose --env-file .env.production -f docker-compose.prod.yml run --rm app php artisan migrate --force
docker compose --env-file .env.production -f docker-compose.prod.yml run --rm app php artisan optimize
```

Do not run `migrate:fresh` on a production database. PostgreSQL data persists
in the `quiz_pgdata` named volume. The app binds only to localhost port 8080;
the database and PHP-FPM are not published to the host.

## 4. Issue the Let's Encrypt certificate

Install a temporary HTTP-only Nginx site so Certbot's HTTP-01 challenge has a
webroot. It serves challenge files from `/var/www/certbot` and proxies other
requests to the application.

```bash
sudo mkdir -p /var/www/certbot
DOMAIN="$(sed -n 's/^DOMAIN=//p' .env.production)"
envsubst '${DOMAIN}' < docker/nginx/vps.conf.example \
  | sudo tee /etc/nginx/sites-available/quiz >/dev/null
sudo ln -sfn /etc/nginx/sites-available/quiz /etc/nginx/sites-enabled/quiz
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx

sudo certbot certonly --webroot -w /var/www/certbot -d "$DOMAIN"
```

Certbot will prompt for an email address and agreement to its terms.

## 5. Enable HTTPS and automatic HTTP redirect

Install the provided full TLS config after Certbot has obtained the certificate:

```bash
DOMAIN="$(sed -n 's/^DOMAIN=//p' .env.production)"
envsubst '${DOMAIN}' < docker/nginx/vps-https.conf.example \
  | sudo tee /etc/nginx/sites-available/quiz >/dev/null
sudo nginx -t
sudo systemctl reload nginx
sudo systemctl enable --now certbot.timer
```

The HTTPS Nginx config in `docker/nginx/vps-https.conf.example` redirects all
HTTP requests to HTTPS, except ACME challenge requests needed for certificate
renewal. Certbot renewals run from the system timer. Add a reload deploy hook:

```bash
printf '#!/bin/sh\nsystemctl reload nginx\n' \
  | sudo tee /etc/letsencrypt/renewal-hooks/deploy/reload-nginx >/dev/null
sudo chmod 755 /etc/letsencrypt/renewal-hooks/deploy/reload-nginx
sudo certbot renew --dry-run
```

Verify the site:

```bash
curl -I "https://$DOMAIN"
curl -I "http://$DOMAIN"
```

The HTTPS request should succeed, and HTTP should return a redirect to HTTPS.

## 6. Updating the application

Back up the database before deploying schema changes. Then:

```bash
cd /opt/quiz
git pull --ff-only
docker compose --env-file .env.production -f docker-compose.prod.yml build --pull
docker compose --env-file .env.production -f docker-compose.prod.yml up -d
docker compose --env-file .env.production -f docker-compose.prod.yml run --rm app php artisan migrate --force
docker compose --env-file .env.production -f docker-compose.prod.yml run --rm app php artisan optimize
```

## Operations

```bash
docker compose --env-file .env.production -f docker-compose.prod.yml logs -f app webserver db
docker compose --env-file .env.production -f docker-compose.prod.yml exec -T db \
  sh -c 'pg_dump -U "$POSTGRES_USER" "$POSTGRES_DB"' > "backup-$(date +%F).sql"
```

Back up the SQL dump off the VPS regularly. Preserve the `quiz_pgdata` volume
when rebuilding containers; never use `docker compose down -v` on production.

## Production environment variables

`.env.production.example` is the safe, tracked template. Copy it to
`.env.production` on the VPS and supply real values there. `.env.production` is
explicitly ignored by Git. Required settings:

- Application: `APP_NAME`, `APP_ENV`, `APP_DEBUG`, `APP_KEY`, `APP_URL`
- PostgreSQL: `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`,
  `DB_USERNAME`, `DB_PASSWORD`
- Runtime: `SESSION_DRIVER`, `SESSION_SECURE_COOKIE`, `CACHE_STORE`,
  `QUEUE_CONNECTION`, `LOG_CHANNEL`
- Google OAuth: `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`,
  `GOOGLE_REDIRECT_URI`
- Host routing and TLS: `DOMAIN`
