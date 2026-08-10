<?php

declare(strict_types=1);

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\Cache;

class DashboardCacheService
{
    /**
     * @template TPayload of array<string, mixed>
     *
     * @param string $tenantKey
     * @param int $userId
     * @param array<int, int> $branchIds
     * @param Closure(): TPayload $resolver
     *
     * @return TPayload
     */
    public function rememberUserPayload(string $tenantKey, int $userId, array $branchIds, Closure $resolver): array
    {
        $version = $this->currentVersion($tenantKey);
        sort($branchIds);
        $branchHash = sha1(implode(',', $branchIds));
        $cacheKey = "dashboard:analytics:{$tenantKey}:v{$version}:u{$userId}:b{$branchHash}";

        return Cache::remember($cacheKey, now()->addMinutes(2), $resolver);
    }

    public function invalidateForCurrentTenant(): void
    {
        $this->invalidateForTenant($this->resolveTenantCacheKey());
    }

    public function invalidateForTenant(string $tenantKey): void
    {
        $versionKey = $this->versionKey($tenantKey);
        $cached = Cache::get($versionKey, 1);
        $currentVersion = is_int($cached) ? $cached : 1;
        Cache::forever($versionKey, $currentVersion + 1);
    }

    public function resolveTenantCacheKey(): string
    {
        $tenant = tenant();

        if (is_object($tenant) && method_exists($tenant, 'getTenantKey')) {
            /** @var mixed $key */
            $key = $tenant->getTenantKey();

            if (is_scalar($key)) {
                return (string) $key;
            }
        }

        return 'tenant';
    }

    private function currentVersion(string $tenantKey): int
    {
        $versionKey = $this->versionKey($tenantKey);
        $cached = Cache::get($versionKey, 1);
        $version = is_int($cached) ? $cached : 1;

        if ($version < 1) {
            Cache::forever($versionKey, 1);

            return 1;
        }

        return $version;
    }

    private function versionKey(string $tenantKey): string
    {
        return "dashboard:analytics:version:{$tenantKey}";
    }
}
