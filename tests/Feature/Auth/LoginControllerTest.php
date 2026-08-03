<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Enums\Tenant\UserStatus;
use App\Features\LocalAuth;
use App\Features\VerifyLoginWithIdp;
use App\Models\Tenant\User;
use App\Services\IdpAccessVerificationService;
use Illuminate\Support\Facades\Hash;
use Laravel\Pennant\Feature;

test('login form is accessible to guests', function () {
    $this->get(route('login'))->assertOk();
});
test('login form renders correct view', function () {
    $this->get(route('login'))->assertViewIs('tenant.auth.login');
});
test('user can login with valid credentials', function () {
    $user = User::factory()->create([
        'password' => Hash::make('Password1!'),
    ]);

    $response = $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'Password1!',
    ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($user);
});
test('login fails with invalid password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('CorrectPassword1!'),
    ]);

    $response = $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'WrongPassword!',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});
test('login fails with nonexistent email', function () {
    $response = $this->post(route('login'), [
        'email' => 'nobody@example.com',
        'password' => 'Password1!',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});
test('login requires email', function () {
    $response = $this->post(route('login'), [
        'password' => 'Password1!',
    ]);

    $response->assertSessionHasErrors('email');
});
test('login requires valid email format', function () {
    $response = $this->post(route('login'), [
        'email' => 'not-an-email',
        'password' => 'Password1!',
    ]);

    $response->assertSessionHasErrors('email');
});
test('login requires password', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login'), [
        'email' => $user->email,
    ]);

    $response->assertSessionHasErrors('password');
});
test('login with remember me sets cookie', function () {
    $user = User::factory()->create([
        'password' => Hash::make('Password1!'),
    ]);

    $response = $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'Password1!',
        'remember' => true,
    ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($user);
});
test('login fails for a user who has not accepted their invite', function () {
    $user = User::factory()->create([
        'password' => Hash::make('Password1!'),
        'status' => UserStatus::Invited,
        'invited_at' => now(),
    ]);

    $response = $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'Password1!',
    ]);

    $response->assertSessionHasErrors(['email' => __('auth.invite_not_accepted')]);
    $this->assertGuest();
});
test('login form shows form when local auth is enabled', function () {
    Feature::for($this->tenant)->activate(LocalAuth::class);

    $this->get(route('login'))->assertOk()->assertSee('sign_in_form', false);
});
test('login form hides form when local auth is disabled', function () {
    Feature::for($this->tenant)->deactivate(LocalAuth::class);

    $this->get(route('login'))->assertOk()->assertDontSee('sign_in_form', false);
});
test('login returns 403 when local auth is disabled', function () {
    Feature::for($this->tenant)->deactivate(LocalAuth::class);

    $this->post(route('login'), [
        'email' => 'user@example.com',
        'password' => 'Password1!',
    ])->assertForbidden();
});
test('login succeeds when verify with idp is enabled and idp grants access', function () {
    Feature::for($this->tenant)->activate(VerifyLoginWithIdp::class);

    $this->mock(IdpAccessVerificationService::class)
        ->shouldReceive('userHasAccess')
        ->once()
        ->andReturn(true);

    $user = User::factory()->create(['password' => Hash::make('Password1!')]);

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'Password1!',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
});
test('login fails when verify with idp is enabled and idp denies access', function () {
    Feature::for($this->tenant)->activate(VerifyLoginWithIdp::class);

    $this->mock(IdpAccessVerificationService::class)
        ->shouldReceive('userHasAccess')
        ->once()
        ->andReturn(false);

    $user = User::factory()->create(['password' => Hash::make('Password1!')]);

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'Password1!',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});
test('verify with idp is not called when feature is disabled', function () {
    Feature::for($this->tenant)->deactivate(VerifyLoginWithIdp::class);

    $this->mock(IdpAccessVerificationService::class)
        ->shouldNotReceive('userHasAccess');

    $user = User::factory()->create(['password' => Hash::make('Password1!')]);

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'Password1!',
    ])->assertRedirect(route('dashboard'));
});
test('authenticated user can logout', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('logout'));

    $this->assertGuest();
});
test('logout redirects to login', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $response->assertRedirect(route('login'));
});
