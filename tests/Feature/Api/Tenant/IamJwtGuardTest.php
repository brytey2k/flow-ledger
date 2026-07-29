<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Auth\IamJwtGuard;
use App\Models\Tenant\User;
use App\Repositories\UserRepository;
use App\Services\SsoClientService;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;

test('api route missing authorization returns 401', function () {
    $this->getJson('/api/me')->assertUnauthorized();
});
test('api route non bearer header returns 401', function () {
    $this->getJson('/api/me', ['Authorization' => 'Basic abc123'])->assertUnauthorized();
});
test('user returns null when bearer token is absent', function () {
    $request = Request::create('/api/me', 'GET');
    $guard = makeGuardForIamJwtGuard('test-sub', $this->user, $request);

    expect($guard->user())->toBeNull();
    expect($guard->guest())->toBeTrue();
});
test('user returns null when jwt parsing throws', function () {
    $sso = $this->createMock(SsoClientService::class);
    $userRepo = $this->createMock(UserRepository::class);
    $provider = $this->createMock(UserProvider::class);

    $request = Request::create('/api/me', 'GET', [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer bad-token']);

    $guard = new class ($provider, $request, $sso, $userRepo) extends IamJwtGuard {
        public function parseAndValidate(string $rawToken): string
        {
            throw new RuntimeException('Token invalid');
        }
    };

    expect($guard->user())->toBeNull();
});
test('user returns null when sub does not match any user', function () {
    $request = Request::create('/api/me', 'GET', [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer valid-token']);
    $guard = makeGuardForIamJwtGuard('unknown-sub', null, $request);

    expect($guard->user())->toBeNull();
    expect($guard->check())->toBeFalse();
});
test('user returns authenticated user when token is valid', function () {
    $this->user->oidc_sub = 'valid-sub';
    $request = Request::create('/api/me', 'GET', [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer valid-token']);
    $guard = makeGuardForIamJwtGuard('valid-sub', $this->user, $request);

    $resolved = $guard->user();

    expect($resolved)->not->toBeNull();
    expect($resolved->getAuthIdentifier())->toBe($this->user->id);
    expect($guard->check())->toBeTrue();
});
test('user is resolved only once', function () {
    $userRepo = $this->createMock(UserRepository::class);
    $userRepo->expects($this->once())
        ->method('findByOidcSub')
        ->willReturn($this->user);

    $sso = $this->createMock(SsoClientService::class);
    $provider = $this->createMock(UserProvider::class);
    $request = Request::create('/api/me', 'GET', [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer valid-token']);

    $guard = new class ($provider, $request, $sso, $userRepo) extends IamJwtGuard {
        public function parseAndValidate(string $rawToken): string
        {
            return 'valid-sub';
        }
    };

    $guard->user();
    $guard->user();
    // second call must not re-resolve
});
// ── Helpers ───────────────────────────────────────────────────────────────
function makeGuardForIamJwtGuard(string $stubbedSub, User|null $resolvedUser, Request $request): IamJwtGuard
{
    $sso = test()->createMock(SsoClientService::class);
    $provider = test()->createMock(UserProvider::class);

    $userRepo = test()->createMock(UserRepository::class);
    $userRepo->method('findByOidcSub')
        ->with($stubbedSub)
        ->willReturn($resolvedUser);

    return new class ($provider, $request, $sso, $userRepo, $stubbedSub) extends IamJwtGuard {
        public function __construct(UserProvider $provider, Request $request, SsoClientService $ssoClient, UserRepository $userRepository, private readonly string $stubbedSub)
        {
            parent::__construct($provider, $request, $ssoClient, $userRepository);
        }

        public function parseAndValidate(string $rawToken): string
        {
            return $this->stubbedSub;
        }
    };
}
