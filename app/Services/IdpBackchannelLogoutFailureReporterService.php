<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class IdpBackchannelLogoutFailureReporterService
{
    private const REPORT_TOKEN_SCOPE = 'logout:report';

    public function __construct(private readonly SsoClientService $ssoClient) {}

    /** @param array<string, mixed> $payload */
    public function report(array $payload): void
    {
        try {
            $token = $this->getClientCredentialsToken();

            $this->ssoClient->idpHttp()
                ->withToken($token)
                ->timeout(10)
                ->connectTimeout(5)
                ->retry(2, 200)
                ->post(
                    rtrim(config()->string('sso.idp_internal_url'), '/') . '/api/m2m/backchannel-logout/failures',
                    array_merge($payload, ['product_slug' => config()->string('sso.product_slug')]),
                );
        } catch (\Throwable $e) {
            Log::warning('Failed to report backchannel logout failure to IdP', ['error' => $e->getMessage()]);
        }
    }

    private function getClientCredentialsToken(): string
    {
        $clientId = config()->string('sso.m2m_client_id');
        $cacheKey = 'idp.m2m.token.' . sha1($clientId . '|' . self::REPORT_TOKEN_SCOPE);

        return Cache::remember($cacheKey, now()->addMinutes(55), function () use ($clientId): string {
            $response = $this->ssoClient->idpHttp()
                ->asForm()
                ->timeout(10)
                ->connectTimeout(5)
                ->retry(2, 200)
                ->post(
                    rtrim(config()->string('sso.idp_internal_url'), '/') . '/oauth/token',
                    [
                        'grant_type' => 'client_credentials',
                        'client_id' => $clientId,
                        'client_secret' => config()->string('sso.m2m_client_secret'),
                        'scope' => self::REPORT_TOKEN_SCOPE,
                    ],
                );

            if ($response->failed()) {
                throw new RuntimeException('Failed to obtain M2M report token from IdP: ' . $response->body());
            }

            $token = $response->json('access_token');

            if (! is_string($token) || $token === '') {
                throw new RuntimeException('IdP returned an empty M2M report access token.');
            }

            return $token;
        });
    }
}
