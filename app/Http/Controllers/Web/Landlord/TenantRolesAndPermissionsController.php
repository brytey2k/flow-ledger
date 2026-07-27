<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Landlord;

use App\Http\Controllers\Controller;
use App\Http\Requests\Landlord\RolePermissionsSyncRequest;
use App\Http\Requests\Landlord\UserPermissionsSyncRequest;
use App\Http\Requests\Landlord\UserRolesSyncRequest;
use App\Models\Landlord\User as LandlordUser;
use App\Models\Tenant;
use App\Services\LandlordTenantAccessControlService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Landlord-side management of a tenant's Spatie roles/permissions, replacing
 * direct DB edits as the workaround for PermissionEscalationGuard-blocked
 * grants (see that class for the guard itself).
 *
 * This controller deliberately does NOT construct or call
 * PermissionEscalationGuard. That guard checks an acting user's own
 * getAllPermissions() against what they're trying to grant — but a landlord
 * (App\Models\Landlord\User) has no Spatie roles/permissions at all and isn't
 * a tenant-scoped actor, so the check is structurally inapplicable, not
 * merely skipped. Landlord is already a superset-trust actor in this system
 * (can migrate:fresh a tenant via TenantResetService, can impersonate any
 * tenant user via TenantImpersonationService), so granting any tenant
 * permission/role from here is consistent with that existing trust level.
 * Do not "fix" this by trying to adapt the guard for a landlord actor.
 */
class TenantRolesAndPermissionsController extends Controller
{
    public function __construct(private readonly LandlordTenantAccessControlService $accessControlService) {}

    public function roles(Tenant $tenant): View
    {
        $roles = $this->accessControlService->listRoles($tenant);

        return view('landlord.tenants.roles.index', [
            'tenant' => $tenant,
            'roles' => $roles,
        ]);
    }

    public function editRolePermissions(Tenant $tenant, int $role): View
    {
        $roleModel = $this->accessControlService->findRole($tenant, $role);
        $permissions = $this->accessControlService->listPermissions($tenant);

        return view('landlord.tenants.roles.permissions', [
            'tenant' => $tenant,
            'role' => $roleModel,
            'permissions' => $permissions,
        ]);
    }

    public function updateRolePermissions(RolePermissionsSyncRequest $request, Tenant $tenant, int $role): RedirectResponse
    {
        /** @var array<int, int|string> $rawPermissionIds */
        $rawPermissionIds = $request->validated('permissions', []);
        $permissionIds = array_map('intval', $rawPermissionIds);

        /** @var LandlordUser $causer */
        $causer = $request->user('landlord');

        $this->accessControlService->syncRolePermissions($tenant, $role, $permissionIds, $causer);

        return redirect()
            ->route('landlord.tenants.roles.permissions.edit', [$tenant, $role])
            ->with('success', __('flash.landlord.role_permissions_updated'));
    }

    public function users(Tenant $tenant, Request $request): View
    {
        $page = (int) $request->query('page', 1);
        $users = $this->accessControlService->listUsersPaginated($tenant, 15, $page);

        return view('landlord.tenants.users.index', [
            'tenant' => $tenant,
            'users' => $users,
        ]);
    }

    public function editUserRoles(Tenant $tenant, int $user): View
    {
        $userModel = $this->accessControlService->findUser($tenant, $user);
        $roles = $this->accessControlService->listRoles($tenant);

        return view('landlord.tenants.users.roles', [
            'tenant' => $tenant,
            'user' => $userModel,
            'roles' => $roles,
        ]);
    }

    public function updateUserRoles(UserRolesSyncRequest $request, Tenant $tenant, int $user): RedirectResponse
    {
        /** @var array<int, int|string> $rawRoleIds */
        $rawRoleIds = $request->validated('roles', []);
        $roleIds = array_map('intval', $rawRoleIds);

        /** @var LandlordUser $causer */
        $causer = $request->user('landlord');

        $this->accessControlService->syncUserRoles($tenant, $user, $roleIds, $causer);

        return redirect()
            ->route('landlord.tenants.users.roles.edit', [$tenant, $user])
            ->with('success', __('flash.landlord.user_roles_updated'));
    }

    public function editUserPermissions(Tenant $tenant, int $user): View
    {
        $userModel = $this->accessControlService->findUser($tenant, $user);
        $permissions = $this->accessControlService->listPermissions($tenant);

        return view('landlord.tenants.users.permissions', [
            'tenant' => $tenant,
            'user' => $userModel,
            'permissions' => $permissions,
        ]);
    }

    public function updateUserPermissions(UserPermissionsSyncRequest $request, Tenant $tenant, int $user): RedirectResponse
    {
        /** @var array<int, int|string> $rawPermissionIds */
        $rawPermissionIds = $request->validated('permissions', []);
        $permissionIds = array_map('intval', $rawPermissionIds);

        /** @var LandlordUser $causer */
        $causer = $request->user('landlord');

        $this->accessControlService->syncUserPermissions($tenant, $user, $permissionIds, $causer);

        return redirect()
            ->route('landlord.tenants.users.permissions.edit', [$tenant, $user])
            ->with('success', __('flash.landlord.user_permissions_updated'));
    }
}
