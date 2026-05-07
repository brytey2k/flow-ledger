<?php

declare(strict_types=1);

namespace App\Features;

use App\Enums\FeatureFlag;

class AdvancedReporting
{
    public string $name = FeatureFlag::AdvancedReporting->value;

    public function resolve(mixed $scope): mixed
    {
        return false;
    }
}
