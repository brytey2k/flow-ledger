<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Jobs\CreateTenantFromIdpJob;
use App\Jobs\ReportTenantProvisionedToIdpJob;
use App\Models\Tenant;
use App\Services\SsoClientService;
use DateTimeImmutable;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Tests\LandlordTestCase;

/**
 * Exercises the central-domain POST /api/idp/tenants endpoint end-to-end by
 * minting RS256 provision tokens with a locally generated RSA keypair and
 * stubbing only the public key source (SsoClientService::getIdpPublicKeyPem()).
 * Signature verification, issuer, audience, expiry and claim validation all
 * run for real.
 */
class IdpTenantProvisionEndpointTest extends LandlordTestCase
{
    private const IDP_URL = 'https://idp.test';

    private const PRODUCT = 'flow-ledger';

    private const IDP_TENANT_ID = 'iam-tenant-42';

    private const SUBDOMAIN = 'acme-idp';

    private const ENVIRONMENT_KEY = 'env-key-abc';

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

        config([
            'sso.idp_url' => self::IDP_URL,
            'sso.product_slug' => self::PRODUCT,
        ]);

        $this->mock(SsoClientService::class)
            ->shouldReceive('getIdpPublicKeyPem')
            ->andReturn(static::$publicKeyPem);

        Queue::fake();
    }

    // ── Token verification failures ───────────────────────────────────────────

    public function test_missing_provision_token_returns_422(): void
    {
        $this->post('/api/idp/tenants')
            ->assertStatus(422);

        Queue::assertNothingPushed();
    }

    public function test_garbage_token_returns_401(): void
    {
        $this->post('/api/idp/tenants', ['provision_token' => 'not-a-jwt'])
            ->assertStatus(401);

        Queue::assertNothingPushed();
    }

    public function test_expired_token_returns_401(): void
    {
        $now = new DateTimeImmutable('now', new \DateTimeZone('UTC'));

        $token = $this->mintProvisionToken(
            issuedAt: $now->modify('-2 hours'),
            expiresAt: $now->modify('-1 hour'),
        );

        $this->post('/api/idp/tenants', ['provision_token' => $token])
            ->assertStatus(401);

        Queue::assertNothingPushed();
    }

    public function test_token_with_wrong_issuer_returns_401(): void
    {
        $token = $this->mintProvisionToken(issuer: 'https://evil-idp.test');

        $this->post('/api/idp/tenants', ['provision_token' => $token])
            ->assertStatus(401);

        Queue::assertNothingPushed();
    }

    public function test_token_with_wrong_audience_returns_401(): void
    {
        $token = $this->mintProvisionToken(audience: 'some-other-product');

        $this->post('/api/idp/tenants', ['provision_token' => $token])
            ->assertStatus(401);

        Queue::assertNothingPushed();
    }

    // ── Claim validation failures ─────────────────────────────────────────────

    public function test_token_with_missing_claims_returns_422(): void
    {
        $token = $this->mintProvisionToken(claims: ['environment_key' => null]);

        $this->post('/api/idp/tenants', ['provision_token' => $token])
            ->assertStatus(422);

        Queue::assertNothingPushed();
    }

    public function test_token_with_invalid_subdomain_returns_422(): void
    {
        $token = $this->mintProvisionToken(claims: ['subdomain' => 'Not Valid!']);

        $this->post('/api/idp/tenants', ['provision_token' => $token])
            ->assertStatus(422);

        Queue::assertNothingPushed();
    }

    public function test_token_with_overlong_subdomain_returns_422(): void
    {
        $token = $this->mintProvisionToken(claims: ['subdomain' => str_repeat('a', 51)]);

        $this->post('/api/idp/tenants', ['provision_token' => $token])
            ->assertStatus(422);

        Queue::assertNothingPushed();
    }

    // ── Accepted requests ─────────────────────────────────────────────────────

    public function test_valid_token_queues_tenant_creation(): void
    {
        $this->post('/api/idp/tenants', ['provision_token' => $this->mintProvisionToken()])
            ->assertStatus(202)
            ->assertJson(['status' => 'accepted']);

        Queue::assertPushed(
            CreateTenantFromIdpJob::class,
            static fn(CreateTenantFromIdpJob $job): bool => $job->idpTenantId === self::IDP_TENANT_ID
                && $job->name === 'Acme Corp'
                && $job->subdomain === self::SUBDOMAIN
                && $job->environmentKey === self::ENVIRONMENT_KEY,
        );
        Queue::assertNotPushed(ReportTenantProvisionedToIdpJob::class);
    }

    public function test_existing_tenant_by_idp_tenant_id_reports_already_exists(): void
    {
        $existing = new Tenant([
            'id' => 'existing-' . Str::random(6),
            'name' => 'Existing Tenant',
            'is_suspended' => false,
            'idp_tenant_id' => self::IDP_TENANT_ID,
        ]);
        $existing->saveQuietly();

        $this->post('/api/idp/tenants', ['provision_token' => $this->mintProvisionToken()])
            ->assertStatus(202)
            ->assertJson(['status' => 'accepted']);

        Queue::assertPushed(
            ReportTenantProvisionedToIdpJob::class,
            static fn(ReportTenantProvisionedToIdpJob $job): bool => $job->idpTenantId === self::IDP_TENANT_ID
                && $job->environmentKey === self::ENVIRONMENT_KEY
                && $job->status === 'already_exists',
        );
        Queue::assertNotPushed(CreateTenantFromIdpJob::class);
    }

    public function test_existing_tenant_by_subdomain_reports_already_exists(): void
    {
        $existing = new Tenant([
            'id' => self::SUBDOMAIN,
            'name' => 'Existing Tenant',
            'is_suspended' => false,
        ]);
        $existing->saveQuietly();

        $this->post('/api/idp/tenants', ['provision_token' => $this->mintProvisionToken()])
            ->assertStatus(202)
            ->assertJson(['status' => 'accepted']);

        Queue::assertPushed(
            ReportTenantProvisionedToIdpJob::class,
            static fn(ReportTenantProvisionedToIdpJob $job): bool => $job->idpTenantId === self::IDP_TENANT_ID
                && $job->environmentKey === self::ENVIRONMENT_KEY
                && $job->status === 'already_exists',
        );
        Queue::assertNotPushed(CreateTenantFromIdpJob::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * @param array<string, string|null> $claims claim overrides; null removes the claim
     * @param string|null|null $issuer
     * @param string|null|null $audience
     * @param DateTimeImmutable|null|null $issuedAt
     * @param DateTimeImmutable|null|null $expiresAt
     */
    private function mintProvisionToken(
        array $claims = [],
        string|null $issuer = null,
        string|null $audience = null,
        DateTimeImmutable|null $issuedAt = null,
        DateTimeImmutable|null $expiresAt = null,
    ): string {
        $now = new DateTimeImmutable('now', new \DateTimeZone('UTC'));

        $configuration = Configuration::forAsymmetricSigner(
            new Sha256(),
            InMemory::plainText(static::$privateKeyPem),
            InMemory::plainText(static::$publicKeyPem),
        );

        $builder = $configuration->builder()
            ->withHeader('kid', '1')
            ->issuedBy($issuer ?? self::IDP_URL)
            ->permittedFor($audience ?? self::PRODUCT)
            ->issuedAt($issuedAt ?? $now)
            ->expiresAt($expiresAt ?? $now->modify('+10 minutes'));

        $claims = array_merge([
            'idp_tenant_id' => self::IDP_TENANT_ID,
            'name' => 'Acme Corp',
            'subdomain' => self::SUBDOMAIN,
            'environment_key' => self::ENVIRONMENT_KEY,
        ], $claims);

        foreach ($claims as $claim => $value) {
            if ($value !== null) {
                $builder = $builder->withClaim($claim, $value);
            }
        }

        return $builder
            ->getToken($configuration->signer(), $configuration->signingKey())
            ->toString();
    }
}
