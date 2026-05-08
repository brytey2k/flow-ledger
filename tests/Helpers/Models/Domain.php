<?php

declare(strict_types=1);

namespace Tests\Helpers\Models;

use Stancl\Tenancy\Database\Models\Domain as BaseDomain;

class Domain extends BaseDomain
{
    public function getConnectionName(): string
    {
        return 'testing_pgsql';
    }
}
