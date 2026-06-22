<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Stancl\Tenancy\Features\UserImpersonation;

class ImpersonationController extends Controller
{
    public function impersonate(Request $request, string $token): RedirectResponse
    {
        $request->session()->put('impersonated', true);

        return UserImpersonation::makeResponse($token);
    }

    public function exit(Request $request): RedirectResponse
    {
        $request->session()->forget('impersonated');
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landlord.tenants.index');
    }
}
