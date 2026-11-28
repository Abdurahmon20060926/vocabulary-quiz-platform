#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

COMPOSE_FILE="docker-compose.prod.yml"
ENV_FILE=".env.production"
PULL=false
SEED=false

for argument in "$@"; do
    case "$argument" in
        --pull) PULL=true ;;
        --seed) SEED=true ;; # Backward compatibility: seeding is automatic and idempotent.
        *)
            printf 'Unknown option: %s\nUsage: %s [--pull] [--seed]\n' "$argument" "$0" >&2
            exit 2
            ;;
    esac
done

if [[ ! -f "$ENV_FILE" ]]; then
    printf 'Missing %s. Copy .env.production.example to .env.production and configure it first.\n' "$ENV_FILE" >&2
    exit 1
fi

read_env_value() {
    local name="$1"
    awk -v name="$name" 'index($0, name "=") == 1 { sub("^[^=]*=", ""); print; exit }' "$ENV_FILE"
}

write_env_value() {
    local name="$1"
    local value="$2"
    local temporary_file

    temporary_file="$(mktemp "${ENV_FILE}.XXXXXX")"
    while IFS= read -r line || [[ -n "$line" ]]; do
        if [[ "${line%%=*}" == "$name" ]]; then
            printf '%s=%s\n' "$name" "$value"
        else
            printf '%s\n' "$line"
        fi
    done < "$ENV_FILE" > "$temporary_file"

    if ! grep -q "^${name}=" "$ENV_FILE"; then
        printf '%s=%s\n' "$name" "$value" >> "$temporary_file"
    fi

    chmod --reference="$ENV_FILE" "$temporary_file"
    mv -- "$temporary_file" "$ENV_FILE"
}

app_key="$(read_env_value APP_KEY)"
if [[ -z "${app_key//[[:space:]]/}" ]]; then
    write_env_value APP_KEY "base64:$(openssl rand -base64 32 | tr -d '\n')"
fi

db_password="$(read_env_value DB_PASSWORD)"
if [[ -z "${db_password//[[:space:]]/}" || "$db_password" == "CHANGE_ME" ]]; then
    write_env_value DB_PASSWORD "$(openssl rand -hex 24)"
fi

app_domain="$(read_env_value APP_DOMAIN)"
if [[ -z "${app_domain//[[:space:]]/}" || "$app_domain" == "YOUR_DOMAIN_HERE" ]]; then
    printf 'Set APP_DOMAIN to your real domain in %s before deploying.\n' "$ENV_FILE" >&2
    exit 1
fi

compose=(docker compose -f "$COMPOSE_FILE" --env-file "$ENV_FILE")

if [[ "$PULL" == true ]]; then
    git pull --ff-only
fi

"${compose[@]}" up -d --build

db_container="$("${compose[@]}" ps -q db)"
if [[ -z "$db_container" ]]; then
    printf 'Could not find the database container after Compose startup.\n' >&2
    exit 1
fi

printf 'Waiting for the database health check'
for attempt in {1..60}; do
    health_status="$(docker inspect --format '{{if .State.Health}}{{.State.Health.Status}}{{else}}missing{{end}}' "$db_container")"
    if [[ "$health_status" == "healthy" ]]; then
        printf ' ready\n'
        break
    fi
    if [[ "$health_status" == "unhealthy" || "$health_status" == "missing" ]]; then
        printf '\nDatabase health check is %s.\n' "$health_status" >&2
        exit 1
    fi
    if [[ "$attempt" == 60 ]]; then
        printf '\nTimed out waiting for the database to become healthy.\n' >&2
        exit 1
    fi
    printf '.'
    sleep 2
done

"${compose[@]}" exec -T app php artisan migrate --force

# Demo quiz seeding is intentionally idempotent, so it is safe on every deploy.
"${compose[@]}" exec -T app php artisan db:seed --force

if ! "${compose[@]}" exec -T app sh -c \
    'php artisan config:cache && php artisan route:cache && php artisan view:cache'; then
    printf 'Route or view caching failed; falling back to config cache only.\n' >&2
    "${compose[@]}" exec -T app php artisan optimize:clear
    "${compose[@]}" exec -T app php artisan config:cache
fi

printf 'Deployed: https://%s\n' "$app_domain"
"${compose[@]}" ps
