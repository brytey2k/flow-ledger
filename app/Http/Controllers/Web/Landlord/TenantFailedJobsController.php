<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Landlord;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\AuditLogRecorder;
use App\Services\TenantFailedJobService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;

class TenantFailedJobsController extends Controller
{
    public function index(Tenant $tenant, TenantFailedJobService $failedJobs): View
    {
        return view('landlord.tenants.failed-jobs', ['tenant' => $tenant, 'jobs' => $failedJobs->list($tenant)]);
    }

    public function retry(Tenant $tenant, string $scope, string $uuid, TenantFailedJobService $failedJobs, AuditLogRecorder $audit): RedirectResponse
    {
        return $this->act($tenant, $audit, 'retry-failed-job', $scope, $uuid, fn() => $failedJobs->retry($tenant, $scope, $uuid), __('flash.tenants.failed_job_retried'));
    }

    public function discard(Tenant $tenant, string $scope, string $uuid, TenantFailedJobService $failedJobs, AuditLogRecorder $audit): RedirectResponse
    {
        return $this->act($tenant, $audit, 'discard-failed-job', $scope, $uuid, fn() => $failedJobs->discard($tenant, $scope, $uuid), __('flash.tenants.failed_job_discarded'));
    }

    private function act(Tenant $tenant, AuditLogRecorder $audit, string $action, string $scope, string $uuid, \Closure $callback, string $success): RedirectResponse
    {
        try {
            $audit->record($tenant->tenantId(), $action, ['scope' => $scope, 'uuid' => $uuid], function () use ($callback): array {
                $callback();

                return ['jobs' => 1];
            });
        } catch (InvalidArgumentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', $success);
    }
}
