<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Exceptions\InvalidProvisionTokenException;
use App\Http\Controllers\Controller;
use App\Jobs\CreateTenantFromIdpJob;
use App\Jobs\ReportTenantProvisionedToIdpJob;
use App\Models\Tenant;
use App\Repositories\TenantRepository;
use App\Services\IamProvisionTokenVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Accepts tenant-provisioning requests from the IAM IdP. The request carries
 * a signed provision token whose claims describe the tenant to create; the
 * actual creation runs on the queue and the outcome is reported back to the
 * IdP via a machine-to-machine call.
 */
class IdpTenantProvisionController extends Controller
{
    private const SUBDOMAIN_PATTERN = '/^[a-z0-9-]+$/';

    private const SUBDOMAIN_MAX_LENGTH = 50;

    public function __construct(
        private readonly IamProvisionTokenVerifier $tokenVerifier,
        private readonly TenantRepository $tenantRepository,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $rawToken = $request->input('provision_token');

        if (! is_string($rawToken) || $rawToken === '') {
            return response()->json(['message' => 'The provision_token field is required.'], 422);
        }

        try {
            $claims = $this->tokenVerifier->verify($rawToken);
        } catch (InvalidProvisionTokenException) {
            return response()->json(['message' => 'Invalid provision token.'], 401);
        }

        $idpTenantId = $claims['idp_tenant_id'] ?? null;
        $name = $claims['name'] ?? null;
        $subdomain = $claims['subdomain'] ?? null;
        $environmentKey = $claims['environment_key'] ?? null;

        if (! is_string($idpTenantId) || $idpTenantId === ''
            || ! is_string($name) || $name === ''
            || ! is_string($subdomain) || strlen($subdomain) > self::SUBDOMAIN_MAX_LENGTH
            || preg_match(self::SUBDOMAIN_PATTERN, $subdomain) !== 1
            || ! is_string($environmentKey) || $environmentKey === '') {
            return response()->json(['message' => 'Provision token claims are missing or invalid.'], 422);
        }

        if ($this->tenantRepository->findByIdpTenantId($idpTenantId) !== null || Tenant::find($subdomain) !== null) {
            ReportTenantProvisionedToIdpJob::dispatch($idpTenantId, $environmentKey, 'already_exists');

            return response()->json(['status' => 'accepted'], 202);
        }

        CreateTenantFromIdpJob::dispatch($idpTenantId, $name, $subdomain, $environmentKey);

        return response()->json(['status' => 'accepted'], 202);
    }
}
