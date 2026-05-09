<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant\User;
use App\Models\Tenant\WorkflowAction;
use App\Models\Tenant\WorkflowInstance;
use App\Models\Tenant\WorkflowInstanceStage;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WorkflowEngineService
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function startWorkflow(Model $subject, WorkflowTemplate $template): WorkflowInstance
    {
        return DB::transaction(function () use ($subject, $template): WorkflowInstance {
            $instance = WorkflowInstance::create([
                'workflow_template_id' => $template->id,
                'workflowable_type' => $subject::class,
                'workflowable_id' => $subject->getKey(),
                'status' => 'in_progress',
            ]);

            foreach ($template->stages as $stage) {
                WorkflowInstanceStage::create([
                    'workflow_instance_id' => $instance->id,
                    'workflow_stage_id' => $stage->id,
                    'status' => 'pending',
                ]);
            }

            /** @var WorkflowInstance $freshInstance */
            $freshInstance = $instance->fresh('instanceStages.stage');
            $this->activateNextStages($freshInstance);

            return $instance;
        });
    }

    public function activateNextStages(WorkflowInstance $instance): void
    {
        $pendingStages = $instance->instanceStages()
            ->where('status', 'pending')
            ->with('stage')
            ->get()
            ->sortBy('stage.display_order');

        if ($pendingStages->isEmpty()) {
            return;
        }

        /** @var WorkflowInstanceStage $firstStage */
        $firstStage = $pendingStages->first();
        /** @var WorkflowStage $firstStageDef */
        $firstStageDef = $firstStage->stage;
        $minOrder = $firstStageDef->display_order;
        $nextBatch = $pendingStages->filter(fn($is) => $is->stage instanceof WorkflowStage && $is->stage->display_order === $minOrder);

        /** @var Model $workflowable */
        $workflowable = $instance->workflowable;
        $requestAmount = $this->resolveRequestAmount($workflowable);

        $anyActivated = false;
        $activatedStages = new Collection();

        foreach ($nextBatch as $instanceStage) {
            /** @var WorkflowStage $stageDef */
            $stageDef = $instanceStage->stage;
            $threshold = $stageDef->skip_below_amount;
            if ($threshold !== null && $requestAmount < (float) $threshold) {
                $instanceStage->update(['status' => 'skipped', 'completed_at' => now()]);
            } else {
                $instanceStage->update(['status' => 'active', 'started_at' => now()]);
                $activatedStages->push($instanceStage);
                $anyActivated = true;
            }
        }

        if (! $anyActivated) {
            /** @var WorkflowInstance $freshInstance */
            $freshInstance = $instance->fresh('instanceStages.stage');
            $this->activateNextStages($freshInstance);

            return;
        }

        foreach ($activatedStages as $activated) {
            /** @var WorkflowInstanceStage $freshActivated */
            $freshActivated = $activated->fresh('stage.roles');
            $this->notifications->notifyStageApprovers($freshActivated);
        }
    }

    public function approve(WorkflowInstanceStage $instanceStage, User $user, string|null $comment = null): void
    {
        DB::transaction(function () use ($instanceStage, $user, $comment): void {
            WorkflowAction::create([
                'workflow_instance_stage_id' => $instanceStage->id,
                'user_id' => $user->id,
                'action' => 'approve',
                'comment' => $comment,
            ]);

            $instanceStage->update(['status' => 'approved', 'completed_at' => now()]);

            /** @var WorkflowStage $stage */
            $stage = $instanceStage->stage;
            /** @var WorkflowInstance $instance */
            $instance = $instanceStage->instance;

            /** @var Model $approveWorkflowable */
            $approveWorkflowable = $instance->workflowable;
            activity()
                ->performedOn($approveWorkflowable)
                ->causedBy($user)
                ->event('stage.approved')
                ->withProperties(['stage' => $stage->name, 'comment' => $comment])
                ->log('Stage approved');

            if ($stage->parallel_group_id !== null) {
                /** @var \App\Models\Tenant\WorkflowParallelGroup $group */
                $group = $stage->parallelGroup;

                $siblings = $instance->instanceStages()
                    ->whereHas('stage', fn($q) => $q->where('parallel_group_id', $stage->parallel_group_id))
                    ->where('id', '!=', $instanceStage->id)
                    ->get();

                if ($group->require_all) {
                    $allResolved = $siblings->every(
                        fn(WorkflowInstanceStage $s) => in_array($s->status, ['approved', 'skipped', 'cancelled'], true),
                    );
                    if (! $allResolved) {
                        return;
                    }
                } else {
                    $siblings->each(function (WorkflowInstanceStage $sibling): void {
                        if (in_array($sibling->status, ['pending', 'active'], true)) {
                            $sibling->update(['status' => 'cancelled', 'completed_at' => now()]);
                        }
                    });
                }
            }

            /** @var WorkflowInstance $freshInstance */
            $freshInstance = $instance->fresh();
            $this->advanceWorkflow($freshInstance);
        });
    }

    public function reject(WorkflowInstanceStage $instanceStage, User $user, string $comment): void
    {
        $workflowable = DB::transaction(function () use ($instanceStage, $user, $comment): Model {
            WorkflowAction::create([
                'workflow_instance_stage_id' => $instanceStage->id,
                'user_id' => $user->id,
                'action' => 'reject',
                'comment' => $comment,
            ]);

            $instanceStage->update(['status' => 'rejected', 'completed_at' => now()]);

            /** @var WorkflowInstance $instance */
            $instance = $instanceStage->instance;
            $instance->instanceStages()
                ->whereIn('status', ['pending', 'active'])
                ->update(['status' => 'cancelled', 'completed_at' => now()]);

            $instance->update(['status' => 'cancelled']);
            /** @var Model $workflowable */
            $workflowable = $instance->workflowable;
            $workflowable->update(['status' => 'cancelled']);

            /** @var WorkflowStage $stage */
            $stage = $instanceStage->stage;

            activity()
                ->performedOn($workflowable)
                ->causedBy($user)
                ->event('stage.rejected')
                ->withProperties([
                    'old_status' => 'in_workflow',
                    'new_status' => 'cancelled',
                    'stage' => $stage->name,
                    'comment' => $comment,
                ])
                ->log('Stage rejected');

            return $workflowable;
        });

        $this->notifications->notifyRejected($workflowable, $comment);
    }

    public function sendBack(WorkflowInstanceStage $instanceStage, User $user, string $comment): void
    {
        $workflowable = DB::transaction(function () use ($instanceStage, $user, $comment): Model {
            WorkflowAction::create([
                'workflow_instance_stage_id' => $instanceStage->id,
                'user_id' => $user->id,
                'action' => 'send_back',
                'comment' => $comment,
            ]);

            $instanceStage->update(['status' => 'sent_back', 'completed_at' => now()]);

            /** @var WorkflowInstance $instance */
            $instance = $instanceStage->instance;
            $instance->update(['sent_back_to_stage_id' => $instanceStage->id]);
            /** @var Model $workflowable */
            $workflowable = $instance->workflowable;
            $workflowable->update(['status' => 'sent_back']);

            /** @var WorkflowStage $stage */
            $stage = $instanceStage->stage;

            activity()
                ->performedOn($workflowable)
                ->causedBy($user)
                ->event('stage.sent_back')
                ->withProperties([
                    'old_status' => 'in_workflow',
                    'new_status' => 'sent_back',
                    'stage' => $stage->name,
                    'comment' => $comment,
                ])
                ->log('Sent back for revision');

            return $workflowable;
        });

        $this->notifications->notifySentBack($workflowable, $comment);
    }

    public function resubmitAfterFix(Model $subject, User $user): void
    {
        DB::transaction(function () use ($subject, $user): void {
            $instance = WorkflowInstance::where('workflowable_type', $subject::class)
                ->where('workflowable_id', $subject->getKey())
                ->where('status', 'in_progress')
                ->firstOrFail();

            $sentBackStage = WorkflowInstanceStage::findOrFail($instance->sent_back_to_stage_id);

            $sentBackStage->update([
                'status' => 'active',
                'started_at' => now(),
                'completed_at' => null,
            ]);

            $instance->update(['sent_back_to_stage_id' => null]);
            $subject->update(['status' => 'in_workflow']);

            activity()
                ->performedOn($subject)
                ->causedBy($user)
                ->event('request.resubmitted')
                ->withProperties(['old_status' => 'sent_back', 'new_status' => 'in_workflow'])
                ->log('Resubmitted for approval');
        });
    }

    public function canUserActOnStage(WorkflowInstanceStage $instanceStage, User $user): bool
    {
        /** @var WorkflowStage $stage */
        $stage = $instanceStage->stage;
        $roleIds = $stage->roles()->pluck('roles.id');

        return $user->roles()->whereIn('id', $roleIds)->exists();
    }

    private function advanceWorkflow(WorkflowInstance $instance): void
    {
        $activeCount = $instance->instanceStages()->where('status', 'active')->count();
        $pendingCount = $instance->instanceStages()->where('status', 'pending')->count();

        if ($activeCount === 0 && $pendingCount === 0) {
            $this->markInstanceCompleted($instance);

            return;
        }

        if ($activeCount === 0) {
            /** @var WorkflowInstance $freshInstance */
            $freshInstance = $instance->fresh('instanceStages.stage');
            $this->activateNextStages($freshInstance);
        }
    }

    private function markInstanceCompleted(WorkflowInstance $instance): void
    {
        $instance->update(['status' => 'completed']);

        /** @var Model $subject */
        $subject = $instance->workflowable;
        $subject->update(['status' => 'approved', 'approved_at' => now()]);

        activity()
            ->performedOn($subject)
            ->event('request.approved')
            ->withProperties(['old_status' => 'in_workflow', 'new_status' => 'approved'])
            ->log('Fully approved');

        $this->notifications->notifyFullyApproved($subject);
    }

    private function resolveRequestAmount(Model $subject): float
    {
        $amount = $subject->getAttribute('total_amount');

        return is_numeric($amount) ? (float) $amount : 0.0;
    }
}
