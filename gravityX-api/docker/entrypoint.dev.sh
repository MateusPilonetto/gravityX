#!/bin/sh
set -eu

database_path="${DB_DATABASE:-/var/db/database.sqlite}"
app_state_path="${APP_STATE_PATH:-/var/app-state}"
app_key_file="$app_state_path/app.key"

# The source directory is bind-mounted in Compose, so its vendor directory is
# supplied by a named volume and must be initialized before Artisan runs.
if [ ! -f vendor/autoload.php ] || [ composer.lock -nt vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist
fi

mkdir -p \
    "$(dirname "$database_path")" \
    "$app_state_path" \
    storage/app/private \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    bootstrap/cache
touch "$database_path"

if [ -z "${APP_KEY:-}" ]; then
    if [ -f "$app_key_file" ]; then
        APP_KEY="$(cat "$app_key_file")"
    else
        APP_KEY="$(php artisan key:generate --show)"
        umask 077
        printf '%s\n' "$APP_KEY" > "$app_key_file"
    fi

    export APP_KEY
fi

php artisan migrate --force
php artisan optimize:clear
php artisan storage:link --force

if [ "${RUN_SCHEDULER:-true}" = "true" ]; then
    php artisan schedule:work &
fi

exec "$@"
