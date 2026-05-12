<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Landlord;

use App\Http\Controllers\Controller;
use App\Http\Requests\Landlord\TenantCreateRequest;
use App\Http\Requests\Landlord\TenantDeleteRequest;
use App\Http\Requests\Landlord\TenantResetRequest;
use App\Models\Tenant;
use App\Services\NewTenantSetupService;
use App\Services\TenantResetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Stancl\Tenancy\Jobs\DeleteDatabase;

class TenantsController extends Controller
{
    public function index(): View
    {
        $tenants = Tenant::with('domains')->orderByDesc('created_at')->get();

        return view('landlord.tenants.index', compact('tenants'));
    }

    public function create(): View
    {
        return view('landlord.tenants.create');
    }

    public function store(TenantCreateRequest $request, NewTenantSetupService $service): RedirectResponse
    {
        /** @var array{id: string, name: string, admin_email: string, admin_password: string} $data */
        $data = $request->validated();

        $service->createTenant(
            $data['id'],
            $data['name'],
            $data['admin_email'],
            $data['admin_password'],
        );

        return redirect()
            ->route('landlord.tenants.index')
            ->with('success', __('flash.tenants.created'));
    }

    public function suspend(Tenant $tenant): RedirectResponse
    {
        $tenant->update(['is_suspended' => true]);

        return back()->with('success', __('flash.tenants.suspended'));
    }

    public function unsuspend(Tenant $tenant): RedirectResponse
    {
        $tenant->update(['is_suspended' => false]);

        return back()->with('success', __('flash.tenants.reactivated'));
    }

    public function reset(TenantResetRequest $request, Tenant $tenant, TenantResetService $tenantResetService): RedirectResponse
    {
        $expectedName = $this->resolveTenantName($tenant);
        $providedName = trim($request->string('confirm_tenant_name')->toString());

        if (strcasecmp($providedName, $expectedName) !== 0) {
            return redirect()
                ->route('landlord.tenants.index')
                ->with('error', __('flash.tenants.reset_confirm_mismatch'));
        }

        try {
            $tenantResetService->reset($tenant);
        } catch (\Throwable $throwable) {
            report($throwable);

            return redirect()
                ->route('landlord.tenants.index')
                ->with('error', __('flash.tenants.reset_failed'));
        }

        return redirect()
            ->route('landlord.tenants.index')
            ->with('success', __('flash.tenants.reset_success'));
    }

    public function destroy(TenantDeleteRequest $request, Tenant $tenant): RedirectResponse
    {
        $expectedName = $this->resolveTenantName($tenant);
        $providedName = trim($request->string('confirm_tenant_name')->toString());

        if (strcasecmp($providedName, $expectedName) !== 0) {
            return redirect()
                ->route('landlord.tenants.index')
                ->with('error', __('flash.tenants.delete_confirm_mismatch'));
        }

        try {
            if ($request->boolean('delete_database')) {
                dispatch_sync(new DeleteDatabase($tenant));
            }

            $tenant->delete();
        } catch (\Throwable $throwable) {
            report($throwable);

            return redirect()
                ->route('landlord.tenants.index')
                ->with('error', __('flash.tenants.delete_failed'));
        }

        return redirect()
            ->route('landlord.tenants.index')
            ->with('success', __('flash.tenants.deleted'));
    }

    private function resolveTenantName(Tenant $tenant): string
    {
        $name = $tenant->getAttribute('name');
        $key = $tenant->getTenantKey();
        $id = is_scalar($key) ? (string) $key : '';

        return is_string($name) && $name !== '' ? $name : $id;
    }
}
