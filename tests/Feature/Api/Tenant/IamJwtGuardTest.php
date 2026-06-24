<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Tenant;

use App\Auth\IamJwtGuard;
use App\Models\Tenant\User;
use App\Repositories\UserRepository;
use App\Services\SsoClientService;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use RuntimeException;
use Tests\TenantAppTestCase;

class IamJwtGuardTest extends TenantAppTestCase
{
    // ── HTTP-level smoke tests ────────────────────────────────────────────────

    public function test_api_route_missing_authorization_returns_401(): void
    {
        $this->getJson('/api/me')->assertUnauthorized();
    }

    public function test_api_route_non_bearer_header_returns_401(): void
    {
        $this->getJson('/api/me', ['Authorization' => 'Basic abc123'])->assertUnauthorized();
    }

    // ── Guard unit tests ──────────────────────────────────────────────────────

    public function test_user_returns_null_when_bearer_token_is_absent(): void
    {
        $request = Request::create('/api/me', 'GET');
        $guard = $this->makeGuard('test-sub', $this->user, $request);

        $this->assertNull($guard->user());
        $this->assertTrue($guard->guest());
    }

    public function test_user_returns_null_when_jwt_parsing_throws(): void
    {
        $sso = $this->createMock(SsoClientService::class);
        $userRepo = $this->createMock(UserRepository::class);
        $provider = $this->createMock(UserProvider::class);

        $request = Request::create('/api/me', 'GET', [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer bad-token']);

        $guard = new class ($provider, $request, $sso, $userRepo) extends IamJwtGuard {
            protected function parseAndValidate(string $rawToken): string
            {
                throw new RuntimeException('Token invalid');
            }
        };

        $this->assertNull($guard->user());
    }

    public function test_user_returns_null_when_sub_does_not_match_any_user(): void
    {
        $request = Request::create('/api/me', 'GET', [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer valid-token']);
        $guard = $this->makeGuard('unknown-sub', null, $request);

        $this->assertNull($guard->user());
        $this->assertFalse($guard->check());
    }

    public function test_user_returns_authenticated_user_when_token_is_valid(): void
    {
        $this->user->oidc_sub = 'valid-sub';
        $request = Request::create('/api/me', 'GET', [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer valid-token']);
        $guard = $this->makeGuard('valid-sub', $this->user, $request);

        $resolved = $guard->user();

        $this->assertNotNull($resolved);
        $this->assertSame($this->user->id, $resolved->getAuthIdentifier());
        $this->assertTrue($guard->check());
    }

    public function test_user_is_resolved_only_once(): void
    {
        $userRepo = $this->createMock(UserRepository::class);
        $userRepo->expects($this->once())
            ->method('findByOidcSub')
            ->willReturn($this->user);

        $sso = $this->createMock(SsoClientService::class);
        $provider = $this->createMock(UserProvider::class);
        $request = Request::create('/api/me', 'GET', [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer valid-token']);

        $guard = new class ($provider, $request, $sso, $userRepo) extends IamJwtGuard {
            protected function parseAndValidate(string $rawToken): string
            {
                return 'valid-sub';
            }
        };

        $guard->user();
        $guard->user(); // second call must not re-resolve
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeGuard(string $stubbedSub, User|null $resolvedUser, Request $request): IamJwtGuard
    {
        $sso = $this->createMock(SsoClientService::class);
        $provider = $this->createMock(UserProvider::class);

        $userRepo = $this->createMock(UserRepository::class);
        $userRepo->method('findByOidcSub')
            ->with($stubbedSub)
            ->willReturn($resolvedUser);

        return new class ($provider, $request, $sso, $userRepo, $stubbedSub) extends IamJwtGuard {
            public function __construct(
                UserProvider $provider,
                Request $request,
                SsoClientService $ssoClient,
                UserRepository $userRepository,
                private readonly string $stubbedSub,
            ) {
                parent::__construct($provider, $request, $ssoClient, $userRepository);
            }

            protected function parseAndValidate(string $rawToken): string
            {
                return $this->stubbedSub;
            }
        };
    }
}
