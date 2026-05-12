<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\ApprovalActionRequest;
use App\Models\Tenant\WorkflowInstanceStage;
use App\Repositories\WorkflowInstanceRepository;
use App\Services\WorkflowEngineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkflowApprovalsController extends Controller
{
    public function __construct(
        private readonly WorkflowEngineService $engine,
        private readonly WorkflowInstanceRepository $repository,
    ) {}

    public function index(Request $request): View
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        $instanceStages = $this->repository->activeStagesForUser($user);

        return view('tenant.approvals.index', compact('instanceStages'));
    }

    public function show(WorkflowInstanceStage $instanceStage): View
    {
        /** @var \App\Models\Tenant\User $user */
        $user = auth()->user();

        abort_unless($this->engine->canUserActOnStage($instanceStage, $user), 403);

        $instanceStage->load([
            'stage',
            'actions.user',
            'instance.instanceStages.stage',
            'instance.workflowable.staff',
            'instance.workflowable.branch',
            'instance.workflowable.currency',
            'instance.workflowable.items',
        ]);

        return view('tenant.approvals.show', compact('instanceStage'));
    }

    public function store(ApprovalActionRequest $request, WorkflowInstanceStage $instanceStage): RedirectResponse
    {
        /** @var \App\Models\Tenant\User $user */
        $user = auth()->user();

        abort_unless($this->engine->canUserActOnStage($instanceStage, $user), 403);
        abort_unless($instanceStage->isActive(), 403);

        $dto = $request->toDto();

        match ($dto->action) {
            'approve' => $this->engine->approve($instanceStage, $user, $dto->comment),
            'reject' => $this->engine->reject($instanceStage, $user, (string) $dto->comment),
            'send_back' => $this->engine->sendBack($instanceStage, $user, (string) $dto->comment),
            default => null,
        };

        /** @var \Illuminate\Database\Eloquent\Model $subject */
        $subject = $instanceStage->instance?->workflowable;

        return redirect()->route('payment-requests.show', $subject)
            ->with('success', __('flash.approvals.action_recorded'));
    }
}
