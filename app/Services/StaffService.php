<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Tenant\StaffDto;
use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\Staff;
use App\Models\Tenant\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StaffService
{
    public function __construct(
        private readonly UserService $userService,
        private readonly BranchScopeService $branchScope,
    ) {}

    /** @throws AuthorizationException */
    public function create(StaffDto $dto, Model|null $actor = null): Staff
    {
        $this->authorizeBranch($dto, $actor);
        $this->authorizeLoginCreation($dto, $actor);

        return DB::transaction(function () use ($dto, $actor): Staff {
            $userId = $dto->userId;

            if ($dto->newUser !== null) {
                $user = $this->userService->create($dto->newUser, $actor);
                $userId = $user->id;
            }

            $staff = Staff::create([
                'first_name' => $dto->firstName,
                'last_name' => $dto->lastName,
                'email' => $dto->email,
                'phone' => $dto->phone,
                'department_id' => $dto->departmentId,
                'position_id' => $dto->positionId,
                'user_id' => $userId,
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

    /** @throws AuthorizationException */
    public function update(Staff $staff, StaffDto $dto, Model|null $actor = null): void
    {
        if ($staff->branch_id !== $dto->branchId) {
            $this->authorizeBranch($dto, $actor);

            if ($actor instanceof User && $staff->user_id === $actor->id) {
                throw new AuthorizationException(__('You cannot move your own staff record to another branch.'));
            }
        }

        if ($staff->user_id === null) {
            $this->authorizeLoginCreation($dto, $actor);
        }

        DB::transaction(function () use ($staff, $dto, $actor): void {
            $userId = $staff->user_id;
            $oldBranchId = $staff->branch_id;

            if ($userId === null) {
                if ($dto->newUser !== null) {
                    $user = $this->userService->create($dto->newUser, $actor);
                    $userId = $user->id;
                } elseif ($dto->userId !== null) {
                    $userId = $dto->userId;
                }
            }

            $staff->update([
                'first_name' => $dto->firstName,
                'last_name' => $dto->lastName,
                'email' => $dto->email,
                'phone' => $dto->phone,
                'department_id' => $dto->departmentId,
                'position_id' => $dto->positionId,
                'user_id' => $userId,
                'branch_id' => $dto->branchId,
            ]);

            if ($userId !== null && $oldBranchId !== $dto->branchId) {
                $this->userService->syncBranch($userId, $dto->branchId, $oldBranchId);
            }

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

    /** The destination branch must be one the acting user may work in. */
    private function authorizeBranch(StaffDto $dto, Model|null $actor): void
    {
        if ($actor instanceof User && $dto->branchId !== null && ! in_array($dto->branchId, $this->branchScope->allowedBranchIds($actor), true)) {
            throw new AuthorizationException(__('You cannot place staff in a branch outside your access.'));
        }
    }

    /** Creating a login from the staff form needs the same permission as creating a user. */
    private function authorizeLoginCreation(StaffDto $dto, Model|null $actor): void
    {
        if ($dto->newUser !== null && $actor instanceof User && ! $actor->can(PermissionKey::CreateUser->value)) {
            throw new AuthorizationException(__('You do not have permission to create user accounts.'));
        }
    }
}
