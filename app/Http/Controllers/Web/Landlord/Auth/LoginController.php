<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Landlord\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('landlord.auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::guard('landlord')->attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->onlyInput('email');
        }

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
