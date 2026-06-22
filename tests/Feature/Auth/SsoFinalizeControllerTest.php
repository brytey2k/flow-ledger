<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Data\Auth\SsoUserClaimsDto;
use App\Interfaces\SessionInvalidatorInterface;
use App\Models\Tenant\User;
use App\Services\SsoUserProvisioningService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Tests\TenantAppTestCase;

class SsoFinalizeControllerTest extends TenantAppTestCase
{
    private function storeSsoToken(string $token, SsoUserClaimsDto $claims): void
    {
        Cache::put("sso_login:{$token}", $claims->toArray(), now()->addSeconds(30));
    }

    private function makeClaims(User|null $user = null): SsoUserClaimsDto
    {
        $user ??= $this->user;

        return new SsoUserClaimsDto(
            sub: 'sub-' . $user->id,
            email: $user->email,
            name: $user->first_name . ' ' . $user->last_name,
            email_verified: true,
            tenant_id: $this->tenant->id,
            products: ['flow-ledger'],
            roles: [],
        );
    }

    public function test_finalize_returns_403_when_token_is_missing(): void
    {
        $this->get(route('sso.finalize'))->assertForbidden();
    }

    public function test_finalize_returns_403_when_token_is_invalid_or_expired(): void
    {
        $this->get(route('sso.finalize', ['token' => 'invalid-token']))->assertForbidden();
    }

    public function test_finalize_logs_in_user_and_redirects_to_dashboard(): void
    {
        $token = 'valid-token-' . uniqid();
        $claims = $this->makeClaims();

        $this->storeSsoToken($token, $claims);

        $this->mock(SsoUserProvisioningService::class)
            ->shouldReceive('findOrCreateTenantUser')
            ->once()
            ->andReturn($this->user);

        $response = $this->get(route('sso.finalize', ['token' => $token]));

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->user);
    }

    public function test_finalize_consumes_token_so_it_cannot_be_reused(): void
    {
        $token = 'one-time-token-' . uniqid();
        $claims = $this->makeClaims();

        $this->storeSsoToken($token, $claims);

        $this->mock(SsoUserProvisioningService::class)
            ->shouldReceive('findOrCreateTenantUser')
            ->once()
            ->andReturn($this->user);

        $this->get(route('sso.finalize', ['token' => $token]));

        // Second request with same token should fail
        Auth::logout();
        $this->get(route('sso.finalize', ['token' => $token]))->assertForbidden();
    }

    public function test_finalize_calls_session_invalidator_track(): void
    {
        $token = 'track-test-token-' . uniqid();
        $claims = $this->makeClaims();

        $this->storeSsoToken($token, $claims);

        $this->mock(SsoUserProvisioningService::class)
            ->shouldReceive('findOrCreateTenantUser')
            ->andReturn($this->user);

        $mockInvalidator = $this->mock(SessionInvalidatorInterface::class);
        $mockInvalidator->shouldReceive('track')
            ->once()
            ->with($this->user->id, \Mockery::type('string'));

        $this->get(route('sso.finalize', ['token' => $token]));
    }
}
