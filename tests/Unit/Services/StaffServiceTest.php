<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
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

beforeEach(function () {
    $this->service = app(StaffService::class);
});
test('create returns staff instance', function () {
    Notification::fake();
    $dto = makeStaffDto();

    $staff = $this->service->create($dto, $this->user);

    expect($staff)->toBeInstanceOf(Staff::class);
});
test('create persists staff to database', function () {
    Notification::fake();
    $dto = makeStaffDto();

    $staff = $this->service->create($dto, $this->user);

    expect(Staff::find($staff->id))->not->toBeNull();
});
test('create without user sets user id to null', function () {
    Notification::fake();
    $dto = makeStaffDto(userId: null, newUser: null);

    $staff = $this->service->create($dto, $this->user);

    expect($staff->user_id)->toBeNull();
});
test('create stores correct staff fields', function () {
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

    expect($staff->first_name)->toBe('Alice');
    expect($staff->last_name)->toBe('Smith');
    expect($staff->email)->toBe('alice@example.com');
    expect($staff->phone)->toBe('0200000001');
    expect($staff->department_id)->toBe($department->id);
    expect($staff->position_id)->toBe($position->id);
    expect($staff->branch_id)->toBe($this->branch->id);
});
test('create with new user creates linked user', function () {
    Notification::fake();
    $newUserDto = makeCreateUserDto();
    $dto = makeStaffDto(newUser: $newUserDto);

    $staff = $this->service->create($dto, $this->user);

    expect($staff->user_id)->not->toBeNull();
    expect(User::find($staff->user_id))->not->toBeNull();
});
test('create with new user links correct user email', function () {
    Notification::fake();
    $email = Str::uuid() . '@staff.example.com';
    $newUserDto = makeCreateUserDto(email: $email);
    $dto = makeStaffDto(newUser: $newUserDto);

    $staff = $this->service->create($dto, $this->user);

    $linkedUser = User::find($staff->user_id);
    expect($linkedUser->email)->toBe($email);
});
test('create with existing user id links that user', function () {
    $existingUser = User::factory()->create([
        'branch_id' => $this->branch->id,
        'operational_branch_id' => $this->branch->id,
    ]);
    $dto = makeStaffDto(userId: $existingUser->id, newUser: null);

    $staff = $this->service->create($dto, $this->user);

    expect($staff->user_id)->toBe($existingUser->id);
});
test('update changes staff first name', function () {
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
    expect($staff->first_name)->toBe('UpdatedFirst');
});
test('update changes department and position', function () {
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
    expect($staff->department_id)->toBe($newDepartment->id);
    expect($staff->position_id)->toBe($newPosition->id);
});
test('update can link existing user to staff with no user', function () {
    $staff = Staff::factory()->withBranch($this->branch)->create(['user_id' => null]);
    $existingUser = User::factory()->create([
        'branch_id' => $this->branch->id,
        'operational_branch_id' => $this->branch->id,
    ]);
    $dto = makeStaffDtoFromStaff($staff, userId: $existingUser->id);

    $this->service->update($staff, $dto, $this->user);

    $staff->refresh();
    expect($staff->user_id)->toBe($existingUser->id);
});
test('update creates and links new user when staff has none', function () {
    Notification::fake();
    $staff = Staff::factory()->withBranch($this->branch)->create(['user_id' => null]);
    $newUserDto = makeCreateUserDto();
    $dto = makeStaffDtoFromStaff($staff, newUser: $newUserDto);

    $this->service->update($staff, $dto, $this->user);

    $staff->refresh();
    expect($staff->user_id)->not->toBeNull();
    expect(User::find($staff->user_id))->not->toBeNull();
});
test('update syncs user branch when branch changes', function () {
    $linkedUser = User::factory()->create([
        'branch_id' => $this->branch->id,
        'operational_branch_id' => $this->branch->id,
    ]);
    $staff = Staff::factory()
        ->withUser($linkedUser)
        ->withBranch($this->branch)
        ->create();
    $newBranch = Branch::factory()->create(['level_id' => $this->level->id]);
    $dto = makeStaffDtoFromStaff($staff, branchId: $newBranch->id, userId: $linkedUser->id);

    $this->service->update($staff, $dto, $this->user);

    $linkedUser->refresh();
    expect($linkedUser->branch_id)->toBe($newBranch->id);
});
test('update does not sync branch when branch unchanged', function () {
    $linkedUser = User::factory()->create([
        'branch_id' => $this->branch->id,
        'operational_branch_id' => $this->branch->id,
    ]);
    $staff = Staff::factory()
        ->withUser($linkedUser)
        ->withBranch($this->branch)
        ->create();
    $dto = makeStaffDtoFromStaff($staff, branchId: $this->branch->id, userId: $linkedUser->id);

    $this->service->update($staff, $dto, $this->user);

    $linkedUser->refresh();
    expect($linkedUser->branch_id)->toBe($this->branch->id);
});
test('update does not override existing user link', function () {
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
    $dto = makeStaffDtoFromStaff($staff, userId: $anotherUser->id);

    $this->service->update($staff, $dto, $this->user);

    $staff->refresh();
    expect($staff->user_id)->toBe($linkedUser->id);
});
test('delete soft deletes staff record', function () {
    $staff = Staff::factory()->withBranch($this->branch)->create();
    $staffId = $staff->id;

    $this->service->delete($staff, $this->user);

    expect(Staff::find($staffId))->toBeNull();
    expect(Staff::withTrashed()->find($staffId))->not->toBeNull();
});
test('delete sets deleted at timestamp', function () {
    $staff = Staff::factory()->withBranch($this->branch)->create();
    $staffId = $staff->id;

    $this->service->delete($staff, $this->user);

    $deleted = Staff::withTrashed()->find($staffId);
    expect($deleted->deleted_at)->not->toBeNull();
});
// ── Helpers ───────────────────────────────────────────────────────────────
function makeStaffDto(int|null $userId = null, CreateUserDto|null $newUser = null, int|null $branchId = null): StaffDto
{
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
        branchId: $branchId ?? test()->branch->id,
        newUser: $newUser,
    );
}
function makeStaffDtoFromStaff(Staff $staff, int|null $userId = null, int|null $branchId = null, CreateUserDto|null $newUser = null): StaffDto
{
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
function makeCreateUserDto(string $email = ''): CreateUserDto
{
    return new CreateUserDto(
        firstName: 'Staff',
        lastName: 'User',
        email: $email !== '' ? $email : Str::uuid() . '@example.com',
        password: 'Password123!',
        branchId: test()->branch->id,
        operationalBranchId: test()->branch->id,
    );
}
