# Docker Development Setup

## Prerequisites
- Docker Engine 24+
- Docker Compose v2+

## Services

| Service | Purpose | Exposed |
|---------|---------|---------|
| `db` | PostgreSQL 16 | Internal only |
| `app` | PHP 8.3-FPM + Node (Vite runs here) | Internal (port 9000) |
| `webserver` | Nginx reverse proxy | localhost:8080 |

The `app` container has both PHP and Node so Wayfinder's Vite plugin can call `php artisan wayfinder:generate` during the build.

## First-time setup

```bash
# 1. Copy and configure environment
cp .env.example .env
# Edit .env — set your GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET at minimum.
# DB credentials are pre-configured for Docker Compose.

# 2. Build and start
docker compose up -d --build

# 3. Run migrations and seed
docker compose exec app php artisan migrate --seed

# 4. Generate application key (if not set)
docker compose exec app php artisan key:generate
```

Visit **http://localhost:8080**

## Development with Vite HMR

```bash
# In a separate terminal, start the Vite dev server inside the app container:
docker compose exec app npm run dev
```

Then visit http://localhost:5173 (Vite HMR) or http://localhost:8080 (Nginx).

## Useful commands

```bash
# Run tests
docker compose exec app php artisan test

# Artisan
docker compose exec app php artisan migrate:fresh --seed

# Generate Wayfinder typed routes
docker compose exec app php artisan wayfinder:generate

# View logs
docker compose logs -f app

# Stop everything
docker compose down
```

## Production deployment on an Ubuntu VPS

The development Compose file remains unchanged. Production uses
[`docker-compose.production.yml`](docker-compose.production.yml), which builds
an immutable application image, persists PostgreSQL and application storage in
named volumes, and binds the internal Nginx service to `127.0.0.1:8080`.
Public HTTPS is terminated by Nginx on the VPS.

### Recommended VPS

- 2 vCPU, 4 GB RAM, 50 GB SSD
- Ubuntu 24.04 LTS
- A DNS name such as `quiz.example.com` pointing to the VPS
- Docker Engine and the Docker Compose plugin

This is suitable for a small first release. Increase to 4 vCPU/8 GB RAM when
traffic or PostgreSQL workload requires it. A typical VPS costs about
$12–$24/month; a domain is usually $10–$20/year. Let's Encrypt certificates
are free.

### Fresh-server setup

Run on the VPS:

```bash
sudo apt update && sudo apt -y upgrade
sudo apt install -y ca-certificates curl git nginx certbot python3-certbot-nginx ufw
curl -fsSL https://get.docker.com | sudo sh
sudo usermod -aG docker "$USER"
newgrp docker

sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw --force enable

sudo mkdir -p /opt/quiz
sudo chown "$USER:$USER" /opt/quiz
git clone <repository-url> /opt/quiz
cd /opt/quiz
cp .env.example .env.production
chmod 600 .env.production
```

Edit `.env.production` and set at least:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://quiz.example.com
APP_KEY=base64:<generated-with-php-artisan-key-generate>
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=quizapp
DB_USERNAME=<long-random-database-user>
DB_PASSWORD=<long-random-database-password>
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=file
QUEUE_CONNECTION=sync
LOG_CHANNEL=stderr
LOG_LEVEL=warning
GOOGLE_CLIENT_ID=<google-client-id>
GOOGLE_CLIENT_SECRET=<google-client-secret>
GOOGLE_REDIRECT_URI=https://quiz.example.com/auth/google/callback
```

Generate the application key before the first build using a temporary PHP
container, without installing PHP on the host:

```bash
docker run --rm php:8.3-cli \
  php -r 'echo "base64:" . base64_encode(random_bytes(32)) . PHP_EOL;'
```

Put the displayed `base64:...` value into `APP_KEY`. Remove any temporary
`composer-setup.php` or generated host files if created.

Configure the VPS reverse proxy:

```bash
sudo cp docker/nginx/vps.conf.example /etc/nginx/sites-available/quiz
sudo ln -s /etc/nginx/sites-available/quiz /etc/nginx/sites-enabled/quiz
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx
sudo certbot --nginx -d quiz.example.com
```

Then build and start the application:

```bash
docker compose --env-file .env.production \
  -f docker-compose.production.yml build --pull
docker compose --env-file .env.production \
  -f docker-compose.production.yml up -d
docker compose --env-file .env.production \
  -f docker-compose.production.yml run --rm app php artisan migrate --force
docker compose --env-file .env.production \
  -f docker-compose.production.yml run --rm app php artisan optimize
docker compose --env-file .env.production \
  -f docker-compose.production.yml run --rm app php artisan storage:link
curl -I https://quiz.example.com
```

Do not run `migrate:fresh` in production. PostgreSQL data is stored in the `quiz_pgdata` volume and application writable
state in `quiz_app_storage`; back up both volumes before upgrades:

```bash
docker compose --env-file .env.production -f docker-compose.production.yml \
  exec -T db sh -c 'pg_dump -U "$POSTGRES_USER" "$POSTGRES_DB"' > "backup-$(date +%F).sql"
docker run --rm -v quiz_app_storage:/source -v "$PWD:/backup" alpine \
  tar czf "/backup/app-storage-$(date +%F).tgz" -C /source .
```

Deploy updates with:

```bash
git pull --ff-only
docker compose --env-file .env.production -f docker-compose.production.yml build --pull
docker compose --env-file .env.production -f docker-compose.production.yml up -d
docker compose --env-file .env.production -f docker-compose.production.yml run --rm app php artisan migrate --force
docker compose --env-file .env.production -f docker-compose.production.yml run --rm app php artisan optimize
```

View logs with `docker compose ... logs -f app webserver db`. Never commit
`.env.production`; it is ignored by Git and should remain mode `600`.

### Google OAuth

In Google Cloud Console, create an OAuth consent screen and a **Web
application** client. Add exactly:

- Authorized JavaScript origin: `https://quiz.example.com`
- Authorized redirect URI: `https://quiz.example.com/auth/google/callback`

Set the client ID, secret and callback in `.env.production`, then recreate the
app container and clear cached configuration:

```bash
docker compose --env-file .env.production -f docker-compose.production.yml up -d --force-recreate app
docker compose --env-file .env.production -f docker-compose.production.yml run --rm app php artisan optimize:clear
docker compose --env-file .env.production -f docker-compose.production.yml run --rm app php artisan optimize
```

Use a verified production domain in Google OAuth. The email address is stored
privately by the application and is not exposed in the UI.
