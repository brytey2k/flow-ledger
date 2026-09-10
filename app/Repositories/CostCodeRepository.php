<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Tenant\CostCode;
use Illuminate\Database\Eloquent\Collection;

class CostCodeRepository
{
    /**
     * @param array{q?: string, department_id?: int} $filters
     *
     * @return Collection<int, CostCode>
     */
    public function allWithDepartment(array $filters = []): Collection
    {
        $search = $filters['q'] ?? null;
        $departmentId = $filters['department_id'] ?? null;

        return CostCode::with('department')
            ->when($search, fn($query) => $query->where(
                fn($query) => $query->where('code', 'ilike', "%{$search}%")
                    ->orWhere('name', 'ilike', "%{$search}%"),
            ))
            ->when($departmentId, fn($query) => $query->where('department_id', $departmentId))
            ->orderBy('code')
            ->orderBy('id')
            ->get();
    }

    /** @return Collection<int, CostCode> */
    public function allOrderedByCode(): Collection
    {
        return CostCode::orderBy('code')->get();
    }

    /** @return Collection<int, CostCode> */
    public function forDepartment(int $departmentId): Collection
    {
        return CostCode::where('department_id', $departmentId)->orderBy('code')->get();
    }
}
