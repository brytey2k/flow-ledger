<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Collection;

class PermissionRepository
{
    /** @return Collection<int, Permission> */
    public function allOrderedByName(): Collection
    {
        return Permission::orderBy('name')->get();
    }

    /**
     * @param list<int> $ids
     *
     * @return Collection<int, Permission>
     */
    public function findByIds(array $ids): Collection
    {
        return Permission::whereIn('id', $ids)->get();
    }
}
