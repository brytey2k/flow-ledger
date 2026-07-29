<?php

declare(strict_types=1);

uses(Tests\LandlordTestCase::class);
use App\Jobs\CreateTenantFromIdpJob;
use App\Jobs\ReportTenantProvisionedToIdpJob;
use App\Models\Tenant;
use App\Services\SsoClientService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;

const IDP_URL_IDP_TENANT_PROVISION_ENDPOINT = 'https://idp.test';
const PRODUCT_IDP_TENANT_PROVISION_ENDPOINT = 'flow-ledger';
const IDP_TENANT_ID_IDP_TENANT_PROVISION_ENDPOINT = 'iam-tenant-42';
const SUBDOMAIN_IDP_TENANT_PROVISION_ENDPOINT = 'acme-idp';
const ENVIRONMENT_KEY_IDP_TENANT_PROVISION_ENDPOINT = 'env-key-abc';
beforeEach(function () {
    config([
        'sso.idp_url' => IDP_URL_IDP_TENANT_PROVISION_ENDPOINT,
        'sso.product_slug' => PRODUCT_IDP_TENANT_PROVISION_ENDPOINT,
    ]);

    $this->mock(SsoClientService::class)
        ->shouldReceive('getIdpPublicKeyPem')
        ->andReturn(idpProvisionKeys()['public']);

    Queue::fake();
});
/**
 * @return array{private: string, public: string}
 */
function idpProvisionKeys(): array
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
test('missing provision token returns 422', function () {
    $this->post('/api/idp/tenants')
        ->assertStatus(422);

    Queue::assertNothingPushed();
});
test('garbage token returns 401', function () {
    $this->post('/api/idp/tenants', ['provision_token' => 'not-a-jwt'])
        ->assertStatus(401);

    Queue::assertNothingPushed();
});
test('expired token returns 401', function () {
    $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));

    $token = mintProvisionToken(issuedAt: $now->modify('-2 hours'), expiresAt: $now->modify('-1 hour'));

    $this->post('/api/idp/tenants', ['provision_token' => $token])
        ->assertStatus(401);

    Queue::assertNothingPushed();
});
test('token with wrong issuer returns 401', function () {
    $token = mintProvisionToken(issuer: 'https://evil-idp.test');

    $this->post('/api/idp/tenants', ['provision_token' => $token])
        ->assertStatus(401);

    Queue::assertNothingPushed();
});
test('token with wrong audience returns 401', function () {
    $token = mintProvisionToken(audience: 'some-other-product');

    $this->post('/api/idp/tenants', ['provision_token' => $token])
        ->assertStatus(401);

    Queue::assertNothingPushed();
});
test('token with missing claims returns 422', function () {
    $token = mintProvisionToken(claims: ['environment_key' => null]);

    $this->post('/api/idp/tenants', ['provision_token' => $token])
        ->assertStatus(422);

    Queue::assertNothingPushed();
});
test('token with invalid subdomain returns 422', function () {
    $token = mintProvisionToken(claims: ['subdomain' => 'Not Valid!']);

    $this->post('/api/idp/tenants', ['provision_token' => $token])
        ->assertStatus(422);

    Queue::assertNothingPushed();
});
test('token with overlong subdomain returns 422', function () {
    $token = mintProvisionToken(claims: ['subdomain' => str_repeat('a', 51)]);

    $this->post('/api/idp/tenants', ['provision_token' => $token])
        ->assertStatus(422);

    Queue::assertNothingPushed();
});
test('valid token queues tenant creation', function () {
    $this->post('/api/idp/tenants', ['provision_token' => mintProvisionToken()])
        ->assertStatus(202)
        ->assertJson(['status' => 'accepted']);

    Queue::assertPushed(
        CreateTenantFromIdpJob::class,
        static fn(CreateTenantFromIdpJob $job): bool => $job->idpTenantId === IDP_TENANT_ID_IDP_TENANT_PROVISION_ENDPOINT
            && $job->name === 'Acme Corp'
            && $job->subdomain === SUBDOMAIN_IDP_TENANT_PROVISION_ENDPOINT
            && $job->environmentKey === ENVIRONMENT_KEY_IDP_TENANT_PROVISION_ENDPOINT,
    );
    Queue::assertNotPushed(ReportTenantProvisionedToIdpJob::class);
});
test('existing tenant by idp tenant id reports already exists', function () {
    $existing = new Tenant([
        'id' => 'existing-' . Str::random(6),
        'name' => 'Existing Tenant',
        'is_suspended' => false,
        'idp_tenant_id' => IDP_TENANT_ID_IDP_TENANT_PROVISION_ENDPOINT,
    ]);
    $existing->saveQuietly();

    $this->post('/api/idp/tenants', ['provision_token' => mintProvisionToken()])
        ->assertStatus(202)
        ->assertJson(['status' => 'accepted']);

    Queue::assertPushed(
        ReportTenantProvisionedToIdpJob::class,
        static fn(ReportTenantProvisionedToIdpJob $job): bool => $job->idpTenantId === IDP_TENANT_ID_IDP_TENANT_PROVISION_ENDPOINT
            && $job->environmentKey === ENVIRONMENT_KEY_IDP_TENANT_PROVISION_ENDPOINT
            && $job->status === 'already_exists',
    );
    Queue::assertNotPushed(CreateTenantFromIdpJob::class);
});
test('existing tenant by subdomain reports already exists', function () {
    $existing = new Tenant([
        'id' => SUBDOMAIN_IDP_TENANT_PROVISION_ENDPOINT,
        'name' => 'Existing Tenant',
        'is_suspended' => false,
    ]);
    $existing->saveQuietly();

    $this->post('/api/idp/tenants', ['provision_token' => mintProvisionToken()])
        ->assertStatus(202)
        ->assertJson(['status' => 'accepted']);

    Queue::assertPushed(
        ReportTenantProvisionedToIdpJob::class,
        static fn(ReportTenantProvisionedToIdpJob $job): bool => $job->idpTenantId === IDP_TENANT_ID_IDP_TENANT_PROVISION_ENDPOINT
            && $job->environmentKey === ENVIRONMENT_KEY_IDP_TENANT_PROVISION_ENDPOINT
            && $job->status === 'already_exists',
    );
    Queue::assertNotPushed(CreateTenantFromIdpJob::class);
});
// ── Helpers ───────────────────────────────────────────────────────────────
/**
 * @param array<string, string|null> $claims claim overrides; null removes the claim
 * @param string|null|null $issuer
 * @param string|null|null $audience
 * @param DateTimeImmutable|null|null $issuedAt
 * @param DateTimeImmutable|null|null $expiresAt
 */
function mintProvisionToken(array $claims = [], string|null $issuer = null, string|null $audience = null, DateTimeImmutable|null $issuedAt = null, DateTimeImmutable|null $expiresAt = null): string
{
    $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));

    $configuration = Configuration::forAsymmetricSigner(
        new Sha256(),
        InMemory::plainText(idpProvisionKeys()['private']),
        InMemory::plainText(idpProvisionKeys()['public']),
    );

    $builder = $configuration->builder()
        ->withHeader('kid', '1')
        ->issuedBy($issuer ?? IDP_URL_IDP_TENANT_PROVISION_ENDPOINT)
        ->permittedFor($audience ?? PRODUCT_IDP_TENANT_PROVISION_ENDPOINT)
        ->issuedAt($issuedAt ?? $now)
        ->expiresAt($expiresAt ?? $now->modify('+10 minutes'));

    $claims = array_merge([
        'idp_tenant_id' => IDP_TENANT_ID_IDP_TENANT_PROVISION_ENDPOINT,
        'name' => 'Acme Corp',
        'subdomain' => SUBDOMAIN_IDP_TENANT_PROVISION_ENDPOINT,
        'environment_key' => ENVIRONMENT_KEY_IDP_TENANT_PROVISION_ENDPOINT,
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
