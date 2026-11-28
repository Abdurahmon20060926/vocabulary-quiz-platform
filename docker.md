# Run Locally with Docker Compose

These steps are for local development on Ubuntu. The app is available at
**http://localhost:8080**. Docker Compose starts PostgreSQL, the Laravel app,
and Nginx. The development app image includes Composer development packages, so
you can run Pest tests inside the app container.

## 1. Check Docker access on Ubuntu

First check that Docker works without `sudo`:

```bash
docker ps
```

If you see `permission denied` for `/var/run/docker.sock`, Docker is installed
but your current login does not have permission to use its socket. Check the
socket group and the `docker` group:

```bash
ls -l /var/run/docker.sock
getent group docker
```

If the `docker` group does not exist, create it; then add your account:

```bash
if ! getent group docker >/dev/null; then sudo groupadd docker; fi
sudo usermod -aG docker "$USER"
```

Then **log out of Ubuntu and log back in** (or restart the computer and reopen
VS Code). Existing terminals do not automatically receive new group
membership. Check again:

```bash
id -nG
docker ps
```

If you want to apply the new group to just the current terminal, you can run
`newgrp docker`, then check `docker ps` again. Do not fix this by making the
Docker socket world-writable with `chmod 666`; access to Docker is effectively
root access.

## 2. Configure the local environment

From the repository root, copy the example file and add your Google OAuth
credentials:

```bash
cp .env.example .env
```

Edit `.env` and set `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET`. The example
already has the local Compose database settings, file-based sessions, and
correct local app URL. Do not change the local callback URL unless you also
change the app URL and Nginx port consistently.

The app container reads its database settings from this `.env` file. Compose
does not override them with fixed database environment values, so PHPUnit can
select its SQLite in-memory database when you run tests inside the container.

In Google Cloud Console, add this exact **Authorized redirect URI**:

```text
http://localhost:8080/auth/google/callback
```

For the **Authorized JavaScript origin**, add:

```text
http://localhost:8080
```

## 3. Build and start the services

```bash
docker compose up -d --build
```

Wait until the database is healthy, then generate the Laravel key and create
the database tables and demo quizzes. This one-time first command is the
documented way to start a fresh local database:

```bash
docker compose ps
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

Open **http://localhost:8080**. Explore should show the seeded, published,
listed quizzes, including both strict and casual quizzes.

`php artisan db:seed` is safe to run again. It uses fixed demo quiz IDs and
does not create duplicates or modify demo quizzes that already exist.

The app container has isolated Docker volumes for Laravel's writable storage
and bootstrap cache. This avoids depending on host directory ownership and
does not require `chmod 777` or manual `sudo` permission changes.

## 4. Useful commands

```bash
# See service status
docker compose ps

# Check which migrations have run
docker compose exec app php artisan migrate:status

# Run the feature tests (the dev image includes test dependencies)
docker compose exec app php artisan test --compact

# View service logs
docker compose logs --tail=100 app webserver db

# Stop containers but keep database and other named volumes
docker compose down
```

To completely reset local Docker data and start with an empty database:

```bash
docker compose down -v
docker compose up -d --build
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

After the first setup, running `php artisan db:seed` is safe again because the
demo seeder is idempotent.

**Warning:** `docker compose down -v` deletes the local PostgreSQL database and
all other named volumes for this Compose project. Use it only when you are
comfortable losing local quiz data.

## Production deployment

Production uses a separate environment template and Compose file. Follow
[`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md); do not use the development
`.env` file or development Compose stack as a production setup.
