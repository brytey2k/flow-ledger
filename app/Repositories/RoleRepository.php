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
        return Role::orderBy('name')->orderBy('id')->get();
    }

    /**
     * @param array{q?: string} $filters
     *
     * @return Collection<int, Role>
     */
    public function allWithCounts(array $filters = []): Collection
    {
        $search = $filters['q'] ?? null;

        return Role::withCount(['users', 'permissions'])
            ->when($search, fn($query) => $query->where('name', 'ilike', "%{$search}%"))
            ->orderBy('name')
            ->orderBy('id')
            ->get();
    }
}
