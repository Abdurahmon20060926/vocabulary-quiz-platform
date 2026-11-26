# Docker

The root `docker-compose.yml` remains the local development setup. For an
Ubuntu VPS deployment, use [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md) and
`docker-compose.prod.yml`; production uses a separate environment file,
production PHP settings, PostgreSQL persistence, host Nginx, and Let's Encrypt.
