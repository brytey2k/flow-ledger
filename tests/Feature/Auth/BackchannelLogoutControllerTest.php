<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Services\SsoClientService;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Tests\TenantAppTestCase;

class BackchannelLogoutControllerTest extends TenantAppTestCase
{
    private static string $privateKeyPem = '';

    private static string $publicKeyPem = '';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $resource = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        openssl_pkey_export($resource, static::$privateKeyPem);
        $details = openssl_pkey_get_details($resource);
        static::$publicKeyPem = $details['key'];
    }

    protected function setUp(): void
    {
        parent::setUp();

        config(['sso.idp_url' => 'https://idp.test']);

        $this->mock(SsoClientService::class)
            ->shouldReceive('getIdpPublicKeyPem')
            ->andReturn(static::$publicKeyPem);
    }

    private function makeLogoutToken(
        string $sub,
        string $tid,
        bool $expired = false,
        bool $missingEvents = false,
        bool $wrongIssuer = false,
    ): string {
        $config = Configuration::forAsymmetricSigner(
            new Sha256(),
            InMemory::plainText(static::$privateKeyPem),
            InMemory::plainText(static::$publicKeyPem),
        );

        $issuer = $wrongIssuer ? 'https://wrong-idp.test' : 'https://idp.test';

        $builder = $config->builder()
            ->issuedBy($issuer)
            ->relatedTo($sub)
            ->withClaim('tid', $tid);

        if (! $missingEvents) {
            $builder = $builder->withClaim('events', [
                'http://schemas.openid.net/event/backchannel-logout' => (object) [],
            ]);
        }

        $expiry = $expired
            ? new \DateTimeImmutable('-1 hour')
            : new \DateTimeImmutable('+1 hour');

        $builder = $builder->expiresAt($expiry);

        return $builder->getToken($config->signer(), $config->signingKey())->toString();
    }

    // ── Missing / malformed token ─────────────────────────────────────────────

    public function test_returns_400_when_logout_token_is_missing(): void
    {
        $this->post(route('sso.backchannel-logout'))
            ->assertStatus(400)
            ->assertSeeText('Missing logout_token');
    }

    public function test_returns_400_when_logout_token_is_empty_string(): void
    {
        $this->post(route('sso.backchannel-logout'), ['logout_token' => ''])
            ->assertStatus(400)
            ->assertSeeText('Missing logout_token');
    }

    public function test_returns_400_when_logout_token_is_not_a_valid_jwt(): void
    {
        // Three parts (two dots) satisfies the structure check but invalid base64/JSON content
        // causes a parse error which the controller now converts to a RuntimeException → 400.
        $this->post(route('sso.backchannel-logout'), ['logout_token' => 'aaa.bbb.ccc'])
            ->assertStatus(400)
            ->assertSeeText('Invalid logout token');
    }

    public function test_returns_400_when_token_is_expired(): void
    {
        $token = $this->makeLogoutToken('sub-abc', 'tid-abc', expired: true);

        $this->post(route('sso.backchannel-logout'), ['logout_token' => $token])
            ->assertStatus(400)
            ->assertSeeText('Invalid logout token');
    }

    public function test_returns_400_when_token_has_wrong_issuer(): void
    {
        $token = $this->makeLogoutToken('sub-abc', 'tid-abc', wrongIssuer: true);

        $this->post(route('sso.backchannel-logout'), ['logout_token' => $token])
            ->assertStatus(400)
            ->assertSeeText('Invalid logout token');
    }

    public function test_returns_400_when_token_is_missing_backchannel_logout_event_claim(): void
    {
        $token = $this->makeLogoutToken('sub-abc', 'tid-abc', missingEvents: true);

        $this->post(route('sso.backchannel-logout'), ['logout_token' => $token])
            ->assertStatus(400)
            ->assertSeeText('Invalid logout token');
    }

    public function test_returns_400_when_public_key_is_unavailable(): void
    {
        $this->mock(SsoClientService::class)
            ->shouldReceive('getIdpPublicKeyPem')
            ->andReturn('');

        $token = $this->makeLogoutToken('sub-abc', 'tid-abc');

        $this->post(route('sso.backchannel-logout'), ['logout_token' => $token])
            ->assertStatus(400)
            ->assertSeeText('Invalid logout token');
    }

    // ── Tenant not found ──────────────────────────────────────────────────────

    public function test_returns_200_when_idp_tenant_id_does_not_match_any_tenant(): void
    {
        $token = $this->makeLogoutToken('sub-abc', 'unknown-tid-' . uniqid());

        $this->post(route('sso.backchannel-logout'), ['logout_token' => $token])
            ->assertStatus(200)
            ->assertSeeText('');
    }

    // ── User not found in tenant ──────────────────────────────────────────────

    public function test_returns_200_when_user_not_found_in_tenant(): void
    {
        $tid = 'tid-' . uniqid();
        $this->tenant->update(['idp_tenant_id' => $tid]);

        $token = $this->makeLogoutToken('unknown-sub-' . uniqid(), $tid);

        $this->post(route('sso.backchannel-logout'), ['logout_token' => $token])
            ->assertStatus(200);

        // Re-initialize tenancy so tearDown can roll back tenant transaction
        tenancy()->initialize($this->tenant);
    }
}
