<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\RetirementRequest;
use App\Services\RetirementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RetirementRequestSubmitController extends Controller
{
    public function __construct(private readonly RetirementService $service) {}

    public function store(Request $request, RetirementRequest $retirementRequest): RedirectResponse
    {
        if (! $retirementRequest->isDraft()) {
            return redirect()->route('retirement-requests.show', $retirementRequest)
                ->with('error', 'Only draft retirements can be submitted.');
        }

        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        $this->service->submit($retirementRequest, $user);

        return redirect()->route('retirement-requests.show', $retirementRequest)
            ->with('success', 'Retirement submitted for approval.');
    }
}
