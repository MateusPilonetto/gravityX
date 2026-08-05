#!/bin/sh

set -eu

database_path=/var/db/database.sqlite
app_state_path=/var/app-state
app_key_file="$app_state_path/app.key"

mkdir -p \
    /var/db \
    "$app_state_path" \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views
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

php artisan schedule:work &
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
