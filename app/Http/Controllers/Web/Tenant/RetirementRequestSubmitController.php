<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\WorkflowTemplate;
use App\Services\RetirementService;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RetirementRequestSubmitController extends Controller
{
    public function __construct(
        private readonly RetirementService $service,
        private readonly SettingsService $settingsService,
    ) {}

    public function store(Request $request, RetirementRequest $retirementRequest): RedirectResponse
    {
        if (! $retirementRequest->isDraft()) {
            return redirect()->route('retirement-requests.show', $retirementRequest)
                ->with('error', __('flash.retirements.submit_only_draft'));
        }

        if ($this->settingsService->isRetirementSourceDocumentRequired() && $retirementRequest->attachments()->doesntExist()) {
            return redirect()->route('retirement-requests.show', $retirementRequest)
                ->with('error', __('flash.retirements.source_documents_required'));
        }

        $template = WorkflowTemplate::where('type', 'retirement')->first();

        if ($template === null) {
            return redirect()->route('retirement-requests.show', $retirementRequest)
                ->with('error', __('flash.retirements.missing_workflow_template'));
        }

        if (! $template->stages()->exists()) {
            return redirect()->route('retirement-requests.show', $retirementRequest)
                ->with('error', __('flash.retirements.no_workflow_stages'));
        }

        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        $this->service->submit($retirementRequest, $user);

        return redirect()->route('retirement-requests.show', $retirementRequest)
            ->with('success', __('flash.retirements.submitted'));
    }
}
