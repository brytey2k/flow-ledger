<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant\Auth;

use App\Features\DelegateIdentityToIdp;
use App\Features\LocalAuth;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;
use Laravel\Pennant\Feature;

class ForgotPasswordController extends Controller
{
    public function showForm(): View
    {
        return view('tenant.auth.forgot-password');
    }

    public function sendLink(Request $request): RedirectResponse
    {
        abort_unless(Feature::for(tenant())->active(LocalAuth::class), 403);

        if (Feature::for(tenant())->active(DelegateIdentityToIdp::class)) {
            return back()->with('status', __('auth.password_managed_by_idp'));
        }

        $request->validate(['email' => ['required', 'email']]);

        Password::sendResetLink($request->only('email'));

        return back()->with('status', __('If that email exists in our system, a password reset link has been sent.'));
    }
}
