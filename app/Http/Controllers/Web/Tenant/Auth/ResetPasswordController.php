<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant\Auth;

use App\Enums\Tenant\UserStatus;
use App\Features\DelegateIdentityToIdp;
use App\Features\LocalAuth;
use App\Features\VerifyLoginWithIdp;
use App\Http\Controllers\Controller;
use App\Interfaces\SessionInvalidatorInterface;
use App\Models\Tenant\User;
use App\Services\IdpAccessVerificationService;
use App\Services\LoginAttemptLimiter;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;
use Laravel\Pennant\Feature;

class ResetPasswordController extends Controller
{
    public function __construct(
        private readonly LoginAttemptLimiter $loginAttemptLimiter,
        private readonly SessionInvalidatorInterface $sessionInvalidator,
        private readonly IdpAccessVerificationService $idpAccessVerification,
    ) {}

    public function showForm(Request $request, string $token): RedirectResponse|View
    {
        if (Feature::for(tenant())->active(DelegateIdentityToIdp::class)) {
            return redirect()->route('login')->with('status', __('auth.password_managed_by_idp'));
        }

        return view('tenant.auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        abort_unless(Feature::for(tenant())->active(LocalAuth::class), 403);

        if (Feature::for(tenant())->active(DelegateIdentityToIdp::class)) {
            return back()->with('status', __('auth.password_managed_by_idp'))->withInput($request->only('email'));
        }

        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $isOnboarding = ! $user->hasVerifiedEmail();

                $user->forceFill(['password' => Hash::make($password)])
                    ->setRememberToken(Str::random(60));

                if ($isOnboarding) {
                    $user->forceFill([
                        'status' => UserStatus::Active,
                        'activated_at' => now(),
                    ]);
                }

                $user->save();

                if ($isOnboarding) {
                    $user->markEmailAsVerified();
                    event(new Verified($user));
                }

                event(new PasswordReset($user));
            },
        );

        if ($status === Password::PasswordReset) {
            $this->loginAttemptLimiter->clear($this->loginAttemptLimiter->tenantScope(), $request->string('email')->toString());

            /** @var CanResetPassword|null $resetUser */
            $resetUser = Password::broker()->getUser($request->only('email'));
            if ($resetUser instanceof User) {
                // Sessions opened before the reset (possibly by someone else) must not outlive it.
                $this->sessionInvalidator->revokeOtherSessions($resetUser->id);

                // Signing in straight after a reset must not skip the identity provider access check
                // that the normal sign-in path enforces.
                if (Feature::for(tenant())->active(VerifyLoginWithIdp::class)
                    && ! $this->idpAccessVerification->userHasAccess($resetUser->oidc_sub, $resetUser->email)) {
                    return redirect()->route('login')->withErrors(['email' => 'Access denied. Please contact your administrator.']);
                }

                Auth::login($resetUser);
                $request->session()->regenerate();
            }

            return redirect()->route('dashboard')->with('success', __('Your password has been reset.'));
        }

        return back()->withErrors(['email' => __(is_string($status) ? $status : 'passwords.token')])->withInput($request->only('email'));
    }
}
