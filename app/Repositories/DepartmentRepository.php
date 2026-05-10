<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Tenant\Department;
use Illuminate\Database\Eloquent\Collection;

class DepartmentRepository
{
    /** @return Collection<int, Department> */
    public function allOrderedByName(): Collection
    {
        return Department::orderBy('name')->get();
    }
}
