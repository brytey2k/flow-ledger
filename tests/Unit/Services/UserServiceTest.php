<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\DTOs\Tenant\CreateUserDto;
use App\DTOs\Tenant\UpdateUserDto;
use App\Models\Role;
use App\Models\Tenant\User;
use App\Notifications\WelcomeNotification;
use App\Services\UserService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->service = app(UserService::class);
});
test('create returns user model', function () {
    Notification::fake();
    $dto = makeCreateDto();

    $user = $this->service->create($dto);

    expect($user)->toBeInstanceOf(User::class);
});
test('create persists user to database', function () {
    Notification::fake();
    $dto = makeCreateDto();

    $user = $this->service->create($dto);

    expect(User::find($user->id))->not->toBeNull();
    expect($user->email)->toBe($dto->email);
});
test('create sets must change password to true', function () {
    Notification::fake();
    $dto = makeCreateDto();

    $user = $this->service->create($dto);

    expect((bool) $user->must_change_password)->toBeTrue();
});
test('create sends welcome notification', function () {
    Notification::fake();
    $dto = makeCreateDto();

    $user = $this->service->create($dto);

    Notification::assertSentTo($user, WelcomeNotification::class);
});
test('create assigns roles when provided', function () {
    Notification::fake();
    $role = Role::create(['name' => 'role_' . Str::uuid(), 'guard_name' => 'web']);
    $dto = makeCreateDto([$role->id]);

    $user = $this->service->create($dto);

    expect($user->hasRole($role))->toBeTrue();
});
test('create does not assign roles when none provided', function () {
    Notification::fake();
    $dto = makeCreateDto([]);

    $user = $this->service->create($dto);

    expect($user->fresh()->roles)->toHaveCount(0);
});
test('update changes user name', function () {
    $dto = new UpdateUserDto(
        firstName: 'UpdatedFirst',
        lastName: 'UpdatedLast',
        email: $this->user->email,
        password: null,
    );

    $this->service->update($this->user, $dto);

    $this->user->refresh();
    expect($this->user->first_name)->toBe('UpdatedFirst');
    expect($this->user->last_name)->toBe('UpdatedLast');
});
test('update changes user email', function () {
    $newEmail = 'updated_' . Str::uuid() . '@example.com';
    $dto = new UpdateUserDto(
        firstName: $this->user->first_name,
        lastName: $this->user->last_name,
        email: $newEmail,
        password: null,
    );

    $this->service->update($this->user, $dto);

    $this->user->refresh();
    expect($this->user->email)->toBe($newEmail);
});
test('update changes password when provided', function () {
    $dto = new UpdateUserDto(
        firstName: $this->user->first_name,
        lastName: $this->user->last_name,
        email: $this->user->email,
        password: 'NewPassword123!',
    );

    $this->service->update($this->user, $dto);

    $this->user->refresh();
    expect(Illuminate\Support\Facades\Hash::check('NewPassword123!', $this->user->password))->toBeTrue();
});
test('update does not change password when null', function () {
    $originalPassword = $this->user->password;
    $dto = new UpdateUserDto(
        firstName: $this->user->first_name,
        lastName: $this->user->last_name,
        email: $this->user->email,
        password: null,
    );

    $this->service->update($this->user, $dto);

    $this->user->refresh();
    expect($this->user->password)->toBe($originalPassword);
});
test('update syncs roles', function () {
    $newRole = Role::create(['name' => 'new_role_' . Str::uuid(), 'guard_name' => 'web']);
    $dto = new UpdateUserDto(
        firstName: $this->user->first_name,
        lastName: $this->user->last_name,
        email: $this->user->email,
        password: null,
        roles: [$newRole->id],
    );

    $this->service->update($this->user, $dto);

    $this->user->refresh()->unsetRelation('roles');
    expect($this->user->hasRole($newRole))->toBeTrue();
});
test('sync branch updates branch id', function () {
    $newBranch = App\Models\Tenant\Branch::factory()->create(['level_id' => $this->level->id]);
    $oldBranchId = $this->user->branch_id;

    $this->service->syncBranch($this->user->id, $newBranch->id, $oldBranchId);

    $this->user->refresh();
    expect($this->user->branch_id)->toBe($newBranch->id);
});
test('sync branch also updates operational branch when matching', function () {
    $newBranch = App\Models\Tenant\Branch::factory()->create(['level_id' => $this->level->id]);
    $oldBranchId = $this->user->operational_branch_id;

    $this->service->syncBranch($this->user->id, $newBranch->id, $oldBranchId);

    $this->user->refresh();
    expect($this->user->operational_branch_id)->toBe($newBranch->id);
});
test('sync branch does not update operational branch when different', function () {
    $newBranch = App\Models\Tenant\Branch::factory()->create(['level_id' => $this->level->id]);
    $originalOperationalBranchId = $this->user->operational_branch_id;

    $this->service->syncBranch($this->user->id, $newBranch->id, null);

    $this->user->refresh();
    expect($this->user->operational_branch_id)->toBe($originalOperationalBranchId);
});
test('sync branch does nothing for nonexistent user', function () {
    $this->expectNotToPerformAssertions();

    $this->service->syncBranch(999999, $this->branch->id, null);
});
test('delete removes user from database', function () {
    $user = User::factory()->create(['branch_id' => $this->branch->id, 'operational_branch_id' => $this->branch->id]);
    $userId = $user->id;

    $this->service->delete($user);

    expect(User::find($userId))->toBeNull();
});
// ── Helpers ───────────────────────────────────────────────────────────────
/** @param array<int, int> $roles */
function makeCreateDto(array $roles = []): CreateUserDto
{
    return new CreateUserDto(
        firstName: 'Test',
        lastName: 'User',
        email: Str::uuid() . '@example.com',
        password: 'Password123!',
        branchId: test()->branch->id,
        operationalBranchId: test()->branch->id,
        roles: $roles,
    );
}
