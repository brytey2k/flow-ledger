<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\CurrencyDenominationStoreRequest;
use App\Http\Requests\Tenant\CurrencyDenominationUpdateRequest;
use App\Models\Tenant\Currency;
use App\Models\Tenant\CurrencyDenomination;
use App\Repositories\CurrencyDenominationRepository;
use App\Services\CurrencyDenominationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CurrencyDenominationsController extends Controller
{
    public function __construct(
        private readonly CurrencyDenominationRepository $repository,
        private readonly CurrencyDenominationService $service,
    ) {}

    public function index(Currency $currency): View
    {
        $denominations = $this->repository->allForCurrency($currency);

        return view('tenant.currencies.denominations.index', compact('currency', 'denominations'));
    }

    public function create(Currency $currency): View
    {
        return view('tenant.currencies.denominations.create', compact('currency'));
    }

    public function store(CurrencyDenominationStoreRequest $request, Currency $currency): RedirectResponse
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();

        $this->service->store($request->toDto(), $user);

        return redirect()
            ->route('currency.denominations.index', $currency)
            ->with('success', __('flash.denomination.created'));
    }

    public function edit(Currency $currency, CurrencyDenomination $denomination): View
    {
        return view('tenant.currencies.denominations.edit', compact('currency', 'denomination'));
    }

    public function update(CurrencyDenominationUpdateRequest $request, Currency $currency, CurrencyDenomination $denomination): RedirectResponse
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();

        $this->service->update($denomination, $request->toDto(), $user);

        return redirect()
            ->route('currency.denominations.index', $currency)
            ->with('success', __('flash.denomination.updated'));
    }

    public function destroy(Request $request, Currency $currency, CurrencyDenomination $denomination): RedirectResponse
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();

        try {
            $this->service->delete($denomination, $user);
        } catch (\LogicException $e) {
            return redirect()
                ->route('currency.denominations.index', $currency)
                ->with('error', __('flash.denomination.in_use'));
        }

        return redirect()
            ->route('currency.denominations.index', $currency)
            ->with('success', __('flash.denomination.deleted'));
    }
}
