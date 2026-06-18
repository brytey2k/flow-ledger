<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase;
    use HasDomains;

    protected $fillable = [
        'id',
        'name',
        'is_suspended',
        'idp_tenant_id',
    ];

    /** @return list<string> */
    public static function getCustomColumns(): array
    {
        return ['id', 'idp_tenant_id', 'is_suspended'];
    }

    /** @return HasMany<Domain, $this> */
    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }

    public function isSuspended(): bool
    {
        return (bool) $this->getAttribute('is_suspended');
    }
}
