<?php

declare(strict_types=1);

namespace App\Interfaces;

use App\Enums\FeatureFlag;
use App\Models\Tenant;

interface FeatureFlagServiceInterface
{
    public function getValue(string $feature, Tenant|null $tenant = null): mixed;

    public function activate(FeatureFlag $feature, Tenant $tenant): void;

    public function deactivate(FeatureFlag $feature, Tenant $tenant): void;

    /**
     * Get all feature flags with their current state for a tenant.
     *
     * @param Tenant $tenant
     *
     * @return array<string, bool>
     */
    public function getAll(Tenant $tenant): array;

    public function activateForAll(FeatureFlag $feature): void;

    public function deactivateForAll(FeatureFlag $feature): void;

    public function clearAllCache(): void;
}
