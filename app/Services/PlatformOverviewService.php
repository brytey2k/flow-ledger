<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\RefreshTenantHealthJob;
use App\Models\Landlord\TenantHealthSnapshot;
use App\Models\Tenant;
use App\Services\Backup\BackupService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Throwable;

class PlatformOverviewService
{
    private const BACKUP_PROBLEMS_CACHE_KEY = 'platform-overview.backup-problems';

    private const BACKUP_PROBLEMS_TTL_SECONDS = 300;

    public function __construct(
        private readonly TenantHealthService $health,
        private readonly BackupService $backups,
        private readonly PlatformHeartbeatService $heartbeat,
    ) {}

    /**
     * Reads the stored readings. Nothing here opens a customer's database.
     *
     * @return array{
     *     tenants: list<array{tenant: Tenant, reading: TenantHealthSnapshot|null}>,
     *     backup_problems: list<string>,
     *     totals: array{tenants: int, needing_attention: int, failed_jobs: int, blocked_stages: int},
     *     oldest_reading_at: \Carbon\Carbon|null,
     *     heartbeat: array{scheduler: array{ok: bool, last_seen_at: int|null}, queue: array{ok: bool, last_seen_at: int|null}, ok: bool}
     * }
     */
    public function overview(): array
    {
        /** @var Collection<int, Tenant> $tenants */
        $tenants = Tenant::query()->orderBy('id')->get();
        $readings = TenantHealthSnapshot::query()->whereIn('tenant_id', $tenants->modelKeys())->get()->keyBy('tenant_id');

        $rows = [];
        $totals = ['tenants' => 0, 'needing_attention' => 0, 'failed_jobs' => 0, 'blocked_stages' => 0];
        $oldest = null;

        foreach ($tenants as $tenant) {
            $reading = $readings->get($tenant->tenantId());

            $rows[] = ['tenant' => $tenant, 'reading' => $reading];

            $totals['tenants']++;
            $totals['needing_attention'] += $reading === null || $reading->needs_attention ? 1 : 0;
            $totals['failed_jobs'] += $this->count($reading?->snapshot['failed_jobs'] ?? null);
            $totals['blocked_stages'] += $this->count($reading?->snapshot['blocked_stages'] ?? null);

            if ($reading !== null && ($oldest === null || $reading->captured_at->lt($oldest))) {
                $oldest = $reading->captured_at;
            }
        }

        /** @var list<string> $problems */
        $problems = Cache::remember(
            self::BACKUP_PROBLEMS_CACHE_KEY,
            self::BACKUP_PROBLEMS_TTL_SECONDS,
            fn(): array => $this->backups->problems(),
        );

        return ['tenants' => $rows, 'backup_problems' => $problems, 'totals' => $totals, 'oldest_reading_at' => $oldest, 'heartbeat' => $this->heartbeat->status()];
    }

    /** Takes a fresh reading for one customer. A database that cannot be read is stored as an error, not thrown. */
    public function refresh(Tenant $tenant): TenantHealthSnapshot
    {
        $snapshot = null;
        $error = null;

        try {
            $snapshot = $this->health->snapshot($tenant);
        } catch (Throwable $throwable) {
            report($throwable);
            $error = mb_substr($throwable->getMessage(), 0, 1000);
        }

        $attention = $snapshot === null ? ['Database could not be read'] : $this->attention($snapshot);

        return TenantHealthSnapshot::query()->updateOrCreate(
            ['tenant_id' => $tenant->tenantId()],
            [
                'snapshot' => $snapshot,
                'attention' => $attention,
                'needs_attention' => $attention !== [],
                'error' => $error,
                'captured_at' => now(),
            ],
        );
    }

    /** Queues one reading per customer, clears readings for deleted customers and forgets cached backup problems. */
    public function queueRefreshAll(): int
    {
        /** @var Collection<int, Tenant> $tenants */
        $tenants = Tenant::query()->get();
        $ids = [];

        foreach ($tenants as $tenant) {
            $ids[] = $tenant->tenantId();
        }

        TenantHealthSnapshot::query()->whereNotIn('tenant_id', $ids)->delete();
        Cache::forget(self::BACKUP_PROBLEMS_CACHE_KEY);

        foreach ($ids as $id) {
            RefreshTenantHealthJob::dispatch($id);
        }

        return count($ids);
    }

    private function count(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    /**
     * @param array<string, mixed> $snapshot
     *
     * @return list<string>
     */
    private function attention(array $snapshot): array
    {
        $reasons = [];

        foreach ([
            'blocked_stages' => 'blocked workflow stage(s)',
            'stale_stages' => 'stale workflow stage(s)',
            'overdue_retirements' => 'overdue retirement(s)',
            'failed_jobs' => 'failed job(s)',
        ] as $key => $label) {
            $count = $this->count($snapshot[$key] ?? null);

            if ($count > 0) {
                $reasons[] = "{$count} {$label}";
            }
        }

        $backupAt = $snapshot['last_backup_at'] ?? null;
        $maxAgeHours = is_numeric(config('backup.max_age_hours')) ? (int) config('backup.max_age_hours') : 8;

        if (! is_string($backupAt) || Carbon::parse($backupAt)->lt(now()->subHours($maxAgeHours))) {
            $reasons[] = 'No recent backup';
        }

        return $reasons;
    }
}
