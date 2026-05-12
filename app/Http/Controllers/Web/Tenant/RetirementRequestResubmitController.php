<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\RetirementRequest;
use App\Services\WorkflowEngineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RetirementRequestResubmitController extends Controller
{
    public function __construct(private readonly WorkflowEngineService $engine) {}

    public function store(Request $request, RetirementRequest $retirementRequest): RedirectResponse
    {
        if (! $retirementRequest->isSentBack()) {
            return redirect()->route('retirement-requests.show', $retirementRequest)
                ->with('error', __('flash.retirements.resubmit_only_sent_back'));
        }

        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        $this->engine->resubmitAfterFix($retirementRequest, $user);

        return redirect()->route('retirement-requests.show', $retirementRequest)
            ->with('success', __('flash.retirements.resubmitted'));
    }
}
