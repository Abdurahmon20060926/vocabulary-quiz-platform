#!/bin/sh
set -eu

mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

if [ -d /shared-public-build ] && [ ! -f /shared-public-build/manifest.json ]; then
    cp -a /opt/quiz-public-build/. /shared-public-build/
fi

if [ -d /shared-public ]; then
    find /shared-public -mindepth 1 -maxdepth 1 -exec rm -rf -- {} +
    cp -a public/. /shared-public/
fi

exec "$@"
