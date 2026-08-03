<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Enums\Tenant\UserStatus;
use App\Features\DelegateIdentityToIdp;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\PermissionsSyncRequest;
use App\Http\Requests\Tenant\UserInviteRequest;
use App\Http\Requests\Tenant\UserStoreRequest;
use App\Http\Requests\Tenant\UserUpdateRequest;
use App\Jobs\InviteUserToIamJob;
use App\Models\Tenant;
use App\Models\Tenant\User;
use App\Repositories\BranchRepository;
use App\Repositories\PermissionRepository;
use App\Repositories\RoleRepository;
use App\Repositories\UserRepository;
use App\Services\PermissionEscalationGuard;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Laravel\Pennant\Feature;

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

    private function identityDelegated(): bool
    {
        return Feature::for(tenant())->active(DelegateIdentityToIdp::class);
    }

    private function currentIdpTenantId(): string|null
    {
        /** @var Tenant|null $currentTenant */
        $currentTenant = tenant();

        return $currentTenant?->idp_tenant_id;
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

        if ($this->identityDelegated()) {
            return view('tenant.users.invite', [
                'roles' => $roles,
                'branches' => $branches,
            ]);
        }

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

    /**
     * Invite a new user when identity is delegated to the IDP.
     *
     * @param UserInviteRequest $request
     */
    public function storeInvite(UserInviteRequest $request): RedirectResponse
    {
        abort_unless($this->identityDelegated(), 403);

        /** @var User $authUser */
        $authUser = $request->user();

        $validated = $request->validated();

        $roleIds = [];
        if (isset($validated['roles']) && is_array($validated['roles'])) {
            /** @var array<int, int|string> $roles */
            $roles = $validated['roles'];
            $roleIds = array_map('intval', $roles);

            $deniedPermissionNames = $this->permissionEscalationGuard->deniedPermissionNamesForRoles($authUser, $roleIds, []);
            if ($deniedPermissionNames !== []) {
                return redirect()
                    ->route('users.create')
                    ->withInput($request->all())
                    ->with('error', $this->permissionGrantDeniedMessage($deniedPermissionNames));
            }
        }

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'password' => bcrypt(Str::random(32)),
            'must_change_password' => false,
            'is_oidc_user' => true,
            'status' => UserStatus::Invited,
            'invited_at' => now(),
            'invited_by' => $authUser->id,
            'branch_id' => $validated['branch_id'],
            'operational_branch_id' => $validated['operational_branch_id'] ?? $validated['branch_id'],
        ]);

        if ($roleIds !== []) {
            $user->syncRoles($roleIds);
        }

        InviteUserToIamJob::dispatch($user->id, $this->currentIdpTenantId());

        return redirect()
            ->route('users.index')
            ->with('success', __('flash.users.invited'));
    }

    /**
     * Resend an invitation to a user who has not yet accepted it.
     *
     * @param User $user
     */
    public function resendInvite(User $user): RedirectResponse
    {
        abort_unless($this->identityDelegated(), 403);
        abort_unless($user->status === UserStatus::Invited, 404);

        InviteUserToIamJob::dispatch($user->id, $this->currentIdpTenantId());

        return redirect()
            ->route('users.index')
            ->with('success', __('flash.users.invite_resent'));
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
