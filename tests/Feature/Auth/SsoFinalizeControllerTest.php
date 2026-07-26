<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Data\Auth\SsoUserClaimsDto;
use App\Exceptions\UnverifiedEmailException;
use App\Interfaces\SessionInvalidatorInterface;
use App\Models\Tenant\User;
use App\Services\SsoUserProvisioningService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Tests\TenantAppTestCase;

class SsoFinalizeControllerTest extends TenantAppTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        // The automated-tests tenant fixture doesn't set idp_tenant_id; the
        // finalize controller now requires it to match the SSO claims.
        $this->tenant->forceFill(['idp_tenant_id' => 'idp-' . $this->tenant->id])->save();
    }

    /**
     * Mirrors SsoController::routeToTenant(), which writes this token on the
     * central domain via the explicit store name — bypassing Stancl's
     * tenant cache tagging, since the finalize request that reads it back
     * arrives on a tenant subdomain where tenancy has already tagged the
     * Cache facade. The key is scoped to the tenant it was minted for so a
     * token cannot be replayed against a different tenant's subdomain.
     *
     * @param string $token
     * @param SsoUserClaimsDto $claims
     * @param string|null $forTenantId Defaults to the current test tenant.
     */
    private function storeSsoToken(string $token, SsoUserClaimsDto $claims, string|null $forTenantId = null): void
    {
        $forTenantId ??= (string) $this->tenant->id;

        Cache::store(config()->string('cache.default'))
            ->put("sso_login:{$forTenantId}:{$token}", $claims->toArray(), now()->addSeconds(30));
    }

    private function makeClaims(User|null $user = null, string|null $tenantId = null): SsoUserClaimsDto
    {
        $user ??= $this->user;

        return new SsoUserClaimsDto(
            sub: 'sub-' . $user->id,
            email: $user->email,
            name: $user->first_name . ' ' . $user->last_name,
            email_verified: true,
            tenant_id: $tenantId ?? $this->tenant->idp_tenant_id,
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

    public function test_finalize_cannot_read_a_token_written_through_the_tenant_tagged_cache(): void
    {
        $token = 'tenant-tagged-token-' . uniqid();
        Cache::put("sso_login:{$token}", $this->makeClaims()->toArray(), now()->addSeconds(30));

        $this->get(route('sso.finalize', ['token' => $token]))->assertForbidden();
    }

    public function test_finalize_returns_403_when_token_was_minted_for_a_different_tenant(): void
    {
        $token = 'cross-tenant-token-' . uniqid();
        $this->storeSsoToken($token, $this->makeClaims(), forTenantId: 'some-other-tenant-id');

        $this->get(route('sso.finalize', ['token' => $token]))->assertForbidden();
    }

    public function test_finalize_returns_403_when_claims_tenant_id_does_not_match_resolved_tenant(): void
    {
        $token = 'mismatched-claims-token-' . uniqid();
        $claims = $this->makeClaims(tenantId: 'some-other-idp-tenant-id');

        $this->storeSsoToken($token, $claims);

        $this->get(route('sso.finalize', ['token' => $token]))->assertForbidden();
        $this->assertGuest();
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

    public function test_finalize_redirects_to_login_with_specific_error_when_email_is_unverified(): void
    {
        $token = 'unverified-token-' . uniqid();
        $claims = $this->makeClaims();

        $this->storeSsoToken($token, $claims);

        $this->mock(SsoUserProvisioningService::class)
            ->shouldReceive('findOrCreateTenantUser')
            ->once()
            ->andThrow(new UnverifiedEmailException());

        $response = $this->get(route('sso.finalize', ['token' => $token]));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
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
