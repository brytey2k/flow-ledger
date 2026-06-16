<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Tenant\CreateUserDto;
use App\DTOs\Tenant\UpdateUserDto;
use App\Models\Tenant\User;
use App\Notifications\WelcomeNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class UserService
{
    public function create(CreateUserDto $dto, Model|null $actor = null): User
    {
        $user = DB::transaction(function () use ($dto, $actor): User {
            $user = User::create([
                'first_name' => $dto->firstName,
                'last_name' => $dto->lastName,
                'email' => $dto->email,
                'password' => $dto->password,
                'must_change_password' => true,
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
            $user->notify(new WelcomeNotification($dto->password, route('login')));
        }

        return $user;
    }

    public function update(User $user, UpdateUserDto $dto, Model|null $actor = null): void
    {
        DB::transaction(function () use ($user, $dto, $actor): void {
            $attributes = [
                'first_name' => $dto->firstName,
                'last_name' => $dto->lastName,
                'email' => $dto->email,
            ];

            if ($dto->password !== null) {
                $attributes['password'] = $dto->password;
            }

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
}
