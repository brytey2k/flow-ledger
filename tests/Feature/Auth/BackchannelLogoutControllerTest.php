<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Interfaces\SessionInvalidatorInterface;
use App\Models\Tenant\User;
use App\Services\IdpBackchannelLogoutFailureReporterService;
use App\Services\SsoClientService;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;

beforeEach(function () {
    config([
        'sso.idp_url' => 'https://idp.test',
        'sso.client_id' => 'flow-ledger-client',
    ]);

    $this->mock(SsoClientService::class)
        ->shouldReceive('getIdpPublicKeyPem')
        ->andReturn(backchannelLogoutKeys()['public']);
});
/**
 * @return array{private: string, public: string}
 */
function backchannelLogoutKeys(): array
{
    static $keys = null;

    if ($keys === null) {
        $resource = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        openssl_pkey_export($resource, $privateKeyPem);
        $details = openssl_pkey_get_details($resource);
        $keys = ['private' => $privateKeyPem, 'public' => $details['key']];
    }

    return $keys;
}
function makeLogoutToken(string $sub, string $tid, bool $expired = false, bool $missingEvents = false, bool $wrongIssuer = false, bool $wrongAudience = false, bool $missingJti = false, string|null $jti = null): string
{
    $config = Configuration::forAsymmetricSigner(
        new Sha256(),
        InMemory::plainText(backchannelLogoutKeys()['private']),
        InMemory::plainText(backchannelLogoutKeys()['public']),
    );

    $issuer = $wrongIssuer ? 'https://wrong-idp.test' : 'https://idp.test';
    $audience = $wrongAudience ? 'some-other-client' : 'flow-ledger-client';

    $builder = $config->builder()
        ->issuedBy($issuer)
        ->permittedFor($audience)
        ->relatedTo($sub)
        ->withClaim('tid', $tid);

    if (! $missingJti) {
        $builder = $builder->identifiedBy($jti ?? 'jti-' . uniqid());
    }

    if (! $missingEvents) {
        $builder = $builder->withClaim('events', [
            'http://schemas.openid.net/event/backchannel-logout' => (object) [],
        ]);
    }

    $expiry = $expired
        ? new DateTimeImmutable('-1 hour')
        : new DateTimeImmutable('+1 hour');

    $builder = $builder->expiresAt($expiry);

    return $builder->getToken($config->signer(), $config->signingKey())->toString();
}
test('returns 400 when logout token is missing', function () {
    $this->post(route('sso.backchannel-logout'))
        ->assertStatus(400)
        ->assertSeeText('Missing logout_token');
});
test('returns 400 when logout token is empty string', function () {
    $this->post(route('sso.backchannel-logout'), ['logout_token' => ''])
        ->assertStatus(400)
        ->assertSeeText('Missing logout_token');
});
test('returns 400 when logout token is not a valid jwt', function () {
    // Three parts (two dots) satisfies the structure check but invalid base64/JSON content
    // causes a parse error which the controller now converts to a RuntimeException → 400.
    $this->post(route('sso.backchannel-logout'), ['logout_token' => 'aaa.bbb.ccc'])
        ->assertStatus(400)
        ->assertSeeText('Invalid logout token');
});
test('returns 400 when token is expired', function () {
    $token = makeLogoutToken('sub-abc', 'tid-abc', expired: true);

    $this->post(route('sso.backchannel-logout'), ['logout_token' => $token])
        ->assertStatus(400)
        ->assertSeeText('Invalid logout token');
});
test('returns 400 when token has wrong issuer', function () {
    $token = makeLogoutToken('sub-abc', 'tid-abc', wrongIssuer: true);

    $this->post(route('sso.backchannel-logout'), ['logout_token' => $token])
        ->assertStatus(400)
        ->assertSeeText('Invalid logout token');
});
test('returns 400 when token is missing backchannel logout event claim', function () {
    $token = makeLogoutToken('sub-abc', 'tid-abc', missingEvents: true);

    $this->post(route('sso.backchannel-logout'), ['logout_token' => $token])
        ->assertStatus(400)
        ->assertSeeText('Invalid logout token');
});
test('returns 400 when token has wrong audience', function () {
    $token = makeLogoutToken('sub-abc', 'tid-abc', wrongAudience: true);

    $this->post(route('sso.backchannel-logout'), ['logout_token' => $token])
        ->assertStatus(400)
        ->assertSeeText('Invalid logout token');
});
test('returns 400 when token is missing jti claim', function () {
    $token = makeLogoutToken('sub-abc', 'tid-abc', missingJti: true);

    $this->post(route('sso.backchannel-logout'), ['logout_token' => $token])
        ->assertStatus(400)
        ->assertSeeText('Invalid logout token');
});
test('returns 400 when logout token is replayed', function () {
    // Use a tid with no matching tenant so the request short-circuits
    // before the controller re-initializes tenancy — that re-init tears
    // down and reconnects the tenant DB connection, which would silently
    // discard this test's uncommitted transaction and defeat the assertion.
    $token = makeLogoutToken('sub-' . uniqid(), 'unknown-tid-' . uniqid());

    $this->post(route('sso.backchannel-logout'), ['logout_token' => $token])
        ->assertStatus(200);

    $this->post(route('sso.backchannel-logout'), ['logout_token' => $token])
        ->assertStatus(400)
        ->assertSeeText('Invalid logout token');
});
test('returns 400 when public key is unavailable', function () {
    $this->mock(SsoClientService::class)
        ->shouldReceive('getIdpPublicKeyPem')
        ->andReturn('');

    $token = makeLogoutToken('sub-abc', 'tid-abc');

    $this->post(route('sso.backchannel-logout'), ['logout_token' => $token])
        ->assertStatus(400)
        ->assertSeeText('Invalid logout token');
});
test('returns 200 when idp tenant id does not match any tenant', function () {
    $token = makeLogoutToken('sub-abc', 'unknown-tid-' . uniqid());

    $this->post(route('sso.backchannel-logout'), ['logout_token' => $token])
        ->assertStatus(200)
        ->assertSeeText('');
});
test('returns 200 when user not found in tenant', function () {
    $tid = 'tid-' . uniqid();
    $this->tenant->update(['idp_tenant_id' => $tid]);

    $token = makeLogoutToken('unknown-sub-' . uniqid(), $tid);

    $this->post(route('sso.backchannel-logout'), ['logout_token' => $token])
        ->assertStatus(200);

    // Re-initialize tenancy so tearDown can roll back tenant transaction
    tenancy()->initialize($this->tenant);
});
test('invalidates user sessions via session invalidator', function () {
    $tid = 'tid-' . uniqid();
    $sub = 'sub-' . uniqid();
    $this->tenant->update(['idp_tenant_id' => $tid]);

    $user = User::factory()->create(['oidc_sub' => $sub]);

    $mockInvalidator = $this->mock(SessionInvalidatorInterface::class);
    $mockInvalidator->shouldReceive('invalidate')
        ->once()
        ->with($user->id);

    $token = makeLogoutToken($sub, $tid);

    $this->post(route('sso.backchannel-logout'), ['logout_token' => $token])
        ->assertStatus(200);

    tenancy()->initialize($this->tenant);
});
test('reports failure to idp when session invalidation throws', function () {
    $tid = 'tid-' . uniqid();
    $sub = 'sub-' . uniqid();
    $this->tenant->update(['idp_tenant_id' => $tid]);

    User::factory()->create(['oidc_sub' => $sub]);

    $this->mock(SessionInvalidatorInterface::class)
        ->shouldReceive('invalidate')
        ->once()
        ->andThrow(new RuntimeException('DB connection failed'));

    $mockReporter = $this->mock(IdpBackchannelLogoutFailureReporterService::class);
    $mockReporter->shouldReceive('report')
        ->once()
        ->with(Mockery::on(fn(array $payload) => $payload['error_code'] === 'DATABASE_UNAVAILABLE'));

    $token = makeLogoutToken($sub, $tid);

    $this->post(route('sso.backchannel-logout'), ['logout_token' => $token])
        ->assertStatus(200);

    tenancy()->initialize($this->tenant);
});
