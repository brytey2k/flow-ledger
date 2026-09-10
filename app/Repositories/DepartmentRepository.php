<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Tenant\Department;
use Illuminate\Database\Eloquent\Collection;

class DepartmentRepository
{
    /**
     * @param array{q?: string} $filters
     *
     * @return Collection<int, Department>
     */
    public function allOrderedByName(array $filters = []): Collection
    {
        $search = $filters['q'] ?? null;

        return Department::query()
            ->when($search, fn($query) => $query->where('name', 'ilike', "%{$search}%"))
            ->orderBy('name')
            ->orderBy('id')
            ->get();
    }
}
