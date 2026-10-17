<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Landlord\Auth;

use App\Http\Controllers\Controller;
use App\Services\LoginAttemptLimiter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(private readonly LoginAttemptLimiter $loginAttemptLimiter) {}

    public function create(): View
    {
        return view('landlord.auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var array{email: string, password: string} $credentials */
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if ($this->loginAttemptLimiter->isLocked('landlord', $credentials['email'])) {
            return back()->withErrors([
                'email' => __('auth.account_locked', ['minutes' => $this->loginAttemptLimiter->minutesUntilUnlocked('landlord', $credentials['email'])]),
            ])->onlyInput('email');
        }

        if (! Auth::guard('landlord')->attempt($credentials, $request->boolean('remember'))) {
            if ($this->loginAttemptLimiter->recordFailure('landlord', $credentials['email'])) {
                Log::warning('Landlord account locked after repeated failed sign-in attempts', [
                    'email' => $credentials['email'],
                    'ip' => $request->ip(),
                ]);
            }

            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->onlyInput('email');
        }

        $this->loginAttemptLimiter->clear('landlord', $credentials['email']);

        $request->session()->regenerate();

        return redirect()->route('landlord.tenants.index');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('landlord')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landlord.login');
    }
}
