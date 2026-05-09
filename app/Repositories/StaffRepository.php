<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Tenant\Staff;
use App\Models\Tenant\User;
use Illuminate\Database\Eloquent\Collection;

class StaffRepository
{
    /** @return Collection<int, Staff> */
    public function allWithRelations(): Collection
    {
        return Staff::with(['department', 'position'])->orderBy('last_name')->orderBy('first_name')->get();
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
