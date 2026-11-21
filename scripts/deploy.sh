#!/usr/bin/env bash
#
# Deploys the code checked out on this server. The CI deploy jobs (.github/workflows/workflow.yml) fast-forward the
# checkout and then run this script. By hand it is the same two steps:
#
#   git pull --ff-only origin main
#   bash scripts/deploy.sh production        # or: staging
#
# It installs dependencies, checks the .env (`php artisan app:check-environment`; production must pass every
# check, staging only the ones that break the app), builds assets, migrates the central and every tenant database,
# syncs permissions, rebuilds Laravel's config/route/view/event caches and restarts the queue workers.
#
# It never runs cache:clear (or optimize:clear, which includes it): the cache holds rate-limit counters, sign-in
# lockouts and scheduler locks, none of which a deploy should wipe.
#
# The app is NOT put into maintenance mode: it keeps serving requests, and queue workers keep running, while the
# deploy changes the code and migrates the databases underneath them. A failed deploy therefore leaves the app up, on
# whatever the script got through. Fix the cause and re-run this script (every step is safe to repeat). If a migration
# failed, check which schemas are behind with `php artisan migrate:status` and `php artisan tenants:migrate --pretend`,
# and run `php artisan down` by hand if the app should not take writes until they are consistent.
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
