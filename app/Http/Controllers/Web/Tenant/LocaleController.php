<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $locale = $request->string('locale')->toString();
        $allowedLocales = ['en', 'fr'];

        if (! in_array($locale, $allowedLocales, true)) {
            $locale = config('app.locale', 'en');
        }

        $request->session()->put('locale', $locale);

        $user = $request->user();

        if ($user instanceof \App\Models\Tenant\User && $user->locale !== $locale) {
            $user->forceFill(['locale' => $locale])->save();
        }

        return back();
    }
}
