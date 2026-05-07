<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\FeatureFlag;
use App\Interfaces\FeatureFlagServiceInterface;
use App\Models\Tenant;
use Illuminate\Support\Facades\Cache;
use Laravel\Pennant\Feature;

class PennantFeatureFlagService implements FeatureFlagServiceInterface
{
    private const int CACHE_TTL = 3600 * 12;

    private const string CACHE_TAG = 'feature_flags';

    private function getCacheKey(Tenant $tenant): string
    {
        return "tenant.{$tenant->id}";
    }

    #[\Override]
    public function getValue(string $feature, Tenant|null $tenant = null): mixed
    {
        if ($tenant === null) {
            throw new \InvalidArgumentException('Tenant must be provided.');
        }

        return Feature::for($tenant)->value($feature);
    }

    #[\Override]
    public function activate(FeatureFlag $feature, Tenant $tenant): void
    {
        Feature::for($tenant)->activate($feature->featureClass());
        $this->clearCache($tenant);
    }

    #[\Override]
    public function deactivate(FeatureFlag $feature, Tenant $tenant): void
    {
        Feature::for($tenant)->deactivate($feature->featureClass());
        $this->clearCache($tenant);
    }

    /**
     * @return array<string, bool>
     */
    #[\Override]
    public function getAll(Tenant $tenant): array
    {
        return Cache::tags([self::CACHE_TAG])->remember(
            $this->getCacheKey($tenant),
            self::CACHE_TTL,
            function () use ($tenant): array {
                $flags = [];

                foreach (FeatureFlag::cases() as $flag) {
                    $flags[$flag->value] = (bool) Feature::for($tenant)->value($flag->featureClass());
                }

                return $flags;
            },
        );
    }

    #[\Override]
    public function activateForAll(FeatureFlag $feature): void
    {
        Feature::activateForEveryone($feature->featureClass());
        $this->clearAllCache();
    }

    #[\Override]
    public function deactivateForAll(FeatureFlag $feature): void
    {
        Feature::deactivateForEveryone($feature->featureClass());
        $this->clearAllCache();
    }

    private function clearCache(Tenant $tenant): void
    {
        Cache::tags([self::CACHE_TAG])->forget($this->getCacheKey($tenant));
    }

    #[\Override]
    public function clearAllCache(): void
    {
        try {
            Cache::tags([self::CACHE_TAG])->flush();
        } catch (\BadMethodCallException) {
            Cache::flush();
        }
    }
}
