<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;

class RoleRepository
{
    /** @return Collection<int, Role> */
    public function allOrderedByName(): Collection
    {
        return Role::orderBy('name')->get();
    }

    /** @return Collection<int, Role> */
    public function allWithCounts(): Collection
    {
        return Role::withCount(['users', 'permissions'])->orderBy('name')->get();
    }
}
