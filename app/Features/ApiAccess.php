<?php

declare(strict_types=1);

namespace App\Features;

use App\Enums\FeatureFlag;

class ApiAccess
{
    public string $name = FeatureFlag::ApiAccess->value;

    public function resolve(mixed $scope): mixed
    {
        return false;
    }
}
