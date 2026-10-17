<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant;
use Closure;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Failed jobs can land in the central database or, when the worker was inside tenant context at
 * failure time, in the tenant's own database. Both are listed and acted on.
 */
class TenantFailedJobService
{
    public const SCOPE_CENTRAL = 'central';

    public const SCOPE_TENANT = 'tenant';

    public function countFor(Tenant $tenant): int
    {
        /** @var int $tenantCount */
        $tenantCount = $tenant->run(fn(): int => DB::table('failed_jobs')->count());

        return $this->centralQuery($tenant)->count() + $tenantCount;
    }

    /** @return list<array{scope: string, uuid: string, queue: string, job: string, exception: string, failed_at: string}> */
    public function list(Tenant $tenant, int $limit = 50): array
    {
        /** @var list<object> $tenantRows */
        $tenantRows = $tenant->run(fn(): array => DB::table('failed_jobs')->latest('failed_at')->limit($limit)->get()->all());
        /** @var list<object> $centralRows */
        $centralRows = $this->centralQuery($tenant)->latest('failed_at')->limit($limit)->get()->all();

        $rows = [...$this->present(self::SCOPE_CENTRAL, $centralRows), ...$this->present(self::SCOPE_TENANT, $tenantRows)];

        usort($rows, static fn(array $a, array $b): int => strcmp($b['failed_at'], $a['failed_at']));

        return array_slice($rows, 0, $limit);
    }

    public function retry(Tenant $tenant, string $scope, string $uuid): void
    {
        $this->inScope($tenant, $scope, $uuid, static function () use ($uuid): void {
            Artisan::call('queue:retry', ['id' => [$uuid]]);
        });
    }

    public function discard(Tenant $tenant, string $scope, string $uuid): void
    {
        $this->inScope($tenant, $scope, $uuid, static function () use ($uuid): void {
            Artisan::call('queue:forget', ['id' => $uuid]);
        });
    }

    /** @param Closure(): void $callback */
    private function inScope(Tenant $tenant, string $scope, string $uuid, Closure $callback): void
    {
        if ($scope === self::SCOPE_CENTRAL) {
            if (! $this->centralQuery($tenant)->where('uuid', $uuid)->exists()) {
                throw new InvalidArgumentException('That failed job does not belong to this tenant.');
            }

            $callback();

            return;
        }

        if ($scope !== self::SCOPE_TENANT) {
            throw new InvalidArgumentException('Unknown failed-job scope.');
        }

        $tenant->run($callback);
    }

    private function centralQuery(Tenant $tenant): Builder
    {
        $connection = config('tenancy.database.central_connection');

        return DB::connection(is_string($connection) ? $connection : null)
            ->table('failed_jobs')
            ->where('payload', 'like', '%"tenant_id":"' . $tenant->tenantId() . '"%');
    }

    /**
     * @param string $scope
     * @param list<object> $rows
     *
     * @return list<array{scope: string, uuid: string, queue: string, job: string, exception: string, failed_at: string}>
     */
    private function present(string $scope, array $rows): array
    {
        return array_map(static function (object $row) use ($scope): array {
            $text = static fn(string $field): string => isset($row->{$field}) && is_scalar($row->{$field}) ? (string) $row->{$field} : '';
            $payload = json_decode($text('payload'), true);

            return [
                'scope' => $scope,
                'uuid' => $text('uuid'),
                'queue' => $text('queue'),
                'job' => is_array($payload) && is_string($payload['displayName'] ?? null) ? $payload['displayName'] : 'Unknown job',
                'exception' => mb_substr((string) strtok($text('exception'), "\n"), 0, 300),
                'failed_at' => $text('failed_at'),
            ];
        }, $rows);
    }
}
