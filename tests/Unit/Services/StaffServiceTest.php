<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTOs\Tenant\CreateUserDto;
use App\DTOs\Tenant\StaffDto;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Department;
use App\Models\Tenant\Position;
use App\Models\Tenant\Staff;
use App\Models\Tenant\User;
use App\Services\StaffService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TenantAppTestCase;

class StaffServiceTest extends TenantAppTestCase
{
    private StaffService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(StaffService::class);
    }

    // ── create ────────────────────────────────────────────────────────────────

    public function test_create_returns_staff_instance(): void
    {
        Notification::fake();
        $dto = $this->makeStaffDto();

        $staff = $this->service->create($dto, $this->user);

        $this->assertInstanceOf(Staff::class, $staff);
    }

    public function test_create_persists_staff_to_database(): void
    {
        Notification::fake();
        $dto = $this->makeStaffDto();

        $staff = $this->service->create($dto, $this->user);

        $this->assertNotNull(Staff::find($staff->id));
    }

    public function test_create_without_user_sets_user_id_to_null(): void
    {
        Notification::fake();
        $dto = $this->makeStaffDto(userId: null, newUser: null);

        $staff = $this->service->create($dto, $this->user);

        $this->assertNull($staff->user_id);
    }

    public function test_create_stores_correct_staff_fields(): void
    {
        Notification::fake();
        $department = Department::factory()->create();
        $position = Position::factory()->create();
        $dto = new StaffDto(
            firstName: 'Alice',
            lastName: 'Smith',
            email: 'alice@example.com',
            phone: '0200000001',
            departmentId: $department->id,
            positionId: $position->id,
            userId: null,
            branchId: $this->branch->id,
        );

        $staff = $this->service->create($dto, $this->user);

        $this->assertSame('Alice', $staff->first_name);
        $this->assertSame('Smith', $staff->last_name);
        $this->assertSame('alice@example.com', $staff->email);
        $this->assertSame('0200000001', $staff->phone);
        $this->assertSame($department->id, $staff->department_id);
        $this->assertSame($position->id, $staff->position_id);
        $this->assertSame($this->branch->id, $staff->branch_id);
    }

    public function test_create_with_new_user_creates_linked_user(): void
    {
        Notification::fake();
        $newUserDto = $this->makeCreateUserDto();
        $dto = $this->makeStaffDto(newUser: $newUserDto);

        $staff = $this->service->create($dto, $this->user);

        $this->assertNotNull($staff->user_id);
        $this->assertNotNull(User::find($staff->user_id));
    }

    public function test_create_with_new_user_links_correct_user_email(): void
    {
        Notification::fake();
        $email = Str::uuid() . '@staff.example.com';
        $newUserDto = $this->makeCreateUserDto(email: $email);
        $dto = $this->makeStaffDto(newUser: $newUserDto);

        $staff = $this->service->create($dto, $this->user);

        $linkedUser = User::find($staff->user_id);
        $this->assertSame($email, $linkedUser->email);
    }

    public function test_create_with_existing_user_id_links_that_user(): void
    {
        $existingUser = User::factory()->create([
            'branch_id' => $this->branch->id,
            'operational_branch_id' => $this->branch->id,
        ]);
        $dto = $this->makeStaffDto(userId: $existingUser->id, newUser: null);

        $staff = $this->service->create($dto, $this->user);

        $this->assertSame($existingUser->id, $staff->user_id);
    }

    // ── update ────────────────────────────────────────────────────────────────

    public function test_update_changes_staff_first_name(): void
    {
        Notification::fake();
        $staff = Staff::factory()->withBranch($this->branch)->create();
        $department = Department::factory()->create();
        $position = Position::factory()->create();
        $dto = new StaffDto(
            firstName: 'UpdatedFirst',
            lastName: $staff->last_name,
            email: $staff->email,
            phone: $staff->phone,
            departmentId: $department->id,
            positionId: $position->id,
            userId: null,
            branchId: $this->branch->id,
        );

        $this->service->update($staff, $dto, $this->user);

        $staff->refresh();
        $this->assertSame('UpdatedFirst', $staff->first_name);
    }

    public function test_update_changes_department_and_position(): void
    {
        Notification::fake();
        $staff = Staff::factory()->withBranch($this->branch)->create();
        $newDepartment = Department::factory()->create();
        $newPosition = Position::factory()->create();
        $dto = new StaffDto(
            firstName: $staff->first_name,
            lastName: $staff->last_name,
            email: $staff->email,
            phone: $staff->phone,
            departmentId: $newDepartment->id,
            positionId: $newPosition->id,
            userId: null,
            branchId: $this->branch->id,
        );

        $this->service->update($staff, $dto, $this->user);

        $staff->refresh();
        $this->assertSame($newDepartment->id, $staff->department_id);
        $this->assertSame($newPosition->id, $staff->position_id);
    }

    public function test_update_can_link_existing_user_to_staff_with_no_user(): void
    {
        $staff = Staff::factory()->withBranch($this->branch)->create(['user_id' => null]);
        $existingUser = User::factory()->create([
            'branch_id' => $this->branch->id,
            'operational_branch_id' => $this->branch->id,
        ]);
        $dto = $this->makeStaffDtoFromStaff($staff, userId: $existingUser->id);

        $this->service->update($staff, $dto, $this->user);

        $staff->refresh();
        $this->assertSame($existingUser->id, $staff->user_id);
    }

    public function test_update_creates_and_links_new_user_when_staff_has_none(): void
    {
        Notification::fake();
        $staff = Staff::factory()->withBranch($this->branch)->create(['user_id' => null]);
        $newUserDto = $this->makeCreateUserDto();
        $dto = $this->makeStaffDtoFromStaff($staff, newUser: $newUserDto);

        $this->service->update($staff, $dto, $this->user);

        $staff->refresh();
        $this->assertNotNull($staff->user_id);
        $this->assertNotNull(User::find($staff->user_id));
    }

    public function test_update_syncs_user_branch_when_branch_changes(): void
    {
        $linkedUser = User::factory()->create([
            'branch_id' => $this->branch->id,
            'operational_branch_id' => $this->branch->id,
        ]);
        $staff = Staff::factory()
            ->withUser($linkedUser)
            ->withBranch($this->branch)
            ->create();
        $newBranch = Branch::factory()->create(['level_id' => $this->level->id]);
        $dto = $this->makeStaffDtoFromStaff($staff, branchId: $newBranch->id, userId: $linkedUser->id);

        $this->service->update($staff, $dto, $this->user);

        $linkedUser->refresh();
        $this->assertSame($newBranch->id, $linkedUser->branch_id);
    }

    public function test_update_does_not_sync_branch_when_branch_unchanged(): void
    {
        $linkedUser = User::factory()->create([
            'branch_id' => $this->branch->id,
            'operational_branch_id' => $this->branch->id,
        ]);
        $staff = Staff::factory()
            ->withUser($linkedUser)
            ->withBranch($this->branch)
            ->create();
        $dto = $this->makeStaffDtoFromStaff($staff, branchId: $this->branch->id, userId: $linkedUser->id);

        $this->service->update($staff, $dto, $this->user);

        $linkedUser->refresh();
        $this->assertSame($this->branch->id, $linkedUser->branch_id);
    }

    public function test_update_does_not_override_existing_user_link(): void
    {
        $linkedUser = User::factory()->create([
            'branch_id' => $this->branch->id,
            'operational_branch_id' => $this->branch->id,
        ]);
        $staff = Staff::factory()
            ->withUser($linkedUser)
            ->withBranch($this->branch)
            ->create();
        $anotherUser = User::factory()->create([
            'branch_id' => $this->branch->id,
            'operational_branch_id' => $this->branch->id,
        ]);
        // Passing a different userId should have no effect since staff already has a user
        $dto = $this->makeStaffDtoFromStaff($staff, userId: $anotherUser->id);

        $this->service->update($staff, $dto, $this->user);

        $staff->refresh();
        $this->assertSame($linkedUser->id, $staff->user_id);
    }

    // ── delete ────────────────────────────────────────────────────────────────

    public function test_delete_soft_deletes_staff_record(): void
    {
        $staff = Staff::factory()->withBranch($this->branch)->create();
        $staffId = $staff->id;

        $this->service->delete($staff, $this->user);

        $this->assertNull(Staff::find($staffId));
        $this->assertNotNull(Staff::withTrashed()->find($staffId));
    }

    public function test_delete_sets_deleted_at_timestamp(): void
    {
        $staff = Staff::factory()->withBranch($this->branch)->create();
        $staffId = $staff->id;

        $this->service->delete($staff, $this->user);

        $deleted = Staff::withTrashed()->find($staffId);
        $this->assertNotNull($deleted->deleted_at);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeStaffDto(
        int|null $userId = null,
        CreateUserDto|null $newUser = null,
        int|null $branchId = null,
    ): StaffDto {
        $department = Department::factory()->create();
        $position = Position::factory()->create();

        return new StaffDto(
            firstName: fake()->firstName(),
            lastName: fake()->lastName(),
            email: Str::uuid() . '@example.com',
            phone: fake()->phoneNumber(),
            departmentId: $department->id,
            positionId: $position->id,
            userId: $userId,
            branchId: $branchId ?? $this->branch->id,
            newUser: $newUser,
        );
    }

    private function makeStaffDtoFromStaff(
        Staff $staff,
        int|null $userId = null,
        int|null $branchId = null,
        CreateUserDto|null $newUser = null,
    ): StaffDto {
        return new StaffDto(
            firstName: $staff->first_name,
            lastName: $staff->last_name,
            email: $staff->email,
            phone: $staff->phone,
            departmentId: $staff->department_id,
            positionId: $staff->position_id,
            userId: $userId,
            branchId: $branchId ?? $staff->branch_id,
            newUser: $newUser,
        );
    }

    private function makeCreateUserDto(string $email = ''): CreateUserDto
    {
        return new CreateUserDto(
            firstName: 'Staff',
            lastName: 'User',
            email: $email !== '' ? $email : Str::uuid() . '@example.com',
            password: 'Password123!',
            branchId: $this->branch->id,
            operationalBranchId: $this->branch->id,
        );
    }
}
