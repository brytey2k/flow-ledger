<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Data\Auth\SsoUserClaimsDto;
use App\Exceptions\UnverifiedEmailException;
use App\Interfaces\SessionInvalidatorInterface;
use App\Models\Tenant\User;
use App\Services\SsoUserProvisioningService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    // The automated-tests tenant fixture doesn't set idp_tenant_id; the
    // finalize controller now requires it to match the SSO claims.
    $this->tenant->forceFill(['idp_tenant_id' => 'idp-' . $this->tenant->id])->save();
});
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
function storeSsoTokenForSsoFinalizeController(string $token, SsoUserClaimsDto $claims, string|null $forTenantId = null): void
{
    $forTenantId ??= (string) test()->tenant->id;

    Cache::store(config()->string('cache.default'))
        ->put("sso_login:{$forTenantId}:{$token}", $claims->toArray(), now()->addSeconds(30));
}
function makeClaimsForSsoFinalizeController(User|null $user = null, string|null $tenantId = null): SsoUserClaimsDto
{
    $user ??= test()->user;

    return new SsoUserClaimsDto(
        sub: 'sub-' . $user->id,
        email: $user->email,
        name: $user->first_name . ' ' . $user->last_name,
        email_verified: true,
        tenant_id: $tenantId ?? test()->tenant->idp_tenant_id,
        products: ['flow-ledger'],
        roles: [],
    );
}
test('finalize returns 403 when token is missing', function () {
    $this->get(route('sso.finalize'))->assertForbidden();
});
test('finalize returns 403 when token is invalid or expired', function () {
    $this->get(route('sso.finalize', ['token' => 'invalid-token']))->assertForbidden();
});
test('finalize cannot read a token written through the tenant tagged cache', function () {
    $token = 'tenant-tagged-token-' . uniqid();
    Cache::put("sso_login:{$token}", makeClaimsForSsoFinalizeController()->toArray(), now()->addSeconds(30));

    $this->get(route('sso.finalize', ['token' => $token]))->assertForbidden();
});
test('finalize returns 403 when token was minted for a different tenant', function () {
    $token = 'cross-tenant-token-' . uniqid();
    storeSsoTokenForSsoFinalizeController($token, makeClaimsForSsoFinalizeController(), forTenantId: 'some-other-tenant-id');

    $this->get(route('sso.finalize', ['token' => $token]))->assertForbidden();
});
test('finalize returns 403 when claims tenant id does not match resolved tenant', function () {
    $token = 'mismatched-claims-token-' . uniqid();
    $claims = makeClaimsForSsoFinalizeController(tenantId: 'some-other-idp-tenant-id');

    storeSsoTokenForSsoFinalizeController($token, $claims);

    $this->get(route('sso.finalize', ['token' => $token]))->assertForbidden();
    $this->assertGuest();
});
test('finalize logs in user and redirects to dashboard', function () {
    $token = 'valid-token-' . uniqid();
    $claims = makeClaimsForSsoFinalizeController();

    storeSsoTokenForSsoFinalizeController($token, $claims);

    $this->mock(SsoUserProvisioningService::class)
        ->shouldReceive('findOrCreateTenantUser')
        ->once()
        ->andReturn($this->user);

    $response = $this->get(route('sso.finalize', ['token' => $token]));

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($this->user);
});
test('finalize consumes token so it cannot be reused', function () {
    $token = 'one-time-token-' . uniqid();
    $claims = makeClaimsForSsoFinalizeController();

    storeSsoTokenForSsoFinalizeController($token, $claims);

    $this->mock(SsoUserProvisioningService::class)
        ->shouldReceive('findOrCreateTenantUser')
        ->once()
        ->andReturn($this->user);

    $this->get(route('sso.finalize', ['token' => $token]));

    // Second request with same token should fail
    Auth::logout();
    $this->get(route('sso.finalize', ['token' => $token]))->assertForbidden();
});
test('finalize redirects to login with specific error when email is unverified', function () {
    $token = 'unverified-token-' . uniqid();
    $claims = makeClaimsForSsoFinalizeController();

    storeSsoTokenForSsoFinalizeController($token, $claims);

    $this->mock(SsoUserProvisioningService::class)
        ->shouldReceive('findOrCreateTenantUser')
        ->once()
        ->andThrow(new UnverifiedEmailException());

    $response = $this->get(route('sso.finalize', ['token' => $token]));

    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});
test('finalize calls session invalidator track', function () {
    $token = 'track-test-token-' . uniqid();
    $claims = makeClaimsForSsoFinalizeController();

    storeSsoTokenForSsoFinalizeController($token, $claims);

    $this->mock(SsoUserProvisioningService::class)
        ->shouldReceive('findOrCreateTenantUser')
        ->andReturn($this->user);

    $mockInvalidator = $this->mock(SessionInvalidatorInterface::class);
    $mockInvalidator->shouldReceive('track')
        ->once()
        ->with($this->user->id, Mockery::type('string'));

    $this->get(route('sso.finalize', ['token' => $token]));
});
