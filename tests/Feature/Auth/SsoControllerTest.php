<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Data\Auth\SsoUserClaimsDto;
use App\Services\SsoClientService;
use App\Services\SsoUserProvisioningService;
use Tests\LandlordTestCase;

class SsoControllerTest extends LandlordTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'sso.idp_url' => 'https://idp.test',
            'sso.client_id' => 'test-client',
            'sso.redirect_uri' => 'https://app.test/auth/sso/callback',
            'sso.scopes' => ['openid', 'email', 'profile'],
            'sso.product_slug' => 'flow-ledger',
        ]);
    }

    // ── redirect ──────────────────────────────────────────────────────────────

    public function test_redirect_sends_user_to_idp_authorization_url(): void
    {
        $response = $this->get(route('sso.redirect'));

        $response->assertRedirectContains('https://idp.test/oauth/authorize');
        $response->assertRedirectContains('response_type=code');
        $response->assertRedirectContains('client_id=test-client');
    }

    public function test_redirect_stores_pkce_verifier_in_session(): void
    {
        $response = $this->get(route('sso.redirect'));

        $response->assertRedirect();
        $location = $response->headers->get('Location', '');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $params);
        $state = $params['state'] ?? '';

        $this->assertNotEmpty($state);
        $this->assertTrue($response->baseResponse->getSession()->has("sso_pkce:{$state}"));
    }

    public function test_redirect_stores_valid_return_to_url_in_session(): void
    {
        $returnTo = route('landlord.tenants.index');

        $response = $this->get(route('sso.redirect', ['return_to' => $returnTo]));

        $response->assertRedirect();
        $location = $response->headers->get('Location', '');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $params);
        $state = $params['state'] ?? '';

        $this->assertTrue($response->baseResponse->getSession()->has("sso_return:{$state}"));
    }

    public function test_redirect_does_not_store_external_return_to_url(): void
    {
        $response = $this->get(route('sso.redirect', ['return_to' => 'https://evil.com/steal']));

        $response->assertRedirect();
        $location = $response->headers->get('Location', '');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $params);
        $state = $params['state'] ?? '';

        $this->assertFalse($response->baseResponse->getSession()->has("sso_return:{$state}"));
    }

    // ── callback ──────────────────────────────────────────────────────────────

    public function test_callback_aborts_when_state_is_missing(): void
    {
        $this->get(route('sso.callback', ['code' => 'abc']))->assertForbidden();
    }

    public function test_callback_aborts_when_code_is_missing(): void
    {
        $this->get(route('sso.callback', ['state' => 'some-state']))->assertForbidden();
    }

    public function test_callback_redirects_with_error_when_state_is_invalid(): void
    {
        $this->mock(SsoClientService::class)
            ->shouldReceive('validateAndConsumeState')
            ->once()
            ->andReturn(false);

        $response = $this->get(route('sso.callback', ['state' => 'bad-state', 'code' => 'abc']));

        $response->assertForbidden();
    }

    public function test_callback_redirects_with_error_when_pkce_verifier_is_missing(): void
    {
        $client = $this->mock(SsoClientService::class);
        $client->shouldReceive('validateAndConsumeState')->andReturn(true);

        // No PKCE verifier in session — callback should abort
        $response = $this->get(route('sso.callback', ['state' => 'valid-state', 'code' => 'abc']));

        $response->assertForbidden();
    }

    public function test_callback_redirects_with_error_when_token_exchange_fails(): void
    {
        $state = 'test-state-' . uniqid();

        $client = $this->mock(SsoClientService::class);
        $client->shouldReceive('validateAndConsumeState')->andReturn(true);
        $client->shouldReceive('exchangeCodeForTokens')->andThrow(new \RuntimeException('Exchange failed'));

        $response = $this->withSession(["sso_pkce:{$state}" => 'test-verifier'])
            ->get(route('sso.callback', ['state' => $state, 'code' => 'abc']));

        $response->assertForbidden();
    }

    public function test_callback_redirects_with_error_when_userinfo_fetch_fails(): void
    {
        $state = 'test-state-' . uniqid();

        $client = $this->mock(SsoClientService::class);
        $client->shouldReceive('validateAndConsumeState')->andReturn(true);
        $client->shouldReceive('exchangeCodeForTokens')->andReturn(['access_token' => 'tok']);
        $client->shouldReceive('fetchUserInfo')->andThrow(new \RuntimeException('Userinfo failed'));

        $response = $this->withSession(["sso_pkce:{$state}" => 'test-verifier'])
            ->get(route('sso.callback', ['state' => $state, 'code' => 'abc']));

        $response->assertForbidden();
    }

    public function test_callback_aborts_when_user_has_no_product_access(): void
    {
        $state = 'test-state-' . uniqid();

        $claims = new SsoUserClaimsDto('sub-1', 'user@test.com', 'Test User', true, 'tid-1', []);

        $client = $this->mock(SsoClientService::class);
        $client->shouldReceive('validateAndConsumeState')->andReturn(true);
        $client->shouldReceive('exchangeCodeForTokens')->andReturn(['access_token' => 'tok']);
        $client->shouldReceive('fetchUserInfo')->andReturn($claims);

        $response = $this->withSession(["sso_pkce:{$state}" => 'test-verifier'])
            ->get(route('sso.callback', ['state' => $state, 'code' => 'abc']));

        $response->assertForbidden();
    }

    public function test_callback_logs_in_landlord_user_and_redirects_to_tenants(): void
    {
        $state = 'test-state-' . uniqid();

        // Landlord user: tenant_id is null
        $claims = new SsoUserClaimsDto('sub-landlord', 'admin@idp.test', 'Admin', true, null, []);

        $client = $this->mock(SsoClientService::class);
        $client->shouldReceive('validateAndConsumeState')->andReturn(true);
        $client->shouldReceive('exchangeCodeForTokens')->andReturn(['access_token' => 'tok']);
        $client->shouldReceive('fetchUserInfo')->andReturn($claims);

        $this->mock(SsoUserProvisioningService::class)
            ->shouldReceive('findOrCreateLandlordUser')
            ->once()
            ->with($claims)
            ->andReturn($this->landlordUser);

        $response = $this->withSession(["sso_pkce:{$state}" => 'verifier'])
            ->get(route('sso.callback', ['state' => $state, 'code' => 'abc']));

        $response->assertRedirect(route('landlord.tenants.index'));
        $this->assertAuthenticatedAs($this->landlordUser, 'landlord');
    }

    public function test_callback_routes_tenant_user_to_tenant_domain(): void
    {
        $state = 'test-state-' . uniqid();
        $tid = 'tid-' . uniqid();

        $this->tenant->update(['idp_tenant_id' => $tid]);
        $this->tenant->domains()->create(['domain' => 'test-company.localhost']);

        $claims = new SsoUserClaimsDto('sub-1', 'user@test.com', 'Test User', true, $tid, ['flow-ledger']);

        $client = $this->mock(SsoClientService::class);
        $client->shouldReceive('validateAndConsumeState')->andReturn(true);
        $client->shouldReceive('exchangeCodeForTokens')->andReturn(['access_token' => 'tok']);
        $client->shouldReceive('fetchUserInfo')->andReturn($claims);

        $response = $this->withSession(["sso_pkce:{$state}" => 'verifier'])
            ->get(route('sso.callback', ['state' => $state, 'code' => 'abc']));

        $response->assertRedirect();
        $location = $response->headers->get('Location', '');
        $this->assertStringContainsString('test-company.localhost', $location);
        $this->assertStringContainsString('/auth/sso/finalize', $location);
        $this->assertStringContainsString('token=', $location);
    }

    public function test_callback_aborts_when_tenant_not_registered(): void
    {
        $state = 'test-state-' . uniqid();

        $claims = new SsoUserClaimsDto('sub-1', 'user@test.com', 'Test', true, 'unknown-tid', ['flow-ledger']);

        $client = $this->mock(SsoClientService::class);
        $client->shouldReceive('validateAndConsumeState')->andReturn(true);
        $client->shouldReceive('exchangeCodeForTokens')->andReturn(['access_token' => 'tok']);
        $client->shouldReceive('fetchUserInfo')->andReturn($claims);

        $this->withSession(["sso_pkce:{$state}" => 'verifier'])
            ->get(route('sso.callback', ['state' => $state, 'code' => 'abc']))
            ->assertForbidden();
    }

    // ── logout ────────────────────────────────────────────────────────────────

    public function test_logout_redirects_to_idp_end_session_endpoint(): void
    {
        $this->actingAs($this->landlordUser, 'landlord')
            ->post(route('sso.logout'))
            ->assertRedirect('https://idp.test/connect/end-session');
    }

    public function test_logout_clears_authenticated_session(): void
    {
        $this->actingAs($this->landlordUser, 'landlord')
            ->post(route('sso.logout'));

        $this->assertGuest('landlord');
    }
}
