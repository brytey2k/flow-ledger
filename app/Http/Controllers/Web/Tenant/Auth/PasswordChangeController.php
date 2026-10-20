<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant\Auth;

use App\Http\Controllers\Controller;
use App\Interfaces\SessionInvalidatorInterface;
use App\Models\Tenant\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PasswordChangeController extends Controller
{
    public function __construct(private readonly SessionInvalidatorInterface $sessionInvalidator) {}

    public function show(): View
    {
        return view('tenant.auth.change-password');
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        /** @var User $user */
        $user = $request->user();

        $user->forceFill([
            'password' => Hash::make($request->string('password')->toString()),
            'remember_token' => Str::random(60),
        ])->save();

        // A stolen session or remember-me cookie must not survive the owner changing their password.
        $this->sessionInvalidator->revokeOtherSessions($user->id, $request->session()->getId());

        return redirect()->route('dashboard')->with('success', __('flash.password.changed'));
    }
}
