<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\Auth\SsoUserClaimsDto;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;
use RuntimeException;

class SsoClientService
{
    // ── PKCE ─────────────────────────────────────────────────────────────────

    /** @return array{verifier: string, challenge: string} */
    public function generatePkce(): array
    {
        $verifier = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

        return ['verifier' => $verifier, 'challenge' => $challenge];
    }

    // ── State (CSRF) ──────────────────────────────────────────────────────────

    public function generateState(): string
    {
        $state = Str::random(40);
        Cache::put("sso_state:{$state}", true, now()->addMinutes(5));

        return $state;
    }

    public function validateAndConsumeState(string $state): bool
    {
        return Cache::pull("sso_state:{$state}") !== null;
    }

    // ── Authorization URL ─────────────────────────────────────────────────────

    public function buildAuthorizationUrl(string $state, string $codeChallenge): string
    {
        /** @var list<string> $scopes */
        $scopes = (array) config('sso.scopes');

        $params = http_build_query([
            'response_type' => 'code',
            'client_id' => config()->string('sso.client_id'),
            'redirect_uri' => config()->string('sso.redirect_uri'),
            'scope' => implode(' ', $scopes),
            'state' => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ]);

        return rtrim(config()->string('sso.idp_url'), '/') . '/oauth/authorize?' . $params;
    }

    // ── Token Exchange ────────────────────────────────────────────────────────

    /** @return array{access_token: string, id_token?: string, refresh_token?: string, token_type: string} */
    public function exchangeCodeForTokens(string $code, string $codeVerifier): array
    {
        $response = Http::asForm()->post(
            rtrim(config()->string('sso.idp_internal_url'), '/') . '/oauth/token',
            [
                'grant_type' => 'authorization_code',
                'client_id' => config()->string('sso.client_id'),
                'client_secret' => config()->string('sso.client_secret'),
                'redirect_uri' => config()->string('sso.redirect_uri'),
                'code' => $code,
                'code_verifier' => $codeVerifier,
            ],
        );

        if ($response->failed()) {
            throw new RuntimeException('Token exchange with IDP failed: ' . $response->body());
        }

        $json = $response->json();
        /** @var array{access_token: string, id_token?: string, refresh_token?: string, token_type: string} $json */
        $json = is_array($json) ? $json : [];

        return $json;
    }

    // ── ID Token Validation ───────────────────────────────────────────────────

    /** Validates the ID token signature, issuer, audience, and expiry. */
    public function validateIdToken(string $idToken): void
    {
        if ($idToken === '') {
            throw new RuntimeException('ID token is empty.');
        }

        $pem = $this->getIdpPublicKeyPem();

        if ($pem === '') {
            throw new RuntimeException('IDP public key PEM is empty.');
        }

        $configuration = Configuration::forAsymmetricSigner(
            new Sha256(),
            InMemory::plainText('verify-only'),
            InMemory::plainText($pem),
        );

        $issuer = rtrim(config()->string('sso.idp_url'), '/') ?: throw new RuntimeException('SSO IDP URL is not configured.');
        $audience = config()->string('sso.client_id') ?: throw new RuntimeException('SSO client ID is not configured.');

        $configuration->setValidationConstraints(
            new SignedWith($configuration->signer(), $configuration->verificationKey()),
            new IssuedBy($issuer),
            new PermittedFor($audience),
        );

        $token = $configuration->parser()->parse($idToken);

        try {
            $configuration->validator()->assert(
                $token,
                ...$configuration->validationConstraints(),
            );
        } catch (RequiredConstraintsViolated $e) {
            throw new RuntimeException('ID token validation failed: ' . $e->getMessage());
        }

        if ($token->isExpired(now()->toDateTimeImmutable())) {
            throw new RuntimeException('ID token has expired.');
        }
    }

    // ── UserInfo ──────────────────────────────────────────────────────────────

    /** Fetches user claims from the IDP userinfo endpoint. */
    public function fetchUserInfo(string $accessToken): SsoUserClaimsDto
    {
        $response = Http::withToken($accessToken)
            ->get(rtrim(config()->string('sso.idp_internal_url'), '/') . '/oauth/userinfo');

        if ($response->failed()) {
            throw new RuntimeException('Failed to fetch userinfo from IDP: ' . $response->body());
        }

        $raw = $response->json();

        /** @var array{sub?: string, email?: string, name?: string, email_verified?: bool, tenant_id?: int|string, products?: list<string>} $data */
        $data = is_array($raw) ? $raw : [];

        /** @var list<string> $products */
        $products = array_values(array_filter((array) ($data['products'] ?? []), 'is_string'));

        return new SsoUserClaimsDto(
            sub: $data['sub'] ?? '',
            email: $data['email'] ?? '',
            name: $data['name'] ?? '',
            email_verified: $data['email_verified'] ?? false,
            tenant_id: isset($data['tenant_id']) ? (string) $data['tenant_id'] : null,
            products: $products,
        );
    }

    // ── Public Key (JWKS) ─────────────────────────────────────────────────────

    public function getIdpPublicKeyPem(): string
    {
        return Cache::remember('sso_idp_public_key', now()->addHour(), fn() => $this->fetchPemFromJwks());
    }

    private function fetchPemFromJwks(): string
    {
        $response = Http::get(config()->string('sso.jwks_uri'));

        if ($response->failed()) {
            throw new RuntimeException('Failed to fetch JWKS from IDP.');
        }

        $keys = (array) ($response->json('keys') ?? []);

        foreach ($keys as $key) {
            if (! is_array($key)) {
                continue;
            }
            /** @var array{kty?: string, use?: string, n?: string, e?: string} $key */
            if (($key['kty'] ?? '') === 'RSA' && ($key['use'] ?? 'sig') === 'sig') {
                return $this->jwkToPem($key['n'] ?? '', $key['e'] ?? '');
            }
        }

        throw new RuntimeException('No suitable RSA key found in IDP JWKS response.');
    }

    /** Converts a JWK RSA key (n, e as base64url) to a PEM public key string. */
    private function jwkToPem(string $n, string $e): string
    {
        $modulus = base64_decode(strtr($n, '-_', '+/'));
        $exponent = base64_decode(strtr($e, '-_', '+/'));

        if (ord($modulus[0]) > 0x7F) {
            $modulus = "\x00" . $modulus;
        }
        if (ord($exponent[0]) > 0x7F) {
            $exponent = "\x00" . $exponent;
        }

        $rsaPublicKey = $this->derSequence(
            $this->derInteger($modulus) . $this->derInteger($exponent),
        );

        $algorithmIdentifier = $this->derSequence(
            "\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01\x05\x00",
        );

        $spki = $this->derSequence(
            $algorithmIdentifier . $this->derBitString($rsaPublicKey),
        );

        return "-----BEGIN PUBLIC KEY-----\n"
            . wordwrap(base64_encode($spki), 64, "\n", true)
            . "\n-----END PUBLIC KEY-----";
    }

    /** @param int<0, max> $length */
    private function derLength(int $length): string
    {
        if ($length < 0x80) {
            return chr($length);
        }
        $bytes = '';
        $tmp = $length;
        while ($tmp > 0) {
            /** @var int<0, 255> $byte */
            $byte = $tmp & 0xFF;
            $bytes = chr($byte) . $bytes;
            $tmp >>= 8;
        }

        /** @var int<0, 255> $firstByte */
        $firstByte = 0x80 | strlen($bytes);

        return chr($firstByte) . $bytes;
    }

    private function derSequence(string $content): string
    {
        return "\x30" . $this->derLength(strlen($content)) . $content;
    }

    private function derInteger(string $value): string
    {
        return "\x02" . $this->derLength(strlen($value)) . $value;
    }

    private function derBitString(string $value): string
    {
        return "\x03" . $this->derLength(strlen($value) + 1) . "\x00" . $value;
    }
}
