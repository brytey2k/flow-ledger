<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTOs\Tenant\CreateUserDto;
use App\DTOs\Tenant\UpdateUserDto;
use App\Models\Role;
use App\Models\Tenant\User;
use App\Notifications\WelcomeNotification;
use App\Services\UserService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TenantAppTestCase;

class UserServiceTest extends TenantAppTestCase
{
    private UserService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(UserService::class);
    }

    // ── create ────────────────────────────────────────────────────────────────

    public function test_create_returns_user_model(): void
    {
        Notification::fake();
        $dto = $this->makeCreateDto();

        $user = $this->service->create($dto);

        $this->assertInstanceOf(User::class, $user);
    }

    public function test_create_persists_user_to_database(): void
    {
        Notification::fake();
        $dto = $this->makeCreateDto();

        $user = $this->service->create($dto);

        $this->assertNotNull(User::find($user->id));
        $this->assertSame($dto->email, $user->email);
    }

    public function test_create_sets_must_change_password_to_true(): void
    {
        Notification::fake();
        $dto = $this->makeCreateDto();

        $user = $this->service->create($dto);

        $this->assertTrue((bool) $user->must_change_password);
    }

    public function test_create_sends_welcome_notification(): void
    {
        Notification::fake();
        $dto = $this->makeCreateDto();

        $user = $this->service->create($dto);

        Notification::assertSentTo($user, WelcomeNotification::class);
    }

    public function test_create_assigns_roles_when_provided(): void
    {
        Notification::fake();
        $role = Role::create(['name' => 'role_' . Str::uuid(), 'guard_name' => 'web']);
        $dto = $this->makeCreateDto([$role->id]);

        $user = $this->service->create($dto);

        $this->assertTrue($user->hasRole($role));
    }

    public function test_create_does_not_assign_roles_when_none_provided(): void
    {
        Notification::fake();
        $dto = $this->makeCreateDto([]);

        $user = $this->service->create($dto);

        $this->assertCount(0, $user->fresh()->roles);
    }

    // ── update ────────────────────────────────────────────────────────────────

    public function test_update_changes_user_name(): void
    {
        $dto = new UpdateUserDto(
            firstName: 'UpdatedFirst',
            lastName: 'UpdatedLast',
            email: $this->user->email,
            password: null,
        );

        $this->service->update($this->user, $dto);

        $this->user->refresh();
        $this->assertSame('UpdatedFirst', $this->user->first_name);
        $this->assertSame('UpdatedLast', $this->user->last_name);
    }

    public function test_update_changes_user_email(): void
    {
        $newEmail = 'updated_' . Str::uuid() . '@example.com';
        $dto = new UpdateUserDto(
            firstName: $this->user->first_name,
            lastName: $this->user->last_name,
            email: $newEmail,
            password: null,
        );

        $this->service->update($this->user, $dto);

        $this->user->refresh();
        $this->assertSame($newEmail, $this->user->email);
    }

    public function test_update_changes_password_when_provided(): void
    {
        $dto = new UpdateUserDto(
            firstName: $this->user->first_name,
            lastName: $this->user->last_name,
            email: $this->user->email,
            password: 'NewPassword123!',
        );

        $this->service->update($this->user, $dto);

        $this->user->refresh();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('NewPassword123!', $this->user->password));
    }

    public function test_update_does_not_change_password_when_null(): void
    {
        $originalPassword = $this->user->password;
        $dto = new UpdateUserDto(
            firstName: $this->user->first_name,
            lastName: $this->user->last_name,
            email: $this->user->email,
            password: null,
        );

        $this->service->update($this->user, $dto);

        $this->user->refresh();
        $this->assertSame($originalPassword, $this->user->password);
    }

    public function test_update_syncs_roles(): void
    {
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
        $this->assertTrue($this->user->hasRole($newRole));
    }

    // ── syncBranch ────────────────────────────────────────────────────────────

    public function test_sync_branch_updates_branch_id(): void
    {
        $newBranch = \App\Models\Tenant\Branch::factory()->create(['level_id' => $this->level->id]);
        $oldBranchId = $this->user->branch_id;

        $this->service->syncBranch($this->user->id, $newBranch->id, $oldBranchId);

        $this->user->refresh();
        $this->assertSame($newBranch->id, $this->user->branch_id);
    }

    public function test_sync_branch_also_updates_operational_branch_when_matching(): void
    {
        $newBranch = \App\Models\Tenant\Branch::factory()->create(['level_id' => $this->level->id]);
        $oldBranchId = $this->user->operational_branch_id;

        $this->service->syncBranch($this->user->id, $newBranch->id, $oldBranchId);

        $this->user->refresh();
        $this->assertSame($newBranch->id, $this->user->operational_branch_id);
    }

    public function test_sync_branch_does_not_update_operational_branch_when_different(): void
    {
        $newBranch = \App\Models\Tenant\Branch::factory()->create(['level_id' => $this->level->id]);
        $originalOperationalBranchId = $this->user->operational_branch_id;

        $this->service->syncBranch($this->user->id, $newBranch->id, null);

        $this->user->refresh();
        $this->assertSame($originalOperationalBranchId, $this->user->operational_branch_id);
    }

    public function test_sync_branch_does_nothing_for_nonexistent_user(): void
    {
        $this->expectNotToPerformAssertions();

        $this->service->syncBranch(999999, $this->branch->id, null);
    }

    // ── delete ────────────────────────────────────────────────────────────────

    public function test_delete_removes_user_from_database(): void
    {
        $user = User::factory()->create(['branch_id' => $this->branch->id, 'operational_branch_id' => $this->branch->id]);
        $userId = $user->id;

        $this->service->delete($user);

        $this->assertNull(User::find($userId));
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /** @param array<int, int> $roles */
    private function makeCreateDto(array $roles = []): CreateUserDto
    {
        return new CreateUserDto(
            firstName: 'Test',
            lastName: 'User',
            email: Str::uuid() . '@example.com',
            password: 'Password123!',
            branchId: $this->branch->id,
            operationalBranchId: $this->branch->id,
            roles: $roles,
        );
    }
}
