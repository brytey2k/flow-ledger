<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\CurrencyStoreRequest;
use App\Http\Requests\Tenant\CurrencyUpdateRequest;
use App\Models\Tenant\Currency;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CurrenciesController extends Controller
{
    public function index(): View
    {
        $currencies = Currency::orderBy('short_name')->get();

        return view('tenant.currencies.index', [
            'currencies' => $currencies,
        ]);
    }

    public function create(): View
    {
        return view('tenant.currencies.create');
    }

    public function store(CurrencyStoreRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Currency::create([
            'name' => $validated['name'],
            'short_name' => $validated['short_name'],
            'symbol' => $validated['symbol'],
        ]);

        return redirect()
            ->route('currencies.index')
            ->with('success', 'Currency created successfully.');
    }

    public function edit(Currency $currency): View
    {
        return view('tenant.currencies.edit', [
            'currency' => $currency,
        ]);
    }

    public function update(CurrencyUpdateRequest $request, Currency $currency): RedirectResponse
    {
        $validated = $request->validated();

        $currency->update([
            'name' => $validated['name'],
            'short_name' => $validated['short_name'],
            'symbol' => $validated['symbol'],
        ]);

        return redirect()
            ->route('currencies.index')
            ->with('success', 'Currency updated successfully.');
    }

    public function destroy(Currency $currency): RedirectResponse
    {
        $currency->delete();

        return redirect()
            ->route('currencies.index')
            ->with('success', 'Currency deleted successfully.');
    }
}
