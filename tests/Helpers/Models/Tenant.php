<?php

declare(strict_types=1);

namespace Tests\Helpers\Models;

use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase;
    use HasDomains;

    public function isSuspended(): bool
    {
        return (bool) ($this->getAttribute('is_suspended') ?? false);
    }

    public function getConnectionName(): string
    {
        return 'testing_pgsql';
    }
}
