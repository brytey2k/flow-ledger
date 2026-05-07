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
        $service->createTenant(
            $request->validated('id'),
            $request->validated('name'),
            $request->validated('admin_email'),
            $request->validated('admin_password'),
        );

        return redirect()
            ->route('landlord.tenants.index')
            ->with('success', 'Tenant created successfully.');
    }

    public function suspend(Tenant $tenant): RedirectResponse
    {
        $tenant->update(['is_suspended' => true]);

        return back()->with('success', 'Tenant suspended.');
    }

    public function unsuspend(Tenant $tenant): RedirectResponse
    {
        $tenant->update(['is_suspended' => false]);

        return back()->with('success', 'Tenant reactivated.');
    }

    public function reset(TenantResetRequest $request, Tenant $tenant, TenantResetService $tenantResetService): RedirectResponse
    {
        $expectedName = $this->resolveTenantName($tenant);
        $providedName = trim($request->string('confirm_tenant_name')->toString());

        if (strcasecmp($providedName, $expectedName) !== 0) {
            return redirect()
                ->route('landlord.tenants.index')
                ->with('error', 'Tenant reset canceled: confirmation name does not match.');
        }

        try {
            $tenantResetService->reset($tenant);
        } catch (\Throwable $throwable) {
            report($throwable);

            return redirect()
                ->route('landlord.tenants.index')
                ->with('error', 'Failed to reset tenant.');
        }

        return redirect()
            ->route('landlord.tenants.index')
            ->with('success', 'Tenant reset successfully.');
    }

    public function destroy(TenantDeleteRequest $request, Tenant $tenant): RedirectResponse
    {
        $expectedName = $this->resolveTenantName($tenant);
        $providedName = trim($request->string('confirm_tenant_name')->toString());

        if (strcasecmp($providedName, $expectedName) !== 0) {
            return redirect()
                ->route('landlord.tenants.index')
                ->with('error', 'Tenant deletion canceled: confirmation name does not match.');
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
                ->with('error', 'Failed to delete tenant.');
        }

        return redirect()
            ->route('landlord.tenants.index')
            ->with('success', 'Tenant deleted successfully.');
    }

    private function resolveTenantName(Tenant $tenant): string
    {
        return (string) ($tenant->name ?? $tenant->id);
    }
}
