<?php

declare(strict_types=1);

namespace App\Helpers;

trait ResolvesAutomatedTestTenantMarker
{
    /**
     * Identifies the calling test-runner shell invocation, so that
     * concurrent `sail composer test` runs each track their own automated
     * test tenant instead of one run's teardown deleting another's tenant.
     *
     * Falls back to this process's own PID when the test runner script
     * hasn't exported AUTOMATED_TEST_TENANT_MARKER (e.g. invoked directly).
     *
     * ParaTest (used by `php artisan test --parallel`) forks multiple
     * long-lived worker *processes* that all inherit
     * AUTOMATED_TEST_TENANT_MARKER from the parent shell, so it alone can't
     * distinguish workers. ParaTest also exports a stable per-worker
     * TEST_TOKEN env var, which is folded in here so each worker
     * resolves/creates its own tenant instead of every worker racing on the
     * same tenant database.
     */
    private function marker(): string
    {
        $marker = getenv('AUTOMATED_TEST_TENANT_MARKER');
        $marker = is_string($marker) && $marker !== '' ? $marker : (string) getmypid();

        $token = getenv('TEST_TOKEN');
        if (is_string($token) && $token !== '') {
            $marker .= '-w' . $token;
        }

        return $marker;
    }

    private function markerFilePath(string $marker): string
    {
        return storage_path('framework/testing/automated-tenant-' . $marker . '.id');
    }
}
