<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Tenant\CostCode;
use Illuminate\Database\Eloquent\Collection;

class CostCodeRepository
{
    /** @return Collection<int, CostCode> */
    public function allWithDepartment(): Collection
    {
        return CostCode::with('department')->orderBy('code')->get();
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
