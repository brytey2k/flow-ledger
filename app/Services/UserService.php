<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Tenant\CreateUserDto;
use App\DTOs\Tenant\UpdateUserDto;
use App\Enums\Tenant\UserStatus;
use App\Models\Tenant\User;
use App\Notifications\WelcomeNotification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class UserService
{
    public function __construct(private readonly PermissionEscalationGuard $escalationGuard = new PermissionEscalationGuard()) {}

    /** @throws AuthorizationException when the actor would grant roles carrying permissions they do not hold */
    public function create(CreateUserDto $dto, Model|null $actor = null): User
    {
        if ($actor instanceof User && $this->escalationGuard->deniedPermissionNamesForRoles($actor, $dto->roles, []) !== []) {
            throw new AuthorizationException(__('You cannot assign roles that carry permissions you do not hold.'));
        }

        $user = DB::transaction(function () use ($dto, $actor): User {
            $user = User::create([
                'first_name' => $dto->firstName,
                'last_name' => $dto->lastName,
                'email' => $dto->email,
                'password' => bcrypt(Str::random(32)),
                'status' => UserStatus::Invited,
                'invited_at' => now(),
                'invited_by' => $actor?->getKey(),
                'branch_id' => $dto->branchId,
                'operational_branch_id' => $dto->operationalBranchId,
            ]);

            if (! empty($dto->roles)) {
                $user->syncRoles($dto->roles);
            }

            activity()
                ->performedOn($user)
                ->causedBy($actor)
                ->event('user.created')
                ->withProperties(['email' => $user->email])
                ->log('User account created');

            return $user;
        });

        if (! $user->is_oidc_user) {
            $token = Password::broker()->createToken($user);
            $user->notify(new WelcomeNotification($token));
        }

        return $user;
    }

    /** @throws AuthorizationException when the target holds permissions the actor lacks */
    public function update(User $user, UpdateUserDto $dto, Model|null $actor = null): void
    {
        if ($actor instanceof User && ! $this->escalationGuard->actorMayManage($actor, $user) && $this->changesIdentityOrRoles($user, $dto)) {
            throw new AuthorizationException(__('You cannot change the email or roles of a user who holds permissions you do not hold.'));
        }

        DB::transaction(function () use ($user, $dto, $actor): void {
            $attributes = [
                'first_name' => $dto->firstName,
                'last_name' => $dto->lastName,
                'email' => $dto->email,
            ];

            $user->update($attributes);
            $user->syncRoles($dto->roles);

            activity()
                ->performedOn($user)
                ->causedBy($actor)
                ->event('user.updated')
                ->withProperties(['email' => $user->email])
                ->log('User account updated');
        });
    }

    public function syncBranch(int $userId, int|null $newBranchId, int|null $oldBranchId): void
    {
        $user = User::find($userId);

        if ($user === null) {
            return;
        }

        $updates = ['branch_id' => $newBranchId];

        if ($user->operational_branch_id === $oldBranchId) {
            $updates['operational_branch_id'] = $newBranchId;
        }

        $user->update($updates);
    }

    public function delete(User $user, Model|null $actor = null): void
    {
        DB::transaction(function () use ($user, $actor): void {
            activity()
                ->performedOn($user)
                ->causedBy($actor)
                ->event('user.deleted')
                ->withProperties(['email' => $user->email, 'name' => $user->name])
                ->log('User account deleted');

            $user->delete();
        });
    }

    /** Names alone may be edited freely; the email decides where password resets go, and roles decide access. */
    private function changesIdentityOrRoles(User $user, UpdateUserDto $dto): bool
    {
        /** @var list<int> $currentRoleIds */
        $currentRoleIds = $user->roles()->pluck('roles.id')->map(fn($id): int => is_numeric($id) ? (int) $id : 0)->sort()->values()->all();
        $requestedRoleIds = collect($dto->roles)->map(fn($id): int => (int) $id)->sort()->values()->all();

        return $user->email !== $dto->email || $currentRoleIds !== $requestedRoleIds;
    }
}
