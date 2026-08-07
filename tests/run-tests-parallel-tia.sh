#!/usr/bin/env bash
set -e

# Unique per invocation so concurrent test runs (e.g. multiple sail composer
# test processes) each track and tear down only the tenant they created.
export AUTOMATED_TEST_TENANT_MARKER="$$-$(date +%s%N 2>/dev/null || date +%s)-$RANDOM"

# Laravel's built-in ParallelTesting support auto-clones the *default*
# connection's database per worker (e.g. testing -> testing_test_1) for any
# test using DatabaseTransactions/RefreshDatabase/etc, completely independent
# of this app's own tenant/central database setup. This app's central tenant
# data always lives in the hardcoded "testing_pgsql" connection, which Laravel
# never touches (it's not the default connection) — so under --parallel, the
# app's default "pgsql" connection (used by e.g. App\Models\Tenant at
# request-handling time) silently diverges onto its own empty
# "testing_test_N" database while central tenant rows only exist in
# "testing_pgsql"'s database. Disable Laravel's auto-cloning so "pgsql" stays
# pointed at the same physical database as "testing_pgsql" for every worker,
# matching sequential-run behavior. Unlike run-tests-parallel.sh, this can be
# set via the env var here because we invoke vendor/bin/pest directly below
# instead of going through Collision's TestCommand (which overrides this env
# var from its --without-databases CLI flag).
export LARAVEL_PARALLEL_TESTING_WITHOUT_DATABASES=1

cleanup() {
    local exit_code=$?
    php artisan app:remove-automated-tests-tenant --sweep || true
    exit $exit_code
}

trap cleanup EXIT

php artisan config:clear --ansi
php artisan migrate --database=testing_pgsql --force

# Each ParaTest worker process gets its own tenant database, keyed by
# ParaTest's per-worker TEST_TOKEN env var (see
# App\Helpers\ResolvesAutomatedTestTenantMarker). Provision them here, before
# any worker starts, rather than lazily inside a worker's test setUp():
# Postgres refuses to run CREATE DATABASE inside a transaction block, and by
# the time a test's setUp() runs, DatabaseTransactions already has one open.
PROCESSES=""
for arg in "$@"; do
    case "$arg" in
        --processes=*) PROCESSES="${arg#*=}" ;;
    esac
done
if [ -z "$PROCESSES" ]; then
    PROCESSES=$(php -r "require 'vendor/autoload.php'; echo \ParaTest\Options::getNumberOfCPUCores();")
fi

for token in $(seq 1 "$PROCESSES"); do
    TEST_TOKEN="$token" php artisan app:create-automated-tests-tenant
done

vendor/bin/pest --parallel --tia "$@"
