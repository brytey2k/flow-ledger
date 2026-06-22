<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant\Auth;

use App\Features\LocalAuth;
use App\Features\VerifyLoginWithIdp;
use App\Http\Controllers\Controller;
use App\Models\Tenant\User;
use App\Services\IdpAccessVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Pennant\Feature;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(private readonly IdpAccessVerificationService $idpAccessVerification) {}

    public function showLoginForm(): View
    {
        $localAuthEnabled = Feature::for(tenant())->active(LocalAuth::class);

        return view('tenant.auth.login', compact('localAuthEnabled'));
    }

    public function login(Request $request): RedirectResponse
    {
        abort_unless(Feature::for(tenant())->active(LocalAuth::class), 403);

        /** @var array{email: string, password: string} $credentials */
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->onlyInput('email');
        }

        if (Feature::for(tenant())->active(VerifyLoginWithIdp::class)) {
            /** @var User $user */
            $user = Auth::user();

            if (! $this->idpAccessVerification->userHasAccess($user->oidc_sub, $user->email)) {
                Auth::logout();

                return back()->withErrors([
                    'email' => 'Access denied. Please contact your administrator.',
                ])->onlyInput('email');
            }
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
