<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use RuntimeException;
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

    /**
     * The hostname links should be built against when there's no request to
     * derive it from (queued jobs, artisan commands). Falls back to the
     * oldest domain if none is explicitly marked primary.
     */
    public function getPrimaryDomainHostnameAttribute(): string
    {
        $domain = $this->domains()->where('is_primary', true)->first()
            ?? $this->domains()->oldest('id')->first();

        if ($domain === null) {
            $key = $this->getKey();
            $id = is_scalar($key) ? (string) $key : '';

            throw new RuntimeException("Tenant {$id} has no configured domain.");
        }

        return $domain->domain;
    }
}
