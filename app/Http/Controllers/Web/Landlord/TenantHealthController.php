<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Landlord;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\TenantHealthService;
use Illuminate\View\View;

class TenantHealthController extends Controller
{
    public function index(Tenant $tenant, TenantHealthService $health): View
    {
        return view('landlord.tenants.health', [
            'tenant' => $tenant,
            'snapshot' => $health->snapshot($tenant),
            'staleDays' => TenantHealthService::STALE_STAGE_DAYS,
        ]);
    }
}
