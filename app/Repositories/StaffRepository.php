<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Tenant\Staff;
use App\Models\Tenant\User;
use Illuminate\Database\Eloquent\Collection;

class StaffRepository
{
    /**
     * @param array<int, int> $branchIds
     * @param array{q?: string, branch_id?: int, department_id?: int, position_id?: int} $filters
     *
     * @return Collection<int, Staff>
     */
    public function allWithRelations(array $branchIds, array $filters = []): Collection
    {
        $search = $filters['q'] ?? null;
        $branchId = $filters['branch_id'] ?? null;
        $departmentId = $filters['department_id'] ?? null;
        $positionId = $filters['position_id'] ?? null;

        return Staff::with(['department', 'position'])
            ->whereIn('branch_id', $branchIds)
            ->when($search, fn($query) => $query->where(
                fn($query) => $query->where('first_name', 'ilike', "%{$search}%")
                    ->orWhere('last_name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%")
                    ->orWhere('phone', 'ilike', "%{$search}%"),
            ))
            ->when($branchId, fn($query) => $query->where('branch_id', $branchId))
            ->when($departmentId, fn($query) => $query->where('department_id', $departmentId))
            ->when($positionId, fn($query) => $query->where('position_id', $positionId))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->orderBy('id')
            ->get();
    }

    /** @return Collection<int, User> */
    public function unlinkedUsers(): Collection
    {
        return User::whereDoesntHave('staffProfile')->orderBy('first_name')->get();
    }

    /** @return Collection<int, User> */
    public function unlinkedUsersOrCurrent(Staff $staff): Collection
    {
        return User::where(function ($q) use ($staff): void {
            $q->whereDoesntHave('staffProfile')->orWhere('id', $staff->user_id);
        })->orderBy('first_name')->get();
    }
}
