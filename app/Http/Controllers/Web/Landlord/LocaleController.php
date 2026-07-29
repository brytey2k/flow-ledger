<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Landlord;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class LocaleController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $locale = $request->string('locale')->toString();
        $allowedLocales = array_keys(Config::array('locales.supported', []));

        if (! in_array($locale, $allowedLocales, true)) {
            $locale = config('app.locale', 'en');
        }

        $request->session()->put('locale', $locale);

        return back();
    }
}
