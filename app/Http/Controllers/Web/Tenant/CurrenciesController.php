<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\CurrencyStoreRequest;
use App\Http\Requests\Tenant\CurrencyUpdateRequest;
use App\Models\Tenant\Currency;
use App\Repositories\CurrencyRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CurrenciesController extends Controller
{
    public function __construct(
        private readonly CurrencyRepository $repository,
    ) {}

    public function index(): View
    {
        $currencies = $this->repository->allOrderedByShortName();

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
        $dto = $request->toDto();

        Currency::create([
            'name' => $dto->name,
            'short_name' => $dto->shortName,
            'symbol' => $dto->symbol,
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
        $dto = $request->toDto();

        $currency->update([
            'name' => $dto->name,
            'short_name' => $dto->shortName,
            'symbol' => $dto->symbol,
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
