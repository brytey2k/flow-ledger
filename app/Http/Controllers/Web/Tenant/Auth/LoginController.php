<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant\Auth;

use App\Enums\Tenant\UserStatus;
use App\Features\DelegateIdentityToIdp;
use App\Features\LocalAuth;
use App\Features\VerifyLoginWithIdp;
use App\Http\Controllers\Controller;
use App\Models\Tenant\User;
use App\Notifications\AccountLockedNotification;
use App\Services\IdpAccessVerificationService;
use App\Services\LoginAttemptLimiter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Laravel\Pennant\Feature;

class LoginController extends Controller
{
    public function __construct(
        private readonly IdpAccessVerificationService $idpAccessVerification,
        private readonly LoginAttemptLimiter $loginAttemptLimiter,
    ) {}

    public function showLoginForm(): View
    {
        $localAuthEnabled = Feature::for(tenant())->active(LocalAuth::class);
        $identityDelegated = Feature::for(tenant())->active(DelegateIdentityToIdp::class);

        return view('tenant.auth.login', compact('localAuthEnabled', 'identityDelegated'));
    }

    public function login(Request $request): RedirectResponse
    {
        abort_unless(Feature::for(tenant())->active(LocalAuth::class), 403);

        /** @var array{email: string, password: string} $credentials */
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $invitedUser = User::query()->where('email', $credentials['email'])->where('status', UserStatus::Invited)->first();
        if ($invitedUser !== null) {
            return back()->withErrors([
                'email' => __('auth.invite_not_accepted'),
            ])->onlyInput('email');
        }

        $scope = $this->lockoutScope();

        if ($this->loginAttemptLimiter->isLocked($scope, $credentials['email'])) {
            return back()->withErrors([
                'email' => __('auth.account_locked', ['minutes' => $this->loginAttemptLimiter->minutesUntilUnlocked($scope, $credentials['email'])]),
            ])->onlyInput('email');
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            if ($this->loginAttemptLimiter->recordFailure($scope, $credentials['email'])) {
                $this->handleLockout($credentials['email'], $request);
            }

            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->onlyInput('email');
        }

        $this->loginAttemptLimiter->clear($scope, $credentials['email']);

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

    private function lockoutScope(): string
    {
        $tenantId = tenant('id');

        return 'tenant:' . (is_string($tenantId) ? $tenantId : '');
    }

    private function handleLockout(string $email, Request $request): void
    {
        $user = User::query()->where('email', $email)->first();

        activity()
            ->causedBy($user)
            ->event('auth.locked_out')
            ->withProperties(['email' => $email, 'ip' => $request->ip(), 'user_agent' => $request->userAgent()])
            ->log('Account locked after repeated failed sign-in attempts');

        $user?->notify(new AccountLockedNotification());
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
