#!/usr/bin/env bash
set -e

# Unique per invocation so concurrent test runs (e.g. multiple sail composer
# test processes) each track and tear down only the tenant they created.
export AUTOMATED_TEST_TENANT_MARKER="$$-$(date +%s%N 2>/dev/null || date +%s)-$RANDOM"

cleanup() {
    php artisan app:remove-automated-tests-tenant
}

trap cleanup EXIT

php artisan config:clear --ansi
php artisan migrate --database=testing_pgsql --force
php artisan app:create-automated-tests-tenant
php artisan test "$@"
