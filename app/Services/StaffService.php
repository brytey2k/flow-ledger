<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant\Staff;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StaffService
{
    /** @param array<string, mixed> $data */
    public function create(array $data, Model|null $actor = null): Staff
    {
        return DB::transaction(function () use ($data, $actor): Staff {
            $staff = Staff::create($data);

            activity()
                ->performedOn($staff)
                ->causedBy($actor)
                ->event('staff.created')
                ->withProperties(['name' => $staff->full_name, 'email' => $staff->email])
                ->log('Staff member created');

            return $staff;
        });
    }

    /** @param array<string, mixed> $data */
    public function update(Staff $staff, array $data, Model|null $actor = null): void
    {
        DB::transaction(function () use ($staff, $data, $actor): void {
            $staff->update($data);

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
