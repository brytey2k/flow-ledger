<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\PermissionsSyncRequest;
use App\Http\Requests\Tenant\RoleStoreRequest;
use App\Http\Requests\Tenant\RoleUpdateRequest;
use App\Models\Role;
use App\Repositories\PermissionRepository;
use App\Repositories\RoleRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RolesController extends Controller
{
    public function __construct(
        private readonly RoleRepository $repository,
        private readonly PermissionRepository $permissionRepository,
    ) {}

    public function index(): View
    {
        $roles = $this->repository->allWithCounts();

        return view('tenant.roles.index', [
            'roles' => $roles,
        ]);
    }

    public function create(): View
    {
        return view('tenant.roles.create');
    }

    public function store(RoleStoreRequest $request): RedirectResponse
    {
        $dto = $request->toDto();
        /** @var Role $role */
        $role = Role::create([
            'name' => $dto->name,
            'guard_name' => $dto->guardName,
        ]);

        activity()
            ->performedOn($role)
            ->causedBy($request->user())
            ->event('role.created')
            ->withProperties(['name' => $role->name])
            ->log('Role created');

        return redirect()
            ->route('roles.index')
            ->with('success', __('flash.roles.created'));
    }

    public function edit(Role $role): View
    {
        return view('tenant.roles.edit', [
            'role' => $role,
        ]);
    }

    public function update(RoleUpdateRequest $request, Role $role): RedirectResponse
    {
        $dto = $request->toDto();
        $role->update([
            'name' => $dto->name,
        ]);

        activity()
            ->performedOn($role)
            ->causedBy($request->user())
            ->event('role.updated')
            ->withProperties(['name' => $role->name])
            ->log('Role updated');

        return redirect()
            ->route('roles.index')
            ->with('success', __('flash.roles.updated'));
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->users()->count() > 0) {
            return redirect()
                ->route('roles.index')
                ->with('error', __('flash.roles.delete_blocked_users'));
        }

        activity()
            ->performedOn($role)
            ->causedBy(auth()->user())
            ->event('role.deleted')
            ->withProperties(['name' => $role->name])
            ->log('Role deleted');

        $role->delete();

        return redirect()
            ->route('roles.index')
            ->with('success', __('flash.roles.deleted'));
    }

    public function editPermissions(Role $role): View
    {
        $permissions = $this->permissionRepository->allOrderedByName();
        $role->load('permissions');

        return view('tenant.roles.permissions', [
            'role' => $role,
            'permissions' => $permissions,
        ]);
    }

    public function updatePermissions(PermissionsSyncRequest $request, Role $role): RedirectResponse
    {
        $dto = $request->toDto();

        DB::transaction(function () use ($dto, $role): void {
            if (! empty($dto->permissionIds)) {
                $permissions = $this->permissionRepository->findByIds(array_values($dto->permissionIds));
                $role->syncPermissions($permissions);
            } else {
                $role->syncPermissions([]);
            }
        });

        activity()
            ->performedOn($role)
            ->causedBy($request->user())
            ->event('role.permissions_synced')
            ->withProperties(['permissions' => $role->permissions()->pluck('name')->all()])
            ->log('Role permissions updated');

        return redirect()
            ->route('roles.edit', $role)
            ->with('success', __('flash.roles.permissions_updated'));
    }
}
