#!/usr/bin/env bash
#
# Post-deploy steps, run ON the server by .github/workflows/deploy.yml after the
# built application (vendor/ and public/build included) has been rsynced into place.
# Code and dependencies come from CI; this script only touches runtime state.
#
# The server has no Supervisor: one cron entry runs the scheduler every minute,
# and the scheduler also drains the queue (routes/console.php):
#
#     * * * * * cd ~/web/kadir.gulec.tr/public_html && php8.4 artisan schedule:run >> /dev/null 2>&1
#
set -euo pipefail
cd "$(dirname "$0")/.."

PHP_BIN="${PHP_BIN:-php}"

# storage/ and bootstrap/cache/ are not uploaded; make sure their skeleton exists.
mkdir -p storage/app/public storage/app/private storage/framework/{cache/data,sessions,views} storage/logs bootstrap/cache

# The CI-built vendor/ has no dev packages, but a stale package manifest may still
# list their service providers and break every artisan command.
rm -f bootstrap/cache/packages.php bootstrap/cache/services.php

echo "▶ PHP: $("$PHP_BIN" -r 'echo PHP_VERSION;')"

# Bring the site back up even if a step fails; the exit code stays the failing one.
trap 'echo "▶ Maintenance mode OFF"; "$PHP_BIN" artisan up' EXIT

echo "▶ Maintenance mode ON"
"$PHP_BIN" artisan down --refresh=15 || true

echo "▶ Migrate"
"$PHP_BIN" artisan migrate --force

echo "▶ Sync permissions"
"$PHP_BIN" artisan permissions:sync

echo "▶ Rebuild caches"
"$PHP_BIN" artisan optimize:clear
"$PHP_BIN" artisan optimize

# Only creates the link once; later deploys leave it alone.
if [ ! -L public/storage ]; then
    echo "▶ Storage link"
    "$PHP_BIN" artisan storage:link
fi

# A queue worker running right now finishes its job with the old code, then stops.
"$PHP_BIN" artisan queue:restart
