<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant\Auth;

use App\Data\Auth\SsoUserClaimsDto;
use App\Exceptions\UnverifiedEmailException;
use App\Http\Controllers\Controller;
use App\Interfaces\SessionInvalidatorInterface;
use App\Services\SsoUserProvisioningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class SsoFinalizeController extends Controller
{
    public function __construct(
        private readonly SsoUserProvisioningService $provisioner,
        private readonly SessionInvalidatorInterface $sessionInvalidator,
    ) {}

    /**
     * Step 3 — Completes SSO login on the tenant subdomain.
     * The central-domain callback placed a one-time token in cache; we consume
     * it here, provision the user if needed, and open their session.
     *
     * @param Request $request
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $token = (string) $request->query('token', '');

        if ($token === '') {
            abort(403, 'Missing SSO login token.');
        }

        $currentTenant = tenant();
        abort_unless($currentTenant instanceof \App\Models\Tenant && is_scalar($currentTenant->getKey()), 403);
        $tenantId = (string) $currentTenant->getKey();

        /** @var array{sub: string, email: string, name: string, email_verified: bool, tenant_id: string|null, products: list<string>}|null $raw */
        $raw = Cache::store(config()->string('cache.default'))->pull("sso_login:{$tenantId}:{$token}");

        if ($raw === null) {
            abort(403, 'SSO login token is invalid or has expired. Please sign in again.');
        }

        $claims = SsoUserClaimsDto::fromArray($raw);

        // Defensive check: the tenant-scoped cache key already guarantees this,
        // but assert the IDP's tenant claim matches this tenant in case the key
        // scoping is ever bypassed or misconfigured.
        if ($claims->tenant_id === null || ! is_scalar($currentTenant->idp_tenant_id) || (string) $currentTenant->idp_tenant_id !== $claims->tenant_id) {
            abort(403, 'SSO login token is not valid for this organisation.');
        }

        try {
            $user = $this->provisioner->findOrCreateTenantUser($claims);
        } catch (UnverifiedEmailException $e) {
            return redirect()->route('login')->withErrors(['email' => $e->getMessage()]);
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        $this->sessionInvalidator->track($user->id, $request->session()->getId());

        return redirect()->intended(route('dashboard'));
    }
}
