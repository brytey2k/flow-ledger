<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant\User;

/**
 * Prevents a user from granting (directly, or via a role) a permission they
 * do not themselves hold — whether the target is their own account, an
 * existing account, or a brand-new account they are creating.
 *
 * Only newly granted permissions are checked: permissions the target already
 * has are left alone even if the acting user lacks them, so a no-op resubmit
 * of a form that pre-fills the target's current grants is never blocked.
 */
class PermissionEscalationGuard
{
    /**
     * Returns the permission names that syncing $requestedPermissionIds onto
     * the target would newly grant, but which the acting user does not
     * currently hold. An empty array means the sync is safe to apply.
     *
     * @param User $actor
     * @param array<int, int> $requestedPermissionIds
     * @param array<int, int> $currentPermissionIds
     *
     * @return array<int, string>
     */
    public function deniedPermissionNames(User $actor, array $requestedPermissionIds, array $currentPermissionIds): array
    {
        $newlyGrantedIds = array_diff($requestedPermissionIds, $currentPermissionIds);

        if ($newlyGrantedIds === []) {
            return [];
        }

        /** @var array<int, string> $newlyGrantedNames */
        $newlyGrantedNames = Permission::query()->whereIn('id', $newlyGrantedIds)->pluck('name')->all();

        return $this->namesNotHeldBy($actor, $newlyGrantedNames);
    }

    /**
     * Returns the permission names that assigning $requestedRoleIds to the
     * target would newly grant (via those roles' permissions), but which the
     * acting user does not currently hold. An empty array means the
     * assignment is safe to apply.
     *
     * @param User $actor
     * @param array<int, int> $requestedRoleIds
     * @param array<int, int> $currentRoleIds
     *
     * @return array<int, string>
     */
    public function deniedPermissionNamesForRoles(User $actor, array $requestedRoleIds, array $currentRoleIds): array
    {
        $newlyGrantedRoleIds = array_diff($requestedRoleIds, $currentRoleIds);

        if ($newlyGrantedRoleIds === []) {
            return [];
        }

        $roles = Role::query()
            ->whereIn('id', $newlyGrantedRoleIds)
            ->with('permissions:id,name')
            ->get();

        /** @var array<int, string> $newlyGrantedNames */
        $newlyGrantedNames = $roles
            ->flatMap(fn(Role $role) => $role->permissions->pluck('name'))
            ->unique()
            ->values()
            ->all();

        return $this->namesNotHeldBy($actor, $newlyGrantedNames);
    }

    /**
     * @param User $actor
     * @param array<int, string> $permissionNames
     *
     * @return array<int, string>
     */
    private function namesNotHeldBy(User $actor, array $permissionNames): array
    {
        if ($permissionNames === []) {
            return [];
        }

        /** @var array<int, string> $actorPermissionNames */
        $actorPermissionNames = $actor->getAllPermissions()->pluck('name')->all();

        return array_values(array_diff($permissionNames, $actorPermissionNames));
    }
}
