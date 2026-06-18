<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Tenant;

class TenantRepository
{
    public function findByIdpTenantId(string $idpTenantId): Tenant|null
    {
        return Tenant::query()->where('idp_tenant_id', $idpTenantId)->first();
    }
}
