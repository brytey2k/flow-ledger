#!/usr/bin/env bash
#
# Deploys the code checked out on this server. The CI deploy jobs (.github/workflows/workflow.yml) put the app into
# maintenance mode, fast-forward the checkout and then run this script. By hand it is the same three steps:
#
#   php artisan down --retry=60 --refresh=15
#   git pull --ff-only origin main
#   bash scripts/deploy.sh production        # or: staging
#
# It installs dependencies, checks the .env (`php artisan app:check-environment`; production must pass every
# check, staging only the ones that break the app), builds assets, migrates the central and every tenant database,
# syncs permissions, rebuilds Laravel's config/route/view/event caches, restarts the queue workers and brings the app
# back up.
#
# It never runs cache:clear (or optimize:clear, which includes it): the cache holds rate-limit counters, sign-in
# lockouts and scheduler locks, none of which a deploy should wipe.
#
# If any step fails the script stops and the app STAYS in maintenance mode, and queue workers stay paused, so nothing
# writes to a half-migrated database. Fix the cause and re-run this script (every step is safe to repeat). Run
# `php artisan up` by hand only once the central and tenant schemas are consistent (`php artisan migrate:status`,
# `php artisan tenants:migrate --pretend`).
set -euo pipefail

cd "$(dirname "$0")/.."

target="${1:-}"
# Never dev dependencies on a server, staging included: debugging tools in require-dev can expose requests, queries
# and configuration whenever APP_DEBUG is on, which it often is on staging.
composer_flags=(--no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress)
check_flags=()
case "$target" in
    production)
        check_flags+=(--production)
        ;;
    staging) ;;
    *)
        echo "Usage: scripts/deploy.sh production|staging" >&2
        exit 64
        ;;
esac

# Already down when called from CI; this makes a manual run safe too ("already down" is not an error).
php artisan down --retry=60 --refresh=15

# Drop the previous release's caches before anything boots the new code against them.
php artisan config:clear
php artisan route:clear
php artisan event:clear
php artisan view:clear

composer install "${composer_flags[@]}"

# Refuse to migrate or go live on an unsafe .env (e.g. a development one copied to the server).
php artisan app:check-environment ${check_flags[@]+"${check_flags[@]}"}

npm ci
npm run build

php artisan migrate --force
php artisan tenants:migrate --force
php artisan permissions:sync

php artisan optimize
php artisan queue:restart

php artisan up
