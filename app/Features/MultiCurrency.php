<?php

declare(strict_types=1);

namespace App\Features;

use App\Enums\FeatureFlag;

class MultiCurrency
{
    public string $name = FeatureFlag::MultiCurrency->value;

    public function resolve(mixed $scope): mixed
    {
        return false;
    }
}
