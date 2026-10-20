<?php

declare(strict_types=1);

uses(Tests\LandlordTestCase::class);
use App\Models\Landlord\User;
use Illuminate\Support\Facades\Hash;

test('login form is accessible to guests', function () {
    $this->get(route('landlord.login'))->assertOk();
});
test('login form renders correct view', function () {
    $this->get(route('landlord.login'))->assertViewIs('landlord.auth.login');
});
test('guest is redirected to the landlord login from a protected landlord page', function () {
    $this->get(route('landlord.tenants.index'))
        ->assertRedirect(route('landlord.login'));
});
test('landlord can login with valid credentials', function () {
    $user = User::factory()->create([
        'password' => Hash::make('Password1!'),
    ]);

    $this->post(route('landlord.do-login'), [
        'email' => $user->email,
        'password' => 'Password1!',
    ])->assertRedirect(route('landlord.tenants.index'));

    $this->assertAuthenticatedAs($user, 'landlord');
});
test('login fails with wrong password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('CorrectPassword1!'),
    ]);

    $this->post(route('landlord.do-login'), [
        'email' => $user->email,
        'password' => 'WrongPassword!',
    ])->assertSessionHasErrors('email');

    $this->assertGuest('landlord');
});
test('login fails with nonexistent email', function () {
    $this->post(route('landlord.do-login'), [
        'email' => 'nobody@example.com',
        'password' => 'Password1!',
    ])->assertSessionHasErrors('email');

    $this->assertGuest('landlord');
});
test('login requires email', function () {
    $this->post(route('landlord.do-login'), [
        'password' => 'Password1!',
    ])->assertSessionHasErrors('email');
});
test('login requires valid email format', function () {
    $this->post(route('landlord.do-login'), [
        'email' => 'not-an-email',
        'password' => 'Password1!',
    ])->assertSessionHasErrors('email');
});
test('login requires password', function () {
    $user = User::factory()->create();

    $this->post(route('landlord.do-login'), [
        'email' => $user->email,
    ])->assertSessionHasErrors('password');
});
test('authenticated landlord can logout', function () {
    $this->actingAs($this->landlordUser, 'landlord')
        ->post(route('landlord.logout'));

    $this->assertGuest('landlord');
});
test('logout redirects to landlord login', function () {
    $this->actingAs($this->landlordUser, 'landlord')
        ->post(route('landlord.logout'))
        ->assertRedirect(route('landlord.login'));
});
test('landlord account locks after repeated failures even with the correct password', function () {
    $this->withoutMiddleware(Illuminate\Routing\Middleware\ThrottleRequests::class);
    $user = User::factory()->create(['password' => Hash::make('Correct-Password-1!')]);

    for ($i = 0; $i < App\Services\LoginAttemptLimiter::MAX_ATTEMPTS; $i++) {
        $this->post(route('landlord.do-login'), ['email' => $user->email, 'password' => 'Wrong-Password-1!']);
    }

    $this->post(route('landlord.do-login'), ['email' => $user->email, 'password' => 'Correct-Password-1!'])
        ->assertSessionHasErrors('email');
    $this->assertGuest('landlord');
});
test('landlord keep-alive endpoint returns no content for a signed in landlord user', function () {
    $user = User::factory()->create();

    $this->get(route('landlord.session.keep-alive'))->assertRedirect(route('landlord.login'));
    $this->actingAs($user, 'landlord')->get(route('landlord.session.keep-alive'))->assertNoContent();
});
