<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\DTOs\Tenant\CreateUserDto;
use App\Enums\Tenant\UserStatus;
use App\Models\Tenant\Branch;
use App\Models\Tenant\User;
use App\Notifications\WelcomeNotification;
use App\Services\UserService;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Facades\Notification;

test('welcome notification is queued when regular user is created', function () {
    Notification::fake();

    $branch = Branch::factory()->create();
    $dto = new CreateUserDto(
        firstName: 'Jane',
        lastName: 'Doe',
        email: 'jane@example.com',
        branchId: $branch->id,
        operationalBranchId: $branch->id,
    );

    $user = app(UserService::class)->create($dto);

    Notification::assertSentTo($user, WelcomeNotification::class);
});
test('invited users only have a mail route for welcome notifications', function () {
    $user = User::factory()->create(['status' => UserStatus::Invited]);
    $operationalNotification = new class extends BaseNotification {};

    expect($user->routeNotificationFor('mail', $operationalNotification))->toBeNull()
        ->and($user->routeNotificationFor('mail', new WelcomeNotification('invite-token')))->toBe($user->email);
});
test('welcome notification is not sent for sso users', function () {
    Notification::fake();

    $branch = Branch::factory()->create();

    // A freshly-created user with is_oidc_user=true should not trigger the welcome email.
    User::factory()->create(['is_oidc_user' => true, 'branch_id' => $branch->id, 'operational_branch_id' => $branch->id]);

    Notification::assertNothingSent();
});
test('user can change their password', function () {
    $this->actingAs($this->user)
        ->put(route('password.change.update'), [
            'current_password' => 'password',
            'password' => 'newSecurePassword1!',
            'password_confirmation' => 'newSecurePassword1!',
        ])
        ->assertRedirect(route('dashboard'));

    expect(Illuminate\Support\Facades\Hash::check('newSecurePassword1!', $this->user->fresh()->password))->toBeTrue();
});
test('password change fails when passwords do not match', function () {
    $this->actingAs($this->user)
        ->put(route('password.change.update'), [
            'current_password' => 'password',
            'password' => 'newSecurePassword1!',
            'password_confirmation' => 'differentPassword!',
        ])
        ->assertSessionHasErrors('password');
});
test('password change fails when password is too short', function () {
    $this->actingAs($this->user)
        ->put(route('password.change.update'), [
            'current_password' => 'password',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])
        ->assertSessionHasErrors('password');
});
test('password change fails when current password is wrong', function () {
    $this->actingAs($this->user)
        ->put(route('password.change.update'), [
            'current_password' => 'wrongpassword',
            'password' => 'newSecurePassword1!',
            'password_confirmation' => 'newSecurePassword1!',
        ])
        ->assertSessionHasErrors('current_password');
});
