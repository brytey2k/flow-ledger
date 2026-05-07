<?php

declare(strict_types=1);

namespace App\Features;

use App\Enums\FeatureFlag;

class BulkExport
{
    public string $name = FeatureFlag::BulkExport->value;

    public function resolve(mixed $scope): mixed
    {
        return false;
    }
}
