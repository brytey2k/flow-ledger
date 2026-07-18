<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use RuntimeException;

/**
 * Reports tenant-provisioning outcomes back to the IAM IdP over a
 * client-credentials M2M call. Unlike the backchannel-logout failure
 * reporter, failures here THROW so the queued job retries the report.
 */
class IdpTenantProvisionReporterService
{
    private const REPORT_TOKEN_SCOPE = 'tenant:provision-report';

    public function __construct(private readonly SsoClientService $ssoClient) {}

    public function report(string $idpTenantId, string $environmentKey, string $status): void
    {
        $token = $this->getClientCredentialsToken();

        $response = $this->ssoClient->idpHttp()
            ->withToken($token)
            ->timeout(10)
            ->connectTimeout(5)
            ->retry(2, 200, throw: false)
            ->post(
                rtrim(config()->string('sso.idp_internal_url'), '/') . '/api/m2m/tenants/provisioned',
                [
                    'product_slug' => config()->string('sso.product_slug'),
                    'idp_tenant_id' => $idpTenantId,
                    'environment_key' => $environmentKey,
                    'status' => $status,
                ],
            );

        if ($response->failed()) {
            throw new RuntimeException('Failed to report tenant provisioning to IdP: ' . $response->body());
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
                ->retry(2, 200, throw: false)
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
                throw new RuntimeException('Failed to obtain M2M provision-report token from IdP: ' . $response->body());
            }

            $token = $response->json('access_token');

            if (! is_string($token) || $token === '') {
                throw new RuntimeException('IdP returned an empty M2M provision-report access token.');
            }

            return $token;
        });
    }
}
