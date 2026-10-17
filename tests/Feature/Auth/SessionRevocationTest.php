<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Auth\IamJwtGuard;
use App\Enums\Tenant\UserStatus;
use App\Features\VerifyLoginWithIdp;
use App\Models\Tenant\User;
use App\Repositories\UserRepository;
use App\Services\IdpAccessVerificationService;
use App\Services\SessionInvalidatorService;
use App\Services\SsoClientService;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Laravel\Pennant\Feature;

function storedSessionFor(int $userId, string $id): void
{
    DB::table('sessions')->insert([
        'id' => $id, 'user_id' => $userId, 'ip_address' => '10.0.0.1', 'user_agent' => 'other-browser',
        'payload' => 'x', 'last_activity' => time(),
    ]);
}

test('changing the password signs out the other sessions and rotates the remember token', function () {
    $this->user->forceFill(['password' => Hash::make('Old-Password-2026!'), 'remember_token' => 'old-remember-token'])->save();
    storedSessionFor($this->user->id, 'stolen-session');

    $this->actingAs($this->user)->put(route('password.change.update'), [
        'current_password' => 'Old-Password-2026!',
        'password' => 'Brand-New-Pass-2026!',
        'password_confirmation' => 'Brand-New-Pass-2026!',
    ])->assertRedirect(route('dashboard'));

    expect(DB::table('sessions')->where('id', 'stolen-session')->exists())->toBeFalse();
    expect($this->user->fresh()->remember_token)->not->toBe('old-remember-token');
});

test('resetting the password signs out sessions opened before the reset', function () {
    storedSessionFor($this->user->id, 'stolen-session');
    $token = Password::createToken($this->user);

    $this->post(route('password.update'), [
        'token' => $token,
        'email' => $this->user->email,
        'password' => 'Sturdy-Pass-2026!',
        'password_confirmation' => 'Sturdy-Pass-2026!',
    ])->assertRedirect(route('dashboard'));

    expect(DB::table('sessions')->where('id', 'stolen-session')->exists())->toBeFalse();
    $this->assertAuthenticatedAs($this->user);
});

test('a password reset does not skip the identity provider access check', function () {
    Feature::for($this->tenant)->activate(VerifyLoginWithIdp::class);
    $this->mock(IdpAccessVerificationService::class)->shouldReceive('userHasAccess')->once()->andReturn(false);
    $token = Password::createToken($this->user);

    $this->post(route('password.update'), [
        'token' => $token,
        'email' => $this->user->email,
        'password' => 'Sturdy-Pass-2026!',
        'password_confirmation' => 'Sturdy-Pass-2026!',
    ])->assertRedirect(route('login'));

    $this->assertGuest();
});

function jwtGuardIssuedAt(User $user, int|null $issuedAt): IamJwtGuard
{
    $request = Request::create('/api/me', 'GET', [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer valid-token']);
    $repo = test()->createMock(UserRepository::class);
    $repo->method('findByOidcSub')->willReturn($user);

    return new class (test()->createMock(UserProvider::class), $request, test()->createMock(SsoClientService::class), $repo, $issuedAt) extends IamJwtGuard {
        public function __construct(UserProvider $provider, Request $request, SsoClientService $sso, UserRepository $repo, private readonly int|null $stubIssuedAt)
        {
            parent::__construct($provider, $request, $sso, $repo);
        }

        public function parseAndValidate(string $rawToken): string
        {
            $this->issuedAt = $this->stubIssuedAt;

            return 'valid-sub';
        }
    };
}

test('a bearer token issued before a force sign-out stops working', function () {
    app(SessionInvalidatorService::class)->invalidate($this->user->id);

    expect(jwtGuardIssuedAt($this->user, now()->subMinutes(5)->getTimestamp())->user())->toBeNull();
    expect(jwtGuardIssuedAt($this->user, null)->user())->toBeNull();
});

test('a token issued after the force sign-out works again', function () {
    Cache::put(SessionInvalidatorService::apiRevokedBeforeKey($this->user->id), now()->subMinutes(10)->getTimestamp(), now()->addDay());

    expect(jwtGuardIssuedAt($this->user, now()->getTimestamp())->user())->not->toBeNull();
});

test('a suspended user cannot use a bearer token', function () {
    $this->user->forceFill(['status' => UserStatus::Suspended])->save();

    expect(jwtGuardIssuedAt($this->user, now()->getTimestamp())->user())->toBeNull();
});
