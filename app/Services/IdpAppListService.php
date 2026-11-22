<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

/**
 * Reads the shared application list published by the IdP so users can open another
 * product's homepage or sign in to it through SSO.
 */
class IdpAppListService
{
    private const string APPS_CACHE_KEY = 'idp.apps.list';

    private const int APPS_CACHE_SECONDS = 300;

    private const int REQUEST_TIMEOUT_SECONDS = 10;

    private const int CONNECT_TIMEOUT_SECONDS = 5;

    private const int TOKEN_EXPIRY_BUFFER_SECONDS = 60;

    public function __construct(private readonly SsoClientService $ssoClient) {}

    /**
     * Apps published by the IdP, excluding the one this application represents.
     *
     * @throws RuntimeException when the IdP cannot be reached or returns an unusable response
     *
     * @return list<array{name: string, slug: string, sign_in_url: string, homepage_url: string|null}>
     */
    public function getOtherApps(): array
    {
        $currentSlug = config()->string('sso.product_slug');

        return array_values(array_filter(
            $this->getApps(),
            static fn(array $app): bool => $app['slug'] !== $currentSlug,
        ));
    }

    /**
     * @throws RuntimeException when the IdP cannot be reached or returns an unusable response
     *
     * @return list<array{name: string, slug: string, sign_in_url: string, homepage_url: string|null}>
     */
    public function getApps(): array
    {
        /** @var list<array{name: string, slug: string, sign_in_url: string, homepage_url: string|null}> $apps */
        $apps = Cache::remember(self::APPS_CACHE_KEY, self::APPS_CACHE_SECONDS, fn(): array => $this->fetchApps());

        return $apps;
    }

    /**
     * @return list<array{name: string, slug: string, sign_in_url: string, homepage_url: string|null}>
     */
    private function fetchApps(): array
    {
        try {
            $response = $this->ssoClient->idpHttp()
                ->withToken($this->getClientCredentialsToken())
                ->acceptJson()
                ->timeout(self::REQUEST_TIMEOUT_SECONDS)
                ->connectTimeout(self::CONNECT_TIMEOUT_SECONDS)
                ->retry(2, 200, throw: false)
                ->get($this->idpUrl('/api/m2m/apps'));
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Could not connect to the IdP to fetch the app list: ' . $exception->getMessage(), previous: $exception);
        }

        if ($response->failed()) {
            throw new RuntimeException('IdP app list request failed with HTTP status ' . $response->status() . '.');
        }

        $data = $response->json('data');

        if (! is_array($data)) {
            throw new RuntimeException('IdP app list returned an invalid response.');
        }

        $apps = [];

        foreach ($data as $app) {
            if (! is_array($app) || ! is_string($app['name'] ?? null) || ! is_string($app['slug'] ?? null)) {
                continue;
            }

            $signInUrl = $this->httpUrlOrNull($app['sso_redirect_url'] ?? null);

            if ($signInUrl === null) {
                continue;
            }

            $apps[] = [
                'name' => $app['name'],
                'slug' => $app['slug'],
                'sign_in_url' => $signInUrl,
                'homepage_url' => $this->httpUrlOrNull($app['marketing_page_url'] ?? null),
            ];
        }

        return $apps;
    }

    /**
     * The URLs end up as links in the browser, so anything other than an absolute
     * http(s) URL (e.g. a `javascript:` URI) is discarded.
     *
     * @param mixed $url
     */
    private function httpUrlOrNull(mixed $url): string|null
    {
        if (! is_string($url) || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true) ? $url : null;
    }

    private function getClientCredentialsToken(): string
    {
        $clientId = config()->string('sso.m2m_client_id');
        $clientSecret = config()->string('sso.m2m_client_secret');
        $scope = config()->string('sso.m2m_apps_scope');

        if ($clientId === '' || $clientSecret === '') {
            throw new RuntimeException('SSO M2M client credentials are not configured.');
        }

        $cacheKey = 'idp.m2m.token.' . sha1($clientId . '|' . $scope);
        $cachedToken = Cache::get($cacheKey);

        if (is_string($cachedToken) && $cachedToken !== '') {
            return $cachedToken;
        }

        try {
            $response = $this->ssoClient->idpHttp()
                ->asForm()
                ->acceptJson()
                ->timeout(self::REQUEST_TIMEOUT_SECONDS)
                ->connectTimeout(self::CONNECT_TIMEOUT_SECONDS)
                ->retry(2, 200, throw: false)
                ->post($this->idpUrl('/oauth/token'), [
                    'grant_type' => 'client_credentials',
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'scope' => $scope,
                ]);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Could not connect to the IdP to fetch an M2M token: ' . $exception->getMessage(), previous: $exception);
        }

        $token = $response->json('access_token');

        if ($response->failed() || ! is_string($token) || $token === '') {
            throw new RuntimeException('IdP M2M token request failed with HTTP status ' . $response->status() . '.');
        }

        $expiresIn = $response->json('expires_in');
        $ttl = max((is_numeric($expiresIn) ? (int) $expiresIn : 300) - self::TOKEN_EXPIRY_BUFFER_SECONDS, self::TOKEN_EXPIRY_BUFFER_SECONDS);
        Cache::put($cacheKey, $token, now()->addSeconds($ttl));

        return $token;
    }

    private function idpUrl(string $path): string
    {
        return rtrim(config()->string('sso.idp_internal_url'), '/') . $path;
    }
}
