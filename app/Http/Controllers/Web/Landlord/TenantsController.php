<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Landlord;

use App\Http\Controllers\Controller;
use App\Http\Requests\Landlord\PostImpersonateTenantRequest;
use App\Http\Requests\Landlord\TenantCreateRequest;
use App\Http\Requests\Landlord\TenantDeleteRequest;
use App\Http\Requests\Landlord\TenantResetRequest;
use App\Models\Tenant;
use App\Services\NewTenantSetupService;
use App\Services\TenantImpersonationService;
use App\Services\TenantResetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function selectUser(Tenant $tenant, Request $request, TenantImpersonationService $impersonationService): View
    {
        $tenant->loadMissing('domains');
        $page = (int) $request->query('page', 1);
        $users = $impersonationService->getTenantUsersPaginated($tenant, 15, $page);

        return view('landlord.tenants.select-user', compact('tenant', 'users'));
    }

    public function users(Tenant $tenant, Request $request, TenantImpersonationService $impersonationService): JsonResponse
    {
        $query = $request->string('q')->toString();
        $users = $impersonationService->searchTenantUsers($tenant, $query);

        return response()->json($users->map(fn($u) => [
            'id' => $u->id,
            'name' => trim($u->first_name . ' ' . $u->last_name),
            'email' => $u->email,
        ]));
    }

    public function impersonate(PostImpersonateTenantRequest $request, Tenant $tenant, TenantImpersonationService $impersonationService): RedirectResponse
    {
        $tenant->loadMissing('domains');

        $userIdentifier = $request->string('user_identifier')->toString();
        $user = $impersonationService->findTenantUser($tenant, $userIdentifier);

        if ($user === null) {
            return back()->withInput()->with('error', 'User not found in this tenant.');
        }

        $domain = $tenant->domains->first();

        if ($domain === null) {
            return back()->withInput()->with('error', 'Tenant has no domain configured.');
        }

        $token = $impersonationService->createImpersonationToken($tenant, $user);

        $port = $request->getPort();
        $scheme = $request->getScheme();
        $isDefaultPort = ($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443);

        $url = sprintf(
            '%s://%s/impersonate/%s',
            $scheme,
            $isDefaultPort ? $domain->domain : $domain->domain . ':' . $port,
            $token->token,
        );

        return redirect($url);
    }

    private function resolveTenantName(Tenant $tenant): string
    {
        $name = $tenant->getAttribute('name');
        $key = $tenant->getTenantKey();
        $id = is_scalar($key) ? (string) $key : '';

        return is_string($name) && $name !== '' ? $name : $id;
    }
}
