<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class UserService
{
    /** @param array<string, mixed> $data */
    public function create(array $data, Model|null $actor = null): User
    {
        return DB::transaction(function () use ($data, $actor): User {
            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);

            $roles = $data['roles'] ?? [];
            if (is_array($roles) && ! empty($roles)) {
                $user->syncRoles($roles);
            }

            activity()
                ->performedOn($user)
                ->causedBy($actor)
                ->event('user.created')
                ->withProperties(['email' => $user->email])
                ->log('User account created');

            return $user;
        });
    }

    /** @param array<string, mixed> $data */
    public function update(User $user, array $data, Model|null $actor = null): void
    {
        DB::transaction(function () use ($user, $data, $actor): void {
            $attributes = [
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
            ];

            $password = $data['password'] ?? null;
            if (! empty($password)) {
                $attributes['password'] = $password;
            }

            $user->update($attributes);

            $roles = $data['roles'] ?? [];
            $user->syncRoles(is_array($roles) ? $roles : []);

            activity()
                ->performedOn($user)
                ->causedBy($actor)
                ->event('user.updated')
                ->withProperties(['email' => $user->email])
                ->log('User account updated');
        });
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
