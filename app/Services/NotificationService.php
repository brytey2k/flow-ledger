<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\User;
use App\Models\Tenant\WorkflowInstanceStage;
use App\Models\Tenant\WorkflowStage;
use App\Notifications\RequestApprovedNotification;
use App\Notifications\RequestDisbursedNotification;
use App\Notifications\RequestRejectedNotification;
use App\Notifications\RequestSentBackNotification;
use App\Notifications\RetirementApprovedNotification;
use App\Notifications\RetirementRequiredNotification;
use App\Notifications\StageReadyForApprovalNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class NotificationService
{
    public function notifyStageApprovers(WorkflowInstanceStage $instanceStage): void
    {
        /** @var WorkflowStage $stage */
        $stage = $instanceStage->stage;
        $roleIds = $stage->roles()->pluck('roles.id');

        if ($roleIds->isEmpty()) {
            return;
        }

        /** @var \App\Models\Tenant\WorkflowInstance $instance */
        $instance = $instanceStage->instance;

        $users = User::whereHas('roles', fn($q) => $q->whereIn('id', $roleIds))
            ->whereNotNull('email')
            ->when(
                $stage->scope_to_department,
                function ($query) use ($instance): void {
                    $deptId = $instance->submitter?->staffProfile?->department_id;
                    if ($deptId !== null) {
                        $query->whereHas('staffProfile', fn($q) => $q->where('department_id', $deptId));
                    } else {
                        $query->whereRaw('0 = 1');
                    }
                },
            )
            ->when(
                $stage->scope_to_branch,
                function ($query) use ($instance): void {
                    $branchId = $instance->workflowable?->getAttribute('branch_id');
                    if ($branchId !== null) {
                        $query->whereHas('staffProfile', fn($q) => $q->where('branch_id', $branchId));
                    } else {
                        $query->whereRaw('0 = 1');
                    }
                },
            )
            ->get();

        NotificationFacade::send($users, new StageReadyForApprovalNotification($instanceStage));
    }

    public function notifyFullyApproved(Model $subject): void
    {
        $submitter = $this->resolveSubmitter($subject);
        if ($submitter) {
            if ($subject instanceof RetirementRequest) {
                $submitter->notify(new RetirementApprovedNotification($subject));
            } else {
                $submitter->notify(new RequestApprovedNotification($subject));
            }
        }
    }

    public function notifyRejected(Model $subject, string $comment): void
    {
        $submitter = $this->resolveSubmitter($subject);
        $submitter?->notify(new RequestRejectedNotification($subject, $comment));
    }

    public function notifySentBack(Model $subject, string $comment): void
    {
        $submitter = $this->resolveSubmitter($subject);
        $submitter?->notify(new RequestSentBackNotification($subject, $comment));
    }

    public function notifyDisbursed(Model $subject): void
    {
        $submitter = $this->resolveSubmitter($subject);
        if ($submitter) {
            $submitter->notify(new RequestDisbursedNotification($subject));

            if ($subject instanceof PaymentRequest && $subject->isAdvance()) {
                $submitter->notify(new RetirementRequiredNotification($subject));
            }
        }
    }

    private function resolveSubmitter(Model $subject): User|null
    {
        $event = $subject instanceof RetirementRequest ? 'retirement.submitted' : 'request.submitted';

        if ($subject instanceof PaymentRequest || $subject instanceof RetirementRequest) {
            /** @var \Spatie\Activitylog\Models\Activity|null $activity */
            $activity = $subject->activities()
                ->where('event', $event)
                ->latest()
                ->first();

            return $activity?->causer instanceof User ? $activity->causer : null;
        }

        return null;
    }
}
