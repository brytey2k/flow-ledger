<?php

declare(strict_types=1);

namespace App\Features;

use App\Enums\FeatureFlag;

class VerifyLoginWithIdp
{
    public string $name = FeatureFlag::VerifyLoginWithIdp->value;

    public function resolve(mixed $scope): mixed
    {
        return false;
    }
}
