<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\User;
use App\Models\Tenant\WorkflowInstanceActorClaim;
use App\Models\Tenant\WorkflowInstanceStage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as QueryBuilder;

class WorkflowApproverRepository
{
    /**
     * @param Builder<PaymentRequest> $query
     * @param User $user
     *
     * @return Builder<PaymentRequest>
     */
    public function excludePaymentRequestsParticipatedIn(Builder $query, User $user): Builder
    {
        return $query->whereDoesntHave('workflowInstances', function (Builder $instances) use ($user): void {
            $instances->where(function (Builder $participation) use ($user): void {
                $participation->where('submitter_user_id', $user->id)
                    ->orWhereHas('actorClaims', fn(Builder $claims) => $claims->where('user_id', $user->id));
            });
        });
    }

    public function hasParticipatedIn(PaymentRequest $paymentRequest, User $user): bool
    {
        return $paymentRequest->workflowInstances()
            ->where(function (Builder $instances) use ($user): void {
                $instances->where('submitter_user_id', $user->id)
                    ->orWhereHas('actorClaims', fn(Builder $claims) => $claims->where('user_id', $user->id));
            })
            ->exists();
    }

    /** @return Builder<WorkflowInstanceStage> */
    public function eligibleStagesForUser(User $user): Builder
    {
        $roleIds = $user->roles()->pluck('roles.id');
        $staffProfile = $user->staffProfile;
        $staffDepartmentId = $staffProfile?->department_id;
        $staffBranchId = $staffProfile?->branch_id;

        return WorkflowInstanceStage::query()
            ->join('workflow_stages as ws', 'workflow_instance_stages.workflow_stage_id', '=', 'ws.id')
            ->join('workflow_instances as wi', 'workflow_instance_stages.workflow_instance_id', '=', 'wi.id')
            ->select('workflow_instance_stages.*')
            ->where('workflow_instance_stages.status', 'active')
            ->where(function (Builder $query) use ($roleIds): void {
                $query->where(function (Builder $primary) use ($roleIds): void {
                    $primary->where('workflow_instance_stages.approver_pool', 'primary')
                        ->whereHas('stage.roles', fn(Builder $roles) => $roles->whereIn('roles.id', $roleIds));
                })->orWhere(function (Builder $fallback) use ($roleIds): void {
                    $fallback->where('workflow_instance_stages.approver_pool', 'fallback')
                        ->where(function (Builder $rolesQuery) use ($roleIds): void {
                            $rolesQuery->whereHas('stage.fallbackRoles', fn(Builder $roles) => $roles->whereIn('roles.id', $roleIds))
                                ->orWhereHas('recoveryRoles', fn(Builder $roles) => $roles->whereIn('roles.id', $roleIds));
                        });
                });
            })
            ->where(function (Builder $query) use ($user): void {
                $query->whereNull('wi.submitter_user_id')
                    ->orWhere('wi.submitter_user_id', '!=', $user->id);
            })
            ->where(function (Builder $query) use ($staffDepartmentId): void {
                $query->where('ws.scope_to_department', false)
                    ->when(
                        $staffDepartmentId !== null,
                        fn(Builder $query) => $query->orWhere('wi.department_id', $staffDepartmentId),
                    );
            })
            ->where(function (Builder $query) use ($staffBranchId): void {
                $query->where('ws.scope_to_branch', false)
                    ->when(
                        $staffBranchId !== null,
                        fn(Builder $query) => $query->orWhere('wi.branch_id', $staffBranchId),
                    );
            })
            ->whereNotExists(function (QueryBuilder $query) use ($user): void {
                $query->selectRaw('1')
                    ->from('workflow_actions as prior_actions')
                    ->join('workflow_instance_stages as prior_stages', 'prior_stages.id', '=', 'prior_actions.workflow_instance_stage_id')
                    ->whereColumn('prior_stages.workflow_instance_id', 'wi.id')
                    ->whereColumn('prior_stages.id', '!=', 'workflow_instance_stages.id')
                    ->where('prior_actions.user_id', $user->id);
            })
            ->whereNotExists(function (QueryBuilder $query) use ($user): void {
                $query->selectRaw('1')
                    ->from('workflow_instance_actor_claims as actor_claims')
                    ->whereColumn('actor_claims.workflow_instance_id', 'wi.id')
                    ->whereColumn('actor_claims.workflow_instance_stage_id', '!=', 'workflow_instance_stages.id')
                    ->where('actor_claims.user_id', $user->id);
            });
    }

    /** @return Collection<int, User> */
    public function eligibleUsers(WorkflowInstanceStage $instanceStage, string $pool): Collection
    {
        /** @var \App\Models\Tenant\WorkflowInstance $instance */
        $instance = $instanceStage->instance;
        /** @var \App\Models\Tenant\WorkflowStage $stage */
        $stage = $instanceStage->stage;
        $roleIds = $pool === 'fallback'
            ? $stage->fallbackRoles()->pluck('roles.id')->merge(
                $instanceStage->recoveryRoles()->pluck('roles.id'),
            )->unique()
            : $stage->roles()->pluck('roles.id');

        if ($roleIds->isEmpty()) {
            return new Collection();
        }

        return User::query()
            ->whereHas('roles', fn(Builder $query) => $query->whereIn('roles.id', $roleIds))
            ->when(
                $instance->submitter_user_id !== null,
                fn(Builder $query) => $query->whereKeyNot($instance->submitter_user_id),
            )
            ->when(
                $stage->scope_to_department,
                fn(Builder $query) => $instance->department_id === null
                    ? $query->whereRaw('0 = 1')
                    : $query->whereHas('staffProfile', fn(Builder $staff) => $staff->where('department_id', $instance->department_id)),
            )
            ->when(
                $stage->scope_to_branch,
                fn(Builder $query) => $instance->branch_id === null
                    ? $query->whereRaw('0 = 1')
                    : $query->whereHas('staffProfile', fn(Builder $staff) => $staff->where('branch_id', $instance->branch_id)),
            )
            ->whereNotExists(function (QueryBuilder $query) use ($instance, $instanceStage): void {
                $query->selectRaw('1')
                    ->from('workflow_actions as prior_actions')
                    ->join('workflow_instance_stages as prior_stages', 'prior_stages.id', '=', 'prior_actions.workflow_instance_stage_id')
                    ->whereColumn('prior_actions.user_id', 'users.id')
                    ->where('prior_stages.workflow_instance_id', $instance->id)
                    ->where('prior_stages.id', '!=', $instanceStage->id);
            })
            ->whereNotExists(function (QueryBuilder $query) use ($instance, $instanceStage): void {
                $query->selectRaw('1')
                    ->from('workflow_instance_actor_claims as actor_claims')
                    ->whereColumn('actor_claims.user_id', 'users.id')
                    ->where('actor_claims.workflow_instance_id', $instance->id)
                    ->where('actor_claims.workflow_instance_stage_id', '!=', $instanceStage->id);
            })
            ->get();
    }

    public function findClaim(WorkflowInstanceStage $instanceStage, User $user): WorkflowInstanceActorClaim|null
    {
        return WorkflowInstanceActorClaim::query()
            ->where('workflow_instance_id', $instanceStage->workflow_instance_id)
            ->where('user_id', $user->id)
            ->first();
    }

    public function createClaim(WorkflowInstanceStage $instanceStage, User $user): WorkflowInstanceActorClaim
    {
        return WorkflowInstanceActorClaim::create([
            'workflow_instance_id' => $instanceStage->workflow_instance_id,
            'user_id' => $user->id,
            'workflow_instance_stage_id' => $instanceStage->id,
        ]);
    }
}
