<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Landlord\User as LandlordUser;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\PermissionRegistrar;
use Stancl\Tenancy\Contracts\Tenant as TenantContract;

/**
 * Landlord-side reads/writes against a tenant's own Role/Permission tables —
 * the mechanism that lets a landlord grant a permission a tenant's admin role
 * is currently missing, replacing direct DB edits as the workaround for
 * PermissionEscalationGuard-blocked grants.
 *
 * Deliberately does NOT invoke PermissionEscalationGuard: see
 * App\Http\Controllers\Web\Landlord\TenantRolesAndPermissionsController's
 * class doc-block for why.
 *
 * Every method runs inside $tenant->run() (the established codebase pattern,
 * see TenantImpersonationService) and brackets the Spatie work with
 * PermissionRegistrar::forgetCachedPermissions() on both sides — this keeps
 * the process-level permission cache from bleeding one tenant's
 * roles/permissions into a response for another tenant.
 */
class LandlordTenantAccessControlService
{
    /**
     * @param TenantContract $tenant
     *
     * @return Collection<int, Role>
     */
    public function listRoles(TenantContract $tenant): Collection
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        /** @var Collection<int, Role> $roles */
        $roles = $tenant->run(fn(): Collection => Role::withCount(['users', 'permissions'])
            ->orderBy('name')
            ->get());

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $roles;
    }

    public function findRole(TenantContract $tenant, int $roleId): Role
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        /** @var Role $role */
        $role = $tenant->run(fn(): Role => Role::with('permissions')->findOrFail($roleId));

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $role;
    }

    /**
     * @param TenantContract $tenant
     *
     * @return Collection<int, Permission>
     */
    public function listPermissions(TenantContract $tenant): Collection
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        /** @var Collection<int, Permission> $permissions */
        $permissions = $tenant->run(fn(): Collection => Permission::orderBy('name')->get());

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $permissions;
    }

    /**
     * @param TenantContract $tenant
     * @param int $roleId
     * @param array<int, int> $permissionIds
     * @param LandlordUser $causer
     */
    public function syncRolePermissions(TenantContract $tenant, int $roleId, array $permissionIds, LandlordUser $causer): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $tenant->run(function () use ($roleId, $permissionIds, $causer): void {
            /** @var Role $role */
            $role = Role::findOrFail($roleId);
            $permissions = Permission::whereIn('id', $permissionIds)->get();

            $role->syncPermissions($permissions);

            activity('tenant_permissions')
                ->causedBy($causer)
                ->performedOn($role)
                ->withProperties(['permission_ids' => $permissionIds])
                ->log('Landlord updated permissions for role :subject.name');
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @param TenantContract $tenant
     * @param int $perPage
     * @param int $page
     *
     * @return LengthAwarePaginator<int, TenantUser>
     */
    public function listUsersPaginated(TenantContract $tenant, int $perPage = 15, int $page = 1): LengthAwarePaginator
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        /** @var LengthAwarePaginator<int, TenantUser> $paginator */
        $paginator = $tenant->run(fn(): LengthAwarePaginator => TenantUser::with(['roles', 'permissions', 'branch'])
            ->orderBy('first_name')
            ->paginate($perPage, ['*'], 'page', $page));

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $paginator;
    }

    public function findUser(TenantContract $tenant, int $userId): TenantUser
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        /** @var TenantUser $user */
        $user = $tenant->run(fn(): TenantUser => TenantUser::with(['roles', 'permissions'])->findOrFail($userId));

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $user;
    }

    /**
     * @param TenantContract $tenant
     * @param int $userId
     * @param array<int, int> $roleIds
     * @param LandlordUser $causer
     */
    public function syncUserRoles(TenantContract $tenant, int $userId, array $roleIds, LandlordUser $causer): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $tenant->run(function () use ($userId, $roleIds, $causer): void {
            /** @var TenantUser $user */
            $user = TenantUser::findOrFail($userId);
            $user->syncRoles($roleIds);

            activity('tenant_permissions')
                ->causedBy($causer)
                ->performedOn($user)
                ->withProperties(['role_ids' => $roleIds])
                ->log('Landlord updated roles for user :subject.email');
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @param TenantContract $tenant
     * @param int $userId
     * @param array<int, int> $permissionIds
     * @param LandlordUser $causer
     */
    public function syncUserPermissions(TenantContract $tenant, int $userId, array $permissionIds, LandlordUser $causer): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $tenant->run(function () use ($userId, $permissionIds, $causer): void {
            /** @var TenantUser $user */
            $user = TenantUser::findOrFail($userId);
            $user->syncPermissions($permissionIds);

            activity('tenant_permissions')
                ->causedBy($causer)
                ->performedOn($user)
                ->withProperties(['permission_ids' => $permissionIds])
                ->log('Landlord updated direct permissions for user :subject.email');
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @param TenantContract $tenant
     * @param array<int, int> $ids
     *
     * @return array<int, int>
     */
    public function idsNotBelongingToTenantRoles(TenantContract $tenant, array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        /** @var array<int, int> $unknownIds */
        $unknownIds = $tenant->run(function () use ($ids): array {
            /** @var array<int, int> $existingIds */
            $existingIds = Role::whereIn('id', $ids)->pluck('id')->map(function ($id) {
                /** @var int|string $rawId */
                $rawId = $id;

                return intval($rawId);
            })->all();

            return array_values(array_diff($ids, $existingIds));
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $unknownIds;
    }

    /**
     * @param TenantContract $tenant
     * @param array<int, int> $ids
     *
     * @return array<int, int>
     */
    public function idsNotBelongingToTenantPermissions(TenantContract $tenant, array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        /** @var array<int, int> $unknownIds */
        $unknownIds = $tenant->run(function () use ($ids): array {
            /** @var array<int, int> $existingIds */
            $existingIds = Permission::whereIn('id', $ids)->pluck('id')->map(function ($id) {
                /** @var int|string $rawId */
                $rawId = $id;

                return intval($rawId);
            })->all();

            return array_values(array_diff($ids, $existingIds));
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $unknownIds;
    }
}
