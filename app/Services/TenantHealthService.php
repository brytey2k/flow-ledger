<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class TenantHealthService
{
    public const STALE_STAGE_DAYS = 7;

    public function __construct(private readonly SettingsService $settings, private readonly TenantFailedJobService $failedJobs) {}

    /**
     * @param Tenant $tenant
     *
     * @return array{
     *     payment_requests: array<string, int>,
     *     retirements: array<string, int>,
     *     blocked_stages: int,
     *     stale_stages: int,
     *     overdue_retirements: int,
     *     failed_jobs: int,
     *     last_activity_at: string|null,
     *     attachments: int,
     *     storage_bytes: int,
     *     users: array<string, int>
     * }
     */
    public function snapshot(Tenant $tenant): array
    {
        $failedJobs = $this->failedJobs->countFor($tenant);

        /** @var array{payment_requests: array<string, int>, retirements: array<string, int>, blocked_stages: int, stale_stages: int, overdue_retirements: int, failed_jobs: int, last_activity_at: string|null, attachments: int, storage_bytes: int, users: array<string, int>} $snapshot */
        $snapshot = $tenant->run(function () use ($failedJobs): array {
            $graceDays = $this->settings->getRetirementReminderSettings()['grace_period_days'];

            $lastActivity = DB::table('activity_log')->max('created_at');
            $storageBytes = DB::table('attachments')->whereNull('deleted_at')->sum('size');

            return [
                'payment_requests' => $this->countByStatus('payment_requests'),
                'retirements' => $this->countByStatus('retirement_requests'),
                'blocked_stages' => DB::table('workflow_instance_stages')->where('status', 'blocked')->count(),
                'stale_stages' => DB::table('workflow_instance_stages')
                    ->where('status', 'active')
                    ->where('started_at', '<', now()->subDays(self::STALE_STAGE_DAYS))
                    ->count(),
                'overdue_retirements' => DB::table('payment_requests')
                    ->where('type', 'advance')
                    ->where('status', 'disbursed')
                    ->whereNull('deleted_at')
                    ->where('disbursed_at', '<=', now()->subDays($graceDays))
                    ->whereNotExists(fn(Builder $query) => $query->from('retirement_requests')
                        ->whereColumn('retirement_requests.payment_request_id', 'payment_requests.id')
                        ->whereIn('status', ['in_workflow', 'approved', 'sent_back']))
                    ->count(),
                'failed_jobs' => $failedJobs,
                'last_activity_at' => is_string($lastActivity) ? $lastActivity : null,
                'attachments' => DB::table('attachments')->whereNull('deleted_at')->count(),
                'storage_bytes' => (int) $storageBytes,
                'users' => $this->countByStatus('users'),
            ];
        });

        return $snapshot;
    }

    /** @return array<string, int> */
    private function countByStatus(string $table): array
    {
        /** @var array<string, int> $counts */
        $counts = DB::table($table)
            ->whereNull('deleted_at')
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn($total): int => is_numeric($total) ? (int) $total : 0)
            ->all();

        return $counts;
    }
}
