#!/bin/sh
set -eu

lock_marker="node_modules/.gravityx-package-lock"
lock_hash="$(sha256sum package-lock.json | awk '{print $1}')"

# node_modules is a named volume in Compose. Rebuild it only when the lockfile
# changes so restarts stay fast while dependency changes are picked up.
if [ ! -x node_modules/.bin/vite ] \
    || [ ! -f "$lock_marker" ] \
    || [ "$(cat "$lock_marker")" != "$lock_hash" ]; then
    npm ci
    printf '%s\n' "$lock_hash" > "$lock_marker"
fi

exec "$@"
