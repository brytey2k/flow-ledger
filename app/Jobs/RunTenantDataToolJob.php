<?php

declare(strict_types=1);

namespace App\Jobs;

use App\DTOs\Landlord\TenantDataToolFilters;
use App\Enums\AuditLogStatus;
use App\Enums\TenantDataTool;
use App\Models\Landlord\AuditLog;
use App\Models\Tenant;
use App\Services\TenantDataToolService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Throwable;

class RunTenantDataToolJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 3600;

    public function __construct(public int $auditLogId) {}

    public static function lockKey(string $tenantId): string
    {
        return "tenant-data-tool:{$tenantId}";
    }

    public function handle(TenantDataToolService $service): void
    {
        $auditLog = AuditLog::findOrFail($this->auditLogId);
        $lock = Cache::lock(self::lockKey($auditLog->tenant_id), $this->timeout);

        if (! $lock->get()) {
            $auditLog->update([
                'status' => AuditLogStatus::Failed,
                'error' => 'Another data tool is already running for this tenant.',
                'finished_at' => now(),
            ]);

            return;
        }

        try {
            $auditLog->update(['status' => AuditLogStatus::Running, 'started_at' => now()]);

            $counts = $service->execute(
                Tenant::findOrFail($auditLog->tenant_id),
                TenantDataTool::from($auditLog->action),
                TenantDataToolFilters::fromArray($auditLog->filters ?? []),
            );

            $auditLog->update([
                'status' => AuditLogStatus::Completed,
                'result' => $counts,
                'artifact_path' => $service->artifactPath(),
                'finished_at' => now(),
            ]);
        } finally {
            $lock->release();
        }
    }

    public function failed(Throwable $exception): void
    {
        AuditLog::whereKey($this->auditLogId)->update([
            'status' => AuditLogStatus::Failed,
            'error' => mb_substr($exception->getMessage(), 0, 1000),
            'finished_at' => now(),
        ]);
    }
}
