<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\PermissionsSyncRequest;
use App\Http\Requests\Tenant\UserStoreRequest;
use App\Http\Requests\Tenant\UserUpdateRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UsersController extends Controller
{
    public function index(): View
    {
        $users = User::query()
            ->with('roles')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('tenant.users.index', [
            'users' => $users,
        ]);
    }

    public function create(): View
    {
        $roles = Role::orderBy('name')->get();

        return view('tenant.users.create', [
            'roles' => $roles,
        ]);
    }

    public function store(UserStoreRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        $roles = $validated['roles'] ?? null;
        if (is_array($roles)) {
            $user->syncRoles(array_map(fn(mixed $v) => is_numeric($v) ? (int) $v : 0, $roles));
        }

        return redirect()
            ->route('users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit(User $user): View
    {
        $roles = Role::orderBy('name')->get();
        $user->load('roles');

        return view('tenant.users.edit', [
            'user' => $user,
            'roles' => $roles,
        ]);
    }

    public function update(UserUpdateRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        $data = [
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
        ];

        if ($request->filled('password')) {
            $data['password'] = $validated['password'];
        }

        $user->update($data);

        $roles = $validated['roles'] ?? null;
        if (is_array($roles)) {
            $user->syncRoles(array_map(fn(mixed $v) => is_numeric($v) ? (int) $v : 0, $roles));
        } else {
            $user->syncRoles([]);
        }

        return redirect()
            ->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', 'User deleted successfully.');
    }

    public function editPermissions(User $user): View
    {
        $permissions = Permission::orderBy('name')->get();
        $user->load('permissions');

        return view('tenant.users.permissions', [
            'user' => $user,
            'permissions' => $permissions,
        ]);
    }

    public function updatePermissions(PermissionsSyncRequest $request, User $user): RedirectResponse
    {
        if ($request->has('permissions')) {
            /** @var array<string>|string $permissions */
            $permissions = $request->validated()['permissions'];
            $user->syncPermissions($permissions);
        } else {
            $user->syncPermissions([]);
        }

        return redirect()
            ->route('users.edit', $user)
            ->with('success', 'User permissions updated successfully.');
    }
}
