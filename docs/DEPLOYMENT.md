# Production Deployment

Production uses Docker Compose, Caddy for automatic HTTPS, PostgreSQL, and an
internal Nginx/PHP-FPM application stack.

Follow [GO_LIVE.md](GO_LIVE.md) for the beginner deployment commands. The
production Compose file is `docker-compose.prod.yml`; configure the server's
untracked `.env.production` from `.env.production.example`.

The application and database use named volumes for persistent data. Backups
are optional and are not part of the project requirements. Demo seeding is
idempotent and runs automatically after migrations. Never run
`docker compose -f docker-compose.prod.yml down -v` on production.
