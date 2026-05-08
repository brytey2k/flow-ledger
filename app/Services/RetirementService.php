<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\User;
use App\Models\Tenant\WorkflowTemplate;

class RetirementService
{
    public function __construct(
        private readonly WorkflowEngineService $engine,
    ) {}

    /**
     * @param array{notes: string|null, items: array<int, array{description: string, amount: float|string, account_code_id: int, receipt_number: string|null}>} $data
     * @param PaymentRequest $paymentRequest
     * @param User|null|null $user
     */
    public function createDraft(PaymentRequest $paymentRequest, array $data, User|null $user = null): RetirementRequest
    {
        $items = $data['items'];
        $sumResult = collect($items)->sum('amount');
        $totalExpended = is_numeric($sumResult) ? (float) $sumResult : 0.0;
        $rawAmount = $paymentRequest->getAttribute('total_amount');
        $advanceAmount = is_numeric($rawAmount) ? (float) $rawAmount : 0.0;
        $diff = round($totalExpended - $advanceAmount, 2);

        $differenceType = match (true) {
            $diff > 0 => 'pay_to_staff',
            $diff < 0 => 'refund_to_company',
            default => 'nil',
        };

        $retirement = RetirementRequest::create([
            'payment_request_id' => $paymentRequest->id,
            'status' => 'draft',
            'total_amount_expended' => $totalExpended,
            'difference_amount' => abs($diff),
            'difference_type' => $differenceType,
            'notes' => $data['notes'] ?? null,
        ]);

        foreach ($items as $item) {
            $retirement->items()->create($item);
        }

        activity()
            ->performedOn($retirement)
            ->causedBy($user)
            ->event('retirement.created')
            ->withProperties(['new_status' => 'draft'])
            ->log('Retirement created as draft');

        return $retirement;
    }

    public function submit(RetirementRequest $retirement, User|null $user = null): void
    {
        $template = WorkflowTemplate::where('type', 'retirement')->firstOrFail();

        $retirement->update([
            'status' => 'in_workflow',
            'submitted_at' => now(),
        ]);

        activity()
            ->performedOn($retirement)
            ->causedBy($user)
            ->event('retirement.submitted')
            ->withProperties(['old_status' => 'draft', 'new_status' => 'in_workflow'])
            ->log('Submitted for approval');

        $this->engine->startWorkflow($retirement, $template);
    }

    public function settle(RetirementRequest $retirement, string|null $notes, User|null $user = null): void
    {
        $retirement->update([
            'status' => 'settled',
            'settled_at' => now(),
            'settled_by_user_id' => $user?->id,
            'settlement_notes' => $notes,
        ]);

        activity()
            ->performedOn($retirement)
            ->causedBy($user)
            ->event('retirement.settled')
            ->withProperties(['old_status' => 'approved', 'new_status' => 'settled'])
            ->log('Difference settled');
    }

    public function cancel(RetirementRequest $retirement, User|null $user = null): void
    {
        $oldStatus = $retirement->status;
        $activeInstance = $retirement->activeWorkflowInstance;

        if ($activeInstance instanceof \App\Models\Tenant\WorkflowInstance) {
            $activeInstance->instanceStages()
                ->whereIn('status', ['pending', 'active'])
                ->update(['status' => 'cancelled', 'completed_at' => now()]);

            $activeInstance->update(['status' => 'cancelled']);
        }

        $retirement->update(['status' => 'cancelled']);

        activity()
            ->performedOn($retirement)
            ->causedBy($user)
            ->event('retirement.cancelled')
            ->withProperties(['old_status' => $oldStatus, 'new_status' => 'cancelled'])
            ->log('Retirement cancelled');
    }
}
