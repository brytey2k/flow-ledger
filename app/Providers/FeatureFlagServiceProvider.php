<?php

declare(strict_types=1);

namespace App\Providers;

use App\Interfaces\FeatureFlagServiceInterface;
use App\Services\PennantFeatureFlagService;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class FeatureFlagServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->app->bind(FeatureFlagServiceInterface::class, PennantFeatureFlagService::class);
    }

    /**
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [FeatureFlagServiceInterface::class];
    }
}
