<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant\Auth;

use App\Data\Auth\SsoUserClaimsDto;
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

        /** @var array{sub: string, email: string, name: string, email_verified: bool, tenant_id: string|null, products: list<string>}|null $raw */
        $raw = Cache::pull("sso_login:{$token}");

        if ($raw === null) {
            abort(403, 'SSO login token is invalid or has expired. Please sign in again.');
        }

        $claims = SsoUserClaimsDto::fromArray($raw);

        $user = $this->provisioner->findOrCreateTenantUser($claims);

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        $this->sessionInvalidator->track($user->id, $request->session()->getId());

        return redirect()->intended(route('dashboard'));
    }
}
