<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Landlord;

use App\Http\Controllers\Controller;
use App\Http\Requests\Landlord\TenantAnnouncementRequest;
use App\Models\Tenant;
use App\Services\AuditLogRecorder;
use App\Services\TenantAnnouncementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TenantAnnouncementsController extends Controller
{
    public function edit(Tenant $tenant, TenantAnnouncementService $announcements): View
    {
        return view('landlord.tenants.announcements', ['tenant' => $tenant, 'settings' => $announcements->get($tenant)]);
    }

    public function update(TenantAnnouncementRequest $request, Tenant $tenant, TenantAnnouncementService $announcements, AuditLogRecorder $audit): RedirectResponse
    {
        /** @var array{announcement_message?: string|null, announcement_level?: string|null, maintenance_mode?: bool|null, maintenance_message?: string|null} $data */
        $data = $request->validated();
        $data['maintenance_mode'] = $request->boolean('maintenance_mode');

        $audit->record($tenant->tenantId(), 'update-announcements', $data, function () use ($announcements, $tenant, $data): array {
            $announcements->update($tenant, $data);

            return ['maintenance_mode' => (int) $data['maintenance_mode']];
        });

        return back()->with('success', __('flash.tenants.announcements_updated'));
    }
}
