<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Tenant\StaffDto;
use App\Models\Tenant\Staff;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StaffService
{
    public function create(StaffDto $dto, Model|null $actor = null): Staff
    {
        return DB::transaction(function () use ($dto, $actor): Staff {
            $staff = Staff::create([
                'first_name' => $dto->firstName,
                'last_name' => $dto->lastName,
                'email' => $dto->email,
                'phone' => $dto->phone,
                'department_id' => $dto->departmentId,
                'position_id' => $dto->positionId,
                'user_id' => $dto->userId,
                'branch_id' => $dto->branchId,
            ]);

            activity()
                ->performedOn($staff)
                ->causedBy($actor)
                ->event('staff.created')
                ->withProperties(['name' => $staff->full_name, 'email' => $staff->email])
                ->log('Staff member created');

            return $staff;
        });
    }

    public function update(Staff $staff, StaffDto $dto, Model|null $actor = null): void
    {
        DB::transaction(function () use ($staff, $dto, $actor): void {
            $staff->update([
                'first_name' => $dto->firstName,
                'last_name' => $dto->lastName,
                'email' => $dto->email,
                'phone' => $dto->phone,
                'department_id' => $dto->departmentId,
                'position_id' => $dto->positionId,
                'user_id' => $dto->userId,
                'branch_id' => $dto->branchId,
            ]);

            activity()
                ->performedOn($staff)
                ->causedBy($actor)
                ->event('staff.updated')
                ->withProperties(['name' => $staff->full_name, 'email' => $staff->email])
                ->log('Staff member updated');
        });
    }

    public function delete(Staff $staff, Model|null $actor = null): void
    {
        DB::transaction(function () use ($staff, $actor): void {
            activity()
                ->performedOn($staff)
                ->causedBy($actor)
                ->event('staff.deleted')
                ->withProperties(['name' => $staff->full_name, 'email' => $staff->email])
                ->log('Staff member deleted');

            $staff->delete();
        });
    }
}
