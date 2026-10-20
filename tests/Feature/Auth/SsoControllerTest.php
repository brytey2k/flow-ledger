<?php

declare(strict_types=1);

uses(Tests\LandlordTestCase::class);
use App\Data\Auth\SsoUserClaimsDto;
use App\Services\SsoClientService;
use App\Services\SsoUserProvisioningService;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    config([
        'sso.idp_url' => 'https://idp.test',
        'sso.client_id' => 'test-client',
        'sso.redirect_uri' => 'https://app.test/auth/sso/callback',
        'sso.scopes' => ['openid', 'email', 'profile'],
        'sso.product_slug' => 'flow-ledger',
    ]);
});
test('redirect sends user to idp authorization url', function () {
    $response = $this->get(route('sso.redirect'));

    $response->assertRedirectContains('https://idp.test/oauth/authorize');
    $response->assertRedirectContains('response_type=code');
    $response->assertRedirectContains('client_id=test-client');
});
test('redirect stores pkce verifier in cache', function () {
    $response = $this->get(route('sso.redirect'));

    $response->assertRedirect();
    $location = $response->headers->get('Location', '');
    parse_str((string) parse_url($location, PHP_URL_QUERY), $params);
    $state = $params['state'] ?? '';

    expect($state)->not->toBeEmpty();
    expect(Cache::has("sso_pkce:{$state}"))->toBeTrue();
});
test('redirect stores valid return to url in cache', function () {
    $returnTo = route('landlord.tenants.index');

    $response = $this->get(route('sso.redirect', ['return_to' => $returnTo]));

    $response->assertRedirect();
    $location = $response->headers->get('Location', '');
    parse_str((string) parse_url($location, PHP_URL_QUERY), $params);
    $state = $params['state'] ?? '';

    expect(Cache::has("sso_return:{$state}"))->toBeTrue();
});
test('redirect sets sso state cookie', function () {
    $response = $this->get(route('sso.redirect'));

    $response->assertRedirect();
    $location = $response->headers->get('Location', '');
    parse_str((string) parse_url($location, PHP_URL_QUERY), $params);
    $state = $params['state'] ?? '';

    expect($state)->not->toBeEmpty();
    $response->assertCookie('sso_state', $state);
});
test('redirect does not store external return to url', function () {
    $response = $this->get(route('sso.redirect', ['return_to' => 'https://evil.com/steal']));

    $response->assertRedirect();
    $location = $response->headers->get('Location', '');
    parse_str((string) parse_url($location, PHP_URL_QUERY), $params);
    $state = $params['state'] ?? '';

    expect(Cache::has("sso_return:{$state}"))->toBeFalse();
});
test('redirect does not store backslash return to url', function () {
    // Browsers normalise "/\evil.com" to "//evil.com" (protocol-relative to
    // evil.com), while PHP's parse_url() sees only a host-less path.
    $response = $this->get(route('sso.redirect', ['return_to' => '/\\evil.com']));

    $response->assertRedirect();
    $location = $response->headers->get('Location', '');
    parse_str((string) parse_url($location, PHP_URL_QUERY), $params);
    $state = $params['state'] ?? '';

    expect(Cache::has("sso_return:{$state}"))->toBeFalse();
});
test('callback aborts when state is missing', function () {
    $this->get(route('sso.callback', ['code' => 'abc']))->assertForbidden();
});
test('callback aborts when code is missing', function () {
    $this->get(route('sso.callback', ['state' => 'some-state']))->assertForbidden();
});
test('callback redirects with error when state is invalid', function () {
    $this->mock(SsoClientService::class)
        ->shouldReceive('validateAndConsumeState')
        ->once()
        ->andReturn(false);

    $response = $this->get(route('sso.callback', ['state' => 'bad-state', 'code' => 'abc']));

    $response->assertForbidden();
});
test('callback redirects with error when pkce verifier is missing', function () {
    $client = $this->mock(SsoClientService::class);
    $client->shouldReceive('validateAndConsumeState')->andReturn(true);

    // No PKCE verifier in session — callback should abort
    $response = $this->get(route('sso.callback', ['state' => 'valid-state', 'code' => 'abc']));

    $response->assertForbidden();
});
test('callback aborts when sso state cookie does not match state', function () {
    $state = 'test-state-' . uniqid();

    $client = $this->mock(SsoClientService::class);
    $client->shouldReceive('validateAndConsumeState')->andReturn(true);

    Cache::put("sso_pkce:{$state}", 'test-verifier', now()->addMinutes(5));

    $response = $this->withCookie('sso_state', 'a-different-state')
        ->get(route('sso.callback', ['state' => $state, 'code' => 'abc']));

    $response->assertForbidden();
});
test('callback redirects with error when token exchange fails', function () {
    $state = 'test-state-' . uniqid();

    $client = $this->mock(SsoClientService::class);
    $client->shouldReceive('validateAndConsumeState')->andReturn(true);
    $client->shouldReceive('exchangeCodeForTokens')->andThrow(new RuntimeException('Exchange failed'));

    Cache::put("sso_pkce:{$state}", 'test-verifier', now()->addMinutes(5));
    $response = $this->withCookie('sso_state', $state)
        ->get(route('sso.callback', ['state' => $state, 'code' => 'abc']));

    $response->assertForbidden();
});
test('callback redirects with error when userinfo fetch fails', function () {
    $state = 'test-state-' . uniqid();

    $client = $this->mock(SsoClientService::class);
    $client->shouldReceive('validateAndConsumeState')->andReturn(true);
    $client->shouldReceive('exchangeCodeForTokens')->andReturn(['access_token' => 'tok']);
    $client->shouldReceive('fetchUserInfo')->andThrow(new RuntimeException('Userinfo failed'));

    Cache::put("sso_pkce:{$state}", 'test-verifier', now()->addMinutes(5));
    $response = $this->withCookie('sso_state', $state)
        ->get(route('sso.callback', ['state' => $state, 'code' => 'abc']));

    $response->assertForbidden();
});
test('callback aborts when user has no product access', function () {
    $state = 'test-state-' . uniqid();

    $claims = new SsoUserClaimsDto('sub-1', 'user@test.com', 'Test User', true, 'tid-1', []);

    $client = $this->mock(SsoClientService::class);
    $client->shouldReceive('validateAndConsumeState')->andReturn(true);
    $client->shouldReceive('exchangeCodeForTokens')->andReturn(['access_token' => 'tok']);
    $client->shouldReceive('fetchUserInfo')->andReturn($claims);

    Cache::put("sso_pkce:{$state}", 'test-verifier', now()->addMinutes(5));
    $response = $this->withCookie('sso_state', $state)
        ->get(route('sso.callback', ['state' => $state, 'code' => 'abc']));

    $response->assertForbidden();
});
test('callback logs in landlord user and redirects to tenants', function () {
    $state = 'test-state-' . uniqid();

    // Landlord user: tenant_id is null
    $claims = new SsoUserClaimsDto('sub-landlord', 'admin@idp.test', 'Admin', true, null, []);

    $client = $this->mock(SsoClientService::class);
    $client->shouldReceive('validateAndConsumeState')->andReturn(true);
    $client->shouldReceive('exchangeCodeForTokens')->andReturn(['access_token' => 'tok']);
    $client->shouldReceive('fetchUserInfo')->andReturn($claims);

    $this->mock(SsoUserProvisioningService::class)
        ->shouldReceive('findLandlordUser')
        ->once()
        ->with($claims)
        ->andReturn($this->landlordUser);

    Cache::put("sso_pkce:{$state}", 'verifier', now()->addMinutes(5));
    $response = $this->withCookie('sso_state', $state)
        ->get(route('sso.callback', ['state' => $state, 'code' => 'abc']));

    $response->assertRedirect(route('landlord.tenants.index'));
    $this->assertAuthenticatedAs($this->landlordUser, 'landlord');
});
test('callback refuses a tenantless identity that is not a known landlord user', function () {
    $state = 'test-state-' . uniqid();
    $claims = new SsoUserClaimsDto('sub-stranger', 'stranger@idp.test', 'Stranger', true, null, []);

    $client = $this->mock(SsoClientService::class);
    $client->shouldReceive('validateAndConsumeState')->andReturn(true);
    $client->shouldReceive('exchangeCodeForTokens')->andReturn(['access_token' => 'tok']);
    $client->shouldReceive('fetchUserInfo')->andReturn($claims);

    Cache::put("sso_pkce:{$state}", 'verifier', now()->addMinutes(5));
    $this->withCookie('sso_state', $state)
        ->get(route('sso.callback', ['state' => $state, 'code' => 'abc']))
        ->assertForbidden();

    $this->assertGuest('landlord');
    expect(App\Models\Landlord\User::query()->where('email', 'stranger@idp.test')->exists())->toBeFalse();
});
test('redirect only stores same-origin paths and absolute urls on known hosts as return_to', function (string $returnTo, bool $stored) {
    $response = $this->get(route('sso.redirect', ['return_to' => $returnTo]));
    $response->assertRedirect();
    parse_str((string) parse_url($response->headers->get('Location', ''), PHP_URL_QUERY), $params);
    $state = $params['state'] ?? '';

    expect($state)->not->toBeEmpty();
    expect(Cache::has("sso_return:{$state}"))->toBe($stored);
})->with([
    'scheme without host' => ['http:evil.com', false],
    'scheme and single slash' => ['http:/evil.com', false],
    'protocol relative' => ['//evil.com', false],
    'backslash' => ['/\\evil.com', false],
    'tab smuggled in' => ["/\t/evil.com", false],
    'javascript scheme' => ['javascript:alert(1)', false],
    'plain path' => ['/dashboard', true],
]);
test('callback routes tenant user to tenant domain', function () {
    $state = 'test-state-' . uniqid();
    $tid = 'tid-' . uniqid();

    $this->tenant->update(['idp_tenant_id' => $tid]);
    $this->tenant->domains()->create(['domain' => 'test-company.localhost']);

    $claims = new SsoUserClaimsDto('sub-1', 'user@test.com', 'Test User', true, $tid, ['flow-ledger']);

    $client = $this->mock(SsoClientService::class);
    $client->shouldReceive('validateAndConsumeState')->andReturn(true);
    $client->shouldReceive('exchangeCodeForTokens')->andReturn(['access_token' => 'tok']);
    $client->shouldReceive('fetchUserInfo')->andReturn($claims);

    Cache::put("sso_pkce:{$state}", 'verifier', now()->addMinutes(5));
    $response = $this->withCookie('sso_state', $state)
        ->get(route('sso.callback', ['state' => $state, 'code' => 'abc']));

    $response->assertRedirect();
    $location = $response->headers->get('Location', '');
    $this->assertStringContainsString('test-company.localhost', $location);
    $this->assertStringContainsString('/auth/sso/finalize', $location);
    $this->assertStringContainsString('token=', $location);
});
test('callback aborts when tenant not registered', function () {
    $state = 'test-state-' . uniqid();

    $claims = new SsoUserClaimsDto('sub-1', 'user@test.com', 'Test', true, 'unknown-tid', ['flow-ledger']);

    $client = $this->mock(SsoClientService::class);
    $client->shouldReceive('validateAndConsumeState')->andReturn(true);
    $client->shouldReceive('exchangeCodeForTokens')->andReturn(['access_token' => 'tok']);
    $client->shouldReceive('fetchUserInfo')->andReturn($claims);

    Cache::put("sso_pkce:{$state}", 'verifier', now()->addMinutes(5));
    $this->withCookie('sso_state', $state)
        ->get(route('sso.callback', ['state' => $state, 'code' => 'abc']))
        ->assertForbidden();
});
test('logout redirects to idp end session endpoint', function () {
    $this->actingAs($this->landlordUser, 'landlord')
        ->post(route('sso.logout'))
        ->assertRedirect('https://idp.test/connect/end-session');
});
test('logout clears authenticated session', function () {
    $this->actingAs($this->landlordUser, 'landlord')
        ->post(route('sso.logout'));

    $this->assertGuest('landlord');
});
test('redirect remembers a valid binding hash and ignores a malformed one', function (string $bind, bool $stored) {
    $response = $this->get(route('sso.redirect', ['bind' => $bind]));
    parse_str((string) parse_url($response->headers->get('Location', ''), PHP_URL_QUERY), $params);
    $state = $params['state'] ?? '';

    expect($state)->not->toBeEmpty();
    expect(Cache::has("sso_bind:{$state}"))->toBe($stored);
})->with([
    'sha256 hex' => [str_repeat('a1', 32), true],
    'too short' => ['abc', false],
    'not hex' => [str_repeat('zz', 32), false],
]);
test('callback hands the binding hash to the tenant with the one-time token', function () {
    $state = 'test-state-' . uniqid();
    $tid = 'tid-' . uniqid();
    $hash = str_repeat('b2', 32);
    $this->tenant->update(['idp_tenant_id' => $tid]);
    $this->tenant->domains()->create(['domain' => 'bound-company.localhost']);
    $claims = new SsoUserClaimsDto('sub-1', 'user@test.com', 'Test User', true, $tid, ['flow-ledger']);

    $client = $this->mock(SsoClientService::class);
    $client->shouldReceive('validateAndConsumeState')->andReturn(true);
    $client->shouldReceive('exchangeCodeForTokens')->andReturn(['access_token' => 'tok']);
    $client->shouldReceive('fetchUserInfo')->andReturn($claims);
    Cache::put("sso_pkce:{$state}", 'verifier', now()->addMinutes(5));
    Cache::put("sso_bind:{$state}", $hash, now()->addMinutes(5));

    $location = (string) $this->withCookie('sso_state', $state)
        ->get(route('sso.callback', ['state' => $state, 'code' => 'abc']))
        ->headers->get('Location');
    parse_str((string) parse_url($location, PHP_URL_QUERY), $params);
    $payload = Cache::store(config()->string('cache.default'))->get("sso_login:{$this->tenant->id}:{$params['token']}");

    expect($payload['bind'])->toBe($hash)->and($payload['email'])->toBe('user@test.com');
    expect(Cache::has("sso_bind:{$state}"))->toBeFalse();
});
