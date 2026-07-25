<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\PermissionsSyncRequest;
use App\Http\Requests\Tenant\UserStoreRequest;
use App\Http\Requests\Tenant\UserUpdateRequest;
use App\Models\Tenant\User;
use App\Repositories\BranchRepository;
use App\Repositories\PermissionRepository;
use App\Repositories\RoleRepository;
use App\Repositories\UserRepository;
use App\Services\PermissionEscalationGuard;
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
        private readonly BranchRepository $branchRepository,
        private readonly PermissionEscalationGuard $permissionEscalationGuard,
    ) {}

    /**
     * @param array<int, string> $deniedPermissionNames
     */
    private function permissionGrantDeniedMessage(array $deniedPermissionNames): string
    {
        $shownNames = array_slice($deniedPermissionNames, 0, 5);
        $remaining = count($deniedPermissionNames) - count($shownNames);

        $permissionsText = implode(', ', $shownNames);
        if ($remaining > 0) {
            $permissionsText .= ' ' . __('flash.and_more_count', ['count' => $remaining]);
        }

        return __('flash.users.permission_grant_denied', ['permissions' => $permissionsText]);
    }

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
        $branches = $this->branchRepository->allOrderedByName();

        return view('tenant.users.create', [
            'roles' => $roles,
            'branches' => $branches,
        ]);
    }

    public function store(UserStoreRequest $request): RedirectResponse
    {
        $dto = $request->toDto();

        /** @var User $actor */
        $actor = $request->user();

        // A brand-new account has no existing roles, so every requested role is
        // "newly granted" — this is what stops a CreateUser-only attacker from
        // spinning up an account pre-assigned to an admin role they couldn't
        // otherwise reach.
        $deniedPermissionNames = $this->permissionEscalationGuard->deniedPermissionNamesForRoles($actor, $dto->roles, []);
        if ($deniedPermissionNames !== []) {
            return redirect()
                ->route('users.create')
                ->withInput($request->except(['password', 'password_confirmation']))
                ->with('error', $this->permissionGrantDeniedMessage($deniedPermissionNames));
        }

        $this->service->create($dto, $actor);

        return redirect()
            ->route('users.index')
            ->with('success', __('flash.users.created'));
    }

    public function edit(User $user): View
    {
        $roles = $this->roleRepository->allOrderedByName();
        $branches = $this->branchRepository->allOrderedByName();
        $user->load('roles');

        return view('tenant.users.edit', [
            'user' => $user,
            'roles' => $roles,
            'branches' => $branches,
        ]);
    }

    public function show(User $user): View
    {
        $user->load('staffProfile');

        return view('tenant.users.show', [
            'user' => $user,
        ]);
    }

    public function update(UserUpdateRequest $request, User $user): RedirectResponse
    {
        $dto = $request->toDto();

        /** @var User $actor */
        $actor = $request->user();

        /** @var array<int, int> $currentRoleIds */
        $currentRoleIds = $user->roles()->pluck('roles.id')->map(function ($id) {
            /** @var int|string $rawId */
            $rawId = $id;

            return intval($rawId);
        })->all();

        $deniedPermissionNames = $this->permissionEscalationGuard->deniedPermissionNamesForRoles($actor, $dto->roles, $currentRoleIds);
        if ($deniedPermissionNames !== []) {
            return redirect()
                ->route('users.edit', $user)
                ->withInput($request->except(['password', 'password_confirmation']))
                ->with('error', $this->permissionGrantDeniedMessage($deniedPermissionNames));
        }

        $this->service->update($user, $dto, $actor);

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

        /** @var User $actor */
        $actor = $request->user();

        /** @var array<int, int> $currentPermissionIds */
        $currentPermissionIds = $user->permissions()->pluck('permissions.id')->map(function ($id) {
            /** @var int|string $rawId */
            $rawId = $id;

            return intval($rawId);
        })->all();

        $deniedPermissionNames = $this->permissionEscalationGuard->deniedPermissionNames($actor, $dto->permissionIds, $currentPermissionIds);
        if ($deniedPermissionNames !== []) {
            return redirect()
                ->route('users.permissions.edit', $user)
                ->with('error', $this->permissionGrantDeniedMessage($deniedPermissionNames));
        }

        DB::transaction(function () use ($user, $dto): void {
            if (! empty($dto->permissionIds)) {
                $permissions = $this->permissionRepository->findByIds(array_values($dto->permissionIds));
                $user->syncPermissions($permissions);
            } else {
                $user->syncPermissions([]);
            }
        });

        activity()
            ->performedOn($user)
            ->causedBy($request->user())
            ->event('user.permissions_synced')
            ->withProperties(['permissions' => $user->permissions()->pluck('name')->all()])
            ->log('User permissions updated');

        return redirect()
            ->route('users.edit', $user)
            ->with('success', __('flash.users.permissions_updated'));
    }
}
