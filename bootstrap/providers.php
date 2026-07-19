<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use App\Providers\FeatureFlagServiceProvider;
use App\Providers\MorphMapServiceProvider;
use App\Providers\TenancyServiceProvider;

return [
    AppServiceProvider::class,
    MorphMapServiceProvider::class,
    TenancyServiceProvider::class,
    FeatureFlagServiceProvider::class,
];
