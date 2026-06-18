<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Tenant\User;
use Illuminate\Database\Eloquent\Collection;

class UserRepository
{
    /** @return Collection<int, User> */
    public function allWithRoles(): Collection
    {
        return User::with('roles')->orderBy('created_at', 'desc')->get();
    }

    public function findByOidcSub(string $oidcSub): User|null
    {
        return User::query()->where('oidc_sub', $oidcSub)->first();
    }
}
