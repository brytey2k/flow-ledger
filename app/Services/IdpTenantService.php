<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class IdpTenantService
{
    private const TENANTS_CACHE_KEY = 'idp_tenants_list';

    private const TENANTS_CACHE_SECONDS = 300;

    private const TOKEN_CACHE_MINUTES = 55;

    private const SCOPE = 'tenant:read';

    public function __construct(private readonly SsoClientService $ssoClient) {}

    /** @return list<array{id: int|string, name: string, slug: string}> */
    public function listTenants(): array
    {
        /** @var list<array{id: int|string, name: string, slug: string}>|null $cached */
        $cached = Cache::get(self::TENANTS_CACHE_KEY);

        if ($cached !== null) {
            return $cached;
        }

        $token = $this->getClientCredentialsToken();

        $response = $this->ssoClient->idpHttp()
            ->withToken($token)
            ->timeout(10)
            ->connectTimeout(5)
            ->retry(2, 200, throw: false)
            ->get(rtrim(config()->string('sso.idp_internal_url'), '/') . '/api/m2m/tenants');

        if ($response->failed()) {
            throw new RuntimeException('Failed to fetch tenants from IDP: ' . $response->body());
        }

        /** @var list<array{id: int|string, name: string, slug: string}> $tenants */
        $tenants = (array) ($response->json('data') ?? []);

        Cache::put(self::TENANTS_CACHE_KEY, $tenants, self::TENANTS_CACHE_SECONDS);

        return $tenants;
    }

    private function getClientCredentialsToken(): string
    {
        $clientId = config()->string('sso.m2m_client_id');
        $clientSecret = config()->string('sso.m2m_client_secret');

        if ($clientId === '' || $clientSecret === '') {
            throw new RuntimeException('SSO M2M client credentials are not configured.');
        }

        $cacheKey = 'idp.m2m.token.' . sha1($clientId . '|' . self::SCOPE);

        return Cache::remember($cacheKey, now()->addMinutes(self::TOKEN_CACHE_MINUTES), function () use ($clientId, $clientSecret): string {
            try {
                $response = $this->ssoClient->idpHttp()
                    ->asForm()
                    ->timeout(10)
                    ->connectTimeout(5)
                    ->retry(2, 200, throw: false)
                    ->post(
                        rtrim(config()->string('sso.idp_internal_url'), '/') . '/oauth/token',
                        [
                            'grant_type' => 'client_credentials',
                            'client_id' => $clientId,
                            'client_secret' => $clientSecret,
                            'scope' => self::SCOPE,
                        ],
                    );
            } catch (ConnectionException $e) {
                throw new RuntimeException('Could not connect to IDP to fetch M2M token: ' . $e->getMessage(), previous: $e);
            }

            if ($response->failed()) {
                throw new RuntimeException('IDP rejected M2M token request: ' . $response->body());
            }

            $token = $response->json('access_token');

            if (! is_string($token) || $token === '') {
                throw new RuntimeException('IDP returned an empty M2M access token.');
            }

            return $token;
        });
    }
}
