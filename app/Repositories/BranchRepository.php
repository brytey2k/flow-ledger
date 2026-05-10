<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Tenant\Branch;
use Illuminate\Database\Eloquent\Collection;

class BranchRepository
{
    /** @return Collection<int, Branch> */
    public function allWithRelations(): Collection
    {
        return Branch::with(['level', 'parent'])->orderBy('position')->get();
    }

    /** @return Collection<int, Branch> */
    public function allWithCashbook(): Collection
    {
        return Branch::with(['level', 'currency', 'cashbook.currency'])->orderBy('position')->get();
    }

    /** @return Collection<int, Branch> */
    public function allOrderedByName(): Collection
    {
        return Branch::orderBy('name')->get();
    }

    /** @return Collection<int, Branch> */
    public function allExcept(int $excludeId): Collection
    {
        return Branch::where('id', '!=', $excludeId)->orderBy('name')->get();
    }

    public function findOrFail(int $id): Branch
    {
        /* @var Branch */
        return Branch::findOrFail($id);
    }
}
