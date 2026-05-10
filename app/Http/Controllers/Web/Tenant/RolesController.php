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
        Role::create([
            'name' => $dto->name,
            'guard_name' => $dto->guardName,
        ]);

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role created successfully.');
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

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->users()->count() > 0) {
            return redirect()
                ->route('roles.index')
                ->with('error', 'Cannot delete role that has associated users.');
        }

        $role->delete();

        return redirect()
            ->route('roles.index')
            ->with('success', 'Role deleted successfully.');
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

        return redirect()
            ->route('roles.edit', $role)
            ->with('success', 'Role permissions updated successfully.');
    }
}
