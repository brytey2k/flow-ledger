<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\User;
use App\Models\Tenant\WorkflowInstanceStage;
use App\Repositories\WorkflowApproverRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class WorkflowApproverResolver
{
    public const string PrimaryPool = 'primary';

    public const string FallbackPool = 'fallback';

    public function __construct(private readonly WorkflowApproverRepository $repository) {}

    /** @return Collection<int, User> */
    public function eligibleUsers(WorkflowInstanceStage $instanceStage, string|null $pool = null): Collection
    {
        return $this->repository->eligibleUsers(
            $instanceStage,
            $pool ?? $instanceStage->approver_pool ?? self::PrimaryPool,
        );
    }

    public function canAct(WorkflowInstanceStage $instanceStage, User $user): bool
    {
        return $this->repository->userIsEligible(
            $instanceStage,
            $instanceStage->approver_pool ?? self::PrimaryPool,
            $user,
        );
    }

    public function eligibleUserCount(WorkflowInstanceStage $instanceStage): int
    {
        return $this->repository->eligibleUserCount(
            $instanceStage,
            $instanceStage->approver_pool ?? self::PrimaryPool,
        );
    }

    /** @return LengthAwarePaginator<int, User> */
    public function paginatedEligibleUsers(
        WorkflowInstanceStage $instanceStage,
        string|null $search = null,
        int $perPage = 25,
    ): LengthAwarePaginator {
        return $this->repository->paginatedEligibleUsers(
            $instanceStage,
            $instanceStage->approver_pool ?? self::PrimaryPool,
            $search,
            $perPage,
        );
    }

    public function canDisburse(PaymentRequest $paymentRequest, User $user): bool
    {
        return ! $this->repository->hasParticipatedIn($paymentRequest, $user);
    }

    public function resolvePool(WorkflowInstanceStage $instanceStage): bool
    {
        if ($this->eligibleUsers($instanceStage, self::PrimaryPool)->isNotEmpty()) {
            $this->activate($instanceStage, self::PrimaryPool);

            return true;
        }

        if ($this->eligibleUsers($instanceStage, self::FallbackPool)->isNotEmpty()) {
            $this->activate($instanceStage, self::FallbackPool);

            return true;
        }

        $instanceStage->update([
            'status' => 'blocked',
            'approver_pool' => null,
            'blocked_reason' => 'no_independent_approver',
            'blocked_at' => now(),
            'completed_at' => null,
        ]);

        return false;
    }

    /** @throws AuthorizationException */
    public function claim(WorkflowInstanceStage $instanceStage, User $user): void
    {
        $existingClaim = $this->repository->findClaim($instanceStage, $user);

        if ($existingClaim !== null) {
            if ($existingClaim->workflow_instance_stage_id === $instanceStage->id) {
                return;
            }

            throw new AuthorizationException('You have already acted at another stage of this workflow.');
        }

        $this->repository->createClaim($instanceStage, $user);
    }

    private function activate(WorkflowInstanceStage $instanceStage, string $pool): void
    {
        $instanceStage->update([
            'status' => 'active',
            'approver_pool' => $pool,
            'blocked_reason' => null,
            'blocked_at' => null,
            'started_at' => $instanceStage->started_at ?? now(),
            'completed_at' => null,
        ]);
    }
}
