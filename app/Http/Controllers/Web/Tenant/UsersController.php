<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\PermissionsSyncRequest;
use App\Http\Requests\Tenant\UserStoreRequest;
use App\Http\Requests\Tenant\UserUpdateRequest;
use App\Models\Tenant\User;
use App\Repositories\PermissionRepository;
use App\Repositories\RoleRepository;
use App\Repositories\UserRepository;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UsersController extends Controller
{
    public function __construct(
        private readonly UserService $service,
        private readonly UserRepository $repository,
        private readonly RoleRepository $roleRepository,
        private readonly PermissionRepository $permissionRepository,
    ) {}

    public function index(): View
    {
        $users = $this->repository->allWithRoles();

        return view('tenant.users.index', [
            'users' => $users,
        ]);
    }

    public function create(): View
    {
        $roles = $this->roleRepository->allOrderedByName();

        return view('tenant.users.create', [
            'roles' => $roles,
        ]);
    }

    public function store(UserStoreRequest $request): RedirectResponse
    {
        $this->service->create($request->toDto(), $request->user());

        return redirect()
            ->route('users.index')
            ->with('success', __('flash.users.created'));
    }

    public function edit(User $user): View
    {
        $roles = $this->roleRepository->allOrderedByName();
        $user->load('roles');

        return view('tenant.users.edit', [
            'user' => $user,
            'roles' => $roles,
        ]);
    }

    public function update(UserUpdateRequest $request, User $user): RedirectResponse
    {
        $this->service->update($user, $request->toDto(), $request->user());

        return redirect()
            ->route('users.index')
            ->with('success', __('flash.users.updated'));
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->service->delete($user, auth()->user());

        return redirect()
            ->route('users.index')
            ->with('success', __('flash.users.deleted'));
    }

    public function editPermissions(User $user): View
    {
        $permissions = $this->permissionRepository->allOrderedByName();
        $user->load('permissions');

        return view('tenant.users.permissions', [
            'user' => $user,
            'permissions' => $permissions,
        ]);
    }

    public function updatePermissions(PermissionsSyncRequest $request, User $user): RedirectResponse
    {
        $dto = $request->toDto();

        DB::transaction(function () use ($user, $dto): void {
            if (! empty($dto->permissionIds)) {
                $permissions = $this->permissionRepository->findByIds(array_values($dto->permissionIds));
                $user->syncPermissions($permissions);
            } else {
                $user->syncPermissions([]);
            }
        });

        return redirect()
            ->route('users.edit', $user)
            ->with('success', __('flash.users.permissions_updated'));
    }
}
