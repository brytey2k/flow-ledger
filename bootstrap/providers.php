<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use App\Providers\FeatureFlagServiceProvider;
use App\Providers\TenancyServiceProvider;

return [
    AppServiceProvider::class,
    TenancyServiceProvider::class,
    FeatureFlagServiceProvider::class,
];
