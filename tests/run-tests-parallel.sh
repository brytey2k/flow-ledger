#!/usr/bin/env bash
set -e

cleanup() {
    php artisan app:remove-automated-tests-tenant
}

trap cleanup EXIT

php artisan config:clear --ansi
php artisan migrate --database=testing_pgsql --force
php artisan app:create-automated-tests-tenant
php artisan test --parallel "$@"
