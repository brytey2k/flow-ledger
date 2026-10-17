<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Models\Tenant\User;
use App\Notifications\AccountLockedNotification;
use App\Services\LoginAttemptLimiter;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->withoutMiddleware(Illuminate\Routing\Middleware\ThrottleRequests::class);
});

function failLoginTimes(object $test, string $email, int $times): void
{
    for ($i = 0; $i < $times; $i++) {
        $test->post(route('do-login'), ['email' => $email, 'password' => 'Wrong-Password-1!']);
    }
}

test('account locks after repeated failures even with the correct password', function () {
    $user = User::factory()->create(['password' => Hash::make('Correct-Password-1!')]);

    failLoginTimes($this, $user->email, LoginAttemptLimiter::MAX_ATTEMPTS);

    $this->post(route('do-login'), ['email' => $user->email, 'password' => 'Correct-Password-1!'])
        ->assertSessionHasErrors('email');
    $this->assertGuest();
});
test('the lockout message tells the user how long to wait', function () {
    $user = User::factory()->create(['password' => Hash::make('Correct-Password-1!')]);

    failLoginTimes($this, $user->email, LoginAttemptLimiter::MAX_ATTEMPTS);

    $this->post(route('do-login'), ['email' => $user->email, 'password' => 'Correct-Password-1!'])
        ->assertSessionHasErrors(['email' => __('auth.account_locked', ['minutes' => 15])]);
});
test('failures below the limit do not lock the account', function () {
    $user = User::factory()->create(['password' => Hash::make('Correct-Password-1!')]);

    failLoginTimes($this, $user->email, LoginAttemptLimiter::MAX_ATTEMPTS - 1);

    $this->post(route('do-login'), ['email' => $user->email, 'password' => 'Correct-Password-1!'])
        ->assertRedirect(route('dashboard'));
});
test('a successful login clears earlier failures', function () {
    $user = User::factory()->create(['password' => Hash::make('Correct-Password-1!')]);

    failLoginTimes($this, $user->email, LoginAttemptLimiter::MAX_ATTEMPTS - 1);
    $this->post(route('do-login'), ['email' => $user->email, 'password' => 'Correct-Password-1!']);
    $this->post(route('logout'));

    failLoginTimes($this, $user->email, LoginAttemptLimiter::MAX_ATTEMPTS - 1);

    $this->post(route('do-login'), ['email' => $user->email, 'password' => 'Correct-Password-1!'])
        ->assertRedirect(route('dashboard'));
});
test('the user is emailed and the lockout is logged when the account locks', function () {
    Notification::fake();
    $user = User::factory()->create(['password' => Hash::make('Correct-Password-1!')]);

    failLoginTimes($this, $user->email, LoginAttemptLimiter::MAX_ATTEMPTS);

    Notification::assertSentToTimes($user, AccountLockedNotification::class, 1);
    $this->assertDatabaseHas('activity_log', ['event' => 'auth.locked_out']);
});
test('further failures after lockout do not send another email', function () {
    Notification::fake();
    $user = User::factory()->create(['password' => Hash::make('Correct-Password-1!')]);

    failLoginTimes($this, $user->email, LoginAttemptLimiter::MAX_ATTEMPTS + 3);

    Notification::assertSentToTimes($user, AccountLockedNotification::class, 1);
});
test('locking an unknown email does not error or reveal that it does not exist', function () {
    Notification::fake();

    failLoginTimes($this, 'nobody@example.com', LoginAttemptLimiter::MAX_ATTEMPTS);

    $this->post(route('do-login'), ['email' => 'nobody@example.com', 'password' => 'x'])
        ->assertSessionHasErrors(['email' => __('auth.account_locked', ['minutes' => 15])]);
    Notification::assertNothingSent();
});
test('the keep-alive endpoint needs a signed in user and returns no content', function () {
    $this->get(route('session.keep-alive'))->assertRedirect(route('login'));

    $this->actingAs($this->user)->get(route('session.keep-alive'))->assertNoContent();
});
test('authenticated pages include the session timeout warning', function () {
    $this->actingAs($this->user)->get(route('dashboard'))
        ->assertSee('session-timeout-warning', false);
});
test('a successful password reset lifts the lockout', function () {
    $user = User::factory()->create(['password' => Hash::make('Correct-Password-1!')]);

    failLoginTimes($this, $user->email, LoginAttemptLimiter::MAX_ATTEMPTS + 1);
    $this->post(route('do-login'), ['email' => $user->email, 'password' => 'Correct-Password-1!'])
        ->assertSessionHasErrors('email');

    $this->post(route('password.update'), [
        'token' => Illuminate\Support\Facades\Password::createToken($user),
        'email' => $user->email,
        'password' => 'Brand-New-Pass-2026!',
        'password_confirmation' => 'Brand-New-Pass-2026!',
    ]);
    $this->post(route('logout'));

    $this->post(route('do-login'), ['email' => $user->email, 'password' => 'Brand-New-Pass-2026!'])
        ->assertRedirect(route('dashboard'));
});
test('the correct password on the final allowed attempt still signs in', function () {
    $user = User::factory()->create(['password' => Hash::make('Correct-Password-1!')]);

    failLoginTimes($this, $user->email, LoginAttemptLimiter::MAX_ATTEMPTS - 1);

    $this->post(route('do-login'), ['email' => $user->email, 'password' => 'Correct-Password-1!'])
        ->assertRedirect(route('dashboard'));
});
test('the session timeout warning signs out with a csrf protected post', function () {
    $this->actingAs($this->user)->get(route('dashboard'))
        ->assertSee(route('logout'), false)
        ->assertSee('X-CSRF-TOKEN', false)
        ->assertDontSee('href="' . route('login') . '" class="sgh-btn sgh-btn-outline"', false);
});
