<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Auth\IamJwtGuard;
use App\Repositories\UserRepository;
use App\Services\SsoClientService;
use Illuminate\Http\Request;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;

const IDP_URL_IAM_JWT_GUARD_VALIDATION = 'http://iam.test:81';
const PRODUCT_IAM_JWT_GUARD_VALIDATION = 'flow-ledger';
const SUB = 'oidc-sub-real-token';
const TENANT_ID = 'idp-tenant-under-test';
beforeEach(function () {
    [$this->privatePem, $this->publicPem] = generateRsaKeypair();

    config([
        'sso.idp_url' => IDP_URL_IAM_JWT_GUARD_VALIDATION,
        'sso.audience' => PRODUCT_IAM_JWT_GUARD_VALIDATION,
        'sso.product_slug' => PRODUCT_IAM_JWT_GUARD_VALIDATION,
    ]);

    $this->tenant->forceFill(['idp_tenant_id' => TENANT_ID])->save();
});
test('valid token resolves the user', function () {
    $this->user->forceFill(['oidc_sub' => SUB])->save();

    $token = mintToken();
    $guard = makeGuardForIamJwtGuardValidation($token);

    $resolved = $guard->user();

    expect($resolved)->not->toBeNull();
    expect($resolved->getAuthIdentifier())->toBe($this->user->id);
    expect($guard->check())->toBeTrue();
});
test('token with wrong issuer is rejected', function () {
    $this->user->forceFill(['oidc_sub' => SUB])->save();

    $token = mintToken(issuer: 'http://evil-idp.test');
    $guard = makeGuardForIamJwtGuardValidation($token);

    expect($guard->user())->toBeNull();
});
test('token without matching audience is rejected', function () {
    $this->user->forceFill(['oidc_sub' => SUB])->save();

    $token = mintToken(audience: 'some-other-product');
    $guard = makeGuardForIamJwtGuardValidation($token);

    expect($guard->user())->toBeNull();
});
test('token without matching product is rejected', function () {
    $this->user->forceFill(['oidc_sub' => SUB])->save();

    $token = mintToken(products: ['some-other-product']);
    $guard = makeGuardForIamJwtGuardValidation($token);

    expect($guard->user())->toBeNull();
});
test('expired token is rejected', function () {
    $this->user->forceFill(['oidc_sub' => SUB])->save();

    $now = new DateTimeImmutable('2026-07-04T12:00:00+00:00');
    $token = mintToken(issuedAt: $now->modify('-2 hours'), expiresAt: $now->modify('-1 hour'));
    $guard = makeGuardForIamJwtGuardValidation($token);

    expect($guard->user())->toBeNull();
});
test('token minted for a different tenant is rejected', function () {
    $this->user->forceFill(['oidc_sub' => SUB])->save();

    $token = mintToken(tenantId: 'some-other-idp-tenant-id');
    $guard = makeGuardForIamJwtGuardValidation($token);

    expect($guard->user())->toBeNull();
});
test('token missing tenant claim is rejected', function () {
    $this->user->forceFill(['oidc_sub' => SUB])->save();

    $token = mintToken(tenantId: null);
    $guard = makeGuardForIamJwtGuardValidation($token);

    expect($guard->user())->toBeNull();
});
// ── Helpers ───────────────────────────────────────────────────────────────
/**
 * @param string|null $issuer
 * @param string|null|null $audience
 * @param list<string> $products
 * @param string $subject
 * @param DateTimeImmutable|null|null $issuedAt
 * @param DateTimeImmutable|null|null $expiresAt
 * @param string|null $tenantId Pass null to omit the claim entirely; defaults to the tenant under test.
 */
function mintToken(string|null $issuer = null, string|null $audience = null, array $products = [PRODUCT_IAM_JWT_GUARD_VALIDATION], string $subject = SUB, DateTimeImmutable|null $issuedAt = null, DateTimeImmutable|null $expiresAt = null, string|null $tenantId = TENANT_ID): string
{
    $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));

    $configuration = Configuration::forAsymmetricSigner(
        new Sha256(),
        InMemory::plainText(test()->privatePem),
        InMemory::plainText(test()->publicPem),
    );

    $builder = $configuration->builder()
        ->withHeader('kid', '1')
        ->issuedBy($issuer ?? IDP_URL_IAM_JWT_GUARD_VALIDATION)
        ->permittedFor($audience ?? PRODUCT_IAM_JWT_GUARD_VALIDATION)
        ->relatedTo($subject)
        ->issuedAt($issuedAt ?? $now)
        ->expiresAt($expiresAt ?? $now->modify('+1 hour'))
        ->withClaim('products', $products)
        ->withClaim('scopes', ['openid', 'profile']);

    if ($tenantId !== null) {
        $builder = $builder->withClaim('tenant_id', $tenantId);
    }

    $token = $builder->getToken($configuration->signer(), $configuration->signingKey());

    return $token->toString();
}
function makeGuardForIamJwtGuardValidation(string $rawToken): IamJwtGuard
{
    $sso = test()->createPartialMock(SsoClientService::class, ['getIdpPublicKeyPem']);
    $sso->method('getIdpPublicKeyPem')->willReturn(test()->publicPem);

    $request = Request::create('/api/me', 'GET', server: [
        'HTTP_AUTHORIZATION' => 'Bearer ' . $rawToken,
    ]);

    return new IamJwtGuard(null, $request, $sso, app(UserRepository::class));
}
/**
 * @return array{0: string, 1: string} [privatePem, publicPem]
 */
function generateRsaKeypair(): array
{
    $resource = openssl_pkey_new([
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ]);

    if ($resource === false) {
        test()->fail('Unable to generate RSA keypair for test.');
    }

    openssl_pkey_export($resource, $privatePem);

    $details = openssl_pkey_get_details($resource);
    $publicPem = is_array($details) ? (string) $details['key'] : '';

    return [$privatePem, $publicPem];
}
