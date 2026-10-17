<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Landlord;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\AuditLogRecorder;
use App\Services\TenantStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TenantStorageController extends Controller
{
    public function index(Tenant $tenant, TenantStorageService $storage): View
    {
        return view('landlord.tenants.storage', ['tenant' => $tenant, 'report' => $storage->report($tenant)]);
    }

    public function cleanup(Tenant $tenant, TenantStorageService $storage, AuditLogRecorder $audit): RedirectResponse
    {
        $result = $audit->record($tenant->tenantId(), 'delete-orphaned-files', [], fn(): array => $storage->deleteOrphans($tenant));

        return back()->with('success', __('flash.tenants.storage_cleaned', ['count' => $result['orphaned_files_deleted']]));
    }
}
