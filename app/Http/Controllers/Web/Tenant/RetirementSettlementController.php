<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Exceptions\InsufficientCashbookBalanceException;
use App\Http\Controllers\Controller;
use App\Models\Tenant\RetirementRequest;
use App\Services\RetirementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RetirementSettlementController extends Controller
{
    public function __construct(private readonly RetirementService $service) {}

    public function store(Request $request, RetirementRequest $retirementRequest): RedirectResponse
    {
        if ($retirementRequest->status !== 'approved') {
            return redirect()->back()->with('error', __('flash.retirements.settle_only_approved'));
        }

        $request->validate([
            'settlement_notes' => ['nullable', 'string', 'max:500'],
        ]);

        /** @var \App\Models\Tenant\User|null $user */
        $user = $request->user();

        try {
            $this->service->settle(
                $retirementRequest,
                $request->string('settlement_notes')->toString() ?: null,
                $user,
            );
        } catch (InsufficientCashbookBalanceException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('retirement-requests.show', $retirementRequest)
            ->with('success', __('flash.retirements.settled'));
    }
}
