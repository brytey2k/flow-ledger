<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Exceptions\SendBackNotAllowedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\ApprovalActionRequest;
use App\Http\Requests\Tenant\EligibleApproversRequest;
use App\Http\Requests\Tenant\WorkflowStageRecoveryRequest;
use App\Http\Requests\Tenant\WorkflowTemplateRecoveryRequest;
use App\Models\Role;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\User;
use App\Models\Tenant\WorkflowInstanceStage;
use App\Models\Tenant\WorkflowStage;
use App\Repositories\RoleRepository;
use App\Repositories\WorkflowInstanceRepository;
use App\Services\BranchScopeService;
use App\Services\WorkflowApproverResolver;
use App\Services\WorkflowEngineService;
use App\Services\WorkflowStageRecoveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkflowApprovalsController extends Controller
{
    public function __construct(
        private readonly WorkflowEngineService $engine,
        private readonly WorkflowInstanceRepository $repository,
        private readonly RoleRepository $roles,
        private readonly WorkflowStageRecoveryService $recovery,
        private readonly WorkflowApproverResolver $approvers,
        private readonly BranchScopeService $branchScope,
    ) {}

    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $instanceStages = $this->repository->activeStagesForUser($user);

        return view('tenant.approvals.index', compact('instanceStages'));
    }

    public function show(WorkflowInstanceStage $instanceStage): View
    {
        /** @var User $user */
        $user = auth()->user();

        abort_unless($this->engine->canUserActOnStage($instanceStage, $user), 403);

        $instanceStage->load([
            'stage',
            'actions.user',
            'instance.template',
            'instance.instanceStages.stage',
            'instance.workflowable.staff',
            'instance.workflowable.branch',
            'instance.workflowable.currency',
            'instance.workflowable.items.costCode',
        ]);

        $workflowable = $instanceStage->instance?->workflowable;

        if ($workflowable instanceof RetirementRequest) {
            $workflowable->load('paymentRequest.currency');
        }

        return view('tenant.approvals.show', compact('instanceStage'));
    }

    public function eligibleApprovers(
        EligibleApproversRequest $request,
        WorkflowInstanceStage $instanceStage,
    ): View {
        $instanceStage->load([
            'stage.roles',
            'stage.fallbackRoles',
            'recoveryRoles',
            'instance.template',
            'instance.workflowable',
        ]);

        /** @var User $user */
        $user = $request->user();
        abort_unless($this->userCanAccessRequest($instanceStage, $user), 403);
        abort_unless($instanceStage->isActive(), 422);

        $search = $request->search();
        $eligibleApproverCount = $this->approvers->eligibleUserCount($instanceStage);
        $eligibleApprovers = $this->approvers->paginatedEligibleUsers($instanceStage, $search);
        /** @var WorkflowStage $stage */
        $stage = $instanceStage->stage;
        $approverRoles = $instanceStage->approver_pool === WorkflowApproverResolver::FallbackPool
            ? $stage->fallbackRoles->merge($instanceStage->recoveryRoles)->unique('id')->values()
            : $stage->roles;

        return view('tenant.approvals.eligible-approvers', compact(
            'instanceStage',
            'eligibleApprovers',
            'eligibleApproverCount',
            'approverRoles',
            'search',
        ));
    }

    public function store(ApprovalActionRequest $request, WorkflowInstanceStage $instanceStage): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        abort_unless($this->engine->canUserActOnStage($instanceStage, $user), 403);
        abort_unless($instanceStage->isActive(), 403);

        $dto = $request->toDto();

        try {
            match ($dto->action) {
                'approve' => $this->engine->approve($instanceStage, $user, $dto->comment),
                'reject' => $this->engine->reject($instanceStage, $user, (string) $dto->comment),
                'send_back' => $this->engine->sendBack($instanceStage, $user, (string) $dto->comment),
                default => null,
            };
        } catch (SendBackNotAllowedException) {
            return back()->with('error', __('flash.approvals.send_back_not_allowed'));
        }

        /** @var \Illuminate\Database\Eloquent\Model $subject */
        $subject = $instanceStage->instance?->workflowable;

        $route = $subject instanceof RetirementRequest
            ? route('retirement-requests.show', $subject)
            : route('payment-requests.show', $subject);

        return redirect($route)->with('success', __('flash.approvals.action_recorded'));
    }

    public function retry(WorkflowInstanceStage $instanceStage): RedirectResponse
    {
        abort_unless($instanceStage->isBlocked(), 422);

        if (! $this->engine->retryBlockedStage($instanceStage)) {
            return back()->with('error', __('flash.approvals.retry_still_blocked'));
        }

        return back()->with('success', __('flash.approvals.retry_activated'));
    }

    public function createRecovery(WorkflowInstanceStage $instanceStage): View
    {
        abort_unless($instanceStage->isBlocked(), 422);

        $instanceStage->load(['stage.roles', 'stage.fallbackRoles', 'instance.template', 'instance.workflowable']);
        /** @var WorkflowStage $stage */
        $stage = $instanceStage->stage;
        $configuredRoleIds = $stage->roles
            ->merge($stage->fallbackRoles)
            ->pluck('id');
        $roles = $this->roles->allOrderedByName()
            ->reject(fn(Role $role): bool => $configuredRoleIds->contains($role->id));

        return view('tenant.approvals.recovery', compact('instanceStage', 'roles'));
    }

    public function recover(
        WorkflowStageRecoveryRequest $request,
        WorkflowInstanceStage $instanceStage,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();
        $role = Role::findOrFail($request->roleId());
        $activated = $this->recovery->applyAndRetry($instanceStage, $role, $user, $request->reason());

        return redirect($this->requestUrl($instanceStage))
            ->with($activated ? 'success' : 'error', $activated
                ? __('flash.approvals.recovery_activated')
                : __('flash.approvals.recovery_still_blocked'));
    }

    public function repairTemplate(
        WorkflowTemplateRecoveryRequest $request,
        WorkflowInstanceStage $instanceStage,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();
        $role = Role::findOrFail($request->roleId());
        $template = $this->recovery->prepareTemplateRepair($instanceStage, $role, $user);

        return redirect()->route('workflow-templates.show', $template)
            ->with($template->isDraft() ? 'warning' : 'success', $template->isDraft()
                ? __('flash.approvals.template_repair_draft_ready')
                : __('flash.approvals.template_repair_already_applied'));
    }

    private function requestUrl(WorkflowInstanceStage $instanceStage): string
    {
        $subject = $instanceStage->instance?->workflowable;

        return $subject instanceof RetirementRequest
            ? route('retirement-requests.show', $subject)
            : route('payment-requests.show', $subject);
    }

    private function userCanAccessRequest(WorkflowInstanceStage $instanceStage, User $user): bool
    {
        $subject = $instanceStage->instance?->workflowable;
        if ($subject instanceof RetirementRequest) {
            $subject->loadMissing('paymentRequest');
        }

        $branchId = match (true) {
            $subject instanceof PaymentRequest => $subject->branch_id,
            $subject instanceof RetirementRequest => $subject->paymentRequest?->branch_id,
            default => null,
        };

        return $branchId !== null
            && in_array($branchId, $this->branchScope->allowedBranchIds($user), true);
    }
}
