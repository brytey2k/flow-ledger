<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Tenant\AccountCode;
use Illuminate\Database\Eloquent\Collection;

class AccountCodeRepository
{
    /** @return Collection<int, AccountCode> */
    public function allWithDepartment(): Collection
    {
        return AccountCode::with('department')->orderBy('code')->get();
    }

    /** @return Collection<int, AccountCode> */
    public function allOrderedByCode(): Collection
    {
        return AccountCode::orderBy('code')->get();
    }

    /** @return Collection<int, AccountCode> */
    public function forDepartment(int $departmentId): Collection
    {
        return AccountCode::where('department_id', $departmentId)->orderBy('code')->get();
    }
}
