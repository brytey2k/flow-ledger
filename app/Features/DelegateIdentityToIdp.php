<?php

declare(strict_types=1);

namespace App\Features;

use App\Enums\FeatureFlag;

class DelegateIdentityToIdp
{
    public string $name = FeatureFlag::DelegateIdentityToIdp->value;

    public function resolve(mixed $scope): mixed
    {
        return false;
    }
}
